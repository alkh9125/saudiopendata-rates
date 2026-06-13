<?php
/**
 * Plugin Name: حاسبة التمويل السعودية
 * Description: حاسبة شاملة للقروض الشخصية والعقارية وشراء المديونية والسداد المبكر
 * Version: 2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function saod_finance_enqueue() {
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        array(),
        '4.4.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'saod_finance_enqueue' );

function saod_finance_calc_shortcode() {
    ob_start();
?>

<!-- ===== INTRO — above calculator ===== -->
<div id="saod-intro" dir="rtl" style="font-family:'Segoe UI',Tahoma,Arial,sans-serif; max-width:960px; margin:0 auto 24px; color:#212529; line-height:1.8;">
  <h2 style="font-size:1.4rem; font-weight:900; color:#0f5132; margin-bottom:10px;">حاسبة التمويل المتكاملة</h2>
  <p style="font-size:1rem; color:#495057; margin-bottom:0;">
    أداة تقديرية تساعدك على فهم تكلفة القرض قبل التوجه للبنك. اختر نوع التمويل من التبويبات أدناه وأدخل بياناتك للحصول على تقدير فوري.
  </p>
</div>

<div id="saod-fc" dir="rtl">
<div class="wrap">

  <!-- TABS -->
  <div class="tabs" role="tablist">
    <button class="tab-btn active" data-tab="personal">قرض شخصي</button>
    <button class="tab-btn" data-tab="mortgage">قرض عقاري</button>
    <button class="tab-btn" data-tab="buyout">شراء مديونية</button>
    <button class="tab-btn" data-tab="early">سداد مبكر</button>
  </div>

  <div class="card">
    <div class="card-head" id="tab-title">القرض الشخصي</div>
    <div class="card-body" id="tab-body"></div>
  </div>

</div>
</div>

<style>
:root {
  --primary: #198754;
  --primary-dark: #0f5132;
  --primary-light: #d1e7dd;
  --accent: #d4af37;
  --accent-light: #fef9e7;
  --danger: #dc3545;
  --danger-light: #fff5f5;
  --warn: #e67e22;
  --bg: #f4f6f8;
  --surface: #ffffff;
  --border: #dee2e6;
  --text: #212529;
  --muted: #6c757d;
  --success-bg: #d1e7dd;
  --radius: 10px;
  --radius-lg: 14px;
}

#saod-fc * { box-sizing: border-box; margin: 0; padding: 0; }

#saod-fc {
  font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  padding: 12px;
  direction: rtl;
}

#saod-fc .wrap { max-width: 960px; margin: 0 auto; }

/* ── TABS — scrollable single row on mobile, no wrapping ── */
#saod-fc .tabs {
  display: flex;
  gap: 8px;
  flex-wrap: nowrap;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 8px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
#saod-fc .tabs::-webkit-scrollbar { display: none; }

#saod-fc .tab-btn {
  flex: 0 0 auto;
  padding: 10px 16px;
  border: 1px solid var(--border);
  background: #f8f9fa;
  border-radius: var(--radius);
  cursor: pointer;
  font-weight: 700;
  font-size: .9rem;
  color: #495057;
  transition: .15s;
  text-align: center;
  font-family: inherit;
  white-space: nowrap;
}
#saod-fc .tab-btn:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary-dark); }
#saod-fc .tab-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }

/* ── CARD ── */
#saod-fc .card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,.06);
}

#saod-fc .card-head {
  background: var(--primary);
  color: #fff;
  padding: 14px 16px;
  font-size: 1.15rem;
  font-weight: 900;
  text-align: center;
}

#saod-fc .card-body { padding: 16px; }
@media(min-width:600px) {
  #saod-fc .card-body { padding: 24px; }
  #saod-fc .card-head { padding: 18px 24px; font-size: 1.3rem; }
}

/* ── SECTION ── */
#saod-fc .sec {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px;
  margin-bottom: 12px;
  background: var(--surface);
}
@media(min-width:600px) { #saod-fc .sec { padding: 16px; margin-bottom: 14px; } }

#saod-fc .sec-title {
  font-size: .95rem;
  font-weight: 900;
  color: var(--primary-dark);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}
#saod-fc .badge {
  width: 24px; height: 24px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  font-size: .8rem;
  font-weight: 900;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── GRID — single column on mobile, expand at wider sizes ── */
#saod-fc .g2 { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 12px; }
#saod-fc .g3 { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 12px; }
#saod-fc .g4 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
@media(min-width:500px) {
  #saod-fc .g2 { grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
  #saod-fc .g3 { grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
  #saod-fc .g4 { grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
}
@media(min-width:760px) {
  #saod-fc .g3 { grid-template-columns: 1fr 1fr 1fr; }
  #saod-fc .g4 { grid-template-columns: repeat(4,1fr); }
}

/* ── FIELD ── */
#saod-fc .field label {
  display: block;
  font-size: .85rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 6px;
}
#saod-fc .input-wrap { position: relative; }

#saod-fc .input-wrap input,
#saod-fc .input-wrap select {
  width: 100%;
  height: 46px;
  padding: 0 12px 0 44px;
  border: 1px solid #ced4da;
  border-radius: var(--radius);
  font-size: 16px;
  font-family: inherit;
  color: var(--text);
  background: #fff;
  outline: none;
  transition: border .15s;
}
@media(min-width:600px) {
  #saod-fc .input-wrap input,
  #saod-fc .input-wrap select { height: 42px; font-size: 1rem; }
}
#saod-fc .input-wrap input:focus,
#saod-fc .input-wrap select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(25,135,84,.1);
}
#saod-fc .sfx {
  position: absolute;
  left: 12px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: .82rem;
  font-weight: 700;
  pointer-events: none;
}

#saod-fc input.ro,
#saod-fc input[readonly] {
  background: #f8f9fa !important;
  color: var(--primary);
  font-weight: 800;
}
#saod-fc .note { font-size: .78rem; color: var(--muted); margin-top: 5px; }
#saod-fc .note.red { color: var(--danger); font-weight: 700; }
#saod-fc .note.warn { color: var(--warn); font-weight: 700; }
#saod-fc .note.green { color: var(--primary); font-weight: 700; }

