# saudiopendata-rates

نسب تمويل البنوك السعودية — يُحدَّث يومياً عبر GitHub Actions

---

## الملفات

| الملف | الوصف |
|---|---|
| `scraper.py` | السكرابر — يجيب النسب من البنوك القابلة ويشتق APR |
| `data/rates.json` | ملف النسب الحالي |
| `data/history.jsonl` | سجل تاريخي — كل دورة تحديث في سطر JSON |
| `.github/workflows/scrape.yml` | GitHub Actions — يشغّل السكرابر يومياً 3 صباحاً |
| `plugin-addition.php` | الكود المُضاف لإضافة WordPress |
| `requirements.txt` | مكتبات Python |

---

## خطوات الإعداد

### 1. GitHub

```bash
# أنشئ ريبو جديد باسم: saudiopendata-rates
# ارفع جميع الملفات
# تأكد أن الريبو Public (عشان WordPress يقرأ rates.json مباشرة)
```

### 2. تحديث الـ URL في plugin-addition.php

```php
// غيّر YOUR_USERNAME باسم حسابك على GitHub
define( 'SAOD_RATES_URL', 'https://raw.githubusercontent.com/YOUR_USERNAME/saudiopendata-rates/main/data/rates.json' );
```

### 3. إضافة الكود للإضافة الحالية

افتح ملف إضافة الحاسبة وأضف محتوى `plugin-addition.php` في نهايته.

### 4. استخدام الاختصار في WordPress

في الصفحة التي فيها الحاسبة — أضف بعد قسم النصائح:

```
[bank_rates_table]
```

أو في نفس shortcode الحاسبة — أضف `do_shortcode('[bank_rates_table]')` بعد `saod-tips`.

---

## البنوك

| البنك | طريقة التحديث | شخصي | مديونية | عقاري |
|---|---|---|---|---|
| مصرف الراجحي | Auto | ✅ | ✅ | ✅ |
| بنك الرياض | Auto | ✅ | ✅ | ✅ |
| مصرف الإنماء | Auto | ✅ | ✅ | — |
| البنك الأهلي SNB | Manual | ✅ | ✅ | ✅ |
| البنك العربي ANB | Manual | ✅ | ✅ | — |
| بنك الجزيرة | Manual | ✅ | ✅ | — |
| بنك البلاد | Manual | ✅ | ✅ | ✅ |
| البنك الأول | Manual | — | — | — |

**Auto** = يُحدَّث تلقائياً يومياً
**Manual** = يحتاج تحديث يدوي في `rates.json` شهرياً

---

## تحديث البنوك اليدوية

افتح `data/rates.json` وعدّل النسب للبنوك اليدوية.
ارفع التغييرات على GitHub — WordPress سيقرأ التحديث تلقائياً خلال 24 ساعة.

لتحديث فوري: لوحة تحكم WordPress → Settings → SAOD Rates → Refresh Now

---

## Error Handling

- **فشل السكرابر لبنك معين**: يحتفظ بآخر قيمة معروفة ويضع `status: stale` + علامة ⚠ في الجدول
- **فشل جلب rates.json في WordPress**: يعرض النسخة الاحتياطية المحفوظة في `wp_options`
- **بنك لا ينشر APR**: يشتق APR من معدل الربح (Flat) تلقائياً ويضع علامة (*) في الجدول
