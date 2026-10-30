<?php
// admin_orders.php
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_admin();

// Բոլոր POST գործողությունները պաշտպանված են CSRF թոքենով
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

// Update order status
if(isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    if(in_array($status, ORDER_STATUSES, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, (int)($_POST['order_id'] ?? 0)]);
    }
    header('Location: admin_orders.php?msg=updated');
    exit;
}

// Update order details (edit)
if(isset($_POST['edit_order'])) {
    if(!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        header('Location: admin_orders.php?msg=invalid');
        exit;
    }
    $stmt = $pdo->prepare("UPDATE orders SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ? WHERE id = ?");
    $stmt->execute([
        $_POST['first_name'], 
        $_POST['last_name'], 
        $_POST['email'], 
        $_POST['phone'], 
        $_POST['address'], 
        $_POST['city'], 
        (int)$_POST['order_id']
    ]);
    header('Location: admin_orders.php?msg=edited');
    exit;
}

// Delete order
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
    header('Location: admin_orders.php?msg=deleted');
    exit;
}

$order_stats = $pdo->query("SELECT COUNT(*) AS c, COALESCE(SUM(total_amount), 0) AS s,
                                   COALESCE(SUM(status = 'pending'), 0) AS p, COALESCE(SUM(status = 'delivered'), 0) AS d FROM orders")->fetch();
$pg = paginate((int)$order_stats['c'], 20);
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT " . (int)$pg['per_page'] . " OFFSET " . (int)$pg['offset'])->fetchAll();
$msg = $_GET['msg'] ?? '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_order = null;
if($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_order = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Պատվերների կառավարում - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }
        .admin-container { display: flex; }
        .sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            position: fixed;
        }
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid #34495e; }
        .sidebar-header h2 { font-size: 1.3rem; }
        .sidebar-menu { list-style: none; padding: 1rem 0; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: 0.3s;
        }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #34495e; border-left: 4px solid #3498db; }
        .sidebar-menu li a i { width: 25px; margin-right: 10px; }
        .main-content { flex: 1; margin-left: 260px; padding: 1.5rem; }
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .top-bar h1 { font-size: 1.3rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; margin-left: 1rem; }
        .refresh-btn { background: #27ae60; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; margin-right: 0.5rem; }
        .card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-title { font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
        .table-wrapper { overflow-x: auto; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background: #f5f5f5; }
        .msg { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-pending { background: #ffeaa7; color: #d63031; }
        .badge-processing { background: #74b9ff; color: #0984e3; }
        .badge-shipped { background: #a29bfe; color: #6c5ce7; }
        .badge-delivered { background: #55efc4; color: #00b894; }
        select, button { padding: 0.3rem 0.6rem; border-radius: 4px; border: 1px solid #ddd; }
        button { background: #3498db; color: white; border: none; cursor: pointer; }
        .btn-small { padding: 0.3rem 0.8rem; font-size: 0.75rem; border-radius: 4px; text-decoration: none; display: inline-block; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #3498db; color: white; }
        .btn-edit { background: #27ae60; color: white; }
        .order-number { font-weight: bold; color: #2c3e50; }
        .customer-info { font-size: 0.85rem; }
        .customer-info small { color: #95a5a6; }
        .edit-form { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .edit-form h4 { margin-bottom: 1rem; color: #2c3e50; }
        .edit-form .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.8rem; margin-bottom: 0.8rem; }
        .edit-form input, .edit-form select { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-menu li a span { display: none; }
            .main-content { margin-left: 70px; }
            th, td { padding: 8px 5px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header"><h2><i class="fas fa-book"></i> Alateva</h2><p>Admin Panel</p></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_books.php"><i class="fas fa-book"></i> Գրքեր</a></li>
                <li><a href="admin_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Պատվերներ</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Օգտատերեր</a></li>
                <li><a href="admin_categories.php"><i class="fas fa-tags"></i> Կատեգորիաներ</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-shopping-cart"></i> Պատվերների կառավարում</h1>
                <div>
                    <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="admin_orders.php" class="refresh-btn"><i class="fas fa-sync-alt"></i> Թարմացնել</a>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a>
                </div>
            </div>
            
            <?php if($msg == 'updated'): ?>
            <div class="msg msg-success">✅ Պատվերի կարգավիճակը թարմացվեց</div>
            <?php elseif($msg == 'edited'): ?>
            <div class="msg msg-success">✅ Պատվերի տվյալները թարմացվեցին</div>
            <?php elseif($msg == 'invalid'): ?>
            <div class="msg" style="background:#f8d7da; color:#721c24;">❌ Էլ. հասցեն սխալ է</div>
            <?php elseif($msg == 'deleted'): ?>
            <div class="msg msg-success">✅ Պատվերը ջնջվեց</div>
            <?php endif; ?>
            
            <!-- Edit Order Form -->
            <?php if($edit_order): ?>
            <div class="card">
                <div class="card-title">
                    <span><i class="fas fa-edit"></i> Խմբագրել պատվերը #<?php echo e($edit_order['order_number']); ?></span>
                    <a href="admin_orders.php" class="btn-small btn-danger" style="color:white; text-decoration:none;">❌ Փակել</a>
                </div>
                <form method="POST" class="edit-form">
                <?php echo csrf_field(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $edit_order['id']; ?>">
                    <div class="form-row">
                        <div>
                            <label>Անուն</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_order['first_name']); ?>" required>
                        </div>
                        <div>
                            <label>Ազգանուն</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($edit_order['last_name']); ?>" required>
                        </div>
                        <div>
                            <label>Էլ. փոստ</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_order['email']); ?>" required>
                        </div>
                        <div>
                            <label>Հեռախոս</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_order['phone']); ?>" required>
                        </div>
                        <div>
                            <label>Հասցե</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($edit_order['address']); ?>" required>
                        </div>
                        <div>
                            <label>Քաղաք</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($edit_order['city']); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="edit_order" class="btn-small btn-edit" style="border:none; padding:0.5rem 1rem;">💾 Պահպանել փոփոխությունները</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-title">
                    <span><i class="fas fa-list"></i> Բոլոր պատվերները (<?php echo (int)$order_stats['c']; ?>)</span>
                    <span style="font-size:0.8rem; font-weight:normal;">📅 Վերջին պատվերը առաջինը</span>
                </div>
                
                <?php if(count($orders) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:50px;">ID</th>
                                <th>Պատվերի համար</th>
                                <th>Հաճախորդ</th>
                                <th>Հեռախոս</th>
                                <th style="width:100px;">Գումար</th>
                                <th style="width:120px;">Կարգավիճակ</th>
                                <th style="width:140px;">Ամսաթիվ</th>
                                <th style="width:200px;">Գործողություն</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr id="order-<?php echo $order['id']; ?>">
                                <td style="text-align:center;"><?php echo $order['id']; ?></td>
                                <td class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td class="customer-info">
                                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($order['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td><strong style="color:#e74c3c;"><?php echo number_format($order['total_amount'], 0); ?> դրամ</strong></td>
                                <td>
                                    <span class="badge badge-<?php echo e($order['status']); ?>">
                                        <?php 
                                            switch($order['status']) {
                                                case 'pending': echo 'Սպասման մեջ'; break;
                                                case 'processing': echo 'Մշակվում է'; break;
                                                case 'shipped': echo 'Առաքված'; break;
                                                case 'delivered': echo 'Ստացված'; break;
                                                default: echo $order['status'];
                                            }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display:flex; gap:0.3rem; flex-wrap:wrap; margin-bottom:5px;">
                <?php echo csrf_field(); ?>
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" style="padding:0.3rem;">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>📋 Սպասման մեջ</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>⚙️ Մշակվում է</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>🚚 Առաքված</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>✅ Ստացված</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-small btn-info">💾 Թարմացնել</button>
                                    </form>
                                    <div style="display:flex; gap:0.3rem; flex-wrap:wrap;">
                                        <a href="admin_orders.php?edit=<?php echo $order['id']; ?>" class="btn-small btn-edit" style="color:white; text-decoration:none; background:#27ae60;">
                                            <i class="fas fa-edit"></i> Խմբագրել
                                        </a>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-small btn-warning" style="color:white; text-decoration:none;">
                                            <i class="fas fa-eye"></i> Մանրամասն
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Ջնջե՞լ պատվերը')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$order['id']; ?>">
                                            <button type="submit" class="btn-small btn-danger" style="color:white; text-decoration:none;">
                                                <i class="fas fa-trash"></i> Ջնջել
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo pagination_html($pg); ?>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:3rem;">
                    <i class="fas fa-inbox" style="font-size:3rem; color:#95a5a6;"></i>
                    <p style="margin-top:1rem; color:#95a5a6;">Դեռևս պատվերներ չկան</p>
                    <a href="index.php" class="btn-small btn-info" style="padding:0.5rem 1rem; text-decoration:none;">Շարունակել գնումները</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics Card -->
            <?php if((int)$order_stats['c'] > 0):
                $total_sum = (float)$order_stats['s'];
                $pending_count = (int)$order_stats['p'];
                $delivered_count = (int)$order_stats['d'];
            ?>
            <div class="card">
                <div class="card-title"><i class="fas fa-chart-bar"></i> Վիճակագրություն</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; text-align: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #3498db;"><?php echo (int)$order_stats['c']; ?></div>
                        <div style="color: #7f8c8d;">Ընդհանուր պատվերներ</div>
                    </div>
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #f39c12;"><?php echo $pending_count; ?></div>
                        <div style="color: #7f8c8d;">Սպասման մեջ</div>
                    </div>
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #27ae60;"><?php echo $delivered_count; ?></div>
                        <div style="color: #7f8c8d;">Առաքված / Ստացված</div>
                    </div>
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?php echo number_format($total_sum, 0); ?> դրամ</div>
                        <div style="color: #7f8c8d;">Ընդհանուր եկամուտ</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>