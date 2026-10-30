<?php
// get_order_details.php
require_once 'config/database.php';
require_once 'includes/helpers.php';

if(!is_logged_in()) {
    http_response_code(401);
    echo '<p>Մուտք չեք գործել</p>';
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
if(is_admin()) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, current_user_id()]);
}
$order = $stmt->fetch();

if(!$order) {
    http_response_code(404);
    echo '<p>Պատվերը չի գտնվել</p>';
    exit;
}

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>

<h4 style="margin: 0 0 1rem; color: var(--color-charcoal);">Պատվեր #<?php echo htmlspecialchars($order['order_number']); ?></h4>

<div style="background: var(--color-cream); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <p><strong><i class="fas fa-user"></i> Հաճախորդ:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
    <p><strong><i class="fas fa-envelope"></i> Էլ. փոստ:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
    <p><strong><i class="fas fa-phone"></i> Հեռախոս:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
    <p><strong><i class="fas fa-map-marker-alt"></i> Հասցե:</strong> <?php echo htmlspecialchars($order['address']); ?>, <?php echo htmlspecialchars($order['city']); ?></p>
    <p><strong><i class="fas fa-truck"></i> Առաքման եղանակ:</strong> <?php echo $order['shipping_method'] == 'standard' ? 'Ստանդարտ' : ($order['shipping_method'] == 'express' ? 'Էքսպրես' : 'Ինքնաուղևորում'); ?></p>
    <p><strong><i class="fas fa-credit-card"></i> Վճարման եղանակ:</strong> <?php echo $order['payment_method'] == 'cash' ? 'Կանխիկ' : ($order['payment_method'] == 'card' ? 'Բանկային քարտ' : 'PayPal'); ?></p>
    <p><strong><i class="fas fa-calendar"></i> Ամսաթիվ:</strong> <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
</div>

<h5 style="margin: 0 0 0.5rem;">📦 Պատվերի ապրանքներ</h5>
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: var(--color-light-gray);">
            <th style="padding: 8px; text-align: left;">Անվանում</th>
            <th style="padding: 8px; text-align: center;">Քանակ</th>
            <th style="padding: 8px; text-align: right;">Գին</th>
            <th style="padding: 8px; text-align: right;">Ընդհանուր</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($items as $item): ?>
        <tr style="border-bottom: 1px solid var(--color-light-gray);">
            <td style="padding: 8px;"><?php echo htmlspecialchars($item['book_title']); ?></td>
            <td style="padding: 8px; text-align: center;"><?php echo (int)$item['quantity']; ?></td>
            <td style="padding: 8px; text-align: right;"><?php echo number_format($item['price'], 0); ?> դրամ</td>
            <td style="padding: 8px; text-align: right;"><?php echo number_format($item['price'] * $item['quantity'], 0); ?> դրամ</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background: var(--color-cream);">
            <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Ընդհանուր:</td>
            <td style="padding: 10px; text-align: right; font-weight: bold; color: var(--color-terracotta);"><?php echo number_format($order['total_amount'], 0); ?> դրամ</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top: 1rem; text-align: right;">
    <button onclick="closeOrderModal()" style="background: var(--color-sage); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
        <i class="fas fa-times"></i> Փակել
    </button>
</div>