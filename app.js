// app.js - ընդհանուր JS՝ զամբյուղ, ցանկություններ, dropdown, toast
(function () {
    'use strict';

    var A = window.ALATEVA || { userId: 0, csrf: '' };
    var MAX_QTY = 10;

    // ---------- Օգնականներ ----------
    window.escapeHtml = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    window.formatAmd = function (n) {
        return Number(n || 0).toLocaleString('en-US') + ' դրամ';
    };

    // Յուրաքանչյուր օգտատիրոջ զամբյուղը/ցանկը պահվում է առանձին բանալիով
    function storageKey(name) { return name + '_' + (A.userId || 'guest'); }

    function readList(name) {
        try {
            var v = JSON.parse(localStorage.getItem(storageKey(name)) || '[]');
            return Array.isArray(v) ? v : [];
        } catch (e) { return []; }
    }

    function writeList(name, list) {
        try { localStorage.setItem(storageKey(name), JSON.stringify(list)); } catch (e) { /* ignore */ }
    }

    window.getWishlist = function () { return readList('wishlist'); };
    window.saveWishlist = function (list) { writeList('wishlist', list); };
    // Հին (ընդհանուր) բանալիները, որոնք խառնում էին տարբեր օգտատերերի զամբյուղները
    // Զամբյուղն այժմ բազայում է. հին localStorage-ի տվյալները մաքրվում են
    try {
        localStorage.removeItem('cart'); localStorage.removeItem('wishlist');
        localStorage.removeItem('cart_' + (A.userId || 'guest')); localStorage.removeItem('promo_' + (A.userId || 'guest'));
    } catch (e) { /* ignore */ }

    // ---------- Ծանուցում ----------
    window.showToast = function (message, type) {
        var box = document.createElement('div');
        box.textContent = message;
        box.setAttribute('role', 'status');
        box.style.cssText = 'position:fixed;top:20px;right:20px;color:#fff;padding:10px 20px;border-radius:6px;z-index:9999;' +
            'box-shadow:0 4px 12px rgba(0,0,0,.2);background:' + (type === 'error' ? '#e74c3c' : (type === 'warn' ? '#f39c12' : '#2ecc71'));
        document.body.appendChild(box);
        setTimeout(function () { box.remove(); }, 2500);
    };

    function askLogin(text) {
        if (confirm(text + '\nՑանկանու՞մ եք անցնել մուտքի էջ:')) {
            var page = location.pathname.split('/').pop() || 'index.php';
            window.location.href = 'login.php?next=' + encodeURIComponent(page + location.search);
        }
    }

    // ---------- Զամբյուղ (պահվում է սերվերում) ----------
    window.cartRequest = async function (payload) {
        const res = await fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': A.csrf },
            body: JSON.stringify(payload)
        });
        if (res.status === 401) {
            askLogin('⚠️ Զամբյուղից օգտվելու համար անհրաժեշտ է մուտք գործել:');
            return { success: false, error: 'login' };
        }
        try { return await res.json(); } catch (e) { return { success: false, error: 'Սերվերի սխալ' }; }
    };

    window.updateCartBadge = function (count) {
        document.querySelectorAll('.cart-badge').forEach(function (el) {
            el.textContent = count;
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });
    };

    window.addToCart = async function (bookId, title, price, qty) {
        if (!A.userId) { askLogin('⚠️ Զամբյուղից օգտվելու համար անհրաժեշտ է մուտք գործել:'); return; }
        qty = Math.max(1, parseInt(qty, 10) || 1);
        try {
            const data = await window.cartRequest({ action: 'add', book_id: Number(bookId), quantity: qty });
            if (data.success) {
                window.updateCartBadge(data.count);
                window.showToast('✅ ' + title + ' ավելացվել է զամբյուղում');
            } else if (data.error !== 'login') {
                window.showToast('⚠️ ' + (data.error || 'Չհաջողվեց ավելացնել'), 'warn');
            }
        } catch (e) {
            window.showToast('❌ Կապի սխալ', 'error');
        }
    };

    // ---------- Ցանկություններ ----------
    window.addToWishlist = function (bookId, title, price) {
        if (!A.userId) { askLogin('⚠️ Ցանկից օգտվելու համար անհրաժեշտ է մուտք գործել:'); return; }
        var list = window.getWishlist();
        if (list.find(function (i) { return i.id == bookId; })) {
            window.showToast('⚠️ Այս գիրքն արդեն ձեր ցանկում է', 'warn');
            return;
        }
        list.push({ id: Number(bookId), title: title, price: Number(price) });
        window.saveWishlist(list);
        window.showToast('✅ ' + title + ' ավելացվել է ցանկում');
    };

    // ---------- Պրոֆիլի dropdown ----------
    function initializePage() {
        var btn = document.querySelector('.profile-dropdown-btn');
        var menu = document.querySelector('.dropdown-menu');
        if (btn && menu) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
            document.addEventListener('click', function () { menu.style.display = 'none'; });
        }

        // ---------- Գրքերի օգնական ----------
        var launcher = document.getElementById('assistant-launcher');
        var panel = document.getElementById('assistant-panel');
        var close = document.getElementById('assistant-close');
        var resetBtn = document.getElementById('assistant-reset');
        var form = document.getElementById('assistant-form');
        var input = document.getElementById('assistant-input');
        var messages = document.getElementById('assistant-messages');
        var chipsBox = document.getElementById('assistant-chips');
        if (launcher && panel && form && input && messages && chipsBox) {
            var STORE = 'alateva_assistant_v2_' + (A.userId || 'guest');
            var log = [];      // [{k: 'bot'|'user', t, books, links, orders}]
            var chips = [];
            var busy = false;
            var typingEl = null;

            function el(tag, cls, text) {
                var n = document.createElement(tag);
                if (cls) n.className = cls;
                if (text != null) n.textContent = text;
                return n;
            }
            function scrollDown() { messages.scrollTop = messages.scrollHeight; }
            function persist() {
                try { sessionStorage.setItem(STORE, JSON.stringify({ log: log.slice(-30), chips: chips, open: !panel.hidden })); } catch (e) { /* ignore */ }
            }
            function readSaved() {
                try {
                    var v = JSON.parse(sessionStorage.getItem(STORE) || 'null');
                    return v && Array.isArray(v.log) ? v : null;
                } catch (e) { return null; }
            }
            function setOpen(open, focus) {
                panel.hidden = !open;
                launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    scrollDown();
                    if (focus !== false) input.focus();
                    if (!log.length && !busy) loadGreeting('init');
                }
                persist();
            }

            // ----- Արտապատկերում -----
            function bookCard(book) {
                var card = el('div', 'assistant-book');
                var main = el('a', 'assistant-book-main');
                main.href = 'book-details.php?id=' + encodeURIComponent(book.id);
                if (book.cover_image) {
                    var img = el('img');
                    img.src = book.cover_image; img.alt = ''; img.loading = 'lazy';
                    main.appendChild(img);
                }
                var info = el('span', 'assistant-book-info');
                info.appendChild(el('strong', '', book.title_hy));
                info.appendChild(el('small', '', book.author + ' · ' + window.formatAmd(book.price)));
                if (book.stock < 1) info.appendChild(el('small', 'assistant-stock out', 'Չկա պահեստում'));
                else if (book.stock <= 5) info.appendChild(el('small', 'assistant-stock low', 'Մնացել է ' + book.stock + ' հատ'));
                main.appendChild(info);
                card.appendChild(main);

                var btn = el('button', 'assistant-book-btn');
                btn.type = 'button';
                if (book.stock > 0) {
                    btn.innerHTML = '<i class="fas fa-cart-plus" aria-hidden="true"></i><span>Զամբյուղ</span>';
                    btn.addEventListener('click', function () { window.addToCart(book.id, book.title_hy, book.price); });
                } else {
                    btn.innerHTML = '<i class="fas fa-bell" aria-hidden="true"></i><span>Ծանուցել</span>';
                    btn.addEventListener('click', function () { notifyMe(book, btn); });
                }
                card.appendChild(btn);
                return card;
            }
            function renderEntry(entry) {
                var item = el('div', 'assistant-message assistant-message-' + (entry.k === 'user' ? 'user' : 'bot') + (entry.err ? ' assistant-message-error' : ''));
                item.appendChild(document.createTextNode(entry.t || ''));
                if (entry.books && entry.books.length) {
                    var list = el('div', 'assistant-book-list');
                    entry.books.forEach(function (b) { list.appendChild(bookCard(b)); });
                    item.appendChild(list);
                }
                if (entry.orders && entry.orders.length) {
                    var ol = el('div', 'assistant-order-list');
                    entry.orders.forEach(function (o) {
                        var row = el('div', 'assistant-order');
                        row.appendChild(el('strong', '', o.number));
                        row.appendChild(el('span', 'assistant-order-status', o.status));
                        row.appendChild(el('small', '', o.total + ' · ' + o.date));
                        ol.appendChild(row);
                    });
                    item.appendChild(ol);
                }
                if (entry.links && entry.links.length) {
                    var lb = el('div', 'assistant-links');
                    entry.links.forEach(function (l) {
                        if (!/^[A-Za-z0-9_\-]+\.php([?#][A-Za-z0-9_=&%#\-]*)?$/.test(l.url)) return;
                        var a = el('a', 'assistant-link', l.label);
                        a.href = l.url;
                        lb.appendChild(a);
                    });
                    if (lb.childNodes.length) item.appendChild(lb);
                }
                messages.appendChild(item);
                scrollDown();
            }
            function renderChips() {
                chipsBox.textContent = '';
                chips.forEach(function (text) {
                    var b = el('button', 'assistant-chip', text);
                    b.type = 'button';
                    b.addEventListener('click', function () { ask(text); });
                    chipsBox.appendChild(b);
                });
                chipsBox.hidden = chips.length === 0;
                scrollDown();
            }
            function showTyping() {
                hideTyping();
                typingEl = el('div', 'assistant-message assistant-message-bot assistant-typing');
                typingEl.setAttribute('aria-label', 'Օգնականը գրում է');
                typingEl.innerHTML = '<span></span><span></span><span></span>';
                messages.appendChild(typingEl);
                scrollDown();
            }
            function hideTyping() { if (typingEl) { typingEl.remove(); typingEl = null; } }
            function setBusy(state) {
                busy = state;
                form.classList.toggle('is-busy', state);
                var submit = form.querySelector('button[type="submit"]');
                if (submit) submit.disabled = state;
            }

            function push(entry) { log.push(entry); renderEntry(entry); }
            function applyReply(data) {
                push({ k: 'bot', t: data.reply || '', books: data.books || [], links: data.links || [], orders: data.orders || [] });
                if (typeof data.cart_count === 'number') window.updateCartBadge(data.cart_count);
                chips = data.chips || [];
                renderChips();
                persist();
            }

            // ----- Հարցումներ -----
            async function request(payload) {
                payload.csrf = A.csrf;
                var ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
                var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, 15000) : null;
                try {
                    var res = await fetch('assistant_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        signal: ctrl ? ctrl.signal : undefined
                    });
                    var data = {};
                    try { data = await res.json(); } catch (e) { data = {}; }
                    return { ok: res.ok, status: res.status, data: data };
                } finally {
                    if (timer) clearTimeout(timer);
                }
            }
            function fail(data) {
                push({ k: 'bot', err: true, t: (data && (data.error || data.reply)) || 'Չհաջողվեց պատասխանել։ Փորձեք կրկին։' });
                chips = [];
                renderChips();
                persist();
            }
            async function loadGreeting(action) {
                setBusy(true);
                showTyping();
                try {
                    var r = await request({ action: action });
                    hideTyping();
                    if (r.ok) applyReply(r.data); else fail(r.data);
                } catch (e) {
                    hideTyping();
                    fail({ error: 'Կապը չհաջողվեց։ Խնդրում ենք փորձել կրկին։' });
                } finally {
                    setBusy(false);
                }
            }
            async function ask(text) {
                text = (text || '').trim();
                if (!text || busy) return;
                push({ k: 'user', t: text });
                chips = [];
                renderChips();
                input.value = '';
                setBusy(true);
                showTyping();
                try {
                    var r = await request({ action: 'chat', message: text });
                    hideTyping();
                    if (r.ok) applyReply(r.data); else fail(r.data);
                } catch (e) {
                    hideTyping();
                    fail({ error: e && e.name === 'AbortError' ? 'Պատասխանը ուշանում է։ Փորձեք կրկին։' : 'Կապը չհաջողվեց։ Խնդրում ենք փորձել կրկին։' });
                } finally {
                    setBusy(false);
                    persist();
                    if (!panel.hidden) input.focus();
                }
            }
            async function notifyMe(book, btn) {
                btn.disabled = true;
                try {
                    var r = await request({ action: 'notify', book_id: book.id });
                    if (r.ok || r.status === 401) {
                        push({ k: 'bot', t: r.data.reply || 'Պատրաստ է։', links: r.data.links || [] });
                        persist();
                    } else {
                        fail(r.data);
                    }
                } catch (e) {
                    fail({ error: 'Կապը չհաջողվեց։ Փորձեք կրկին։' });
                } finally {
                    btn.disabled = false;
                }
            }

            // ----- Իրադարձություններ -----
            launcher.addEventListener('click', function () { setOpen(panel.hidden); });
            if (close) close.addEventListener('click', function () { setOpen(false); launcher.focus(); });
            panel.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { setOpen(false); launcher.focus(); }
            });
            if (resetBtn) resetBtn.addEventListener('click', async function () {
                if (busy) return;
                log = []; chips = [];
                messages.textContent = '';
                renderChips();
                try { sessionStorage.removeItem(STORE); } catch (e) { /* ignore */ }
                await loadGreeting('reset');
            });
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                ask(input.value);
            });

            // ----- Վերականգնում էջերի միջև -----
            var saved = readSaved();
            if (saved && saved.log.length) {
                log = saved.log;
                chips = Array.isArray(saved.chips) ? saved.chips : [];
                log.forEach(renderEntry);
                renderChips();
                if (saved.open) setOpen(true, false);
            }
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePage, { once: true });
    } else {
        initializePage();
    }
})();
