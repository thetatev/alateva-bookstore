<?php
// checkout.php
$page_title = 'Առաքում և վճարում';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'includes/pricing.php';
require_login();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([current_user_id()]);
$user = $stmt->fetch() ?: [];

require_once 'includes/header.php';
?>

<section class="checkout-section">
    <div class="section-container">
        <h2 class="section-title">Առաքում & Վճարում</h2>
        
        <div class="checkout-steps">
            <div class="step completed"><span class="step-num">1</span><span>Զամբյուղ</span></div>
            <div class="step active"><span class="step-num">2</span><span>Առաքում</span></div>
            <div class="step"><span class="step-num">3</span><span>Վճարում</span></div>
            <div class="step"><span class="step-num">4</span><span>Հաստատում</span></div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem;">
            <div>
                <form id="checkout-form" novalidate style="background:var(--color-off-white); border:1px solid var(--color-light-gray); border-radius:12px; padding:1.8rem;">
                    <h3 style="margin-bottom:1.5rem; color:var(--color-charcoal);">Առաքման տվյալներ</h3>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:0.5rem;">Անուն <span style="color:#e74c3c;">*</span></label>
                            <input type="text" id="first_name" class="form-input" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:0.5rem;">Ազգանուն <span style="color:#e74c3c;">*</span></label>
                            <input type="text" id="last_name" class="form-input" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display:block; margin-bottom:0.5rem;">Էլ. փոստ <span style="color:#e74c3c;">*</span></label>
                        <input type="email" id="email" class="form-input" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label style="display:block; margin-bottom:0.5rem;">Հեռախոսահամար <span style="color:#e74c3c;">*</span></label>
                        <input type="tel" id="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label style="display:block; margin-bottom:0.5rem;">Հասցե <span id="address-star" style="color:#e74c3c;">*</span></label>
                        <input type="text" id="address" class="form-input" placeholder="Փողոց, տուն, բնակարան" maxlength="255">
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                        <div class="form-group">
                            <label style="display:block; margin-bottom:0.5rem;">Քաղաք</label>
                            <input type="text" id="city" class="form-input" value="Երևան">
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:0.5rem;">Հետ. ինդեքս</label>
                            <input type="text" id="postal" class="form-input">
                        </div>
                    </div>
                    
                    <hr style="margin:1.5rem 0; border-color:var(--color-light-gray);">
                    
                    <h3 style="margin-bottom:1rem;">Առաքման եղանակ</h3>
                    <div class="shipping-options">
                        <label class="shipping-option selected">
                            <input type="radio" name="shipping" value="standard" checked>
                            <span><strong>Ստանդարտ առաքում</strong><br><small>3-5 աշխատանքային օր</small></span>
                            <span class="shipping-price">1,000 դրամ</span>
                        </label>
                        <label class="shipping-option">
                            <input type="radio" name="shipping" value="express">
                            <span><strong>Էքսպրես առաքում</strong><br><small>1-2 աշխատանքային օր</small></span>
                            <span class="shipping-price">2,000 դրամ</span>
                        </label>
                        <label class="shipping-option">
                            <input type="radio" name="shipping" value="pickup">
                            <span><strong>Ինքնաուղևորում</strong><br><small>Խանութից (Երևան)</small></span>
                            <span class="shipping-price">Անվճար</span>
                        </label>
                    </div>
                    
                    <hr style="margin:1.5rem 0; border-color:var(--color-light-gray);">
                    
                    <h3 style="margin-bottom:1rem;">Վճարման եղանակ</h3>
                    <div class="payment-options">
                        <label class="payment-option selected">
                            <input type="radio" name="payment" value="cash" checked>
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <strong>Կանխիկ</strong>
                                <small>Առաքման ժամանակ</small>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment" value="card">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <strong>Բանկային քարտ</strong>
                                <small>Visa / MasterCard</small>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment" value="paypal">
                            <i class="fab fa-paypal"></i>
                            <div>
                                <strong>PayPal</strong>
                                <small>Արագ և անվտանգ</small>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Card Payment Form -->
                    <div id="card-form" style="display:none; margin-top:1.5rem; padding:1rem; background:var(--color-cream); border-radius:8px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                            <div>
                                <label style="display:block; margin-bottom:0.3rem;">Քարտի համար</label>
                                <input type="text" id="card_number" placeholder="1234 5678 9012 3456" class="form-input" autocomplete="off" inputmode="numeric">
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:0.3rem;">Վավերականության ժամկետ</label>
                                <input type="text" id="card_expiry" placeholder="MM/YY" class="form-input">
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:0.3rem;">CVC/CVV</label>
                                <input type="text" id="card_cvc" placeholder="123" class="form-input">
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:0.3rem;">Քարտատիրոջ անուն</label>
                                <input type="text" id="card_name" placeholder="ԻՆՉՊԵՍ ՔԱՐՏԻ ՎՐԱ" class="form-input">
                            </div>
                        </div>
                        <div style="margin-top:0.5rem; font-size:0.8rem; color:#7f8c8d;">
                            <i class="fas fa-lock"></i> Անվտանգ վճարում - Սա դեմո տարբերակ է, տվյալները չեն պահպանվում
                        </div>
                    </div>
                    
                    <!-- PayPal Form -->
                    <div id="paypal-form" style="display:none; margin-top:1.5rem; padding:1rem; background:var(--color-cream); border-radius:8px; text-align:center;">
                        <i class="fab fa-paypal" style="font-size:2.5rem; color:#003087;"></i>
                        <p style="margin:0.5rem 0;">Դուք կվերահղվեք PayPal-ի վճարման էջ</p>
                        <small style="color:#7f8c8d;">Սա դեմո տարբերակ է</small>
                    </div>
                    
                    <div class="form-group" style="margin-top:1.5rem;">
                        <label>Պատվերի նշումներ (ըստ ցանկության)</label>
                        <textarea id="notes" class="form-input" rows="3" placeholder="Լրացուցիչ տեղեկություններ առաքման համար..."></textarea>
                    </div>
                </form>
            </div>
            
            <div style="background:var(--color-off-white); border:1px solid var(--color-light-gray); border-radius:12px; padding:1.5rem; height:fit-content; position:sticky; top:100px;">
                <h3 style="margin-bottom:1rem;">Պատվերի ամփոփում</h3>
                <div id="order-items" style="margin-bottom:1rem; max-height:300px; overflow-y:auto;"></div>
                <hr style="margin:1rem 0; border-color:var(--color-light-gray);">
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                    <span>Ապրանքների արժեք</span>
                    <span id="checkout-subtotal">0 դրամ</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                    <span>Առաքում</span>
                    <span id="checkout-shipping">1,000 դրամ</span>
                </div>
                <div id="checkout-discount-row" style="display:none; justify-content:space-between; margin-bottom:0.5rem; color:#e74c3c;">
                    <span>Զեղչ</span>
                    <span id="checkout-discount">0 դրամ</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:1rem; padding-top:1rem; border-top:2px solid var(--color-light-gray); font-size:1.2rem; font-weight:700;">
                    <span>Ընդհանուր</span>
                    <span id="checkout-total" style="color:var(--color-terracotta);">0 դրամ</span>
                </div>
                <button type="button" id="submit-order" class="cta-button primary" style="width:100%; margin-top:1.5rem; border:none; cursor:pointer;">
                    <i class="fas fa-lock"></i> Հաստատել և վճարել
                </button>
                <a href="cart.php" style="display:block; text-align:center; margin-top:1rem; color:var(--color-warm-gray);">← Վերադառնալ զամբյուղին</a>
            </div>
        </div>
    </div>
