<?php
// forgot_password.php - վերականգնման հղման հարցում
$page_title = 'Մոռացել եմ գաղտնաբառը';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/mail.php';

if (is_logged_in()) {
    header('Location: profile.php#security');
    exit;
}

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Մուտքագրեք վավեր էլ. հասցե';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, first_name FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Նույն օգտատիրոջ համար՝ առավելագույնը 1 հարցում րոպեում
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)');
                $stmt->execute([$user['id']]);

                if ((int)$stmt->fetchColumn() === 0) {
                    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

                    $token = bin2hex(random_bytes(32));
                    // Բազայում պահվում է միայն հեշը, ոչ թե բուն թոքենը
                    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                        ->execute([$user['id'], hash('sha256', $token)]);

                    $link = app_url('reset_password.php?token=' . $token);
                    send_mail(
                        $email,
                        'Գաղտնաբառի վերականգնում',
                        '<p>Բարև ' . e($user['first_name']) . ',</p>'
                        . '<p>Ստացանք ձեր գաղտնաբառը վերականգնելու հարցում։ Հղումը գործում է 1 ժամ.</p>'
                        . '<p><a href="' . e($link) . '">Սահմանել նոր գաղտնաբառ</a></p>'
                        . '<p style="color:#7f8c8d;">Եթե դուք չեք խնդրել, պարզապես անտեսեք այս նամակը։</p>'
                    );
                }
            }
            // Նույն պատասխանը՝ անկախ նրանից, կա այդ էլ. հասցեն թե ոչ (օգտատերերի ցանկը չբացահայտելու համար)
            $sent = true;
        } catch (Throwable $ex) {
            error_log('forgot_password failed: ' . $ex->getMessage());
            $error = 'Սերվերի սխալ։ Համոզվեք, որ database_upgrade.sql-ը գործարկված է';
        }
    }
}

require_once 'includes/header.php';
?>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2 class="auth-title">Գաղտնաբառի վերականգնում</h2>

                <?php if ($sent): ?>
                    <div class="message success">Եթե այս էլ. հասցեով հաշիվ կա, մենք ուղարկել ենք վերականգնման հղում։ Ստուգեք նաև «Spam» թղթապանակը։</div>
                    <div class="auth-switch"><p><a href="login.php">← Վերադառնալ մուտքի էջ</a></p></div>
                <?php else: ?>
                    <p class="auth-subtitle">Մուտքագրեք ձեր էլ. հասցեն, և մենք կուղարկենք հղում</p>

                    <?php if ($error): ?>
                        <div class="message error"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="email">Էլեկտրոնային հասցե</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo e($_POST['email'] ?? ''); ?>" autocomplete="email" required>
                        </div>
                        <button type="submit" class="auth-button primary">Ուղարկել հղումը</button>
                        <div class="auth-switch"><p><a href="login.php">← Վերադառնալ մուտքի էջ</a></p></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
