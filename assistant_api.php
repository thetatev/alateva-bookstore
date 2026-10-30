<?php
// assistant_api.php - «Գրքերի օգնական»
// Աշխատում է առանց արտաքին AI ծառայության։ Հասկանում է հարցի մտադրությունը (որոնում, գրքի մասին հարց,
// զամբյուղ, պատվեր, առաքում, վճարում, զեղչ), հիշում է զրույցի կոնտեքստը (նախորդ ցուցակը, բյուջեն, ժանրը)
// և տվյալները կարդում է ուղիղ բազայից։
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/pricing.php';
require_once __DIR__ . '/includes/cart.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// mbstring-ի բացակայության դեպքում պարզ փոխարինողներ
if (!function_exists('mb_strtolower')) { function mb_strtolower($s, $e = null) { return strtolower((string)$s); } }
if (!function_exists('mb_strlen')) { function mb_strlen($s, $e = null) { return (int)preg_match_all('/./us', (string)$s); } }
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null, $e = null) {
        $chars = preg_split('//u', (string)$s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return implode('', array_slice($chars, $start, $len));
    }
}
if (!function_exists('mb_strrpos')) {
    function mb_strrpos($h, $n, $offset = 0, $e = null) { $p = strrpos((string)$h, (string)$n); return $p === false ? false : (int)mb_strlen(substr((string)$h, 0, $p)); }
}

const A_PER_PAGE = 4;

// Ժանրեր. detect՝ հարցում փնտրվող բառերի արմատներ (≤3 տառը՝ միայն ճշգրիտ), like՝ կատալոգում որոնվող մասեր
const A_THEMES = [
    'romance'   => ['label' => 'սիրային',        'detect' => ['սիրային', 'սիրավեպ', 'սիրո', 'ռոմանտ', 'սեր', 'սերը', 'love', 'romance'],            'like' => ['սիրավեպ', 'սիրային', 'ռոման', 'սիրահար', 'love', 'romance']],
    'adventure' => ['label' => 'արկածային',      'detect' => ['արկած', 'adventure', 'ճանապարհորդ'],                                                  'like' => ['արկած', 'adventure', 'ճանապարհորդ']],
    'fantasy'   => ['label' => 'ֆենթեզի',        'detect' => ['ֆենթեզի', 'ֆանտ', 'կախարդ', 'մոգ', 'fantasy', 'magic'],                               'like' => ['ֆենթեզի', 'ֆանտաստ', 'ֆանտազ', 'կախարդ', 'fantasy', 'magic']],
    'mystery'   => ['label' => 'դետեկտիվ',       'detect' => ['դետեկտիվ', 'առեղծված', 'հետաքն', 'միստիկ', 'թրիլեր', 'detective', 'mystery', 'thriller'], 'like' => ['դետեկտիվ', 'առեղծված', 'հետաքն', 'միստիկ', 'թրիլեր', 'detective', 'mystery', 'thriller']],
    'history'   => ['label' => 'պատմական',       'detect' => ['պատմակ', 'պատմություն', 'history', 'historical'],                                      'like' => ['պատմակ', 'պատմություն', 'history']],
    'self_help' => ['label' => 'ինքնազարգացում', 'detect' => ['ինքնազարգաց', 'ինքնօգնության', 'մոտիվաց', 'self-help', 'motivation'],                  'like' => ['ինքնազարգաց', 'ինքնօգնության', 'մոտիվաց', 'self-help', 'motivation']],
    'kids'      => ['label' => 'մանկական',       'detect' => ['մանկական', 'երեխա', 'մանուկ', 'children', 'kids'],                                     'like' => ['մանկական', 'երեխա', 'children']],
    'poetry'    => ['label' => 'պոեզիա',         'detect' => ['պոեզիա', 'բանաստեղծ', 'չափածո', 'poetry'],                                            'like' => ['պոեզիա', 'բանաստեղծ', 'poetry']],
    'science'   => ['label' => 'գիտական',        'detect' => ['գիտական', 'գիտություն', 'ֆիզիկա', 'science'],                                          'like' => ['գիտական', 'գիտություն', 'ֆիզիկա', 'science']],
    'classic'   => ['label' => 'դասական',        'detect' => ['դասական', 'classic', 'classics'],                                                     'like' => ['դասական', 'classic']],
];

// Բառեր, որոնք չեն մասնակցում վերնագրի/հեղինակի որոնմանը
const A_STOP = [
    'և', 'ու', 'է', 'եմ', 'ես', 'դու', 'մի', 'այս', 'այդ', 'այն', 'որ', 'ինչ', 'ինչպես', 'համար', 'հետ', 'կամ', 'թե',
    'ինձ', 'մեզ', 'իմ', 'քո', 'ձեր', 'նաև', 'բայց', 'գիրք', 'գիրքը', 'գրքեր', 'գրքերը', 'գրքի', 'խորհուրդ', 'տուր', 'տվեք',
    'ասա', 'ցույց', 'տաս', 'կարող', 'ունեք', 'ունես', 'ունեմ', 'եք', 'ուզում', 'ուզեմ', 'փնտրում', 'փնտրեմ', 'գտիր', 'գտնել',
    'առաջարկիր', 'առաջարկ', 'հետաքրքիր', 'լավ', 'լավագույն', 'հաճելի', 'կարդալու', 'կարդալ', 'կարդամ', 'մասին', 'վերաբերյալ',
    'երբ', 'լինի', 'լինել', 'կլինի', 'նորից', 'արդյոք', 'կարելի', 'պետք', 'տեսնել', 'ինչքան', 'ցանկանում', 'կցանկանայի',
    'please', 'the', 'and', 'for', 'book', 'books', 'with', 'some', 'want', 'need', 'find', 'show', 'give',
];

// Մտադրություն արտահայտող արմատներ. այս բառերը վերնագրի/հեղինակի որոնմանը չեն մասնակցում
const A_INTENT_ROOTS = [
    'ավելաց', 'վերցն', 'գնեմ', 'գնել', 'ծանուց', 'նկարագր', 'սյուժե', 'մանրամաս', 'հասանելի', 'պահեստ', 'մնացել', 'առկա',
    'գին', 'գինը', 'արժե', 'նմանատիպ', 'նման', 'ուրիշ', 'էլի', 'հաջորդ', 'ավելին', 'էժան', 'թանկ', 'առաջին', 'երկրորդ',
    'երրորդ', 'չորրորդ', 'վերջին', 'զամբյուղ', 'ցանկացած', 'բյուջե', 'դրամ', 'հազար', 'ամենա', 'հայտնի', 'նորույթ', 'նոր',
    'թարմ', 'ավելի', 'պակաս', 'ցածր', 'մինչև', 'ժանր', 'հեղինակ', 'տարբերակ', 'պատվիր', 'պատվեր',
];

// ---------- Օգնական ֆունկցիաներ ----------

function a_out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function a_lower(string $s): string {
    // Հայերեն շեշտի/հարցական նշանները հեռացվում են, որպեսզի «ե՞րբ» բառը չկտրվի
    return mb_strtolower(str_replace(["\u{055B}", "\u{055C}", "\u{055E}"], '', $s), 'UTF-8');
}

