<?php
// book-details.php
$page_title = 'Գրքի մանրամասներ';
require_once 'config/database.php';
require_once 'includes/helpers.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get book details
$stmt = $pdo->prepare("SELECT b.*, c.name_hy as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    $page_title = 'Գիրքը չի գտնվել';
    require_once 'includes/header.php';
    echo "<div class='container' style='text-align:center; padding:4rem 1rem;'><h2>Գիրքը չի գտնվել</h2><p><a href='search.php'>← Վերադառնալ գրքերի ցանկին</a></p></div>";
    require_once 'includes/footer.php';
    exit;
}

$page_title = $book['title_hy'];
$book['rating'] = (float)($book['rating'] ?? 0);
$book['reviews_count'] = (int)($book['reviews_count'] ?? 0);
$book['stock'] = (int)($book['stock'] ?? 0);

// «Ծանուցել, երբ հասանելի լինի» - արդյո՞ք օգտատերը արդեն բաժանորդագրվել է
$is_subscribed = false;
if ($book['stock'] < 1 && is_logged_in()) {
    try {
        $sub = $pdo->prepare('SELECT 1 FROM stock_notifications WHERE book_id = ? AND user_id = ?');
        $sub->execute([$book['id'], current_user_id()]);
        $is_subscribed = (bool)$sub->fetchColumn();
    } catch (Throwable $ex) {
        // աղյուսակը դեռ չկա (database_upgrade.sql-ը չի գործարկվել)
    }
}
$notify_msg = $_GET['notify'] ?? '';

// Get related books
$stmt = $pdo->prepare("SELECT * FROM books WHERE category_id = ? AND id != ? LIMIT 4");
$stmt->execute([$book['category_id'], $book_id]);
$related_books = $stmt->fetchAll();

// Get reviews for this book
$reviews_stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.book_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->execute([$book_id]);
$reviews = $reviews_stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="breadcrumb-section">
    <div class="nav-container">
        <div class="breadcrumb">
            <a href="index.php">Գլխավոր</a>
            <i class="fas fa-chevron-right"></i>
            <a href="search.php">Գրքեր</a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($book['title_hy']); ?></span>
        </div>
    </div>
</div>

