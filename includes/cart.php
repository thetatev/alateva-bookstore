<?php
// includes/cart.php - զամբյուղը պահվում է բազայում (cart_items)

/** Զամբյուղի ընթացիկ վիճակը՝ ակտուալ գներով. սպառված գրքերը հեռացվում են, քանակը կրճատվում է մնացորդին */
function cart_load(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT b.id, b.title_hy, b.price, b.stock, ci.quantity
         FROM cart_items ci JOIN books b ON b.id = ci.book_id
         WHERE ci.user_id = ? ORDER BY ci.id'
    );
    $stmt->execute([$userId]);

    $items = [];
    $subtotal = 0.0;
    $count = 0;
    foreach ($stmt->fetchAll() as $row) {
        $id = (int)$row['id'];
        $stock = (int)$row['stock'];
        $qty = (int)$row['quantity'];
        if ($stock < 1) {
            cart_set_qty($pdo, $userId, $id, 0);
            continue;
        }
        $max = min(MAX_QTY_PER_ITEM, $stock);
        if ($qty > $max) {
            $qty = $max;
            cart_set_qty($pdo, $userId, $id, $qty);
        }
        $price = (float)$row['price'];
        $subtotal += $price * $qty;
        $count += $qty;
        $items[] = ['id' => $id, 'title' => $row['title_hy'], 'price' => $price, 'quantity' => $qty, 'max' => $max];
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'count' => $count];
}

function cart_get_qty(PDO $pdo, int $userId, int $bookId): int {
    $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND book_id = ?');
    $stmt->execute([$userId, $bookId]);
    return (int)$stmt->fetchColumn();
}

/** Սահմանում է քանակը (0 կամ պակաս՝ հեռացնում է) */
function cart_set_qty(PDO $pdo, int $userId, int $bookId, int $qty): void {
    if ($qty <= 0) {
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND book_id = ?')->execute([$userId, $bookId]);
        return;
    }
    $pdo->prepare('INSERT INTO cart_items (user_id, book_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = ?')
        ->execute([$userId, $bookId, $qty, $qty]);
}

/** Գրքերի քանակը զամբյուղում (header-ի նշանակի համար). աղյուսակի բացակայության դեպքում՝ 0 */
function cart_count(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
