<?php
// order_details.php
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_admin();

$order_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if(!$order) {
    header('Location: admin_orders.php');
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Պատվերի մանրամասներ - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #ecf0f1; }
        .status { padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.9rem; }
        .status-pending { background: #ffeaa7; color: #d63031; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; }
        .total { font-size: 1.3rem; font-weight: bold; color: #e74c3c; }
        .btn { padding: 0.6rem 1.2rem; background: #3498db; color: white; text-decoration: none; border-radius: 6px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Պատվեր #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <span class="status status-<?php echo e($order['status']); ?>"><?php echo e($order['status']); ?></span>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3>Հաճախորդի տվյալներ</h3>
                    <p><strong>Անուն:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                    <p><strong>Էլ. փոստ:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Հեռախոս:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
                <div>
                    <h3>Առաքման տվյալներ</h3>
                    <p><strong>Հասցե:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>Քաղաք:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                    <p><strong>Առաքման եղանակ:</strong> <?php echo e($order['shipping_method']); ?></p>
                    <p><strong>Վճարման եղանակ:</strong> <?php echo e($order['payment_method']); ?></p>
                </div>
            </div>
            
            <h3>Պատվերի ապրանքներ</h3>
            <table>
                <thead><tr><th>Անվանում</th><th>Քանակ</th><th>Գին</th><th>Ընդհանուր</th></tr></thead>
                <tbody>
                    <?php foreach($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['book_title']); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'], 0); ?> դրամ</td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 0); ?> դրամ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right"><strong>Ընդհանուր:</strong></td><td class="total"><?php echo number_format($order['total_amount'], 0); ?> դրամ</td></tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 2rem;">
                <a href="admin_orders.php" class="btn">← Վերադառնալ պատվերներին</a>
            </div>
        </div>
    </div>
</body>
</html>