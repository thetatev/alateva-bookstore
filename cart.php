<?php
// cart.php
$page_title = 'Իմ զամբյուղը';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_login();
require_once 'includes/header.php';
?>

<section class="cart-section">
    <div class="section-container">
        <h2 class="section-title">Իմ զամբյուղը</h2>
        
        <div id="cart-container">
            <div class="cart-loading">Բեռնում...</div>
        </div>
        
        <div id="empty-cart" style="display:none; text-align:center; padding:3rem;">
            <i class="fas fa-shopping-cart" style="font-size:4rem; color:var(--color-warm-gray);"></i>
            <h3 style="margin-top:1rem;">Ձեր զամբյուղը դատարկ է</h3>
            <p style="color:var(--color-warm-gray);">Ավելացրեք գրքեր զամբյուղում՝ գնումները շարունակելու համար</p>
            <a href="search.php" class="cta-button primary" style="margin-top:1rem; display:inline-block;">Շարունակել գնումները</a>
        </div>
        
        <div id="cart-content" style="display:none;">
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
                <div class="cart-items">
                    <div id="cart-items-list"></div>
                </div>
                <div class="cart-summary">
                    <h3 style="font-size:1.3rem; margin-bottom:1.5rem; padding-bottom:0.5rem; border-bottom:1px solid var(--color-light-gray);">Պատվերի ամփոփում</h3>
                    <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                        <span style="color:var(--color-warm-gray);">Ապրանքների արժեք</span>
                        <span id="subtotal" style="font-weight:500;">0 դրամ</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                        <span style="color:var(--color-warm-gray);">Առաքում</span>
                        <span id="shipping-cost" style="font-weight:500;">Հաշվարկվում է հաջորդ քայլում</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                        <span style="color:var(--color-warm-gray);">Զեղչ</span>
                        <span id="discount-amount" style="font-weight:500; color:#e74c3c;">0 դրամ</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top:1rem; padding-top:1rem; border-top:2px solid var(--color-light-gray); font-size:1.2rem; font-weight:700;">
                        <span>Ընդհանուր (առանց առաքման)</span>
                        <span id="total-price" style="color:var(--color-terracotta);">0 դրամ</span>
                    </div>
                    
                    <div style="display:flex; gap:0.5rem; margin:1.5rem 0;">
                        <input type="text" id="promo-code" placeholder="Պրոմո կոդ" maxlength="30" style="flex:1; padding:0.8rem; border:1px solid var(--color-light-gray); border-radius:6px; font-family:inherit;">
                        <button onclick="applyPromo()" style="background:var(--color-sage); color:white; border:none; padding:0.8rem 1.2rem; border-radius:6px; cursor:pointer;">Կիրառել</button>
                    </div>
                    
                    <a href="checkout.php" class="cta-button primary" style="display:block; text-align:center; width:100%;">Անցնել առաքման</a>
                    <a href="search.php" style="display:block; text-align:center; margin-top:1rem; color:var(--color-warm-gray);">Շարունակել գնումները</a>
                    
                    <div style="display:flex; justify-content:center; gap:1rem; margin-top:1.5rem; padding-top:1rem; border-top:1px solid var(--color-light-gray);">
                        <i class="fab fa-cc-visa" style="font-size:1.5rem; color:var(--color-warm-gray);"></i>
                        <i class="fab fa-cc-mastercard" style="font-size:1.5rem; color:var(--color-warm-gray);"></i>
                        <i class="fab fa-cc-paypal" style="font-size:1.5rem; color:var(--color-warm-gray);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.cart-section { padding: 2rem 1.5rem; }
