// اضافه کردن فیلد به صفحه جزئیات سفارش در مدیریت
add_action('woocommerce_admin_order_data_after_order_details', 'add_custom_field_to_order_details');
function add_custom_field_to_order_details($order) {
    ?>
    <div class="form-field form-field-wide">
        <label for="custom_tracking_id"><?php _e('شناسه رهگیری پست:', 'woocommerce'); ?></label>
        <input type="text" id="custom_tracking_id" name="custom_tracking_id" value="<?php echo get_post_meta($order->get_id(), '_custom_tracking_id', true); ?>" style="width:100%;" maxlength="24" />
        <p class="description"><?php _e('لطفاً یک عدد 24 رقمی وارد کنید.', 'woocommerce'); ?></p>
    </div>
    <?php
}

// ذخیره کردن فیلد در یادداشت‌های سفارش
add_action('woocommerce_process_shop_order_meta', 'save_custom_field_to_order_meta');
function save_custom_field_to_order_meta($order_id) {
    if (isset($_POST['custom_tracking_id'])) {
        $tracking_id = sanitize_text_field($_POST['custom_tracking_id']);

        // ذخیره کد رهگیری در متای سفارش فقط در صورتی که عددی و 24 کاراکتری باشد
        if (!empty($tracking_id) && preg_match('/^\d{24}$/', $tracking_id)) {
            update_post_meta($order_id, '_custom_tracking_id', $tracking_id);

            $note = sprintf(__('شناسه رهگیری پست: <a href="https://tracking.post.ir/search.aspx?id=%1$s#">%1$s</a>', 'woocommerce'), $tracking_id);
            $order = wc_get_order($order_id);
            $order->add_order_note($note, false); // یادداشت عمومی برای نمایش به کاربر
        }
    }
}

// نمایش شناسه رهگیری پست در صفحه جزئیات سفارش (بالای مشخصات فاکتور)
add_action('woocommerce_order_details_before_order_table', 'display_tracking_link_to_customer');
function display_tracking_link_to_customer($order) {
    $tracking_id = get_post_meta($order->get_id(), '_custom_tracking_id', true);

    if (!empty($tracking_id)) {
        echo '<p style="font-weight: bold; font-size: 16px; color: #000;">' . __('شناسه رهگیری پست:', 'woocommerce') . ' ';
        echo '<a href="https://tracking.post.ir/search.aspx?id=' . esc_attr($tracking_id) . '#" target="_blank" style="color: #0071a1; text-decoration: underline;">' . esc_html($tracking_id) . '</a></p>';
        echo '<p style="font-style: italic; color: #555;">' . __('برای مشاهده وضعیت مرسوله، روی لینک بالا کلیک کنید.', 'woocommerce') . '</p>';
    }
}

// اضافه کردن ستون شناسه رهگیری به لیست سفارشات در صفحه حساب کاربری
add_filter('woocommerce_my_account_my_orders_columns', 'add_tracking_id_column_to_my_orders');
function add_tracking_id_column_to_my_orders($columns) {
    // افزودن ستون شناسه رهگیری به ستون پنجم
    $new_columns = [];
    $i = 0;

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        $i++;

        // افزودن ستون جدید پس از ستون چهارم
        if ($i === 4) {
            $new_columns['tracking_id'] = __('کد رهگیری', 'woocommerce');
        }
    }

    return $new_columns;
}

// نمایش شناسه رهگیری در ستون جدید
add_action('woocommerce_my_account_my_orders_column_tracking_id', 'display_tracking_id_in_my_orders');
function display_tracking_id_in_my_orders($order) {
    $tracking_id = get_post_meta($order->get_id(), '_custom_tracking_id', true);

    if (!empty($tracking_id)) {
        echo '<a href="https://tracking.post.ir/search.aspx?id=' . esc_attr($tracking_id) . '#" target="_blank" style="color: #0071a1; text-decoration: underline;">' . esc_html($tracking_id) . '</a>';
    } else {
        echo __('اطلاعاتی یافت نشد.', 'woocommerce');
    }
}

// افزودن تگ سفارشی برای شماره رهگیری در پیامک ووکامرس فارسی
add_filter('wp_sms_tags', 'add_tracking_id_sms_tag');
function add_tracking_id_sms_tag($tags) {
    $tags['{tracking_id}'] = 'نمایش شماره رهگیری پست';
    return $tags;
}

// جایگزینی مقدار شماره رهگیری در تگ پیامک
add_filter('wp_sms_order_meta', 'replace_tracking_id_sms_tag', 10, 2);
function replace_tracking_id_sms_tag($order_meta, $order_id) {
    $tracking_id = get_post_meta($order_id, '_custom_tracking_id', true);

    if (!empty($tracking_id)) {
        $order_meta['{tracking_id}'] = $tracking_id;
    } else {
        $order_meta['{tracking_id}'] = __('شماره رهگیری ثبت نشده است.', 'woocommerce');
    }

    return $order_meta;
}
