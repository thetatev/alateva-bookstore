<?php
// admin_dashboard.php
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_admin();

// Բոլոր POST գործողությունները պաշտպանված են CSRF թոքենով
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

$total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM books WHERE stock < 5")->fetchColumn();
$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
$low_stock_books = $pdo->query("SELECT id, title_hy, stock FROM books WHERE stock < 5 ORDER BY stock ASC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alateva</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
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
        .view-site { background: #27ae60; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 0.9rem; margin-top: 0.3rem; }
        .stat-icon { font-size: 2.5rem; color: #3498db; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-title { font-size: 1.2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ecf0f1; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 12px; text-align: left; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background: #f5f5f5; }
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
        .view-details { background: #27ae60; padding: 0.3rem 0.6rem; border-radius: 4px; color: white; text-decoration: none; font-size: 0.8rem; display: inline-block; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-menu li a span { display: none; }
            .main-content { margin-left: 70px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            th, td { padding: 8px 5px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-book"></i> Alateva</h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="admin_books.php"><i class="fas fa-book"></i> <span>Գրքեր</span></a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> <span>Պատվերներ</span></a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> <span>Օգտատերեր</span></a></li>
                <li><a href="admin_categories.php"><i class="fas fa-tags"></i> <span>Կատեգորիաներ</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Դուրս գալ</span></a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-tachometer-alt"></i> Վարչական վահանակ</h1>
                <div>
                    <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php" class="view-site"><i class="fas fa-eye"></i> Դիտել կայքը</a>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?php echo $total_books; ?></div>
                        <div class="stat-label">Ընդհանուր գրքեր</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Ընդհանուր օգտատերեր</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Ընդհանուր պատվերներ</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?php echo $low_stock; ?></div>
                        <div class="stat-label">Քիչ պաշարով գրքեր</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            
            <!-- Low Stock Books -->
            <?php if(count($low_stock_books) > 0): ?>
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i> 
                    Քիչ պաշարով գրքեր (5-ից քիչ)
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Գրքի անուն</th>
                                <th>Մնացած քանակ</th>
                                <th>Գործողություն</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($low_stock_books as $book): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title_hy']); ?></td>
                                <td style="color:#e74c3c; font-weight:bold;"><?php echo $book['stock']; ?> հատ</td>
                                <td><a href="admin_books.php?edit=<?php echo $book['id']; ?>" class="view-details"><i class="fas fa-plus"></i> Համալրել</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-clock"></i> 
                    Վերջին պատվերներ
                </div>
                <?php if(count($recent_orders) > 0): ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Պատվերի համար</th>
                                <th>Հաճախորդ</th>
                                <th>Հեռախոս</th>
                                <th>Գումար</th>
                                <th>Կարգավիճակ</th>
                                <th>Ամսաթիվ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                    <br><small style="color:#95a5a6;"><?php echo htmlspecialchars($order['email']); ?></small>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:2rem; color:#95a5a6;">
                    <i class="fas fa-inbox" style="font-size:2rem;"></i>
                    <p style="margin-top:0.5rem;">Դեռևս պատվերներ չկան</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>