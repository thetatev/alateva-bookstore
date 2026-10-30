<?php
// cart_api.php - զամբյուղի API (JSON). Գործողություններ՝ list, add, set, remove, promo
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_once 'includes/cart.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Սխալ հարցում'], 405);
}
if (!is_logged_in()) {
    json_response(['success' => false, 'error' => 'login'], 401);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    json_response(['success' => false, 'error' => 'Սեսիան ավարտվել է։ Թարմացրեք էջը'], 403);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}
$action = (string)($data['action'] ?? 'list');
$userId = current_user_id();
$extra = [];

try {
    switch ($action) {
        case 'list':
            break;

        case 'add':
        case 'set':
            $bookId = (int)($data['book_id'] ?? 0);
            $qty = (int)($data['quantity'] ?? 1);

            $stmt = $pdo->prepare('SELECT stock FROM books WHERE id = ?');
            $stmt->execute([$bookId]);
            $row = $stmt->fetch();
            if (!$row) {
                json_response(['success' => false, 'error' => 'Գիրքը չի գտնվել'], 404);
            }
            $stock = (int)$row['stock'];
            if ($stock < 1) {
                json_response(['success' => false, 'error' => 'Գիրքը սպառված է'], 409);
            }
            $max = min(MAX_QTY_PER_ITEM, $stock);

            if ($action === 'add') {
                $current = cart_get_qty($pdo, $userId, $bookId);
                if ($current >= $max) {
                    json_response(['success' => false, 'error' => 'Այս գրքից հնարավոր է վերցնել առավելագույնը ' . $max . ' հատ'], 409);
                }
                $qty = min($max, $current + max(1, $qty));
            } else {
                $qty = min($max, $qty);
            }
            cart_set_qty($pdo, $userId, $bookId, $qty);
            break;

        case 'remove':
            cart_set_qty($pdo, $userId, (int)($data['book_id'] ?? 0), 0);
            break;

        case 'promo':
            $code = strtoupper(trim((string)($data['code'] ?? '')));
            $cart = cart_load($pdo, $userId);
            $check = promo_discount($pdo, $userId, $code, $cart['subtotal']);
            if ($code !== '' && $check['valid']) {
                $_SESSION['promo_code'] = $code;
            } else {
                unset($_SESSION['promo_code']);
            }
            $extra = ['promo_ok' => $check['valid'], 'promo_message' => $check['message']];
            break;

        default:
            json_response(['success' => false, 'error' => 'Անհայտ գործողություն'], 400);
    }

    $cart = cart_load($pdo, $userId);
    $promoCode = (string)($_SESSION['promo_code'] ?? '');
    $promo = promo_discount($pdo, $userId, $promoCode, $cart['subtotal']);
    if ($promoCode !== '' && !$promo['valid']) {
        unset($_SESSION['promo_code']);
        $promoCode = '';
    }

    json_response($extra + [
        'success'    => true,
        'items'      => $cart['items'],
        'subtotal'   => $cart['subtotal'],
        'count'      => $cart['count'],
        'discount'   => $promo['valid'] ? (float)$promo['discount'] : 0,
        'promo_code' => $promoCode,
    ]);
} catch (Throwable $ex) {
    error_log('cart_api failed: ' . $ex->getMessage());
    json_response(['success' => false, 'error' => 'Սերվերի սխալ։ Համոզվեք, որ database_upgrade.sql-ը գործարկված է'], 500);
}