function a_tokens(string $s): array {
    return preg_split('/[^\p{L}\p{N}]+/u', a_lower($s), -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

function a_amd($n): string {
    return number_format((float)$n, 0, '.', ',') . ' դրամ';
}

function a_pick(array $variants): string {
    return $variants[array_rand($variants)];
}

function a_contains(string $haystack, array $needles): bool {
    foreach ($needles as $n) {
        if ($n !== '' && strpos($haystack, $n) !== false) return true;
    }
    return false;
}

/** Երկու բառ նույն արմատից են (հոլովման/հոգնակիի տարբերությամբ) */
function a_match(string $a, string $b): bool {
    if ($a === $b) return true;
    $la = mb_strlen($a, 'UTF-8');
    $lb = mb_strlen($b, 'UTF-8');
    $n = min($la, $lb);
    if ($n < 3) return false;
    if (strpos($a, $b) === 0 || strpos($b, $a) === 0) {
        return $n >= 4 || abs($la - $lb) <= 3;
    }
    if ($n < 5) return false;
    $p = 0;
    while ($p < $n && mb_substr($a, $p, 1, 'UTF-8') === mb_substr($b, $p, 1, 'UTF-8')) $p++;
    return $p >= max(5, $n - 2) && abs($la - $lb) <= 4;
}

/** Արդյո՞ք որևէ token համընկնում է արմատներից մեկին (≤3 տառ՝ միայն ճշգրիտ) */
function a_has(array $tokens, array $roots): bool {
    foreach ($roots as $r) {
        $isShort = mb_strlen($r, 'UTF-8') <= 3;
        foreach ($tokens as $t) {
            if ($isShort ? ($t === $r) : a_match($t, $r)) return true;
        }
    }
    return false;
}

/** LIKE-ի համար բառի արմատ (հոլովակերտները կտրվում են) */
function a_root(string $w): string {
    $l = mb_strlen($w, 'UTF-8');
    if ($l >= 8) return mb_substr($w, 0, $l - 3, 'UTF-8');
    if ($l >= 6) return mb_substr($w, 0, $l - 2, 'UTF-8');
    if ($l >= 4) return mb_substr($w, 0, $l - 1, 'UTF-8');
    return $w;
}

function a_like(string $w): string {
    return '%' . addcslashes($w, '%_\\') . '%';
}

// ---------- Գնի ճանաչում ----------

const A_NUM = '(\d{1,3}(?:[ .,]\d{3})+|\d+)\s*(հազար|հզ|k)?';

function a_money(string $digits, string $unit): ?int {
    $v = (int)preg_replace('/\D/', '', $digits);
    if ($unit !== '') $v *= 1000;
    return ($v >= 200 && $v <= 1000000) ? $v : null;
}

/** Վերադարձնում է [min, max]՝ «մինչև 5000», «3000-ից 6000», «5 հազարից էժան», «500 դրամ» և այլն */
function a_parse_price(string $t): array {
    $min = null;
    $max = null;
    $N = A_NUM;

    if (preg_match('/' . $N . '\s*(?:-ից|ից|-|–|—|մինչև|մինչ)\s*' . $N . '/ui', $t, $m)) {
        $a = a_money($m[1], $m[2] ?? '');
        $b = a_money($m[3], $m[4] ?? '');
        if ($a === null && $b !== null && ($m[4] ?? '') !== '') $a = a_money($m[1], $m[4]);
        if ($a !== null && $b !== null) {
            $min = min($a, $b);
            $max = max($a, $b);
        }
    }
    if ($min === null && $max === null) {
        if (preg_match('/(?:մինչև|մինչ|ոչ\s+ավել(?:ի)?(?:\s+քան)?|under|below|max|<=?)\s*' . $N . '/ui', $t, $m)) {
            $max = a_money($m[1], $m[2] ?? '');
        } elseif (preg_match('/' . $N . '\s*(?:դրամ|դր|amd|֏)?\s*(?:-?ից)?\s*(?:պակաս|ցածր|էժան|ներքև)/ui', $t, $m)) {
            $max = a_money($m[1], $m[2] ?? '');
        }
        if (preg_match('/(?:ավելի\s+քան|ավել\s+քան|առնվազն|over|above|min|>=?)\s*' . $N . '/ui', $t, $m)) {
            $min = a_money($m[1], $m[2] ?? '');
        } elseif (preg_match('/' . $N . '\s*(?:դրամ|դր|amd|֏)?\s*(?:-?ից)?\s*(?:ավել(?:ի)?|բարձր|թանկ)/ui', $t, $m)) {
            $min = a_money($m[1], $m[2] ?? '');
        }
    }
    if ($min === null && $max === null) {
        if (preg_match('/(?:բյուջե\S*|գին\S*|budget|price)\D{0,15}' . $N . '/ui', $t, $m)
            || preg_match('/' . $N . '\s*(?:դրամ|դր|amd|֏)/ui', $t, $m)) {
            $max = a_money($m[1], $m[2] ?? '');
        }
    }
    return [$min, $max];
}

// ---------- Կատալոգ ----------

function a_dedupe(array $rows): array {
    $seen = [];
    $out = [];
    foreach ($rows as $r) {
        $k = a_lower(trim((string)$r['title_hy'])) . '|' . a_lower(trim((string)$r['author']));
        if (isset($seen[$k])) continue;
        $seen[$k] = true;
        $out[] = $r;
    }
    return $out;
}

const A_BOOK_COLS = 'b.id, b.title, b.title_hy, b.author, b.description, b.price, b.cover_image, b.rating, b.reviews_count, b.stock, b.category_id, c.name_hy AS category_name';

function a_book_by_id(PDO $pdo, int $id): ?array {
    if ($id < 1) return null;
    $stmt = $pdo->prepare('SELECT ' . A_BOOK_COLS . ' FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function a_books_by_ids(PDO $pdo, array $ids): array {
    $ids = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT ' . A_BOOK_COLS . ' FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.id IN (' . $in . ')');
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $r) $byId[(int)$r['id']] = $r;
    $out = [];
    foreach ($ids as $id) if (isset($byId[$id])) $out[] = $byId[$id];
    return $out;
}

/** Ֆիլտրերով ընտրություն (միայն առկա գրքեր). $f՝ min, max, cat, theme, sort, q (բառեր), exclude (id) */
function a_books_filtered(PDO $pdo, array $f): array {
    $sql = 'SELECT ' . A_BOOK_COLS . ' FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.stock > 0';
    $p = [];
    if (!empty($f['min'])) { $sql .= ' AND b.price >= ?'; $p[] = (int)$f['min']; }
    if (!empty($f['max'])) { $sql .= ' AND b.price <= ?'; $p[] = (int)$f['max']; }
    if (!empty($f['cat']))  { $sql .= ' AND b.category_id = ?'; $p[] = (int)$f['cat']; }
    if (!empty($f['exclude'])) { $sql .= ' AND b.id <> ?'; $p[] = (int)$f['exclude']; }
    if (!empty($f['theme']) && isset(A_THEMES[$f['theme']])) {
        $conds = [];
        foreach (A_THEMES[$f['theme']]['like'] as $word) {
            $conds[] = '(b.title_hy LIKE ? OR b.title LIKE ? OR b.description LIKE ? OR c.name_hy LIKE ? OR c.name LIKE ?)';
            $t = a_like($word);
            array_push($p, $t, $t, $t, $t, $t);
        }
        $sql .= ' AND (' . implode(' OR ', $conds) . ')';
    }
    if (!empty($f['q'])) {
        $conds = [];
        foreach (array_slice($f['q'], 0, 4) as $w) {
            $conds[] = '(b.title_hy LIKE ? OR b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)';
            $t = a_like(a_root($w));
            array_push($p, $t, $t, $t, $t);
        }
        $sql .= ' AND (' . implode(' OR ', $conds) . ')';
    }
    $orders = [
        'rating'     => 'b.rating DESC, b.reviews_count DESC, b.id DESC',
        'price_asc'  => 'b.price ASC, b.rating DESC',
        'price_desc' => 'b.price DESC, b.rating DESC',
        'new'        => 'b.id DESC',
    ];
    $sql .= ' ORDER BY ' . ($orders[$f['sort'] ?? 'rating'] ?? $orders['rating']) . ' LIMIT 80';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    return a_dedupe($stmt->fetchAll());
}

/** Ազատ տեքստով որոնում (վերնագիր, հեղինակ, նկարագրություն)՝ գնահատականով դասավորված */
function a_search_text(PDO $pdo, array $words): array {
    $words = array_slice(array_values($words), 0, 5);
    if (!$words) return [];
    $conds = [];
    $p = [];
    foreach ($words as $w) {
        $conds[] = '(b.title_hy LIKE ? OR b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)';
        $t = a_like(a_root($w));
        array_push($p, $t, $t, $t, $t);
    }
    $stmt = $pdo->prepare('SELECT ' . A_BOOK_COLS . ' FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE '
        . implode(' OR ', $conds) . ' LIMIT 80');
    $stmt->execute($p);

    $scored = [];
    foreach (a_dedupe($stmt->fetchAll()) as $r) {
        $titleWords = a_tokens($r['title_hy'] . ' ' . $r['title']);
        $authorWords = a_tokens((string)$r['author']);
        $catWords = a_tokens((string)($r['category_name'] ?? ''));
        $desc = a_lower((string)$r['description']);
        $score = 0;
        foreach ($words as $w) {
            $hit = false;
            foreach ($titleWords as $x) { if (a_match($w, $x)) { $score += 3; $hit = true; break; } }
            if (!$hit) foreach ($authorWords as $x) { if (a_match($w, $x)) { $score += 3; $hit = true; break; } }
            if (!$hit) foreach ($catWords as $x) { if (a_match($w, $x)) { $score += 1; $hit = true; break; } }
            if (!$hit && strpos($desc, a_root($w)) !== false) $score += 1;
        }
        if ($score > 0) {
            $r['_score'] = $score;
            $scored[] = $r;
        }
    }
    usort($scored, function ($x, $y) {
        return [$y['_score'], (float)$y['rating']] <=> [$x['_score'], (float)$x['rating']];
    });
    return $scored;
}

function a_payload(array $rows): array {
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'          => (int)$r['id'],
            'title_hy'    => (string)$r['title_hy'],
            'author'      => (string)$r['author'],
            'price'       => (float)$r['price'],
            'cover_image' => book_cover($r),
            'rating'      => (float)$r['rating'],
            'stock'       => (int)$r['stock'],
        ];
    }
    return $out;
}

