<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cart.php';

$isLoggedIn = is_logged_in();
$userName = $_SESSION['user_name'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
$cartCount = $isLoggedIn ? cart_count($pdo, current_user_id()) : 0;
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alateva - <?php echo e($page_title ?? 'Գրախանութ'); ?></title>
    <link rel="stylesheet" href="styles.css?v=20261021-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        window.ALATEVA = {
            userId: <?php echo current_user_id(); ?>,
            csrf: <?php echo json_encode(csrf_token(), JSON_HEX_TAG); ?>
        };
    </script>
</head>
<body>
<div class="bookshop-container">
    <nav class="nav">
        <div class="nav-container">
            <h1 class="logo"><a href="index.php" style="text-decoration: none; color: inherit;">alateva</a></h1>
            <div class="nav-links">
                <?php if ($isLoggedIn): ?>
                    <div class="profile-dropdown">
                        <button type="button" class="nav-link profile-dropdown-btn">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo e($userName); ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu">
                            <?php if (is_admin()): ?>
                                <a href="admin_dashboard.php" class="dropdown-item">
                                    <i class="fas fa-shield-alt"></i> Admin Panel
                                </a>
                                <div class="dropdown-divider"></div>
                            <?php endif; ?>
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Իմ պրոֆիլը
                            </a>
                            <a href="profile.php#orders" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i> Իմ պատվերները
                            </a>
                            <a href="cart.php" class="dropdown-item">
                                <i class="fas fa-shopping-cart"></i> Զամբյուղ
                                <span class="cart-badge" style="background:#c0623b; color:#fff; border-radius:10px; padding:0 7px; font-size:0.75rem; margin-left:4px; <?php echo $cartCount > 0 ? '' : 'display:none;'; ?>"><?php echo $cartCount; ?></span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Դուրս գալ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Մուտք</a>
                    <a href="register.php" class="nav-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">Գրանցվել</a>
                <?php endif; ?>
                <?php if ($isLoggedIn): ?>
                <a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge" style="background:#c0623b; color:#fff; border-radius:10px; padding:0 7px; font-size:0.75rem; <?php echo $cartCount > 0 ? '' : 'display:none;'; ?>"><?php echo $cartCount; ?></span>
                </a>
                <?php endif; ?>
                <a href="search.php" class="nav-link <?php echo $current_page == 'search.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Որոնում
                </a>
                <a href="about.php" class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>">Մեր մասին</a>
                <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Գլխավոր</a>
            </div>
        </div>
    </nav>
