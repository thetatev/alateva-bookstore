<?php
// register.php
$page_title = 'Գրանցվել';
require_once 'config/database.php';
require_once 'includes/helpers.php';

if (is_logged_in()) {
    header('Location: profile.php');
    exit;
}

$error = '';
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $phone      = trim($_POST['phone'] ?? '');

    $old = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'phone' => $phone];

    if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
        $error = 'Խնդրում եմ լրացնել բոլոր պարտադիր դաշտերը';
    } elseif (mb_strlen($first_name) > 100 || mb_strlen($last_name) > 100) {
        $error = 'Անունը կամ ազգանունը չափազանց երկար է';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $error = 'Էլ. հասցեն սխալ է';
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
        $error = 'Հեռախոսահամարը սխալ է';
    } elseif ($password !== $confirm) {
        $error = 'Գաղտնաբառերը չեն համապատասխանում';
    } elseif (strlen($password) < 8) {
        $error = 'Գաղտնաբառը պետք է լինի առնվազն 8 նիշ';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Այս էլ. հասցեն արդեն գրանցված է';
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$first_name, $last_name, $email, $hashed, $phone]);

                login_user([
                    'id' => $pdo->lastInsertId(),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'is_admin' => 0,
                ]);
                header('Location: profile.php');
                exit;
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $error = 'Այս էլ. հասցեն արդեն գրանցված է';
                } else {
                    error_log('Register failed: ' . $ex->getMessage());
                    $error = 'Սխալ է տեղի ունեցել։ Փորձեք կրկին';
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <h2 class="auth-title">Ստեղծել հաշիվ</h2>
                <p class="auth-subtitle">Լրացրեք տվյալները</p>

                <?php if ($error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <?php echo csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Անուն</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" maxlength="100"
                                   value="<?php echo e($old['first_name']); ?>" autocomplete="given-name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Ազգանուն</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" maxlength="100"
                                   value="<?php echo e($old['last_name']); ?>" autocomplete="family-name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Էլեկտրոնային հասցե</label>
                        <input type="email" id="email" name="email" class="form-input" maxlength="100"
                               value="<?php echo e($old['email']); ?>" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Գաղտնաբառ (առնվազն 8 նիշ)</label>
                        <input type="password" id="password" name="password" class="form-input" minlength="8"
                               autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Հաստատել գաղտնաբառը</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8"
                               autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Հեռախոսահամար</label>
                        <input type="tel" id="phone" name="phone" class="form-input" maxlength="20"
                               value="<?php echo e($old['phone']); ?>" autocomplete="tel">
                    </div>

                    <button type="submit" class="auth-button primary">Գրանցվել</button>

                    <div class="auth-switch">
                        <p>Արդեն ունեք հաշիվ? <a href="login.php">Մուտք գործել</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
