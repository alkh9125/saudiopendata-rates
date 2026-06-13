<?php
/**
 * Plugin Name: حاسبة التمويل السعودية
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: حاسبة تمويل شاملة — شخصي، عقاري، شراء مديونية، سداد مبكر — متوافقة مع أنظمة ساما وصندوق التنمية العقاري
 * Version: 4.0.0
 * Author: SaudiOpenData
 * Text Domain: saod-calc
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function saod_calc_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'product' => '' ), $atts );
    $uid  = 'sc-' . wp_unique_id();
    $initial_tab = in_array( $atts['product'], array( 'personal', 'mortgage', 'buyout', 'early' ), true )
        ? $atts['product'] : 'personal';

    ob_start();
    ?>
    <div class="sc" id="<?php echo esc_attr( $uid ); ?>" dir="rtl">
    <style>
    /* ── Design tokens ──────────────────────────────────────── */
    .sc{--c:#0073aa;--cd:#005a87;--cl:#e8f4fd;--g:#16a34a;--gl:#f0fdf4;--o:#ea580c;--r:#dc2626;--p:#7c3aed;--g50:#f9fafb;--g100:#f3f4f6;--g200:#e5e7eb;--g300:#d1d5db;--g400:#9ca3af;--g500:#6b7280;--g600:#4b5563;--g700:#374151;--g900:#111827;--rd:12px;--sh:0 1px 3px rgba(0,0,0,.08);--shm:0 4px 6px rgba(0,0,0,.07);--shl:0 10px 25px rgba(0,0,0,.1);font-family:'Tajawal','Segoe UI',Tahoma,sans-serif;max-width:860px;margin:30px auto;color:var(--g900);line-height:1.6}
    .sc *{box-sizing:border-box}.sc input,.sc select,.sc button{font-family:inherit}

    /* Header */
    .sc .sc-hd{text-align:center;margin-bottom:24px}
    .sc .sc-hd h3{font-size:22px;font-weight:700;margin:0 0 4px}
    .sc .sc-hd p{font-size:13px;color:var(--g500);margin:0}

    /* Tabs */
    .sc .sc-tabs{display:flex;justify-content:center;gap:6px;margin-bottom:22px;flex-wrap:wrap}
    .sc .sc-tab{padding:8px 18px;border:1px solid var(--g200);border-radius:20px;background:#fff;color:var(--g700);cursor:pointer;font-size:13px;transition:all .15s;white-space:nowrap}
    .sc .sc-tab:hover{border-color:var(--c);color:var(--c)}
    .sc .sc-tab.on{background:var(--c);color:#fff;border-color:var(--c)}

    /* Form */
    .sc .sc-form{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:22px;box-shadow:var(--sh);margin-bottom:22px}
    .sc .sc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .sc .sc-f{display:flex;flex-direction:column;gap:4px}
    .sc .sc-f.full{grid-column:1/-1}
    .sc .sc-f label{font-size:12px;font-weight:600;color:var(--g700)}
    .sc .sc-f label small{font-weight:400;color:var(--g400)}
    .sc .sc-f .iw{position:relative;display:flex;align-items:center}
    .sc .sc-f input[type="text"],.sc .sc-f input[type="number"],.sc .sc-f select{width:100%;padding:9px 12px;border:1px solid var(--g200);border-radius:8px;font-size:14px;background:#fff;transition:border-color .15s}
    .sc .sc-f input:focus,.sc .sc-f select:focus{outline:none;border-color:var(--c);box-shadow:0 0 0 3px rgba(0,115,170,.12)}
    .sc .sc-f .sfx{position:absolute;left:10px;color:var(--g400);font-size:12px;pointer-events:none}
    .sc .sc-f input.hs{padding-left:40px}

    /* Chips */
    .sc .cg{display:flex;gap:5px;flex-wrap:wrap}
    .sc .cb{padding:6px 12px;border:1px solid var(--g200);border-radius:8px;background:#fff;color:var(--g600);cursor:pointer;font-size:12px;transition:all .15s;white-space:nowrap}
    .sc .cb:hover{border-color:var(--c);color:var(--c)}
    .sc .cb.on{background:var(--cl);color:var(--c);border-color:var(--c);font-weight:600}

    /* Slider */
    .sc .sr{display:flex;align-items:center;gap:10px}
    .sc .sr input[type="range"]{flex:1;accent-color:var(--c);height:6px;cursor:pointer;padding:0;border:none}
    .sc .sr input[type="range"]:focus{box-shadow:none}
    .sc .sv{min-width:65px;text-align:center;font-weight:700;font-size:14px;color:var(--c)}

    /* Rate toggle */
    .sc .rt{display:flex;border:1px solid var(--g200);border-radius:8px;overflow:hidden;margin-bottom:6px}
    .sc .rt button{flex:1;padding:5px 10px;border:none;background:#fff;font-size:11px;cursor:pointer;color:var(--g500);transition:all .15s}
    .sc .rt button.on{background:var(--c);color:#fff}

    /* Divider */
    .sc .fd{grid-column:1/-1;border:none;border-top:1px dashed var(--g200);margin:2px 0}

    /* Section label */
    .sc .fl{grid-column:1/-1;font-size:12px;font-weight:700;color:var(--g400);margin:0;padding-top:2px;text-transform:uppercase;letter-spacing:.3px}

    /* Collapsible */
    .sc .col-tog{grid-column:1/-1;font-size:12px;color:var(--c);cursor:pointer;user-select:none;padding:6px 0;font-weight:600}
    .sc .col-tog:hover{text-decoration:underline}
    .sc .col-body{display:none;grid-column:1/-1}
    .sc .col-body.vis{display:grid;grid-template-columns:1fr 1fr;gap:16px}

    /* Tab panes */
    .sc .sc-pane{display:none}.sc .sc-pane.vis{display:block}

    /* Results */
    .sc .sc-res{display:none}.sc .sc-res.vis{display:block}

    /* Hero */
    .sc .hero{background:linear-gradient(135deg,var(--c),var(--cd));border-radius:var(--rd);padding:22px 24px;color:#fff;text-align:center;margin-bottom:14px;box-shadow:var(--shl)}
    .sc .hero .hl{font-size:13px;opacity:.85;margin-bottom:3px}
    .sc .hero .hv{font-size:34px;font-weight:700}
    .sc .hero .hv small{font-size:15px;font-weight:400;opacity:.8}
    .sc .hero .hs{font-size:12px;opacity:.7;margin-top:4px}

    /* Alert */
    .sc .al{border-radius:var(--rd);padding:10px 14px;margin-bottom:10px;font-size:12px;display:none;line-height:1.7}
    .sc .al.vis{display:block}
    .sc .al.ok{background:var(--gl);border:1px solid #bbf7d0;color:#166534}
    .sc .al.wn{background:#fef3c7;border:1px solid #fde68a;color:#92400e}
    .sc .al.dg{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
    .sc .al.in{background:var(--cl);border:1px solid #bae0f5;color:var(--cd)}

    /* Metric grid */
    .sc .mg{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:8px;margin-bottom:14px}
    .sc .mc{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:12px;text-align:center;box-shadow:var(--sh)}
    .sc .mc .ml{font-size:10px;color:var(--g400);margin-bottom:2px}
    .sc .mc .mv{font-size:17px;font-weight:700}
    .sc .mc .mv small{font-size:10px;font-weight:400;color:var(--g400)}
    .sc .mc .ms{font-size:9px;color:var(--g400);margin-top:2px}

    /* Fees breakdown */
    .sc .fb{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:14px 18px;box-shadow:var(--sh);margin-bottom:12px}
    .sc .fb h4{font-size:13px;margin:0 0 8px;color:var(--g700)}
    .sc .fr{display:flex;justify-content:space-between;padding:4px 0;font-size:12px;color:var(--g600)}
    .sc .fr.tot{border-top:1px solid var(--g200);margin-top:4px;padding-top:6px;font-weight:700;color:var(--g900);font-size:13px}

    /* Chart */
    .sc .cs{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:18px;box-shadow:var(--sh);margin-bottom:12px;display:flex;align-items:center;justify-content:center;gap:24px;flex-wrap:wrap}
    .sc .cp{width:150px;height:150px;border-radius:50%;position:relative;flex-shrink:0}
    .sc .cc{position:absolute;inset:22px;background:#fff;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center}
    .sc .cc .pv{font-size:18px;font-weight:700}.sc .cc .pl{font-size:9px;color:var(--g400)}
    .sc .cl{display:flex;flex-direction:column;gap:7px}
    .sc .ci{display:flex;align-items:center;gap:6px;font-size:12px}
    .sc .cd{width:10px;height:10px;border-radius:3px;flex-shrink:0}

    /* Early settlement */
    .sc .es{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:18px;box-shadow:var(--sh);margin-bottom:12px}
    .sc .es h4{font-size:14px;margin:0 0 10px;color:var(--g700)}
    .sc .er{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
    .sc .ec{background:var(--g50);border-radius:8px;padding:10px;text-align:center}
    .sc .ec .el{font-size:10px;color:var(--g400)}.sc .ec .ev{font-size:16px;font-weight:700;margin-top:2px}
    .sc .ec .ev.sv{color:var(--g)}

    /* Buyout comparison */
    .sc .bc{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:18px;box-shadow:var(--sh);margin-bottom:12px}
    .sc .bc h4{font-size:14px;margin:0 0 10px;color:var(--g700)}
    .sc .bt{width:100%;border-collapse:collapse;font-size:12px}
    .sc .bt th{background:var(--g100);padding:8px;text-align:center;font-size:11px;color:var(--g500)}
    .sc .bt td{padding:8px;text-align:center;border-bottom:1px solid var(--g100)}
    .sc .bt .hi{color:var(--g);font-weight:700}

    /* Schedule */
    .sc .ss{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);box-shadow:var(--sh);margin-bottom:12px;overflow:hidden}
    .sc .st{padding:12px 18px;cursor:pointer;font-size:13px;font-weight:600;color:var(--g700);display:flex;justify-content:space-between;align-items:center;user-select:none}
    .sc .st:hover{background:var(--g50)}
    .sc .st .ar{transition:transform .2s}
    .sc .ss.op .ar{transform:rotate(180deg)}
    .sc .sw{display:none;max-height:400px;overflow-y:auto}
    .sc .ss.op .sw{display:block}
    .sc .stb{width:100%;border-collapse:collapse;font-size:11px}
    .sc .stb thead{position:sticky;top:0;z-index:1}
    .sc .stb th{background:var(--g100);padding:6px 8px;text-align:center;font-size:10px;color:var(--g500);border-bottom:1px solid var(--g200)}
    .sc .stb td{padding:6px 8px;text-align:center;border-bottom:1px solid var(--g100)}
    .sc .stb tr:hover td{background:var(--cl)}
    .sc .stb .yr td{background:var(--g50);font-weight:700;font-size:11px;color:var(--c)}

    /* Export bar */
    .sc .xb{display:flex;gap:8px;justify-content:center;margin-bottom:12px}
    .sc .xb button{padding:7px 16px;border:1px solid var(--g200);border-radius:8px;background:#fff;color:var(--g700);cursor:pointer;font-size:12px;transition:all .15s}
    .sc .xb button:hover{border-color:var(--c);color:var(--c)}

    /* ── Cash Flow Table ──────────────────────────────── */
    .sc .cft{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:18px;box-shadow:var(--sh);margin-bottom:12px}
    .sc .cft h4{font-size:14px;margin:0 0 12px;color:var(--g700)}
    .sc .cft-sec{margin-bottom:10px}
    .sc .cft-hd{font-size:11px;font-weight:700;color:var(--g400);text-transform:uppercase;letter-spacing:.3px;padding:4px 0;border-bottom:1px solid var(--g200);margin-bottom:4px}
    .sc .cft-r{display:flex;justify-content:space-between;padding:5px 0;font-size:12px;color:var(--g600)}
    .sc .cft-r.sub{padding-right:14px;font-size:11px;color:var(--g400)}
    .sc .cft-t{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;font-weight:700;border-top:1px solid var(--g200);margin-top:4px}
    .sc .cft-res{display:flex;justify-content:space-between;padding:10px 14px;border-radius:8px;font-size:14px;font-weight:700;margin-top:8px}
    .sc .cft-res.pos{background:var(--gl);color:#166534;border:1px solid #bbf7d0}
    .sc .cft-res.neg{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}

    /* ── Bar Chart (Yearly Breakdown) ─────────────────── */
    .sc .bars{background:#fff;border:1px solid var(--g200);border-radius:var(--rd);padding:18px;box-shadow:var(--sh);margin-bottom:12px;overflow:hidden}
    .sc .bars h4{font-size:13px;margin:0 0 10px;color:var(--g700)}
    .sc .bar-row{display:flex;align-items:center;gap:8px;margin:3px 0}
    .sc .bar-lbl{min-width:40px;font-size:10px;color:var(--g500);text-align:center;flex-shrink:0}
    .sc .bar-track{flex:1;height:20px;display:flex;border-radius:4px;overflow:hidden;background:var(--g100)}
    .sc .bar-p{background:var(--c);height:100%;transition:width .3s}
    .sc .bar-i{background:var(--o);height:100%;transition:width .3s}
    .sc .bar-val{min-width:100px;font-size:10px;color:var(--g500);text-align:right;flex-shrink:0;direction:ltr}
    .sc .bar-leg{display:flex;gap:14px;justify-content:center;margin-top:8px;font-size:11px;color:var(--g500)}
    .sc .bar-leg span{display:flex;align-items:center;gap:4px}
    .sc .bar-leg i{width:10px;height:10px;border-radius:2px;display:inline-block}

    /* ── Tips ─────────────────────────────────────────── */
    .sc .tips{background:var(--g50);border:1px solid var(--g200);border-radius:var(--rd);padding:16px 18px;margin-bottom:12px}
    .sc .tips h4{font-size:13px;margin:0 0 8px;color:var(--g700)}
    .sc .tip{font-size:12px;color:var(--g600);padding:4px 0;line-height:1.7;display:flex;gap:6px}
    .sc .tip:before{content:'●';color:var(--c);font-size:8px;margin-top:5px;flex-shrink:0}

    /* Disclaimer */
    .sc .disc{font-size:11px;color:var(--g400);text-align:center;margin-top:14px;line-height:1.8}

    @media(max-width:600px){
        .sc .sc-grid{grid-template-columns:1fr}
        .sc .col-body.vis{grid-template-columns:1fr}
        .sc .mg{grid-template-columns:1fr 1fr}
        .sc .hero .hv{font-size:26px}
        .sc .cs{flex-direction:column}
        .sc .er{grid-template-columns:1fr}
        .sc .bar-val{min-width:70px;font-size:9px}
    }
    @media print{.sc .sc-form,.sc .sc-tabs,.sc .xb,.sc .disc,.sc .tips{display:none!important}.sc .sw{display:block!important;max-height:none!important}}
    </style>

    <div class="sc-hd">
        <h3>حاسبة التمويل</h3>
        <p>حسابات دقيقة وفق أنظمة ساما وصندوق التنمية العقاري — الرسوم والضريبة والدعم مشمولة</p>
    </div>

    <div class="sc-tabs">
        <button type="button" class="sc-tab<?php echo $initial_tab==='personal'?' on':''; ?>" data-t="personal">تمويل شخصي</button>
        <button type="button" class="sc-tab<?php echo $initial_tab==='mortgage'?' on':''; ?>" data-t="mortgage">تمويل عقاري</button>
        <button type="button" class="sc-tab<?php echo $initial_tab==='buyout'?' on':''; ?>" data-t="buyout">شراء مديونية</button>
        <button type="button" class="sc-tab<?php echo $initial_tab==='early'?' on':''; ?>" data-t="early">سداد مبكر</button>
    </div>

    <!-- ═══ PERSONAL TAB ═══ -->
    <div class="sc-pane<?php echo $initial_tab==='personal'?' vis':''; ?>" data-p="personal">
        <div class="sc-form"><div class="sc-grid">
            <div class="sc-f"><label>مبلغ التمويل</label><div class="iw"><input type="text" data-k="p_amount" value="300,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>الراتب الشهري</label><div class="iw"><input type="text" data-k="p_salary" placeholder="12,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>الحالة</label><div class="cg" data-g="p_status"><button type="button" class="cb on" data-v="emp">موظف</button><button type="button" class="cb" data-v="ret">متقاعد</button></div></div>
            <div class="sc-f"><label>جهة العمل</label><div class="cg" data-g="p_sector"><button type="button" class="cb on" data-v="gov">حكومي</button><button type="button" class="cb" data-v="pvt">خاص</button><button type="button" class="cb" data-v="mil">عسكري</button></div></div>
            <hr class="fd">
            <div class="sc-f full"><label>المدة (سنوات)</label><div class="sr"><input type="range" data-k="p_years" min="1" max="5" value="5" step="1"><div class="sv"><span data-d="p_years">5</span></div></div></div>
            <div class="sc-f full">
                <label>نسبة الربح</label>
                <div class="rt"><button type="button" class="on" data-rm="apr">APR — معدل النسبة السنوي</button><button type="button" data-rm="flat">Flat — ثابت</button></div>
                <div class="sr"><input type="range" data-k="p_rate" min="0.5" max="12" value="4.25" step="0.01"><div class="sv"><span data-d="p_rate">4.25</span>%</div></div>
                <div style="font-size:11px;color:var(--g400);margin-top:3px" data-d="p_rate_conv"></div>
            </div>
        </div></div>
        <div class="sc-res" data-r="personal"></div>
    </div>

    <!-- ═══ MORTGAGE TAB ═══ -->
    <div class="sc-pane<?php echo $initial_tab==='mortgage'?' vis':''; ?>" data-p="mortgage">
        <div class="sc-form"><div class="sc-grid">
            <div class="sc-f"><label>قيمة العقار</label><div class="iw"><input type="text" data-k="m_property" value="1,000,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>الراتب الشهري</label><div class="iw"><input type="text" data-k="m_salary" placeholder="15,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>المسكن الأول؟</label><div class="cg" data-g="m_first"><button type="button" class="cb on" data-v="yes">نعم</button><button type="button" class="cb" data-v="no">لا</button></div></div>
            <div class="sc-f"><label>جهة التمويل</label><div class="cg" data-g="m_entity"><button type="button" class="cb on" data-v="bank">بنك</button><button type="button" class="cb" data-v="finco">شركة تمويل</button></div></div>
            <div class="sc-f"><label>الحالة</label><div class="cg" data-g="m_status"><button type="button" class="cb on" data-v="emp">موظف</button><button type="button" class="cb" data-v="ret">متقاعد</button></div></div>
            <div class="sc-f"><label>مدعوم؟</label><div class="cg" data-g="m_subsidized"><button type="button" class="cb" data-v="no">غير مدعوم</button><button type="button" class="cb" data-v="yes">مدعوم (صندوق التنمية)</button></div></div>
            <div class="sc-f full" data-show="m_subsidized=yes" style="display:none"><label>دعم الدفعة المقدمة (REDF)</label><div class="cg" data-g="m_dp_support"><button type="button" class="cb on" data-v="0">بدون</button><button type="button" class="cb" data-v="100000">100,000 ر.س</button><button type="button" class="cb" data-v="150000">150,000 ر.س</button></div></div>
            <hr class="fd">
            <div class="sc-f"><label>الدفعة الأولى <small>(تُحسب تلقائياً)</small></label><div class="iw"><input type="text" data-k="m_down" value="" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>مبلغ التمويل <small>(محسوب)</small></label><div class="iw"><input type="text" data-k="m_amount" value="" inputmode="numeric" class="hs" readonly style="background:var(--g50)"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f full"><label>المدة (سنوات)</label><div class="sr"><input type="range" data-k="m_years" min="1" max="30" value="25" step="1"><div class="sv"><span data-d="m_years">25</span></div></div></div>
            <div class="sc-f full">
                <label>نسبة الربح</label>
                <div class="rt"><button type="button" class="on" data-rm="apr">APR</button><button type="button" data-rm="flat">Flat</button></div>
                <div class="sr"><input type="range" data-k="m_rate" min="0.5" max="12" value="4.25" step="0.01"><div class="sv"><span data-d="m_rate">4.25</span>%</div></div>
                <div style="font-size:11px;color:var(--g400);margin-top:3px" data-d="m_rate_conv"></div>
            </div>

            <hr class="fd">
            <div class="col-tog" data-col="m_cashflow">▸ تحليل التدفق النقدي — مدخرات، سعي، تقييم، تمويل جسري</div>
            <div class="col-body" data-colb="m_cashflow">
                <div class="sc-f"><label>المدخرات المتاحة</label><div class="iw"><input type="text" data-k="m_savings" placeholder="200,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
                <div class="sc-f"><label>رسوم التقييم</label><div class="iw"><input type="text" data-k="m_eval_fee" value="3,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
                <hr class="fd">
                <p class="fl">السعي (الوساطة العقارية)</p>
                <div class="sc-f full"><div class="cg" data-g="m_broker_type"><button type="button" class="cb on" data-v="pct">نسبة %</button><button type="button" class="cb" data-v="fixed">مبلغ ثابت</button><button type="button" class="cb" data-v="none">بدون سعي</button></div></div>
                <div class="sc-f" data-show="m_broker_type=pct"><label>نسبة السعي</label><div class="iw"><input type="number" data-k="m_broker_pct" value="2.5" min="0" max="10" step="0.1"><span class="sfx">%</span></div></div>
                <div class="sc-f" data-show="m_broker_type=fixed"><label>مبلغ السعي</label><div class="iw"><input type="text" data-k="m_broker_fixed" value="25,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
                <hr class="fd">
                <p class="fl">تمويل جسري (شخصي لتغطية الدفعة)</p>
                <div class="sc-f full"><label>تفعيل التمويل الجسري؟</label><div class="cg" data-g="m_bridge"><button type="button" class="cb on" data-v="no">لا</button><button type="button" class="cb" data-v="yes">نعم</button></div></div>
                <div class="sc-f" data-show="m_bridge=yes" style="display:none"><label>نسبة التمويل الجسري (APR)</label><div class="iw"><input type="number" data-k="m_bridge_rate" value="4.25" min="0.5" max="12" step="0.01"><span class="sfx">%</span></div></div>
                <div class="sc-f" data-show="m_bridge=yes" style="display:none"><label>مدة التمويل الجسري (سنوات)</label><div class="iw"><input type="number" data-k="m_bridge_years" value="5" min="1" max="5" step="1"></div></div>
                <div class="sc-f full" data-show="m_bridge=yes" style="display:none">
                    <label>نسبة استخدام التمويل الجسري</label>
                    <div class="sr"><input type="range" data-k="m_bridge_usage" min="0" max="100" value="100" step="5"><div class="sv"><span data-d="m_bridge_usage">100</span>%</div></div>
                </div>
            </div>
        </div></div>
        <div class="sc-res" data-r="mortgage"></div>
    </div>

    <!-- ═══ BUYOUT TAB ═══ -->
    <div class="sc-pane<?php echo $initial_tab==='buyout'?' vis':''; ?>" data-p="buyout">
        <div class="sc-form"><div class="sc-grid">
            <p class="fl">التمويل الحالي</p>
            <div class="sc-f"><label>الرصيد المتبقي الحالي</label><div class="iw"><input type="text" data-k="b_old_bal" value="200,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>النسبة الحالية (APR)</label><div class="iw"><input type="number" data-k="b_old_rate" value="7" min="0" max="30" step="0.01"><span class="sfx">%</span></div></div>
            <div class="sc-f"><label>المدة المتبقية (أشهر)</label><div class="iw"><input type="number" data-k="b_old_months" value="36" min="1" max="360" step="1"></div></div>
            <hr class="fd">
            <p class="fl">التمويل الجديد (شراء المديونية)</p>
            <div class="sc-f"><label>مبلغ نقدي إضافي <small>(اختياري)</small></label><div class="iw"><input type="text" data-k="b_extra" value="0" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>النسبة الجديدة (APR)</label><div class="iw"><input type="number" data-k="b_new_rate" value="3.5" min="0" max="30" step="0.01"><span class="sfx">%</span></div></div>
            <div class="sc-f"><label>المدة الجديدة (سنوات)</label><div class="sr"><input type="range" data-k="b_years" min="1" max="5" value="5" step="1"><div class="sv"><span data-d="b_years">5</span></div></div></div>
            <div class="sc-f"><label>الراتب الشهري</label><div class="iw"><input type="text" data-k="b_salary" placeholder="12,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
        </div></div>
        <div class="sc-res" data-r="buyout"></div>
    </div>

    <!-- ═══ EARLY SETTLEMENT TAB ═══ -->
    <div class="sc-pane<?php echo $initial_tab==='early'?' vis':''; ?>" data-p="early">
        <div class="sc-form"><div class="sc-grid">
            <div class="sc-f"><label>الرصيد المتبقي (أصل الدين)</label><div class="iw"><input type="text" data-k="e_bal" value="200,000" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>النسبة (APR)</label><div class="iw"><input type="number" data-k="e_rate" value="5" min="0" max="30" step="0.01"><span class="sfx">%</span></div></div>
            <div class="sc-f"><label>المدة المتبقية (أشهر)</label><div class="iw"><input type="number" data-k="e_months" value="36" min="1" max="360" step="1"></div></div>
            <div class="sc-f"><label>تكاليف طرف ثالث غير مستردة <small>(تأمين إلخ)</small></label><div class="iw"><input type="text" data-k="e_thirdparty" value="0" inputmode="numeric" class="hs"><span class="sfx">ر.س</span></div></div>
            <div class="sc-f"><label>تمويل عقاري؟</label><div class="cg" data-g="e_realestate"><button type="button" class="cb" data-v="no">لا</button><button type="button" class="cb" data-v="yes">نعم</button></div></div>
            <div class="sc-f" data-show="e_realestate=yes" style="display:none"><label>هل أنت ضمن فترة حظر السداد المبكر؟ <small>(أول سنتين)</small></label><div class="cg" data-g="e_prohib"><button type="button" class="cb on" data-v="no">لا</button><button type="button" class="cb" data-v="yes">نعم</button></div></div>
        </div></div>
        <div class="sc-res" data-r="early"></div>
    </div>

    <div class="disc">
        الأرقام تقريبية وفق الأنظمة المعلنة من ساما وصندوق التنمية العقاري — لا تمثل عرضاً رسمياً من أي بنك.
        <br>الرسوم الإدارية: 0.5% أو 2,500 ر.س أيهما أقل (شخصي) · 1% أو 5,000 ر.س (عقاري) + ضريبة 15%.
        <br>نسب الاستقطاع: 33.33% موظفين · 25% متقاعدين · 55% شاملة العقاري.
    </div>

    <script>
    (function(){
    "use strict";
    var uid = <?php echo wp_json_encode( $uid ); ?>;
    var W = document.getElementById(uid);
    if(!W) return;

    /* ══════════════════════════════════════════════
       §3 REGULATORY CONFIG
       ══════════════════════════════════════════════ */
    var CFG = {
        admin_fee_personal: { pct: 0.5, cap: 2500, effective: '2025-12-22', source: 'https://rulebook.sama.gov.sa/en/guide-financial-institutions-services-fees' },
        admin_fee_realestate: { pct: 1.0, cap: 5000, effective: '2025-12-22', source: 'https://rulebook.sama.gov.sa/en/guide-financial-institutions-services-fees' },
        vat_pct: 15,
        ltv_first_home: 0.90,
        ltv_second_bank: 0.70,
        ltv_second_finco: 0.85,
        rett_pct: 5,
        rett_first_home_exempt: 1000000,
        redf_dp_tiers: [0, 100000, 150000],
        redf_profit_subsidy_cap: 500000,
        redf_full_subsidy_salary: 14000,
        dbr_employee: 0.3333,
        dbr_retiree: 0.25,
        dbr_total_excl_re: 0.45,
        dbr_total_incl_re: 0.55,
        early_reinvest_months: 3,
        early_prohib_years: 2,
        max_personal_years: 5,
        max_mortgage_years: 30,
        max_retired_personal: 5,
        max_retired_mortgage: 20
    };

    /* ══════════════════════════════════════════════
       CALCULATION ENGINE
       ══════════════════════════════════════════════ */
    var E = {};

    E.pmt = function(P, aprPct, n) {
        var r = aprPct / 100 / 12;
        if (r < 1e-9) return P / n;
        return P * r * Math.pow(1+r,n) / (Math.pow(1+r,n) - 1);
    };

    E.flatToApr = function(flatPct, years) {
        var n = years * 12, f = flatPct/100, P = 1e5;
        var ti = P * f * years, emi = (P + ti) / n;
        var lo = 1e-9, hi = 0.5;
        for (var i=0;i<100;i++) {
            var m = (lo+hi)/2;
            var pv = m<1e-9 ? emi*n : emi*(1-Math.pow(1+m,-n))/m;
            if (pv > P) lo = m; else hi = m;
        }
        return Math.round((lo+hi)/2*12*1e4)/100;
    };

    E.aprToFlat = function(aprPct, years) {
        var n = years*12, r = aprPct/100/12, P = 1e5;
        var emi = r<1e-9 ? P/n : P*r/(1-Math.pow(1+r,-n));
        return Math.round((emi*n-P)/(P*years)*1e4)/100;
    };

    E.trueApr = function(netProceeds, emi, n) {
        var lo=0, hi=1;
        for(var i=0;i<100;i++){
            var m=(lo+hi)/2;
            var pv = m<1e-9 ? emi*n : emi*(1-Math.pow(1+m,-n))/m;
            if(pv>netProceeds) lo=m; else hi=m;
        }
        return Math.round((lo+hi)/2*12*1e4)/100;
    };

    E.adminFee = function(amount, isMortgage) {
        var c = isMortgage ? CFG.admin_fee_realestate : CFG.admin_fee_personal;
        var fee = Math.min(amount * c.pct / 100, c.cap);
        var vat = fee * CFG.vat_pct / 100;
        return { fee: fee, vat: vat, total: fee + vat };
    };

    E.rett = function(propertyValue, firstHome) {
        if (firstHome) {
            var taxable = Math.max(0, propertyValue - CFG.rett_first_home_exempt);
            return taxable * CFG.rett_pct / 100;
        }
        return propertyValue * CFG.rett_pct / 100;
    };

    E.ltv = function(firstHome, entity) {
        if (firstHome) return CFG.ltv_first_home;
        return entity === 'finco' ? CFG.ltv_second_finco : CFG.ltv_second_bank;
    };

    E.dbr = function(isRetired) {
        return isRetired ? CFG.dbr_retiree : CFG.dbr_employee;
    };

    E.maxLoan = function(maxEmi, aprPct, n) {
        var r = aprPct/100/12;
        if(r<1e-9) return maxEmi*n;
        return maxEmi*(1-Math.pow(1+r,-n))/r;
    };

    E.amortize = function(P, aprPct, n) {
        var r = aprPct/100/12, emi = E.pmt(P,aprPct,n);
        var rows=[], bal=P;
        for(var m=1;m<=n;m++){
            var interest = bal*r;
            var principal = emi - interest;
            if(m===n){principal=bal;interest=emi-principal;}
            bal -= principal; if(bal<0)bal=0;
            rows.push({m:m,emi:emi,principal:principal,interest:interest,balance:bal});
        }
        return rows;
    };

    E.earlySettle = function(balance, aprPct, remainMonths, thirdParty) {
        var r = aprPct/100/12;
        var comp = 0;
        var tempBal = balance;
        var emi = E.pmt(balance, aprPct, remainMonths);
        for(var i=0; i<Math.min(CFG.early_reinvest_months, remainMonths); i++){
            comp += tempBal * r;
            tempBal -= (emi - tempBal*r);
        }
        var totalIfContinue = emi * remainMonths;
        var settleAmount = balance + comp + (thirdParty||0);
        var saved = totalIfContinue - settleAmount;
        return {
            principal: balance,
            compensation: comp,
            thirdParty: thirdParty||0,
            total: settleAmount,
            savedVsContinue: saved,
            totalIfContinue: totalIfContinue
        };
    };

    E.redfSubsidy = function(financeAmount, aprPct, years, salary) {
        if(!salary || salary<=0) return { subsidizedRate:aprPct, monthlySubsidy:0, totalSubsidy:0 };
        var subsidyCap = Math.min(financeAmount, CFG.redf_profit_subsidy_cap);
        var coveragePct = salary <= CFG.redf_full_subsidy_salary ? 1.0 :
            Math.max(0, 1 - (salary - CFG.redf_full_subsidy_salary) / CFG.redf_full_subsidy_salary);
        if(coveragePct <= 0) return { subsidizedRate:aprPct, monthlySubsidy:0, totalSubsidy:0 };
        var n = years*12;
        var fullEmi = E.pmt(subsidyCap, aprPct, n);
        var principalEmi = subsidyCap / n;
        var monthlySubsidy = (fullEmi - principalEmi) * coveragePct;
        var totalSubsidy = monthlySubsidy * n;
        var fullLoanEmi = E.pmt(financeAmount, aprPct, n);
        var effectiveEmi = fullLoanEmi - monthlySubsidy;
        return {
            subsidizedRate: aprPct,
            coveragePct: coveragePct,
            monthlySubsidy: monthlySubsidy,
            totalSubsidy: totalSubsidy,
            effectiveEmi: effectiveEmi,
            subsidyCap: subsidyCap
        };
    };

    E.yearlyBreakdown = function(schedule) {
        var years = [], yP=0, yI=0;
        for(var i=0;i<schedule.length;i++){
            yP += schedule[i].principal;
            yI += schedule[i].interest;
            if((i+1)%12===0 || i===schedule.length-1){
                years.push({year:Math.ceil((i+1)/12), principal:yP, interest:yI, total:yP+yI});
                yP=0; yI=0;
            }
        }
        return years;
    };

    /* ══════════════════════════════════════════════
       UI HELPERS
       ══════════════════════════════════════════════ */
    function $(s,ctx){return (ctx||W).querySelector(s)}
    function $$(s,ctx){return (ctx||W).querySelectorAll(s)}
    function pn(s){return parseInt(String(s).replace(/[^0-9]/g,''),10)||0}
    function fm(n){return Math.round(n).toLocaleString('ar-SA')}
    function fi(inp){var v=pn(inp.value);if(v>0)inp.value=fm(v)}
    function gv(k){var el=$('[data-k="'+k+'"]');if(!el)return 0;if(el.type==='number')return parseFloat(el.value)||0;return pn(el.value)}
    function sv(k,v){var el=$('[data-k="'+k+'"]');if(el){el.value=typeof v==='number'?fm(v):v}}
    function gc(g){var on=$('[data-g="'+g+'"] .cb.on');return on?on.getAttribute('data-v'):'';}

    var activeTab = <?php echo wp_json_encode( $initial_tab ); ?>;
    var rateModes = {personal:'apr',mortgage:'apr',buyout:'apr'};

    // Tab switch
    $$('.sc-tab').forEach(function(btn){
        btn.addEventListener('click',function(){
            $$('.sc-tab').forEach(function(b){b.classList.remove('on')});
            btn.classList.add('on');
            activeTab = btn.getAttribute('data-t');
            $$('.sc-pane').forEach(function(p){p.classList.toggle('vis',p.getAttribute('data-p')===activeTab)});
            calc();
        });
    });

    // Chip groups
    $$('.cg').forEach(function(cg){
        cg.querySelectorAll('.cb').forEach(function(btn){
            btn.addEventListener('click',function(){
                cg.querySelectorAll('.cb').forEach(function(b){b.classList.remove('on')});
                btn.classList.add('on');
                handleConditionalShow();
                calc();
            });
        });
    });

    // Rate toggles
    $$('.rt').forEach(function(rt){
        rt.querySelectorAll('button').forEach(function(btn){
            btn.addEventListener('click',function(){
                rt.querySelectorAll('button').forEach(function(b){b.classList.remove('on')});
                btn.classList.add('on');
                var pane = rt.closest('.sc-pane');
                var tab = pane ? pane.getAttribute('data-p') : 'personal';
                rateModes[tab] = btn.getAttribute('data-rm');
                calc();
            });
        });
    });

    // Sliders
    $$('input[type="range"]').forEach(function(sl){
        sl.addEventListener('input',function(){
            var k = sl.getAttribute('data-k');
            var d = $('[data-d="'+k+'"]');
            if(d) d.textContent = sl.value;
            calc();
        });
    });

    // Text inputs
    $$('input[type="text"]').forEach(function(inp){
        if(inp.readOnly) return;
        inp.addEventListener('input',function(){fi(inp);calc()});
        inp.addEventListener('blur',function(){fi(inp)});
    });
    $$('input[type="number"]').forEach(function(inp){
        inp.addEventListener('input',calc);
    });

    // Collapsible toggles
    $$('.col-tog').forEach(function(tog){
        tog.addEventListener('click',function(){
            var key = tog.getAttribute('data-col');
            var body = $('[data-colb="'+key+'"]');
            if(!body) return;
            var open = body.classList.toggle('vis');
            tog.textContent = (open ? '▾ ' : '▸ ') + tog.textContent.replace(/^[▸▾]\s*/, '');
            if(open) handleConditionalShow();
        });
    });

    function handleConditionalShow(){
        $$('[data-show]').forEach(function(el){
            var rule = el.getAttribute('data-show').split('=');
            var val = gc(rule[0]);
            el.style.display = val===rule[1] ? '' : 'none';
        });
    }

    function getApr(rateKey, yearsKey, tab) {
        var val = parseFloat($('[data-k="'+rateKey+'"]').value)||0;
        var years = parseInt($('[data-k="'+yearsKey+'"]').value)||1;
        var mode = rateModes[tab]||'apr';
        return mode === 'flat' ? E.flatToApr(val, years) : val;
    }

    function showConv(rateKey, yearsKey, tab, convKey) {
        var val = parseFloat($('[data-k="'+rateKey+'"]').value)||0;
        var years = parseInt($('[data-k="'+yearsKey+'"]').value)||1;
        var mode = rateModes[tab]||'apr';
        var d = $('[data-d="'+convKey+'"]');
        if(!d) return;
        if(mode==='apr') d.textContent='يعادل Flat ≈ '+E.aprToFlat(val,years)+'%';
        else d.textContent='يعادل APR ≈ '+E.flatToApr(val,years)+'%';
    }

    /* ══════════════════════════════════════════════
       TIPS DATA
       ══════════════════════════════════════════════ */
    var TIPS = {
        personal: [
            'قارن بالـ APR وليس النسبة الثابتة — الـ APR يشمل الفرق الناتج عن طريقة احتساب الأقساط.',
            'الرسوم الإدارية تُخصم عند الصرف في بعض البنوك. اسأل: هل المبلغ المحول لحسابي قبل أو بعد الرسوم؟',
            'بعض جهات العمل لديها اتفاقيات أسعار خاصة مع البنوك — اسأل قسم الموارد البشرية.',
            'إذا لديك تمويل حالي بنسبة أعلى، فكّر بشراء المديونية قبل أخذ تمويل جديد.'
        ],
        mortgage: [
            'إعفاء المسكن الأول يوفر لك حتى 50,000 ر.س من ضريبة التصرفات العقارية.',
            'دعم صندوق التنمية العقاري يقلل التكلفة بشكل كبير لرواتب حتى 14,000 ر.س.',
            'التمويل الجسري (قرض شخصي لتغطية الدفعة) يضيف عبئاً شهرياً — خطط لسداده بأسرع وقت.',
            'قارن التكلفة الكاملة (قسط + رسوم + ضريبة + سعي + تقييم) وليس القسط الشهري فقط.',
            'نسبة الاستقطاع الشامل مع العقاري حتى 55% — لكن الأقل أفضل لراحتك المالية.'
        ],
        buyout: [
            'شراء المديونية يستحق عندما الفرق بالنسبة أكثر من 1% والمدة المتبقية طويلة.',
            'تذكّر أن تعويض السداد المبكر (3 أشهر أرباح) يُحسب ضمن تكلفة النقل.',
            'كلما كان النقل أبكر في عمر القرض، كلما كان التوفير أكبر.',
            'بعض البنوك تعرض نقداً إضافياً مع شراء المديونية — لكن هذا يزيد التكلفة الإجمالية.'
        ],
        early: [
            'ساما تحدد تعويض السداد المبكر بأرباح 3 أشهر كحد أقصى (قاعدة إعادة الاستثمار).',
            'التمويل العقاري: ممنوع السداد المبكر أول سنتين. بعدها يحق لك بدون قيد.',
            'تكاليف التأمين والطرف الثالث غير المستردة تُضاف لمبلغ السداد — اسأل بنكك عن التفاصيل.',
            'إذا توفر لديك مبلغ كبير، السداد المبكر يوفر أكثر كلما كانت النسبة أعلى والمدة المتبقية أطول.'
        ]
    };

    /* ══════════════════════════════════════════════
       RENDER RESULTS
       ══════════════════════════════════════════════ */
    function renderResults(container, data) {
        var h = '';

        // Hero
        h += '<div class="hero"><div class="hl">'+data.heroLabel+'</div>';
        h += '<div class="hv">'+fm(data.heroValue)+' <small>ر.س'+( data.heroSuffix||' / شهر')+'</small></div>';
        if(data.heroSub) h += '<div class="hs">'+data.heroSub+'</div>';
        h += '</div>';

        // Alerts
        if(data.alerts) data.alerts.forEach(function(a){
            h += '<div class="al vis '+a.type+'">'+a.text+'</div>';
        });

        // Metrics
        if(data.metrics) {
            h += '<div class="mg">';
            data.metrics.forEach(function(m){
                h += '<div class="mc"><div class="ml">'+m.label+'</div>';
                h += '<div class="mv"'+(m.color?' style="color:'+m.color+'"':'')+'>'+m.value+(m.unit?' <small>'+m.unit+'</small>':'')+'</div>';
                if(m.sub) h += '<div class="ms">'+m.sub+'</div>';
                h += '</div>';
            });
            h += '</div>';
        }

        // Fees
        if(data.fees) {
            h += '<div class="fb"><h4>تفصيل الرسوم والتكاليف</h4>';
            data.fees.forEach(function(f){
                h += '<div class="fr'+(f.total?' tot':'')+'"><span>'+f.label+'</span><span>'+f.value+'</span></div>';
            });
            h += '</div>';
        }

        // Cash flow table
        if(data.cashflow) {
            var cf = data.cashflow;
            h += '<div class="cft"><h4>تحليل التدفق النقدي — هل تكفي سيولتك؟</h4>';

            h += '<div class="cft-sec"><div class="cft-hd">المطلوب نقداً</div>';
            cf.costs.forEach(function(c){
                h += '<div class="cft-r'+(c.sub?' sub':'')+'"><span>'+c.label+'</span><span>'+c.value+'</span></div>';
            });
            h += '<div class="cft-t"><span>إجمالي المطلوب</span><span>'+fm(cf.totalCosts)+' ر.س</span></div>';
            h += '</div>';

            h += '<div class="cft-sec"><div class="cft-hd">المتاح</div>';
            cf.funds.forEach(function(c){
                h += '<div class="cft-r'+(c.sub?' sub':'')+'"><span>'+c.label+'</span><span>'+c.value+'</span></div>';
            });
            h += '<div class="cft-t"><span>إجمالي المتاح</span><span>'+fm(cf.totalFunds)+' ر.س</span></div>';
            h += '</div>';

            var gap = cf.totalFunds - cf.totalCosts;
            h += '<div class="cft-res '+(gap>=0?'pos':'neg')+'"><span>'+(gap>=0?'فائض':'عجز')+'</span><span>'+fm(Math.abs(gap))+' ر.س</span></div>';
            h += '</div>';
        }

        // Buyout comparison
        if(data.comparison) {
            h += '<div class="bc"><h4>المقارنة: القديم مقابل الجديد</h4>';
            h += '<table class="bt"><thead><tr><th></th><th>التمويل الحالي</th><th>شراء المديونية</th><th>الفرق</th></tr></thead><tbody>';
            data.comparison.forEach(function(r){
                h += '<tr><td style="font-weight:600;text-align:right">'+r.label+'</td>';
                h += '<td>'+r.old+'</td><td>'+r.new_+'</td>';
                h += '<td class="'+(r.better?'hi':'')+'">'+r.diff+'</td></tr>';
            });
            h += '</tbody></table></div>';
        }

        // Pie chart
        if(data.chart) {
            var segs = data.chart.segments;
            var total = segs.reduce(function(s,x){return s+x.value},0);
            var grad = '', deg = 0;
            segs.forEach(function(s,i){
                var d = (s.value/total)*360;
                grad += s.color+' '+deg+'deg '+(deg+d)+'deg';
                if(i<segs.length-1)grad+=', ';
                deg += d;
            });
            h += '<div class="cs"><div class="cp" style="background:conic-gradient('+grad+')">';
            h += '<div class="cc"><span class="pv">'+data.chart.centerPct+'</span><span class="pl">'+data.chart.centerLabel+'</span></div>';
            h += '</div><div class="cl">';
            segs.forEach(function(s){
                h += '<div class="ci"><div class="cd" style="background:'+s.color+'"></div><span>'+s.label+': <strong>'+fm(s.value)+'</strong> ر.س</span></div>';
            });
            h += '</div></div>';
        }

        // Bar chart (yearly breakdown)
        if(data.schedule && data.schedule.length > 12) {
            var yrs = E.yearlyBreakdown(data.schedule);
            var maxTotal = Math.max.apply(null, yrs.map(function(y){return y.total}));
            h += '<div class="bars"><h4>توزيع الأقساط السنوي — أصل الدين مقابل كلفة الأجل</h4>';
            yrs.forEach(function(y){
                var pW = (y.principal/maxTotal*100).toFixed(1);
                var iW = (y.interest/maxTotal*100).toFixed(1);
                h += '<div class="bar-row">';
                h += '<div class="bar-lbl">'+y.year+'</div>';
                h += '<div class="bar-track"><div class="bar-p" style="width:'+pW+'%"></div><div class="bar-i" style="width:'+iW+'%"></div></div>';
                h += '<div class="bar-val">'+fm(y.principal)+' | '+fm(y.interest)+'</div>';
                h += '</div>';
            });
            h += '<div class="bar-leg"><span><i style="background:var(--c)"></i> أصل الدين</span><span><i style="background:var(--o)"></i> كلفة الأجل</span></div>';
            h += '</div>';
        }

        // Early settlement section
        if(data.earlySection) {
            var es = data.earlySection;
            h += '<div class="es"><h4>السداد المبكر</h4>';
            h += '<div class="er">';
            es.cards.forEach(function(c){
                h += '<div class="ec"><div class="el">'+c.label+'</div><div class="ev'+(c.green?' sv':'')+'">'+c.value+'</div></div>';
            });
            h += '</div></div>';
        }

        // Export bar
        if(data.schedule) {
            h += '<div class="xb"><button type="button" data-act="csv">تصدير CSV</button><button type="button" data-act="print">طباعة</button></div>';
        }

        // Schedule
        if(data.schedule) {
            h += '<div class="ss" data-sched><div class="st"><span>جدول السداد التفصيلي ('+data.schedule.length+' شهر)</span><span class="ar">▾</span></div>';
            h += '<div class="sw"><table class="stb"><thead><tr><th>#</th><th>القسط</th><th>أصل الدين</th><th>كلفة الأجل</th><th>الرصيد</th></tr></thead><tbody>';
            var yP=0,yI=0;
            data.schedule.forEach(function(r){
                yP+=r.principal;yI+=r.interest;
                h+='<tr><td>'+r.m+'</td><td>'+fm(r.emi)+'</td><td>'+fm(r.principal)+'</td><td>'+fm(r.interest)+'</td><td>'+fm(r.balance)+'</td></tr>';
                if(r.m%12===0||r.m===data.schedule.length){
                    h+='<tr class="yr"><td colspan="5">السنة '+Math.ceil(r.m/12)+' — أصل: '+fm(yP)+' | ربح: '+fm(yI)+' ر.س</td></tr>';
                    yP=0;yI=0;
                }
            });
            h += '</tbody></table></div></div>';
        }

        // Tips
        if(data.tips && data.tips.length) {
            h += '<div class="tips"><h4>نصائح</h4>';
            data.tips.forEach(function(t){
                h += '<div class="tip"><span>'+t+'</span></div>';
            });
            h += '</div>';
        }

        container.innerHTML = h;
        container.classList.add('vis');

        // Schedule toggle
        var sched = container.querySelector('[data-sched]');
        if(sched){
            sched.querySelector('.st').addEventListener('click',function(){sched.classList.toggle('op')});
        }

        // Export handlers
        container.querySelectorAll('[data-act]').forEach(function(btn){
            btn.addEventListener('click',function(){
                var act = btn.getAttribute('data-act');
                if(act==='print') window.print();
                if(act==='csv' && data.schedule) exportCSV(data.schedule);
            });
        });
    }

    function exportCSV(rows){
        var csv = '﻿#,القسط,أصل الدين,كلفة الأجل,الرصيد\n';
        rows.forEach(function(r){
            csv+=r.m+','+Math.round(r.emi)+','+Math.round(r.principal)+','+Math.round(r.interest)+','+Math.round(r.balance)+'\n';
        });
        var blob = new Blob([csv],{type:'text/csv;charset=utf-8;'});
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'جدول_السداد.csv';
        a.click();
    }

    /* ══════════════════════════════════════════════
       CALC DISPATCHER
       ══════════════════════════════════════════════ */
    function calc(){
        handleConditionalShow();
        if(activeTab==='personal') calcPersonal();
        else if(activeTab==='mortgage') calcMortgage();
        else if(activeTab==='buyout') calcBuyout();
        else if(activeTab==='early') calcEarly();
    }

    /* ── PERSONAL ────────────────────────────────── */
    function calcPersonal(){
        var amount = gv('p_amount'), salary = gv('p_salary');
        var years = parseInt($('[data-k="p_years"]').value)||5;
        var months = years*12;
        var apr = getApr('p_rate','p_years','personal');
        var isRetired = gc('p_status')==='ret';
        showConv('p_rate','p_years','personal','p_rate_conv');

        if(amount<=0) return;

        var fees = E.adminFee(amount, false);
        var emi = E.pmt(amount, apr, months);
        var total = emi*months;
        var profit = total-amount;
        var netProceeds = amount - fees.total;
        var trueApr = E.trueApr(netProceeds, emi, months);
        var totalWithFees = total + fees.total;

        var alerts = [];
        if(salary>0){
            var dbr = E.dbr(isRetired);
            var ratio = emi/salary;
            if(ratio<=dbr) alerts.push({type:'ok',text:'✓ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong> — ضمن حد ساما ('+(dbr*100).toFixed(1)+'% '+(isRetired?'متقاعدين':'موظفين')+')'});
            else if(ratio<=CFG.dbr_total_excl_re) alerts.push({type:'wn',text:'⚠ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong> — يتجاوز حد التمويل الشخصي ('+(dbr*100).toFixed(1)+'%) لكن ضمن الحد الكلي (45%)'});
            else alerts.push({type:'dg',text:'✕ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong> — يتجاوز الحد الأقصى. مرجع: أنظمة الإقراض المسؤول، ساما'});
        }

        var schedule = E.amortize(amount, apr, months);

        var data = {
            heroLabel: 'القسط الشهري',
            heroValue: emi,
            heroSub: fees.total>0?'رسوم لمرة واحدة: '+fm(fees.total)+' ر.س (شاملة الضريبة)':'',
            alerts: alerts,
            metrics: [
                {label:'إجمالي السداد',value:fm(totalWithFees),unit:'ر.س'},
                {label:'أرباح البنك',value:fm(profit),unit:'ر.س',color:'var(--o)',sub:(profit/amount*100).toFixed(1)+'% من الأصل'},
                {label:'الرسوم + ضريبة',value:fm(fees.total),unit:'ر.س',color:'var(--p)'},
                {label:'APR الفعلي (شامل الرسوم)',value:trueApr+'%',color:'var(--r)'},
                {label:'عدد الأقساط',value:months,unit:'شهر'}
            ],
            fees: [
                {label:'الرسوم الإدارية ('+CFG.admin_fee_personal.pct+'% · سقف '+fm(CFG.admin_fee_personal.cap)+' ر.س)', value:fm(fees.fee)+' ر.س'},
                {label:'ضريبة القيمة المضافة ('+CFG.vat_pct+'%)', value:fm(fees.vat)+' ر.س'},
                {label:'الإجمالي', value:fm(fees.total)+' ر.س', total:true}
            ],
            chart: {
                centerPct: ((profit+fees.total)/(amount+profit+fees.total)*100).toFixed(1)+'%',
                centerLabel: 'تكلفة التمويل',
                segments: [
                    {label:'أصل المبلغ',value:amount,color:'var(--c)'},
                    {label:'أرباح البنك',value:profit,color:'var(--o)'},
                    {label:'رسوم + ضريبة',value:fees.total,color:'var(--p)'}
                ]
            },
            schedule: schedule,
            tips: TIPS.personal
        };

        if(salary>0){
            var dbrLim = E.dbr(isRetired);
            var maxEmi = salary*dbrLim;
            var maxLoan = E.maxLoan(maxEmi, apr, months);
            data.metrics.push({label:'أقصى تمويل ممكن ('+((dbrLim*100).toFixed(0))+'%)',value:fm(maxLoan),unit:'ر.س',color:'var(--c)'});
        }

        renderResults($('[data-r="personal"]'), data);
    }

    /* ── MORTGAGE ─────────────────────────────────── */
    function calcMortgage(){
        var prop = gv('m_property'), salary = gv('m_salary');
        var years = parseInt($('[data-k="m_years"]').value)||25;
        var months = years*12;
        var apr = getApr('m_rate','m_years','mortgage');
        var firstHome = gc('m_first')==='yes';
        var entity = gc('m_entity')||'bank';
        var isRetired = gc('m_status')==='ret';
        var isSubsidized = gc('m_subsidized')==='yes';
        var dpSupport = isSubsidized ? parseInt(gc('m_dp_support'))||0 : 0;
        showConv('m_rate','m_years','mortgage','m_rate_conv');

        if(prop<=0) return;

        // LTV & down payment
        var ltv = E.ltv(firstHome, entity);
        var minDown = Math.ceil(prop * (1-ltv));
        var userDown = gv('m_down');
        if(userDown < minDown || userDown===0) {
            userDown = Math.max(0, minDown - dpSupport);
            sv('m_down', userDown);
        }
        var financed = prop - userDown - dpSupport;
        if(financed<0) financed=0;
        sv('m_amount', financed);

        // RETT
        var rett = E.rett(prop, firstHome);

        // Fees
        var fees = E.adminFee(financed, true);

        // Brokerage
        var brokerType = gc('m_broker_type') || 'pct';
        var brokerage = 0;
        if(brokerType === 'pct') brokerage = prop * (gv('m_broker_pct') || 2.5) / 100;
        else if(brokerType === 'fixed') brokerage = gv('m_broker_fixed');

        // Eval fees
        var evalFee = gv('m_eval_fee');

        // EMI
        var emi = E.pmt(financed, apr, months);
        var total = emi*months;
        var profit = total-financed;

        // REDF subsidy
        var redf = {monthlySubsidy:0,totalSubsidy:0,coveragePct:0};
        if(isSubsidized && salary>0){
            redf = E.redfSubsidy(financed, apr, years, salary);
        }
        var effectiveEmi = emi - redf.monthlySubsidy;
        var effectiveTotal = effectiveEmi * months;

        var trueApr = E.trueApr(financed - fees.total, effectiveEmi, months);

        // Bridge loan
        var bridgeOn = gc('m_bridge') === 'yes';
        var bridgeAmount = 0, bridgeEmi = 0, bridgeTotal = 0, bridgeProfit = 0;
        var bridgeUsage = 100;
        if(bridgeOn){
            var bridgeRate = gv('m_bridge_rate') || 4.25;
            var bridgeYears = gv('m_bridge_years') || 5;
            var bridgeMonths = bridgeYears * 12;
            bridgeUsage = parseInt($('[data-k="m_bridge_usage"]').value) || 100;

            var savings = gv('m_savings');
            var totalUpfront = userDown + rett + brokerage + evalFee + fees.total;
            var gap = Math.max(0, totalUpfront - savings - dpSupport);
            bridgeAmount = Math.round(gap * bridgeUsage / 100);

            if(bridgeAmount > 0){
                var bridgeFees = E.adminFee(bridgeAmount, false);
                bridgeEmi = E.pmt(bridgeAmount, bridgeRate, bridgeMonths);
                bridgeTotal = bridgeEmi * bridgeMonths;
                bridgeProfit = bridgeTotal - bridgeAmount;
            }
        }

        var combinedEmi = effectiveEmi + bridgeEmi;

        // Cash flow analysis
        var savings = gv('m_savings');
        var totalCosts = userDown + rett + brokerage + evalFee + fees.total;
        var totalFunds = savings + dpSupport + (bridgeOn ? bridgeAmount : 0);

        var costItems = [
            {label:'الدفعة المقدمة (من جيبك)', value:fm(userDown)+' ر.س'},
            {label:'ضريبة التصرفات العقارية (RETT)', value:fm(rett)+' ر.س'}
        ];
        if(brokerage > 0) costItems.push({label:'السعي (الوساطة)', value:fm(brokerage)+' ر.س'});
        if(evalFee > 0) costItems.push({label:'رسوم التقييم', value:fm(evalFee)+' ر.س'});
        costItems.push({label:'الرسوم الإدارية + ضريبة', value:fm(fees.total)+' ر.س'});

        var fundItems = [];
        if(savings > 0) fundItems.push({label:'المدخرات', value:fm(savings)+' ر.س'});
        if(dpSupport > 0) fundItems.push({label:'دعم الدفعة المقدمة (REDF)', value:fm(dpSupport)+' ر.س'});
        if(bridgeOn && bridgeAmount > 0) fundItems.push({label:'حصيلة التمويل الجسري', value:fm(bridgeAmount)+' ر.س', sub:true});
        if(fundItems.length === 0) fundItems.push({label:'لم تُدخل مدخرات', value:'0 ر.س'});

        var hasCashFlow = savings > 0 || bridgeOn;

        // Alerts
        var alerts = [];
        alerts.push({type:'in',text:'نسبة التمويل (LTV): <strong>'+(ltv*100)+'%</strong> — الدفعة الأولى المطلوبة: <strong>'+((1-ltv)*100)+'%</strong> = '+fm(minDown)+' ر.س'+(dpSupport>0?' (بعد دعم REDF '+fm(dpSupport)+' ر.س = '+fm(userDown)+' ر.س من جيبك)':'')});

        if(firstHome && prop<=CFG.rett_first_home_exempt){
            alerts.push({type:'ok',text:'✓ ضريبة التصرفات العقارية: <strong>معفى بالكامل</strong> — المسكن الأول بقيمة أقل من '+fm(CFG.rett_first_home_exempt)+' ر.س.'});
        } else if(firstHome){
            var rettExempt = Math.min(prop, CFG.rett_first_home_exempt) * CFG.rett_pct/100;
            alerts.push({type:'wn',text:'⚠ ضريبة التصرفات العقارية: <strong>'+fm(rett)+' ر.س</strong> — الإعفاء يغطي أول '+fm(CFG.rett_first_home_exempt)+' ر.س فقط (وفّرت '+fm(rettExempt)+' ر.س).'});
        } else {
            alerts.push({type:'wn',text:'⚠ ضريبة التصرفات العقارية: <strong>'+fm(rett)+' ر.س</strong> (5% من قيمة العقار). لا يوجد إعفاء للمسكن الثاني.'});
        }

        if(isSubsidized){
            if(redf.totalSubsidy>0){
                alerts.push({type:'ok',text:'✓ دعم REDF: يتحمل الصندوق <strong>'+fm(redf.totalSubsidy)+' ر.س</strong> من الأرباح ('+(redf.coveragePct*100).toFixed(0)+'% تغطية على أول '+fm(CFG.redf_profit_subsidy_cap)+' ر.س). القسط بعد الدعم: <strong>'+fm(effectiveEmi)+' ر.س</strong>'});
            } else if(salary>CFG.redf_full_subsidy_salary*2){
                alerts.push({type:'dg',text:'✕ راتبك يتجاوز سقف الدعم — يُعامل كتمويل غير مدعوم.'});
            } else if(salary<=0){
                alerts.push({type:'in',text:'أدخل راتبك لحساب أهلية دعم REDF. الدعم الكامل لرواتب حتى '+fm(CFG.redf_full_subsidy_salary)+' ر.س.'});
            }
        }

        if(salary>0){
            var dbrLim = CFG.dbr_total_incl_re;
            var ratio = combinedEmi/salary;
            if(ratio<=E.dbr(isRetired)) alerts.push({type:'ok',text:'✓ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong>'+(bridgeEmi>0?' (عقاري + جسري)':'')+' — ضمن الحد.'});
            else if(ratio<=dbrLim) alerts.push({type:'wn',text:'⚠ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong>'+(bridgeEmi>0?' (عقاري + جسري)':'')+' — مقبول مع التمويل العقاري (حد 55%) لكن يتجاوز حد التمويل الشخصي.'});
            else alerts.push({type:'dg',text:'✕ الاستقطاع <strong>'+(ratio*100).toFixed(1)+'%</strong>'+(bridgeEmi>0?' (عقاري + جسري)':'')+' — يتجاوز الحد الأقصى الشامل (55%). مرجع: ساما.'});
        }

        if(bridgeOn && bridgeAmount > 0){
            alerts.push({type:'in',text:'التمويل الجسري: <strong>'+fm(bridgeAmount)+' ر.س</strong> بقسط شهري <strong>'+fm(bridgeEmi)+' ر.س</strong> لمدة '+gv('m_bridge_years')+' سنوات. إجمالي القسط الشهري (عقاري + جسري): <strong>'+fm(combinedEmi)+' ر.س</strong>'});
        }

        if(hasCashFlow){
            var gap = totalFunds - totalCosts;
            if(gap >= 0) alerts.push({type:'ok',text:'✓ سيولتك تكفي — لديك فائض <strong>'+fm(gap)+' ر.س</strong> بعد جميع التكاليف.'});
            else alerts.push({type:'dg',text:'✕ عجز في السيولة: <strong>'+fm(Math.abs(gap))+' ر.س</strong>. '+(bridgeOn?'حتى مع التمويل الجسري.':'فعّل التمويل الجسري لتغطية الفرق.')});
        }

        var feesLines = [
            {label:'الرسوم الإدارية ('+CFG.admin_fee_realestate.pct+'% · سقف '+fm(CFG.admin_fee_realestate.cap)+')', value:fm(fees.fee)+' ر.س'},
            {label:'ضريبة القيمة المضافة ('+CFG.vat_pct+'%)', value:fm(fees.vat)+' ر.س'},
            {label:'ضريبة التصرفات العقارية (RETT)', value:fm(rett)+' ر.س'}
        ];
        if(brokerage>0) feesLines.push({label:'السعي (الوساطة)', value:fm(brokerage)+' ر.س'});
        if(evalFee>0) feesLines.push({label:'رسوم التقييم', value:fm(evalFee)+' ر.س'});
        if(dpSupport>0) feesLines.push({label:'دعم الدفعة المقدمة (REDF)',value:'-'+fm(dpSupport)+' ر.س'});
        var totalCash = userDown + rett + fees.total + brokerage + evalFee;
        feesLines.push({label:'إجمالي المطلوب نقداً عند التوقيع',value:fm(totalCash)+' ر.س',total:true});

        var totalCost = effectiveTotal + totalCash + bridgeTotal;

        var data = {
            heroLabel: 'القسط الشهري'+(redf.monthlySubsidy>0?' (بعد الدعم)':'')+(bridgeEmi>0?' + الجسري':''),
            heroValue: combinedEmi,
            heroSub: (redf.monthlySubsidy>0?'قبل الدعم: '+fm(emi)+' ر.س · دعم REDF: '+fm(redf.monthlySubsidy)+' ر.س':'')+(bridgeEmi>0?(redf.monthlySubsidy>0?' · ':'')+'عقاري: '+fm(effectiveEmi)+' + جسري: '+fm(bridgeEmi):''),
            alerts: alerts,
            metrics: [
                {label:'مبلغ التمويل',value:fm(financed),unit:'ر.س'},
                {label:'الدفعة الأولى',value:fm(userDown+dpSupport),unit:'ر.س',sub:dpSupport>0?'منها '+fm(dpSupport)+' دعم':''},
                {label:'إجمالي السداد',value:fm(effectiveTotal+(bridgeTotal)),unit:'ر.س',sub:bridgeTotal>0?'عقاري: '+fm(effectiveTotal)+' + جسري: '+fm(bridgeTotal):''},
                {label:'أرباح البنك',value:fm(profit+(bridgeProfit)),unit:'ر.س',color:'var(--o)',sub:redf.totalSubsidy>0?'يتحمل REDF '+fm(redf.totalSubsidy):''},
                {label:'APR شامل الرسوم',value:trueApr+'%',color:'var(--r)'},
                {label:'التكلفة الكاملة',value:fm(totalCost),unit:'ر.س',color:'var(--r)',sub:'شامل الدفعة والرسوم والضريبة والسعي'}
            ],
            fees: feesLines,
            chart: {
                centerPct:((profit-redf.totalSubsidy+fees.total+rett+brokerage+evalFee)/(financed+profit-redf.totalSubsidy+fees.total+rett+brokerage+evalFee)*100).toFixed(1)+'%',
                centerLabel:'تكلفة التمويل',
                segments:[
                    {label:'أصل التمويل',value:financed,color:'var(--c)'},
                    {label:'أرباح (بعد الدعم)',value:Math.max(0,profit-redf.totalSubsidy)+bridgeProfit,color:'var(--o)'},
                    {label:'رسوم + ضريبة + RETT + سعي',value:fees.total+rett+brokerage+evalFee,color:'var(--p)'}
                ]
            },
            schedule: E.amortize(financed, apr, months),
            tips: TIPS.mortgage
        };

        if(hasCashFlow){
            data.cashflow = {
                costs: costItems,
                totalCosts: totalCosts,
                funds: fundItems,
                totalFunds: totalFunds
            };
        }

        renderResults($('[data-r="mortgage"]'), data);
    }

    /* ── BUYOUT ───────────────────────────────────── */
    function calcBuyout(){
        var oldBal = gv('b_old_bal'), oldRate = gv('b_old_rate'), oldMonths = gv('b_old_months');
        var extra = gv('b_extra'), newRate = gv('b_new_rate');
        var newYears = parseInt($('[data-k="b_years"]').value)||5;
        var newMonths = newYears*12;
        var salary = gv('b_salary');

        if(oldBal<=0) return;

        var oldEmi = E.pmt(oldBal, oldRate, oldMonths);
        var oldTotal = oldEmi * oldMonths;

        var settle = E.earlySettle(oldBal, oldRate, oldMonths, 0);

        var newAmount = settle.principal + settle.compensation + extra;
        var fees = E.adminFee(newAmount, false);
        var newEmi = E.pmt(newAmount, newRate, newMonths);
        var newTotal = newEmi * newMonths;
        var newProfit = newTotal - newAmount;
        var trueApr = E.trueApr(newAmount - fees.total, newEmi, newMonths);

        var monthlySaving = oldEmi - newEmi;
        var totalSaving = oldTotal - newTotal - fees.total - settle.compensation;

        var alerts = [];
        if(totalSaving>0) alerts.push({type:'ok',text:'✓ شراء المديونية يوفر لك <strong>'+fm(totalSaving)+' ر.س</strong> إجمالاً و <strong>'+fm(monthlySaving)+' ر.س</strong> شهرياً'});
        else alerts.push({type:'dg',text:'✕ شراء المديونية بهذه الشروط يكلّفك <strong>'+fm(Math.abs(totalSaving))+' ر.س</strong> إضافية. راجع النسبة أو المدة.'});

        if(salary>0){
            var ratio = newEmi/salary;
            var dbr = CFG.dbr_employee;
            if(ratio>dbr) alerts.push({type:'wn',text:'⚠ الاستقطاع '+(ratio*100).toFixed(1)+'% يتجاوز حد ساما ('+(dbr*100).toFixed(1)+'%)'});
        }

        var data = {
            heroLabel:'القسط الشهري الجديد',
            heroValue: newEmi,
            heroSub: monthlySaving>0?'توفير شهري: '+fm(monthlySaving)+' ر.س مقارنة بالتمويل الحالي':'',
            alerts: alerts,
            metrics:[
                {label:'مبلغ السداد المبكر',value:fm(settle.total),unit:'ر.س',sub:'أصل '+fm(settle.principal)+' + تعويض '+fm(settle.compensation)},
                {label:'مبلغ التمويل الجديد',value:fm(newAmount),unit:'ر.س',sub:extra>0?'يشمل '+fm(extra)+' نقد إضافي':''},
                {label:'أرباح البنك الجديد',value:fm(newProfit),unit:'ر.س',color:'var(--o)'},
                {label:'الرسوم + ضريبة',value:fm(fees.total),unit:'ر.س',color:'var(--p)'},
                {label:'APR شامل الرسوم',value:trueApr+'%',color:'var(--r)'},
                {label:'صافي التوفير/التكلفة',value:(totalSaving>=0?'':'-')+fm(Math.abs(totalSaving)),unit:'ر.س',color:totalSaving>=0?'var(--g)':'var(--r)'}
            ],
            comparison:[
                {label:'القسط الشهري',old:fm(oldEmi)+' ر.س',new_:fm(newEmi)+' ر.س',diff:(monthlySaving>=0?'-':'+') +fm(Math.abs(monthlySaving))+' ر.س',better:monthlySaving>0},
                {label:'النسبة (APR)',old:oldRate+'%',new_:newRate+'%',diff:(newRate<oldRate?'-':'+')+Math.abs(newRate-oldRate).toFixed(2)+'%',better:newRate<oldRate},
                {label:'المدة المتبقية',old:oldMonths+' شهر',new_:newMonths+' شهر',diff:(newMonths-oldMonths)+' شهر',better:newMonths<=oldMonths},
                {label:'إجمالي المتبقي',old:fm(oldTotal)+' ر.س',new_:fm(newTotal+fees.total)+' ر.س',diff:(totalSaving>=0?'-':'+')+fm(Math.abs(totalSaving))+' ر.س',better:totalSaving>0}
            ],
            fees:[
                {label:'تعويض السداد المبكر (3 أشهر)',value:fm(settle.compensation)+' ر.س'},
                {label:'الرسوم الإدارية ('+CFG.admin_fee_personal.pct+'%)',value:fm(fees.fee)+' ر.س'},
                {label:'ضريبة ('+CFG.vat_pct+'%)',value:fm(fees.vat)+' ر.س'},
                {label:'الإجمالي',value:fm(fees.total+settle.compensation)+' ر.س',total:true}
            ],
            schedule: E.amortize(newAmount, newRate, newMonths),
            tips: TIPS.buyout
        };

        renderResults($('[data-r="buyout"]'), data);
    }

    /* ── EARLY SETTLEMENT ─────────────────────────── */
    function calcEarly(){
        var bal = gv('e_bal'), rate = gv('e_rate'), months = gv('e_months');
        var tp = gv('e_thirdparty');
        var isRE = gc('e_realestate')==='yes';
        var prohib = isRE && gc('e_prohib')==='yes';

        if(bal<=0||months<=0) return;

        var settle = E.earlySettle(bal, rate, months, tp);

        var alerts = [];
        if(prohib) alerts.push({type:'dg',text:'⚠ أنت ضمن فترة حظر السداد المبكر (أول سنتين للتمويل العقاري). قد يرفض البنك الطلب أو يفرض شروطاً إضافية. مرجع: ساما'});
        if(settle.savedVsContinue>0) alerts.push({type:'ok',text:'✓ السداد المبكر الآن يوفر لك <strong>'+fm(settle.savedVsContinue)+' ر.س</strong> مقارنة بإكمال الأقساط حتى نهاية المدة.'});

        var data = {
            heroLabel:'مبلغ السداد المبكر المطلوب',
            heroValue: settle.total,
            heroSuffix:'',
            alerts:alerts,
            metrics:[
                {label:'أصل الدين المتبقي',value:fm(settle.principal),unit:'ر.س'},
                {label:'تعويض إعادة الاستثمار',value:fm(settle.compensation),unit:'ر.س',color:'var(--o)',sub:'أرباح 3 أشهر قادمة (حد ساما)'},
                {label:'تكاليف طرف ثالث',value:fm(settle.thirdParty),unit:'ر.س'},
                {label:'إجمالي لو أكملت الأقساط',value:fm(settle.totalIfContinue),unit:'ر.س',color:'var(--g600)'},
                {label:'التوفير بالسداد المبكر',value:fm(settle.savedVsContinue),unit:'ر.س',color:'var(--g)'}
            ],
            fees:[
                {label:'أصل الدين المتبقي',value:fm(settle.principal)+' ر.س'},
                {label:'تعويض إعادة الاستثمار ('+CFG.early_reinvest_months+' أشهر أرباح)',value:fm(settle.compensation)+' ر.س'},
                {label:'تكاليف طرف ثالث غير مستردة',value:fm(settle.thirdParty)+' ر.س'},
                {label:'إجمالي مبلغ السداد المبكر',value:fm(settle.total)+' ر.س',total:true}
            ],
            chart:{
                centerPct:((settle.compensation+settle.thirdParty)/settle.total*100).toFixed(1)+'%',
                centerLabel:'تكلفة السداد المبكر',
                segments:[
                    {label:'أصل الدين',value:settle.principal,color:'var(--c)'},
                    {label:'تعويض 3 أشهر',value:settle.compensation,color:'var(--o)'},
                    {label:'طرف ثالث',value:Math.max(settle.thirdParty,1),color:'var(--p)'}
                ]
            },
            tips: TIPS.early
        };

        renderResults($('[data-r="early"]'), data);
    }

    // Initial
    calc();

    })();
    </script>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'saod_calculator', 'saod_calc_shortcode' );
