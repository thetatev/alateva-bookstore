<?php
// admin_categories.php
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
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    header('Location: admin_categories.php?msg=deleted');
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $name = trim($_POST['name'] ?? '');
    $name_hy = trim($_POST['name_hy'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Icon-ը օգտագործվում է CSS class-ում՝ թույլատրվում են միայն Font Awesome class-երի նիշեր
    if($name === '' || $name_hy === '' || ($icon !== '' && !preg_match('/^[a-z0-9\- ]{1,60}$/i', $icon))) {
        header('Location: admin_categories.php?msg=invalid');
        exit;
    }
    
    if((int)($_POST['cat_id'] ?? 0) > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name=?, name_hy=?, icon=?, description=? WHERE id=?");
        $stmt->execute([$name, $name_hy, $icon, $description, (int)$_POST['cat_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, name_hy, icon, description) VALUES (?,?,?,?)");
        $stmt->execute([$name, $name_hy, $icon, $description]);
    }
    header('Location: admin_categories.php?msg=saved');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$edit_cat = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Կատեգորիաների կառավարում - Admin</title>
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
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 6px; }
        .btn { padding: 0.6rem 1.2rem; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; }
        .msg { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; }
        .msg-success { background: #d4edda; color: #155724; }
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
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Օգտատերեր</a></li>
                <li><a href="admin_categories.php" class="active"><i class="fas fa-tags"></i> Կատեգորիաներ</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-tags"></i> Կատեգորիաների կառավարում</h1>
                <div><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="logout.php" class="logout-btn">Դուրս գալ</a></div>
            </div>
            
            <?php if($msg == 'saved'): ?>
            <div class="msg msg-success">✅ Կատեգորիան հաջողությամբ պահպանվեց</div>
            <?php elseif($msg == 'invalid'): ?>
            <div class="msg" style="background:#f8d7da; color:#721c24;">❌ Տվյալները սխալ են</div>
            <?php elseif($msg == 'deleted'): ?>
            <div class="msg msg-success">✅ Կատեգորիան հաջողությամբ ջնջվեց</div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-title"><?php echo $edit_cat ? '✏️ Խմբագրել կատեգորիա' : '➕ Ավելացնել նոր կատեգորիա'; ?></div>
                <form method="POST">
                <?php echo csrf_field(); ?>
                    <?php if($edit_cat): ?>
                    <input type="hidden" name="cat_id" value="<?php echo $edit_cat['id']; ?>">
                    <?php endif; ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group"><label>Name (English)</label><input type="text" name="name" value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>" required></div>
                        <div class="form-group"><label>Name (Հայերեն)</label><input type="text" name="name_hy" value="<?php echo htmlspecialchars($edit_cat['name_hy'] ?? ''); ?>" required></div>
                        <div class="form-group"><label>Icon (fa-...)</label><input type="text" name="icon" value="<?php echo htmlspecialchars($edit_cat['icon'] ?? 'fa-book'); ?>" placeholder="fa-magic"></div>
                    </div>
                    <div class="form-group"><label>Նկարագրություն</label><textarea name="description" rows="2"><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea></div>
                    <button type="submit" name="save_category" class="btn">💾 Պահպանել</button>
                    <?php if($edit_cat): ?>
                    <a href="admin_categories.php" class="btn" style="background:#95a5a6;">❌ Չեղարկել</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-list"></i> Կատեգորիաների ցանկ</div>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Name (EN)</th><th>Name (HY)</th><th>Icon</th><th>Գործողություն</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($categories as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><?php echo htmlspecialchars($cat['name_hy']); ?></td>
                            <td><i class="fas <?php echo e($cat['icon']); ?>"></i> <?php echo e($cat['icon']); ?></td>
                            <td>
                                <a href="admin_categories.php?edit=<?php echo $cat['id']; ?>" class="btn" style="background:#3498db; padding:0.3rem 0.6rem; font-size:0.8rem;">✏️</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Համոզվա՞ծ եք ջնջել')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="delete_id" value="<?php echo (int)$cat['id']; ?>">
                                    <button type="submit" class="btn" style="background:#e74c3c; padding:0.3rem 0.6rem; font-size:0.8rem;">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>