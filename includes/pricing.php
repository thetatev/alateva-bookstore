<?php
// includes/pricing.php - գների, առաքման և զեղչի հաշվարկը ՄԻԱՅՆ սերվերի կողմից

const SHIPPING_PRICES = ['standard' => 1000, 'express' => 2000, 'pickup' => 0];
const PAYMENT_METHODS = ['cash', 'card', 'paypal'];
const MAX_QTY_PER_ITEM = 10;
const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered'];

/** Պրոմո կոդի զեղչ։ WELCOME10՝ 1000 դրամ, միայն առաջին պատվերի համար */
function promo_discount(PDO $pdo, int $userId, string $code, float $subtotal): array {
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['discount' => 0, 'valid' => false, 'message' => ''];
    }
    if ($code === 'WELCOME10') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $stmt->execute([$userId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return ['discount' => 0, 'valid' => false, 'message' => 'Այս կոդը գործում է միայն առաջին պատվերի համար'];
        }
        return ['discount' => min(1000, $subtotal), 'valid' => true, 'message' => 'Պրոմո կոդը կիրառվեց՝ 1,000 դրամ զեղչ'];
    }
    return ['discount' => 0, 'valid' => false, 'message' => 'Սխալ պրոմո կոդ'];
}
