"""
Saudi Bank Rates Scraper
========================
Scrapes personal finance, buyout, and mortgage rates from ALL 8 Saudi banks
using Playwright (headless Chromium) for JavaScript-rendered pages.
- Auto-scrapes all banks via Playwright
- Derives APR from Flat rate when needed (and vice versa)
- Keeps history log of all rates per run
- On error: keeps last known good value, marks status = 'stale'
"""

import json
import re
import os
import math
import logging
from datetime import date, datetime
from pathlib import Path
from copy import deepcopy

from playwright.sync_api import sync_playwright, Page, Browser
from scipy.optimize import brentq

# -- Logging ---------------------------------------------------------------
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)
log = logging.getLogger(__name__)

# -- Paths ------------------------------------------------------------------
BASE_DIR     = Path(__file__).parent
RATES_FILE   = BASE_DIR / "data" / "rates.json"
HISTORY_FILE = BASE_DIR / "data" / "history.jsonl"

# Plausible ranges used to reject garbage matches from page text/tables
APR_RANGE  = (0.5, 30.0)
FLAT_RANGE = (0.5, 10.0)

# Page load timeout (ms)
PAGE_TIMEOUT = 30_000
# Extra wait after load for JS rendering (ms)
RENDER_WAIT  = 3_000


# ==========================================================================
# APR <-> FLAT CONVERSION
# ==========================================================================

def flat_to_apr(flat_pct: float, years: int = 5) -> float:
    """Convert Flat rate % to APR % using NPV equation."""
    months = years * 12
    flat   = flat_pct / 100
    principal = 100_000  # arbitrary principal

    total_interest = principal * flat * years
    emi = (principal + total_interest) / months

    def equation(r):
        if r < 1e-9:
            return emi * months - principal
        return emi * (1 - (1 + r) ** -months) / r - principal

    try:
        monthly_r = brentq(equation, 1e-9, 0.5)
        return round(monthly_r * 12 * 100, 2)
    except Exception:
        # Fallback approximation: APR ~ Flat * 1.85
        return round(flat_pct * 1.85, 2)


def apr_to_flat(apr_pct: float, years: int = 5) -> float:
    """Convert APR % to approximate Flat rate %."""
    months = years * 12
    r = apr_pct / 100 / 12
    principal = 100_000

    if r < 1e-9:
        emi = principal / months
    else:
        emi = r * principal / (1 - (1 + r) ** -months)

    total_pay      = emi * months
    total_interest = total_pay - principal
    flat           = total_interest / (principal * years)
    return round(flat * 100, 2)


# ==========================================================================
# RATE EXTRACTION HELPERS
# ==========================================================================

def extract_pct(text: str) -> float | None:
    """Extract first percentage number from text string."""
    m = re.search(r"(\d+\.?\d*)\s*%", text)
    return float(m.group(1)) if m else None


def first_pct_in_range(text: str, low: float, high: float) -> float | None:
    """Return the first percentage in text within [low, high]."""
    for m in re.finditer(r"(\d+\.?\d*)\s*%", text):
        val = float(m.group(1))
        if low <= val <= high:
            return val
    return None


def all_pcts_in_range(text: str, low: float, high: float) -> list[float]:
    """Return all percentages in text within [low, high]."""
    results = []
    for m in re.finditer(r"(\d+\.?\d*)\s*%", text):
        val = float(m.group(1))
        if low <= val <= high:
            results.append(val)
    return results


def extract_apr_from_text(text: str) -> float | None:
    """
    Try multiple regex strategies to find an APR value:
    1. "معدل النسبة السنوي" + number%
    2. "APR" + number%
    3. "يبدأ من" + number%
    """
    patterns = [
        r"معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%",
        r"APR[^%\d]{0,30}(\d+\.?\d*)\s*%",
        r"يبدأ\s*من[^%\d]{0,20}(\d+\.?\d*)\s*%",
        r"نسبة[^%\d]{0,30}(\d+\.?\d*)\s*%",
    ]
    for pat in patterns:
        m = re.search(pat, text, re.IGNORECASE)
        if m:
            val = float(m.group(1))
            if APR_RANGE[0] <= val <= APR_RANGE[1]:
                return val
    return None


