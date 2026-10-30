<?php
// add_review.php - FULL WORKING VERSION
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_login();

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$error = '';

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if(!$book) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, rating, title, comment FROM reviews WHERE book_id = ? AND user_id = ?");
$stmt->execute([$book_id, $_SESSION['user_id']]);
$already_reviewed = $stmt->fetch();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $rating = (int)($_POST['rating'] ?? 0);
    $review_title = mb_substr(trim($_POST['review_title'] ?? ''), 0, 150);
    $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 2000);
    
    if($rating < 1 || $rating > 5) {
        $error = 'Գնահատականը պետք է լինի 1-5 միջակայքում';
    } elseif(empty($comment)) {
        $error = 'Խնդրում եմ գրել ձեր կարծիքը';
    } else {
        if($already_reviewed) {
            $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, title = ?, comment = ? WHERE book_id = ? AND user_id = ?");
            $stmt->execute([$rating, $review_title, $comment, $book_id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (book_id, user_id, reviewer_name, rating, title, comment) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$book_id, $_SESSION['user_id'], $_SESSION['user_name'], $rating, $review_title, $comment]);
        }

        // Միջին գնահատականը վերահաշվարկվում է և՛ նոր, և՛ փոփոխված կարծիքի դեպքում
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE book_id = ?");
        $stmt->execute([$book_id]);
        $stats = $stmt->fetch();

        $stmt = $pdo->prepare("UPDATE books SET rating = ?, reviews_count = ? WHERE id = ?");
        $stmt->execute([round((float)$stats['avg_rating'], 1), (int)$stats['total'], $book_id]);

        header('Location: book-details.php?id=' . $book_id . '&review=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Գնահատել գիրքը - Alateva</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rating-container {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 8px;
        }
        .rating-container input {
            display: none;
        }
        .rating-container label {
            font-size: 32px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.1s;
        }
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-light-gray);
            border-radius: 8px;
            font-family: inherit;
        }
        textarea.form-input {
            resize: vertical;
        }
        .error-msg {
            background: #fee;
            color: #c00;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c00;
        }
    </style>
</head>
<body>
<div class="bookshop-container">
    <?php require_once 'includes/header.php'; ?>
    
    <div style="max-width: 600px; margin: 2rem auto; padding: 0 1rem;">
        <div style="background: var(--color-off-white); border: 1px solid var(--color-light-gray); border-radius: 16px; overflow: hidden;">
            
            <div style="background: var(--color-charcoal); color: white; padding: 1.5rem; text-align: center;">
                <h2 style="margin: 0;"><i class="fas fa-star"></i> Գնահատել գիրքը</h2>
                <p style="margin: 0.5rem 0 0; opacity: 0.8;">Կիսվեք ձեր կարծիքով մյուս ընթերցողների հետ</p>
            </div>
            
            <div style="padding: 1.5rem; background: var(--color-cream); border-bottom: 1px solid var(--color-light-gray);">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if(!empty($book['id'])): ?>
                        <img src="<?php echo htmlspecialchars(book_cover($book)); ?>" style="width: 70px; height: 95px; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                        <div style="width: 70px; height: 95px; background: var(--color-light-gray); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-book" style="font-size: 2rem; color: var(--color-warm-gray);"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3 style="margin: 0 0 0.3rem; color: var(--color-charcoal);"><?php echo htmlspecialchars($book['title_hy']); ?></h3>
                        <p style="margin: 0; color: var(--color-warm-gray);"><i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p style="margin: 0.3rem 0 0; color: var(--color-terracotta); font-weight: bold;"><?php echo number_format($book['price'], 0); ?> դրամ</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" style="padding: 1.5rem;">
                <?php echo csrf_field(); ?>
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.8rem; font-weight: 600; color: var(--color-charcoal);">
                        <i class="fas fa-star" style="color: #f39c12;"></i> Ձեր գնահատականը <span style="color: #e74c3c;">*</span>
                    </label>
                    <div class="rating-container">
                        <input type="radio" name="rating" value="5" id="star5" <?php echo ($already_reviewed && $already_reviewed['rating'] == 5) ? 'checked' : ''; ?>>
                        <label for="star5" class="fas fa-star"></label>
                        
                        <input type="radio" name="rating" value="4" id="star4" <?php echo ($already_reviewed && $already_reviewed['rating'] == 4) ? 'checked' : ''; ?>>
                        <label for="star4" class="fas fa-star"></label>
                        
                        <input type="radio" name="rating" value="3" id="star3" <?php echo ($already_reviewed && $already_reviewed['rating'] == 3) ? 'checked' : ''; ?>>
                        <label for="star3" class="fas fa-star"></label>
                        
                        <input type="radio" name="rating" value="2" id="star2" <?php echo ($already_reviewed && $already_reviewed['rating'] == 2) ? 'checked' : ''; ?>>
                        <label for="star2" class="fas fa-star"></label>
                        
                        <input type="radio" name="rating" value="1" id="star1" <?php echo ($already_reviewed && $already_reviewed['rating'] == 1) ? 'checked' : ''; ?>>
                        <label for="star1" class="fas fa-star"></label>
                    </div>
                    <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--color-warm-gray);">
                        <i class="fas fa-info-circle"></i> 1 աստղ = վատ, 5 աստղ = հիանալի (սեղմեք աստղի վրա)
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-charcoal);">
                        <i class="fas fa-heading"></i> Կարծիքի վերնագիր (ըստ ցանկության)
                    </label>
                    <input type="text" name="review_title" class="form-input" placeholder="Օրինակ՝ Հրաշալի գիրք" value="<?php echo htmlspecialchars($already_reviewed['title'] ?? ''); ?>">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-charcoal);">
                        <i class="fas fa-comment"></i> Ձեր կարծիքը <span style="color: #e74c3c;">*</span>
                    </label>
                    <textarea name="comment" rows="5" class="form-input" placeholder="Գրեք ձեր տպավորությունները գրքի մասին..." required><?php echo htmlspecialchars($already_reviewed['comment'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="background: var(--color-terracotta); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem;">
                        <i class="fas fa-paper-plane"></i> Ուղարկել կարծիքը
                    </button>
                    <a href="book-details.php?id=<?php echo $book_id; ?>" style="background: var(--color-light-gray); color: var(--color-charcoal); border: none; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-times"></i> Չեղարկել
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var stars = document.querySelectorAll('.rating-container label');
    var container = document.querySelector('.rating-container');
    
    function getStarNumber(star) {
        var forId = star.getAttribute('for');
        return parseInt(forId.replace('star', ''));
    }
    
    function setStarsColor(rating) {
        stars.forEach(function(star) {
            var starNum = getStarNumber(star);
            if (starNum <= rating) {
                star.style.color = '#FFD700';
            } else {
                star.style.color = '#ddd';
            }
        });
    }
    
    function getSelectedRating() {
        var checked = document.querySelector('.rating-container input:checked');
        if (checked) {
            return parseInt(checked.value);
        }
        return 0;
    }
    
    stars.forEach(function(star) {
        star.addEventListener('click', function(e) {
            var forId = this.getAttribute('for');
            var radio = document.getElementById(forId);
            if (radio) {
                radio.checked = true;
                var rating = parseInt(radio.value);
                setStarsColor(rating);
            }
        });
        
        star.addEventListener('mouseenter', function() {
            var rating = getStarNumber(this);
            setStarsColor(rating);
        });
    });
    
    container.addEventListener('mouseleave', function() {
        var rating = getSelectedRating();
        setStarsColor(rating);
    });
    
    // Initialize
    var initialRating = getSelectedRating();
    setStarsColor(initialRating);
});
</script>
</body>
</html>