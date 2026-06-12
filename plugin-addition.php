<?php
/**
 * Plugin Name: جدول مقارنة نسب البنوك
 * Plugin URI: https://github.com/alkh9125/saudiopendata-rates
 * Description: جدول مقارنة نسب التمويل للبنوك السعودية — يُحدَّث يومياً تلقائياً
 * Version: 1.1.0
 * Author: SaudiOpenData
 * Text Domain: saod-rates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Configuration ────────────────────────────────────────────────────────────
define( 'SAOD_RATES_URL', 'https://raw.githubusercontent.com/alkh9125/saudiopendata-rates/main/data/rates.json' );
define( 'SAOD_CACHE_KEY', 'saod_bank_rates_cache' );
define( 'SAOD_CACHE_TTL', DAY_IN_SECONDS );

// ── Fetch & Cache ────────────────────────────────────────────────────────────
function saod_get_rates() {
    $cached = get_option( SAOD_CACHE_KEY );

    // Serve cache while it is still fresh — avoids hitting GitHub on every page view
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

    // Fallback to stale cache if the fetch failed
    if ( $cached && isset( $cached['data'] ) ) {
        return $cached['data'];
    }

    return null;
}

// ── Shortcode: [bank_rates_table] ────────────────────────────────────────────
function saod_bank_rates_table_shortcode( $atts ) {
    $rates = saod_get_rates();

    if ( ! $rates ) {
        return '<p style="color:#999;text-align:center;">عذراً، لا يمكن تحميل نسب البنوك حالياً.</p>';
    }

    $products = array(
        'personal' => 'تمويل شخصي',
        'buyout'   => 'شراء مديونية',
        'mortgage' => 'تمويل عقاري',
    );

    $uid = 'saod-' . wp_unique_id();

    ob_start();
    ?>
    <div class="saod-rates-wrapper" id="<?php echo esc_attr( $uid ); ?>" dir="rtl" style="margin-top:30px;font-family:inherit;">
        <h3 style="text-align:center;margin-bottom:5px;">مقارنة نسب التمويل في البنوك السعودية</h3>
        <p style="text-align:center;color:#888;font-size:13px;margin-bottom:15px;">
            آخر تحديث للبيانات: <?php echo esc_html( $rates['last_updated'] ?? '—' ); ?>
            &nbsp;·&nbsp; النسب استرشادية «تبدأ من» وتختلف حسب الراتب وجهة العمل ومدة التمويل
        </p>

        <!-- Tabs -->
        <div class="saod-tabs" style="display:flex;justify-content:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            <?php $first = true; foreach ( $products as $key => $label ) : ?>
                <button type="button" class="saod-tab-btn<?php echo $first ? ' active' : ''; ?>"
                        data-tab="<?php echo esc_attr( $uid . '-' . $key ); ?>"
                        style="padding:8px 18px;border:1px solid #ddd;border-radius:20px;background:<?php echo $first ? '#0073aa' : '#fff'; ?>;color:<?php echo $first ? '#fff' : '#333'; ?>;cursor:pointer;font-size:14px;">
                    <?php echo esc_html( $label ); ?>
                </button>
            <?php $first = false; endforeach; ?>
        </div>

        <!-- Tables -->
        <?php $first = true; foreach ( $products as $key => $label ) : ?>
            <div class="saod-tab-content" id="<?php echo esc_attr( $uid . '-' . $key ); ?>"
                 style="<?php echo $first ? '' : 'display:none;'; ?>overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:14px;min-width:560px;">
                    <thead>
                        <tr style="background:#f7f7f7;">
                            <th style="padding:10px;border:1px solid #eee;text-align:right;">البنك</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">معدل النسبة السنوي (APR)</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">هامش الربح الثابت (Flat)</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">أقصى مبلغ</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">أقصى مدة</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">المصدر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Sort: available banks by APR ascending, unavailable at the bottom
                        $banks_sorted = $rates['banks'];
                        usort( $banks_sorted, function( $a, $b ) use ( $key ) {
                            $apr_a = $a[ $key ]['apr'] ?? null;
                            $apr_b = $b[ $key ]['apr'] ?? null;
                            if ( $apr_a === null && $apr_b === null ) return 0;
                            if ( $apr_a === null ) return 1;
                            if ( $apr_b === null ) return -1;
                            return $apr_a <=> $apr_b;
                        } );

                        // Lowest available APR gets highlighted as the best offer
                        $best_apr = null;
                        foreach ( $banks_sorted as $b ) {
                            if ( isset( $b[ $key ]['apr'] ) && $b[ $key ]['apr'] !== null
                                 && ( $b[ $key ]['status'] ?? '' ) !== 'unavailable' ) {
                                $best_apr = $b[ $key ]['apr'];
                                break;
                            }
                        }

                        foreach ( $banks_sorted as $bank ) :
                            $product = $bank[ $key ] ?? null;
                            if ( ! $product ) continue;

                            $apr        = $product['apr'] ?? null;
                            $flat       = $product['flat'] ?? null;
                            $derived    = $product['apr_derived'] ?? false;
                            $status     = $product['status'] ?? 'unavailable';
                            $max_amount = $product['max_amount'] ?? null;
                            $max_years  = $product['max_years'] ?? null;
                            $row_date   = $product['last_updated'] ?? null;
                            $source     = $bank['source_url'][ $key ] ?? null;
                            $is_best    = ( $best_apr !== null && $apr === $best_apr && $status !== 'unavailable' );

                            $row_style = 'background:' . ( $is_best ? '#f0f9f0' : '#fff' ) . ';';

                            // APR cell
                            if ( $status === 'unavailable' || $apr === null ) {
                                $apr_html = '<span style="color:#bbb;">غير معلَن</span>';
                            } else {
                                $apr_html = '<strong>' . esc_html( 'يبدأ من ' . $apr . '%' ) . '</strong>';
                                if ( $derived ) {
                                    $apr_html .= ' <span title="مشتق رياضياً من الهامش الثابت" style="color:#f0ad4e;cursor:help;">*</span>';
                                }
                                if ( $status === 'stale' ) {
                                    $apr_html .= ' <span title="آخر تحديث ناجح: ' . esc_attr( $row_date ?: 'غير معروف' ) . ' — قد لا تكون النسبة محدثة" style="cursor:help;">⚠</span>';
                                }
                                if ( $is_best ) {
                                    $apr_html .= ' <span style="background:#5cb85c;color:#fff;font-size:11px;padding:1px 7px;border-radius:10px;">الأقل</span>';
                                }
                            }

                            // Flat cell
                            if ( $flat === null ) {
                                $flat_html = '<span style="color:#bbb;">—</span>';
                            } else {
                                $flat_html = esc_html( $flat . '%' );
                            }

                            $amount_display = $max_amount ? number_format( $max_amount ) . ' ريال' : '—';
                            $years_display  = $max_years ? $max_years . ' سنة' : '—';
                        ?>
                        <tr style="<?php echo esc_attr( $row_style ); ?>">
                            <td style="padding:10px;border:1px solid #eee;text-align:right;font-weight:bold;">
                                <?php echo esc_html( $bank['name_ar'] ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo $apr_html; ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo $flat_html; ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo esc_html( $amount_display ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo esc_html( $years_display ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php if ( $source ) : ?>
                                    <a href="<?php echo esc_url( $source ); ?>" target="_blank" rel="nofollow noopener"
                                       style="color:#0073aa;text-decoration:none;font-size:13px;">موقع البنك ↗</a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php $first = false; endforeach; ?>

        <div style="background:#fdf8ee;border:1px solid #f0e3c0;border-radius:8px;padding:10px 15px;margin-top:15px;font-size:12.5px;color:#7a6a45;line-height:1.8;">
            <strong>تنبيه:</strong> النسب أعلاه استرشادية ومجمعة من المواقع الرسمية للبنوك، وقد تختلف النسبة الفعلية
            حسب راتبك وجهة عملك ومدة التمويل. اضغط على «موقع البنك» للتحقق من النسبة المحدثة قبل اتخاذ أي قرار.
            <br>
            (*) = معدل APR مشتق رياضياً من الهامش الثابت
            &nbsp;|&nbsp; ⚠ = تعذّر التحديث التلقائي مؤخراً — النسبة من آخر تحديث ناجح
            &nbsp;|&nbsp; <strong>Flat</strong> = هامش الربح الثابت على أصل المبلغ، و<strong>APR</strong> = معدل النسبة السنوي
            الفعلي شامل الرسوم — المقارنة الصحيحة بين البنوك تكون بالـ APR.
        </div>
    </div>

    <!-- Tab switching script -->
    <script>
    (function(){
        var wrap = document.getElementById('<?php echo esc_js( $uid ); ?>');
        if (!wrap) return;
        var btns = wrap.querySelectorAll('.saod-tab-btn');
        btns.forEach(function(btn){
            btn.addEventListener('click', function(){
                btns.forEach(function(b){
                    b.style.background = '#fff';
                    b.style.color = '#333';
                    b.classList.remove('active');
                });
                btn.style.background = '#0073aa';
                btn.style.color = '#fff';
                btn.classList.add('active');

                wrap.querySelectorAll('.saod-tab-content').forEach(function(c){
                    c.style.display = 'none';
                });
                var target = document.getElementById(btn.getAttribute('data-tab'));
                if (target) target.style.display = '';
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'bank_rates_table', 'saod_bank_rates_table_shortcode' );

// ── Admin: Manual Refresh ────────────────────────────────────────────────────
function saod_rates_admin_menu() {
    add_options_page(
        'SAOD Rates',
        'SAOD Rates',
        'manage_options',
        'saod-rates',
        'saod_rates_admin_page'
    );
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