</section>

<style>
.checkout-section { padding: 2rem 1.5rem; }
.checkout-steps { display: flex; justify-content: center; gap: 2rem; margin-bottom: 2rem; flex-wrap: wrap; }
.step { display: flex; align-items: center; gap: 0.5rem; color: var(--color-warm-gray); }
.step .step-num { width: 32px; height: 32px; background: var(--color-light-gray); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
.step.active { color: var(--color-terracotta); }
.step.active .step-num { background: var(--color-terracotta); color: white; }
.step.completed { color: var(--color-sage); }
.step.completed .step-num { background: var(--color-sage); color: white; }
.shipping-option, .payment-option {
    display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--color-light-gray); border-radius: 8px; margin-bottom: 0.8rem; cursor: pointer; transition: all 0.3s;
}
.shipping-option.selected, .payment-option.selected { border-color: var(--color-terracotta); background: rgba(193,125,100,0.05); }
.shipping-option .shipping-price { margin-left: auto; font-weight: 600; color: var(--color-terracotta); }
.payment-option i { font-size: 1.3rem; width: 35px; color: var(--color-terracotta); }
.payment-option div { flex: 1; }
.payment-option small { display: block; font-size: 0.7rem; color: var(--color-warm-gray); }
.form-group { margin-bottom: 1rem; }
.form-input { width: 100%; padding: 0.8rem; border: 1px solid var(--color-light-gray); border-radius: 6px; font-family: inherit; }
.form-input:focus { outline: none; border-color: var(--color-terracotta); }
@media (max-width: 768px) { .checkout-section .section-container > div { grid-template-columns: 1fr !important; } }
</style>

