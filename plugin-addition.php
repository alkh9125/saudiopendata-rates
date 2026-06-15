<?php
/**
 * Plugin Name: جدول مقارنة نسب البنوك
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: جدول مقارنة نسب التمويل للبنوك السعودية — يُحدَّث يومياً تلقائياً
 * Version: 3.0.0
 * Author: SaudiOpenData
 * Text Domain: saod-rates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SAOD_RATES_URL', 'https://raw.githubusercontent.com/alkh9125/saudiopendata-rates/main/data/rates.json' );
define( 'SAOD_CACHE_KEY', 'saod_bank_rates_cache' );
define( 'SAOD_CACHE_TTL', DAY_IN_SECONDS );

function saod_get_rates() {
    $cached = get_option( SAOD_CACHE_KEY );
    if ( $cached && isset( $cached['data'], $cached['fetched_at'] )
         && ( time() - $cached['fetched_at'] ) < SAOD_CACHE_TTL ) {
        return $cached['data'];
    }
    $response = wp_remote_get( SAOD_RATES_URL, array(
        'timeout' => 10,
        'headers' => array( 'Accept' => 'application/json' ),
    ) );
    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( $data && isset( $data['banks'] ) ) {
            update_option( SAOD_CACHE_KEY, array(
                'data'       => $data,
                'fetched_at' => time(),
            ) );
            return $data;
        }
    }
    if ( $cached && isset( $cached['data'] ) ) {
        return $cached['data'];
    }
    return null;
}

function saod_bank_rates_table_shortcode( $atts ) {
    $rates = saod_get_rates();
    if ( ! $rates ) {
        return '<p style="color:#999;text-align:center;">عذراً، لا يمكن تحميل نسب البنوك حالياً.</p>';
    }

    $uid = 'saod-' . wp_unique_id();
    $banks_json = wp_json_encode( $rates['banks'], JSON_UNESCAPED_UNICODE );
    $last_updated = esc_html( $rates['last_updated'] ?? '—' );

    ob_start();
    ?>
    <div class="saod-v2" id="<?php echo esc_attr( $uid ); ?>" dir="rtl">

    <style>
    .saod-v2 {
        --primary: #198754;
        --primary-dark: #0f5132;
        --primary-light: #d1e7dd;
        --accent: #d4af37;
        --accent-light: #fef9e7;
        --danger: #dc3545;
        --warn: #e67e22;
        --bg: #f4f6f8;
        --surface: #ffffff;
        --border: #dee2e6;
        --text: #212529;
        --muted: #6c757d;
        --radius: 10px;
        --radius-lg: 14px;
        --shadow: 0 2px 8px rgba(0,0,0,.04);
        --shadow-md: 0 4px 12px rgba(0,0,0,.08);
        font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        max-width: 960px;
        margin: 30px auto;
        color: var(--text);
        line-height: 1.6;
        background: var(--bg);
        padding: 12px;
    }
    .saod-v2 * { box-sizing: border-box; margin: 0; padding: 0; }

    .saod-v2 .saod-header { text-align: center; margin-bottom: 20px; }
    .saod-v2 .saod-header h3 {
        font-size: 1.4rem; margin: 0 0 6px; font-weight: 900;
        color: var(--primary-dark);
    }
    .saod-v2 .saod-header .saod-sub { font-size: .85rem; color: var(--muted); }

    /* Tabs — match calculator */
    .saod-v2 .saod-tabs {
        display: flex; gap: 8px; flex-wrap: nowrap;
        overflow-x: auto; -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 8px;
        margin-bottom: 12px; box-shadow: var(--shadow);
        justify-content: center;
    }
    .saod-v2 .saod-tabs::-webkit-scrollbar { display: none; }
    .saod-v2 .saod-tab-btn {
        padding: 8px 20px; border: none; border-radius: 8px;
        background: transparent; color: var(--muted); cursor: pointer;
        font-size: .9rem; font-weight: 600; font-family: inherit;
        transition: all .2s; white-space: nowrap;
    }
    .saod-v2 .saod-tab-btn:hover { color: var(--primary); background: var(--primary-light); }
    .saod-v2 .saod-tab-btn.active {
        background: var(--primary); color: #fff;
    }

    /* Input bar — match calculator card style */
    .saod-v2 .saod-inputs {
        display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;
        align-items: flex-end; justify-content: center;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 16px 20px;
        box-shadow: var(--shadow);
    }
    .saod-v2 .saod-input-group { display: flex; flex-direction: column; gap: 4px; }
    .saod-v2 .saod-input-group label {
        font-size: .8rem; color: var(--muted); font-weight: 700;
    }
    .saod-v2 .saod-input-group input,
    .saod-v2 .saod-input-group select {
        padding: 8px 14px; border: 1px solid var(--border); border-radius: var(--radius);
        font-size: .95rem; font-family: inherit; background: #fff;
        min-width: 140px; text-align: center; outline: none;
        height: 42px;
    }
    .saod-v2 .saod-input-group input:focus,
    .saod-v2 .saod-input-group select:focus {
        border-color: var(--primary); box-shadow: 0 0 0 3px rgba(25,135,84,.12);
    }

    /* Cards */
    .saod-v2 .saod-cards { display: flex; flex-direction: column; gap: 10px; }

    .saod-v2 .saod-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: 16px 20px;
        box-shadow: var(--shadow); transition: all .15s; position: relative;
    }
    .saod-v2 .saod-card:hover { box-shadow: var(--shadow-md); }
    .saod-v2 .saod-card.saod-best {
        border-color: var(--primary); background: var(--primary-light);
    }

    .saod-v2 .saod-card-top {
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; flex-wrap: wrap;
    }
    .saod-v2 .saod-bank-name { font-size: 1rem; font-weight: 800; }

    .saod-v2 .saod-badge {
        font-size: .7rem; padding: 3px 12px; border-radius: 8px;
        font-weight: 700; white-space: nowrap;
    }
    .saod-v2 .saod-badge-best { background: var(--primary); color: #fff; }
    .saod-v2 .saod-badge-rank { background: var(--primary-light); color: var(--primary-dark); }

    .saod-v2 .saod-card-main {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 12px; margin-top: 12px;
    }
    .saod-v2 .saod-metric { text-align: center; }
    .saod-v2 .saod-metric-label {
        font-size: .7rem; color: var(--muted); margin-bottom: 2px;
        font-weight: 600; letter-spacing: .3px;
    }
    .saod-v2 .saod-metric-value {
        font-size: 1.15rem; font-weight: 800; color: var(--text);
    }
    .saod-v2 .saod-metric-value.saod-rial { color: var(--primary); }
    .saod-v2 .saod-metric-value small {
        font-size: .7rem; font-weight: 400; color: var(--muted);
    }
    .saod-v2 .saod-metric-value .derived {
        color: var(--warn); cursor: help; font-size: .75rem;
    }
    .saod-v2 .saod-metric-value .stale {
        cursor: help; font-size: .75rem;
    }

    .saod-v2 .saod-card-footer {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border);
        font-size: .78rem; color: var(--muted); flex-wrap: wrap; gap: 6px;
    }
    .saod-v2 .saod-card-footer a {
        color: var(--primary); text-decoration: none; font-weight: 600;
    }
    .saod-v2 .saod-card-footer a:hover { text-decoration: underline; }

    /* Unavailable banks compact grid */
    .saod-v2 .saod-other-banks { margin-top: 16px; }
    .saod-v2 .saod-other-title {
        font-size: .82rem; color: var(--muted); font-weight: 700;
        margin-bottom: 10px; padding-right: 4px;
    }
    .saod-v2 .saod-other-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 8px;
    }
    .saod-v2 .saod-other-item {
        display: flex; align-items: center; justify-content: space-between;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 10px 14px;
        font-size: .88rem; font-weight: 600;
    }
    .saod-v2 .saod-other-item a {
        color: var(--primary); text-decoration: none; font-size: .8rem; font-weight: 700;
        white-space: nowrap;
    }
    .saod-v2 .saod-other-item a:hover { text-decoration: underline; }

    /* Empty state */
    .saod-v2 .saod-empty {
        text-align: center; padding: 30px 20px;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-lg); color: var(--muted);
    }
    .saod-v2 .saod-empty p { font-size: .95rem; margin-bottom: 4px; }
    .saod-v2 .saod-empty small { font-size: .8rem; }

    .saod-v2 .saod-savings {
        font-size: .78rem; color: var(--warn); font-weight: 700; margin-top: 8px;
        text-align: center;
    }
    .saod-v2 .saod-savings.saod-savings-best { color: var(--primary); }

    /* Compare bar */
    .saod-v2 .saod-compare-bar {
        display: none; position: sticky; bottom: 0; left: 0; right: 0;
        background: var(--primary); color: #fff; padding: 10px 16px;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        text-align: center; font-size: .9rem; font-weight: 700;
        cursor: pointer; box-shadow: 0 -2px 8px rgba(0,0,0,.15);
        z-index: 10; margin-top: 12px;
    }
    .saod-v2 .saod-compare-bar.visible { display: block; }

    .saod-v2 .saod-compare-check {
        position: absolute; top: 16px; left: 16px; cursor: pointer;
    }
    .saod-v2 .saod-compare-check input {
        margin: 0; cursor: pointer; width: 16px; height: 16px;
        accent-color: var(--primary);
    }

    /* Info box */
    .saod-v2 .saod-info {
        background: var(--accent-light); border: 1px solid var(--accent);
        border-radius: var(--radius-lg); padding: 14px 18px; margin-top: 16px;
        font-size: .82rem; color: #7a6520; line-height: 1.8;
    }
    .saod-v2 .saod-info summary {
        cursor: pointer; font-weight: 800; font-size: .85rem;
        list-style: none; display: flex; align-items: center; gap: 6px;
        color: var(--primary-dark);
    }
    .saod-v2 .saod-info summary::before { content: '▸'; transition: transform .15s; }
    .saod-v2 .saod-info[open] summary::before { transform: rotate(90deg); }
    .saod-v2 .saod-info .saod-info-body { margin-top: 8px; }

    /* Compare modal */
    .saod-v2 .saod-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 9999; justify-content: center; align-items: center; padding: 16px;
    }
    .saod-v2 .saod-modal-overlay.visible { display: flex; }
    .saod-v2 .saod-modal {
        background: var(--surface); border-radius: var(--radius-lg); padding: 24px;
        max-width: 700px; width: 100%; max-height: 85vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.3); position: relative;
    }
    .saod-v2 .saod-modal-close {
        position: absolute; top: 12px; left: 12px; background: none;
        border: none; font-size: 22px; cursor: pointer; color: var(--muted);
        font-family: inherit;
    }
    .saod-v2 .saod-modal h4 {
        margin: 0 0 16px; font-size: 1.1rem; text-align: center;
        font-weight: 800; color: var(--primary-dark);
    }
    .saod-v2 .saod-compare-grid { display: grid; gap: 12px; }
    .saod-v2 .saod-compare-row {
        display: grid; gap: 8px; padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }
    .saod-v2 .saod-compare-row-label {
        font-size: .75rem; color: var(--muted); font-weight: 700;
    }
    .saod-v2 .saod-compare-row-values { display: grid; gap: 8px; }
    .saod-v2 .saod-compare-cell {
        text-align: center; font-size: .9rem; font-weight: 700;
    }
    .saod-v2 .saod-compare-cell.highlight { color: var(--primary); }

    @media (max-width: 600px) {
        .saod-v2 .saod-card-main { grid-template-columns: 1fr 1fr; gap: 8px; }
        .saod-v2 .saod-inputs { flex-direction: column; align-items: stretch; }
        .saod-v2 .saod-input-group input,
        .saod-v2 .saod-input-group select { width: 100%; min-width: unset; }
    }
    </style>

    <div class="saod-header">
        <h3>مقارنة نسب التمويل في البنوك السعودية</h3>
        <div class="saod-sub">آخر تحديث: <?php echo $last_updated; ?> · الأرقام تمثل أقل نسبة معلنة (يبدأ من)</div>
    </div>

    <div class="saod-tabs">
        <button type="button" class="saod-tab-btn active" data-tab="personal">تمويل شخصي</button>
        <button type="button" class="saod-tab-btn" data-tab="buyout">شراء مديونية</button>
        <button type="button" class="saod-tab-btn" data-tab="mortgage">تمويل عقاري</button>
    </div>

    <div class="saod-inputs">
        <div class="saod-input-group">
            <label>مبلغ التمويل (ريال)</label>
            <input type="text" id="<?php echo esc_attr( $uid ); ?>-amount" value="300,000" inputmode="numeric">
        </div>
        <div class="saod-input-group">
            <label>المدة (سنوات)</label>
            <select id="<?php echo esc_attr( $uid ); ?>-years">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5" selected>5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
                <option value="30">30</option>
            </select>
        </div>
    </div>

    <div class="saod-cards" id="<?php echo esc_attr( $uid ); ?>-cards"></div>

    <div class="saod-compare-bar" id="<?php echo esc_attr( $uid ); ?>-compare-bar">
        قارن بين البنوك المحددة (<span class="compare-count">0</span>)
    </div>

    <div class="saod-modal-overlay" id="<?php echo esc_attr( $uid ); ?>-modal">
        <div class="saod-modal">
            <button type="button" class="saod-modal-close">&times;</button>
            <h4>مقارنة تفصيلية</h4>
            <div class="saod-compare-grid" id="<?php echo esc_attr( $uid ); ?>-compare-body"></div>
        </div>
    </div>

    <details class="saod-info">
        <summary>ما الفرق بين النسبة الثابتة (Flat) و APR؟</summary>
        <div class="saod-info-body">
            البنوك تعلن عادةً «هامش الربح الثابت» لأنه يبدو رقماً أصغر — لكن النسبة الحقيقية التي تدفعها هي <strong>APR</strong> (معدل النسبة السنوي)، وهي تقريباً ضعف الرقم المعلن.
            <br>السبب: الهامش الثابت يُحسب على كامل المبلغ الأصلي طوال المدة، بينما APR يأخذ بالحسبان أنك تسدد جزءاً من الأصل كل شهر.
            <br><strong>المقارنة الصحيحة بين البنوك تكون دائماً بالـ APR.</strong>
            <br>الأرقام المعلّمة بـ <span style="color:#e67e22;">*</span> مشتقة رياضياً من النسبة الأخرى المعلنة من البنك.
        </div>
    </details>

    <script>
    (function(){
        var uid = <?php echo wp_json_encode( $uid ); ?>;
        var banks = <?php echo $banks_json; ?>;
        var wrap = document.getElementById(uid);
        if (!wrap) return;

        var currentTab = 'personal';
        var compareSet = new Set();

        var amountEl = document.getElementById(uid + '-amount');
        var yearsEl = document.getElementById(uid + '-years');
        var cardsEl = document.getElementById(uid + '-cards');
        var compareBar = document.getElementById(uid + '-compare-bar');
        var modalOverlay = document.getElementById(uid + '-modal');
        var compareBody = document.getElementById(uid + '-compare-body');

        function parseAmount(s) {
            return parseInt(String(s).replace(/[^0-9]/g, ''), 10) || 0;
        }
        function fmtN(n) {
            return Math.round(Number(n) || 0).toLocaleString('en-US');
        }
        function formatAmount(el) {
            var v = parseAmount(el.value);
            if (v > 0) el.value = fmtN(v);
        }

        function flatToApr(flatPct, years) {
            var n = years * 12, f = flatPct / 100, P = 1e5;
            var ti = P * f * years, emi = (P + ti) / n;
            var lo = 1e-9, hi = 0.5;
            for (var i = 0; i < 100; i++) {
                var m = (lo + hi) / 2;
                var pv = m < 1e-9 ? emi * n : emi * (1 - Math.pow(1 + m, -n)) / m;
                if (pv > P) lo = m; else hi = m;
            }
            return Math.round((lo + hi) / 2 * 12 * 1e4) / 100;
        }

        function aprToFlat(aprPct, years) {
            var n = years * 12, r = aprPct / 100 / 12, P = 1e5;
            var emi = r < 1e-9 ? P / n : P * r / (1 - Math.pow(1 + r, -n));
            return Math.round((emi * n - P) / (P * years) * 1e4) / 100;
        }

        function calcEMI(principal, aprPct, years) {
            var months = years * 12;
            var r = aprPct / 100 / 12;
            if (r < 1e-9) return principal / months;
            return r * principal / (1 - Math.pow(1 + r, -months));
        }

        function getApr(p, years) {
            if (p.apr != null && !p.apr_derived) return { val: p.apr, derived: false };
            if (p.apr != null) return { val: p.apr, derived: p.apr_derived };
            if (p.flat != null) return { val: flatToApr(p.flat, years), derived: true };
            return null;
        }

        function getFlat(p, years) {
            if (p.flat != null && !p.flat_derived) return { val: p.flat, derived: false };
            if (p.flat != null) return { val: p.flat, derived: p.flat_derived };
            if (p.apr != null) return { val: aprToFlat(p.apr, years), derived: true };
            return null;
        }

        function sortBanks(tab, years) {
            var arr = banks.slice();
            arr.sort(function(a, b) {
                var da = a[tab] ? getApr(a[tab], years) : null;
                var db = b[tab] ? getApr(b[tab], years) : null;
                var aprA = (da && a[tab].status !== 'unavailable') ? da.val : null;
                var aprB = (db && b[tab].status !== 'unavailable') ? db.val : null;
                if (aprA === null && aprB === null) return 0;
                if (aprA === null) return 1;
                if (aprB === null) return -1;
                return aprA - aprB;
            });
            return arr;
        }

        function render() {
            var amount = parseAmount(amountEl.value);
            var years = parseInt(yearsEl.value, 10) || 5;
            var sorted = sortBanks(currentTab, years);

            var available = [];
            var unavailable = [];
            for (var i = 0; i < sorted.length; i++) {
                var bank = sorted[i];
                var p = bank[currentTab];
                if (!p) continue;
                var aprData = getApr(p, years);
                var status = p.status || 'unavailable';
                if (status === 'unavailable' || !aprData) {
                    unavailable.push(bank);
                } else {
                    available.push(bank);
                }
            }

            var bestApr = null;
            var bestEMI = null;
            if (available.length > 0) {
                var p0 = available[0][currentTab];
                var ad0 = getApr(p0, years);
                if (ad0) { bestApr = ad0.val; bestEMI = calcEMI(amount, ad0.val, years); }
            }

            var html = '';

            if (available.length === 0) {
                html += '<div class="saod-empty">';
                html += '<p>لا توجد نسب متاحة حالياً لهذا المنتج</p>';
                html += '<small>يمكنك زيارة مواقع البنوك أدناه للاطلاع على أحدث النسب</small>';
                html += '</div>';
            }

            for (var i = 0; i < available.length; i++) {
                var bank = available[i];
                var p = bank[currentTab];
                var aprData = getApr(p, years);
                var flatData = getFlat(p, years);
                var status = p.status || 'ok';
                var source = (bank.source_url && bank.source_url[currentTab]) || '';
                var maxAmt = p.max_amount;
                var maxYrs = p.max_years;
                var isBest = (aprData.val === bestApr);

                var cardClass = 'saod-card';
                if (isBest) cardClass += ' saod-best';

                var checked = compareSet.has(bank.id) ? ' checked' : '';

                html += '<div class="' + cardClass + '" data-bank="' + bank.id + '">';
                html += '<label class="saod-compare-check" title="اختر للمقارنة"><input type="checkbox" data-id="' + bank.id + '"' + checked + '></label>';

                html += '<div class="saod-card-top">';
                html += '<p class="saod-bank-name">' + escHtml(bank.name_ar) + '</p>';
                if (isBest) {
                    html += '<span class="saod-badge saod-badge-best">الأفضل</span>';
                } else {
                    html += '<span class="saod-badge saod-badge-rank">#' + (i + 1) + '</span>';
                }
                html += '</div>';

                var apr = aprData.val;
                var emi = calcEMI(amount, apr, years);
                var totalCost = emi * years * 12;
                var totalProfit = totalCost - amount;

                html += '<div class="saod-card-main">';

                html += '<div class="saod-metric">';
                html += '<div class="saod-metric-label">القسط الشهري</div>';
                html += '<div class="saod-metric-value saod-rial">' + fmtN(Math.round(emi)) + ' <small>ر.س</small></div>';
                html += '</div>';

                html += '<div class="saod-metric">';
                html += '<div class="saod-metric-label">إجمالي الربح</div>';
                html += '<div class="saod-metric-value">' + fmtN(Math.round(totalProfit)) + ' <small>ر.س</small></div>';
                html += '</div>';

                html += '<div class="saod-metric">';
                html += '<div class="saod-metric-label">APR</div>';
                html += '<div class="saod-metric-value">' + apr + '%';
                if (aprData.derived) html += ' <span class="derived" title="مشتق رياضياً من الثابت">*</span>';
                if (status === 'stale') html += ' <span class="stale" title="قد لا تكون محدثة">⚠</span>';
                html += '</div>';
                html += '</div>';

                html += '<div class="saod-metric">';
                html += '<div class="saod-metric-label">Flat (ثابت)</div>';
                html += '<div class="saod-metric-value">';
                if (flatData) {
                    html += flatData.val + '%';
                    if (flatData.derived) html += ' <span class="derived" title="مشتق رياضياً من APR">*</span>';
                } else {
                    html += '—';
                }
                html += '</div>';
                html += '</div>';

                html += '</div>';

                if (isBest && available.length > 1) {
                    html += '<div class="saod-savings saod-savings-best">الأقل تكلفة بين جميع البنوك</div>';
                } else if (bestEMI != null) {
                    var diff = totalCost - (bestEMI * years * 12);
                    if (diff > 0) {
                        html += '<div class="saod-savings">تكلفة إضافية ' + fmtN(Math.round(diff)) + ' ر.س مقارنة بالأرخص</div>';
                    }
                }

                html += '<div class="saod-card-footer">';
                var footerParts = [];
                if (maxAmt) footerParts.push('أقصى مبلغ: ' + fmtN(maxAmt) + ' ر.س');
                if (maxYrs) footerParts.push('أقصى مدة: ' + maxYrs + ' سنة');
                html += '<span>' + footerParts.join(' · ') + '</span>';
                if (source) {
                    html += '<a href="' + escAttr(source) + '" target="_blank" rel="nofollow noopener">موقع البنك ↗</a>';
                }
                html += '</div>';

                html += '</div>';
            }

            if (unavailable.length > 0) {
                html += '<div class="saod-other-banks">';
                html += '<div class="saod-other-title">بنوك أخرى — النسبة غير متوفرة حالياً</div>';
                html += '<div class="saod-other-grid">';
                for (var j = 0; j < unavailable.length; j++) {
                    var ub = unavailable[j];
                    var src = (ub.source_url && ub.source_url[currentTab]) || '';
                    html += '<div class="saod-other-item">';
                    html += '<span>' + escHtml(ub.name_ar) + '</span>';
                    if (src) {
                        html += '<a href="' + escAttr(src) + '" target="_blank" rel="nofollow noopener">موقع البنك ↗</a>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                html += '</div>';
            }

            cardsEl.innerHTML = html;

            cardsEl.querySelectorAll('.saod-compare-check input').forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var id = this.getAttribute('data-id');
                    if (this.checked) { compareSet.add(id); } else { compareSet.delete(id); }
                    updateCompareBar();
                });
            });

            updateCompareBar();
        }

        function updateCompareBar() {
            var count = compareSet.size;
            if (count >= 2) {
                compareBar.classList.add('visible');
                compareBar.querySelector('.compare-count').textContent = count;
            } else {
                compareBar.classList.remove('visible');
            }
        }

        function showCompare() {
            if (compareSet.size < 2) return;
            var amount = parseAmount(amountEl.value);
            var years = parseInt(yearsEl.value, 10) || 5;

            var selected = [];
            banks.forEach(function(b) {
                if (compareSet.has(b.id)) selected.push(b);
            });

            var cols = selected.length;
            var gridCols = 'repeat(' + cols + ', 1fr)';

            var html = '';

            function row(label, values) {
                html += '<div class="saod-compare-row">';
                html += '<div class="saod-compare-row-label">' + label + '</div>';
                html += '<div class="saod-compare-row-values" style="grid-template-columns:' + gridCols + '">';
                var numericVals = values.map(function(v) { return typeof v === 'number' ? v : Infinity; });
                var minVal = Math.min.apply(null, numericVals);
                values.forEach(function(v, i) {
                    var cls = 'saod-compare-cell';
                    if (typeof v === 'number' && v === minVal && numericVals.filter(function(x){return x===minVal;}).length < numericVals.length) cls += ' highlight';
                    var display = typeof v === 'number' ? fmtN(Math.round(v)) : v;
                    html += '<div class="' + cls + '">' + display + '</div>';
                });
                html += '</div></div>';
            }

            html += '<div class="saod-compare-row"><div class="saod-compare-row-label">البنك</div>';
            html += '<div class="saod-compare-row-values" style="grid-template-columns:' + gridCols + '">';
            selected.forEach(function(b) {
                html += '<div class="saod-compare-cell" style="font-weight:800;">' + escHtml(b.name_ar) + '</div>';
            });
            html += '</div></div>';

            row('APR', selected.map(function(b) {
                var p = b[currentTab];
                if (!p) return '—';
                var d = getApr(p, years);
                return d ? d.val + '%' + (d.derived ? ' *' : '') : '—';
            }));

            row('Flat (ثابت)', selected.map(function(b) {
                var p = b[currentTab];
                if (!p) return '—';
                var d = getFlat(p, years);
                return d ? d.val + '%' + (d.derived ? ' *' : '') : '—';
            }));

            row('القسط الشهري', selected.map(function(b) {
                var p = b[currentTab];
                if (!p) return '—';
                var d = getApr(p, years);
                if (!d) return '—';
                return calcEMI(amount, d.val, years);
            }));

            row('إجمالي السداد', selected.map(function(b) {
                var p = b[currentTab];
                if (!p) return '—';
                var d = getApr(p, years);
                if (!d) return '—';
                return calcEMI(amount, d.val, years) * years * 12;
            }));

            row('إجمالي الربح', selected.map(function(b) {
                var p = b[currentTab];
                if (!p) return '—';
                var d = getApr(p, years);
                if (!d) return '—';
                return calcEMI(amount, d.val, years) * years * 12 - amount;
            }));

            row('أقصى مبلغ', selected.map(function(b) {
                var p = b[currentTab];
                return (p && p.max_amount) ? fmtN(p.max_amount) + ' ر.س' : '—';
            }));

            row('أقصى مدة', selected.map(function(b) {
                var p = b[currentTab];
                return (p && p.max_years) ? p.max_years + ' سنة' : '—';
            }));

            compareBody.innerHTML = html;
            modalOverlay.classList.add('visible');
        }

        function escHtml(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }
        function escAttr(s) {
            return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        wrap.querySelectorAll('.saod-tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.saod-tab-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentTab = btn.getAttribute('data-tab');
                if (currentTab === 'mortgage' && parseInt(yearsEl.value) <= 5) {
                    yearsEl.value = '20';
                } else if (currentTab !== 'mortgage' && parseInt(yearsEl.value) > 5) {
                    yearsEl.value = '5';
                }
                compareSet.clear();
                render();
            });
        });

        amountEl.addEventListener('input', function() { formatAmount(amountEl); render(); });
        amountEl.addEventListener('blur', function() { formatAmount(amountEl); });
        yearsEl.addEventListener('change', render);
        compareBar.addEventListener('click', showCompare);

        modalOverlay.querySelector('.saod-modal-close').addEventListener('click', function() {
            modalOverlay.classList.remove('visible');
        });
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) modalOverlay.classList.remove('visible');
        });

        formatAmount(amountEl);
        render();
    })();
    </script>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'bank_rates_table', 'saod_bank_rates_table_shortcode' );

