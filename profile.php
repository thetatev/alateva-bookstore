<?php
// profile.php
$page_title = 'Իմ պրոֆիլը';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_login();

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // Հաշիվը ջնջվել է՝ փակում ենք սեսիան
    logout_user();
    header('Location: login.php');
    exit;
}

// Get user orders from database
$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$_SESSION['user_id']]);
$user_orders = $orders->fetchAll();

// Get user reviews
$reviews = $pdo->prepare("SELECT r.*, b.title_hy, b.cover_image FROM reviews r LEFT JOIN books b ON r.book_id = b.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
$reviews->execute([$_SESSION['user_id']]);
$user_reviews = $reviews->fetchAll();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    csrf_check();
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if(empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'Անունը, ազգանունը և էլ.փոստը պարտադիր են';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Էլ. հասցեն սխալ է';
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if($stmt->fetch()) {
            $error = 'Այս էլ.փոստն արդեն գրանցված է';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $_SESSION['user_id']]);
            $success = '✅ Տվյալները հաջողությամբ թարմացվեցին';
            
            // Update session
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}

$pw_success = '';
$pw_error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    csrf_check();
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw = $_POST['new_password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if(!password_verify($current_pw, $user['password_hash'])) {
        $pw_error = 'Ընթացիկ գաղտնաբառը սխալ է';
    } elseif(strlen($new_pw) < 8) {
        $pw_error = 'Նոր գաղտնաբառը պետք է լինի առնվազն 8 նիշ';
    } elseif($new_pw !== $confirm_pw) {
        $pw_error = 'Նոր գաղտնաբառերը չեն համապատասխանում';
    } elseif($new_pw === $current_pw) {
        $pw_error = 'Նոր գաղտնաբառը պետք է տարբերվի ընթացիկից';
    } else {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($new_pw, PASSWORD_DEFAULT), current_user_id()]);
        session_regenerate_id(true);
        $pw_success = '✅ Գաղտնաբառը հաջողությամբ փոխվեց';
    }
}

require_once 'includes/header.php';
?>