/* ── TOGGLE — stack vertically on small screens ── */
#saod-fc .tog { display: flex; gap: 6px; flex-wrap: wrap; }
#saod-fc .tog button {
  flex: 1 1 auto;
  min-width: 0;
  height: 46px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: #f8f9fa;
  cursor: pointer;
  font-weight: 700;
  font-size: .85rem;
  font-family: inherit;
  color: #495057;
  transition: .15s;
  padding: 0 10px;
  white-space: nowrap;
}
@media(max-width:420px) {
  #saod-fc .tog { flex-direction: column; gap: 6px; }
  #saod-fc .tog button { width: 100%; height: 44px; }
}
@media(min-width:600px) { #saod-fc .tog button { height: 42px; font-size: .88rem; } }
#saod-fc .tog button.on { background: var(--primary); border-color: var(--primary); color: #fff; }

/* ── BANNER ── */
#saod-fc .banner {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  border-radius: var(--radius-lg);
  padding: 14px 12px;
  color: #fff;
  text-align: center;
  margin: 12px 0;
}
#saod-fc .banner .big { font-size: 1.6rem; font-weight: 900; color: #ffeb3b; margin: 6px 0 4px; }
@media(min-width:500px) { #saod-fc .banner .big { font-size: 1.9rem; } }
#saod-fc .banner small { opacity: .88; font-size: .82rem; }

/* ── RESULTS GRID ── */
#saod-fc .results {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  padding: 10px;
  background: #f0fdf4;
  border: 1px solid #c3e6cb;
  border-radius: var(--radius-lg);
  margin-top: 12px;
}
@media(min-width:500px) {
  #saod-fc .results { grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: 10px; padding: 14px; }
}
#saod-fc .rbox {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 8px;
  text-align: center;
}
@media(min-width:500px) { #saod-fc .rbox { padding: 12px; } }
#saod-fc .rbox .lbl { font-size: .72rem; color: var(--muted); font-weight: 700; }
#saod-fc .rbox .val { font-size: 1.05rem; font-weight: 900; margin-top: 4px; color: var(--text); }
@media(min-width:500px) { #saod-fc .rbox .val { font-size: 1.2rem; } }
#saod-fc .rbox .val.g { color: var(--primary); }
#saod-fc .rbox .val.a { color: var(--accent); }
#saod-fc .rbox .val.r { color: var(--danger); }
#saod-fc .rbox .val.p { color: #d63384; }

/* ── CASH TABLE ── */
#saod-fc .cf-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
#saod-fc .cf-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; }
#saod-fc .cf-table tr:last-child td { border-bottom: none; }
#saod-fc .cf-table .neg { color: var(--danger); font-weight: 700; }
#saod-fc .cf-table .pos { color: var(--primary); font-weight: 700; }
#saod-fc .cf-table .total-row td { background: #f8f9fa; font-weight: 800; }
#saod-fc .cf-table .final-row td { background: var(--primary); color: #fff; font-weight: 900; border-radius: 0; }
#saod-fc .cf-table .final-row.def td { background: var(--danger); }
#saod-fc .cf-table .short-row { background: #fff5f5; display: none; }

/* ── SCHEDULE TABLE ── */
#saod-fc .tbl-wrap { overflow-x: auto; margin-top: 14px; -webkit-overflow-scrolling: touch; }
table.sched { width: 100%; border-collapse: collapse; font-size: .85rem; min-width: 420px; }
table.sched th, table.sched td { padding: 8px 10px; border-bottom: 1px solid #eee; text-align: center; }
table.sched thead th { background: var(--primary); color: #fff; font-weight: 700; }

/* ── BRIDGE BOX ── */
#saod-fc .bridge-box {
  margin-top: 10px;
  border: 1px solid #c3e6cb;
  background: #f0fdf4;
  border-radius: var(--radius);
  overflow: hidden;
}
#saod-fc .bridge-head {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 12px 14px;
  cursor: pointer;
  font-weight: 800;
  font-size: .9rem;
  color: var(--primary-dark);
  user-select: none;
}
#saod-fc .bridge-body { display: none; padding: 14px; border-top: 1px dashed #a7f3d0; }

/* ── RANGE ── */
#saod-fc input[type=range] { width: 100%; accent-color: var(--accent); cursor: pointer; }

/* ── BTN ── */
#saod-fc .btn {
  width: 100%;
  padding: 14px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  font-size: 1rem;
  font-weight: 800;
  cursor: pointer;
  font-family: inherit;
  margin-top: 12px;
  transition: background .15s;
  min-height: 48px;
}
#saod-fc .btn:hover { background: var(--primary-dark); }
#saod-fc .btn.outline {
  background: transparent;
  border: 1px solid var(--primary);
  color: var(--primary);
}
#saod-fc .btn.outline:hover { background: var(--primary-light); }

/* ── CHART ── */
#saod-fc .chart-wrap { position: relative; height: 240px; margin: 12px 0; }
@media(min-width:600px) { #saod-fc .chart-wrap { height: 320px; } }

/* ── HIDDEN ── */
#saod-fc .hidden { display: none !important; }

/* ── TIMELINE ── */
#saod-fc .tl-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: .8rem; }
#saod-fc .tl-yr { width: 46px; font-weight: 800; color: var(--muted); flex-shrink: 0; }
#saod-fc .tl-bars { flex: 1; height: 18px; background: #eee; border-radius: 4px; overflow: hidden; display: flex; }
#saod-fc .tl-m { background: var(--primary); }
#saod-fc .tl-p { background: #d63384; }
#saod-fc .tl-tot { font-weight: 800; min-width: 64px; text-align: left; font-size: .78rem; }

/* ── WARN BOX ── */
#saod-fc .warn-box {
  background: var(--accent-light);
  border: 1px solid #f0c040;
  border-radius: var(--radius);
  padding: 11px 13px;
  font-size: .84rem;
  margin-top: 10px;
  color: #6d4c00;
  line-height: 1.6;
}

/* ── EARLY PAYOFF ── */
#saod-fc .ep-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: .88rem; }
#saod-fc .ep-row:last-child { border-bottom: none; }
#saod-fc .ep-row .lbl { color: var(--muted); }
#saod-fc .ep-row .v { font-weight: 800; }
#saod-fc .ep-row .v.g { color: var(--primary); }
#saod-fc .ep-row .v.r { color: var(--danger); }
</style>

<script>
(function() {
    function initCalc() {
// ══════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════
const N = (id) => {
  const el = document.getElementById(id);
  if (!el) return 0;
  return parseFloat(String(el.value).replace(/,/g, '')) || 0;
};
const S = (id, v) => {
  const el = document.getElementById(id);
  if (el) el.textContent = fmt(v);
};
const fmt = (n) => Math.round(Number(n) || 0).toLocaleString('en-US');
const fmtDec = (n, d=2) => (Number(n)||0).toLocaleString('en-US', {minimumFractionDigits:d, maximumFractionDigits:d});
const PMT = (r, n, pv) => { if(n<=0) return 0; if(r===0) return pv/n; return r*pv/(1-Math.pow(1+r,-n)); };
const PV  = (r, n, p)  => { if(n<=0) return 0; if(r===0) return p*n; return p*(1-Math.pow(1+r,-n))/r; };

// SAMA regulatory constants
const SAMA = {
  admin_personal_pct: 0.5,
  admin_personal_cap: 2500,
  admin_mortgage_pct: 1.0,
  admin_mortgage_cap: 5000,
  vat_pct: 15,
  dbr_employee: 1/3,
  dbr_retiree: 0.25,
  dbr_total_incl_re: 0.65,
  rett_pct: 5,
  rett_first_home_exempt: 1000000,
  early_reinvest_months: 3,
  early_prohib_years: 2,
  sakani_low_salary: 10000,
  sakani_low_amount: 150000,
  sakani_high_amount: 100000
};

function adminFee(amount, isMortgage) {
  var pct = isMortgage ? SAMA.admin_mortgage_pct : SAMA.admin_personal_pct;
  var cap = isMortgage ? SAMA.admin_mortgage_cap : SAMA.admin_personal_cap;
  var fee = Math.min(amount * pct / 100, cap);
  var vat = fee * SAMA.vat_pct / 100;
  return { fee: fee, vat: vat, total: fee + vat };
}

function flatToApr(flatPct, years) {
  var n = years * 12, f = flatPct/100, P = 1e5;
  var ti = P * f * years, emi = (P + ti) / n;
  var lo = 1e-9, hi = 0.5;
  for (var i=0;i<100;i++) {
    var m = (lo+hi)/2;
    var pv = m<1e-9 ? emi*n : emi*(1-Math.pow(1+m,-n))/m;
    if (pv > P) lo = m; else hi = m;
  }
  return Math.round((lo+hi)/2*12*1e4)/100;
}

function aprToFlat(aprPct, years) {
  var n = years*12, r = aprPct/100/12, P = 1e5;
  var emi = r<1e-9 ? P/n : P*r/(1-Math.pow(1+r,-n));
  return Math.round((emi*n-P)/(P*years)*1e4)/100;
}

function trueApr(netProceeds, emi, n) {
  var lo=0, hi=1;
  for(var i=0;i<100;i++){
    var m=(lo+hi)/2;
    var pv = m<1e-9 ? emi*n : emi*(1-Math.pow(1+m,-n))/m;
    if(pv>netProceeds) lo=m; else hi=m;
  }
  return Math.round((lo+hi)/2*12*1e4)/100;
}

function earlySettleSAMA(balance, aprPct, remainMonths) {
  var r = aprPct/100/12;
  var comp = 0;
  var tempBal = balance;
  var emi = PMT(r, remainMonths, balance);
  for(var i=0; i<Math.min(SAMA.early_reinvest_months, remainMonths); i++){
    comp += tempBal * r;
    tempBal -= (emi - tempBal*r);
  }
  return comp;
}

function setFmt(inp) {
  inp.addEventListener('input', () => {
    const raw = inp.value.replace(/,/g,'').replace(/[^\d.]/g,'');
    if(!raw) return;
    const num = parseFloat(raw);
    if(isFinite(num)) inp.value = num.toLocaleString('en-US');
  });
}

function togGroup(groupId, stateObj, key, cb) {
  const el = document.getElementById(groupId);
  if (!el) return;
  el.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', () => {
      el.querySelectorAll('button').forEach(b => b.classList.remove('on'));
      btn.classList.add('on');
      stateObj[key] = btn.dataset.v;
      if(cb) cb();
    });
  });
}