def extract_flat_from_text(text: str) -> float | None:
    """
    Try multiple regex strategies to find a flat/profit margin rate:
    1. "هامش الربح" + number%
    2. "هامش ربح" + number%
    3. "معدل الربح" + number%
    """
    patterns = [
        r"هامش\s*(?:ال)?ربح[^%\d]{0,40}(\d+\.?\d*)\s*%",
        r"معدل\s*الربح[^%\d]{0,40}(\d+\.?\d*)\s*%",
        r"نسبة\s*الربح[^%\d]{0,40}(\d+\.?\d*)\s*%",
    ]
    for pat in patterns:
        m = re.search(pat, text, re.IGNORECASE)
        if m:
            val = float(m.group(1))
            if FLAT_RANGE[0] <= val <= FLAT_RANGE[1]:
                return val
    return None


def extract_rates_from_table(text: str) -> dict:
    """
    Try to extract APR and flat rates from tabular text.
    Returns dict with optional 'apr' and 'flat' keys.
    """
    result = {}
    apr = extract_apr_from_text(text)
    if apr:
        result["apr"] = apr
    flat = extract_flat_from_text(text)
    if flat:
        result["flat"] = flat
    return result


# ==========================================================================
# PAGE FETCHING HELPER
# ==========================================================================

def fetch_page_text(page: Page, url: str) -> str | None:
    """
    Navigate to URL using Playwright, wait for content to render,
    and return the full page text. Returns None on failure.
    """
    try:
        page.goto(url, timeout=PAGE_TIMEOUT, wait_until="domcontentloaded")
        page.wait_for_timeout(RENDER_WAIT)
        # Additional wait: try to wait for body content to be non-empty
        try:
            page.wait_for_selector("body", timeout=5000)
        except Exception:
            pass
        text = page.inner_text("body")
        return text
    except Exception as e:
        log.warning(f"Fetch failed [{url}]: {e}")
        return None


def try_urls(page: Page, urls: list[str]) -> str | None:
    """Try multiple URLs in order; return the first successful page text."""
    for url in urls:
        text = fetch_page_text(page, url)
        if text and len(text.strip()) > 100:
            return text
        log.info(f"  URL yielded no useful content, trying next: {url}")
    return None


# ==========================================================================
# BANK-SPECIFIC SCRAPERS
# ==========================================================================

