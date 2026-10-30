<?php
// includes/mail.php - էլ. նամակների ուղարկում և նամակների ձևանմուշներ
require_once __DIR__ . '/helpers.php';

/**
 * Ուղարկում է HTML նամակ։ Վերադարձնում է true, եթե PHP-ն ընդունել է նամակը։
 * XAMPP-ում mail() սովորաբար կարգավորված չէ, ուստի development ռեժիմում նամակը գրվում է նաև logs/mail.log-ում
 * (այնտեղից կարելի է վերցնել օր. վերականգնման հղումը)։ APP_ENV=production-ում տեքստը լոգում չի պահվում։
 */
function send_mail(string $to, string $subject, string $htmlBody): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $html = '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif; color:#2c3e50; max-width:600px; margin:0 auto;">'
          . '<h2 style="color:#c0623b;">' . e(SHOP_NAME) . '</h2>' . $htmlBody
          . '<hr style="border:none; border-top:1px solid #ddd; margin:24px 0;">'
          . '<p style="color:#7f8c8d; font-size:12px;">' . e(SHOP_NAME) . ' · ' . e(SHOP_EMAIL) . '</p></body></html>';

    $headers = "MIME-Version: 1.0\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . 'From: ' . SHOP_NAME . ' <' . SHOP_EMAIL . ">\r\n"
             . 'Reply-To: ' . SHOP_EMAIL . "\r\n";
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $sent = @mail($to, $encodedSubject, $html, $headers);

    if (APP_ENV !== 'production') {
        $dir = dirname(__DIR__) . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $htmlBody))));
        @file_put_contents(
            $dir . '/mail.log',
            '[' . date('Y-m-d H:i:s') . '] ' . ($sent ? 'SENT' : 'NOT SENT (mail() չի աշխատում)') . " TO: $to | SUBJECT: $subject\n$text\n\n",
            FILE_APPEND | LOCK_EX
        );
    }
    return $sent;
}

/**
 * Պատվերի հաստատում հաճախորդին և ծանուցում խանութին։ Սխալը երբեք չի խանգարում պատվերին։
 * $o՝ order_number, first_name, last_name, email, phone, address, city, shipping_method, payment_method,
 *     subtotal, shipping, discount, total
 * $lines՝ [['title'=>, 'price'=>, 'qty'=>], ...]
 */
function send_order_emails(array $o, array $lines): void {
    try {
        $shippingNames = ['standard' => 'Ստանդարտ առաքում', 'express' => 'Էքսպրես առաքում', 'pickup' => 'Ինքնաուղևորում'];
        $paymentNames = ['cash' => 'Կանխիկ', 'card' => 'Բանկային քարտ', 'paypal' => 'PayPal'];
        $money = function ($n) { return number_format((float)$n, 0) . ' դրամ'; };

        $rows = '';
        foreach ($lines as $l) {
            $rows .= '<tr><td style="padding:6px; border-bottom:1px solid #eee;">' . e($l['title']) . ' × ' . (int)$l['qty'] . '</td>'
                   . '<td style="padding:6px; border-bottom:1px solid #eee; text-align:right;">' . e($money($l['price'] * $l['qty'])) . '</td></tr>';
        }
        $rows .= '<tr><td style="padding:6px;">Առաքում</td><td style="padding:6px; text-align:right;">' . e($money($o['shipping'])) . '</td></tr>';
        if ($o['discount'] > 0) {
            $rows .= '<tr><td style="padding:6px;">Զեղչ</td><td style="padding:6px; text-align:right;">-' . e($money($o['discount'])) . '</td></tr>';
        }
        $rows .= '<tr><td style="padding:6px;"><strong>Ընդհանուր</strong></td><td style="padding:6px; text-align:right;"><strong>' . e($money($o['total'])) . '</strong></td></tr>';

        $table = '<table style="width:100%; border-collapse:collapse;">' . $rows . '</table>';
        $ship = $shippingNames[$o['shipping_method']] ?? $o['shipping_method'];
        $pay = $paymentNames[$o['payment_method']] ?? $o['payment_method'];
        $address = e($o['address'] . ', ' . $o['city']);

        // Հաճախորդին
        send_mail(
            $o['email'],
            'Ձեր պատվերը ընդունված է՝ ' . $o['order_number'],
            '<p>Բարև ' . e($o['first_name']) . ',</p><p>Շնորհակալություն պատվերի համար։ Պատվերի համարը՝ <strong>' . e($o['order_number']) . '</strong>։</p>'
            . $table
            . '<p>Առաքում՝ ' . e($ship) . '<br>Վճարում՝ ' . e($pay) . '<br>Հասցե՝ ' . $address . '</p>'
            . '<p>Մենք կկապվենք ձեզ հետ առաքման համար։</p>'
        );

        // Խանութին
        send_mail(
            SHOP_EMAIL,
            'Նոր պատվեր՝ ' . $o['order_number'],
            '<p>Նոր պատվեր <strong>' . e($o['order_number']) . '</strong></p>'
            . '<p>Հաճախորդ՝ ' . e($o['first_name'] . ' ' . $o['last_name']) . '<br>Էլ. փոստ՝ ' . e($o['email'])
            . '<br>Հեռախոս՝ ' . e($o['phone']) . '<br>Հասցե՝ ' . $address . '<br>Առաքում՝ ' . e($ship) . '<br>Վճարում՝ ' . e($pay) . '</p>'
            . $table
            . '<p><a href="' . e(app_url('admin_orders.php')) . '">Բացել admin վահանակը</a></p>'
        );
    } catch (Throwable $ex) {
        error_log('send_order_emails failed: ' . $ex->getMessage());
    }
}