<script>
const SHIPPING_COSTS = <?php echo json_encode(SHIPPING_PRICES); ?>;
let serverData = { items: [], subtotal: 0, discount: 0 };

function selectedShipping() { return document.querySelector('input[name="shipping"]:checked').value; }
function selectedPayment() { return document.querySelector('input[name="payment"]:checked').value; }

// Գները վերցնում ենք սերվերից (localStorage-ի գները կարող են հնացած կամ փոփոխված լինել)
async function loadCheckoutCart() {
    const container = document.getElementById('order-items');
    try {
        const data = await cartRequest({ action: 'list' });
        if (!data.success) { throw new Error(data.error); }
        serverData = data;

        let html = '';
        data.items.forEach(item => {
            html += `<div style="display:flex; justify-content:space-between; margin-bottom:0.8rem; font-size:0.9rem;">
                        <span>${escapeHtml(item.title)} x${item.quantity}</span>
                        <span style="color:var(--color-terracotta);">${formatAmd(item.price * item.quantity)}</span>
                    </div>`;
        });
        container.innerHTML = html || '<p style="color:var(--color-warm-gray);">Զամբյուղը դատարկ է</p>';
    } catch (err) {
        container.innerHTML = '<p style="color:#e74c3c;">Չհաջողվեց բեռնել զամբյուղը։ Թարմացրեք էջը։</p>';
    }
    updateCheckoutTotal();
}

function updateCheckoutTotal() {
    const shipping = SHIPPING_COSTS[selectedShipping()] || 0;
    const total = Math.max(0, serverData.subtotal - serverData.discount + shipping);

    document.getElementById('checkout-subtotal').innerText = formatAmd(serverData.subtotal);
    document.getElementById('checkout-shipping').innerText = shipping === 0 ? 'Անվճար' : formatAmd(shipping);
    document.getElementById('checkout-total').innerText = formatAmd(total);

    const row = document.getElementById('checkout-discount-row');
    row.style.display = serverData.discount > 0 ? 'flex' : 'none';
    document.getElementById('checkout-discount').innerText = '-' + formatAmd(serverData.discount);

    document.getElementById('address-star').style.display = selectedShipping() === 'pickup' ? 'none' : 'inline';
}

function markSelected(selector, input) {
    document.querySelectorAll(selector).forEach(opt => opt.classList.remove('selected'));
    input.closest('label').classList.add('selected');
}

function validCardNumber(num) {
    // Luhn ալգորիթմ
    if (!/^\d{13,19}$/.test(num)) { return false; }
    let sum = 0, alt = false;
    for (let i = num.length - 1; i >= 0; i--) {
        let n = parseInt(num[i], 10);
        if (alt) { n *= 2; if (n > 9) { n -= 9; } }
        sum += n;
        alt = !alt;
    }
    return sum % 10 === 0;
}

