<?php
////////////////ГЕНЕРАТОР ПРОМОКОДОВ////////////////////////
function add_contact_to_unisender($email, $name) {
    $api_key = 'UR_API';
    $list_id = 'UR_LIST_ID ';

    $api_url = 'https://api.unisender.com/ru/api/subscribe';

    $params = array(
        'api_key' => $api_key,
        'list_ids' => $list_id,
        'fields[email]' => $email,
        'fields[NAME]' => $name,
    );

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);
}

function process_form_after_submission($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();

        $name = isset($posted_data['text-170']) ? sanitize_text_field($posted_data['text-170']) : '';

        $email = isset($posted_data['email-505']) ? sanitize_email($posted_data['email-505']) : '';

        $existing_coupon = get_existing_coupon($email);

        if (!$existing_coupon) {
            $coupon_code = generate_unique_coupon($email);

            if ($coupon_code) {
                save_coupon_to_table($email, $coupon_code);
                add_contact_to_unisender($email, $name);

                $coupon = array(
                    'post_title'   => $coupon_code,
                    'post_content' => '',
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'post_type'    => 'shop_coupon',
                    'post_excerpt' => 'Промокод для ' . $email,
                );

                $new_coupon_id = wp_insert_post($coupon);

                update_post_meta($new_coupon_id, 'discount_type', 'fixed_cart');
                update_post_meta($new_coupon_id, 'coupon_amount', 1000);
                update_post_meta($new_coupon_id, 'individual_use', 'yes');
                update_post_meta($new_coupon_id, 'usage_limit', 1);
                update_post_meta($new_coupon_id, 'expiry_date', strtotime('+1 week'));

                send_coupon_email($email, $coupon_code, $name);
            }
        }
    }
}
add_action('wpcf7_mail_sent', 'process_form_after_submission');

function generate_unique_coupon($email) {
    $coupon_code = 'ST_' . wp_generate_password(7, false);
    $existing_coupon = get_existing_coupon_by_code($coupon_code);

    if ($existing_coupon) {
        return generate_unique_coupon($email);
    }

    return $coupon_code;
}

function save_coupon_to_table($email, $coupon_code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'UR_DB';

    $wpdb->insert(
        $table_name,
        array(
            'email'       => $email,
            'coupon_code' => $coupon_code,
        ),
        array('%s', '%s')
    );
}

function get_existing_coupon($email) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'UR_DB';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $email));

    return $result;
}

function get_existing_coupon_by_code($coupon_code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'UR_DB';

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE coupon_code = %s", $coupon_code));

    return $result;
}
function send_coupon_email($email, $coupon_code, $name) {
    $subject = 'Ваш промокод';

    $message = '<html lang = "ru">
<body>
<div style="max-width: 800px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">

    <div style="text-align: center;">
        <img src="UR_URL" alt="logomail" style="max-width: 100%;">
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #333; font-size: 32px; font-weight: 600; margin: 0;">Здравствуйте!</p>
        <p style="color: #333; font-size: 32px; font-weight: 600; margin: 0;">' . $name . ' </p>
        <p style="color: #333; font-size: 18px; margin: 10px 0;">Благодарим Вас за подписку на нашу рассылку!</p>
        <p style="color: #333; font-size: 18px; margin: 10px 0;">В качестве подарка мы дарим вам купон на сумму <span style="color: #333; font-weight: 600;">1000 ₽</span></p>
    </div>

    <div style="text-align: center; margin-top: 40px; padding: 20px; background: rgba(248, 248, 248, 0.71); border-radius: 10px;">
        <div style="margin-bottom: 20px;">
            <img src="UR_URL" alt="giftcoupon" style="max-width: 100%;">
        </div>
        <p style="color: #333; font-size: 28px; font-weight: 600; margin: 0;">Ваш купон!</p>
        <p style="color: #333; font-size: 18px; margin: 10px 0;">Используйте его для получения скидки в корзине нашего магазина</p>
        <p style="color: #f5c76d; font-size: 21px; font-weight: 600; margin: 0 auto; border-radius: 1000px; background: #FFF; width: 203px; height: 40px; line-height: 40px; text-align: center;">' . $coupon_code . '</p>
        <a href="UR_URL" style="text-decoration: none;">
        <div style="background: linear-gradient(90deg, #f1d9a9 0%, #f8edda 100%); border-radius: 6px; display: inline-flex; justify-content: center; align-items: center; gap: 10px; padding: 10px 40px; width: 200px; height: 20px; margin: 20px auto;">
            <p style="color: #000; font-size: 18px; font-weight: 400; margin: 0;">Перейти к покупкам</p>
        </div>
        </a>
    </div>

</div>
</body>
</html>';

    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $email);

    wp_mail($email, $subject, $message, $headers, $name);
}
?>
