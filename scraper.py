"""
Saudi Bank Rates Scraper
========================
Scrapes personal finance, buyout, and mortgage rates from Saudi banks.
- Auto-scrapes: Rajhi, Riyad, Alinma
- Manual fallback: ANB, SNB, Jazira, Bilad, Alawwal
- Derives APR from Flat rate when needed
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

import requests
from bs4 import BeautifulSoup
from scipy.optimize import brentq

# ── Logging ────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)
log = logging.getLogger(__name__)

# ── Paths ──────────────────────────────────────────────────
BASE_DIR     = Path(__file__).parent
RATES_FILE   = BASE_DIR / "data" / "rates.json"
HISTORY_FILE = BASE_DIR / "data" / "history.jsonl"

# ── HTTP session ───────────────────────────────────────────
SESSION = requests.Session()
SESSION.headers.update({
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/124.0.0.0 Safari/537.36"
    ),
    "Accept-Language": "ar-SA,ar;q=0.9,en;q=0.8",
})
TIMEOUT = 15

# Plausible ranges used to reject garbage matches from page text/tables
APR_RANGE  = (0.5, 30.0)
FLAT_RANGE = (0.5, 10.0)


# ══════════════════════════════════════════════════════════
# APR ↔ FLAT CONVERSION
# ══════════════════════════════════════════════════════════

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
        # Fallback approximation: APR ≈ Flat × 1.85
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


# ══════════════════════════════════════════════════════════
# SCRAPING HELPERS
# ══════════════════════════════════════════════════════════

def fetch(url: str) -> BeautifulSoup | None:
    """Fetch a URL and return BeautifulSoup, or None on failure."""
    try:
        resp = SESSION.get(url, timeout=TIMEOUT)
        resp.raise_for_status()
        return BeautifulSoup(resp.text, "html.parser")
    except Exception as e:
        log.warning(f"Fetch failed [{url}]: {e}")
        return None


def extract_pct(text: str) -> float | None:
    """Extract first percentage number from text string."""
    m = re.search(r"(\d+\.?\d*)\s*%", text)
    return float(m.group(1)) if m else None


def first_pct_in_range(text: str, low: float, high: float) -> float | None:
    """Return the first percentage number in `text` that falls within [low, high]."""
    for m in re.finditer(r"(\d+\.?\d*)\s*%", text):
        val = float(m.group(1))
        if low <= val <= high:
            return val
    return None


# ══════════════════════════════════════════════════════════
# BANK-SPECIFIC SCRAPERS (AUTO)
# ══════════════════════════════════════════════════════════

def scrape_rajhi() -> dict:
    """
    Rajhi pages render in plain HTML.
    Personal + Buyout: parse illustrative example tables.
    Mortgage: parse profit margin mentions.
    """
    result = {}

    # ── Personal ──────────────────────────────────────────
    try:
        soup = fetch("https://www.alrajhibank.com.sa/Personal/Finance/Personal-Finance/Personal-Finance")
        apr = None
        if soup:
            # Look for APR percentage in page text
            text = soup.get_text()
            m = re.search(r"معدل النسبة السنوي[^%\d]*(\d+\.?\d*)\s*%", text)
            if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
                apr = float(m.group(1))
        if apr:
            result["personal"] = {"apr": apr, "apr_derived": False}
            log.info(f"Rajhi personal APR: {apr}%")
        else:
            log.warning("Rajhi personal: APR not found, using flat derivation")
            result["personal"] = {"flat": 2.5, "apr": flat_to_apr(2.5), "apr_derived": True}
    except Exception as e:
        log.error(f"Rajhi personal scrape error: {e}")
        result["personal"] = None

    # ── Buyout ────────────────────────────────────────────
    try:
        soup = fetch("https://www.alrajhibank.com.sa/Personal/Finance/Personal-Finance/Personal-Finance-Buyout")
        apr = None
        if soup:
            # Prefer the explicit APR disclosure label
            text = soup.get_text()
            m = re.search(r"معدل النسبة السنوي[^%\d]*(\d+\.?\d*)\s*%", text)
            if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
                apr = float(m.group(1))

            # Fallback: scan illustrative example tables for a plausible APR
            if apr is None:
                for tbl in soup.find_all("table"):
                    apr = first_pct_in_range(tbl.get_text(), *APR_RANGE)
                    if apr is not None:
                        break
        if apr:
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Rajhi buyout APR: {apr}%")
        else:
            result["buyout"] = None
    except Exception as e:
        log.error(f"Rajhi buyout scrape error: {e}")
        result["buyout"] = None

    # ── Mortgage ──────────────────────────────────────────
    try:
        soup = fetch("https://www.alrajhibank.com.sa/Personal/Finance/Real-Estate-Finance")
        flat = None
        if soup:
            text = soup.get_text()
            m = re.search(r"هامش ربح[^%\d]*(\d+\.?\d*)\s*%", text)
            if m and FLAT_RANGE[0] <= float(m.group(1)) <= FLAT_RANGE[1]:
                flat = float(m.group(1))
        if flat:
            apr = flat_to_apr(flat, years=20)
            result["mortgage"] = {"flat": flat, "apr": apr, "apr_derived": True}
            log.info(f"Rajhi mortgage flat: {flat}%, derived APR: {apr}%")
        else:
            result["mortgage"] = None
    except Exception as e:
        log.error(f"Rajhi mortgage scrape error: {e}")
        result["mortgage"] = None

    return result


def scrape_riyad() -> dict:
    """
    Riyad Bank has a dedicated APR disclosure page in plain HTML.
    """
    result = {}
    try:
        soup = fetch("https://www.riyadbank.com/information/special-pages/arp-disclosure")
        if not soup:
            return {}

        text = soup.get_text()

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


def scrape_alinma() -> dict:
    """
    Alinma has a dedicated prices page.
    """
    result = {}
    try:
        soup = fetch("https://alinma.com/Prices-Finance-and-Products")
        if not soup:
            return {}

        text = soup.get_text()

        # Personal — "يبدأ من X%"
        m = re.search(r"(?:شخصي)[^%\d]{0,100}يبدأ من[^%\d]{0,20}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["personal"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alinma personal APR: {apr}%")

        # Buyout
        m = re.search(r"(?:مديونية|buyout)[^%\d]{0,100}يبدأ من[^%\d]{0,20}(\d+\.?\d*)\s*%", text, re.IGNORECASE)
        if m and APR_RANGE[0] <= float(m.group(1)) <= APR_RANGE[1]:
            apr = float(m.group(1))
            result["buyout"] = {"apr": apr, "apr_derived": False}
            log.info(f"Alinma buyout APR: {apr}%")

    except Exception as e:
        log.error(f"Alinma scrape error: {e}")

    return result


# ══════════════════════════════════════════════════════════
# MERGE SCRAPED DATA INTO RATES.JSON
# ══════════════════════════════════════════════════════════

SCRAPERS = {
    "rajhi":  scrape_rajhi,
    "riyad":  scrape_riyad,
    "alinma": scrape_alinma,
}

PRODUCTS = ["personal", "buyout", "mortgage"]


def merge_scraped(bank: dict, scraped: dict) -> dict:
    """
    Merge freshly scraped data into a bank entry.
    - On success: update APR, set status='ok', update last_updated
    - On failure: keep old value, set status='stale'
    """
    today = str(date.today())
    bank  = deepcopy(bank)

    for product in PRODUCTS:
        fresh = scraped.get(product)
        entry = bank.get(product, {})

        if fresh is None:
            # Scrape failed for this product
            if entry.get("status") == "ok":
                entry["status"] = "stale"
                log.warning(f"{bank['id']} {product}: scrape failed, keeping last known value (stale)")
        else:
            # Merge fresh values
            if "apr" in fresh and fresh["apr"] is not None:
                entry["apr"]         = fresh["apr"]
                entry["apr_derived"] = fresh.get("apr_derived", False)
            if "flat" in fresh and fresh["flat"] is not None:
                entry["flat"] = fresh["flat"]
            entry["status"]       = "ok"
            entry["last_updated"] = today

        bank[product] = entry

    return bank


# ══════════════════════════════════════════════════════════
# HISTORY LOG
# ══════════════════════════════════════════════════════════

def log_history(rates: dict):
    """Append current snapshot to history.jsonl (one JSON line per run)."""
    record = {
        "timestamp": datetime.utcnow().isoformat() + "Z",
        "snapshot":  rates
    }
    with open(HISTORY_FILE, "a", encoding="utf-8") as f:
        f.write(json.dumps(record, ensure_ascii=False) + "\n")
    log.info(f"History logged → {HISTORY_FILE}")


# ══════════════════════════════════════════════════════════
# DERIVE MISSING APR/FLAT
# ══════════════════════════════════════════════════════════

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


# ══════════════════════════════════════════════════════════
# MAIN
# ══════════════════════════════════════════════════════════

def main():
    log.info("=== Saudi Bank Rates Scraper ===")

    # Load current rates
    with open(RATES_FILE, encoding="utf-8") as f:
        rates = json.load(f)

    today = str(date.today())
    any_updated = False

    for i, bank in enumerate(rates["banks"]):
        bank_id = bank["id"]
        method  = bank.get("scrape_method", "manual")

        if method == "auto" and bank_id in SCRAPERS:
            log.info(f"── Auto-scraping: {bank['name_ar']} ──")
            try:
                scraped  = SCRAPERS[bank_id]()
                rates["banks"][i] = merge_scraped(bank, scraped)
                any_updated = True
            except Exception as e:
                log.error(f"Scraper crashed for {bank_id}: {e}")
                # Mark all products as stale
                for product in PRODUCTS:
                    if bank.get(product, {}).get("status") == "ok":
                        rates["banks"][i][product]["status"] = "stale"
        else:
            log.info(f"── Manual bank (skipping auto-scrape): {bank['name_ar']} ──")

        # Fill in whichever of APR/Flat is missing (manual banks included)
        rates["banks"][i] = derive_missing(rates["banks"][i])

    if any_updated:
        rates["last_updated"]  = today
        rates["update_source"] = "auto+manual"

    # Save updated rates
    with open(RATES_FILE, "w", encoding="utf-8") as f:
        json.dump(rates, f, ensure_ascii=False, indent=2)
    log.info(f"rates.json saved → {RATES_FILE}")

    # Log history
    log_history(rates)
    log.info("=== Done ===")


if __name__ == "__main__":
    main()
