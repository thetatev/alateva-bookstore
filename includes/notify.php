<?php
// includes/notify.php - «Ծանուցել, երբ հասանելի լինի»
require_once __DIR__ . '/mail.php';

/** Նամակ է ուղարկում գրքի բաժանորդներին և հեռացնում նրանց ցանկից։ Սխալը չի խանգարում admin գործողությանը։ */
function notify_back_in_stock(PDO $pdo, int $bookId): void {
    try {
        $stmt = $pdo->prepare('SELECT title_hy FROM books WHERE id = ?');
        $stmt->execute([$bookId]);
        $title = $stmt->fetchColumn();
        if ($title === false) {
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT u.id, u.first_name, u.email FROM stock_notifications sn
             JOIN users u ON u.id = sn.user_id WHERE sn.book_id = ? LIMIT 200'
        );
        $stmt->execute([$bookId]);
        $url = app_url('book-details.php?id=' . $bookId);

        foreach ($stmt->fetchAll() as $u) {
            send_mail(
                $u['email'],
                '«' . $title . '» գիրքը նորից հասանելի է',
                '<p>Բարև ' . e($u['first_name']) . ',</p><p>Գիրքը, որի մասին խնդրել էիք ծանուցել՝ <strong>«' . e($title)
                . '»</strong>, նորից առկա է պահեստում։</p><p><a href="' . e($url) . '">Դիտել գիրքը</a></p>'
            );
            $pdo->prepare('DELETE FROM stock_notifications WHERE book_id = ? AND user_id = ?')->execute([$bookId, $u['id']]);
        }
    } catch (Throwable $ex) {
        error_log('notify_back_in_stock failed: ' . $ex->getMessage());
    }
}