// ══════════════════════════════════════════════════════════
// TABS ROUTING
// ══════════════════════════════════════════════════════════
const titles = { personal:'القرض الشخصي', mortgage:'القرض العقاري', buyout:'شراء المديونية', early:'السداد المبكر' };
let activeTab = 'personal';
let chartInst = null;

document.querySelectorAll('#saod-fc .tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#saod-fc .tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeTab = btn.dataset.tab;
    document.querySelector('#saod-fc #tab-title').textContent = titles[activeTab];
    renderTab(activeTab);
  });
});

renderTab('personal');

function renderTab(tab) {
  if(chartInst) { chartInst.destroy(); chartInst = null; }
  const body = document.querySelector('#saod-fc #tab-body');
  if(tab === 'personal') { body.innerHTML = personalHTML(); mountPersonal(); }
  else if(tab === 'mortgage') { body.innerHTML = mortgageHTML(); mountMortgage(); }
  else if(tab === 'buyout') { body.innerHTML = buyoutHTML(); mountBuyout(); }
  else if(tab === 'early') { body.innerHTML = earlyHTML(); mountEarly(); }
}

// ══════════════════════════════════════════════════════════
// TAB 1 — PERSONAL LOAN
// ══════════════════════════════════════════════════════════
function personalHTML() {
  return `
<div class="sec">
  <div class="sec-title"><span class="badge">1</span> الملف المالي</div>
  <div class="g3">
    <div class="field"><label>الراتب الشهري</label><div class="input-wrap"><input id="p_sal" class="pfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الالتزامات الحالية</label><div class="input-wrap"><input id="p_liab" class="pfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الحالة الوظيفية</label>
      <div class="tog" id="ptog_sec">
        <button class="on" data-v="emp">موظف (33%)</button>
        <button data-v="ret">متقاعد (25%)</button>
      </div>
    </div>
  </div>
</div>

<div class="sec">
  <div class="sec-title"><span class="badge">2</span> تفاصيل القرض</div>
  <div class="g3">
    <div class="field">
      <label>نوع النسبة</label>
      <div class="tog" id="ptog_rt">
        <button class="on" data-v="flat">Flat (إسترشادي)</button>
        <button data-v="apr">APR</button>
      </div>
      <div class="note red" id="p_rate_note">تنبيه: Flat استرشادي قد يختلف عن عقد البنك</div>
    </div>
    <div class="field"><label>نسبة الهامش / الفائدة %</label><div class="input-wrap"><input id="p_rate" type="number" value="2.5" step="0.01"><span class="sfx">%</span></div>
      <div class="note" id="p_conv_note" style="color:var(--primary)"></div>
    </div>
    <div class="field"><label>مدة القرض</label>
      <div class="input-wrap">
        <select id="p_years">
          <option value="1">1 سنة</option><option value="2">2 سنوات</option>
          <option value="3">3 سنوات</option><option value="4">4 سنوات</option>
          <option value="5" selected>5 سنوات</option>
        </select>
      </div>
    </div>
  </div>
  <div class="g2">
    <div class="field">
      <label>المبلغ المطلوب (صافي في حسابك)</label>
      <div class="input-wrap"><input id="p_amt" class="pfmt" placeholder="أدخل المبلغ"><span class="sfx">ر.س</span></div>
      <div class="note green" id="p_max_note">أقصى مبلغ متاح: —</div>
    </div>
    <div class="field">
      <label>الحد الأقصى التقديري</label>
      <div class="input-wrap"><input id="p_max_disp" class="ro" readonly><span class="sfx">ر.س</span></div>
      <div class="note">بناءً على الراتب والالتزامات</div>
    </div>
  </div>
</div>

<div class="results" id="p_results">
  <div class="rbox"><div class="lbl">القسط الشهري</div><div class="val g" id="p_emi">—</div></div>
  <div class="rbox"><div class="lbl">رسوم + ضريبة</div><div class="val warn" id="p_fees" style="color:var(--warn)">—</div><small class="note">تُخصم من الأصل</small></div>
  <div class="rbox"><div class="lbl">الأصل بالعقد</div><div class="val" id="p_gross">—</div></div>
  <div class="rbox"><div class="lbl">صافي في حسابك</div><div class="val g" id="p_net">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي الفوائد</div><div class="val r" id="p_int">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي السداد</div><div class="val" id="p_total">—</div></div>
  <div class="rbox"><div class="lbl">APR الفعلي (شامل الرسوم)</div><div class="val r" id="p_true_apr">—</div></div>
</div>

<button class="btn" id="p_detail_btn">عرض جدول السداد والرسم البياني</button>

<div id="p_detail_sec" class="hidden">
  <div class="chart-wrap"><canvas id="p_chart"></canvas></div>
  <div class="warn-box hidden" id="p_flat_warn">
    <strong>تنبيه:</strong> اخترت Flat — الأرقام استرشادية. للدقة اطلب من البنك نسبة APR واستخدمها.
  </div>
  <div style="display:flex;gap:10px;margin-top:10px;">
    <button class="btn outline" style="margin-top:0" id="p_tbl_btn">عرض / إخفاء الجدول</button>
    <button class="btn outline" style="margin-top:0" id="p_csv_btn">تحميل CSV</button>
  </div>
  <div id="p_tbl_wrap" class="tbl-wrap hidden">
    <table class="sched" id="p_tbl">
      <thead><tr><th>الشهر</th><th>القسط</th><th>الربح</th><th>الأصل</th><th>الرصيد</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>
`;
}

