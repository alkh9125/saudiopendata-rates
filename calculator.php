<?php
/**
 * Plugin Name: حاسبة التمويل السعودية
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: حاسبة تمويل شخصي وعقاري شاملة — القسط الشهري، إجمالي الأرباح، جدول السداد، والسداد المبكر
 * Version: 1.0.0
 * Author: SaudiOpenData
 * Text Domain: saod-calc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function saod_calc_shortcode( $atts ) {
    $uid = 'saod-calc-' . wp_unique_id();
    ob_start();
    ?>
    <div class="saod-calc" id="<?php echo esc_attr( $uid ); ?>" dir="rtl">

    <style>
    .saod-calc {
        --primary: #0073aa;
        --primary-dark: #005a87;
        --primary-light: #e8f4fd;
        --green: #16a34a;
        --green-light: #f0fdf4;
        --orange: #ea580c;
        --red: #dc2626;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-700: #374151;
        --gray-900: #111827;
        --radius: 12px;
        --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
        --shadow-lg: 0 10px 25px rgba(0,0,0,.1), 0 4px 10px rgba(0,0,0,.06);
        font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
        max-width: 800px;
        margin: 30px auto;
        color: var(--gray-900);
        line-height: 1.6;
    }
    .saod-calc * { box-sizing: border-box; }
    .saod-calc input, .saod-calc select, .saod-calc button { font-family: inherit; }

    .saod-calc .calc-header {
        text-align: center; margin-bottom: 28px;
    }
    .saod-calc .calc-header h3 {
        font-size: 24px; font-weight: 700; margin: 0 0 6px;
    }
    .saod-calc .calc-header p {
        font-size: 14px; color: var(--gray-500); margin: 0;
    }

    /* Type tabs */
    .saod-calc .calc-types {
        display: flex; justify-content: center; gap: 8px;
        margin-bottom: 24px; flex-wrap: wrap;
    }
    .saod-calc .calc-type-btn {
        padding: 8px 22px; border: 1px solid var(--gray-200); border-radius: 20px;
        background: #fff; color: var(--gray-700); cursor: pointer; font-size: 14px;
        transition: all .15s;
    }
    .saod-calc .calc-type-btn:hover { border-color: var(--primary); color: var(--primary); }
    .saod-calc .calc-type-btn.active {
        background: var(--primary); color: #fff; border-color: var(--primary);
    }

    /* Input form */
    .saod-calc .calc-form {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 24px; box-shadow: var(--shadow); margin-bottom: 24px;
    }
    .saod-calc .calc-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
    }
    .saod-calc .calc-field { display: flex; flex-direction: column; gap: 6px; }
    .saod-calc .calc-field.full { grid-column: 1 / -1; }
    .saod-calc .calc-field label {
        font-size: 13px; font-weight: 600; color: var(--gray-700);
    }
    .saod-calc .calc-field label small {
        font-weight: 400; color: var(--gray-400);
    }
    .saod-calc .calc-field .input-wrap {
        position: relative; display: flex; align-items: center;
    }
    .saod-calc .calc-field input,
    .saod-calc .calc-field select {
        width: 100%; padding: 10px 14px; border: 1px solid var(--gray-200);
        border-radius: 8px; font-size: 15px; background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    .saod-calc .calc-field input:focus,
    .saod-calc .calc-field select:focus {
        outline: none; border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0,115,170,.12);
    }
    .saod-calc .calc-field .input-suffix {
        position: absolute; left: 12px; color: var(--gray-400);
        font-size: 13px; pointer-events: none;
    }
    .saod-calc .calc-field input.has-suffix { padding-left: 50px; }

    /* Slider */
    .saod-calc .calc-slider-row {
        display: flex; align-items: center; gap: 12px;
    }
    .saod-calc .calc-slider-row input[type="range"] {
        flex: 1; accent-color: var(--primary); height: 6px; cursor: pointer;
        padding: 0; border: none;
    }
    .saod-calc .calc-slider-row input[type="range"]:focus { box-shadow: none; }
    .saod-calc .calc-slider-val {
        min-width: 60px; text-align: center; font-weight: 700;
        font-size: 15px; color: var(--primary);
    }

    /* Rate input toggle */
    .saod-calc .rate-toggle {
        display: flex; gap: 0; border: 1px solid var(--gray-200); border-radius: 8px;
        overflow: hidden; margin-bottom: 8px;
    }
    .saod-calc .rate-toggle button {
        flex: 1; padding: 6px 12px; border: none; background: #fff;
        font-size: 12px; cursor: pointer; color: var(--gray-500); transition: all .15s;
    }
    .saod-calc .rate-toggle button.active {
        background: var(--primary); color: #fff;
    }

    /* Results */
    .saod-calc .calc-results {
        display: none;
    }
    .saod-calc .calc-results.visible { display: block; }

    .saod-calc .results-hero {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius); padding: 28px; color: #fff;
        text-align: center; margin-bottom: 16px; box-shadow: var(--shadow-lg);
    }
    .saod-calc .results-hero .hero-label {
        font-size: 14px; opacity: .85; margin-bottom: 4px;
    }
    .saod-calc .results-hero .hero-value {
        font-size: 36px; font-weight: 700; letter-spacing: -0.5px;
    }
    .saod-calc .results-hero .hero-value small {
        font-size: 16px; font-weight: 400; opacity: .8;
    }

    .saod-calc .results-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
        margin-bottom: 16px;
    }
    .saod-calc .result-card {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 16px; text-align: center; box-shadow: var(--shadow);
    }
    .saod-calc .result-card .rc-label {
        font-size: 12px; color: var(--gray-400); margin-bottom: 4px;
    }
    .saod-calc .result-card .rc-value {
        font-size: 20px; font-weight: 700;
    }
    .saod-calc .result-card .rc-value small {
        font-size: 12px; font-weight: 400; color: var(--gray-400);
    }
    .saod-calc .result-card .rc-sub {
        font-size: 11px; color: var(--gray-400); margin-top: 4px;
    }

    /* Salary check */
    .saod-calc .salary-check {
        border-radius: var(--radius); padding: 14px 18px; margin-bottom: 16px;
        font-size: 13px; display: none;
    }
    .saod-calc .salary-check.visible { display: block; }
    .saod-calc .salary-check.ok {
        background: var(--green-light); border: 1px solid #bbf7d0; color: #166534;
    }
    .saod-calc .salary-check.warn {
        background: #fef3c7; border: 1px solid #fde68a; color: #92400e;
    }
    .saod-calc .salary-check.danger {
        background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
    }

    /* Pie chart */
    .saod-calc .chart-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 20px; box-shadow: var(--shadow); margin-bottom: 16px;
        display: flex; align-items: center; justify-content: center; gap: 32px;
        flex-wrap: wrap;
    }
    .saod-calc .chart-pie {
        width: 160px; height: 160px; border-radius: 50%; position: relative;
        flex-shrink: 0;
    }
    .saod-calc .chart-center {
        position: absolute; inset: 25px; background: #fff; border-radius: 50%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .saod-calc .chart-center .pct { font-size: 22px; font-weight: 700; color: var(--gray-900); }
    .saod-calc .chart-center .pct-label { font-size: 10px; color: var(--gray-400); }
    .saod-calc .chart-legend { display: flex; flex-direction: column; gap: 10px; }
    .saod-calc .chart-legend-item {
        display: flex; align-items: center; gap: 8px; font-size: 14px;
    }
    .saod-calc .chart-legend-dot {
        width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0;
    }

    /* Early settlement */
    .saod-calc .early-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 20px; box-shadow: var(--shadow); margin-bottom: 16px;
    }
    .saod-calc .early-section h4 {
        font-size: 15px; margin: 0 0 12px; color: var(--gray-700);
    }
    .saod-calc .early-result {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;
    }
    .saod-calc .early-card {
        background: var(--gray-50); border-radius: 8px; padding: 12px; text-align: center;
    }
    .saod-calc .early-card .ec-label { font-size: 11px; color: var(--gray-400); }
    .saod-calc .early-card .ec-value { font-size: 18px; font-weight: 700; margin-top: 2px; }
    .saod-calc .early-card .ec-value.savings { color: var(--green); }

    /* Schedule table */
    .saod-calc .schedule-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        box-shadow: var(--shadow); margin-bottom: 16px; overflow: hidden;
    }
    .saod-calc .schedule-toggle {
        padding: 14px 20px; cursor: pointer; font-size: 14px; font-weight: 600;
        color: var(--gray-700); display: flex; justify-content: space-between;
        align-items: center; user-select: none;
    }
    .saod-calc .schedule-toggle:hover { background: var(--gray-50); }
    .saod-calc .schedule-toggle .arrow { transition: transform .2s; }
    .saod-calc .schedule-section.open .arrow { transform: rotate(180deg); }
    .saod-calc .schedule-table-wrap {
        display: none; max-height: 400px; overflow-y: auto;
    }
    .saod-calc .schedule-section.open .schedule-table-wrap { display: block; }
    .saod-calc .schedule-table {
        width: 100%; border-collapse: collapse; font-size: 13px;
    }
    .saod-calc .schedule-table thead { position: sticky; top: 0; z-index: 1; }
    .saod-calc .schedule-table th {
        background: var(--gray-100); padding: 8px 12px; text-align: center;
        font-size: 12px; color: var(--gray-500); font-weight: 600;
        border-bottom: 1px solid var(--gray-200);
    }
    .saod-calc .schedule-table td {
        padding: 8px 12px; text-align: center; border-bottom: 1px solid var(--gray-100);
    }
    .saod-calc .schedule-table tr:hover td { background: var(--primary-light); }
    .saod-calc .schedule-table .yr-row td {
        background: var(--gray-50); font-weight: 700; font-size: 12px;
        color: var(--primary);
    }

    /* Disclaimer */
    .saod-calc .calc-disclaimer {
        font-size: 12px; color: var(--gray-400); text-align: center;
        margin-top: 16px; line-height: 1.8;
    }

    @media (max-width: 600px) {
        .saod-calc .calc-grid { grid-template-columns: 1fr; }
        .saod-calc .results-grid { grid-template-columns: 1fr; }
        .saod-calc .results-hero .hero-value { font-size: 28px; }
        .saod-calc .chart-section { flex-direction: column; }
        .saod-calc .early-result { grid-template-columns: 1fr; }
    }
    </style>

    <div class="calc-header">
        <h3>حاسبة التمويل</h3>
        <p>احسب القسط الشهري، إجمالي التكلفة، وجدول السداد التفصيلي</p>
    </div>

    <!-- Type tabs -->
    <div class="calc-types">
        <button type="button" class="calc-type-btn active" data-type="personal">تمويل شخصي</button>
        <button type="button" class="calc-type-btn" data-type="buyout">شراء مديونية</button>
        <button type="button" class="calc-type-btn" data-type="mortgage">تمويل عقاري</button>
    </div>

    <!-- Form -->
    <div class="calc-form">
        <div class="calc-grid">
            <div class="calc-field">
                <label>مبلغ التمويل</label>
                <div class="input-wrap">
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-amount" value="300,000" inputmode="numeric" class="has-suffix">
                    <span class="input-suffix">ر.س</span>
                </div>
            </div>

            <div class="calc-field">
                <label>الراتب الشهري <small>(اختياري)</small></label>
                <div class="input-wrap">
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-salary" placeholder="مثال: 12,000" inputmode="numeric" class="has-suffix">
                    <span class="input-suffix">ر.س</span>
                </div>
            </div>

            <div class="calc-field full">
                <label>مدة التمويل</label>
                <div class="calc-slider-row">
                    <input type="range" id="<?php echo esc_attr( $uid ); ?>-years-slider" min="1" max="5" value="5" step="1">
                    <div class="calc-slider-val"><span id="<?php echo esc_attr( $uid ); ?>-years-display">5</span> سنوات</div>
                </div>
            </div>

            <div class="calc-field full">
                <label>نسبة الربح</label>
                <div class="rate-toggle">
                    <button type="button" class="active" data-mode="apr">APR — معدل النسبة السنوي</button>
                    <button type="button" data-mode="flat">Flat — هامش الربح الثابت</button>
                </div>
                <div class="calc-slider-row">
                    <input type="range" id="<?php echo esc_attr( $uid ); ?>-rate-slider" min="0.5" max="12" value="4.25" step="0.01">
                    <div class="calc-slider-val"><span id="<?php echo esc_attr( $uid ); ?>-rate-display">4.25</span>%</div>
                </div>
                <div style="font-size:12px;color:var(--gray-400);margin-top:4px;" id="<?php echo esc_attr( $uid ); ?>-rate-converted"></div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="calc-results" id="<?php echo esc_attr( $uid ); ?>-results">

        <!-- Hero: Monthly payment -->
        <div class="results-hero">
            <div class="hero-label">القسط الشهري</div>
            <div class="hero-value"><span id="<?php echo esc_attr( $uid ); ?>-emi">0</span> <small>ر.س / شهر</small></div>
        </div>

        <!-- Key metrics -->
        <div class="results-grid">
            <div class="result-card">
                <div class="rc-label">إجمالي السداد</div>
                <div class="rc-value"><span id="<?php echo esc_attr( $uid ); ?>-total">0</span> <small>ر.س</small></div>
            </div>
            <div class="result-card">
                <div class="rc-label">إجمالي الأرباح (التكلفة)</div>
                <div class="rc-value" style="color:var(--orange);"><span id="<?php echo esc_attr( $uid ); ?>-profit">0</span> <small>ر.س</small></div>
                <div class="rc-sub" id="<?php echo esc_attr( $uid ); ?>-profit-pct"></div>
            </div>
            <div class="result-card">
                <div class="rc-label">عدد الأقساط</div>
                <div class="rc-value"><span id="<?php echo esc_attr( $uid ); ?>-months">0</span> <small>شهر</small></div>
            </div>
        </div>

        <!-- Salary check -->
        <div class="salary-check" id="<?php echo esc_attr( $uid ); ?>-salary-check"></div>

        <!-- Pie chart -->
        <div class="chart-section">
            <div class="chart-pie" id="<?php echo esc_attr( $uid ); ?>-pie"></div>
            <div class="chart-legend">
                <div class="chart-legend-item">
                    <div class="chart-legend-dot" style="background:var(--primary);"></div>
                    <span>أصل المبلغ: <strong id="<?php echo esc_attr( $uid ); ?>-leg-principal">0</strong> ر.س</span>
                </div>
                <div class="chart-legend-item">
                    <div class="chart-legend-dot" style="background:var(--orange);"></div>
                    <span>أرباح البنك: <strong id="<?php echo esc_attr( $uid ); ?>-leg-interest">0</strong> ر.س</span>
                </div>
            </div>
        </div>

        <!-- Early settlement -->
        <div class="early-section">
            <h4>السداد المبكر</h4>
            <div class="calc-field">
                <label>السداد بعد <small>(أشهر من بداية التمويل)</small></label>
                <div class="calc-slider-row">
                    <input type="range" id="<?php echo esc_attr( $uid ); ?>-early-slider" min="1" max="60" value="24" step="1">
                    <div class="calc-slider-val"><span id="<?php echo esc_attr( $uid ); ?>-early-display">24</span> شهر</div>
                </div>
            </div>
            <div class="early-result">
                <div class="early-card">
                    <div class="ec-label">المبلغ المتبقي للسداد</div>
                    <div class="ec-value" id="<?php echo esc_attr( $uid ); ?>-early-remaining">0</div>
                </div>
                <div class="early-card">
                    <div class="ec-label">الأرباح المدفوعة</div>
                    <div class="ec-value" id="<?php echo esc_attr( $uid ); ?>-early-paid">0</div>
                </div>
                <div class="early-card">
                    <div class="ec-label">الأرباح الموفّرة</div>
                    <div class="ec-value savings" id="<?php echo esc_attr( $uid ); ?>-early-saved">0</div>
                </div>
                <div class="early-card">
                    <div class="ec-label">نسبة التوفير</div>
                    <div class="ec-value savings" id="<?php echo esc_attr( $uid ); ?>-early-pct">0%</div>
                </div>
            </div>
        </div>

        <!-- Amortization schedule -->
        <div class="schedule-section" id="<?php echo esc_attr( $uid ); ?>-schedule">
            <div class="schedule-toggle">
                <span>جدول السداد التفصيلي</span>
                <span class="arrow">▾</span>
            </div>
            <div class="schedule-table-wrap">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>الشهر</th>
                            <th>القسط</th>
                            <th>من الأصل</th>
                            <th>الأرباح</th>
                            <th>الرصيد المتبقي</th>
                        </tr>
                    </thead>
                    <tbody id="<?php echo esc_attr( $uid ); ?>-schedule-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="calc-disclaimer">
        الأرقام تقريبية للمقارنة فقط ولا تمثل عرضاً رسمياً من أي بنك.
        النسبة الفعلية تعتمد على تقييم البنك لملفك الائتماني.
    </div>

    <script>
    (function(){
        var uid = <?php echo wp_json_encode( $uid ); ?>;
        var el = function(id) { return document.getElementById(uid + '-' + id); };
        var wrap = document.getElementById(uid);
        if (!wrap) return;

        var currentType = 'personal';
        var rateMode = 'apr'; // 'apr' or 'flat'

        var amountEl = el('amount');
        var salaryEl = el('salary');
        var yearsSlider = el('years-slider');
        var yearsDisplay = el('years-display');
        var rateSlider = el('rate-slider');
        var rateDisplay = el('rate-display');
        var rateConverted = el('rate-converted');
        var earlySlider = el('early-slider');
        var earlyDisplay = el('early-display');
        var resultsEl = el('results');

        function parseNum(s) { return parseInt(String(s).replace(/[^0-9]/g, ''), 10) || 0; }
        function fmt(n) { return Math.round(n).toLocaleString('ar-SA'); }
        function formatInput(input) {
            var v = parseNum(input.value);
            if (v > 0) input.value = fmt(v);
        }

        function flatToApr(flatPct, years) {
            var months = years * 12;
            var flat = flatPct / 100;
            var principal = 100000;
            var totalInterest = principal * flat * years;
            var emi = (principal + totalInterest) / months;

            var lo = 1e-9, hi = 0.5;
            for (var iter = 0; iter < 100; iter++) {
                var mid = (lo + hi) / 2;
                var pv = mid < 1e-9 ? emi * months : emi * (1 - Math.pow(1 + mid, -months)) / mid;
                if (pv > principal) lo = mid; else hi = mid;
            }
            return Math.round((lo + hi) / 2 * 12 * 10000) / 100;
        }

        function aprToFlat(aprPct, years) {
            var months = years * 12;
            var r = aprPct / 100 / 12;
            var principal = 100000;
            var emi = r < 1e-9 ? principal / months : r * principal / (1 - Math.pow(1 + r, -months));
            var totalInterest = emi * months - principal;
            return Math.round(totalInterest / (principal * years) * 10000) / 100;
        }

        function calcEMI(principal, aprPct, months) {
            var r = aprPct / 100 / 12;
            if (r < 1e-9) return principal / months;
            return r * principal / (1 - Math.pow(1 + r, -months));
        }

        function getApr() {
            var val = parseFloat(rateSlider.value) || 0;
            var years = parseInt(yearsSlider.value) || 5;
            if (rateMode === 'flat') return flatToApr(val, years);
            return val;
        }

        function updateConversion() {
            var val = parseFloat(rateSlider.value) || 0;
            var years = parseInt(yearsSlider.value) || 5;
            if (rateMode === 'apr') {
                var flat = aprToFlat(val, years);
                rateConverted.textContent = 'يعادل هامش ربح ثابت (Flat) ≈ ' + flat + '%';
            } else {
                var apr = flatToApr(val, years);
                rateConverted.textContent = 'يعادل معدل نسبة سنوي (APR) ≈ ' + apr + '%';
            }
        }

        function render() {
            var amount = parseNum(amountEl.value);
            var salary = parseNum(salaryEl.value);
            var years = parseInt(yearsSlider.value) || 5;
            var months = years * 12;
            var apr = getApr();

            if (amount <= 0 || apr <= 0) {
                resultsEl.classList.remove('visible');
                return;
            }
            resultsEl.classList.add('visible');

            var emi = calcEMI(amount, apr, months);
            var total = emi * months;
            var profit = total - amount;
            var profitPct = (profit / amount * 100).toFixed(1);

            el('emi').textContent = fmt(emi);
            el('total').textContent = fmt(total);
            el('profit').textContent = fmt(profit);
            el('profit-pct').textContent = profitPct + '% من أصل المبلغ';
            el('months').textContent = months;

            // Salary check
            var salaryCheck = el('salary-check');
            if (salary > 0) {
                var ratio = emi / salary * 100;
                salaryCheck.classList.add('visible');
                if (ratio <= 33) {
                    salaryCheck.className = 'salary-check visible ok';
                    salaryCheck.innerHTML = '✓ القسط يمثل <strong>' + ratio.toFixed(1) + '%</strong> من راتبك — ضمن الحد المقبول (أقل من 33%)';
                } else if (ratio <= 45) {
                    salaryCheck.className = 'salary-check visible warn';
                    salaryCheck.innerHTML = '⚠ القسط يمثل <strong>' + ratio.toFixed(1) + '%</strong> من راتبك — قريب من الحد الأقصى. قد يؤثر على موافقة البنك.';
                } else {
                    salaryCheck.className = 'salary-check visible danger';
                    salaryCheck.innerHTML = '✕ القسط يمثل <strong>' + ratio.toFixed(1) + '%</strong> من راتبك — يتجاوز النسبة المسموحة. قد يُرفض طلب التمويل.';
                }
            } else {
                salaryCheck.classList.remove('visible');
            }

            // Pie chart
            var principalPct = amount / total * 100;
            var interestPct = 100 - principalPct;
            var deg = principalPct / 100 * 360;
            el('pie').style.background = 'conic-gradient(var(--primary) 0deg ' + deg + 'deg, var(--orange) ' + deg + 'deg 360deg)';
            el('pie').innerHTML = '<div class="chart-center"><span class="pct">' + interestPct.toFixed(1) + '%</span><span class="pct-label">أرباح البنك</span></div>';
            el('leg-principal').textContent = fmt(amount);
            el('leg-interest').textContent = fmt(profit);

            // Early settlement
            earlySlider.max = months - 1;
            if (parseInt(earlySlider.value) >= months) earlySlider.value = Math.floor(months / 2);
            calcEarly();

            // Schedule
            buildSchedule(amount, apr, months, emi);

            updateConversion();
        }

        function calcEarly() {
            var amount = parseNum(amountEl.value);
            var years = parseInt(yearsSlider.value) || 5;
            var months = years * 12;
            var apr = getApr();
            var r = apr / 100 / 12;
            var emi = calcEMI(amount, apr, months);
            var total = emi * months;
            var totalProfit = total - amount;

            var earlyMonth = parseInt(earlySlider.value) || 1;
            earlyDisplay.textContent = earlyMonth;

            var balance = amount;
            var paidInterest = 0;
            for (var m = 0; m < earlyMonth; m++) {
                var interest = balance * r;
                var principal = emi - interest;
                paidInterest += interest;
                balance -= principal;
            }
            if (balance < 0) balance = 0;

            var remainingInterest = totalProfit - paidInterest;
            var savedPct = totalProfit > 0 ? (remainingInterest / totalProfit * 100).toFixed(1) : 0;

            el('early-remaining').textContent = fmt(balance) + ' ر.س';
            el('early-paid').textContent = fmt(paidInterest) + ' ر.س';
            el('early-saved').textContent = fmt(remainingInterest) + ' ر.س';
            el('early-pct').textContent = savedPct + '%';
        }

        function buildSchedule(amount, apr, months, emi) {
            var r = apr / 100 / 12;
            var balance = amount;
            var html = '';
            var yearPrincipal = 0, yearInterest = 0;

            for (var m = 1; m <= months; m++) {
                var interest = balance * r;
                var principal = emi - interest;
                if (m === months) { principal = balance; interest = emi - principal; }
                balance -= principal;
                if (balance < 0) balance = 0;

                yearPrincipal += principal;
                yearInterest += interest;

                html += '<tr>';
                html += '<td>' + m + '</td>';
                html += '<td>' + fmt(emi) + '</td>';
                html += '<td>' + fmt(principal) + '</td>';
                html += '<td>' + fmt(interest) + '</td>';
                html += '<td>' + fmt(balance) + '</td>';
                html += '</tr>';

                if (m % 12 === 0 || m === months) {
                    var yr = Math.ceil(m / 12);
                    html += '<tr class="yr-row"><td colspan="5">السنة ' + yr + ' — الأصل: ' + fmt(yearPrincipal) + ' ر.س | الأرباح: ' + fmt(yearInterest) + ' ر.س</td></tr>';
                    yearPrincipal = 0;
                    yearInterest = 0;
                }
            }
            el('schedule-body').innerHTML = html;
        }

        // Type tabs
        wrap.querySelectorAll('.calc-type-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.calc-type-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentType = btn.getAttribute('data-type');
                var isMortgage = currentType === 'mortgage';
                yearsSlider.max = isMortgage ? 30 : 5;
                if (isMortgage && parseInt(yearsSlider.value) <= 5) yearsSlider.value = 20;
                if (!isMortgage && parseInt(yearsSlider.value) > 5) yearsSlider.value = 5;
                yearsDisplay.textContent = yearsSlider.value;
                render();
            });
        });

        // Rate toggle
        wrap.querySelectorAll('.rate-toggle button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.rate-toggle button').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                rateMode = btn.getAttribute('data-mode');
                render();
            });
        });

        // Slider events
        yearsSlider.addEventListener('input', function() {
            yearsDisplay.textContent = this.value;
            render();
        });
        rateSlider.addEventListener('input', function() {
            rateDisplay.textContent = this.value;
            render();
        });
        earlySlider.addEventListener('input', calcEarly);

        // Input events
        amountEl.addEventListener('input', function() { formatInput(amountEl); render(); });
        amountEl.addEventListener('blur', function() { formatInput(amountEl); });
        salaryEl.addEventListener('input', function() { formatInput(salaryEl); render(); });
        salaryEl.addEventListener('blur', function() { formatInput(salaryEl); });

        // Schedule toggle
        el('schedule').querySelector('.schedule-toggle').addEventListener('click', function() {
            el('schedule').classList.toggle('open');
        });

        // Initial render
        formatInput(amountEl);
        render();
    })();
    </script>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'saod_calculator', 'saod_calc_shortcode' );
