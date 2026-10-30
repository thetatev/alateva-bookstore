<?php
// cover.php?id=N - գրքի կազմ SVG-ով (օգտագործվում է, երբ գրքին իրական նկար չի տրված)
require_once __DIR__ . '/config/database.php';

function cv_len(string $s): int { return (int)preg_match_all('/./us', $s); }
function cv_sub(string $s, int $start, ?int $len = null): string {
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return implode('', array_slice($chars, $start, $len));
}
/** Բաժանում է տեքստը տողերի՝ մինչև $width նիշ, առավելագույնը $maxLines տող */
function cv_wrap(string $text, int $width, int $maxLines): array {
    $lines = [];
    $cur = '';
    foreach (preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
        while (cv_len($word) > $width) {            // չափազանց երկար բառ
            if ($cur !== '') { $lines[] = $cur; $cur = ''; }
            $lines[] = cv_sub($word, 0, $width);
            $word = cv_sub($word, $width);
        }
        if ($cur === '') $cur = $word;
        elseif (cv_len($cur) + 1 + cv_len($word) <= $width) $cur .= ' ' . $word;
        else { $lines[] = $cur; $cur = $word; }
    }
    if ($cur !== '') $lines[] = $cur;
    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, 0, $maxLines);
        $lines[$maxLines - 1] = rtrim(cv_sub($lines[$maxLines - 1], 0, max(1, $width - 1))) . '…';
    }
    return $lines;
}
function cv_e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
$title = 'Գիրք';
$author = '';
$category = '';
try {
    $stmt = $pdo->prepare('SELECT b.title_hy, b.author, c.name_hy AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.id = ?');
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $title = (string)$row['title_hy'];
        $author = (string)$row['author'];
        $category = (string)($row['category_name'] ?? '');
    }
} catch (Throwable $e) {
    error_log('cover.php failed: ' . $e->getMessage());
}

// Գույնը կախված է կատեգորիայից (կամ վերնագրից), որպեսզի նույն բաժնի գրքերը նմանվեն
$palette = [
    ['#8A9B8F', '#5f7165'], ['#C17D64', '#8f553f'], ['#A89F94', '#6e665c'], ['#6f8aa0', '#46606f'],
    ['#9a7aa0', '#6a4d70'], ['#b49a5a', '#7c6a38'], ['#7aa08a', '#4b705c'], ['#a07a7a', '#704a4a'],
];
$pick = $palette[crc32($category !== '' ? $category : $title) % count($palette)];

$titleLines = cv_wrap($title, 14, 5);
$authorLines = cv_wrap($author, 20, 2);
$size = count($titleLines) > 3 ? 25 : 30;

$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 450" width="300" height="450" role="img" aria-label="' . cv_e($title) . '">';
$svg .= '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $pick[0] . '"/><stop offset="1" stop-color="' . $pick[1] . '"/></linearGradient></defs>';
$svg .= '<rect width="300" height="450" fill="url(#g)"/>';
$svg .= '<rect x="0" y="0" width="14" height="450" fill="#000" opacity=".16"/>';
$svg .= '<rect x="34" y="34" width="232" height="382" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2"/>';
$svg .= '<line x1="70" y1="318" x2="230" y2="318" stroke="#fff" stroke-opacity=".7" stroke-width="2"/>';
$y = 150 - (count($titleLines) - 1) * ($size * 0.6);
foreach ($titleLines as $line) {
    $svg .= '<text x="150" y="' . round($y) . '" text-anchor="middle" font-family="Georgia, \'Noto Serif Armenian\', serif" font-size="' . $size . '" font-weight="700" fill="#fff">' . cv_e($line) . '</text>';
    $y += $size * 1.25;
}
$y = 352;
foreach ($authorLines as $line) {
    $svg .= '<text x="150" y="' . $y . '" text-anchor="middle" font-family="Arial, \'Noto Sans Armenian\', sans-serif" font-size="17" fill="#fff" opacity=".92">' . cv_e($line) . '</text>';
    $y += 23;
}
if ($category !== '') {
    $svg .= '<text x="150" y="398" text-anchor="middle" font-family="Arial, \'Noto Sans Armenian\', sans-serif" font-size="12" letter-spacing="2" fill="#fff" opacity=".7">' . cv_e(function_exists('mb_strtoupper') ? mb_strtoupper($category, 'UTF-8') : $category) . '</text>';
}
$svg .= '</svg>';

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $svg;