function mountPersonal() {
  const st = { sec:'emp', rt:'flat' };
  let schedule = [];

  document.querySelectorAll('#p_results .rbox').forEach(b => { b.querySelector('.val').textContent = '0'; });
  document.querySelectorAll('#tab-body .pfmt').forEach(setFmt);

  togGroup('ptog_sec', st, 'sec', calc);
  togGroup('ptog_rt', st, 'rt', () => {
    const n = document.getElementById('p_rate_note');
    const r = document.getElementById('p_rate');
    if(st.rt === 'flat') { n.classList.remove('hidden'); r.value = '2.5'; }
    else { n.classList.add('hidden'); r.value = '4.5'; }
    calc();
  });

  ['p_sal','p_liab','p_amt','p_rate','p_years'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.addEventListener('input', calc);
    if(el) el.addEventListener('change', calc);
  });

  document.getElementById('p_detail_btn').addEventListener('click', () => {
    const sec = document.getElementById('p_detail_sec');
    sec.classList.remove('hidden');
    if(schedule.length) drawPersonalChart(schedule);
    if(schedule.length) drawPersonalTable(schedule);
    sec.scrollIntoView({behavior:'smooth'});
  });
  document.getElementById('p_tbl_btn').addEventListener('click', () => {
    document.getElementById('p_tbl_wrap').classList.toggle('hidden');
  });
  document.getElementById('p_csv_btn').addEventListener('click', () => exportCSV(schedule, 'قرض_شخصي'));

  function calc() {
    const sal    = N('p_sal');
    const liab   = N('p_liab');
    const rate   = parseFloat(document.getElementById('p_rate').value) || 0;
    const yrs    = parseInt(document.getElementById('p_years').value);
    const reqNet = N('p_amt');

    const dbr    = st.sec === 'emp' ? SAMA.dbr_employee : SAMA.dbr_retiree;
    const maxEmi = Math.max(0, sal * dbr - liab);
    const months = yrs * 12;

    // Rate conversion display
    const convNote = document.getElementById('p_conv_note');
    if(convNote && rate > 0) {
      if(st.rt === 'flat') convNote.textContent = 'يعادل APR ≈ ' + flatToApr(rate, yrs) + '%';
      else convNote.textContent = 'يعادل Flat ≈ ' + aprToFlat(rate, yrs) + '%';
    }

    let maxGross = 0;
    if(maxEmi > 0) {
      if(st.rt === 'flat') maxGross = (maxEmi * months) / (1 + (rate/100) * yrs);
      else maxGross = PV(rate/12/100, months, maxEmi);
    }
    const fMax    = adminFee(maxGross, false);
    const maxNet  = Math.max(0, maxGross - fMax.total);
    document.getElementById('p_max_disp').value = fmt(maxNet);
    const noteEl = document.getElementById('p_max_note');
    noteEl.textContent = `أقصى مبلغ متاح: ${fmt(maxNet)} ر.س`;
    noteEl.className = reqNet > maxNet && maxNet > 0 ? 'note red' : 'note green';
    if(reqNet > maxNet && maxNet > 0) noteEl.textContent += ' (تجاوزت الحد!)';

    if(!reqNet) return;

    let gross = reqNet / (1 - SAMA.admin_personal_pct/100 * (1 + SAMA.vat_pct/100));
    const feesCheck = adminFee(gross, false);
    if(feesCheck.fee >= SAMA.admin_personal_cap) gross = reqNet + SAMA.admin_personal_cap * (1 + SAMA.vat_pct/100);
    const fees = adminFee(gross, false);

    let emi = 0, totalInt = 0;
    let aprForCalc = rate;
    if(st.rt === 'flat') {
      totalInt = gross * (rate/100) * yrs;
      emi = (gross + totalInt) / months;
      aprForCalc = flatToApr(rate, yrs);
    } else {
      const mr = rate / 12 / 100;
      emi = PMT(mr, months, gross);
      totalInt = Math.max(0, emi * months - gross);
    }
    const totalPay = gross + totalInt;

    // True APR including fees
    const netProceeds = gross - fees.total;
    const effectiveApr = trueApr(netProceeds, emi, months);

    schedule = buildSchedule(gross, emi, rate, months, st.rt, totalInt);

    S('p_emi',   emi);
    S('p_fees',  fees.total);
    S('p_gross', gross);
    S('p_net',   reqNet);
    S('p_int',   totalInt);
    S('p_total', totalPay);
    document.getElementById('p_true_apr').textContent = fmtDec(effectiveApr) + '%';

    const fw = document.getElementById('p_flat_warn');
    if(fw) { if(st.rt === 'flat') fw.classList.remove('hidden'); else fw.classList.add('hidden'); }

    const sec = document.getElementById('p_detail_sec');
    if(sec && !sec.classList.contains('hidden')) {
      drawPersonalChart(schedule);
      drawPersonalTable(schedule);
    }
  }

  function drawPersonalChart(sch) {
    const ctx = document.getElementById('p_chart');
    if(!ctx) return;
    if(chartInst) { chartInst.destroy(); chartInst = null; }
    const yData = [];
    let yr = {y:1, p:0, i:0};
    sch.forEach(r => {
      yr.p += r.prin; yr.i += r.int;
      if(r.m % 12 === 0 || r.m === sch.length) { yData.push({...yr}); yr = {y:yr.y+1, p:0, i:0}; }
    });
    chartInst = new Chart(ctx, {
      type:'bar',
      data:{
        labels: yData.map(d => `سنة ${d.y}`),
        datasets:[
          { label:'سداد الأصل', data: yData.map(d=>Math.round(d.p)), backgroundColor:'#198754', stack:'s' },
          { label:'الفائدة',    data: yData.map(d=>Math.round(d.i)), backgroundColor:'#dc3545', stack:'s' }
        ]
      },
      options:{
        responsive:true, maintainAspectRatio:false,
        scales:{ x:{grid:{display:false}}, y:{display:false, stacked:true} },
        plugins:{ legend:{position:'top'} }
      }
    });
  }

  function drawPersonalTable(sch) {
    const tbody = document.querySelector('#p_tbl tbody');
    if(!tbody) return;
    tbody.innerHTML = sch.map(r => `<tr><td>${r.m}</td><td>${fmt(r.emi)}</td><td>${fmt(r.int)}</td><td>${fmt(r.prin)}</td><td>${fmt(r.bal)}</td></tr>`).join('');
  }

  calc();
}

function buildSchedule(principal, emi, rate, months, mode, flatTotalInt) {
  const data = [];
  let bal = principal;
  const mr    = rate / 12 / 100;
  const flatMI = flatTotalInt / months;
  for(let i = 1; i <= months; i++) {
    const intP = mode === 'apr' ? (bal * mr) : flatMI;
    if(i === months) {
      data.push({ m:i, emi: bal + intP, int: intP, prin: bal, bal: 0 });
    } else {
      const prinP = Math.min(emi - intP, bal);
      bal = Math.max(0, bal - prinP);
      data.push({ m:i, emi, int:intP, prin:prinP, bal });
    }
  }
  return data;
}

function exportCSV(data, name) {
  if(!data.length) { alert('احسب أولاً'); return; }
  let csv = '﻿الشهر,القسط,الفائدة,الأصل,الرصيد\n';
  data.forEach(r => csv += `${r.m},${r.emi.toFixed(2)},${r.int.toFixed(2)},${r.prin.toFixed(2)},${r.bal.toFixed(2)}\n`);
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8;'}));
  a.download = name + '.csv';
  a.click();
}