function a_stock_text(int $stock): string {
    if ($stock < 1) return 'այս պահին պահեստում չկա';
    if ($stock <= 5) return 'մնացել է միայն ' . $stock . ' հատ';
    return 'առկա է պահեստում';
}

function a_short(string $text, int $max = 240): string {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)));
    if (mb_strlen($text, 'UTF-8') <= $max) return $text;
    $cut = mb_substr($text, 0, $max, 'UTF-8');
    $sp = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($sp !== false && $sp > $max * 0.6) $cut = mb_substr($cut, 0, $sp, 'UTF-8');
    return rtrim($cut, " ,.;:-") . '…';
}

function a_cat_chips(array $cats, ?int $exceptId, int $n): array {
    shuffle($cats);
    $out = [];
    foreach ($cats as $c) {
        if ($exceptId !== null && (int)$c['id'] === $exceptId) continue;
        if (trim((string)$c['name_hy']) === '') continue;
        $out[] = (string)$c['name_hy'];
        if (count($out) >= $n) break;
    }
    return $out;
}

// ---------- Պատասխանի կառուցում ----------

function a_reply(string $text, array $extra = []): array {
    return $extra + ['reply' => $text, 'books' => [], 'chips' => [], 'links' => []];
}

function a_cart_add(PDO $pdo, int $uid, array $book): array {
    $stock = (int)$book['stock'];
    if ($stock < 1) return ['ok' => false, 'msg' => 'այս պահին պահեստում չկա'];
    $max = min(MAX_QTY_PER_ITEM, $stock);
    $cur = cart_get_qty($pdo, $uid, (int)$book['id']);
    if ($cur >= $max) return ['ok' => false, 'msg' => 'զամբյուղում արդեն առավելագույն քանակն է (' . $max . ' հատ)'];
    cart_set_qty($pdo, $uid, (int)$book['id'], $cur + 1);
    return ['ok' => true, 'qty' => $cur + 1];
}

function a_notify(PDO $pdo, int $uid, array $book): string {
    if ((int)$book['stock'] > 0) return 'available';
    $pdo->prepare('INSERT IGNORE INTO stock_notifications (book_id, user_id) VALUES (?, ?)')->execute([(int)$book['id'], $uid]);
    return 'on';
}

function a_status_label(string $s): string {
    $map = ['pending' => 'Սպասման մեջ', 'processing' => 'Մշակվում է', 'shipped' => 'Առաքված', 'delivered' => 'Ստացված'];
    return $map[$s] ?? $s;
}

function a_login_links(string $next = 'index.php'): array {
    return [
        ['label' => 'Մուտք գործել', 'url' => 'login.php?next=' . rawurlencode($next)],
        ['label' => 'Գրանցվել', 'url' => 'register.php'],
    ];
}

function a_default_ctx(): array {
    return ['min' => null, 'max' => null, 'cat' => null, 'cat_name' => null, 'theme' => null, 'sort' => null,
            'q' => [], 'offset' => 0, 'last' => [], 'prices' => [], 'focus' => 0];
}

function a_filter_labels(array $c): array {
    $l = [];
    if ($c['min'] && $c['max']) $l[] = number_format($c['min'], 0, '.', ',') . '–' . a_amd($c['max']);
    elseif ($c['max']) $l[] = 'մինչև ' . a_amd($c['max']);
    elseif ($c['min']) $l[] = 'սկսած ' . a_amd($c['min']);
    if ($c['cat_name']) $l[] = $c['cat_name'];
    if ($c['theme'] && isset(A_THEMES[$c['theme']])) $l[] = A_THEMES[$c['theme']]['label'];
    if ($c['q']) $l[] = '«' . implode(' ', $c['q']) . '»';
    if ($c['sort'] === 'price_asc') $l[] = 'ամենաէժանները';
    if ($c['sort'] === 'price_desc') $l[] = 'ամենաթանկերը';
    if ($c['sort'] === 'new') $l[] = 'նորույթներ';
    return $l;
}

// ---------- Հարցումների մշակում ----------

function a_init(PDO $pdo): array {
    $cats = $pdo->query('SELECT id, name, name_hy FROM categories')->fetchAll();
    $chips = ['Ամենահայտնի գրքերը'];
    foreach (a_cat_chips($cats, null, 2) as $c) $chips[] = $c;
    $chips[] = 'Առաքման պայմանները';
    $chips[] = is_logged_in() ? 'Իմ պատվերները' : 'Ինչ կարող ես անել';
    return a_reply('Բարև։ Ես alateva-ի գրքերի օգնականն եմ։ Կարող եմ առաջարկել գիրք ըստ ժանրի, հեղինակի կամ բյուջեի, ստուգել պաշարը, ավելացնել գիրքը զամբյուղ և պատմել պատվերի ու առաքման մասին։', ['chips' => $chips]);
}

