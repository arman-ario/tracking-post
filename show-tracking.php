
// ثبت اسکریپت‌های مورد نیاز
function order_tracking_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'orderTrackingAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('order_tracking_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'order_tracking_enqueue_scripts');

// Shortcode برای نمایش فرم پیگیری سفارش
function display_order_tracking_form() {
    ob_start();
    ?>
    <div class="order-tracking-form-container">
        <form id="order-tracking-form" method="post">
            <div class="form-group">
                <label for="order_number">شماره سفارش:</label>
                <input type="text" id="order_number" name="order_number" required 
                       placeholder="مثال: 1234" maxlength="10">
            </div>
            <div class="form-group">
                <label for="phone_last4">4 رقم آخر شماره موبایل:</label>
                <input type="text" id="phone_last4" name="phone_last4" required 
                       placeholder="مثال: 1234" maxlength="4" 
                       pattern="[0-9]{4}">
            </div>
            <button type="submit" class="button">پیگیری سفارش</button>
            <?php wp_nonce_field('order_tracking_nonce', 'order_tracking_nonce'); ?>
        </form>
        <div id="tracking-result"></div>
    </div>

    <style>
    .order-tracking-form-container {
        max-width: 500px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: right; /* راست چین کردن محتوا */
        direction: rtl; /* تنظیم جهت برای زبان فارسی */
    }
    .form-group {
        margin-bottom: 15px;
        text-align: right;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
        text-align: right;
    }
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        text-align: right;
        direction: rtl;
    }
    .form-group input:focus {
        border-color: #2271b1;
        outline: none;
        box-shadow: 0 0 5px rgba(34,113,177,0.2);
    }
    .button {
        width: 100%;
        padding: 12px;
        background-color: #2271b1;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
        text-align: center;
    }
    .button:hover {
        background-color: #135e96;
    }
    .button:disabled {
        background-color: #999;
        cursor: not-allowed;
    }
    #tracking-result {
        margin-top: 20px;
        padding: 15px;
        border-radius: 4px;
        display: none;
        line-height: 1.6;
        text-align: right;
        direction: rtl;
    }
    .tracking-success {
        background: #f0f9eb;
        border: 1px solid #c2e7b0;
        color: #67c23a;
    }
    .tracking-error {
        background: #fef0f0;
        border: 1px solid #fbc4c4;
        color: #f56c6c;
    }
    .tracking-link {
        color: #2271b1;
        text-decoration: none;
        font-weight: bold;
    }
    .tracking-link:hover {
        text-decoration: underline;
    }
    /* اضافه کردن فونت فارسی (اختیاری) */
    @font-face {
        font-family: 'IRANSans';
        src: url('path/to/iranSans.eot');
        src: url('path/to/iranSans.eot?#iefix') format('embedded-opentype'),
             url('path/to/iranSans.woff2') format('woff2'),
             url('path/to/iranSans.woff') format('woff'),
             url('path/to/iranSans.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    /* اعمال فونت به کل فرم */
    .order-tracking-form-container,
    .order-tracking-form-container * {
        font-family: 'IRANSans', Tahoma, Arial, sans-serif;
    }
    /* اصلاح فاصله برای متن‌های کوتاه */
    small {
        display: block;
        margin-top: 5px;
        color: #666;
    }
    /* استایل برای پیام‌های وضعیت */
    strong {
        font-weight: bold;
        display: inline-block;
        margin: 0 5px;
    }
</style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#phone_last4').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        $('#order-tracking-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'check_order_tracking',
                order_number: $('#order_number').val().trim(),
                phone_last4: $('#phone_last4').val().trim(),
                nonce: $('#order_tracking_nonce').val()
            };

            if (formData.phone_last4.length !== 4 || !/^\d+$/.test(formData.order_number)) {
                $('#tracking-result')
                    .removeClass()
                    .addClass('tracking-error')
                    .html('لطفاً اطلاعات را به درستی وارد کنید.')
                    .show();
                return;
            }

            $('#tracking-result').hide();
            $('.button').prop('disabled', true).text('در حال بررسی...');

            $.ajax({
                url: orderTrackingAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    var resultDiv = $('#tracking-result');
                    resultDiv.removeClass('tracking-success tracking-error');
                    
                    if (response.success) {
                        resultDiv.addClass('tracking-success');
                    } else {
                        resultDiv.addClass('tracking-error');
                    }
                    
                    resultDiv.html(response.data.message).show();
                },
                error: function() {
                    $('#tracking-result')
                        .removeClass()
                        .addClass('tracking-error')
                        .html('خطا در برقراری ارتباط. لطفاً دوباره تلاش کنید.')
                        .show();
                },
                complete: function() {
                    $('.button').prop('disabled', false).text('پیگیری سفارش');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('order_tracking_form', 'display_order_tracking_form');

// Ajax handler برای بررسی سفارش
add_action('wp_ajax_check_order_tracking', 'handle_order_tracking_check');
add_action('wp_ajax_nopriv_check_order_tracking', 'handle_order_tracking_check');

function handle_order_tracking_check() {
    check_ajax_referer('order_tracking_nonce', 'nonce');

    $order_number = isset($_POST['order_number']) ? sanitize_text_field($_POST['order_number']) : '';
    $phone_last4 = isset($_POST['phone_last4']) ? sanitize_text_field($_POST['phone_last4']) : '';

    if (empty($order_number) || empty($phone_last4) || strlen($phone_last4) !== 4 || !is_numeric($phone_last4)) {
        wp_send_json_error([
            'message' => 'لطفاً اطلاعات را به درستی وارد کنید.'
        ]);
    }

    $order = wc_get_order($order_number);
    
    if (!$order) {
        wp_send_json_error([
            'message' => 'سفارشی با این شماره یافت نشد.'
        ]);
    }

    $order_phone = $order->get_billing_phone();
    $order_phone_last4 = substr(preg_replace('/[^0-9]/', '', $order_phone), -4);

    if ($order_phone_last4 !== $phone_last4) {
        wp_send_json_error([
            'message' => '4 رقم آخر شماره موبایل وارد شده صحیح نیست.'
        ]);
    }

    $customer_first_name = $order->get_billing_first_name();
    $customer_last_name = $order->get_billing_last_name();
    $customer_city = $order->get_billing_city();
    $customer_name = trim($customer_first_name . ' ' . $customer_last_name);
    
    $tracking_id = get_post_meta($order->get_id(), '_custom_tracking_id', true);

    if (!empty($tracking_id)) {
        $tracking_link = esc_url("https://tracking.post.ir/search.aspx?id=" . $tracking_id . "#");
        $message = sprintf(
            '%s عزیز، کد رهگیری سفارش شما به مقصد %s:<br>
            <a href="%s" target="_blank" class="tracking-link">%s</a><br>
            <small>برای پیگیری مرسوله روی کد رهگیری کلیک کنید.</small>',
            esc_html($customer_name),
            esc_html($customer_city),
            $tracking_link,
            esc_html($tracking_id)
        );
        wp_send_json_success(['message' => $message]);
    } else {
        $order_status = wc_get_order_status_name($order->get_status());
        $message = sprintf(
            '%s عزیز،<br>
            وضعیت سفارش شما به مقصد %s: <strong>%s</strong><br>
            <small>کد رهگیری پستی هنوز ثبت نشده است. به محض آماده شدن سفارش و ثبت کد رهگیری، 
            از طریق پیامک به شما اطلاع‌رسانی خواهد شد.</small>',
            esc_html($customer_name),
            esc_html($customer_city),
            $order_status
        );
        wp_send_json_error(['message' => $message]);
    }
}

// ترجمه وضعیت‌های سفارش
function get_persian_order_status($status) {
    $statuses = [
        'pending' => 'در انتظار پرداخت',
        'processing' => 'در حال آماده‌سازی',
        'on-hold' => 'در انتظار بررسی',
        'completed' => 'تکمیل شده',
        'cancelled' => 'لغو شده',
        'refunded' => 'مسترد شده',
        'failed' => 'ناموفق',
    ];
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

add_filter('wc_order_statuses', 'custom_wc_order_statuses');
function custom_wc_order_statuses($order_statuses) {
    $new_statuses = [];
    foreach ($order_statuses as $key => $status) {
        $status_name = str_replace('wc-', '', $key);
        $new_statuses[$key] = get_persian_order_status($status_name);
    }
    return $new_statuses;
}