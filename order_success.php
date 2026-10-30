<?php
// order_success.php - պատվերի հաստատման էջ
$page_title = 'Պատվերը ընդունված է';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_login();

$number = (string)($_GET['n'] ?? '');
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ?');
$stmt->execute([$number, current_user_id()]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: profile.php#orders');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

$shippingNames = ['standard' => 'Ստանդարտ առաքում', 'express' => 'Էքսպրես առաքում', 'pickup' => 'Ինքնաուղևորում'];
$paymentNames = ['cash' => 'Կանխիկ', 'card' => 'Բանկային քարտ', 'paypal' => 'PayPal'];

require_once 'includes/header.php';
?>

<section class="checkout-section" style="padding:3rem 1.5rem;">
    <div class="section-container" style="max-width:700px; margin:0 auto;">
        <div style="text-align:center; margin-bottom:2rem;">
            <div style="font-size:4rem;">🎉</div>
            <h2 class="section-title">Շնորհակալություն պատվերի համար</h2>
            <p>Պատվերի համարը՝ <strong><?php echo e($order['order_number']); ?></strong></p>
            <p style="color:var(--color-warm-gray);">Մենք կկապվենք ձեզ հետ առաքման համար։</p>
        </div>

        <div style="background:var(--color-off-white); border:1px solid var(--color-light-gray); border-radius:12px; padding:1.5rem;">
            <table style="width:100%; border-collapse:collapse;">
                <?php foreach ($items as $item): ?>
                <tr style="border-bottom:1px solid var(--color-light-gray);">
                    <td style="padding:8px;"><?php echo e($item['book_title']); ?> × <?php echo (int)$item['quantity']; ?></td>
                    <td style="padding:8px; text-align:right;"><?php echo number_format($item['price'] * $item['quantity'], 0); ?> դրամ</td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td style="padding:8px;">Առաքում (<?php echo e($shippingNames[$order['shipping_method']] ?? $order['shipping_method']); ?>)</td>
                    <td style="padding:8px; text-align:right;"><?php echo number_format($order['shipping_price'], 0); ?> դրամ</td>
                </tr>
                <tr style="font-weight:700;">
                    <td style="padding:8px;">Ընդհանուր</td>
                    <td style="padding:8px; text-align:right; color:var(--color-terracotta);"><?php echo number_format($order['total_amount'], 0); ?> դրամ</td>
                </tr>
            </table>
            <p style="margin-top:1rem; color:var(--color-warm-gray);">
                Վճարման եղանակ՝ <?php echo e($paymentNames[$order['payment_method']] ?? $order['payment_method']); ?><br>
                Հասցե՝ <?php echo e($order['address'] . ', ' . $order['city']); ?>
            </p>
        </div>

        <div style="text-align:center; margin-top:2rem;">
            <a href="profile.php#orders" class="cta-button primary" style="display:inline-block;">Իմ պատվերները</a>
            <a href="search.php" class="cta-button secondary" style="display:inline-block; margin-left:0.5rem;">Շարունակել գնումները</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