// ── Admin: Manual Refresh ────────────────────────────────────────────────────
function saod_rates_admin_menu() {
    add_options_page( 'SAOD Rates', 'SAOD Rates', 'manage_options', 'saod-rates', 'saod_rates_admin_page' );
}
add_action( 'admin_menu', 'saod_rates_admin_menu' );

function saod_rates_admin_page() {
    if ( isset( $_POST['saod_refresh'] ) && check_admin_referer( 'saod_refresh_nonce' ) ) {
        delete_option( SAOD_CACHE_KEY );
        $rates = saod_get_rates();
        $msg = $rates ? 'تم تحديث النسب بنجاح' : 'فشل التحديث — تحقق من الاتصال';
        echo '<div class="notice notice-' . ( $rates ? 'success' : 'error' ) . '"><p>' . esc_html( $msg ) . '</p></div>';
    }
    $cached = get_option( SAOD_CACHE_KEY );
    $last_fetch = $cached && isset( $cached['fetched_at'] )
        ? date( 'Y-m-d H:i:s', $cached['fetched_at'] )
        : 'لم يتم الجلب بعد';
    ?>
    <div class="wrap">
        <h1>SAOD Bank Rates</h1>
        <p>آخر جلب للبيانات: <strong><?php echo esc_html( $last_fetch ); ?></strong></p>
        <form method="post">
            <?php wp_nonce_field( 'saod_refresh_nonce' ); ?>
            <input type="submit" name="saod_refresh" class="button button-primary" value="تحديث الآن">
        </form>
    </div>
    <?php
}
