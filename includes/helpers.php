<?php
// includes/helpers.php - ընդհանուր օգնող ֆունկցիաներ
require_once __DIR__ . '/../config/app.php';

/** HTML escape (տեքստի և attribute-ների համար) */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Արժեք՝ HTML onclick="..." attribute-ի ներսում JS արգումենտ դարձնելու համար (անվտանգ) */
function js_attr($value): string {
    return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8');
}

// ---------- Մուտք և իրավունքներ ----------

function current_user_id(): int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function is_logged_in(): bool {
    return current_user_id() > 0;
}

function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
}

/** Թույլատրում է միայն տեղային .php էջեր՝ ?next= redirect-ի համար */
function safe_next(?string $next): string {
    if ($next && preg_match('/^[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_=&\-]*)?$/', $next)) {
        return $next;
    }
    return '';
}

function require_login(): void {
    if (!is_logged_in()) {
        $next = basename($_SERVER['PHP_SELF']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $next .= '?' . $_SERVER['QUERY_STRING'];
        }
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: login.php');
        exit;
    }
}

/** Մուտքի հաջողության դեպքում սեսիայի լրացում (session fixation-ից պաշտպանություն) */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin']   = (int)($user['is_admin'] ?? 0);
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------- CSRF ----------

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool {
    return is_string($token) && $token !== '' && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** HTML ձևերի համար՝ ձախողման դեպքում կանգնեցնում է հարցումը */
function csrf_check(): void {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Անվավեր հարցում (CSRF)։ Թարմացրեք էջը և փորձեք կրկին։');
    }
}

// ---------- JSON API օգնականներ ----------

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Հղումներ ----------

/** Կայքի բացարձակ հղում (նամակների համար). production-ում սահմանեք APP_URL */
function app_url(string $path = ''): string {
    if (APP_URL !== '') {
        $base = APP_URL;
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (!preg_match('/^[A-Za-z0-9.\-:]+$/', $host)) {
            $host = 'localhost';
        }
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $base = $scheme . '://' . $host . $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

// ---------- Էջավորում (pagination) ----------

/** Վերադարձնում է ընթացիկ էջի տվյալները՝ page, pages, per_page, offset, total */
function paginate(int $total, int $perPage): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    return ['page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage, 'total' => $total];
}

/** Էջերի հղումներ. $params՝ պահպանվող GET պարամետրեր (page-ը անտեսվում է) */
function pagination_html(array $p, array $params = []): string {
    if ($p['pages'] <= 1) {
        return '';
    }
    unset($params['page']);

    $link = function (int $n, string $label, bool $active = false) use ($params) {
        $style = 'padding:6px 12px; border:1px solid #ccc; border-radius:6px; text-decoration:none; color:inherit; min-width:36px; text-align:center;';
        if ($active) {
            $style .= ' background:var(--color-terracotta, #c0623b); color:#fff; border-color:var(--color-terracotta, #c0623b);';
        }
        $href = '?' . http_build_query($params + ['page' => $n]);
        return '<a href="' . e($href) . '" style="' . $style . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    };

    $html = '<nav aria-label="Էջեր" style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap; margin:1.5rem 0;">';
    if ($p['page'] > 1) {
        $html .= $link($p['page'] - 1, '‹');
    }
    $last = 0;
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($i === 1 || $i === $p['pages'] || abs($i - $p['page']) <= 2) {
            if ($last && $i - $last > 1) {
                $html .= '<span style="padding:6px 4px;">…</span>';
            }
            $html .= $link($i, (string)$i, $i === $p['page']);
            $last = $i;
        }
    }
    if ($p['page'] < $p['pages']) {
        $html .= $link($p['page'] + 1, '›');
    }
    return $html . '</nav>';
}

// ---------- Գրքի կազմ ----------

/** Կազմի հղումը. եթե նկար չկա, օգտագործվում է cover.php-ով գեներացված կազմը */
function book_cover(array $book): string {
    $cover = trim((string)($book['cover_image'] ?? ''));
    return $cover !== '' ? $cover : 'cover.php?id=' . (int)($book['id'] ?? 0);
}