<section class="book-details-section">
    <div class="nav-container">
        <div class="book-details-container">
            <div class="book-details-image">
                <div class="book-large-image">
                    <img src="<?php echo e(book_cover($book)); ?>" 
                         alt="<?php echo htmlspecialchars($book['title_hy']); ?>" 
                         class="book-large-cover" id="mainBookImage">
                </div>
            </div>
            
            <div class="book-details-info">
                <div class="book-header">
                    <h1 class="book-title-large"><?php echo htmlspecialchars($book['title_hy']); ?></h1>
                    <div class="book-author-large">
                        <span>Հեղինակ:</span>
                        <span class="author-link"><?php echo htmlspecialchars($book['author']); ?></span>
                    </div>
                    
                    <div class="book-rating-large">
                        <div class="stars">
                            <?php
                            $full = floor($book['rating']);
                            $half = ($book['rating'] - $full) >= 0.5;
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $full) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif($i == $full+1 && $half) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="rating-value"><?php echo number_format($book['rating'], 1); ?>/5</span>
                        <span class="review-count">(<?php echo number_format($book['reviews_count']); ?> գնահատական)</span>
                    </div>
                </div>
                
                <div class="book-price-section">
                    <div class="price-main">
                        <span class="current-price"><?php echo number_format($book['price'], 0); ?>դ</span>
                        <?php if($book['original_price']): ?>
                        <span class="original-price"><?php echo number_format($book['original_price'], 0); ?>դ</span>
                        <span class="discount">-<?php echo round(($book['original_price'] - $book['price']) / $book['original_price'] * 100); ?>%</span>
                        <?php endif; ?>
                    </div>
                    <div class="stock-status <?php echo $book['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>" style="<?php echo ($book['stock'] > 0 && $book['stock'] <= 5) ? 'color:#e67e22;' : ''; ?>">
                        <i class="fas <?php echo $book['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <span><?php
                            if ($book['stock'] < 1) { echo 'Չկա պահեստում'; }
                            elseif ($book['stock'] <= 5) { echo 'Մնացել է միայն ' . (int)$book['stock'] . ' հատ'; }
                            else { echo 'Առկա է պահեստում'; }
                        ?></span>
                    </div>
                </div>
                
                <div class="book-description">
                    <h3>Նկարագրություն</h3>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    
                    <div class="book-specs">
                        <div class="spec-item">
                            <span class="spec-label">Հրատարակչություն:</span>
                            <span class="spec-value"><?php echo htmlspecialchars($book['publisher'] ?? 'Նշված չէ'); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Էջեր:</span>
                            <span class="spec-value"><?php echo e($book['pages'] ?? 'Նշված չէ'); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Տարեթիվ:</span>
                            <span class="spec-value"><?php echo e($book['year'] ?? 'Նշված չէ'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="book-actions">
                    <div class="quantity-selector">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" max="<?php echo max(1, min(10, $book['stock'])); ?>">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($book['stock'] > 0): ?>
                        <button class="add-to-cart-btn primary" onclick="addToCart(<?php echo (int)$book['id']; ?>, <?php echo js_attr($book['title_hy']); ?>, <?php echo (float)$book['price']; ?>, document.querySelector('.quantity-input').value)">
                            <i class="fas fa-shopping-cart"></i> Ավելացնել զամբյուղում
                        </button>
                        <?php else: ?>
                        <button class="add-to-cart-btn primary" disabled style="opacity:.6; cursor:not-allowed;">
                            <i class="fas fa-times-circle"></i> Սպառված է
                        </button>
                        <?php if (!is_logged_in()): ?>
                        <a href="login.php?next=<?php echo urlencode('book-details.php?id=' . (int)$book['id']); ?>" class="add-to-wishlist-btn" style="text-decoration:none; text-align:center;">
                            <i class="fas fa-bell"></i> Մուտք գործեք՝ ծանուցում ստանալու համար
                        </a>
                        <?php else: ?>
                        <form method="POST" action="notify_stock.php" style="display:contents;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
                            <?php if ($is_subscribed): ?>
                            <input type="hidden" name="action" value="unsubscribe">
                            <button type="submit" class="add-to-wishlist-btn"><i class="fas fa-bell-slash"></i> Չեղարկել ծանուցումը</button>
                            <?php else: ?>
                            <input type="hidden" name="action" value="subscribe">
                            <button type="submit" class="add-to-wishlist-btn"><i class="fas fa-bell"></i> Ծանուցել, երբ հասանելի լինի</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                        <?php if ($notify_msg === 'on' || $is_subscribed): ?>
                        <p style="color:#27ae60; margin-top:0.5rem;">✅ Կուղարկենք նամակ, երբ գիրքը նորից առկա լինի</p>
                        <?php elseif ($notify_msg === 'off'): ?>
                        <p style="color:var(--color-warm-gray); margin-top:0.5rem;">Ծանուցումը չեղարկվեց</p>
                        <?php elseif ($notify_msg === 'error'): ?>
                        <p style="color:#e74c3c; margin-top:0.5rem;">Չհաջողվեց։ Փորձեք կրկին</p>
                        <?php endif; ?>
                        <?php endif; ?>
                        <button class="add-to-wishlist-btn" onclick="addToWishlist(<?php echo (int)$book['id']; ?>, <?php echo js_attr($book['title_hy']); ?>, <?php echo (float)$book['price']; ?>)">
                            <i class="fas fa-heart"></i> Ավելացնել ցանկում
                        </button>
                    </div>
                </div>
                
                <div class="book-meta">
                    <div class="meta-item"><i class="fas fa-shipping-fast"></i><span>Առաքում 3-5 աշխատանքային օրում</span></div>
                    <div class="meta-item"><i class="fas fa-shield-alt"></i><span>100% անվտանգ գնումներ</span></div>
                    <div class="meta-item"><i class="fas fa-undo"></i><span>14 օր վերադարձի երաշխիք</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="book-tabs-section">
    <div class="nav-container">
        <div class="book-tabs">
            <button class="book-tab-btn active" data-tab="description">📖 Մանրամասն նկարագրություն</button>
            <button class="book-tab-btn" data-tab="reviews">⭐ Գնահատականներ (<?php echo count($reviews); ?>)</button>
            <button class="book-tab-btn" data-tab="author">✍️ Հեղինակի մասին</button>
        </div>
        
        <div class="book-tab-content active" id="description-tab">
            <div class="detailed-description">
                <h3>Գրքի մասին</h3>
                <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            </div>
        </div>
        
        <div class="book-tab-content" id="reviews-tab">
            <div class="reviews-container">
                <div class="reviews-summary" style="margin-bottom:2rem; padding:1rem; background:var(--color-cream); border-radius:8px;">
                    <div style="display:flex; align-items:center; gap:2rem; flex-wrap:wrap;">
                        <div style="text-align:center;">
                            <div style="font-size:3rem; font-weight:bold; color:var(--color-terracotta);"><?php echo number_format($book['rating'], 1); ?></div>
                            <div class="stars">
                                <?php
                                $full = floor($book['rating']);
                                for($i=1; $i<=5; $i++) {
                                    if($i <= $full) {
                                        echo '<i class="fas fa-star" style="color:#FFD700;"></i>';
                                    } elseif($i == $full+1 && ($book['rating'] - $full) >= 0.5) {
                                        echo '<i class="fas fa-star-half-alt" style="color:#FFD700;"></i>';
                                    } else {
                                        echo '<i class="far fa-star" style="color:#ddd;"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div><small><?php echo number_format($book['reviews_count']); ?> գնահատական</small></div>
                        </div>
                        <div>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="add_review.php?book_id=<?php echo $book_id; ?>" class="cta-button primary" style="display:inline-block; padding:0.8rem 1.5rem;">
                                    ✍️ Գրել կարծիք
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="cta-button secondary" style="display:inline-block; padding:0.8rem 1.5rem;">
                                    🔑 Մուտք գործեք կարծիք գրելու համար
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if(count($reviews) > 0): ?>
                    <div class="reviews-list">
                        <?php foreach($reviews as $review): ?>
                        <div style="border-bottom:1px solid var(--color-light-gray); padding:1.5rem 0;">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:0.5rem;">
                                <div>
                                    <strong><?php echo e(trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')) ?: ($review['reviewer_name'] ?? 'Անանուն')); ?></strong>
                                    <small style="color:var(--color-warm-gray); margin-left:0.5rem;">📅 <?php echo date('d.m.Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <div class="stars">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fas fa-star" style="color:#FFD700;"></i>
                                        <?php else: ?>
                                            <i class="far fa-star" style="color:#ddd;"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if($review['title']): ?>
                                <p style="font-weight:bold; margin-top:0.5rem;">"<?php echo htmlspecialchars($review['title']); ?>"</p>
                            <?php endif; ?>
                            <p style="color:var(--color-warm-gray); margin-top:0.3rem;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--color-warm-gray); text-align:center; padding:2rem;">Դեռևս կարծիքներ չկան: Եղեք առաջինը:</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="book-tab-content" id="author-tab">
            <div class="author-info">
                <h3><?php echo htmlspecialchars($book['author']); ?></h3>
                <p style="color:var(--color-warm-gray);">Հայտնի հայ գրող</p>
            </div>
        </div>
    </div>
</section>

<?php if(count($related_books) > 0): ?>
<section class="related-books-section">
    <div class="nav-container">
        <h2 class="section-title">Նմանատիպ գրքեր</h2>
        <div class="books-grid">
            <?php foreach($related_books as $rbook): ?>
            <div class="book-card" onclick="location.href='book-details.php?id=<?php echo $rbook['id']; ?>'">
                <div class="book-image-wrapper">
                    <img src="<?php echo e(book_cover($rbook)); ?>" alt="<?php echo e($rbook['title_hy']); ?>" class="book-cover" loading="lazy">
                </div>
                <div class="book-info">
                    <h3 class="book-title"><?php echo htmlspecialchars($rbook['title_hy']); ?></h3>
                    <p class="book-author"><?php echo htmlspecialchars($rbook['author']); ?></p>
                    <div class="book-footer">
                        <span class="book-price"><?php echo number_format($rbook['price'], 0); ?>դ</span>
                        <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?php echo (int)$rbook['id']; ?>, <?php echo js_attr($rbook['title_hy']); ?>, <?php echo (float)$rbook['price']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Quantity selector
document.querySelectorAll('.quantity-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        let input = this.parentElement.querySelector('.quantity-input');
        let val = parseInt(input.value);
        if(this.classList.contains('minus') && val > 1) input.value = val - 1;
        if(this.classList.contains('plus') && val < <?php echo max(1, min(10, $book['stock'])); ?>) input.value = val + 1;
    });
});

// Tabs functionality
document.querySelectorAll('.book-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.book-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.book-tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.dataset.tab + '-tab').classList.add('active');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>