def scrape_rajhi(page: Page) -> dict:
    """Al Rajhi Bank: Personal, Buyout, Mortgage."""
    result = {}

    # -- Personal --
    try:
        urls = [
            "https://www.alrajhibank.com.sa/Personal/Finance/Personal-Finance",
            "https://www.alrajhibank.com.sa/Personal/Finance/Personal-Finance/Personal-Finance",
        ]
        text = try_urls(page, urls)
        apr = None
        if text:
            apr = extract_apr_from_text(text)
            if apr is None:
                apr = first_pct_in_range(text, *APR_RANGE)
        if apr:
            result["personal"] = {"apr": apr, "apr_derived": False}
            log.info(f"Rajhi personal APR: {apr}%")
        else:
            log.warning("Rajhi personal: APR not found")
    except Exception as e:
        log.error(f"Rajhi personal scrape error: {e}")

    # -- Buyout --
    try:
        text = fetch_page_text(page, "https://www.alrajhibank.com.sa/Personal/Finance/Personal-Finance/Personal-Finance-Buyout")
        apr = None
        if text:
            apr = extract_apr_from_text(text)
            if apr is None:
                apr = first_pct_in_range(text, *APR_RANGE)
        if apr:
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Rajhi buyout APR: {apr}%")
    except Exception as e:
        log.error(f"Rajhi buyout scrape error: {e}")

    # -- Mortgage --
    try:
        text = fetch_page_text(page, "https://www.alrajhibank.com.sa/Personal/Finance/Real-Estate-Finance")
        flat = None
        if text:
            flat = extract_flat_from_text(text)
            if flat is None:
                # Try APR directly
                apr = extract_apr_from_text(text)
                if apr:
                    result["mortgage"] = {"apr": apr, "apr_derived": False}
                    log.info(f"Rajhi mortgage APR: {apr}%")
        if flat:
            apr = flat_to_apr(flat, years=20)
            result["mortgage"] = {"flat": flat, "apr": apr, "apr_derived": True}
            log.info(f"Rajhi mortgage flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"Rajhi mortgage scrape error: {e}")

    return result


def scrape_snb(page: Page) -> dict:
    """Saudi National Bank (Al Ahli): Personal, Buyout, Mortgage."""
    result = {}

    try:
        urls = [
            "https://www.alahli.com/ar/pages/personal-banking/finance/finance-and-saving-pricing-personal-finance",
            "https://www.alahli.com/ar/pages/personal-banking/finance/finance-and-saving-pricing",
            "https://www.alahli.com/ar/pages/personal-banking/finance/personal-finance",
        ]
        text = try_urls(page, urls)
        if not text:
            return {}

        # Look for personal finance APR
        # SNB page often has sections: personal, buyout, mortgage
        personal_apr = None
        buyout_apr = None
        mortgage_apr = None

        # Strategy 1: look for section-specific patterns
        m = re.search(r"(?:تمويل\s*شخصي|شخصي|personal)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            personal_apr = float(m.group(1))

        m = re.search(r"(?:مديونية|buyout|شراء)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            buyout_apr = float(m.group(1))

        m = re.search(r"(?:عقاري|mortgage|سكني)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            mortgage_apr = float(m.group(1))

        # Strategy 2: if section-specific failed, try generic extraction
        if personal_apr is None:
            personal_apr = extract_apr_from_text(text)

        # Strategy 3: try flat rate extraction
        flat = extract_flat_from_text(text)

        if personal_apr:
            result["personal"] = {"apr": personal_apr, "apr_derived": False}
            log.info(f"SNB personal APR: {personal_apr}%")
        elif flat:
            apr = flat_to_apr(flat)
            result["personal"] = {"flat": flat, "apr": apr, "apr_derived": True}
            log.info(f"SNB personal flat: {flat}%, derived APR: {apr}%")

        if buyout_apr:
            result["buyout"] = {"apr": buyout_apr, "apr_derived": False}
            log.info(f"SNB buyout APR: {buyout_apr}%")

        if mortgage_apr:
            result["mortgage"] = {"apr": mortgage_apr, "apr_derived": False}
            log.info(f"SNB mortgage APR: {mortgage_apr}%")

    except Exception as e:
        log.error(f"SNB scrape error: {e}")

    return result


def scrape_riyad(page: Page) -> dict:
    """Riyad Bank: APR disclosure page has all products."""
    result = {}

    try:
        text = fetch_page_text(page, "https://www.riyadbank.com/information/special-pages/arp-disclosure")
        if not text:
            return {}

        # Personal
        m = re.search(r"(?:شخصي|personal)[^%\d]{0,60}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["personal"] = {"apr": apr, "apr_derived": False}
            log.info(f"Riyad personal APR: {apr}%")

        # Buyout
        m = re.search(r"(?:مديونية|buyout)[^%\d]{0,60}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Riyad buyout APR: {apr}%")

        # Mortgage
        m = re.search(r"(?:عقاري|mortgage)[^%\d]{0,60}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["mortgage"] = {"apr": apr, "apr_derived": False}
            log.info(f"Riyad mortgage APR: {apr}%")

    except Exception as e:
        log.error(f"Riyad scrape error: {e}")

    return result


def scrape_anb(page: Page) -> dict:
    """Arab National Bank: Personal, Buyout, APR page."""
    result = {}

    # -- Personal --
    try:
        urls = [
            "https://anb.com.sa/ar/web/anb/personal-finance-offer",
            "https://anb.com.sa/web/anb/annual-profit-rate",
        ]
        text = try_urls(page, urls)
        if text:
            apr = extract_apr_from_text(text)
            if apr is None:
                # Try generic section match
                m = re.search(r"(?:شخصي|personal)[^%\d]{0,80}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
                if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
                    apr = float(m.group(1))
            flat = extract_flat_from_text(text)
            if apr:
                result["personal"] = {"apr": apr, "apr_derived": False}
                log.info(f"ANB personal APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat)
                result["personal"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"ANB personal flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"ANB personal scrape error: {e}")

    # -- Buyout --
    try:
        text = fetch_page_text(page, "https://anb.com.sa/ar/web/anb/personal-loan-offer2")
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["buyout"] = {"apr": apr, "apr_derived": False}
                log.info(f"ANB buyout APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat)
                result["buyout"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"ANB buyout flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"ANB buyout scrape error: {e}")

    # -- Mortgage (from APR page) --
    try:
        text = fetch_page_text(page, "https://anb.com.sa/web/anb/annual-profit-rate")
        if text:
            m = re.search(r"(?:عقاري|mortgage|سكني)[^%\d]{0,80}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
            if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
                apr = float(m.group(1))
                result["mortgage"] = {"apr": apr, "apr_derived": False}
                log.info(f"ANB mortgage APR: {apr}%")
            else:
                flat = extract_flat_from_text(text)
                if flat:
                    apr = flat_to_apr(flat, years=20)
                    result["mortgage"] = {"flat": flat, "apr": apr, "apr_derived": True}
                    log.info(f"ANB mortgage flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"ANB mortgage scrape error: {e}")

    return result


def scrape_alinma(page: Page) -> dict:
    """Alinma Bank: Prices page and personal finance page."""
    result = {}

    try:
        urls = [
            "https://alinma.com/Prices-Finance-and-Products",
            "https://www.alinma.com/Retail/Financing/Personal-Finance/Personal-Finance",
        ]
        text = try_urls(page, urls)
        if not text:
            return {}

        # Personal
        m = re.search(r"(?:شخصي)[^%\d]{0,100}يبدأ\s*من[^%\d]{0,20}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["personal"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alinma personal APR: {apr}%")
        else:
            apr = extract_apr_from_text(text)
            if apr:
                result["personal"] = {"apr": apr, "apr_derived": False}
                log.info(f"Alinma personal APR (generic): {apr}%")

        # Buyout
        m = re.search(r"(?:مديونية|buyout)[^%\d]{0,100}يبدأ\s*من[^%\d]{0,20}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alinma buyout APR: {apr}%")

        # Mortgage
        m = re.search(r"(?:عقاري|mortgage|سكني)[^%\d]{0,100}(?:يبدأ\s*من)?[^%\d]{0,20}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["mortgage"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alinma mortgage APR: {apr}%")

    except Exception as e:
        log.error(f"Alinma scrape error: {e}")

    return result


def scrape_jazira(page: Page) -> dict:
    """Bank Al Jazira: Personal finance page."""
    result = {}

    # -- Personal --
    try:
        urls = [
            "https://www.bankaljazira.com/ar/personal/finance/personal-finance",
            "https://www.aljazirabank.com.sa/ar-sa/personal/finances-ar-SA/cc/pf?cc=pf",
        ]
        text = try_urls(page, urls)
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["personal"] = {"apr": apr, "apr_derived": False}
                log.info(f"Jazira personal APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat)
                result["personal"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"Jazira personal flat: {flat}%, derived APR: {apr}%")
            else:
                # Try "starts from" pattern
                pct = first_pct_in_range(text, *APR_RANGE)
                if pct:
                    result["personal"] = {"apr": pct, "apr_derived": False}
                    log.info(f"Jazira personal APR (fallback): {pct}%")
    except Exception as e:
        log.error(f"Jazira personal scrape error: {e}")

    # -- Buyout --
    try:
        text = fetch_page_text(page, "https://www.bankaljazira.com/ar/personal/finance/buyout")
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["buyout"] = {"apr": apr, "apr_derived": False}
                log.info(f"Jazira buyout APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat)
                result["buyout"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"Jazira buyout flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"Jazira buyout scrape error: {e}")

    # -- Mortgage --
    try:
        text = fetch_page_text(page, "https://www.bankaljazira.com/ar/personal/finance/home-finance")
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["mortgage"] = {"apr": apr, "apr_derived": False}
                log.info(f"Jazira mortgage APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat, years=20)
                result["mortgage"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"Jazira mortgage flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"Jazira mortgage scrape error: {e}")

    return result


def scrape_bilad(page: Page) -> dict:
    """Bank Albilad: Personal finance page."""
    result = {}

    # -- Personal --
    try:
        text = fetch_page_text(page, "https://www.bankalbilad.com/ar/personal/financing/personal/Pages/default.aspx")
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["personal"] = {"apr": apr, "apr_derived": False}
                log.info(f"Bilad personal APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat)
                result["personal"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"Bilad personal flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"Bilad personal scrape error: {e}")

    # -- Buyout (same page or dedicated) --
    try:
        # Bilad may have buyout info on the same page; also try dedicated URL
        text = fetch_page_text(page, "https://www.bankalbilad.com/ar/personal/financing/personal/Pages/default.aspx")
        if text:
            m = re.search(r"(?:مديونية|buyout|شراء)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
            if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
                apr = float(m.group(1))
                result["buyout"] = {"apr": apr, "apr_derived": False}
                log.info(f"Bilad buyout APR: {apr}%")
    except Exception as e:
        log.error(f"Bilad buyout scrape error: {e}")

    # -- Mortgage --
    try:
        text = fetch_page_text(page, "https://www.bankalbilad.com/ar/personal/financing/real-estate/albilad")
        if text:
            apr = extract_apr_from_text(text)
            flat = extract_flat_from_text(text)
            if apr:
                result["mortgage"] = {"apr": apr, "apr_derived": False}
                log.info(f"Bilad mortgage APR: {apr}%")
            elif flat:
                apr = flat_to_apr(flat, years=20)
                result["mortgage"] = {"flat": flat, "apr": apr, "apr_derived": True}
                log.info(f"Bilad mortgage flat: {flat}%, derived APR: {apr}%")
    except Exception as e:
        log.error(f"Bilad mortgage scrape error: {e}")

    return result


def scrape_alawwal(page: Page) -> dict:
    """SAB (formerly Alawwal): Pricing and personal finance pages."""
    result = {}

    try:
        urls = [
            "https://www.sab.com/ar/personal/prices-of-financing-and-savings-products/",
            "https://www.sab.com/ar/financing/personal-finance/",
        ]
        text = try_urls(page, urls)
        if not text:
            return {}

        # Personal
        personal_apr = None
        m = re.search(r"(?:تمويل\s*شخصي|شخصي|personal)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            personal_apr = float(m.group(1))
        if personal_apr is None:
            personal_apr = extract_apr_from_text(text)
        flat = extract_flat_from_text(text)

        if personal_apr:
            result["personal"] = {"apr": personal_apr, "apr_derived": False}
            log.info(f"Alawwal/SAB personal APR: {personal_apr}%")
        elif flat:
            apr = flat_to_apr(flat)
            result["personal"] = {"flat": flat, "apr": apr, "apr_derived": True}
            log.info(f"Alawwal/SAB personal flat: {flat}%, derived APR: {apr}%")

        # Buyout
        m = re.search(r"(?:مديونية|buyout|شراء)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alawwal/SAB buyout APR: {apr}%")

        # Mortgage
        m = re.search(r"(?:عقاري|mortgage|سكني)[^%\d]{0,100}معدل\s*النسبة\s*السنوي[ة]?[^%\d]{0,40}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["mortgage"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alawwal/SAB mortgage APR: {apr}%")

    except Exception as e:
        log.error(f"Alawwal/SAB scrape error: {e}")

    return result


# ==========================================================================
# SCRAPER REGISTRY
# ==========================================================================

SCRAPERS = {
    "rajhi":   scrape_rajhi,
    "snb":     scrape_snb,
    "riyad":   scrape_riyad,
    "anb":     scrape_anb,
    "alinma":  scrape_alinma,
    "jazira":  scrape_jazira,
    "bilad":   scrape_bilad,
    "alawwal": scrape_alawwal,
}

PRODUCTS = ["personal", "buyout", "mortgage"]


# ==========================================================================
# MERGE SCRAPED DATA INTO RATES.JSON
# ==========================================================================

def merge_scraped(bank: dict, scraped: dict) -> dict:
    """
    Merge freshly scraped data into a bank entry.
    - On success: update APR, set status='ok', update last_updated
    - On failure: mark status='unavailable' — don't keep stale numbers
    """
    today = str(date.today())
    bank  = deepcopy(bank)

    for product in PRODUCTS:
        fresh = scraped.get(product)
        entry = bank.get(product, {})

        if fresh is None:
            entry["status"] = "unavailable"
            for k in ("apr", "flat", "apr_derived", "flat_derived"):
                entry.pop(k, None)
            entry["last_updated"] = today
            bank[product] = entry
            log.warning(f"{bank['id']} {product}: scrape failed, rate removed — bank link only")
            continue
        else:
            if "apr" in fresh and fresh["apr"] is not None:
                entry["apr"]         = fresh["apr"]
                entry["apr_derived"] = fresh.get("apr_derived", False)
            if "flat" in fresh and fresh["flat"] is not None:
                entry["flat"] = fresh["flat"]
            entry["status"]       = "ok"
            entry["last_updated"] = today

        bank[product] = entry

    return bank


# ==========================================================================
# HISTORY LOG
# ==========================================================================

def log_history(rates: dict):
    """Append current snapshot to history.jsonl (one JSON line per run)."""
    record = {
        "timestamp": datetime.utcnow().isoformat() + "Z",
        "snapshot":  rates
    }
    with open(HISTORY_FILE, "a", encoding="utf-8") as f:
        f.write(json.dumps(record, ensure_ascii=False) + "\n")
    log.info(f"History logged -> {HISTORY_FILE}")


# ==========================================================================
# DERIVE MISSING APR/FLAT
# ==========================================================================

def derive_missing(bank: dict) -> dict:
    """
    Ensure every product entry has both APR and Flat:
    derive whichever is missing from the other, flagging it
    (apr_derived / flat_derived) so the table can mark it with (*).
    """
    for product in PRODUCTS:
        entry = bank.get(product)
        if not isinstance(entry, dict):
            continue

        years = 20 if product == "mortgage" else 5
        apr, flat = entry.get("apr"), entry.get("flat")

        if apr is not None and flat is None:
            entry["flat"]         = apr_to_flat(apr, years)
            entry["flat_derived"] = True
        elif flat is not None and apr is None:
            entry["apr"]         = flat_to_apr(flat, years)
            entry["apr_derived"] = True
        elif apr is not None and flat is not None:
            entry.setdefault("flat_derived", False)

    return bank


# ==========================================================================
# MAIN
# ==========================================================================

def main():
    log.info("=== Saudi Bank Rates Scraper (Playwright) ===")

    # Load current rates
    with open(RATES_FILE, encoding="utf-8") as f:
        rates = json.load(f)

    today = str(date.today())
    any_updated = False

    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True)
        context = browser.new_context(
            locale="ar-SA",
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/124.0.0.0 Safari/537.36"
            ),
            extra_http_headers={
                "Accept-Language": "ar-SA,ar;q=0.9,en;q=0.8",
            },
        )
        page = context.new_page()

        for i, bank in enumerate(rates["banks"]):
            bank_id = bank["id"]

            # All banks are now auto-scraped via Playwright
            if bank_id in SCRAPERS:
                log.info(f"-- Scraping: {bank['name_ar']} ({bank_id}) --")
                try:
                    scraped = SCRAPERS[bank_id](page)
                    rates["banks"][i] = merge_scraped(bank, scraped)
                    any_updated = True
                except Exception as e:
                    log.error(f"Scraper crashed for {bank_id}: {e}")
                    # Mark all products as stale
                    for product in PRODUCTS:
                        if bank.get(product, {}).get("status") == "ok":
                            rates["banks"][i][product]["status"] = "stale"
            else:
                log.warning(f"-- No scraper for: {bank['name_ar']} ({bank_id}), skipping --")

            # Fill in whichever of APR/Flat is missing
            rates["banks"][i] = derive_missing(rates["banks"][i])

        browser.close()

    if any_updated:
        rates["last_updated"]  = today
        rates["update_source"] = "auto+manual"

    # Save updated rates
    with open(RATES_FILE, "w", encoding="utf-8") as f:
        json.dump(rates, f, ensure_ascii=False, indent=2)
    log.info(f"rates.json saved -> {RATES_FILE}")

    # Log history
    log_history(rates)
    log.info("=== Done ===")


if __name__ == "__main__":
    main()
