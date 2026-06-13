<?php
/**
 * Plugin Name: جدول مقارنة نسب البنوك
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: جدول مقارنة نسب التمويل للبنوك السعودية — يُحدَّث يومياً تلقائياً
 * Version: 2.0.0
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
        --primary: #0073aa;
        --primary-light: #e8f4fd;
        --green: #16a34a;
        --green-light: #f0fdf4;
        --orange: #ea580c;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-700: #374151;
        --gray-900: #111827;
        --radius: 12px;
        --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
        --shadow-md: 0 4px 6px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.06);
        font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
        max-width: 900px;
        margin: 30px auto;
        color: var(--gray-900);
        line-height: 1.6;
    }
    .saod-v2 * { box-sizing: border-box; }

    .saod-v2 .saod-header { text-align: center; margin-bottom: 24px; }
    .saod-v2 .saod-header h3 { font-size: 22px; margin: 0 0 4px; font-weight: 700; }
    .saod-v2 .saod-header .saod-sub { font-size: 13px; color: var(--gray-500); }

    /* Tabs */
    .saod-v2 .saod-tabs {
        display: flex; justify-content: center; gap: 8px;
        margin-bottom: 20px; flex-wrap: wrap;
    }
    .saod-v2 .saod-tab-btn {
        padding: 8px 20px; border: 1px solid var(--gray-200); border-radius: 20px;
        background: #fff; color: var(--gray-700); cursor: pointer; font-size: 14px;
        font-family: inherit; transition: all .15s;
    }
    .saod-v2 .saod-tab-btn:hover { border-color: var(--primary); color: var(--primary); }
    .saod-v2 .saod-tab-btn.active {
        background: var(--primary); color: #fff; border-color: var(--primary);
    }

    /* Input bar */
    .saod-v2 .saod-inputs {
        display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
        align-items: flex-end; justify-content: center;
    }
    .saod-v2 .saod-input-group { display: flex; flex-direction: column; gap: 4px; }
    .saod-v2 .saod-input-group label {
        font-size: 12px; color: var(--gray-500); font-weight: 600;
    }
    .saod-v2 .saod-input-group input,
    .saod-v2 .saod-input-group select {
        padding: 8px 12px; border: 1px solid var(--gray-200); border-radius: 8px;
        font-size: 14px; font-family: inherit; background: #fff;
        min-width: 130px; text-align: center;
    }
    .saod-v2 .saod-input-group input:focus,
    .saod-v2 .saod-input-group select:focus {
        outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,115,170,.12);
    }

    /* Cards container */
    .saod-v2 .saod-cards { display: flex; flex-direction: column; gap: 12px; }

    /* Bank card */
    .saod-v2 .saod-card {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 16px 20px; box-shadow: var(--shadow); transition: all .15s;
        position: relative;
    }
    .saod-v2 .saod-card:hover { box-shadow: var(--shadow-md); }
    .saod-v2 .saod-card.saod-best {
        border-color: var(--green); background: var(--green-light);
    }
    .saod-v2 .saod-card.saod-unavailable {
        opacity: .55; background: var(--gray-50);
    }

    .saod-v2 .saod-card-top {
        display: flex; justify-content: space-between; align-items: flex-start;
        gap: 12px; flex-wrap: wrap;
    }
    .saod-v2 .saod-bank-name {
        font-size: 16px; font-weight: 700; margin: 0;
    }
    .saod-v2 .saod-badge {
        font-size: 11px; padding: 2px 10px; border-radius: 10px;
        font-weight: 600; white-space: nowrap;
    }
    .saod-v2 .saod-badge-best { background: var(--green); color: #fff; }
    .saod-v2 .saod-badge-rank { background: var(--gray-100); color: var(--gray-500); }

    .saod-v2 .saod-card-main {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px; margin-top: 12px;
    }
    .saod-v2 .saod-metric { text-align: center; }
    .saod-v2 .saod-metric-label {
        font-size: 11px; color: var(--gray-400); margin-bottom: 2px;
        text-transform: uppercase; letter-spacing: .3px;
    }
    .saod-v2 .saod-metric-value {
        font-size: 20px; font-weight: 700; color: var(--gray-900);
    }
    .saod-v2 .saod-metric-value.saod-rial { color: var(--primary); }
    .saod-v2 .saod-metric-value small {
        font-size: 12px; font-weight: 400; color: var(--gray-500);
    }

    .saod-v2 .saod-card-footer {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--gray-100);
        font-size: 12px; color: var(--gray-400); flex-wrap: wrap; gap: 6px;
    }
    .saod-v2 .saod-card-footer a {
        color: var(--primary); text-decoration: none;
    }
    .saod-v2 .saod-card-footer a:hover { text-decoration: underline; }

    .saod-v2 .saod-savings {
        font-size: 12px; color: var(--orange); font-weight: 600; margin-top: 8px;
        text-align: center;
    }
    .saod-v2 .saod-savings.saod-savings-best {
        color: var(--green);
    }

    /* Compare bar */
    .saod-v2 .saod-compare-bar {
        display: none; position: sticky; bottom: 0; left: 0; right: 0;
        background: var(--primary); color: #fff; padding: 10px 16px;
        border-radius: var(--radius) var(--radius) 0 0;
        text-align: center; font-size: 14px; cursor: pointer;
        box-shadow: 0 -2px 8px rgba(0,0,0,.15); z-index: 10;
        margin-top: 12px;
    }
    .saod-v2 .saod-compare-bar.visible { display: block; }

    /* Compare checkbox */
    .saod-v2 .saod-compare-check {
        position: absolute; top: 16px; left: 16px; cursor: pointer;
    }
    .saod-v2 .saod-compare-check input { margin: 0; cursor: pointer; width: 16px; height: 16px; }

    /* Info box */
    .saod-v2 .saod-info {
        background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius);
        padding: 14px 18px; margin-top: 16px; font-size: 13px; color: #92400e;
        line-height: 1.8;
    }
    .saod-v2 .saod-info summary {
        cursor: pointer; font-weight: 700; font-size: 13px;
        list-style: none; display: flex; align-items: center; gap: 6px;
    }
    .saod-v2 .saod-info summary::before { content: '▸'; transition: transform .15s; }
    .saod-v2 .saod-info[open] summary::before { transform: rotate(90deg); }
    .saod-v2 .saod-info .saod-info-body { margin-top: 8px; }

    /* Compare modal overlay */
    .saod-v2 .saod-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
        z-index: 9999; justify-content: center; align-items: center; padding: 16px;
    }
    .saod-v2 .saod-modal-overlay.visible { display: flex; }
    .saod-v2 .saod-modal {
        background: #fff; border-radius: var(--radius); padding: 24px;
        max-width: 700px; width: 100%; max-height: 85vh; overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,.3); position: relative;
    }
    .saod-v2 .saod-modal-close {
        position: absolute; top: 12px; left: 12px; background: none;
        border: none; font-size: 22px; cursor: pointer; color: var(--gray-400);
        font-family: inherit;
    }
    .saod-v2 .saod-modal h4 { margin: 0 0 16px; font-size: 18px; text-align: center; }
    .saod-v2 .saod-compare-grid {
        display: grid; gap: 12px;
    }
    .saod-v2 .saod-compare-row {
        display: grid; gap: 8px; padding: 10px 0;
        border-bottom: 1px solid var(--gray-100);
    }
    .saod-v2 .saod-compare-row-label {
        font-size: 12px; color: var(--gray-400); font-weight: 600;
    }
    .saod-v2 .saod-compare-row-values {
        display: grid; gap: 8px;
    }
    .saod-v2 .saod-compare-cell {
        text-align: center; font-size: 15px; font-weight: 600;
    }
    .saod-v2 .saod-compare-cell.highlight { color: var(--green); }

    /* Responsive */
    @media (max-width: 600px) {
        .saod-v2 .saod-card-main { grid-template-columns: 1fr; gap: 8px; }
        .saod-v2 .saod-metric { text-align: right; display: flex; justify-content: space-between; align-items: baseline; }
        .saod-v2 .saod-metric-label { margin-bottom: 0; }
        .saod-v2 .saod-metric-value { font-size: 18px; }
        .saod-v2 .saod-inputs { flex-direction: column; align-items: stretch; }
        .saod-v2 .saod-input-group input,
        .saod-v2 .saod-input-group select { width: 100%; min-width: unset; }
    }
    </style>

    <div class="saod-header">
        <h3>مقارنة نسب التمويل في البنوك السعودية</h3>
        <div class="saod-sub">آخر تحديث: <?php echo $last_updated; ?> · الأرقام تمثل أقل نسبة معلنة (يبدأ من)</div>
    </div>

    <!-- Tabs -->
    <div class="saod-tabs">
        <button type="button" class="saod-tab-btn active" data-tab="personal">تمويل شخصي</button>
        <button type="button" class="saod-tab-btn" data-tab="buyout">شراء مديونية</button>
        <button type="button" class="saod-tab-btn" data-tab="mortgage">تمويل عقاري</button>
    </div>

    <!-- Calculator inputs -->
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

    <!-- Cards container -->
    <div class="saod-cards" id="<?php echo esc_attr( $uid ); ?>-cards"></div>

    <!-- Compare bar -->
    <div class="saod-compare-bar" id="<?php echo esc_attr( $uid ); ?>-compare-bar">
        بين الفرق بين البنوك المحددة (<span class="compare-count">0</span>)
    </div>

    <!-- Compare modal -->
    <div class="saod-modal-overlay" id="<?php echo esc_attr( $uid ); ?>-modal">
        <div class="saod-modal">
            <button type="button" class="saod-modal-close">&times;</button>
            <h4>مقارنة تفصيلية</h4>
            <div class="saod-compare-grid" id="<?php echo esc_attr( $uid ); ?>-compare-body"></div>
        </div>
    </div>

    <!-- Education note -->
    <details class="saod-info">
        <summary>ما الفرق بين النسبة الثابتة (Flat) و APR؟</summary>
        <div class="saod-info-body">
            البنوك تعلن عادةً «هامش الربح الثابت» لأنه يبدو رقماً أصغر — لكن النسبة الحقيقية التي تدفعها هي <strong>APR</strong> (معدل النسبة السنوي)، وهي تقريباً ضعف الرقم المعلن.
            <br>السبب: الهامش الثابت يُحسب على كامل المبلغ الأصلي طوال المدة، بينما APR يأخذ بالحسبان أنك تسدد جزءاً من الأصل كل شهر.
            <br><strong>المقارنة الصحيحة بين البنوك تكون دائماً بالـ APR.</strong>
            <br>الأرقام المعلّمة بـ (*) مشتقة رياضياً من النسبة الأخرى المعلنة من البنك.
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
        function formatNum(n) {
            return n.toLocaleString('ar-SA');
        }
        function formatAmount(el) {
            var v = parseAmount(el.value);
            if (v > 0) el.value = formatNum(v);
        }

        function calcEMI(principal, aprPct, years) {
            var months = years * 12;
            var r = aprPct / 100 / 12;
            if (r < 1e-9) return principal / months;
            return r * principal / (1 - Math.pow(1 + r, -months));
        }

        function sortBanks(tab) {
            var arr = banks.slice();
            arr.sort(function(a, b) {
                var aprA = (a[tab] && a[tab].apr != null && a[tab].status !== 'unavailable') ? a[tab].apr : null;
                var aprB = (b[tab] && b[tab].apr != null && b[tab].status !== 'unavailable') ? b[tab].apr : null;
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
            var sorted = sortBanks(currentTab);

            var bestApr = null;
            var bestEMI = null;
            var maxTotal = 0;
            for (var i = 0; i < sorted.length; i++) {
                var p = sorted[i][currentTab];
                if (p && p.apr != null && p.status !== 'unavailable') {
                    var emi = calcEMI(amount, p.apr, years);
                    var total = emi * years * 12;
                    if (bestApr === null) { bestApr = p.apr; bestEMI = emi; }
                    if (total > maxTotal) maxTotal = total;
                }
            }

            var html = '';
            var rank = 0;
            for (var i = 0; i < sorted.length; i++) {
                var bank = sorted[i];
                var p = bank[currentTab];
                if (!p) continue;

                var apr = p.apr;
                var flat = p.flat;
                var status = p.status || 'unavailable';
                var derived = p.apr_derived || false;
                var flatDerived = p.flat_derived || false;
                var source = (bank.source_url && bank.source_url[currentTab]) || '';
                var maxAmt = p.max_amount;
                var maxYrs = p.max_years;
                var isUnavailable = (status === 'unavailable' || apr == null);
                var isBest = (!isUnavailable && apr === bestApr);

                rank++;
                var cardClass = 'saod-card';
                if (isBest) cardClass += ' saod-best';
                if (isUnavailable) cardClass += ' saod-unavailable';

                var checked = compareSet.has(bank.id) ? ' checked' : '';

                html += '<div class="' + cardClass + '" data-bank="' + bank.id + '">';

                if (!isUnavailable) {
                    html += '<label class="saod-compare-check" title="اختر للمقارنة"><input type="checkbox" data-id="' + bank.id + '"' + checked + '></label>';
                }

                html += '<div class="saod-card-top">';
                html += '<p class="saod-bank-name">' + escHtml(bank.name_ar) + '</p>';
                if (isBest) {
                    html += '<span class="saod-badge saod-badge-best">الأفضل</span>';
                } else if (!isUnavailable) {
                    html += '<span class="saod-badge saod-badge-rank">#' + rank + '</span>';
                }
                html += '</div>';

                if (isUnavailable) {
                    html += '<div style="text-align:center;padding:16px 0;color:var(--gray-400);">غير متاح حالياً لهذا المنتج</div>';
                } else {
                    var emi = calcEMI(amount, apr, years);
                    var totalCost = emi * years * 12;
                    var totalProfit = totalCost - amount;

                    html += '<div class="saod-card-main">';

                    html += '<div class="saod-metric">';
                    html += '<div class="saod-metric-label">القسط الشهري</div>';
                    html += '<div class="saod-metric-value saod-rial">' + formatNum(Math.round(emi)) + ' <small>ر.س</small></div>';
                    html += '</div>';

                    html += '<div class="saod-metric">';
                    html += '<div class="saod-metric-label">إجمالي الربح</div>';
                    html += '<div class="saod-metric-value">' + formatNum(Math.round(totalProfit)) + ' <small>ر.س</small></div>';
                    html += '</div>';

                    html += '<div class="saod-metric">';
                    html += '<div class="saod-metric-label">APR (يبدأ من)</div>';
                    html += '<div class="saod-metric-value">' + apr + '%';
                    if (derived) html += ' <span title="مشتق رياضياً" style="color:#f0ad4e;cursor:help;font-size:14px;">*</span>';
                    if (status === 'stale') html += ' <span title="قد لا تكون محدثة" style="cursor:help;font-size:14px;">⚠</span>';
                    html += '</div>';
                    html += '</div>';

                    html += '</div>';

                    if (isBest) {
                        html += '<div class="saod-savings saod-savings-best">الأقل تكلفة بين جميع البنوك</div>';
                    } else if (bestEMI != null) {
                        var diff = totalCost - (bestEMI * years * 12);
                        if (diff > 0) {
                            html += '<div class="saod-savings">تكلفة إضافية ' + formatNum(Math.round(diff)) + ' ر.س مقارنة بالأرخص</div>';
                        }
                    }
                }

                html += '<div class="saod-card-footer">';
                var footerParts = [];
                if (flat != null) {
                    var flatStr = 'الثابت: ' + flat + '%';
                    if (flatDerived) flatStr += ' *';
                    footerParts.push(flatStr);
                }
                if (maxAmt) footerParts.push('أقصى مبلغ: ' + formatNum(maxAmt));
                if (maxYrs) footerParts.push('أقصى مدة: ' + maxYrs + ' سنة');
                html += '<span>' + footerParts.join(' · ') + '</span>';
                if (source) {
                    html += '<a href="' + escAttr(source) + '" target="_blank" rel="nofollow noopener">موقع البنك ↗</a>';
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
                var minVal = Infinity;
                var numericVals = values.map(function(v) { return typeof v === 'number' ? v : Infinity; });
                minVal = Math.min.apply(null, numericVals);
                values.forEach(function(v, i) {
                    var cls = 'saod-compare-cell';
                    if (typeof v === 'number' && v === minVal && numericVals.filter(function(x){return x===minVal;}).length < numericVals.length) cls += ' highlight';
                    var display = typeof v === 'number' ? formatNum(Math.round(v)) : v;
                    html += '<div class="' + cls + '">' + display + '</div>';
                });
                html += '</div></div>';
            }

            html += '<div class="saod-compare-row"><div class="saod-compare-row-label">البنك</div>';
            html += '<div class="saod-compare-row-values" style="grid-template-columns:' + gridCols + '">';
            selected.forEach(function(b) {
                html += '<div class="saod-compare-cell" style="font-weight:700;">' + escHtml(b.name_ar) + '</div>';
            });
            html += '</div></div>';

            row('APR (يبدأ من)', selected.map(function(b) {
                var p = b[currentTab];
                return (p && p.apr != null) ? p.apr + '%' : '—';
            }));

            row('الثابت (Flat)', selected.map(function(b) {
                var p = b[currentTab];
                return (p && p.flat != null) ? p.flat + '%' : '—';
            }));

            row('القسط الشهري', selected.map(function(b) {
                var p = b[currentTab];
                if (!p || p.apr == null) return '—';
                return calcEMI(amount, p.apr, years);
            }));

            row('إجمالي السداد', selected.map(function(b) {
                var p = b[currentTab];
                if (!p || p.apr == null) return '—';
                return calcEMI(amount, p.apr, years) * years * 12;
            }));

            row('إجمالي الربح', selected.map(function(b) {
                var p = b[currentTab];
                if (!p || p.apr == null) return '—';
                return calcEMI(amount, p.apr, years) * years * 12 - amount;
            }));

            row('أقصى مبلغ', selected.map(function(b) {
                var p = b[currentTab];
                return (p && p.max_amount) ? formatNum(p.max_amount) + ' ر.س' : '—';
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

        // Tab switching
        wrap.querySelectorAll('.saod-tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.saod-tab-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentTab = btn.getAttribute('data-tab');

                // Auto-adjust years for mortgage
                if (currentTab === 'mortgage' && parseInt(yearsEl.value) <= 5) {
                    yearsEl.value = '20';
                } else if (currentTab !== 'mortgage' && parseInt(yearsEl.value) > 5) {
                    yearsEl.value = '5';
                }

                compareSet.clear();
                render();
            });
        });

        // Input events
        amountEl.addEventListener('input', function() { formatAmount(amountEl); render(); });
        amountEl.addEventListener('blur', function() { formatAmount(amountEl); });
        yearsEl.addEventListener('change', render);

        // Compare bar click
        compareBar.addEventListener('click', showCompare);

        // Modal close
        modalOverlay.querySelector('.saod-modal-close').addEventListener('click', function() {
            modalOverlay.classList.remove('visible');
        });
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) modalOverlay.classList.remove('visible');
        });

        // Initial render
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
        $msg = $rates ? 'تم تحديث النسب بنجاح ✓' : 'فشل التحديث — تحقق من الاتصال';
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
