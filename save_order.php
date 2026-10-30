<?php
// save_order.php - պատվերի պահպանում
// Կարևոր՝ գները, առաքման արժեքը, զեղչը և ընդհանուր գումարը հաշվարկվում են ՍԵՐՎԵՐՈՒՄ՝ տվյալների բազայի գներով։
// Բրաուզերից ընդունվում են միայն գրքի id-ն և քանակը։
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_once 'includes/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Սխալ հարցում'], 405);
}
if (!is_logged_in()) {
    json_response(['success' => false, 'error' => 'Պատվեր անելու համար մուտք գործեք'], 401);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    json_response(['success' => false, 'error' => 'Սեսիան ավարտվել է։ Թարմացրեք էջը'], 403);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    json_response(['success' => false, 'error' => 'Տվյալները չեն ստացվել'], 400);
}

$field = function ($key, $max) use ($data) {
    return mb_substr(trim((string)($data[$key] ?? '')), 0, $max);
};

$first_name      = $field('first_name', 100);
$last_name       = $field('last_name', 100);
$email           = $field('email', 100);
$phone           = $field('phone', 50);
$address         = $field('address', 255);
$city            = $field('city', 100);
$postal_code     = $field('postal_code', 20);
$notes           = $field('notes', 1000);
$shipping_method = (string)($data['shipping_method'] ?? '');
$payment_method  = (string)($data['payment_method'] ?? '');
$promo_code      = (string)($_SESSION['promo_code'] ?? '');

if (!array_key_exists($shipping_method, SHIPPING_PRICES)) {
    json_response(['success' => false, 'error' => 'Առաքման եղանակը սխալ է'], 422);
}
if (!in_array($payment_method, PAYMENT_METHODS, true)) {
    json_response(['success' => false, 'error' => 'Վճարման եղանակը սխալ է'], 422);
}
if ($shipping_method === 'pickup' && $address === '') {
    $address = 'Ինքնաուղևորում';
}
if ($first_name === '' || $last_name === '' || $phone === '' || $address === '') {
    json_response(['success' => false, 'error' => 'Լրացրեք բոլոր պարտադիր դաշտերը'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Էլ. հասցեն սխալ է'], 422);
}
if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) {
    json_response(['success' => false, 'error' => 'Հեռախոսահամարը սխալ է'], 422);
}
if ($city === '') {
    $city = 'Երևան';
}

$userId = current_user_id();

try {
    $pdo->beginTransaction();

    // Զամբյուղը վերցվում է բազայից (բրաուզերից ոչինչ չի ընդունվում)
    $stmt = $pdo->prepare('SELECT book_id, quantity FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[(int)$row['book_id']] = min(MAX_QTY_PER_ITEM, (int)$row['quantity']);
    }
    if (!$items) {
        throw new RuntimeException('Զամբյուղը դատարկ է');
    }

    // Գրքերի ընթացիկ գները և մնացորդը (կողպում ենք տողերը, որպեսզի երկու հաճախորդ չգնեն նույն վերջին օրինակը)
    $ids = array_keys($items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, title_hy, price, stock FROM books WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);

    $books = [];
    foreach ($stmt->fetchAll() as $b) {
        $books[(int)$b['id']] = $b;
    }

    $subtotal = 0.0;
    $lines = [];
    foreach ($items as $bookId => $qty) {
        if (!isset($books[$bookId])) {
            throw new RuntimeException('Զամբյուղի գրքերից մեկն այլևս հասանելի չէ');
        }
        $book = $books[$bookId];
        if ((int)$book['stock'] < $qty) {
            throw new RuntimeException('«' . $book['title_hy'] . '» գրքից մնացել է միայն ' . (int)$book['stock'] . ' հատ');
        }
        $price = (float)$book['price'];
        $subtotal += $price * $qty;
        $lines[] = ['id' => $bookId, 'title' => $book['title_hy'], 'price' => $price, 'qty' => $qty];
    }

    $shippingPrice = (float)SHIPPING_PRICES[$shipping_method];
    $promo = promo_discount($pdo, $userId, $promo_code, $subtotal);
    $discount = $promo['valid'] ? (float)$promo['discount'] : 0.0;
    $total = max(0.0, $subtotal - $discount + $shippingPrice);

    $orderNumber = 'ORD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $stmt = $pdo->prepare(
        "INSERT INTO orders (order_number, user_id, first_name, last_name, email, phone, address, city, postal_code,
                             shipping_method, shipping_price, payment_method, subtotal, total_amount, notes, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
    );
    $stmt->execute([
        $orderNumber, $userId, $first_name, $last_name, $email, $phone, $address, $city, $postal_code,
        $shipping_method, $shippingPrice, $payment_method, $subtotal, $total, $notes,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $stmtItem  = $pdo->prepare('INSERT INTO order_items (order_id, book_title, quantity, price) VALUES (?, ?, ?, ?)');
    $stmtStock = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($lines as $line) {
        $stmtItem->execute([$orderId, $line['title'], $line['qty'], $line['price']]);
        $stmtStock->execute([$line['qty'], $line['id'], $line['qty']]);
        if ($stmtStock->rowCount() !== 1) {
            throw new RuntimeException('«' . $line['title'] . '» գիրքը վաճառվեց մինչ պատվերի հաստատումը');
        }
    }

    $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    $pdo->commit();
    unset($_SESSION['promo_code']);

    // Էլ. նամակներ (սխալը չի խանգարում պատվերին)
    send_order_emails([
        'order_number' => $orderNumber, 'first_name' => $first_name, 'last_name' => $last_name, 'email' => $email,
        'phone' => $phone, 'address' => $address, 'city' => $city, 'shipping_method' => $shipping_method,
        'payment_method' => $payment_method, 'subtotal' => $subtotal, 'shipping' => $shippingPrice,
        'discount' => $discount, 'total' => $total,
    ], $lines);

    json_response([
        'success'      => true,
        'order_number' => $orderNumber,
        'total'        => $total,
        'redirect'     => 'order_success.php?n=' . urlencode($orderNumber),
    ]);
} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'error' => $ex->getMessage()], 409);
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('save_order failed: ' . $ex->getMessage());
    json_response(['success' => false, 'error' => 'Սերվերի սխալ։ Փորձեք կրկին'], 500);
}