function a_chat(PDO $pdo, string $message): array {
    $raw = a_lower($message);
    $tokens = a_tokens($message);
    $logged = is_logged_in();
    $uid = current_user_id();
    $ctx = $_SESSION['assistant_ctx'] ?? [];
    $ctx += a_default_ctx();
    $cats = $pdo->query('SELECT id, name, name_hy FROM categories')->fetchAll();

    $save = function () use (&$ctx) { $_SESSION['assistant_ctx'] = $ctx; };
    $popular = array_merge(['Ամենահայտնի գրքերը'], a_cat_chips($cats, null, 2));

    // ----- Մտադրություններ -----
    $addWord     = a_has($tokens, ['ավելաց', 'վերցն', 'գնեմ', 'գնել', 'գնենք', 'պատվիրեմ', 'add', 'buy', 'դիր']);
    $notifyWord  = a_has($tokens, ['ծանուց', 'notify', 'տեղեկաց', 'հայտնեք']);
    $detailWord  = a_has($tokens, ['նկարագր', 'մասին', 'սյուժե', 'մանրամաս', 'summary', 'details', 'պատմիր']);
    $stockWord   = a_has($tokens, ['կա', 'պահեստ', 'հասանելի', 'մնացել', 'առկա', 'stock', 'available']);
    $priceWord   = a_has($tokens, ['գին', 'գինը', 'ինչքան', 'արժե', 'price', 'cost']);
    $similarWord = a_has($tokens, ['նմանատիպ', 'նման', 'similar']);
    $moreWord    = a_has($tokens, ['ուրիշ', 'այլ', 'էլի', 'եւս', 'ևս', 'հաջորդ', 'ավելին', 'more', 'next', 'another']);

    // ----- Վերսկսում -----
    if (a_contains($raw, ['նորից սկսենք', 'սկսենք նորից', 'մաքրել', 'մաքրիր', 'reset', 'ֆիլտրերը հանիր'])) {
        $ctx = a_default_ctx();
        $save();
        $r = a_init($pdo);
        $r['reply'] = 'Ֆիլտրերը մաքրվեցին։ Ի՞նչ գիրք եք փնտրում։';
        return $r;
    }

    // ----- Շնորհակալություն, ողջույն, օգնություն -----
    if (count($tokens) <= 3 && a_has($tokens, ['շնորհակալ', 'շնորհակալություն', 'մերսի', 'thanks', 'thank', 'սպասիբո'])) {
        return a_reply(a_pick(['Խնդրեմ։ Եթե ուզում եք, կարող եմ առաջարկել ևս մի քանի գիրք։', 'Սիրով։ Ուրիշ բանո՞վ կարող եմ օգնել։']),
            ['chips' => array_merge(['Ուրիշ տարբերակներ'], a_cat_chips($cats, $ctx['cat'] ? (int)$ctx['cat'] : null, 2))]);
    }
    if (count($tokens) <= 2 && a_has($tokens, ['բարև', 'բարեւ', 'ողջույն', 'hello', 'hi', 'hey', 'բարի'])) {
        return a_init($pdo);
    }
    if ((count($tokens) <= 6 && a_contains($raw, ['ինչ կարող', 'ինչով կարող', 'ինչ գիտես', 'ինչ ես անում', 'հնարավորություն']))
        || (count($tokens) <= 2 && a_has($tokens, ['help', 'օգնիր']))) {
        $lines = [
            'Ահա թե ինչով կարող եմ օգնել.',
            '• Գիրք ընտրել՝ ըստ ժանրի, հեղինակի, բյուջեի («դետեկտիվ մինչև 5000 դրամ»)։',
            '• Հասկանալ շարունակությունը՝ «ավելի էժան», «ուրիշ տարբերակներ», «նմանատիպ»։',
            '• Պատմել գրքի մասին, ստուգել պաշարը, ծանուցել, երբ սպառված գիրքը հասանելի լինի։',
            '• Ավելացնել գիրքը զամբյուղ («ավելացրու երկրորդը»)։',
            '• Ցույց տալ ձեր զամբյուղն ու պատվերների կարգավիճակը։',
            '• Պատմել առաքման, վճարման և զեղչի մասին։',
        ];
        return a_reply(implode("\n", $lines), ['chips' => ['Ամենահայտնի գրքերը', 'Առաքման պայմանները', 'Զեղչի կոդ']]);
    }

    // ----- Պատվեր -----
    $orderNo = null;
    if (preg_match('/ord-\d{6}-[a-z0-9]{6}/i', $raw, $om)) $orderNo = strtoupper($om[0]);
    $orderWord = false;
    foreach ($tokens as $t) { if (strpos($t, 'պատվեր') === 0 || strpos($t, 'order') === 0) { $orderWord = true; break; } }
    $trackWord = a_has($tokens, ['կարգավիճակ', 'որտեղ', 'ուր', 'հետևել', 'status', 'track', 'ստացա', 'ստացել', 'ժամանեց', 'երբ'])
        || a_contains($raw, ['իմ պատվեր', 'my order']);
    $howWord = a_has($tokens, ['ինչպես', 'ինչպե']);
    $buyWord = $addWord || a_has($tokens, ['պատվիր']) || $orderWord;

    if ($howWord && $buyWord && !$orderNo && !($orderWord && $trackWord)) {
        return a_reply("Գիրք պատվիրելը պարզ է.\n1. Ընտրեք գիրքը և ավելացրեք զամբյուղ (կարող եմ դա անել ձեր փոխարեն)։\n2. Բացեք զամբյուղը և անցեք պատվերի ձևակերպմանը։\n3. Լրացրեք առաքման տվյալները, ընտրեք առաքման և վճարման եղանակը և հաստատեք։", [
            'links' => [['label' => 'Բացել զամբյուղը', 'url' => 'cart.php']],
            'chips' => ['Առաքման պայմանները', 'Վճարման եղանակներ', 'Զեղչի կոդ'],
        ]);
    }

    if ($orderNo || ($orderWord && ($trackWord || count($tokens) <= 3) && !$addWord)) {
        if (!$logged) {
            return a_reply('Պատվերների կարգավիճակը տեսնելու համար անհրաժեշտ է մուտք գործել ձեր հաշիվ։', [
                'links' => a_login_links('profile.php'), 'chips' => ['Առաքման պայմանները']]);
        }
        try {
            if ($orderNo) {
                $stmt = $pdo->prepare('SELECT order_number, status, total_amount, created_at FROM orders WHERE user_id = ? AND order_number = ? LIMIT 1');
                $stmt->execute([$uid, $orderNo]);
            } else {
                $stmt = $pdo->prepare('SELECT order_number, status, total_amount, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3');
                $stmt->execute([$uid]);
            }
            $rows = $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('assistant orders failed: ' . $e->getMessage());
            $rows = [];
        }
        if (!$rows) {
            return a_reply($orderNo ? 'Այդ համարով պատվեր ձեր հաշվում չգտա։ Ստուգեք համարը կամ բացեք «Իմ պատվերները» բաժինը։'
                                    : 'Դուք դեռ պատվերներ չունեք։ Ընտրեք գիրք, և ես կօգնեմ ձևակերպել առաջինը։', [
                'links' => [['label' => 'Իմ պատվերները', 'url' => 'profile.php#orders']],
                'chips' => ['Ամենահայտնի գրքերը', 'Զեղչի կոդ']]);
        }
        $orders = [];
        foreach ($rows as $o) {
            $orders[] = [
                'number' => (string)$o['order_number'],
                'status' => a_status_label((string)$o['status']),
                'total'  => a_amd($o['total_amount']),
                'date'   => date('d.m.Y', strtotime((string)$o['created_at'])),
            ];
        }
        $first = $orders[0];
        $text = count($orders) === 1
            ? 'Պատվեր ' . $first['number'] . '՝ կարգավիճակը «' . $first['status'] . '» է։'
            : 'Ձեր վերջին պատվերի (' . $first['number'] . ') կարգավիճակը՝ «' . $first['status'] . '»։ Ստորև երևում են վերջին ' . count($orders) . ' պատվերները։';
        return a_reply($text, ['orders' => $orders, 'links' => [['label' => 'Բոլոր պատվերները', 'url' => 'profile.php#orders']], 'chips' => ['Ամենահայտնի գրքերը']]);
    }

    // ----- Զամբյուղ -----
    if (a_has($tokens, ['զամբյուղ', 'cart', 'basket']) && !$addWord) {
        if (!$logged) {
            return a_reply('Զամբյուղը պահվում է ձեր հաշվում, ուստի նախ անհրաժեշտ է մուտք գործել։', ['links' => a_login_links('cart.php')]);
        }
        try {
            $cart = cart_load($pdo, $uid);
        } catch (Throwable $e) {
            error_log('assistant cart failed: ' . $e->getMessage());
            return a_reply('Զամբյուղը այս պահին չհաջողվեց բացել։ Փորձեք մի փոքր ուշ։');
        }
        if (!$cart['items']) {
            return a_reply('Ձեր զամբյուղը դատարկ է։ Ուզո՞ւմ եք օգնեմ ընտրել գիրք։', ['chips' => $popular]);
        }
        $lines = [];
        foreach ($cart['items'] as $it) $lines[] = '• ' . $it['title'] . ' × ' . $it['quantity'] . ' — ' . a_amd($it['price'] * $it['quantity']);
        $lines[] = 'Ընդամենը՝ ' . a_amd($cart['subtotal']) . ' (առաքումը ավելանում է պատվերի ժամանակ)։';
        return a_reply("Ձեր զամբյուղում.\n" . implode("\n", $lines), [
            'cart_count' => (int)$cart['count'],
            'links' => [['label' => 'Բացել զամբյուղը', 'url' => 'cart.php'], ['label' => 'Ձևակերպել պատվեր', 'url' => 'checkout.php']],
            'chips' => ['Զեղչի կոդ', 'Առաքման պայմանները'],
        ]);
    }

    // ----- Հաճախ տրվող հարցեր -----
    if (a_has($tokens, ['առաք', 'ուղարկ', 'shipping', 'delivery'])) {
        $s = SHIPPING_PRICES;
        return a_reply("Առաքման եղանակները.\n• Ստանդարտ՝ " . a_amd($s['standard']) . ", 3-5 աշխատանքային օր։\n• Էքսպրես՝ " . a_amd($s['express']) . ", 1-2 աշխատանքային օր։\n• Ինքնաուղևորում խանութից (Երևան)՝ անվճար։\nԵղանակն ընտրվում է պատվերի ձևակերպման ժամանակ։", [
            'chips' => ['Վճարման եղանակներ', 'Զեղչի կոդ']]);
    }
    if (a_has($tokens, ['վճար', 'payment', 'pay', 'քարտ', 'կանխիկ', 'paypal']) && !$addWord) {
        return a_reply("Վճարման եղանակները.\n• Կանխիկ՝ առաքման ժամանակ։\n• Բանկային քարտ (Visa / MasterCard)։\n• PayPal։", ['chips' => ['Առաքման պայմանները', 'Զեղչի կոդ']]);
    }
    if (a_has($tokens, ['պրոմո', 'զեղչ', 'կոդ', 'promo', 'discount', 'coupon', 'welcome10'])) {
        $text = 'WELCOME10 կոդը տալիս է 1,000 դրամ զեղչ և գործում է միայն առաջին պատվերի համար։ Այն մուտքագրվում է զամբյուղում։';
        if ($logged) {
            try {
                $chk = promo_discount($pdo, $uid, 'WELCOME10', 1000.0);
                $text .= $chk['valid'] ? ' Դուք դեռ պատվեր չունեք, ուստի կոդը ձեզ համար հասանելի է։' : ' Դուք արդեն ունեք պատվեր, ուստի այս կոդը ձեզ համար այլևս չի գործի։';
            } catch (Throwable $e) {
                error_log('assistant promo failed: ' . $e->getMessage());
            }
        }
        return a_reply($text, ['links' => [['label' => 'Բացել զամբյուղը', 'url' => 'cart.php']], 'chips' => ['Առաքման պայմանները', 'Ամենահայտնի գրքերը']]);
    }
    if (a_has($tokens, ['գաղտնաբառ', 'password', 'մոռացել'])) {
        return a_reply('Գաղտնաբառը մոռացել եք՝ ստացեք վերականգնման հղում էլ. փոստով։ Եթե մուտք եք գործել, գաղտնաբառը կարող եք փոխել պրոֆիլում։', [
            'links' => [['label' => 'Վերականգնել գաղտնաբառը', 'url' => 'forgot_password.php'], ['label' => 'Իմ պրոֆիլը', 'url' => 'profile.php']]]);
    }
    if (a_has($tokens, ['վերադարձ', 'փոխանակ', 'return', 'refund'])) {
        return a_reply('Վերադարձի և փոխանակման կանոնների մասին ճշգրիտ տվյալ ինձ մոտ չկա։ Գրեք ' . SHOP_EMAIL . ' հասցեին կամ զանգահարեք +37477000000 համարով։');
    }
    if (a_has($tokens, ['կապ', 'հեռախոս', 'contact', 'phone', 'email'])) {
        return a_reply('Կապվեք մեզ հետ՝ ' . SHOP_EMAIL . ' կամ +37477000000։');
    }

    // ----- Ֆիլտրերի ճանաչում -----
    [$pmin, $pmax] = a_parse_price($raw);
    $clearPrice = a_has($tokens, ['ցանկացած', 'any']) && a_contains($raw, ['գին', 'բյուջե', 'price']);

    $sortReq = null;
    foreach ($tokens as $t) {
        if (strpos($t, 'ամենաէժան') === 0) { $sortReq = 'price_asc'; break; }
        if (strpos($t, 'ամենաթանկ') === 0) { $sortReq = 'price_desc'; break; }
        if (strpos($t, 'ամենահայտն') === 0 || strpos($t, 'ամենալավ') === 0 || strpos($t, 'բեսթսելլեր') === 0
            || strpos($t, 'ամենաշատ') === 0 || strpos($t, 'լավագույն') === 0 || strpos($t, 'հայտնի') === 0) { $sortReq = 'rating'; break; }
    }
    if ($sortReq === null && a_has($tokens, ['նոր', 'թարմ', 'նորույթ', 'new', 'latest'])) $sortReq = 'new';

    $noPrice = $pmin === null && $pmax === null;
    $cheaper = $sortReq === null && $noPrice && a_has($tokens, ['էժան', 'cheaper', 'cheap']);
    $pricier = $sortReq === null && $noPrice && a_has($tokens, ['թանկ', 'expensive']);

    // Կատեգորիա
    $cat = null;
    $catScore = 0;
    $catTokens = [];
    foreach ($cats as $c) {
        $score = 0;
        $hits = [];
        foreach (a_tokens($c['name_hy'] . ' ' . $c['name']) as $w) {
            if (mb_strlen($w, 'UTF-8') < 3) continue;
            foreach ($tokens as $t) {
                if (a_match($t, $w)) { $score++; $hits[] = $t; break; }
            }
        }
        if ($score > $catScore) { $cat = $c; $catScore = $score; $catTokens = $hits; }
    }
    // Ժանր
    $theme = null;
    $themeTokens = [];
    foreach (A_THEMES as $key => $def) {
        foreach ($tokens as $t) {
            if (a_has([$t], $def['detect'])) { $theme = $key; $themeTokens[] = $t; }
        }
        if ($theme !== null) break;
    }
    if ($cat !== null) $theme = null;

    // Բովանդակային բառեր (վերնագիր/հեղինակ)
    $consumed = array_merge($catTokens, $themeTokens);
    $content = [];
    foreach ($tokens as $t) {
        if (ctype_digit($t) || mb_strlen($t, 'UTF-8') < 3 || in_array($t, $consumed, true) || in_array($t, A_STOP, true)) continue;
        if (a_has([$t], A_INTENT_ROOTS)) continue;
        $content[] = $t;
    }

    $hasFilters = !$noPrice || $cat !== null || $theme !== null || $sortReq !== null
        || $cheaper || $pricier || $moreWord || $clearPrice || $similarWord;

    // ----- Գրքի ճանաչում (համար, դերանուն, վերնագիր) -----
    $ids = array_values($ctx['last']);
    $ordinal = null;
    if (a_has($tokens, ['առաջին', 'first'])) $ordinal = 0;
    elseif (a_has($tokens, ['երկրորդ', 'second'])) $ordinal = 1;
    elseif (a_has($tokens, ['երրորդ', 'third'])) $ordinal = 2;
    elseif (a_has($tokens, ['չորրորդ', 'fourth'])) $ordinal = 3;
    elseif (preg_match('/(?<!\d)([1-4])\s*-?\s*(?:ին|րդ)/u', $raw, $om2)) $ordinal = (int)$om2[1] - 1;
    elseif ($ids && a_has($tokens, ['վերջին', 'last'])) $ordinal = count($ids) - 1;
    $pronoun = a_has($tokens, ['այս', 'այդ', 'դա', 'սա', 'այն', 'this', 'that', 'it']);
    $anyBookIntent = $addWord || $notifyWord || $detailWord || $stockWord || $priceWord;

    $book = null;
    if ($ordinal !== null && isset($ids[$ordinal])) {
        $book = a_book_by_id($pdo, (int)$ids[$ordinal]);
    } elseif ($ordinal !== null && !$ids && !$hasFilters && !$content) {
        return a_reply('Նախ ասեք՝ ինչ գիրք եք փնտրում, և ես կառաջարկեմ տարբերակներ, որոնցից կկարողանաք ընտրել։', ['chips' => $popular]);
    } elseif ($pronoun && $ctx['focus'] && ($anyBookIntent || $similarWord)) {
        $book = a_book_by_id($pdo, (int)$ctx['focus']);
    } elseif ($ids && !$hasFilters && $content) {
        $best = null;
        $bestScore = 0;
        foreach (a_books_by_ids($pdo, $ids) as $r) {
            $tw = a_tokens($r['title_hy'] . ' ' . $r['title']);
            $score = 0;
            foreach ($content as $w) foreach ($tw as $x) { if (a_match($w, $x)) { $score += 3; break; } }
            if ($score > $bestScore) { $best = $r; $bestScore = $score; }
        }
        if ($best) $book = $best;
    }
    if (!$book && !$content && $ctx['focus'] && ($similarWord || ($anyBookIntent && !$hasFilters))) {
        $book = a_book_by_id($pdo, (int)$ctx['focus']);
    }
    if (!$book && $similarWord && !$content && $ids) {
        $book = a_book_by_id($pdo, (int)$ids[0]);
    }
    if (!$book && !$hasFilters && $content) {
        // Վերնագրով կամ հեղինակով որոնում ամբողջ կատալոգում
        $found = a_search_text($pdo, $content);
        $strong = array_values(array_filter($found, fn($r) => $r['_score'] >= 3));
        if ($strong) {
            $topScore = $strong[0]['_score'];
            $tops = array_values(array_filter($strong, fn($r) => $r['_score'] === $topScore));
            if ($anyBookIntent) {
                $book = $strong[0];
            } elseif (count($tops) === 1) {
                $book = $strong[0];
            } else {
                // Մի քանի հավասար համընկնում (օր.՝ հեղինակի բոլոր գրքերը). ցույց ենք տալիս ցուցակ
                $list = array_slice($strong, 0, A_PER_PAGE);
                $ctx['last'] = array_map(fn($r) => (int)$r['id'], $list);
                $ctx['prices'] = array_map(fn($r) => (float)$r['price'], $list);
                $ctx['focus'] = 0;
                $save();
                return a_reply('Գտա ' . count($strong) . ' համապատասխան գիրք։ Ահա առաջինները։', ['books' => a_payload($list), 'chips' => $popular]);
            }
        } elseif ($found && !$anyBookIntent) {
            // Միայն թույլ համընկնում (նկարագրության մեջ)
            $list = array_slice($found, 0, A_PER_PAGE);
            $ctx['last'] = array_map(fn($r) => (int)$r['id'], $list);
            $ctx['prices'] = array_map(fn($r) => (float)$r['price'], $list);
            $ctx['focus'] = 0;
            $save();
            return a_reply('Վերնագրով կամ հեղինակով ճշգրիտ համընկնում չգտա, բայց այս գրքերի նկարագրությունը կապ ունի ձեր ասածի հետ։', ['books' => a_payload($list), 'chips' => $popular]);
        }
    }

    // ----- Գործողություններ գրքի հետ -----
    if ($book && ($anyBookIntent || !$hasFilters)) {
        $id = (int)$book['id'];
        $title = (string)$book['title_hy'];
        $stock = (int)$book['stock'];
        $ctx['last'] = [$id];
        $ctx['prices'] = [(float)$book['price']];
        $ctx['focus'] = $id;
        $payload = a_payload([$book]);
        $simChip = 'Նմանատիպ գրքեր';

        if ($addWord) {
            if (!$logged) {
                $save();
                return a_reply('«' . $title . '» գիրքը զամբյուղ ավելացնելու համար նախ մուտք գործեք ձեր հաշիվ։', ['books' => $payload, 'links' => a_login_links('book-details.php?id=' . $id)]);
            }
            try {
                $res = a_cart_add($pdo, $uid, $book);
                $count = cart_count($pdo, $uid);
            } catch (Throwable $e) {
                error_log('assistant add failed: ' . $e->getMessage());
                $save();
                return a_reply('Չհաջողվեց ավելացնել զամբյուղ։ Փորձեք կրկին կամ օգտվեք գրքի էջից։', ['books' => $payload]);
            }
            $save();
            if (!$res['ok']) {
                $chips = $stock < 1 ? ['Ծանուցել, երբ հասանելի լինի', $simChip] : ['Տեսնել զամբյուղը', $simChip];
                return a_reply('«' . $title . '» գիրքը չհաջողվեց ավելացնել. ' . $res['msg'] . '։', ['books' => $payload, 'chips' => $chips]);
            }
            return a_reply('✅ «' . $title . '» ավելացվեց զամբյուղ (' . $res['qty'] . ' հատ)։ Զամբյուղում ընդհանուր ' . $count . ' գիրք է։', [
                'cart_count' => $count,
                'links' => [['label' => 'Բացել զամբյուղը', 'url' => 'cart.php'], ['label' => 'Ձևակերպել պատվեր', 'url' => 'checkout.php']],
                'chips' => ['Ուրիշ տարբերակներ', $simChip, 'Տեսնել զամբյուղը'],
            ]);
        }
        if ($notifyWord) {
            if (!$logged) {
                $save();
                return a_reply('Ծանուցում ստանալու համար անհրաժեշտ է մուտք գործել։', ['books' => $payload, 'links' => a_login_links('book-details.php?id=' . $id)]);
            }
            try {
                $st = a_notify($pdo, $uid, $book);
            } catch (Throwable $e) {
                error_log('assistant notify failed: ' . $e->getMessage());
                $st = 'error';
            }
            $save();
            if ($st === 'available') return a_reply('«' . $title . '» արդեն առկա է, կարող եք ավելացնել զամբյուղ։', ['books' => $payload, 'chips' => ['Ավելացնել զամբյուղ', $simChip]]);
            if ($st === 'error') return a_reply('Չհաջողվեց գրանցել ծանուցումը։ Փորձեք գրքի էջից։', ['books' => $payload]);
            return a_reply('Լավ, կծանուցեմ էլ. փոստով, երբ «' . $title . '» գիրքը նորից հասանելի լինի։', ['books' => $payload, 'chips' => [$simChip, 'Ամենահայտնի գրքերը']]);
        }
        if (!$similarWord) {
            $save();
            $chips = $stock > 0 ? ['Ավելացնել զամբյուղ', $simChip, 'Ուրիշ տարբերակներ'] : ['Ծանուցել, երբ հասանելի լինի', $simChip];
            if ($detailWord && !$stockWord && !$priceWord) {
                $desc = a_short((string)$book['description']);
                $text = '«' . $title . '», ' . $book['author'] . '։ ' . ($desc !== '' ? $desc : 'Նկարագրություն դեռ չկա։') . ' Գինը՝ ' . a_amd($book['price']) . '։';
                return a_reply($text, ['books' => $payload, 'chips' => $chips]);
            }
            if ($stockWord) {
                $text = '«' . $title . '»՝ ' . a_stock_text($stock) . '։';
                if ($stock >= 1) $text .= ' Գինը՝ ' . a_amd($book['price']) . '։';
                return a_reply($text, ['books' => $payload, 'chips' => $chips]);
            }
            if ($priceWord) {
                return a_reply('«' . $title . '»՝ ' . a_amd($book['price']) . '։ Գիրքը ' . a_stock_text($stock) . '։', ['books' => $payload, 'chips' => $chips]);
            }
            $text = a_pick(['Ահա ինչ գտա։', 'Սա է ձեր փնտրածը։']) . ' «' . $title . '», ' . $book['author'] . ', ' . a_amd($book['price']) . ', ' . a_stock_text($stock) . '։';
            return a_reply($text, ['books' => $payload, 'chips' => $chips]);
        }
        // $similarWord. անցնում ենք նմանատիպ գրքերի որոնմանը (ներքևում)
    }
    if (!$book && ($addWord || $notifyWord) && !$hasFilters && !$content) {
        $chips = [];
        $ordNames = ['առաջինը', 'երկրորդը', 'երրորդը', 'չորրորդը'];
        foreach (array_slice($ids, 0, 4) as $i => $unused) {
            $chips[] = ($notifyWord ? 'Ծանուցել ' : 'Ավելացնել ') . $ordNames[$i];
        }
        return a_reply($ids ? 'Որ մեկը նկատի ունեք։ Նշեք համարը կամ վերնագիրը։' : 'Նախ ասեք՝ ինչ գիրք եք փնտրում, և ես կառաջարկեմ տարբերակներ։', [
            'chips' => $chips ?: $popular]);
    }

    // ----- Չհասկացված հարց -----
    $recWord = a_has($tokens, ['խորհուրդ', 'առաջարկ', 'առաջարկիր', 'recommend', 'suggest', 'փնտր', 'գիրք', 'գրքեր', 'գրքի', 'կարդալ', 'կարդամ', 'ուզում', 'ցույց']);
    if (!$hasFilters && !$content && !$anyBookIntent && !$book && !$recWord) {
        $r = a_init($pdo);
        $r['reply'] = 'Այդ հարցին ճիշտ պատասխան չունեմ։ Կարող եմ առաջարկել գիրք ըստ ժանրի, հեղինակի կամ բյուջեի, ստուգել պաշարը, ցույց տալ ձեր պատվերները կամ պատմել առաքման ու զեղչի մասին։';
        return $r;
    }

    // ----- Որոնում / առաջարկ ֆիլտրերով -----
    $prevCtx = $ctx;
    if ($similarWord && $book) {
        $ctx['cat'] = $book['category_id'] ? (int)$book['category_id'] : null;
        $ctx['cat_name'] = $book['category_name'] ?? null;
        $ctx['theme'] = null;
        $ctx['q'] = [];
        $ctx['offset'] = 0;
    }
    $fresh = false; // նոր թեմա՝ էջավորումը զրոյացվում է
    if ($clearPrice) { $ctx['min'] = null; $ctx['max'] = null; $fresh = true; }
    if (!$noPrice) { $ctx['min'] = $pmin; $ctx['max'] = $pmax; $fresh = true; }
    if ($cat !== null) {
        $ctx['cat'] = (int)$cat['id'];
        $ctx['cat_name'] = (string)$cat['name_hy'];
        $ctx['theme'] = null;
        $ctx['q'] = $content;
        $fresh = true;
    } elseif ($theme !== null) {
        $ctx['theme'] = $theme;
        $ctx['cat'] = null;
        $ctx['cat_name'] = null;
        $ctx['q'] = $content;
        $fresh = true;
    } elseif ($content && !$similarWord) {
        $ctx['q'] = $content;
        $fresh = true;
    }
    if ($sortReq !== null) { $ctx['sort'] = $sortReq; $fresh = true; }
    if ($cheaper) {
        if ($ctx['prices']) {
            $ctx['max'] = max(200, (int)floor(min($ctx['prices'])) - 1);
            $ctx['min'] = null;
            $ctx['sort'] = null;
        } else {
            $ctx['sort'] = 'price_asc';
        }
        $fresh = true;
    }
    if ($pricier) {
        if ($ctx['prices']) {
            $ctx['min'] = (int)ceil(max($ctx['prices'])) + 1;
            $ctx['max'] = null;
            $ctx['sort'] = null;
        } else {
            $ctx['sort'] = 'price_desc';
        }
        $fresh = true;
    }
    if ($fresh) $ctx['offset'] = 0;
    elseif ($moreWord) $ctx['offset'] = (int)$ctx['offset'] + A_PER_PAGE;

    $filters = [
        'min' => $ctx['min'], 'max' => $ctx['max'], 'cat' => $ctx['cat'], 'theme' => $ctx['theme'],
        'sort' => $ctx['sort'], 'q' => $ctx['q'], 'exclude' => ($similarWord && $book) ? (int)$book['id'] : 0,
    ];
    $all = a_books_filtered($pdo, $filters);
    $relaxedQ = false;
    if (!$all && $filters['q']) {
        // Բովանդակային բառը կատալոգում չկա. փորձում ենք առանց դրա
        $filters['q'] = [];
        $all = a_books_filtered($pdo, $filters);
        if ($all) { $relaxedQ = true; $ctx['q'] = []; }
    }
    $cheaperFailed = false;
    if (!$all && ($cheaper || $pricier)) {
        // Ավելի էժան/թանկ տարբերակ չկա. վերադառնում ենք նախորդ ընտրությանը
        $ctx = $prevCtx;
        $filters = ['min' => $ctx['min'], 'max' => $ctx['max'], 'cat' => $ctx['cat'], 'theme' => $ctx['theme'], 'sort' => $ctx['sort'], 'q' => $ctx['q'], 'exclude' => 0];
        $all = a_books_filtered($pdo, $filters);
        $ctx['offset'] = 0;
        $cheaperFailed = true;
    }
    $total = count($all);
    $offset = (int)$ctx['offset'];

    $endNote = false;
    if ($total > 0 && $offset >= $total) {
        // Էջավորման վերջը. սկսում ենք սկզբից
        $offset = 0;
        $ctx['offset'] = 0;
        $endNote = true;
    }
    $page = array_slice($all, $offset, A_PER_PAGE);

    $labels = a_filter_labels($ctx);
    $labelText = $labels ? ' (' . implode(', ', $labels) . ')' : '';
    $exceptCat = $ctx['cat'] ? (int)$ctx['cat'] : null;

    if (!$page) {
        $ctx['last'] = [];
        $ctx['prices'] = [];
        $ctx['focus'] = 0;
        $save();
        $text = $ctx['theme']
            ? 'Կատալոգում այս թեմային հստակ համապատասխանող հասանելի գիրք չգտա' . $labelText . '։ Փորձեք այլ ժանր, հեղինակ կամ բյուջե։'
            : 'Այս պայմաններին համապատասխանող հասանելի գիրք չգտա' . $labelText . '։ Փորձեք մեղմացնել բյուջեն կամ փոխել ժանրը։';
        $chips = ['Մաքրել ֆիլտրերը'];
        if ($ctx['max'] || $ctx['min']) array_unshift($chips, 'Ցանկացած գին');
        foreach (a_cat_chips($cats, $exceptCat, 2) as $c) $chips[] = $c;
        return a_reply($text, ['chips' => $chips]);
    }

    $ctx['last'] = array_map(fn($r) => (int)$r['id'], $page);
    $ctx['prices'] = array_map(fn($r) => (float)$r['price'], $page);
    $ctx['focus'] = count($page) === 1 ? (int)$page[0]['id'] : 0;
    $save();

    $remaining = max(0, $total - ($offset + count($page)));
    if ($similarWord && $book) {
        $text = '«' . $book['title_hy'] . '» գրքին նման տարբերակներ' . ($book['category_name'] ? ' «' . $book['category_name'] . '» բաժնից' : '') . '։';
    } elseif ($cheaperFailed) {
        $text = ($cheaper ? 'Սրանից էժան տարբերակ այս պայմաններով չկա։' : 'Սրանից թանկ տարբերակ այս պայմաններով չկա։') . ' Ահա նույն ընտրությունը.';
    } elseif ($endNote) {
        $text = 'Այս պայմաններով ավելի շատ գիրք չկա, ահա ցուցակը սկզբից' . $labelText . '։';
    } elseif ($relaxedQ) {
        $text = 'Նշված բառով ճշգրիտ համընկնում չգտա, ահա ընդհանուր տարբերակներ' . $labelText . '։';
    } elseif ($offset > 0) {
        $text = 'Ահա ևս մի քանի տարբերակ' . $labelText . '։';
    } else {
        $text = a_pick(['Ահա ձեզ համար ընտրածս', 'Գտա հետևյալ տարբերակները', 'Ահա ինչ կարող եմ առաջարկել']) . $labelText . '։';
    }
    if ($remaining > 0) $text .= ' Եվս ' . $remaining . ' գիրք կա՝ գրեք «ուրիշ տարբերակներ»։';
    if (count($page) === 1) $text .= ' Գինը՝ ' . a_amd($page[0]['price']) . ', ' . a_stock_text((int)$page[0]['stock']) . '։';

    $chips = [];
    if ($remaining > 0) $chips[] = 'Ուրիշ տարբերակներ';
    $chips[] = 'Ավելի էժան';
    if ($ctx['sort'] !== 'rating') $chips[] = 'Ավելի հայտնի';
    foreach (a_cat_chips($cats, $exceptCat, 2) as $c) $chips[] = $c;
    if ($labels) $chips[] = 'Մաքրել ֆիլտրերը';

    $links = [];
    if ($ctx['cat']) {
        foreach ($cats as $c) {
            if ((int)$c['id'] === (int)$ctx['cat']) {
                $links[] = ['label' => 'Տեսնել «' . $c['name_hy'] . '» բաժինը', 'url' => 'search.php?category=' . rawurlencode($c['name'])];
                break;
            }
        }
    }
    return a_reply($text, ['books' => a_payload($page), 'chips' => array_slice(array_values(array_unique($chips)), 0, 5), 'links' => $links]);
}

