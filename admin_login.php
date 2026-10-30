<?php
// admin_login.php
require_once 'config/database.php';
require_once 'includes/helpers.php';

if (is_admin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_admin = 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Նույն սեսիայի բանալիները, ինչ սովորական մուտքի դեպքում (admin էջերը ստուգում են user_id + is_admin)
            login_user($admin);
            $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            header('Location: admin_dashboard.php');
            exit;
        }
        $error = 'Սխալ էլ. հասցե կամ գաղտնաբառ';
    } else {
        $error = 'Խնդրում եմ լրացնել բոլոր դաշտերը';
    }
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alateva - Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-box h1 { text-align: center; margin-bottom: 0.5rem; color: #333; }
        .login-box p { text-align: center; color: #666; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; }
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .error {
            background: #fee;
            color: #c00;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover { color: #667eea; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1><i class="fas fa-shield-alt"></i> Alateva Admin</h1>
        <p>Մուտք գործեք վարչական վահանակ</p>
        
        <?php if($error): ?>
            <div class="error"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Էլեկտրոնային հասցե</label>
                <input type="email" name="email" required placeholder="admin@alateva.com">
            </div>
            <div class="form-group">
                <label>Գաղտնաբառ</label>
                <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
            </div>
            <button type="submit" class="btn">Մուտք գործել</button>
        </form>
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Վերադառնալ խանութ</a>
    </div>
</body>
</html>