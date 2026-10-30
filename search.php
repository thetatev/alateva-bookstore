<?php
// search.php
$page_title = 'Որոնում';
require_once 'includes/header.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$price_filter = $_GET['price'] ?? 'all';
$sort = $_GET['sort'] ?? 'popularity';

$query = "SELECT b.*, c.name_hy as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
$params = [];

if ($search !== '') {
    // % և _ նիշերը չեն դառնում wildcard
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $query .= " AND (b.title_hy LIKE ? OR b.title LIKE ? OR b.author LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($category !== 'all') {
    $query .= " AND c.name = ?";
    $params[] = $category;
}

if ($price_filter === '0-3000') {
    $query .= " AND b.price < 3000";
} elseif ($price_filter === '3000-5000') {
    $query .= " AND b.price >= 3000 AND b.price < 5000";
} elseif ($price_filter === '5000+') {
    $query .= " AND b.price >= 5000";
}

// Էջավորում՝ նախ հաշվում ենք արդյունքների ընդհանուր քանակը
$count_stmt = $pdo->prepare('SELECT COUNT(*) ' . substr($query, strpos($query, ' FROM ')));
$count_stmt->execute($params);
$pg = paginate((int)$count_stmt->fetchColumn(), 12);

switch ($sort) {
    case 'price-low': $query .= " ORDER BY b.price ASC"; break;
    case 'price-high': $query .= " ORDER BY b.price DESC"; break;
    case 'rating': $query .= " ORDER BY b.rating DESC"; break;
    default: $query .= " ORDER BY b.reviews_count DESC, b.id DESC";
}

$query .= " LIMIT " . (int)$pg['per_page'] . " OFFSET " . (int)$pg['offset'];

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<section class="search-section">
    <div class="section-container">
        <h2 class="section-title">Գտեք ձեր գիրքը</h2>
        
        <form method="GET" class="search-box">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" placeholder="Որոնել գրքեր, հեղինակներ..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-button">Որոնել</button>
            </div>
            
            <div class="search-filters">
                <div class="filter-group">
                    <label>Կատեգորիա</label>
                    <select name="category">
                        <option value="all">Բոլորը</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo e($cat['name']); ?>" <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name_hy']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Գին</label>
                    <select name="price">
                        <option value="all">Բոլորը</option>
                        <option value="0-3000" <?php echo $price_filter == '0-3000' ? 'selected' : ''; ?>>Մինչև 3,000դ</option>
                        <option value="3000-5000" <?php echo $price_filter == '3000-5000' ? 'selected' : ''; ?>>3,000-5,000դ</option>
                        <option value="5000+" <?php echo $price_filter == '5000+' ? 'selected' : ''; ?>>5,000դ+</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Դասակարգել</label>
                    <select name="sort">
                        <option value="popularity" <?php echo $sort == 'popularity' ? 'selected' : ''; ?>>Հանրաճանաչություն</option>
                        <option value="price-low" <?php echo $sort == 'price-low' ? 'selected' : ''; ?>>Գին (աճող)</option>
                        <option value="price-high" <?php echo $sort == 'price-high' ? 'selected' : ''; ?>>Գին (նվազող)</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Գնահատական</option>
                    </select>
                </div>
            </div>
        </form>
        
        <div class="results-count">Գտնվել է <?php echo (int)$pg['total']; ?> գիրք<?php echo $pg['pages'] > 1 ? ' (էջ ' . (int)$pg['page'] . '/' . (int)$pg['pages'] . ')' : ''; ?></div>
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
            <div class="book-card" onclick="location.href='book-details.php?id=<?php echo $book['id']; ?>'">
                <div class="book-image-wrapper">
                    <img src="<?php echo e(book_cover($book)); ?>" alt="<?php echo e($book['title_hy']); ?>" class="book-cover" loading="lazy">
                </div>
                <div class="book-info">
                    <h3><?php echo htmlspecialchars($book['title_hy']); ?></h3>
                    <p><?php echo htmlspecialchars($book['author']); ?></p>
                    <div class="book-footer">
                        <span class="book-price"><?php echo number_format($book['price'], 0); ?>դ</span>
                        <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo (int)$book['id']; ?>, <?php echo js_attr($book['title_hy']); ?>, <?php echo (float)$book['price']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php echo pagination_html($pg, $_GET); ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>