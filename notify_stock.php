<?php
// notify_stock.php - «Ծանուցել, երբ հասանելի լինի» բաժանորդագրություն / չեղարկում
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
csrf_check();

$bookId = (int)($_POST['book_id'] ?? 0);
$action = ($_POST['action'] ?? 'subscribe') === 'unsubscribe' ? 'unsubscribe' : 'subscribe';
$result = 'error';

try {
    $stmt = $pdo->prepare('SELECT stock FROM books WHERE id = ?');
    $stmt->execute([$bookId]);
    $row = $stmt->fetch();

    if ($row) {
        if ($action === 'unsubscribe') {
            $pdo->prepare('DELETE FROM stock_notifications WHERE book_id = ? AND user_id = ?')->execute([$bookId, current_user_id()]);
            $result = 'off';
        } elseif ((int)$row['stock'] > 0) {
            $result = 'available'; // արդեն առկա է
        } else {
            $pdo->prepare('INSERT IGNORE INTO stock_notifications (book_id, user_id) VALUES (?, ?)')->execute([$bookId, current_user_id()]);
            $result = 'on';
        }
    }
} catch (Throwable $ex) {
    error_log('notify_stock failed: ' . $ex->getMessage());
}

header('Location: book-details.php?id=' . $bookId . '&notify=' . $result);
exit;