// ══════════════════════════════════════════════════════════
// TAB 2 — MORTGAGE
// ══════════════════════════════════════════════════════════
function mortgageHTML() {
  return `
<div class="sec">
  <div class="sec-title"><span class="badge">1</span> الملف المالي</div>
  <div class="g3">
    <div class="field"><label>الراتب الصافي</label><div class="input-wrap"><input id="m_sal" class="mfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الالتزامات</label><div class="input-wrap"><input id="m_liab" class="mfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الحالة الوظيفية</label>
      <div class="tog" id="mtog_sec"><button class="on" data-v="emp">موظف</button><button data-v="ret">متقاعد</button></div>
    </div>
  </div>
  <div class="g3">
    <div class="field"><label>نوع المسكن</label>
      <div class="tog" id="mtog_home"><button class="on" data-v="first">مسكن أول (10%)</button><button data-v="second">مسكن ثاني (30%)</button></div>
    </div>
    <div class="field"><label>الدعم السكني (سكني)</label>
      <div class="tog" id="mtog_sub"><button class="on" data-v="yes">مدعوم</button><button data-v="no">غير مدعوم</button></div>
    </div>
    <div class="field"><label>دعم الدفعة (تقديري)</label>
      <div class="input-wrap"><input id="m_supp" class="ro" readonly value="0"><span class="sfx">ر.س</span></div>
      <div class="note" id="m_supp_note">يُحسب تلقائياً حسب الراتب — أدخل راتبك أولاً</div>
    </div>
  </div>
</div>

<div class="sec">
  <div class="sec-title"><span class="badge">2</span> التمويل</div>
  <div class="g3">
    <div class="field"><label>مدة العقاري (سنة)</label><input id="m_yrs" type="number" value="20" min="5" max="30" style="height:42px;width:100%;padding:0 12px;border:1px solid #ced4da;border-radius:var(--radius);font-size:1rem;font-family:inherit;outline:none;"></div>
    <div class="field"><label>نسبة الهامش / الفائدة %</label><div class="input-wrap"><input id="m_rate" type="number" value="4.0" step="0.1"><span class="sfx">%</span></div>
      <div class="note" id="m_conv_note" style="color:var(--primary)"></div>
    </div>
    <div class="field"><label>نوع النسبة</label>
      <div class="tog" id="mtog_rt"><button class="on" data-v="flat">Flat</button><button data-v="apr">APR</button></div>
      <div class="note red" id="m_rate_note">Flat استرشادي — APR أدق</div>
    </div>
  </div>

  <div class="bridge-box">
    <div class="bridge-head" id="bridge_head">
      <span>قرض شخصي للدفعة (دمج قرضين)</span>
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" id="bridge_chk" style="width:18px;height:18px;accent-color:var(--primary);">
        <span>تفعيل</span>
      </label>
    </div>
    <div class="bridge-body" id="bridge_body">
      <div class="g3">
        <div class="field"><label>مدة الشخصي</label>
          <select id="p2_yrs" style="height:42px;width:100%;padding:0 12px;border:1px solid #ced4da;border-radius:var(--radius);font-size:1rem;font-family:inherit;">
            <option value="5">5 سنوات</option><option value="4">4 سنوات</option>
            <option value="3">3 سنوات</option><option value="2">2 سنة</option><option value="1">1 سنة</option>
          </select>
        </div>
        <div class="field"><label>هامش الشخصي %</label><div class="input-wrap"><input id="p2_rate" type="number" value="2.5" step="0.1"><span class="sfx">%</span></div></div>
        <div class="field"><label>الصافي المتاح</label><div class="input-wrap"><input id="p2_net" class="ro" readonly value="0"><span class="sfx">ر.س</span></div></div>
      </div>
      <div class="field">
        <label>نسبة استخدام سيولة الشخصي للدفعة (<span id="p2_pct">100%</span>)</label>
        <div class="g2" style="align-items:center;margin-bottom:0;">
          <input type="range" id="p2_slider" min="0" max="100" value="100">
          <div class="input-wrap"><input id="p2_used" class="ro" readonly value="0"><span class="sfx">ر.س</span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="banner">
  <small id="max_lbl">الحد الأقصى للتمويل العقاري (تقديري)</small>
  <div class="big" id="m_max">0</div>
  <small>يتغير حسب الراتب والمدة والنسبة</small>
</div>

<div class="sec">
  <div class="sec-title"><span class="badge">3</span> العقار وتحليل الكاش</div>
  <div class="g3">
    <div class="field">
      <label style="color:var(--primary);font-size:1rem;">قيمة العقار</label>
      <div class="input-wrap"><input id="m_price" class="mfmt" placeholder="0" style="font-weight:900;color:var(--primary);font-size:1.1rem;"><span class="sfx">ر.س</span></div>
    </div>
  </div>
  <div class="g3">
    <div class="field"><label>رسوم السعي</label>
      <div class="tog" id="mtog_sai"><button class="on" data-v="pct">نسبة</button><button data-v="fix">مبلغ</button><button data-v="no">بدون</button></div>
    </div>
    <div class="field"><label>قيمة السعي</label><div class="input-wrap"><input id="m_sai_v" value="2.5"><span class="sfx" id="m_sai_sfx">%</span></div></div>
    <div class="field"><label>مدخراتك الشخصية</label><div class="input-wrap"><input id="m_save" class="mfmt" placeholder="0" style="border-color:var(--accent)"><span class="sfx">ر.س</span></div></div>
  </div>
  <div class="g2">
    <div class="field"><label>رسوم تقييم</label><div class="input-wrap"><input id="m_eval" class="mfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>رسوم أخرى</label><div class="input-wrap"><input id="m_other" class="mfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
  </div>

  <table class="cf-table">
    <tr><td>الدفعة المطلوبة (<span id="m_dp_lbl">10%</span>)</td><td class="neg" id="cf_dp">0</td></tr>
    <tr><td>ضريبة التصرفات العقارية</td><td class="neg" id="cf_tax">0</td></tr>
    <tr><td>رسوم السعي</td><td class="neg" id="cf_sai">0</td></tr>
    <tr><td>رسوم التقييم + أخرى</td><td class="neg" id="cf_eval">0</td></tr>
    <tr><td>رسوم إدارية + ضريبة (تقديري)</td><td class="neg" id="cf_admin">0</td></tr>
    <tr class="short-row" id="m_short_row"><td class="neg">فارق السعر (فوق الحد)</td><td class="neg" id="cf_short">0</td></tr>
    <tr class="total-row"><td><strong>إجمالي المطلوب نقداً</strong></td><td class="neg" id="cf_need">0</td></tr>
    <tr><td>دعم سكني</td><td class="pos" id="cf_supp">0</td></tr>
    <tr><td>صافي الشخصي المستخدم</td><td class="pos" id="cf_pused">0</td></tr>
    <tr><td>مدخرات شخصية</td><td class="pos" id="cf_save">0</td></tr>
    <tr class="total-row"><td><strong>إجمالي السيولة</strong></td><td class="pos" id="cf_avail">0</td></tr>
    <tr class="final-row" id="m_final_row"><td>الصافي النهائي (عجز / فائض)</td><td id="cf_res">0</td></tr>
  </table>
</div>

<div class="results">
  <div class="rbox"><div class="lbl">مبلغ التمويل</div><div class="val g" id="m_res_loan">0</div></div>
  <div class="rbox"><div class="lbl">القسط الشهري (تقديري)</div><div class="val g" id="m_res_emi1">0</div><div class="note" id="m_emi1_note">ثابت</div></div>
  <div class="rbox hidden" id="m_emi2_box"><div class="lbl">القسط بعد الشخصي</div><div class="val a" id="m_res_emi2">0</div></div>
  <div class="rbox hidden" id="m_p_emi_box"><div class="lbl">قسط الشخصي</div><div class="val p" id="m_res_p_emi">0</div></div>
  <div class="rbox"><div class="lbl">إجمالي فوائد العقاري</div><div class="val r" id="m_res_mint">0</div></div>
  <div class="rbox"><div class="lbl">إجمالي السداد</div><div class="val" id="m_res_mtotal">0</div></div>
  <div class="rbox"><div class="lbl">APR الفعلي (شامل الرسوم)</div><div class="val r" id="m_res_true_apr">—</div></div>
</div>

<button class="btn" id="m_detail_btn">عرض الجدول الزمني</button>
<div id="m_timeline" class="hidden" style="margin-top:14px;"></div>
`;
}

