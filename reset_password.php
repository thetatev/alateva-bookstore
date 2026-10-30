<?php
// reset_password.php - նոր գաղտնաբառի սահմանում նամակի հղումով
$page_title = 'Նոր գաղտնաբառ';
require_once 'config/database.php';
require_once 'includes/helpers.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;
$error = '';

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()');
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch() ?: null;
    } catch (Throwable $ex) {
        error_log('reset_password lookup failed: ' . $ex->getMessage());
    }
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Գաղտնաբառը պետք է լինի առնվազն 8 նիշ';
    } elseif ($password !== $confirm) {
        $error = 'Գաղտնաբառերը չեն համապատասխանում';
    } else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
        // Թոքենը մեկանգամյա է
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$reset['user_id']]);
        unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
        header('Location: login.php?reset=1');
        exit;
    }
}

require_once 'includes/header.php';
?>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2 class="auth-title">Նոր գաղտնաբառ</h2>

                <?php if (!$reset): ?>
                    <div class="message error">Հղումը անվավեր է կամ ժամկետն անցել է։</div>
                    <div class="auth-switch"><p><a href="forgot_password.php">Ստանալ նոր հղում</a></p></div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="token" value="<?php echo e($token); ?>">
                        <div class="form-group">
                            <label for="password">Նոր գաղտնաբառ (առնվազն 8 նիշ)</label>
                            <input type="password" id="password" name="password" class="form-input" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Հաստատել գաղտնաբառը</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8" autocomplete="new-password" required>
                        </div>
                        <button type="submit" class="auth-button primary">Պահպանել</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