function validExpiry(value) {
    const m = /^(0[1-9]|1[0-2])\/(\d{2})$/.exec(value);
    if (!m) { return false; }
    const now = new Date();
    const year = 2000 + parseInt(m[2], 10);
    const month = parseInt(m[1], 10);
    return year > now.getFullYear() || (year === now.getFullYear() && month >= now.getMonth() + 1);
}

async function submitOrder() {
    const v = id => document.getElementById(id).value.trim();
    const shippingMethod = selectedShipping();
    const paymentMethod = selectedPayment();

    if (!v('first_name') || !v('last_name') || !v('email') || !v('phone') || (shippingMethod !== 'pickup' && !v('address'))) {
        alert('Խնդրում եմ լրացնել բոլոր պարտադիր դաշտերը');
        return;
    }
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v('email'))) {
        alert('Էլ. հասցեն սխալ է');
        return;
    }
    if (serverData.items.length === 0) {
        alert('Ձեր զամբյուղը դատարկ է');
        return;
    }

    // Քարտի տվյալները միայն ստուգվում են բրաուզերում և սերվեր չեն ուղարկվում (դեմո տարբերակ)
    if (paymentMethod === 'card') {
        const number = v('card_number').replace(/\s/g, '');
        if (!v('card_name') || !v('card_expiry') || !v('card_cvc') || !number) {
            alert('Խնդրում եմ լրացնել քարտի բոլոր տվյալները');
            return;
        }
        if (!validCardNumber(number)) { alert('Քարտի համարը սխալ է'); return; }
        if (!validExpiry(v('card_expiry'))) { alert('Քարտի վավերականության ժամկետը սխալ է կամ անցել է (MM/YY)'); return; }
        if (!/^\d{3,4}$/.test(v('card_cvc'))) { alert('CVC կոդը սխալ է'); return; }
    }

    const orderData = {
        first_name: v('first_name'),
        last_name: v('last_name'),
        email: v('email'),
        phone: v('phone'),
        address: v('address'),
        city: v('city'),
        postal_code: v('postal'),
        notes: v('notes'),
        shipping_method: shippingMethod,
        payment_method: paymentMethod,
        // Զամբյուղը, գինը, զեղչը և գումարը սերվերը վերցնում է բազայից
    };

    const btn = document.getElementById('submit-order');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Մշակվում է...';
    btn.disabled = true;

    try {
        const res = await fetch('save_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.ALATEVA.csrf },
            body: JSON.stringify(orderData)
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = data.redirect || 'profile.php#orders';
            return;
        }
        alert('❌ ' + (data.error || 'Սխալ պատվերի պահպանման ժամանակ։ Փորձեք կրկին'));
    } catch (err) {
        alert('❌ Կապի սխալ։ Ստուգեք ինտերնետը և փորձեք կրկին');
    }
    btn.innerHTML = originalText;
    btn.disabled = false;
}

document.addEventListener('DOMContentLoaded', function () {
    loadCheckoutCart();

    document.querySelectorAll('input[name="shipping"]').forEach(r => r.addEventListener('change', function () {
        markSelected('.shipping-option', this);
        updateCheckoutTotal();
    }));

    document.querySelectorAll('input[name="payment"]').forEach(r => r.addEventListener('change', function () {
        markSelected('.payment-option', this);
        const method = selectedPayment();
        document.getElementById('card-form').style.display = method === 'card' ? 'block' : 'none';
        document.getElementById('paypal-form').style.display = method === 'paypal' ? 'block' : 'none';
    }));

    document.getElementById('submit-order').addEventListener('click', submitOrder);
    document.getElementById('checkout-form').addEventListener('submit', function (e) { e.preventDefault(); });

    document.getElementById('card_number').addEventListener('input', function (e) {
        const value = e.target.value.replace(/\D/g, '').substring(0, 19);
        e.target.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    document.getElementById('card_expiry').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 3) { value = value.substring(0, 2) + '/' + value.substring(2, 4); }
        e.target.value = value.substring(0, 5);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