// ---------- Մուտքի ստուգումներ ----------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    a_out(['error' => 'Օգտագործեք POST հարցումը։'], 405);
}
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || !csrf_valid($payload['csrf'] ?? null)) {
    a_out(['error' => 'Հարցումն անվավեր է։ Թարմացրեք էջը և փորձեք կրկին։'], 403);
}

$now = time();
$_SESSION['assistant_requests'] = array_values(array_filter($_SESSION['assistant_requests'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['assistant_requests']) >= 30) {
    a_out(['error' => 'Մի փոքր սպասեք և նորից փորձեք։'], 429);
}
$_SESSION['assistant_requests'][] = $now;

$action = (string)($payload['action'] ?? 'chat');

try {
    if ($action === 'init') {
        a_out(a_init($pdo));
    }
    if ($action === 'reset') {
        $_SESSION['assistant_ctx'] = a_default_ctx();
        a_out(a_init($pdo));
    }
    if ($action === 'notify') {
        if (!is_logged_in()) {
            a_out(a_reply('Ծանուցում ստանալու համար անհրաժեշտ է մուտք գործել։', ['links' => a_login_links('index.php')]), 401);
        }
        $nbook = a_book_by_id($pdo, (int)($payload['book_id'] ?? 0));
        if (!$nbook) a_out(['error' => 'Գիրքը չի գտնվել։'], 404);
        $st = a_notify($pdo, current_user_id(), $nbook);
        $msg = $st === 'available'
            ? '«' . $nbook['title_hy'] . '» արդեն առկա է, կարող եք ավելացնել զամբյուղ։'
            : 'Լավ, կծանուցեմ էլ. փոստով, երբ «' . $nbook['title_hy'] . '» գիրքը հասանելի լինի։';
        a_out(a_reply($msg));
    }

    // chat
    $message = trim((string)($payload['message'] ?? ''));
    $len = (int)preg_match_all('/./us', $message);
    if ($message === '' || $len > 300) {
        a_out(['error' => 'Գրեք մինչև 300 նիշով հարցում։'], 422);
    }
    a_out(a_chat($pdo, $message));
} catch (Throwable $e) {
    error_log('assistant_api failed: ' . $e->getMessage());
    a_out(['error' => 'Օգնականը ժամանակավորապես անհասանելի է։ Փորձեք մի փոքր ուշ։'], 500);
}
