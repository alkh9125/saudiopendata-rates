<?php
/**
 * Plugin Name: حاسبة التمويل السعودية
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: حاسبة تمويل شخصي وعقاري شاملة — القسط الشهري، الرسوم، الضريبة، السداد المبكر، مدعوم/غير مدعوم، متقاعد/موظف
 * Version: 2.0.0
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
        --purple: #7c3aed;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-900: #111827;
        --radius: 12px;
        --shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
        --shadow-md: 0 4px 6px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.06);
        --shadow-lg: 0 10px 25px rgba(0,0,0,.1), 0 4px 10px rgba(0,0,0,.06);
        font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif;
        max-width: 840px;
        margin: 30px auto;
        color: var(--gray-900);
        line-height: 1.6;
    }
    .saod-calc * { box-sizing: border-box; }
    .saod-calc input, .saod-calc select, .saod-calc button { font-family: inherit; }

    /* Header */
    .saod-calc .calc-header { text-align: center; margin-bottom: 28px; }
    .saod-calc .calc-header h3 { font-size: 24px; font-weight: 700; margin: 0 0 6px; }
    .saod-calc .calc-header p { font-size: 14px; color: var(--gray-500); margin: 0; }

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

    /* Form */
    .saod-calc .calc-form {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 24px; box-shadow: var(--shadow); margin-bottom: 24px;
    }
    .saod-calc .calc-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
    }
    .saod-calc .calc-field { display: flex; flex-direction: column; gap: 5px; }
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
    .saod-calc .calc-field input[type="text"],
    .saod-calc .calc-field input[type="number"],
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

    /* Toggle chips (for sector, subsidy, retirement) */
    .saod-calc .chip-group {
        display: flex; gap: 6px; flex-wrap: wrap;
    }
    .saod-calc .chip-btn {
        padding: 7px 14px; border: 1px solid var(--gray-200); border-radius: 8px;
        background: #fff; color: var(--gray-600); cursor: pointer; font-size: 13px;
        transition: all .15s; white-space: nowrap;
    }
    .saod-calc .chip-btn:hover { border-color: var(--primary); color: var(--primary); }
    .saod-calc .chip-btn.active {
        background: var(--primary-light); color: var(--primary);
        border-color: var(--primary); font-weight: 600;
    }

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
        min-width: 70px; text-align: center; font-weight: 700;
        font-size: 15px; color: var(--primary);
    }

    /* Rate toggle */
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

    /* Section divider */
    .saod-calc .form-divider {
        grid-column: 1 / -1; border: none; border-top: 1px dashed var(--gray-200);
        margin: 4px 0;
    }
    .saod-calc .form-section-title {
        grid-column: 1 / -1; font-size: 13px; font-weight: 700;
        color: var(--gray-500); margin: 0; padding-top: 2px;
    }

    /* Mortgage-only fields */
    .saod-calc .mortgage-only { display: none; }
    .saod-calc.is-mortgage .mortgage-only { display: flex; }

    /* Fees section */
    .saod-calc .fees-toggle {
        grid-column: 1 / -1; font-size: 13px; color: var(--primary);
        cursor: pointer; user-select: none; padding: 4px 0;
    }
    .saod-calc .fees-toggle:hover { text-decoration: underline; }
    .saod-calc .fees-fields { display: none; grid-column: 1 / -1; }
    .saod-calc .fees-fields.visible {
        display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
    }

    /* Results */
    .saod-calc .calc-results { display: none; }
    .saod-calc .calc-results.visible { display: block; }

    .saod-calc .results-hero {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius); padding: 24px 28px; color: #fff;
        text-align: center; margin-bottom: 16px; box-shadow: var(--shadow-lg);
    }
    .saod-calc .hero-label { font-size: 14px; opacity: .85; margin-bottom: 4px; }
    .saod-calc .hero-value { font-size: 36px; font-weight: 700; }
    .saod-calc .hero-value small { font-size: 16px; font-weight: 400; opacity: .8; }
    .saod-calc .hero-sub { font-size: 13px; opacity: .75; margin-top: 6px; }

    .saod-calc .results-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px; margin-bottom: 16px;
    }
    .saod-calc .result-card {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 14px; text-align: center; box-shadow: var(--shadow);
    }
    .saod-calc .result-card .rc-label {
        font-size: 11px; color: var(--gray-400); margin-bottom: 3px;
    }
    .saod-calc .result-card .rc-value {
        font-size: 18px; font-weight: 700;
    }
    .saod-calc .result-card .rc-value small {
        font-size: 11px; font-weight: 400; color: var(--gray-400);
    }
    .saod-calc .result-card .rc-sub {
        font-size: 10px; color: var(--gray-400); margin-top: 3px;
    }

    /* Alerts */
    .saod-calc .calc-alert {
        border-radius: var(--radius); padding: 12px 16px; margin-bottom: 12px;
        font-size: 13px; display: none;
    }
    .saod-calc .calc-alert.visible { display: block; }
    .saod-calc .calc-alert.ok { background: var(--green-light); border: 1px solid #bbf7d0; color: #166534; }
    .saod-calc .calc-alert.warn { background: #fef3c7; border: 1px solid #fde68a; color: #92400e; }
    .saod-calc .calc-alert.danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .saod-calc .calc-alert.info { background: var(--primary-light); border: 1px solid #bae0f5; color: var(--primary-dark); }

    /* Fees breakdown */
    .saod-calc .fees-breakdown {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 16px 20px; box-shadow: var(--shadow); margin-bottom: 12px;
    }
    .saod-calc .fees-breakdown h4 {
        font-size: 14px; margin: 0 0 10px; color: var(--gray-700);
    }
    .saod-calc .fees-row {
        display: flex; justify-content: space-between; padding: 5px 0;
        font-size: 13px; color: var(--gray-600);
    }
    .saod-calc .fees-row.total {
        border-top: 1px solid var(--gray-200); margin-top: 6px; padding-top: 8px;
        font-weight: 700; color: var(--gray-900); font-size: 14px;
    }
    .saod-calc .fees-row .fees-val { font-weight: 600; }

    /* Chart */
    .saod-calc .chart-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 20px; box-shadow: var(--shadow); margin-bottom: 12px;
        display: flex; align-items: center; justify-content: center; gap: 28px;
        flex-wrap: wrap;
    }
    .saod-calc .chart-pie {
        width: 160px; height: 160px; border-radius: 50%; position: relative; flex-shrink: 0;
    }
    .saod-calc .chart-center {
        position: absolute; inset: 25px; background: #fff; border-radius: 50%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .saod-calc .chart-center .pct { font-size: 20px; font-weight: 700; }
    .saod-calc .chart-center .pct-label { font-size: 10px; color: var(--gray-400); }
    .saod-calc .chart-legend { display: flex; flex-direction: column; gap: 8px; }
    .saod-calc .chart-legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .saod-calc .chart-legend-dot { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }

    /* Early settlement */
    .saod-calc .early-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 20px; box-shadow: var(--shadow); margin-bottom: 12px;
    }
    .saod-calc .early-section h4 { font-size: 15px; margin: 0 0 12px; color: var(--gray-700); }
    .saod-calc .early-result {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px;
    }
    .saod-calc .early-card {
        background: var(--gray-50); border-radius: 8px; padding: 12px; text-align: center;
    }
    .saod-calc .early-card .ec-label { font-size: 11px; color: var(--gray-400); }
    .saod-calc .early-card .ec-value { font-size: 17px; font-weight: 700; margin-top: 2px; }
    .saod-calc .early-card .ec-value.savings { color: var(--green); }

    /* Schedule */
    .saod-calc .schedule-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        box-shadow: var(--shadow); margin-bottom: 12px; overflow: hidden;
    }
    .saod-calc .schedule-toggle {
        padding: 14px 20px; cursor: pointer; font-size: 14px; font-weight: 600;
        color: var(--gray-700); display: flex; justify-content: space-between;
        align-items: center; user-select: none;
    }
    .saod-calc .schedule-toggle:hover { background: var(--gray-50); }
    .saod-calc .schedule-toggle .arrow { transition: transform .2s; }
    .saod-calc .schedule-section.open .arrow { transform: rotate(180deg); }
    .saod-calc .schedule-table-wrap { display: none; max-height: 400px; overflow-y: auto; }
    .saod-calc .schedule-section.open .schedule-table-wrap { display: block; }
    .saod-calc .schedule-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .saod-calc .schedule-table thead { position: sticky; top: 0; z-index: 1; }
    .saod-calc .schedule-table th {
        background: var(--gray-100); padding: 8px 10px; text-align: center;
        font-size: 11px; color: var(--gray-500); font-weight: 600;
        border-bottom: 1px solid var(--gray-200);
    }
    .saod-calc .schedule-table td {
        padding: 7px 10px; text-align: center; border-bottom: 1px solid var(--gray-100);
        font-size: 12px;
    }
    .saod-calc .schedule-table tr:hover td { background: var(--primary-light); }
    .saod-calc .schedule-table .yr-row td {
        background: var(--gray-50); font-weight: 700; font-size: 12px; color: var(--primary);
    }

    /* Max eligibility */
    .saod-calc .max-section {
        background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius);
        padding: 16px 20px; box-shadow: var(--shadow); margin-bottom: 12px;
    }
    .saod-calc .max-section h4 { font-size: 14px; margin: 0 0 10px; color: var(--gray-700); }
    .saod-calc .max-row {
        display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px;
        border-bottom: 1px solid var(--gray-100);
    }
    .saod-calc .max-row:last-child { border-bottom: none; }
    .saod-calc .max-row .max-val { font-weight: 700; color: var(--primary); }

    /* Disclaimer */
    .saod-calc .calc-disclaimer {
        font-size: 12px; color: var(--gray-400); text-align: center;
        margin-top: 16px; line-height: 1.8;
    }

    @media (max-width: 600px) {
        .saod-calc .calc-grid { grid-template-columns: 1fr; }
        .saod-calc .fees-fields.visible { grid-template-columns: 1fr; }
        .saod-calc .results-grid { grid-template-columns: 1fr 1fr; }
        .saod-calc .hero-value { font-size: 28px; }
        .saod-calc .chart-section { flex-direction: column; }
        .saod-calc .early-result { grid-template-columns: 1fr; }
    }
    </style>

    <div class="calc-header">
        <h3>حاسبة التمويل</h3>
        <p>احسب القسط الشهري الفعلي شاملاً الرسوم الإدارية والضريبة</p>
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

            <!-- Row 1: Amount + Salary -->
            <div class="calc-field">
                <label>مبلغ التمويل</label>
                <div class="input-wrap">
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-amount" value="300,000" inputmode="numeric" class="has-suffix">
                    <span class="input-suffix">ر.س</span>
                </div>
            </div>
            <div class="calc-field">
                <label>الراتب الشهري</label>
                <div class="input-wrap">
                    <input type="text" id="<?php echo esc_attr( $uid ); ?>-salary" placeholder="مثال: 12,000" inputmode="numeric" class="has-suffix">
                    <span class="input-suffix">ر.س</span>
                </div>
            </div>

            <!-- Row 2: Sector + Status -->
            <div class="calc-field">
                <label>جهة العمل</label>
                <div class="chip-group" id="<?php echo esc_attr( $uid ); ?>-sector">
                    <button type="button" class="chip-btn active" data-val="gov">حكومي</button>
                    <button type="button" class="chip-btn" data-val="private">خاص</button>
                    <button type="button" class="chip-btn" data-val="military">عسكري</button>
                </div>
            </div>
            <div class="calc-field">
                <label>الحالة الوظيفية</label>
                <div class="chip-group" id="<?php echo esc_attr( $uid ); ?>-employment">
                    <button type="button" class="chip-btn active" data-val="active">على رأس العمل</button>
                    <button type="button" class="chip-btn" data-val="retired">متقاعد</button>
                </div>
            </div>

            <!-- Mortgage-only: subsidy -->
            <div class="calc-field full mortgage-only">
                <label>نوع التمويل العقاري</label>
                <div class="chip-group" id="<?php echo esc_attr( $uid ); ?>-subsidy">
                    <button type="button" class="chip-btn active" data-val="unsubsidized">غير مدعوم</button>
                    <button type="button" class="chip-btn" data-val="subsidized">مدعوم (صندوق التنمية العقارية)</button>
                </div>
            </div>

            <!-- Divider -->
            <hr class="form-divider">

            <!-- Term slider -->
            <div class="calc-field full">
                <label>مدة التمويل</label>
                <div class="calc-slider-row">
                    <input type="range" id="<?php echo esc_attr( $uid ); ?>-years-slider" min="1" max="5" value="5" step="1">
                    <div class="calc-slider-val"><span id="<?php echo esc_attr( $uid ); ?>-years-display">5</span> سنوات</div>
                </div>
            </div>

            <!-- Rate -->
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

            <!-- Fees toggle -->
            <div class="fees-toggle" id="<?php echo esc_attr( $uid ); ?>-fees-toggle">
                ◂ إعدادات الرسوم والتأمين
            </div>
            <div class="fees-fields" id="<?php echo esc_attr( $uid ); ?>-fees-fields">
                <div class="calc-field">
                    <label>الرسوم الإدارية <small>(%)</small></label>
                    <div class="input-wrap">
                        <input type="number" id="<?php echo esc_attr( $uid ); ?>-admin-fee-pct" value="1" min="0" max="5" step="0.1">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div class="calc-field">
                    <label>سقف الرسوم الإدارية <small>(ريال)</small></label>
                    <div class="input-wrap">
                        <input type="text" id="<?php echo esc_attr( $uid ); ?>-admin-fee-cap" value="2,500" inputmode="numeric" class="has-suffix">
                        <span class="input-suffix">ر.س</span>
                    </div>
                </div>
                <div class="calc-field">
                    <label>ضريبة القيمة المضافة <small>(%)</small></label>
                    <div class="input-wrap">
                        <input type="number" id="<?php echo esc_attr( $uid ); ?>-vat" value="15" min="0" max="25" step="1" disabled style="background:var(--gray-50);">
                        <span class="input-suffix">%</span>
                    </div>
                </div>
                <div class="calc-field">
                    <label>تأمين (اختياري) <small>(مبلغ مقطوع)</small></label>
                    <div class="input-wrap">
                        <input type="text" id="<?php echo esc_attr( $uid ); ?>-insurance" value="0" inputmode="numeric" class="has-suffix">
                        <span class="input-suffix">ر.س</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Results -->
    <div class="calc-results" id="<?php echo esc_attr( $uid ); ?>-results">

        <!-- Hero -->
        <div class="results-hero">
            <div class="hero-label">القسط الشهري</div>
            <div class="hero-value"><span id="<?php echo esc_attr( $uid ); ?>-emi">0</span> <small>ر.س / شهر</small></div>
            <div class="hero-sub" id="<?php echo esc_attr( $uid ); ?>-hero-sub"></div>
        </div>

        <!-- Alerts -->
        <div class="calc-alert" id="<?php echo esc_attr( $uid ); ?>-alert-dbr"></div>
        <div class="calc-alert" id="<?php echo esc_attr( $uid ); ?>-alert-retired"></div>
        <div class="calc-alert info" id="<?php echo esc_attr( $uid ); ?>-alert-subsidy" style="display:none;"></div>

        <!-- Key metrics -->
        <div class="results-grid">
            <div class="result-card">
                <div class="rc-label">إجمالي السداد</div>
                <div class="rc-value"><span id="<?php echo esc_attr( $uid ); ?>-total">0</span> <small>ر.س</small></div>
            </div>
            <div class="result-card">
                <div class="rc-label">أرباح البنك</div>
                <div class="rc-value" style="color:var(--orange);"><span id="<?php echo esc_attr( $uid ); ?>-profit">0</span> <small>ر.س</small></div>
                <div class="rc-sub" id="<?php echo esc_attr( $uid ); ?>-profit-pct"></div>
            </div>
            <div class="result-card">
                <div class="rc-label">الرسوم + ضريبة</div>
                <div class="rc-value" style="color:var(--purple);"><span id="<?php echo esc_attr( $uid ); ?>-fees-total">0</span> <small>ر.س</small></div>
            </div>
            <div class="result-card">
                <div class="rc-label">التكلفة الحقيقية الكاملة</div>
                <div class="rc-value" style="color:var(--red);"><span id="<?php echo esc_attr( $uid ); ?>-true-cost">0</span> <small>ر.س</small></div>
                <div class="rc-sub" id="<?php echo esc_attr( $uid ); ?>-true-apr"></div>
            </div>
            <div class="result-card">
                <div class="rc-label">عدد الأقساط</div>
                <div class="rc-value"><span id="<?php echo esc_attr( $uid ); ?>-months-val">0</span> <small>شهر</small></div>
            </div>
        </div>

        <!-- Fees breakdown -->
        <div class="fees-breakdown" id="<?php echo esc_attr( $uid ); ?>-fees-detail"></div>

        <!-- Max eligibility -->
        <div class="max-section" id="<?php echo esc_attr( $uid ); ?>-max-section" style="display:none;">
            <h4>أقصى تمويل ممكن بناءً على راتبك</h4>
            <div id="<?php echo esc_attr( $uid ); ?>-max-body"></div>
        </div>

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
                <div class="chart-legend-item">
                    <div class="chart-legend-dot" style="background:var(--purple);"></div>
                    <span>رسوم + ضريبة: <strong id="<?php echo esc_attr( $uid ); ?>-leg-fees">0</strong> ر.س</span>
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
                            <th>الرصيد</th>
                        </tr>
                    </thead>
                    <tbody id="<?php echo esc_attr( $uid ); ?>-schedule-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="calc-disclaimer">
        الأرقام تقريبية وتشمل الرسوم الإدارية وضريبة القيمة المضافة (15%) حسب الإعدادات أعلاه.
        <br>لا تمثل عرضاً رسمياً — النسبة الفعلية تعتمد على تقييم البنك لملفك الائتماني ونسبة الاستقطاع المتاحة.
        <br>الحد الأقصى لنسبة الاستقطاع حسب أنظمة ساما: <strong>33%</strong> من الراتب للتمويل الشخصي، <strong>65%</strong> لإجمالي الالتزامات.
    </div>

    <script>
    (function(){
        var uid = <?php echo wp_json_encode( $uid ); ?>;
        var el = function(id) { return document.getElementById(uid + '-' + id); };
        var wrap = document.getElementById(uid);
        if (!wrap) return;

        var state = {
            type: 'personal',
            rateMode: 'apr',
            sector: 'gov',
            employment: 'active',
            subsidy: 'unsubsidized',
            feesOpen: false
        };

        // SAMA DBR limits
        var DBR_PERSONAL = 0.33;
        var DBR_TOTAL = 0.65;

        // Retired: max term limits (years)
        var RETIRED_MAX_PERSONAL = 5;
        var RETIRED_MAX_MORTGAGE = 20;
        // Retired: typical age limit for loan maturity = 70
        // We can't know age, so we just flag it

        // Subsidized mortgage: government covers profit up to a salary threshold
        var SUBSIDY_SALARY_CAP = 14000;
        var SUBSIDY_RATE_REDUCTION = 100; // 100% of profit covered for eligible

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
            for (var i = 0; i < 100; i++) {
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

        // True APR: effective rate including fees amortized over loan term (bisection)
        function calcTrueApr(netProceeds, emi, months) {
            var lo = 0, hi = 1;
            for (var i = 0; i < 100; i++) {
                var mid = (lo + hi) / 2;
                var pv = mid < 1e-9 ? emi * months : emi * (1 - Math.pow(1 + mid, -months)) / mid;
                if (pv > netProceeds) lo = mid; else hi = mid;
            }
            return Math.round((lo + hi) / 2 * 12 * 10000) / 100;
        }

        function getApr() {
            var val = parseFloat(rateSlider.value) || 0;
            var years = parseInt(yearsSlider.value) || 5;
            if (state.rateMode === 'flat') return flatToApr(val, years);
            return val;
        }

        function updateConversion() {
            var val = parseFloat(rateSlider.value) || 0;
            var years = parseInt(yearsSlider.value) || 5;
            if (state.rateMode === 'apr') {
                rateConverted.textContent = 'يعادل هامش ربح ثابت (Flat) ≈ ' + aprToFlat(val, years) + '%';
            } else {
                rateConverted.textContent = 'يعادل معدل نسبة سنوي (APR) ≈ ' + flatToApr(val, years) + '%';
            }
        }

        function getMaxYears() {
            if (state.type === 'mortgage') {
                return state.employment === 'retired' ? RETIRED_MAX_MORTGAGE : 30;
            }
            return state.employment === 'retired' ? RETIRED_MAX_PERSONAL : 5;
        }

        function render() {
            var amount = parseNum(amountEl.value);
            var salary = parseNum(salaryEl.value);
            var years = parseInt(yearsSlider.value) || 5;
            var months = years * 12;
            var apr = getApr();
            var isMortgage = state.type === 'mortgage';
            var isSubsidized = isMortgage && state.subsidy === 'subsidized';
            var isRetired = state.employment === 'retired';

            // Subsidized mortgage: if salary <= cap, profit is covered by government
            var effectiveApr = apr;
            var subsidyAmount = 0;
            if (isSubsidized && salary > 0 && salary <= SUBSIDY_SALARY_CAP) {
                effectiveApr = 0.01; // near zero — government covers profit
            } else if (isSubsidized && salary > SUBSIDY_SALARY_CAP) {
                // Partial subsidy: simplified — government covers a portion
                var subsidyPct = Math.max(0, 1 - (salary - SUBSIDY_SALARY_CAP) / SUBSIDY_SALARY_CAP);
                effectiveApr = apr * (1 - subsidyPct);
            }

            if (amount <= 0 || apr <= 0) {
                resultsEl.classList.remove('visible');
                return;
            }
            resultsEl.classList.add('visible');

            // Fees
            var adminFeePct = parseFloat(el('admin-fee-pct').value) || 0;
            var adminFeeCap = parseNum(el('admin-fee-cap').value);
            var vatPct = parseFloat(el('vat').value) || 15;
            var insurance = parseNum(el('insurance').value);

            var adminFee = Math.min(amount * adminFeePct / 100, adminFeeCap > 0 ? adminFeeCap : Infinity);
            var vatOnFee = adminFee * vatPct / 100;
            var totalFees = adminFee + vatOnFee + insurance;

            // EMI calculation
            var emi = calcEMI(amount, effectiveApr, months);
            var total = emi * months;
            var profit = total - amount;

            // Subsidy calculation
            if (isSubsidized && salary > 0 && salary <= SUBSIDY_SALARY_CAP) {
                var fullEmi = calcEMI(amount, apr, months);
                subsidyAmount = (fullEmi - emi) * months;
            }

            var totalWithFees = total + totalFees;
            var trueCost = profit + totalFees;
            var profitPct = (profit / amount * 100).toFixed(1);

            // True APR (including fees)
            var netProceeds = amount - totalFees;
            var trueApr = netProceeds > 0 ? calcTrueApr(netProceeds, emi, months) : effectiveApr;

            // Hero
            el('emi').textContent = fmt(emi);
            var heroSub = '';
            if (totalFees > 0) {
                heroSub = 'رسوم لمرة واحدة: ' + fmt(totalFees) + ' ر.س';
            }
            el('hero-sub').textContent = heroSub;

            // Metrics
            el('total').textContent = fmt(totalWithFees);
            el('profit').textContent = fmt(profit);
            el('profit-pct').textContent = profitPct + '% من أصل المبلغ';
            el('fees-total').textContent = fmt(totalFees);
            el('true-cost').textContent = fmt(trueCost);
            el('true-apr').textContent = 'APR الفعلي شامل الرسوم: ' + trueApr + '%';
            el('months-val').textContent = months;

            // Fees breakdown
            var fb = el('fees-detail');
            var fbHtml = '<h4>تفصيل الرسوم</h4>';
            fbHtml += '<div class="fees-row"><span>الرسوم الإدارية (' + adminFeePct + '%)</span><span class="fees-val">' + fmt(adminFee) + ' ر.س</span></div>';
            if (adminFeeCap > 0 && (amount * adminFeePct / 100) > adminFeeCap) {
                fbHtml += '<div class="fees-row"><span style="color:var(--green);font-size:12px;">تم تطبيق سقف الرسوم: ' + fmt(adminFeeCap) + ' ر.س</span><span></span></div>';
            }
            fbHtml += '<div class="fees-row"><span>ضريبة القيمة المضافة (' + vatPct + '% على الرسوم)</span><span class="fees-val">' + fmt(vatOnFee) + ' ر.س</span></div>';
            if (insurance > 0) {
                fbHtml += '<div class="fees-row"><span>التأمين</span><span class="fees-val">' + fmt(insurance) + ' ر.س</span></div>';
            }
            fbHtml += '<div class="fees-row total"><span>إجمالي الرسوم</span><span class="fees-val">' + fmt(totalFees) + ' ر.س</span></div>';
            fb.innerHTML = fbHtml;

            // DBR / Salary alerts
            var alertDbr = el('alert-dbr');
            var alertRetired = el('alert-retired');
            var alertSubsidy = el('alert-subsidy');

            if (salary > 0) {
                var dbrRatio = emi / salary;
                var dbrPct = (dbrRatio * 100).toFixed(1);
                alertDbr.classList.add('visible');

                if (dbrRatio <= DBR_PERSONAL) {
                    alertDbr.className = 'calc-alert visible ok';
                    alertDbr.innerHTML = '✓ نسبة الاستقطاع <strong>' + dbrPct + '%</strong> من راتبك — ضمن حد ساما (' + (DBR_PERSONAL*100) + '%)';
                } else if (dbrRatio <= DBR_TOTAL) {
                    alertDbr.className = 'calc-alert visible warn';
                    alertDbr.innerHTML = '⚠ نسبة الاستقطاع <strong>' + dbrPct + '%</strong> من راتبك — تتجاوز الحد للتمويل الشخصي (' + (DBR_PERSONAL*100) + '%) لكن ضمن الحد الكلي (' + (DBR_TOTAL*100) + '%). قد يُقبل كتمويل عقاري أو بضامن.';
                } else {
                    alertDbr.className = 'calc-alert visible danger';
                    alertDbr.innerHTML = '✕ نسبة الاستقطاع <strong>' + dbrPct + '%</strong> — تتجاوز الحد الأقصى لساما (' + (DBR_TOTAL*100) + '%). يُحتمل رفض الطلب.';
                }

                // Max eligibility
                var maxSection = el('max-section');
                maxSection.style.display = '';
                var maxEmi33 = salary * DBR_PERSONAL;
                var maxEmi65 = salary * DBR_TOTAL;
                var maxAmount33 = calcMaxLoan(maxEmi33, effectiveApr, months);
                var maxAmount65 = calcMaxLoan(maxEmi65, effectiveApr, months);
                var maxBody = el('max-body');
                maxBody.innerHTML =
                    '<div class="max-row"><span>بنسبة استقطاع 33% (قسط ' + fmt(maxEmi33) + ' ر.س)</span><span class="max-val">' + fmt(maxAmount33) + ' ر.س</span></div>' +
                    '<div class="max-row"><span>بنسبة استقطاع 65% (قسط ' + fmt(maxEmi65) + ' ر.س)</span><span class="max-val">' + fmt(maxAmount65) + ' ر.س</span></div>';
            } else {
                alertDbr.classList.remove('visible');
                el('max-section').style.display = 'none';
            }

            // Retired alert
            if (isRetired) {
                alertRetired.className = 'calc-alert visible warn';
                var maxYrs = getMaxYears();
                alertRetired.innerHTML = '⚠ للمتقاعدين: أقصى مدة تمويل عادةً <strong>' + maxYrs + ' سنوات</strong>، ويجب ألا يتجاوز عمرك عند نهاية التمويل 70 سنة. النسب عادةً أعلى بـ 0.5-1% مقارنة بالموظفين.';
            } else {
                alertRetired.classList.remove('visible');
            }

            // Subsidy alert
            if (isSubsidized) {
                alertSubsidy.style.display = 'block';
                if (salary > 0 && salary <= SUBSIDY_SALARY_CAP) {
                    alertSubsidy.innerHTML = '✓ مؤهل للدعم الكامل: صندوق التنمية العقارية يتحمل كامل الأرباح (توفير ' + fmt(subsidyAmount) + ' ر.س). الشرط: المسكن الأول ولم يسبق لك الاستفادة.';
                } else if (salary > 0 && salary <= SUBSIDY_SALARY_CAP * 2) {
                    alertSubsidy.innerHTML = '⚠ دعم جزئي: راتبك يتجاوز ' + fmt(SUBSIDY_SALARY_CAP) + ' ر.س — الصندوق يدعم جزءاً من الأرباح. النسبة الفعلية بعد الدعم: <strong>' + effectiveApr.toFixed(2) + '%</strong>';
                } else if (salary > 0) {
                    alertSubsidy.innerHTML = '✕ راتبك يتجاوز سقف الدعم (' + fmt(SUBSIDY_SALARY_CAP * 2) + ' ر.س). التمويل يُعامل كغير مدعوم.';
                } else {
                    alertSubsidy.innerHTML = 'أدخل راتبك لمعرفة أهليتك للدعم السكني. الدعم الكامل لأصحاب الرواتب حتى ' + fmt(SUBSIDY_SALARY_CAP) + ' ر.س.';
                }
            } else {
                alertSubsidy.style.display = 'none';
            }

            // Pie chart — 3 segments
            var totalAll = amount + profit + totalFees;
            var pDeg = (amount / totalAll) * 360;
            var iDeg = (profit / totalAll) * 360;
            el('pie').style.background = 'conic-gradient(var(--primary) 0deg ' + pDeg + 'deg, var(--orange) ' + pDeg + 'deg ' + (pDeg+iDeg) + 'deg, var(--purple) ' + (pDeg+iDeg) + 'deg 360deg)';
            var costPct = ((profit + totalFees) / totalAll * 100).toFixed(1);
            el('pie').innerHTML = '<div class="chart-center"><span class="pct">' + costPct + '%</span><span class="pct-label">إجمالي التكلفة</span></div>';
            el('leg-principal').textContent = fmt(amount);
            el('leg-interest').textContent = fmt(profit);
            el('leg-fees').textContent = fmt(totalFees);

            // Early settlement
            earlySlider.max = months - 1;
            if (parseInt(earlySlider.value) >= months) earlySlider.value = Math.floor(months / 2);
            calcEarly();

            // Schedule
            buildSchedule(amount, effectiveApr, months, emi);

            updateConversion();
        }

        function calcMaxLoan(maxEmi, aprPct, months) {
            var r = aprPct / 100 / 12;
            if (r < 1e-9) return maxEmi * months;
            return maxEmi * (1 - Math.pow(1 + r, -months)) / r;
        }

        function calcEarly() {
            var amount = parseNum(amountEl.value);
            var years = parseInt(yearsSlider.value) || 5;
            var months = years * 12;
            var apr = getApr();
            var effectiveApr = apr;

            if (state.type === 'mortgage' && state.subsidy === 'subsidized') {
                var salary = parseNum(salaryEl.value);
                if (salary > 0 && salary <= SUBSIDY_SALARY_CAP) {
                    effectiveApr = 0.01;
                } else if (salary > SUBSIDY_SALARY_CAP) {
                    var subsidyPct = Math.max(0, 1 - (salary - SUBSIDY_SALARY_CAP) / SUBSIDY_SALARY_CAP);
                    effectiveApr = apr * (1 - subsidyPct);
                }
            }

            var r = effectiveApr / 100 / 12;
            var emi = calcEMI(amount, effectiveApr, months);
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

        function buildSchedule(amount, aprPct, months, emi) {
            var r = aprPct / 100 / 12;
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

                html += '<tr><td>' + m + '</td><td>' + fmt(emi) + '</td><td>' + fmt(principal) + '</td><td>' + fmt(interest) + '</td><td>' + fmt(balance) + '</td></tr>';

                if (m % 12 === 0 || m === months) {
                    var yr = Math.ceil(m / 12);
                    html += '<tr class="yr-row"><td colspan="5">السنة ' + yr + ' — الأصل: ' + fmt(yearPrincipal) + ' ر.س | الأرباح: ' + fmt(yearInterest) + ' ر.س</td></tr>';
                    yearPrincipal = 0;
                    yearInterest = 0;
                }
            }
            el('schedule-body').innerHTML = html;
        }

        // --- Event handlers ---

        // Type tabs
        wrap.querySelectorAll('.calc-type-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.calc-type-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                state.type = btn.getAttribute('data-type');
                var isMortgage = state.type === 'mortgage';
                wrap.classList.toggle('is-mortgage', isMortgage);

                var maxY = getMaxYears();
                yearsSlider.max = maxY;
                if (isMortgage && parseInt(yearsSlider.value) <= 5) yearsSlider.value = 20;
                if (!isMortgage && parseInt(yearsSlider.value) > 5) yearsSlider.value = 5;
                if (parseInt(yearsSlider.value) > maxY) yearsSlider.value = maxY;
                yearsDisplay.textContent = yearsSlider.value;
                render();
            });
        });

        // Chip groups
        function setupChips(groupId, stateKey) {
            var group = el(groupId);
            if (!group) return;
            group.querySelectorAll('.chip-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    group.querySelectorAll('.chip-btn').forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    state[stateKey] = btn.getAttribute('data-val');

                    // Adjust max years on employment change
                    if (stateKey === 'employment') {
                        var maxY = getMaxYears();
                        yearsSlider.max = maxY;
                        if (parseInt(yearsSlider.value) > maxY) yearsSlider.value = maxY;
                        yearsDisplay.textContent = yearsSlider.value;
                    }
                    render();
                });
            });
        }
        setupChips('sector', 'sector');
        setupChips('employment', 'employment');
        setupChips('subsidy', 'subsidy');

        // Rate toggle
        wrap.querySelectorAll('.rate-toggle button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wrap.querySelectorAll('.rate-toggle button').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                state.rateMode = btn.getAttribute('data-mode');
                render();
            });
        });

        // Sliders
        yearsSlider.addEventListener('input', function() { yearsDisplay.textContent = this.value; render(); });
        rateSlider.addEventListener('input', function() { rateDisplay.textContent = this.value; render(); });
        earlySlider.addEventListener('input', calcEarly);

        // Text inputs
        [amountEl, salaryEl].forEach(function(inp) {
            inp.addEventListener('input', function() { formatInput(inp); render(); });
            inp.addEventListener('blur', function() { formatInput(inp); });
        });

        // Fee inputs
        ['admin-fee-pct', 'admin-fee-cap', 'insurance'].forEach(function(id) {
            var inp = el(id);
            if (!inp) return;
            if (id === 'admin-fee-cap' || id === 'insurance') {
                inp.addEventListener('input', function() { formatInput(inp); render(); });
                inp.addEventListener('blur', function() { formatInput(inp); });
            } else {
                inp.addEventListener('input', render);
            }
        });

        // Fees toggle
        el('fees-toggle').addEventListener('click', function() {
            state.feesOpen = !state.feesOpen;
            el('fees-fields').classList.toggle('visible', state.feesOpen);
            el('fees-toggle').textContent = (state.feesOpen ? '▸ ' : '◂ ') + 'إعدادات الرسوم والتأمين';
        });

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
