<?php
/**
 * Bank Rates Comparison Table — Addition to حاسبة التمويل السعودية
 * Fetches rates from GitHub (saudiopendata-rates) and displays a comparison table.
 * Add this code to the end of the existing calculator plugin file.
 */

// ── Configuration ────────────────────────────────────────────────────────────
define( 'SAOD_RATES_URL', 'https://raw.githubusercontent.com/alkh9125/saudiopendata-rates/main/data/rates.json' );
define( 'SAOD_CACHE_KEY', 'saod_bank_rates_cache' );
define( 'SAOD_CACHE_TTL', DAY_IN_SECONDS );

// ── Fetch & Cache ────────────────────────────────────────────────────────────
function saod_get_rates() {
    $cached = get_option( SAOD_CACHE_KEY );

    // Try fetching fresh data
    $response = wp_remote_get( SAOD_RATES_URL, array(
        'timeout' => 10,
        'headers' => array( 'Accept' => 'application/json' ),
    ) );

    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data && isset( $data['banks'] ) ) {
            // Save to cache
            update_option( SAOD_CACHE_KEY, array(
                'data'       => $data,
                'fetched_at' => time(),
            ) );
            return $data;
        }
    }

    // Fallback to cached data
    if ( $cached && isset( $cached['data'] ) ) {
        return $cached['data'];
    }

    return null;
}

// ── Check if cache is fresh ──────────────────────────────────────────────────
function saod_is_cache_fresh() {
    $cached = get_option( SAOD_CACHE_KEY );
    if ( ! $cached || ! isset( $cached['fetched_at'] ) ) {
        return false;
    }
    return ( time() - $cached['fetched_at'] ) < SAOD_CACHE_TTL;
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

    ob_start();
    ?>
    <div class="saod-rates-wrapper" style="margin-top:30px;">
        <h3 style="text-align:center;margin-bottom:5px;">مقارنة نسب البنوك السعودية</h3>
        <p style="text-align:center;color:#888;font-size:13px;margin-bottom:15px;">
            آخر تحديث: <?php echo esc_html( $rates['last_updated'] ?? '—' ); ?>
        </p>

        <!-- Tabs -->
        <div class="saod-tabs" style="display:flex;justify-content:center;gap:10px;margin-bottom:20px;">
            <?php $first = true; foreach ( $products as $key => $label ) : ?>
                <button class="saod-tab-btn<?php echo $first ? ' active' : ''; ?>"
                        data-tab="saod-<?php echo esc_attr( $key ); ?>"
                        style="padding:8px 18px;border:1px solid #ddd;border-radius:20px;background:<?php echo $first ? '#0073aa' : '#fff'; ?>;color:<?php echo $first ? '#fff' : '#333'; ?>;cursor:pointer;font-size:14px;">
                    <?php echo esc_html( $label ); ?>
                </button>
            <?php $first = false; endforeach; ?>
        </div>

        <!-- Tables -->
        <?php $first = true; foreach ( $products as $key => $label ) : ?>
            <div class="saod-tab-content" id="saod-<?php echo esc_attr( $key ); ?>"
                 style="<?php echo $first ? '' : 'display:none;'; ?>">
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead>
                        <tr style="background:#f7f7f7;">
                            <th style="padding:10px;border:1px solid #eee;text-align:right;">البنك</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">معدل النسبة السنوي (APR)</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">أقصى مبلغ</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">أقصى مدة</th>
                            <th style="padding:10px;border:1px solid #eee;text-align:center;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Sort banks by APR (lowest first), unavailable at bottom
                        $banks_sorted = $rates['banks'];
                        usort( $banks_sorted, function( $a, $b ) use ( $key ) {
                            $apr_a = $a[ $key ]['apr'] ?? PHP_INT_MAX;
                            $apr_b = $b[ $key ]['apr'] ?? PHP_INT_MAX;
                            return $apr_a <=> $apr_b;
                        } );

                        foreach ( $banks_sorted as $bank ) :
                            $product = $bank[ $key ] ?? null;
                            if ( ! $product ) continue;

                            $apr        = $product['apr'];
                            $derived    = $product['apr_derived'] ?? false;
                            $status     = $product['status'] ?? 'unavailable';
                            $max_amount = $product['max_amount'] ?? null;
                            $max_years  = $product['max_years'] ?? null;

                            if ( $status === 'unavailable' || $apr === null ) {
                                $apr_display = '—';
                            } else {
                                $apr_display = $apr . '%';
                                if ( $derived ) $apr_display .= ' <span title="مشتق من معدل الربح الثابت" style="color:#f0ad4e;">*</span>';
                            }

                            $status_icon = '';
                            if ( $status === 'ok' ) {
                                $status_icon = '<span style="color:#5cb85c;" title="محدّث">✓</span>';
                            } elseif ( $status === 'stale' ) {
                                $status_icon = '<span style="color:#f0ad4e;" title="قديم — قد لا يكون دقيقاً">⚠</span>';
                            } else {
                                $status_icon = '<span style="color:#999;">—</span>';
                            }

                            $amount_display = $max_amount ? number_format( $max_amount ) . ' ريال' : '—';
                            $years_display  = $max_years ? $max_years . ' سنة' : '—';
                        ?>
                        <tr>
                            <td style="padding:10px;border:1px solid #eee;text-align:right;font-weight:bold;">
                                <?php echo esc_html( $bank['name_ar'] ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo $apr_display; ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo esc_html( $amount_display ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo esc_html( $years_display ); ?>
                            </td>
                            <td style="padding:10px;border:1px solid #eee;text-align:center;">
                                <?php echo $status_icon; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php $first = false; endforeach; ?>

        <p style="text-align:center;color:#aaa;font-size:12px;margin-top:10px;">
            (*) = معدل مشتق من نسبة الربح الثابتة &nbsp;|&nbsp; ⚠ = بيانات قديمة قد لا تكون دقيقة
        </p>
    </div>

    <!-- Tab switching script -->
    <script>
    (function(){
        var btns = document.querySelectorAll('.saod-tab-btn');
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

                document.querySelectorAll('.saod-tab-content').forEach(function(c){
                    c.style.display = 'none';
                });
                document.getElementById(btn.getAttribute('data-tab')).style.display = '';
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'bank_rates_table', 'saod_bank_rates_table_shortcode' );

// ── Admin: Manual Refresh (optional) ─────────────────────────────────────────
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