.cart-items { background: var(--color-off-white); border-radius: 12px; padding: 1rem; border: 1px solid var(--color-light-gray); }
.cart-item { display: flex; gap: 1.5rem; padding: 1.2rem 0; border-bottom: 1px solid var(--color-light-gray); }
.cart-item:last-child { border-bottom: none; }
.cart-item-details { flex: 1; }
.cart-item-title { font-size: 1.1rem; font-weight: 600; color: var(--color-charcoal); margin-bottom: 0.3rem; }
.cart-item-price { color: var(--color-terracotta); font-weight: 600; margin: 0.3rem 0; }
.cart-item-quantity { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; }
.cart-item-quantity button { width: 28px; height: 28px; border: 1px solid var(--color-light-gray); background: white; cursor: pointer; border-radius: 4px; transition: all 0.3s; }
.cart-item-quantity button:hover { background: var(--color-terracotta); color: white; border-color: var(--color-terracotta); }
.cart-item-quantity input { width: 50px; text-align: center; border: 1px solid var(--color-light-gray); border-radius: 4px; padding: 0.3rem; }
.cart-item-remove { background: none; border: none; color: var(--color-warm-gray); cursor: pointer; font-size: 1.1rem; transition: color 0.3s; padding: 0 0.5rem; }
.cart-item-remove:hover { color: #e74c3c; }
.cart-summary { background: var(--color-off-white); border-radius: 12px; padding: 1.5rem; border: 1px solid var(--color-light-gray); height: fit-content; position: sticky; top: 100px; }
@media (max-width: 768px) { .cart-section .section-container > div { grid-template-columns: 1fr !important; } }
</style>

<script>
async function loadCart(payload) {
    const loading = document.getElementById('cart-container');
    const data = await cartRequest(payload || { action: 'list' });
    if (!data.success) {
        if (data.error !== 'login') {
            loading.innerHTML = '<p style="text-align:center;color:#e74c3c;">' + escapeHtml(data.error || 'Չհաջողվեց բեռնել զամբյուղը') + '</p>';
        }
        return null;
    }
    loading.style.display = 'none';
    render(data);
    return data;
}

function render(data) {
    const emptyDiv = document.getElementById('empty-cart');
    const cartContent = document.getElementById('cart-content');
    updateCartBadge(data.count);

    if (data.items.length === 0) {
        emptyDiv.style.display = 'block';
        cartContent.style.display = 'none';
        return;
    }
    emptyDiv.style.display = 'none';
    cartContent.style.display = 'block';

    let html = '';
    data.items.forEach(item => {
        html += `
            <div class="cart-item" data-id="${item.id}">
                <div class="cart-item-details">
                    <div class="cart-item-title">${escapeHtml(item.title)}</div>
                    <div class="cart-item-price">${formatAmd(item.price)}</div>
                    <div class="cart-item-quantity">
                        <button type="button" data-action="dec" aria-label="Պակասեցնել">-</button>
                        <input type="number" value="${item.quantity}" min="1" max="${item.max}" data-action="set" aria-label="Քանակ">
                        <button type="button" data-action="inc" aria-label="Ավելացնել">+</button>
                    </div>
                </div>
                <button type="button" class="cart-item-remove" data-action="remove" aria-label="Հեռացնել"><i class="fas fa-trash-alt"></i></button>
            </div>`;
    });
    document.getElementById('cart-items-list').innerHTML = html;

    const total = Math.max(0, data.subtotal - data.discount);
    document.getElementById('subtotal').innerText = formatAmd(data.subtotal);
    document.getElementById('discount-amount').innerText = data.discount > 0 ? '-' + formatAmd(data.discount) : '0 դրամ';
    document.getElementById('total-price').innerText = formatAmd(total);
    if (data.promo_code) { document.getElementById('promo-code').value = data.promo_code; }
}

async function sendChange(payload) {
    const data = await cartRequest(payload);
    if (data.success) {
        render(data);
    } else if (data.error !== 'login') {
        showToast('⚠️ ' + (data.error || 'Սխալ'), 'warn');
        loadCart();
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action]');
    if (!btn || btn.tagName === 'INPUT') { return; }
    const row = btn.closest('.cart-item');
    if (!row) { return; }
    const id = Number(row.dataset.id);
    const current = parseInt(row.querySelector('input[data-action="set"]').value, 10) || 1;
    const action = btn.dataset.action;

    if (action === 'inc') {
        sendChange({ action: 'set', book_id: id, quantity: current + 1 });
    } else if (action === 'dec') {
        sendChange({ action: 'set', book_id: id, quantity: Math.max(1, current - 1) });
    } else if (action === 'remove') {
        sendChange({ action: 'remove', book_id: id });
    }
});

document.addEventListener('change', function (e) {
    if (e.target.matches('input[data-action="set"]')) {
        const id = Number(e.target.closest('.cart-item').dataset.id);
        const qty = parseInt(e.target.value, 10);
        if (qty >= 1) {
            sendChange({ action: 'set', book_id: id, quantity: qty });
        } else {
            loadCart();
        }
    }
});

async function applyPromo() {
    const code = document.getElementById('promo-code').value.trim();
    if (!code) { return; }
    const data = await cartRequest({ action: 'promo', code: code });
    if (data.success && data.promo_ok) {
        showToast('✅ ' + data.promo_message);
    } else if (data.error !== 'login') {
        showToast('❌ ' + (data.promo_message || data.error || 'Սխալ պրոմո կոդ'), 'error');
    }
    if (data.success) { render(data); }
}

document.addEventListener('DOMContentLoaded', function () { loadCart(); });
</script>

<?php require_once 'includes/footer.php'; ?>
