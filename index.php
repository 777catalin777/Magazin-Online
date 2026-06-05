<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/language_switcher.php';
require_once __DIR__ . '/app/includes/catalog.php';

$productNames = [];
foreach ($products as $product) {
    $productNames[$product['key']] = lang($product['key']);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="author" content="Tintiuc Catalin">
    <meta name="description" content="Maison Lure - moda atent selectata pentru fiecare zi">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(lang('site_title')) ?></title>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/images/favicon/site.webmanifest">
</head>

<body>
    <header class="site-header">
        <a href="index.php" class="brand" aria-label="Maison Lure">
            <span class="brand-name">Maison Lure</span>
        </a>

        <form class="search-form" role="search" onsubmit="return false;">
            <i class="bx bx-search"></i>
            <input type="search" name="search" aria-label="<?= htmlspecialchars(lang('search_placeholder')) ?>"
                placeholder="<?= htmlspecialchars(lang('search_placeholder')) ?>">
        </form>

        <div class="header-actions">
            <div class="language-switcher" aria-label="<?= htmlspecialchars(lang('language')) ?>">
                <a href="?lang=ro" class="<?= $lang === 'ro' ? 'active' : '' ?>">RO</a>
                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
                <a href="?lang=ru" class="<?= $lang === 'ru' ? 'active' : '' ?>">RU</a>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= ($_SESSION['role'] ?? 'user') === 'admin' ? 'admin_page.php' : 'user_page.php' ?>"
                    class="icon-link">
                    <i class="bx bx-user"></i><span><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="icon-link">
                    <i class="bx bx-user"></i><span><?= htmlspecialchars(lang('login')) ?></span>
                </a>
            <?php endif; ?>

            <div class="cart-container">
                <button class="cart-button" type="button" aria-label="<?= htmlspecialchars(lang('cart')) ?>">
                    <i class="bx bx-shopping-bag"></i>
                    <span id="cart-count" class="hidden">0</span>
                </button>
                <aside class="cart-content" aria-label="<?= htmlspecialchars(lang('cart')) ?>">
                    <div class="cart-heading">
                        <div>
                            <span class="eyebrow"><?= htmlspecialchars(lang('products')) ?></span>
                            <h3><?= htmlspecialchars(lang('cart')) ?></h3>
                        </div>
                        <i class="bx bx-shopping-bag"></i>
                    </div>
                    <div id="cart-empty" class="cart-empty">
                        <i class="bx bx-shopping-bag empty-cart-icon"></i>
                        <p><?= htmlspecialchars(lang('empty_cart')) ?></p>
                    </div>
                    <ul id="cart-items"></ul>
                    <div class="cart-summary">
                        <p><?= htmlspecialchars(lang('products')) ?> <span id="item-count">0</span></p>
                        <p><?= htmlspecialchars(lang('total')) ?> <strong><span id="cart-total">0</span> MDL</strong>
                        </p>
                    </div>
                    <button class="clear-cart" type="button"><?= htmlspecialchars(lang('clear_cart')) ?></button>
                </aside>
            </div>
        </div>
    </header>

    <nav class="category-bar" aria-label="Categorii principale">
        <div class="category-bar-inner">
            <a href="#">Nou în</a>
            <a href="#">Îmbrăcăminte</a>
            <a href="#">Pantofi</a>
            <a href="#">Accessorii</a>
            <a href="#">Mărci</a>
            <a href="#">Îmbrăcăminte sport</a>
            <a href="#">Îngrijire</a>
            <a href="#">Topman</a>
            <a href="#">Vânzare</a>
        </div>
        <div class="mega-menu" aria-label="New in">
            <div class="mega-column mega-links">
                <h2>PRODUSE NOI</h2>
                <a href="#" class="strong-link">Vezi toate</a>
                <a href="#" class="strong-link">Noutăți: Astăzi</a>
                <a href="#" class="strong-link">Noua marcă: Desigual</a>
                <a href="#">Îmbrăcăminte</a>
                <a href="#">Pantofi</a>
                <a href="#">Tricouri și veste</a>
                <a href="#">Blugi</a>
                <a href="#">Accessorii</a>
                <a href="#">Pulovere și cardigane</a>
            </div>

            <div class="mega-column">
                <h2>VARĂ</h2>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Tricou Polo.avif" alt="" loading="lazy" decoding="async">
                    <span>Esențiale de vară</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Tricou Nike Premium.avif" alt="" loading="lazy" decoding="async">
                    <span>Lenjerie</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Tricou Negru.avif" alt="" loading="lazy" decoding="async">
                    <span>Magazin de sărbători</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Pantaloni Jack & Jones.avif" alt="" loading="lazy" decoding="async">
                    <span>Costume de baie</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Adidas Samba.avif" alt="" loading="lazy" decoding="async">
                    <span>Sandale</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Blugi Collusion.avif" alt="" loading="lazy" decoding="async">
                    <span>Pantaloni scurți</span>
                </a>
            </div>

            <div class="mega-column">
                <h2>CELE MAI CĂUTATE FAVORITE</h2>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Hanorac Dior.avif" alt="" loading="lazy" decoding="async">
                    <span>Cel mai mediatizat al tău</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Hanorac Weekday.avif" alt="" loading="lazy" decoding="async">
                    <span>Costume de in</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/Hanorac Adidas.avif" alt="" loading="lazy" decoding="async">
                    <span>Festival</span>
                </a>
                <a href="#" class="mega-media-link">
                    <img src="assets/images/produse/NewBalance 9060.avif" alt="" loading="lazy" decoding="async">
                    <span>Jorts</span>
                </a>
            </div>

            <div class="mega-column mega-edit">
                <h2>NOI EDITĂRI</h2>
                <a href="#" class="mega-edit-card">
                    <img src="assets/images/produse/Hanorac Diorr.png" alt="Match-day ready" loading="lazy"
                        decoding="async">
                    <strong>PREGĂTIT PENTRU ZIUA MECIULUI</strong>
                </a>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">Colecția 2026</span>
                <h1>Definește-ți stilul.<br><em>Poartă-l cu încredere.</em></h1>
                <p>Descoperă îmbrăcăminte modernă, atent selectată pentru confortul și personalitatea ta.</p>
                <a href="#collection" class="hero-cta">Descoperă colecția <i class="bx bx-right-arrow-alt"></i></a>
            </div>
            <div class="hero-showcase">
                <img src="assets/images/produse/Hanorac Diorr.png" alt="Hanorac Dior">
                <div class="floating-note note-one">
                    <strong>-20%</strong><br>
                    Oferta săptămânii
                </div>
                <div class="floating-note note-two">
                    <strong>100+</strong><br>
                    Clienți mulțumiți
                </div>
                <div class="hero-orbit"></div>
            </div>
        </section>

        <section class="benefits" aria-label="Beneficii">
            <div><i class="bx bx-package"></i><span><strong>Livrare rapidă</strong> în toată Moldova</span></div>
            <div><i class="bx bx-check-shield"></i><span><strong>Plăți sigure</strong> și date protejate</span></div>
            <div><i class="bx bx-refresh"></i><span><strong>Retur ușor</strong> în termen de 14 zile</span></div>
        </section>

        <section class="collection" id="collection">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Ediția curentă</span>
                    <h2><?= htmlspecialchars(lang('products')) ?></h2>
                </div>
                <p>O garderobă modernă începe cu îmbrăcăminte pe care vrei să o porți din nou și din nou.</p>
            </div>

            <div class="container">
                <?php foreach ($products as $index => $product): ?>
                    <article class="product" style="--order: <?= $index ?>;">
                        <div class="product-media">
                            <span class="product-tag"><?= htmlspecialchars($product['tag']) ?></span>
                            <img src="assets/images/produse/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars(lang($product['key'])) ?>"
                                loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" <?= $index === 0 ? 'fetchpriority="high"' : '' ?> decoding="async">
                        </div>
                        <div class="product-info">
                            <div>
                                <span class="product-category">Maison selection</span>
                                <h3><?= htmlspecialchars(lang($product['key'])) ?></h3>
                            </div>
                            <p><?= number_format($product['price'], 0, '.', ' ') ?> <span>MDL</span></p>
                        </div>
                        <button class="buy-button" data-key="<?= htmlspecialchars($product['key']) ?>"
                            data-price="<?= $product['price'] ?>">
                            <span><?= htmlspecialchars(lang('add_to_cart')) ?></span>
                            <i class="bx bx-plus"></i>
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="no-results">Nu s-au găsit produse.</div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-main">
            <div class="footer-brand">
                <a href="index.php" class="brand"><span class="brand-name">Maison Lure</span></a>
                <p>Moda contemporană, aleasă cu grijă pentru ritmul tău.</p>
            </div>
            <div>
                <h3>Magazin</h3>
                <a href="#collection">Colecție</a>
                <a href="login.php">Contul meu</a>
                <a href="#collection">Produse noi</a>
            </div>
            <div>
                <h3>Ajutor</h3>
                <a href="#">Livrare și retur</a>
                <a href="#">Întrebări frecvente</a>
                <a href="#">Contacte</a>
            </div>
            <div class="footer-newsletter">
                <h3>Rămâi aproape</h3>
                <p>Află primul despre colecții și oferte noi.</p>
                <a href="login.php">Creează un cont <i class="bx bx-right-arrow-alt"></i></a>
            </div>
        </div>
        <div class="footer-legal">
            <span>&copy; <?= date('Y') ?> Maison Lure</span>
        </div>
    </footer>

    <script>
        window.productNames = <?= json_encode($productNames, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>
    <script src="assets/js/javascript.js" defer></script>
</body>

</html>