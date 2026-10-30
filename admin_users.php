<?php
// admin_users.php
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_admin();

// Բոլոր POST գործողությունները պաշտպանված են CSRF թոքենով
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0")->execute([$id]);
    header('Location: admin_users.php?msg=deleted');
    exit;
}

$pg = paginate((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(), 25);
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT " . (int)$pg['per_page'] . " OFFSET " . (int)$pg['offset'])->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Օգտատերերի կառավարում - Admin</title>
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
        .sidebar-menu { list-style: none; padding: 1rem 0; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
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
        }
        .logout-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; margin-left: 1rem; }
        .card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-title { font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; }
        .badge-admin { background: #27ae60; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; }
        .badge-user { background: #95a5a6; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; }
        .msg { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; background: #d4edda; color: #155724; }
        .btn-danger { background: #e74c3c; color: white; padding: 0.3rem 0.6rem; border-radius: 4px; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header"><h2><i class="fas fa-book"></i> Alateva</h2><p>Admin Panel</p></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_books.php"><i class="fas fa-book"></i> Գրքեր</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Պատվերներ</a></li>
                <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Օգտատերեր</a></li>
                <li><a href="admin_categories.php"><i class="fas fa-tags"></i> Կատեգորիաներ</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-users"></i> Օգտատերերի կառավարում</h1>
                <div><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="logout.php" class="logout-btn">Դուրս գալ</a></div>
            </div>
            
            <?php if($msg == 'deleted'): ?>
            <div class="msg">✅ Օգտատերը հաջողությամբ ջնջվեց</div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-list"></i> Բոլոր օգտատերերը</div>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Անուն Ազգանուն</th><th>Էլ. հասցե</th><th>Հեռախոս</th><th>Գրանցվել է</th><th>Կարգավիճակ</th><th>Գործողություն</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($user['member_since'])); ?></td>
                            <td><?php echo $user['is_admin'] ? '<span class="badge-admin">Ադմին</span>' : '<span class="badge-user">Օգտատեր</span>'; ?></td>
                            <td>
                                <?php if(!$user['is_admin']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Համոզվա՞ծ եք ջնջել')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="delete_id" value="<?php echo (int)$user['id']; ?>">
                                    <button type="submit" class="btn-danger" style="padding:0.3rem 0.6rem; border-radius:4px; text-decoration:none; border:none; cursor:pointer;">🗑️ Ջնջել</button>
                                </form>
                                <?php else: ?>
                                <span style="color:#95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php echo pagination_html($pg); ?>
            </div>
        </div>
    </div>
</body>
</html>