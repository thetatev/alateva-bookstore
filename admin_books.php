<?php
// admin_books.php
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_once 'includes/notify.php';
require_admin();

// Բոլոր POST գործողությունները պաշտպանված են CSRF թոքենով
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$id]);
    header('Location: admin_books.php?msg=deleted');
    exit;
}

if(isset($_POST['update_stock'])) {
    $id = (int)$_POST['book_id'];
    $new_stock = max(0, (int)$_POST['stock']);
    $old = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
    $old->execute([$id]);
    $old_stock = (int)$old->fetchColumn();

    $stmt = $pdo->prepare("UPDATE books SET stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $id]);

    if($old_stock < 1 && $new_stock > 0) {
        notify_back_in_stock($pdo, $id);
    }
    header('Location: admin_books.php?msg=stock_updated');
    exit;
}

$edit_book = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_book = $stmt->fetch();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $title = trim($_POST['title'] ?? '');
    $title_hy = trim($_POST['title_hy'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0) ?: null;
    $cover_image = trim($_POST['cover_image'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '') ?: null;
    $pages = (int)($_POST['pages'] ?? 0) ?: null;
    $year = (int)($_POST['year'] ?? 0) ?: null;

    // Վալիդացիա՝ դատարկ անուններ, բացասական գին/պաշար չեն թույլատրվում
    if($title === '' || $title_hy === '' || $author === '' || $price <= 0 || $stock < 0) {
        header('Location: admin_books.php?msg=invalid');
        exit;
    }
    // Շապիկի հղումը՝ վտանգավոր սխեմաները (javascript:, data:, vbscript:) արգելվում են
    if($cover_image !== '' && preg_match('/^\s*(javascript|vbscript|data):/i', $cover_image)) {
        header('Location: admin_books.php?msg=invalid');
        exit;
    }

    if((int)($_POST['book_id'] ?? 0) > 0) {
        $old = $pdo->prepare("SELECT stock FROM books WHERE id = ?");
        $old->execute([(int)$_POST['book_id']]);
        $old_stock = (int)$old->fetchColumn();

        $stmt = $pdo->prepare("UPDATE books SET title=?, title_hy=?, author=?, description=?, price=?, stock=?, category_id=?, cover_image=?, publisher=?, pages=?, year=? WHERE id=?");
        $stmt->execute([$title, $title_hy, $author, $description, $price, $stock, $category_id, $cover_image, $publisher, $pages, $year, (int)$_POST['book_id']]);
        if($old_stock < 1 && $stock > 0) {
            notify_back_in_stock($pdo, (int)$_POST['book_id']);
        }
        header('Location: admin_books.php?msg=updated');
        exit;
    } else {
        $stmt = $pdo->prepare("INSERT INTO books (title, title_hy, author, description, price, stock, category_id, cover_image, publisher, pages, year) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$title, $title_hy, $author, $description, $price, $stock, $category_id, $cover_image, $publisher, $pages, $year]);
        header('Location: admin_books.php?msg=added');
        exit;
    }
}

$pg = paginate((int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(), 20);
$books = $pdo->query("SELECT b.*, c.name_hy as cat_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.id DESC LIMIT " . (int)$pg['per_page'] . " OFFSET " . (int)$pg['offset'])->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Գրքերի կառավարում - Admin</title>
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
        .card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-title { font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 2px solid #ecf0f1; padding-bottom: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .btn { padding: 0.6rem 1.2rem; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .btn-small { padding: 0.3rem 0.8rem; font-size: 0.8rem; }
        .table-wrapper { overflow-x: auto; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
        th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background: #f5f5f5; }
        .msg { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .stock-low { color: #e74c3c; font-weight: bold; }
        .stock-normal { color: #27ae60; font-weight: bold; }
        .stock-form { display: inline-flex; gap: 0.5rem; align-items: center; }
        .stock-input { width: 70px; padding: 0.3rem; text-align: center; border: 1px solid #ddd; border-radius: 4px; }
        .book-cover { width: 50px; height: 65px; object-fit: cover; border-radius: 4px; background: #ecf0f1; }
        .action-buttons { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .sidebar-header p, .sidebar-menu li a span { display: none; }
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header"><h2><i class="fas fa-book"></i> Alateva</h2><p>Admin Panel</p></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_books.php" class="active"><i class="fas fa-book"></i> Գրքեր</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Պատվերներ</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Օգտատերեր</a></li>
                <li><a href="admin_categories.php"><i class="fas fa-tags"></i> Կատեգորիաներ</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-book"></i> Գրքերի կառավարում</h1>
                <div>
                    <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Դուրս գալ</a>
                </div>
            </div>
            
            <?php if($msg == 'added'): ?>
            <div class="msg msg-success">✅ Նոր գիրքը հաջողությամբ ավելացվեց</div>
            <?php elseif($msg == 'updated'): ?>
            <div class="msg msg-success">✅ Գիրքը հաջողությամբ թարմացվեց</div>
            <?php elseif($msg == 'deleted'): ?>
            <div class="msg msg-success">✅ Գիրքը հաջողությամբ ջնջվեց</div>
            <?php elseif($msg == 'stock_updated'): ?>
            <div class="msg msg-success">✅ Պաշարը թարմացվեց</div>
            <?php elseif($msg == 'invalid'): ?>
            <div class="msg" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;">❌ Տվյալները սխալ են (անուն, հեղինակ, դրական գին, պաշար, շապիկի հղում)</div>
            <?php endif; ?>
            
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-title"><?php echo $edit_book ? '✏️ Խմբագրել գիրքը' : '➕ Ավելացնել նոր գիրք'; ?></div>
                <form method="POST">
                <?php echo csrf_field(); ?>
                    <?php if($edit_book): ?>
                    <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Title (English)</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_book['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Title (Հայերեն)</label>
                            <input type="text" name="title_hy" value="<?php echo htmlspecialchars($edit_book['title_hy'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Հեղինակ</label>
                            <input type="text" name="author" value="<?php echo htmlspecialchars($edit_book['author'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Գին (դրամ)</label>
                            <input type="number" name="price" value="<?php echo e($edit_book['price'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Քանակ պահեստում</label>
                            <input type="number" name="stock" value="<?php echo $edit_book['stock'] ?? 0; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Կատեգորիա</label>
                            <select name="category_id">
                                <option value="">Առանց կատեգորիայի</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (($edit_book['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name_hy']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Նկարի URL (դատարկ՝ գեներացված կազմ)</label>
                            <input type="text" name="cover_image" value="<?php echo htmlspecialchars($edit_book['cover_image'] ?? ''); ?>" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label>Հրատարակչություն</label>
                            <input type="text" name="publisher" maxlength="150" value="<?php echo htmlspecialchars($edit_book['publisher'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Էջեր</label>
                            <input type="number" name="pages" min="1" max="10000" value="<?php echo htmlspecialchars((string)($edit_book['pages'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Տարեթիվ</label>
                            <input type="number" name="year" min="1" max="2100" value="<?php echo htmlspecialchars((string)($edit_book['year'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Նկարագրություն</label>
                        <textarea name="description" rows="3" style="width:100%; padding:0.6rem; border:1px solid #ddd; border-radius:6px;"><?php echo htmlspecialchars($edit_book['description'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="save_book" class="btn">💾 Պահպանել</button>
                    <?php if($edit_book): ?>
                    <a href="admin_books.php" class="btn" style="background:#95a5a6; margin-left:0.5rem;">❌ Չեղարկել</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Books List -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-list"></i> Գրքերի ցանկ 
                    <span style="font-size:0.8rem; font-weight:normal;">(🟢 կանաչ = նորմալ պաշար (≥5) | 🔴 կարմիր = քիչ պաշար (<5))</span>
                </div>
                
                <?php if(count($books) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:50px;">ID</th>
                                <th style="width:80px;">Նկար</th>
                                <th>Անվանում</th>
                                <th style="min-width:130px;">Հեղինակ</th>
                                <th style="width:100px;">Գին</th>
                                <th style="width:150px;">Պաշար</th>
                                <th style="width:140px;">Գործողություն</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($books as $book): ?>
                            <tr>
                                <td style="text-align:center;"><?php echo $book['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars(book_cover($book)); ?>" class="book-cover" alt="" loading="lazy">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($book['title_hy']); ?></strong>
                                    <br><small style="color:#95a5a6;"><?php echo htmlspecialchars($book['title']); ?></small>
                                    <?php
                                        $detail_parts = [];
                                        if(!empty($book['publisher'])) $detail_parts[] = htmlspecialchars($book['publisher']);
                                        if(!empty($book['pages'])) $detail_parts[] = (int)$book['pages'] . ' էջ';
                                        if(!empty($book['year'])) $detail_parts[] = (int)$book['year'];
                                    ?>
                                    <br><small style="color:#7f8c8d;"><?php echo $detail_parts ? implode(' · ', $detail_parts) : '⚠️ Հրատարակչություն/էջեր/տարեթիվ՝ չլրացված'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><strong><?php echo number_format($book['price'], 0); ?> դրամ</strong></td>
                                <td>
                                    <?php if($book['stock'] < 5): ?>
                                    <div class="stock-low">🔴 <?php echo $book['stock']; ?> հատ</div>
                                    <?php else: ?>
                                    <div class="stock-normal">🟢 <?php echo $book['stock']; ?> հատ</div>
                                    <?php endif; ?>
                                    <form method="POST" class="stock-form" style="margin-top:8px;">
                <?php echo csrf_field(); ?>
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <input type="number" name="stock" value="<?php echo $book['stock']; ?>" class="stock-input" min="0">
                                        <button type="submit" name="update_stock" class="btn btn-small" style="background:#27ae60;">Թարմացնել</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_books.php?edit=<?php echo $book['id']; ?>" class="btn btn-small" style="background:#3498db; text-align:center; text-decoration:none; color:white;">
                                            <i class="fas fa-edit"></i> Խմբագրել
                                        </a>
                                        <form method="POST" style="display:contents;" onsubmit="return confirm(<?php echo js_attr('Համոզվա՞ծ եք ջնջել «' . $book['title_hy'] . '» գիրքը'); ?>)">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$book['id']; ?>">
                                            <button type="submit" class="btn btn-small" style="background:#e74c3c; text-align:center; text-decoration:none; color:white;">
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
                    <i class="fas fa-book-open" style="font-size:3rem; color:#95a5a6;"></i>
                    <p style="margin-top:1rem; color:#95a5a6;">Գրքեր չկան</p>
                    <a href="admin_books.php" class="btn" style="margin-top:1rem;">➕ Ավելացնել գիրք</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>