function mountMortgage() {
  const st = { sec:'emp', home:'first', sub:'yes', rt:'flat', sai:'pct' };
  let bridgeOn = false;
  let pNet = 0;

  document.querySelectorAll('#tab-body .mfmt').forEach(setFmt);

  togGroup('mtog_sec',  st, 'sec',  calc);
  togGroup('mtog_home', st, 'home', calc);
  togGroup('mtog_rt',   st, 'rt',   () => {
    const n = document.getElementById('m_rate_note');
    if(st.rt==='flat') { n.classList.remove('hidden'); }
    else { n.classList.add('hidden'); }
    calc();
  });
  togGroup('mtog_sai',  st, 'sai',  () => {
    const sfx = document.getElementById('m_sai_sfx');
    const inp = document.getElementById('m_sai_v');
    if(st.sai==='pct') { sfx.textContent='%'; inp.disabled=false; }
    else if(st.sai==='fix') { sfx.textContent='ر.س'; inp.disabled=false; }
    else { sfx.textContent=''; inp.disabled=true; inp.value='0'; }
    calc();
  });
  togGroup('mtog_sub', st, 'sub', calcSupport);

  ['m_sal','m_liab','m_yrs','m_rate','m_price','m_sai_v','m_eval','m_other','m_save','p2_yrs','p2_rate'].forEach(id => {
    const el = document.getElementById(id);
    if(el) { el.addEventListener('input', calc); el.addEventListener('change', calc); }
  });

  document.getElementById('bridge_chk').addEventListener('change', function() {
    bridgeOn = this.checked;
    document.getElementById('bridge_body').style.display = bridgeOn ? 'block' : 'none';
    document.getElementById('m_emi2_box').classList.toggle('hidden', !bridgeOn);
    document.getElementById('m_p_emi_box').classList.toggle('hidden', !bridgeOn);
    calc();
  });

  document.getElementById('p2_slider').addEventListener('input', function() {
    document.getElementById('p2_pct').textContent = this.value + '%';
    document.getElementById('p2_used').value = fmt(pNet * (this.value/100));
    calc();
  });

  document.getElementById('m_detail_btn').addEventListener('click', () => {
    document.getElementById('m_timeline').classList.remove('hidden');
    document.getElementById('m_timeline').scrollIntoView({behavior:'smooth'});
  });

  function calcSupport() {
    const sal = N('m_sal');
    let s = 0;
    if(st.sub === 'yes' && sal > 0) {
      s = sal < SAMA.sakani_low_salary ? SAMA.sakani_low_amount : SAMA.sakani_high_amount;
    }
    document.getElementById('m_supp').value = fmt(s);
    const noteEl = document.getElementById('m_supp_note');
    if(sal > 0 && st.sub === 'yes') {
      noteEl.textContent = sal < SAMA.sakani_low_salary
        ? 'راتبك أقل من ' + fmt(SAMA.sakani_low_salary) + ' → دعم ' + fmt(SAMA.sakani_low_amount) + ' ر.س'
        : 'راتبك ' + fmt(SAMA.sakani_low_salary) + ' أو أكثر → دعم ' + fmt(SAMA.sakani_high_amount) + ' ر.س';
      noteEl.className = 'note green';
    } else if(st.sub === 'no') {
      noteEl.textContent = 'غير مدعوم — بدون دعم سكني';
      noteEl.className = 'note';
    } else {
      noteEl.textContent = 'يُحسب تلقائياً حسب الراتب — أدخل راتبك أولاً';
      noteEl.className = 'note';
    }
    calc();
  }

  function calc() {
    const sal   = N('m_sal');
    const liab  = N('m_liab');
    const mYrs  = Math.max(5, Math.min(30, parseFloat(document.getElementById('m_yrs').value)||20));
    const mRate = (parseFloat(document.getElementById('m_rate').value)||0)/100;
    const mMo   = mYrs * 12;

    const supp  = N('m_supp');
    const save  = N('m_save');
    const price = N('m_price');

    // Rate conversion display
    const convNote = document.getElementById('m_conv_note');
    if(convNote && mRate > 0) {
      if(st.rt === 'flat') convNote.textContent = 'يعادل APR ≈ ' + flatToApr(mRate*100, mYrs) + '%';
      else convNote.textContent = 'يعادل Flat ≈ ' + aprToFlat(mRate*100, mYrs) + '%';
    }

    if(sal <= 0) return;

    const cap = Math.max(0, sal * SAMA.dbr_total_incl_re - liab - (bridgeOn ? calcPEmi() : 0));
    let maxLoan = 0;
    if(st.rt === 'flat') maxLoan = (cap * mMo) / (1 + mRate * mYrs);
    else maxLoan = PV(mRate/12, mMo, cap);
    document.getElementById('m_max').textContent = fmt(maxLoan);

    let p2Used = 0;
    if(bridgeOn) {
      const pEmi   = calcPEmi();
      const p2Yrs  = parseInt(document.getElementById('p2_yrs').value)||5;
      const p2Rate = (parseFloat(document.getElementById('p2_rate').value)||0)/100;
      const gross  = PV(p2Rate/12, p2Yrs*12, pEmi);
      const p2Fees = adminFee(gross, false);
      pNet = Math.max(0, gross - p2Fees.total);
      document.getElementById('p2_net').value = fmt(pNet);
      document.getElementById('m_res_p_emi').textContent = fmt(pEmi);
      p2Used = pNet * (parseInt(document.getElementById('p2_slider').value)||100) / 100;
      document.getElementById('p2_used').value = fmt(p2Used);
    } else { pNet = 0; }

    const dpPct = st.home === 'first' ? .10 : .30;
    document.getElementById('m_dp_lbl').textContent = Math.round(dpPct*100) + '%';
    const reqDP     = price * dpPct;
    const neededLoan = Math.max(0, price - reqDP);

    let funded = neededLoan, shortfall = 0;
    if(neededLoan > maxLoan) { funded = maxLoan; shortfall = neededLoan - maxLoan; }

    // RETT with first-home exemption
    let tax = 0;
    if(st.home === 'first') {
      var taxable = Math.max(0, price - SAMA.rett_first_home_exempt);
      tax = taxable * SAMA.rett_pct / 100;
    } else {
      tax = price * SAMA.rett_pct / 100;
    }

    let sai = 0;
    const sv = parseFloat(document.getElementById('m_sai_v').value)||0;
    if(st.sai==='pct') sai = price * (sv/100);
    else if(st.sai==='fix') sai = sv;

    const evalF  = N('m_eval');
    const otherF = N('m_other');
    const fees   = adminFee(funded, true);

    S('cf_dp',    reqDP);
    S('cf_tax',   tax);
    S('cf_sai',   sai);
    S('cf_eval',  evalF + otherF);
    S('cf_admin', fees.total);

    const shortRow = document.getElementById('m_short_row');
    if(shortfall > 0) { shortRow.style.display=''; S('cf_short', shortfall); }
    else shortRow.style.display = 'none';

    const needCash = reqDP + tax + sai + evalF + otherF + fees.total + shortfall;
    S('cf_need',  needCash);
    S('cf_supp',  supp);
    S('cf_pused', p2Used);
    S('cf_save',  save);
    const avail = supp + p2Used + save;
    S('cf_avail', avail);
    const res = avail - needCash;
    document.getElementById('cf_res').textContent = fmt(res);
    document.getElementById('m_final_row').className = 'final-row' + (res < 0 ? ' def' : '');

    let finalLoan = funded;
    if(res > 0) finalLoan = Math.max(0, funded - res);
    S('m_res_loan', finalLoan);

    let emi1 = 0, emi2 = 0;
    const r = mRate / 12;

    if(finalLoan > 0) {
      if(st.rt === 'flat') emi1 = finalLoan * (1 + mRate*mYrs) / mMo;
      else emi1 = PMT(r, mMo, finalLoan);
      emi2 = emi1;
    }

    document.getElementById('m_emi1_note').textContent = bridgeOn ? `أثناء الشخصي (${document.getElementById('p2_yrs').value} سنوات)` : 'ثابت';
    S('m_res_emi1', emi1);
    S('m_res_emi2', emi2);

    const mTotalPay = emi1 * mMo;
    const mInt = Math.max(0, mTotalPay - finalLoan);
    S('m_res_mint',   mInt);
    S('m_res_mtotal', mTotalPay);

    // True APR for mortgage
    var mTrueApr = 0;
    if(finalLoan > 0 && emi1 > 0) {
      var netMortgage = finalLoan - fees.total;
      var aprCalc = st.rt === 'flat' ? flatToApr(mRate*100, mYrs) : mRate*100;
      mTrueApr = trueApr(netMortgage, emi1, mMo);
    }
    document.getElementById('m_res_true_apr').textContent = fmtDec(mTrueApr) + '%';

    const pEmiShow = bridgeOn ? calcPEmi() : 0;
    renderTimeline(mYrs, bridgeOn ? parseInt(document.getElementById('p2_yrs').value)||5 : 0, pEmiShow, emi1, emi2);
  }

  function calcPEmi() {
    const sal  = N('m_sal');
    const liab = N('m_liab');
    const dbr  = st.sec === 'emp' ? SAMA.dbr_employee : SAMA.dbr_retiree;
    return Math.max(0, sal * dbr - liab);
  }

  function renderTimeline(mYrs, pYrs, pEmi, m1, m2) {
    const con = document.getElementById('m_timeline');
    if(!con) return;
    const max = Math.max(m1 + pEmi, m2) * 1.2 || 30000;
    let html = '';
    for(let y = 1; y <= mYrs; y++) {
      const isB = y <= pYrs && pEmi > 0;
      const cm  = isB ? m1 : m2;
      const cp  = isB ? pEmi : 0;
      const tot = cm + cp;
      const wM  = Math.min(60, (cm/max)*100);
      const wP  = Math.min(35, (cp/max)*100);
      html += `<div class="tl-row">
        <div class="tl-yr">سنة ${y}</div>
        <div class="tl-bars">
          <div class="tl-m" style="width:${wM}%"></div>
          <div class="tl-p" style="width:${wP}%"></div>
        </div>
        <div class="tl-tot">${fmt(tot)}</div>
      </div>`;
    }
    con.innerHTML = html;
  }

  calcSupport();
}