<section class="profile-hero">
    <div class="profile-hero-content">
        <div class="profile-avatar"><i class="fas fa-user fa-3x"></i></div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <div class="member-since">
                <i class="far fa-calendar-alt"></i> 
                <?php if (!empty($user['member_since'])): ?>Անդամ է <?php echo date('Y.m.d', strtotime($user['member_since'])); ?>-ից<?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="profile-container">
    <aside class="profile-sidebar">
        <ul class="sidebar-menu">
            <li class="sidebar-item"><a href="#personal-info" class="sidebar-link active">👤 Անձնական տվյալներ</a></li>
            <li class="sidebar-item"><a href="#orders" class="sidebar-link">📦 Իմ պատվերները (<?php echo count($user_orders); ?>)</a></li>
            <li class="sidebar-item"><a href="#reviews" class="sidebar-link">⭐ Իմ կարծիքները (<?php echo count($user_reviews); ?>)</a></li>
            <li class="sidebar-item"><a href="#wishlist" class="sidebar-link">❤️ Ցանկություններ</a></li>
            <li class="sidebar-item"><a href="#security" class="sidebar-link">🔒 Գաղտնաբառ</a></li>
        </ul>
    </aside>
    
    <main class="profile-content">
        <!-- Personal Info - EDITABLE -->
        <section id="personal-info" class="profile-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-user-circle"></i></div>
                <h2 class="card-title">Անձնական տվյալներ</h2>
            </div>
            
            <?php if($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" id="profileForm">
                <?php echo csrf_field(); ?>
                <div class="info-item">
                    <label class="info-label">Անուն</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="form-input" style="width: 250px;" required>
                </div>
                <div class="info-item">
                    <label class="info-label">Ազգանուն</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="form-input" style="width: 250px;" required>
                </div>
                <div class="info-item">
                    <label class="info-label">Էլ. փոստ</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-input" style="width: 250px;" required>
                </div>
                <div class="info-item">
                    <label class="info-label">Հեռախոս</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input" style="width: 250px;" placeholder="+374 XX XXX XXX">
                </div>
                <button type="submit" name="update_profile" class="edit-button">
                    <i class="fas fa-save"></i> Պահպանել փոփոխությունները
                </button>
            </form>
        </section>

        <!-- Change password -->
        <section id="security" class="profile-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-lock"></i></div>
                <h2 class="card-title">Գաղտնաբառի փոփոխություն</h2>
            </div>
            <?php if($pw_success): ?>
                <div class="message success"><?php echo e($pw_success); ?></div>
            <?php endif; ?>
            <?php if($pw_error): ?>
                <div class="message error"><?php echo e($pw_error); ?></div>
            <?php endif; ?>
            <form method="POST" id="passwordForm">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="current_password">Ընթացիկ գաղտնաբառ</label>
                    <input type="password" id="current_password" name="current_password" class="form-input" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Նոր գաղտնաբառ (առնվազն 8 նիշ)</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Հաստատել նոր գաղտնաբառը</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8" autocomplete="new-password" required>
                </div>
                <button type="submit" name="change_password" value="1" class="edit-button">Փոխել գաղտնաբառը</button>
            </form>
        </section>
        
        <!-- Orders Section -->
        <section id="orders" class="profile-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-shopping-bag"></i></div>
                <h2 class="card-title">Իմ պատվերները</h2>
            </div>
            
            <?php if(count($user_orders) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: var(--color-charcoal); color: white;">
                                <th style="padding: 12px 15px; text-align: left;">Պատվերի համար</th>
                                <th style="padding: 12px 15px; text-align: left;">Ամսաթիվ</th>
                                <th style="padding: 12px 15px; text-align: left;">Ընդհանուր</th>
                                <th style="padding: 12px 15px; text-align: left;">Կարգավիճակ</th>
                                <th style="padding: 12px 15px; text-align: left;">Գործողություն</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($user_orders as $order): ?>
                            <tr style="border-bottom: 1px solid var(--color-light-gray);">
                                <td style="padding: 12px 15px;">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td style="padding: 12px 15px; color: var(--color-warm-gray);">
                                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <strong style="color: var(--color-terracotta);"><?php echo number_format($order['total_amount'], 0); ?> դրամ</strong>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $status_icon = '';
                                    switch($order['status']) {
                                        case 'pending': 
                                            $status_class = 'status-pending'; 
                                            $status_text = 'Սպասման մեջ'; 
                                            $status_icon = '⏳';
                                            break;
                                        case 'processing': 
                                            $status_class = 'status-processing'; 
                                            $status_text = 'Մշակվում է'; 
                                            $status_icon = '⚙️';
                                            break;
                                        case 'shipped': 
                                            $status_class = 'status-shipped'; 
                                            $status_text = 'Առաքված'; 
                                            $status_icon = '🚚';
                                            break;
                                        case 'delivered': 
                                            $status_class = 'status-delivered'; 
                                            $status_text = 'Ստացված'; 
                                            $status_icon = '✅';
                                            break;
                                        default: 
                                            $status_class = ''; 
                                            $status_text = e($order['status']);
                                            $status_icon = '📋';
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>" style="display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                                        <?php echo $status_icon . ' ' . $status_text; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <button onclick="showOrderDetails('<?php echo $order['id']; ?>')" style="background: var(--color-sage); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> Մանրամասն
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--color-warm-gray);">
                    <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <p>Դեռևս պատվերներ չկան</p>
                    <a href="search.php" class="cta-button primary" style="display: inline-block; margin-top: 1rem;">📚 Սկսել գնումները</a>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Reviews Section -->
        <section id="reviews" class="profile-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-star"></i></div>
                <h2 class="card-title">Իմ կարծիքները</h2>
            </div>
            
            <?php if(count($user_reviews) > 0): ?>
                <?php foreach($user_reviews as $review): ?>
                <div style="border-bottom: 1px solid var(--color-light-gray); padding: 1rem 0;">
                    <div style="display: flex; gap: 1rem;">
                        <?php if(!empty($review['title_hy'])): ?>
                            <img src="<?php echo htmlspecialchars(book_cover(['id' => $review['book_id'] ?? 0, 'cover_image' => $review['cover_image'] ?? ''])); ?>" style="width: 60px; height: 80px; object-fit: cover; border-radius: 8px;">
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 0.5rem;">
                                <h4 style="color: var(--color-charcoal); margin: 0;"><?php echo htmlspecialchars($review['title_hy']); ?></h4>
                                <div>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fas fa-star" style="color: #FFD700;"></i>
                                        <?php else: ?>
                                            <i class="far fa-star" style="color: #ddd;"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if($review['title']): ?>
                                <p style="font-weight: bold; margin: 0.5rem 0 0.3rem;">"<?php echo htmlspecialchars($review['title']); ?>"</p>
                            <?php endif; ?>
                            <p style="color: var(--color-warm-gray); margin: 0;"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <small style="color: var(--color-warm-gray); display: block; margin-top: 0.5rem;">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: var(--color-warm-gray);">
                    <i class="fas fa-star" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    <p>Դեռևս կարծիքներ չկան</p>
                    <a href="search.php" class="action-button" style="display: inline-block; margin-top: 1rem;">📚 Գնահատեք գրքեր</a>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- Wishlist Section -->
        <section id="wishlist" class="profile-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-heart"></i></div>
                <h2 class="card-title">Ցանկությունների ցուցակ</h2>
            </div>
            <div id="wishlist-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            </div>
        </section>
    </main>
</div>

<!-- Modal for Order Details -->
<div id="orderModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; max-width: 500px; width: 90%; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <div style="background: var(--color-charcoal); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><i class="fas fa-shopping-bag"></i> Պատվերի մանրամասներ</h3>
            <button onclick="closeOrderModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="orderModalContent" style="padding: 1.5rem;">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<style>
.profile-hero { background: linear-gradient(135deg, var(--color-sage) 0%, #76897A 100%); padding: 2rem 1.5rem; }
.profile-hero-content { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.profile-avatar { width: 100px; height: 100px; background: var(--color-off-white); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-terracotta); }
.profile-info h1 { color: white; margin-bottom: 0.3rem; }
.profile-info p { color: var(--color-light-gray); }
.member-since { color: var(--color-light-gray); font-size: 0.9rem; }
.profile-container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; display: grid; grid-template-columns: 260px 1fr; gap: 2rem; }
@media (max-width: 768px) { .profile-container { grid-template-columns: 1fr; } }
.profile-sidebar { background: var(--color-off-white); border-radius: 12px; padding: 1rem; border: 1px solid var(--color-light-gray); height: fit-content; }
.sidebar-menu { list-style: none; }
.sidebar-link { display: block; padding: 0.8rem 1rem; text-decoration: none; color: var(--color-charcoal); border-radius: 8px; transition: all 0.3s; }
.sidebar-link:hover, .sidebar-link.active { background: rgba(193,125,100,0.1); color: var(--color-terracotta); }
.profile-card { background: var(--color-off-white); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; border: 1px solid var(--color-light-gray); }
.card-header { display: flex; align-items: center; margin-bottom: 1.5rem; }
.card-icon { width: 50px; height: 50px; background: var(--color-terracotta); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; color: white; }
.card-title { font-size: 1.5rem; color: var(--color-charcoal); }
.info-item { margin-bottom: 1rem; display: flex; align-items: center; flex-wrap: wrap; gap: 1rem; }
.info-label { width: 100px; color: var(--color-warm-gray); font-weight: 500; }
.form-input { padding: 0.6rem 1rem; border: 1px solid var(--color-light-gray); border-radius: 6px; font-family: inherit; }
.form-input:focus { outline: none; border-color: var(--color-terracotta); }
.edit-button { background: var(--color-terracotta); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 6px; cursor: pointer; margin-top: 1rem; }
.action-button { display: inline-block; padding: 0.5rem 1rem; background: var(--color-sage); color: white; border-radius: 6px; text-decoration: none; font-size: 0.9rem; }
.status-pending { background: #ffeaa7; color: #d63031; }
.status-processing { background: #74b9ff; color: #0984e3; }
.status-shipped { background: #a29bfe; color: #6c5ce7; }
.status-delivered { background: #55efc4; color: #00b894; }
.message { padding: 0.8rem; border-radius: 6px; margin-bottom: 1rem; }
.message.success { background: #d4edda; color: #155724; }
.message.error { background: #f8d7da; color: #721c24; }
table th, table td { padding: 12px 15px; text-align: left; }
</style>

<script>
// Load wishlist (պահվում է օգտատիրոջ բանալիով, տվյալները escape են արվում)
function loadWishlist() {
    let wishlist = getWishlist();
    let container = document.getElementById('wishlist-list');
    if(wishlist.length === 0) {
        container.innerHTML = '<p style="color:var(--color-warm-gray); text-align:center; padding:2rem; grid-column:1/-1;">❤️ Ցանկություններ չկան<br><a href="search.php" class="action-button" style="margin-top:1rem;">📚 Ավելացնել գրքեր</a></p>';
        return;
    }
    let html = '';
    wishlist.forEach(item => {
        html += `<div style="background:var(--color-cream); border-radius:8px; padding:1rem; text-align:center;">
                    <h4 style="margin-bottom:0.5rem;"><a href="book-details.php?id=${Number(item.id)}" style="color:inherit; text-decoration:none;">${escapeHtml(item.title)}</a></h4>
                    <p style="color:var(--color-terracotta); font-weight:bold;">${formatAmd(item.price)}</p>
                    <button onclick="removeFromWishlist(${Number(item.id)})" style="background:none; border:none; color:#e74c3c; cursor:pointer; margin-top:0.5rem;">🗑️ Հեռացնել</button>
                 </div>`;
    });
    container.innerHTML = html;
}

function removeFromWishlist(id) {
    saveWishlist(getWishlist().filter(item => item.id != id));
    loadWishlist();
}

// Show order details modal
function showOrderDetails(orderId) {
    fetch('get_order_details.php?id=' + encodeURIComponent(orderId))
        .then(response => { if (!response.ok) { throw new Error('http'); } return response.text(); })
        .then(data => {
            document.getElementById('orderModalContent').innerHTML = data;
            document.getElementById('orderModal').style.display = 'flex';
        })
        .catch(error => {
            document.getElementById('orderModalContent').innerHTML = '<p style="color:red;">Սխալ տեղի ունեցավ</p>';
            document.getElementById('orderModal').style.display = 'flex';
        });
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}

// Active link highlighting on scroll
document.addEventListener('DOMContentLoaded', () => {
    loadWishlist();
    
    let sections = document.querySelectorAll('.profile-card');
    let navLinks = document.querySelectorAll('.sidebar-link');
    
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            let sectionTop = section.offsetTop - 150;
            let sectionHeight = section.clientHeight;
            if(window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        navLinks.forEach(link => {
            link.classList.remove('active');
            if(link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>