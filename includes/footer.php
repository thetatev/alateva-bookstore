    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h4 class="footer-title">alateva</h4>
                <p class="footer-text">Քո վստահելի առցանց գրախանութը՝ որակյալ գրականության համար</p>
            </div>
            <div class="footer-section">
                <h4 class="footer-title">Բաժիններ</h4>
                <a href="index.php" class="footer-link">Գլխավոր</a>
                <a href="search.php" class="footer-link">Որոնում</a>
                <a href="about.php" class="footer-link">Մեր մասին</a>
            </div>
            <div class="footer-section">
                <h4 class="footer-title">Կապ մեզ հետ</h4>
                <p class="footer-text">Email: info@alateva.com</p>
                <p class="footer-text">Հեռախոսահամար: +37477000000</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> alateva. Բոլոր իրավունքները պաշտպանված են։</p>
        </div>
    </footer>
</div>

<section class="book-assistant" id="book-assistant" aria-label="Գրքերի օգնական">
    <button class="assistant-launcher" id="assistant-launcher" type="button" aria-expanded="false" aria-controls="assistant-panel">
        <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i><span>Գրքերի օգնական</span>
    </button>
    <div class="assistant-panel" id="assistant-panel" hidden>
        <div class="assistant-heading">
            <div><strong>Գտնենք քո հաջորդ գիրքը</strong><small>Գրիր ժանրը, հեղինակը կամ բյուջեն. հարցրու պաշարի, պատվերի, առաքման մասին։</small></div>
            <div class="assistant-heading-actions">
                <button type="button" class="assistant-icon-btn" id="assistant-reset" aria-label="Սկսել զրույցը նորից" title="Սկսել նորից"><i class="fas fa-rotate-right" aria-hidden="true"></i></button>
                <button type="button" class="assistant-close" id="assistant-close" aria-label="Փակել">×</button>
            </div>
        </div>
        <div class="assistant-messages" id="assistant-messages" aria-live="polite"></div>
        <div class="assistant-chips" id="assistant-chips" hidden></div>
        <form class="assistant-form" id="assistant-form"><label class="sr-only" for="assistant-input">Գրիր հարցդ</label>
            <input id="assistant-input" name="message" maxlength="300" placeholder="Օրինակ՝ դետեկտիվ մինչև 5000 դրամ" autocomplete="off" required>
            <button type="submit" aria-label="Ուղարկել"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
        </form>
    </div>
</section>

<script src="app.js?v=20261021-1"></script>
</body>
</html>