// ══════════════════════════════════════════════════════════
// TAB 3 — BUYOUT
// ══════════════════════════════════════════════════════════
function buyoutHTML() {
  return `
<div class="sec">
  <div class="sec-title"><span class="badge">1</span> القرض الحالي</div>
  <div class="g3">
    <div class="field"><label>الرصيد المتبقي (المديونية)</label><div class="input-wrap"><input id="bo_bal" class="bofmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>القسط الحالي</label><div class="input-wrap"><input id="bo_emi" class="bofmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>عدد الأشهر المتبقية</label><div class="input-wrap"><input id="bo_rem" type="number" value="48" min="1"><span class="sfx">شهر</span></div></div>
  </div>
</div>

<div class="sec">
  <div class="sec-title"><span class="badge">2</span> العرض الجديد</div>
  <div class="g3">
    <div class="field"><label>الراتب الشهري</label><div class="input-wrap"><input id="bo_sal" class="bofmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الالتزامات الأخرى</label><div class="input-wrap"><input id="bo_liab" class="bofmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>الحالة الوظيفية</label>
      <div class="tog" id="botog_sec"><button class="on" data-v="emp">موظف (33%)</button><button data-v="ret">متقاعد (25%)</button></div>
    </div>
  </div>
  <div class="g3">
    <div class="field">
      <label>نوع النسبة</label>
      <div class="tog" id="botog_rt"><button class="on" data-v="flat">Flat</button><button data-v="apr">APR</button></div>
    </div>
    <div class="field"><label>نسبة الجديد %</label><div class="input-wrap"><input id="bo_rate" type="number" value="2.5" step="0.01"><span class="sfx">%</span></div></div>
    <div class="field"><label>مدة الجديد</label>
      <select id="bo_yrs" style="height:42px;width:100%;padding:0 12px;border:1px solid #ced4da;border-radius:var(--radius);font-size:1rem;font-family:inherit;">
        <option value="1">1 سنة</option><option value="2">2 سنوات</option><option value="3">3 سنوات</option>
        <option value="4">4 سنوات</option><option value="5" selected>5 سنوات</option>
      </select>
    </div>
    <div class="field"><label>مبلغ إضافي (اختياري)</label><div class="input-wrap"><input id="bo_extra" class="bofmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
  </div>
</div>

<div class="results" id="bo_results">
  <div class="rbox"><div class="lbl">القسط الجديد</div><div class="val g" id="bo_new_emi">—</div></div>
  <div class="rbox"><div class="lbl">التوفير الشهري</div><div class="val g" id="bo_saving">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي الدفع القديم</div><div class="val r" id="bo_old_total">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي الدفع الجديد</div><div class="val" id="bo_new_total">—</div></div>
  <div class="rbox"><div class="lbl">الفرق الإجمالي</div><div class="val g" id="bo_diff">—</div></div>
  <div class="rbox"><div class="lbl">الحالة</div><div class="val g" id="bo_status">—</div></div>
</div>

<div id="bo_extra_note" class="hidden" style="margin-top:10px; border:1px solid #c3e6cb; background:#f0fdf4; border-radius:var(--radius); padding:12px 14px; font-size:.85rem; color:#0f5132; line-height:1.9;"></div>
<div id="bo_note" class="warn-box hidden" style="margin-top:8px;"></div>
`;
}

function mountBuyout() {
  const st = { sec: 'emp', rt: 'flat' };
  document.querySelectorAll('#tab-body .bofmt').forEach(setFmt);
  togGroup('botog_sec', st, 'sec', calc);
  togGroup('botog_rt',  st, 'rt',  calc);
  ['bo_bal','bo_emi','bo_rem','bo_sal','bo_liab','bo_rate','bo_yrs','bo_extra'].forEach(id => {
    const el = document.getElementById(id);
    if(el) { el.addEventListener('input', calc); el.addEventListener('change', calc); }
  });

  function calc() {
    const bal    = N('bo_bal');
    const oldEmi = N('bo_emi');
    const remMo  = parseInt(document.getElementById('bo_rem').value)||1;
    const rate   = (parseFloat(document.getElementById('bo_rate').value)||0)/100;
    const yrs    = parseInt(document.getElementById('bo_yrs').value)||5;
    const extra  = N('bo_extra');
    const sal    = N('bo_sal');
    const liab   = N('bo_liab');

    if(!bal) {
      ['bo_new_emi','bo_saving','bo_old_total','bo_new_total','bo_diff','bo_status'].forEach(id => S(id, 0));
      document.getElementById('bo_extra_note').classList.add('hidden');
      return;
    }

    const newPrincipal = bal + extra;
    const newMo = yrs * 12;
    const fees = adminFee(newPrincipal, false);
    let newEmi = 0;
    if(st.rt === 'flat') {
      newEmi = newPrincipal * (1 + rate * yrs) / newMo;
    } else {
      newEmi = PMT(rate/12, newMo, newPrincipal);
    }
    const oldTotal      = oldEmi * remMo;
    const newTotal      = newEmi * newMo;
    const monthlySaving = oldEmi - newEmi;
    const totalDiff     = oldTotal - newTotal - fees.total;

    S('bo_new_emi',   newEmi);
    S('bo_saving',    monthlySaving);
    S('bo_old_total', oldTotal);
    S('bo_new_total', newTotal + fees.total);
    S('bo_diff',      Math.abs(totalDiff));

    const statusEl = document.getElementById('bo_status');
    statusEl.textContent = totalDiff > 0 ? 'توفير' : totalDiff < 0 ? 'تكلفة إضافية' : 'متساوٍ';
    statusEl.className   = 'val ' + (totalDiff > 0 ? 'g' : 'r');

    const extraNoteEl = document.getElementById('bo_extra_note');
    if(extra > 0) {
      let baseEmi;
      if(st.rt === 'flat') baseEmi = bal * (1 + rate * yrs) / newMo;
      else baseEmi = PMT(rate/12, newMo, bal);
      const baseTotal     = baseEmi * newMo;
      const refinanceDiff = oldTotal - baseTotal - fees.total;
      const extraCost     = newTotal - baseTotal;
      extraNoteEl.classList.remove('hidden');
      extraNoteEl.innerHTML =
        `<strong>تحليل شفاف — المبلغ الإضافي (${fmt(extra)} ر.س) يُغيّر المقارنة:</strong><br>` +
        `▸ توفير إعادة تمويل الرصيد فقط: ` +
        `<strong style="color:${refinanceDiff>=0?'var(--primary)':'var(--danger)'};">${refinanceDiff>=0?'+':''}${fmt(refinanceDiff)} ر.س</strong><br>` +
        `▸ تكلفة المبلغ الإضافي على مدة ${yrs} سنوات: ` +
        `<strong style="color:var(--danger);">${fmt(extraCost)} ر.س</strong>`;
    } else {
      extraNoteEl.classList.add('hidden');
    }

    const noteEl   = document.getElementById('bo_note');
    const dbr      = st.sec === 'emp' ? SAMA.dbr_employee : SAMA.dbr_retiree;
    const maxAllowed = sal * dbr - liab;
    if(sal > 0 && newEmi > maxAllowed) {
      noteEl.classList.remove('hidden');
      noteEl.innerHTML = `<strong>تحذير:</strong> القسط الجديد (${fmt(newEmi)} ر.س) يتجاوز الحد المسموح (${fmt(maxAllowed)} ر.س) بناءً على راتبك.`;
    } else {
      noteEl.classList.add('hidden');
    }
  }
}

// ══════════════════════════════════════════════════════════
// TAB 4 — EARLY PAYOFF (SAMA method)
// ══════════════════════════════════════════════════════════
function earlyHTML() {
  return `
<div class="sec">
  <div class="sec-title"><span class="badge">1</span> بيانات القرض الأصلي</div>
  <div class="g3">
    <div class="field"><label>الرصيد المتبقي (أصل الدين)</label><div class="input-wrap"><input id="ep_bal" class="epfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>نسبة الفائدة % (APR)</label><div class="input-wrap"><input id="ep_rate" type="number" value="4.0" step="0.01"><span class="sfx">%</span></div></div>
    <div class="field"><label>المدة المتبقية (شهر)</label><div class="input-wrap"><input id="ep_rem" type="number" value="36" min="1"><span class="sfx">شهر</span></div></div>
  </div>
  <div class="g2">
    <div class="field"><label>تكاليف طرف ثالث غير مستردة (تأمين إلخ)</label><div class="input-wrap"><input id="ep_third" class="epfmt" placeholder="0"><span class="sfx">ر.س</span></div></div>
    <div class="field"><label>تمويل عقاري؟</label>
      <div class="tog" id="eptog_re"><button data-v="no" class="on">لا</button><button data-v="yes">نعم</button></div>
    </div>
  </div>
  <div class="g2" id="ep_prohib_row" style="display:none;">
    <div class="field"><label>هل أنت ضمن أول سنتين؟</label>
      <div class="tog" id="eptog_prohib"><button data-v="no" class="on">لا</button><button data-v="yes">نعم</button></div>
    </div>
  </div>
</div>

<div class="results" id="ep_results">
  <div class="rbox"><div class="lbl">الرصيد المتبقي</div><div class="val g" id="ep_balance">—</div></div>
  <div class="rbox"><div class="lbl">تعويض إعادة الاستثمار</div><div class="val r" id="ep_comp">—</div><small class="note">أرباح 3 أشهر (حد ساما)</small></div>
  <div class="rbox"><div class="lbl">تكاليف طرف ثالث</div><div class="val" id="ep_third_val">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي السداد المبكر</div><div class="val r" id="ep_payoff">—</div></div>
  <div class="rbox"><div class="lbl">إجمالي لو أكملت</div><div class="val" id="ep_wouldpay">—</div></div>
  <div class="rbox"><div class="lbl">التوفير بالسداد المبكر</div><div class="val g" id="ep_saved">—</div></div>
</div>

<div id="ep_note" class="warn-box hidden" style="margin-top:14px;"></div>
`;
}

