<?php
// index.php
$page_title = 'Գլխավոր';
require_once 'includes/header.php';

// Get featured books
$stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 6");
$books = $stmt->fetchAll();

// Get categories
$stmt = $pdo->query("SELECT * FROM categories LIMIT 4");
$categories = $stmt->fetchAll();
?>

<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Բացեք նոր աշխարհներ գրքերի միջոցով</h1>
        <p class="hero-subtitle">Գտեք ձեր հաջորդ սիրելի գիրքը մեր ընտրված հավաքածուից</p>
        <div class="hero-buttons">
            <a href="#books" class="cta-button primary">Դիտել գրքերը</a>
            <a href="about.php" class="cta-button secondary">Իմացեք ավելին</a>
        </div>
    </div>
</section>

<section class="categories-section">
    <div class="section-container">
        <h2 class="section-title">Հայտնի կատեգորիաներ</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <a class="category-card" href="search.php?category=<?php echo urlencode($cat['name']); ?>">
                <div class="category-icon"><i class="fas <?php echo e($cat['icon'] ?? 'fa-book'); ?>"></i></div>
                <h3><?php echo htmlspecialchars($cat['name_hy']); ?></h3>
                <p><?php echo htmlspecialchars($cat['description']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="books-section" id="books">
    <div class="section-container">
        <h2 class="section-title">Առաջարկվող գրքեր</h2>
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
            <div class="book-card" onclick="location.href='book-details.php?id=<?php echo $book['id']; ?>'">
                <div class="book-image-wrapper">
                    <div class="book-image">
                        <img src="<?php echo e(book_cover($book)); ?>" alt="<?php echo e($book['title_hy']); ?>" class="book-cover" loading="lazy">
                    </div>
                </div>
                <div class="book-info">
                    <h3 class="book-title"><?php echo htmlspecialchars($book['title_hy']); ?></h3>
                    <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
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
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
