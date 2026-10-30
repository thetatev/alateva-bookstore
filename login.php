<?php
// login.php
$page_title = 'Մուտք';
require_once 'config/database.php';
require_once 'includes/helpers.php';

$next = safe_next($_GET['next'] ?? ($_POST['next'] ?? ''));

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? 'admin_dashboard.php' : ($next !== '' ? $next : 'profile.php')));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Պարզ սահմանափակում՝ գաղտնաբառ գուշակելու դեմ (5 սխալ -> 5 րոպե)
    $lockedUntil = $_SESSION['login_lock_until'] ?? 0;

    if ($lockedUntil > time()) {
        $error = 'Չափից շատ անհաջող փորձ։ Խնդրում ենք փորձել մի քանի րոպեից։';
    } elseif ($email === '' || $password === '') {
        $error = 'Խնդրում եմ լրացնել բոլոր դաշտերը';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);

            $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

            if ((int)$user['is_admin'] === 1) {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: ' . ($next !== '' ? $next : 'profile.php'));
            }
            exit;
        }

        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 300;
            $_SESSION['login_fails'] = 0;
        }
        $error = 'Սխալ էլ. հասցե կամ գաղտնաբառ';
    }
}

require_once 'includes/header.php';
?>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2 class="auth-title">Մուտք գործել</h2>
                <p class="auth-subtitle">Մուտքագրեք ձեր տվյալները</p>

                <?php if (isset($_GET['reset'])): ?>
                    <div class="message success">✅ Գաղտնաբառը փոխվեց։ Մուտք գործեք նոր գաղտնաբառով</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="next" value="<?php echo e($next); ?>">

                    <div class="form-group">
                        <label for="loginEmail">Էլեկտրոնային հասցե</label>
                        <input type="email" id="loginEmail" name="email" class="form-input" placeholder="example@mail.com"
                               value="<?php echo e($_POST['email'] ?? ''); ?>" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Գաղտնաբառ</label>
                        <div class="password-wrapper">
                            <input type="password" id="loginPassword" name="password" class="form-input"
                                   placeholder="Մուտքագրեք գաղտնաբառը" autocomplete="current-password" required>
                        </div>
                    </div>

                    <div style="text-align:right; margin-bottom:1rem;">
                        <a href="forgot_password.php">Մոռացե՞լ եք գաղտնաբառը</a>
                    </div>

                    <button type="submit" class="auth-button primary">Մուտք գործել</button>

                    <div class="auth-switch">
                        <p>Չունեք հաշիվ? <a href="register.php">Գրանցվել</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