function mountEarly() {
  const st = { re: 'no', prohib: 'no' };
  document.querySelectorAll('#tab-body .epfmt').forEach(setFmt);
  togGroup('eptog_re', st, 're', () => {
    document.getElementById('ep_prohib_row').style.display = st.re === 'yes' ? '' : 'none';
    calc();
  });
  togGroup('eptog_prohib', st, 'prohib', calc);
  ['ep_bal','ep_rate','ep_rem','ep_third'].forEach(id => {
    const el = document.getElementById(id);
    if(el) { el.addEventListener('input', calc); el.addEventListener('change', calc); }
  });

  function calc() {
    const bal     = N('ep_bal');
    const rate    = parseFloat(document.getElementById('ep_rate').value) || 0;
    const remMo   = parseInt(document.getElementById('ep_rem').value) || 1;
    const thirdP  = N('ep_third');
    const isRE    = st.re === 'yes';
    const prohib  = isRE && st.prohib === 'yes';

    if(!bal || !rate) {
      ['ep_balance','ep_comp','ep_third_val','ep_payoff','ep_wouldpay','ep_saved'].forEach(id => S(id, 0));
      return;
    }

    // SAMA method: compensation = interest for next 3 months (reinvestment cap)
    const comp = earlySettleSAMA(bal, rate, remMo);
    const emi = PMT(rate/100/12, remMo, bal);
    const wouldPay = emi * remMo;
    const payoff = bal + comp + thirdP;
    const saved = wouldPay - payoff;

    S('ep_balance',   bal);
    S('ep_comp',      comp);
    S('ep_third_val', thirdP);
    S('ep_payoff',    payoff);
    S('ep_wouldpay',  wouldPay);
    S('ep_saved',     saved);

    const noteEl = document.getElementById('ep_note');
    if(prohib) {
      noteEl.classList.remove('hidden');
      noteEl.style.borderColor = '#fecaca';
      noteEl.style.background  = '#fff5f5';
      noteEl.style.color       = '#991b1b';
      noteEl.innerHTML = `<strong>تحذير:</strong> أنت ضمن فترة حظر السداد المبكر (أول سنتين للتمويل العقاري). قد يرفض البنك الطلب أو يفرض شروطاً إضافية. مرجع: ساما`;
    } else if(saved > 0) {
      noteEl.classList.remove('hidden');
      noteEl.style.borderColor = '#c3e6cb';
      noteEl.style.background  = '#f0fdf4';
      noteEl.style.color       = 'var(--primary-dark)';
      noteEl.innerHTML = `<strong>ممتاز:</strong> السداد المبكر الآن يوفر لك <strong>${fmt(saved)} ر.س</strong> مقارنة بإكمال الأقساط. تعويض إعادة الاستثمار محدد بأرباح ${SAMA.early_reinvest_months} أشهر فقط (نظام ساما).`;
    } else {
      noteEl.classList.add('hidden');
    }
  }
}
    }
    // Wait for Chart.js to load
    function waitForChart(tries) {
        if (typeof Chart !== 'undefined') {
            initCalc();
        } else if (tries > 0) {
            setTimeout(function() { waitForChart(tries - 1); }, 200);
        }
    }
    waitForChart(20);
})();
</script>

<!-- ===== DYNAMIC TIPS — below calculator ===== -->
<div id="saod-tips" dir="rtl" style="font-family:'Segoe UI',Tahoma,Arial,sans-serif; max-width:960px; margin:24px auto 0; color:#212529;">

  <!-- Personal -->
  <div class="saod-tip-panel" data-for="personal">
    <h3 style="font-size:1.05rem; font-weight:900; color:#0f5132; margin-bottom:10px;">نصائح — القرض الشخصي</h3>
    <ul style="padding-right:18px; font-size:.92rem; color:#495057; line-height:2;">
      <li>الاستقطاع لا يتجاوز 33% من الراتب للموظفين و 25% للمتقاعدين.</li>
      <li>اطلب من البنك نسبة APR وليس Flat — الفرق قد يصل لضعف التكلفة الفعلية.</li>
      <li>الرسوم الإدارية 0.5% من القرض بحد أقصى 2,500 ريال + ضريبة 15% (نظام ساما ديسمبر 2025).</li>
      <li>كلما قصرت المدة، قلّت الفوائد الإجمالية حتى لو ارتفع القسط.</li>
    </ul>
  </div>

  <!-- Mortgage -->
  <div class="saod-tip-panel" data-for="mortgage" style="display:none;">
    <h3 style="font-size:1.05rem; font-weight:900; color:#0f5132; margin-bottom:10px;">نصائح — القرض العقاري</h3>
    <ul style="padding-right:18px; font-size:.92rem; color:#495057; line-height:2;">
      <li>الدفعة الأولى 10% للمسكن الأول، 30% للمسكن الثاني.</li>
      <li>الاستقطاع الكلي لا يتجاوز 65% من الراتب بما فيه جميع الالتزامات.</li>
      <li>ضريبة التصرفات العقارية 5% على ما يزيد عن مليون ريال للمسكن الأول.</li>
      <li>دعم سكني: راتب أقل من 10,000 → 150,000 ريال، راتب 10,000 أو أكثر → 100,000 ريال — للمسكن الأول فقط.</li>
      <li>احسب تكاليف السعي والتقييم والرسوم الإدارية ضمن ميزانية الدفعة.</li>
    </ul>
  </div>

  <!-- Buyout -->
  <div class="saod-tip-panel" data-for="buyout" style="display:none;">
    <h3 style="font-size:1.05rem; font-weight:900; color:#0f5132; margin-bottom:10px;">نصائح — شراء المديونية</h3>
    <ul style="padding-right:18px; font-size:.92rem; color:#495057; line-height:2;">
      <li>لا يعني انخفاض القسط الشهري توفيراً حقيقياً — قارن إجمالي السداد.</li>
      <li>إذا أضفت مبلغاً إضافياً، راجع التحليل الشفاف أسفل النتائج لفصل تكلفة المبلغ الجديد عن وفر إعادة التمويل.</li>
      <li>تحقق من رسوم إعادة الجدولة ونقل المديونية قبل الموافقة.</li>
      <li>اطلب جدول السداد الجديد كاملاً وقارنه بالقديم قبل التوقيع.</li>
    </ul>
  </div>

  <!-- Early payoff -->
  <div class="saod-tip-panel" data-for="early" style="display:none;">
    <h3 style="font-size:1.05rem; font-weight:900; color:#0f5132; margin-bottom:10px;">نصائح — السداد المبكر</h3>
    <ul style="padding-right:18px; font-size:.92rem; color:#495057; line-height:2;">
      <li>ساما تحدد تعويض السداد المبكر بأرباح 3 أشهر كحد أقصى (قاعدة إعادة الاستثمار).</li>
      <li>التمويل العقاري: ممنوع السداد المبكر أول سنتين. بعدها بدون غرامة.</li>
      <li>السداد المبكر في بداية القرض أوفر لأن معظم الفوائد تُسدَّد في السنوات الأولى.</li>
      <li>اطلب شهادة براءة الذمة كتابةً فور إتمام السداد.</li>
    </ul>
  </div>

</div>

<script>
(function() {
  function syncTips() {
    var active = document.querySelector('#saod-fc .tab-btn.active');
    if (!active) return;
    var tab = active.getAttribute('data-tab');
    document.querySelectorAll('.saod-tip-panel').forEach(function(p) {
      p.style.display = p.getAttribute('data-for') === tab ? 'block' : 'none';
    });
  }

  function attachTabListeners() {
    var btns = document.querySelectorAll('#saod-fc .tab-btn');
    if (!btns.length) { setTimeout(attachTabListeners, 300); return; }
    btns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        setTimeout(syncTips, 50);
      });
    });
    syncTips();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachTabListeners);
  } else {
    attachTabListeners();
  }
})();
</script>

<?php
    return ob_get_clean();
}
add_shortcode( 'finance_calc', 'saod_finance_calc_shortcode' );
add_shortcode( 'حاسبة', 'saod_finance_calc_shortcode' );
add_shortcode( 'saod_calculator', 'saod_finance_calc_shortcode' );
