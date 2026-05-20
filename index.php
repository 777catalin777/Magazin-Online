<?php 
require_once 'config.php'; 
require_once 'language_switcher.php'; 

// Procesare login/register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Email și parola sunt obligatorii!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin_page.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $_SESSION['login_error'] = "Email sau parolă incorectă!";
                }
            } catch (PDOException $e) {
                $_SESSION['login_error'] = "Eroare la autentificare.";
                error_log("Login error: " . $e->getMessage());
            }
        }
        $_SESSION['active_form'] = 'login';
        header("Location: login.php");
        exit();
    }
    
    // REGISTER
    if (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = "Toate câmpurile sunt obligatorii!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = "Adresa de email nu este validă!";
        } elseif (strlen($password) < 6) {
            $_SESSION['register_error'] = "Parola trebuie să aibă minim 6 caractere!";
        } elseif ($password !== $confirm) {
            $_SESSION['register_error'] = "Parolele nu se potrivesc!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['register_error'] = "Există deja un cont cu acest email!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                    $stmt->execute([$name, $email, $hashed_password]);
                    
                    $_SESSION['register_success'] = "Cont creat cu succes! Te poți autentifica.";
                }
            } catch (PDOException $e) {
                $_SESSION['register_error'] = "Eroare la înregistrare.";
                error_log("Register error: " . $e->getMessage());
            }
        }
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Tintiuc Catalin">
    <meta name="description" content="Magazin online Maison Lure">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(lang('site_title')) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    <link rel="manifest" href="images/favicon/site.webmanifest">
</head>

<body>

    <header>
        <div class="header-left">
            <h1>Maison Lure</h1>
        </div>
        <form class="search-form" onsubmit="return false;">
            <input type="search" name="search" placeholder="<?= htmlspecialchars(lang('search_placeholder')) ?>">
            <button type="submit"><i class="bx bx-search"></i></button>
        </form>
        <div class="header-right">
            <?php if (isset($_SESSION['email'])): ?>
                <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_page.php' : 'user_page.php' ?>" class="login-button">
                    <i class="bx bxs-user"></i> <?= htmlspecialchars($_SESSION['name']) ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="login-button"><i class="bx bxs-user"></i> <?= htmlspecialchars(lang('login')) ?></a>
            <?php endif; ?>

            <div class="cart-container">
                <button class="cart-button"><i class="bx bxs-cart"></i><span id="cart-count" class="hidden">0</span></button>
                <div class="cart-content">
                    <h3><?= htmlspecialchars(lang('cart')) ?></h3>
                    <div id="cart-empty" class="cart-empty">
                        <i class="bx bxs-cart empty-cart-icon"></i>
                        <p><?= htmlspecialchars(lang('empty_cart')) ?></p>
                    </div>
                    <ul id="cart-items"></ul>
                    <div class="cart-summary">
                        <p><?= htmlspecialchars(lang('products')) ?>: <span id="item-count">0</span></p>
                        <p><?= htmlspecialchars(lang('total')) ?>: <span id="cart-total">0</span> MDL</p>
                    </div>
                    <button class="clear-cart"><?= htmlspecialchars(lang('clear_cart')) ?></button>
                </div>
            </div>
            <div class="language-switcher">
                <a href="?lang=ro" class="<?= $lang=='ro'?'active':'' ?>">RO</a>
                <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">EN</a>
                <a href="?lang=ru" class="<?= $lang=='ru'?'active':'' ?>">RU</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="product" style="--order: 0;">
            <img src="images/produse/Tricou Nike Premium.avif" alt="<?= htmlspecialchars(lang('name_product_1')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_1')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 800 MDL</p>
            <button class="buy-button" data-key="name_product_1" data-price="800">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 1;">
            <img src="images/produse/Tricou Polo.avif" alt="<?= htmlspecialchars(lang('name_product_2')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_2')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 990 MDL</p>
            <button class="buy-button" data-key="name_product_2" data-price="990">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 2;">
            <img src="images/produse/Tricou Negru.avif" alt="<?= htmlspecialchars(lang('name_product_3')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_3')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 320 MDL</p>
            <button class="buy-button" data-key="name_product_3" data-price="320">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 3;">
            <img src="images/produse/Blugi Largi.avif" alt="<?= htmlspecialchars(lang('name_product_4')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_4')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 650 MDL</p>
            <button class="buy-button" data-key="name_product_4" data-price="650">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 4;">
            <img src="images/produse/Pantaloni Jack & Jones.avif" alt="<?= htmlspecialchars(lang('name_product_5')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_5')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 650 MDL</p>
            <button class="buy-button" data-key="name_product_5" data-price="650">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 5;">
            <img src="images/produse/Blugi Collusion.avif" alt="<?= htmlspecialchars(lang('name_product_6')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_6')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 630 MDL</p>
            <button class="buy-button" data-key="name_product_6" data-price="630">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 6;">
            <img src="images/produse/Hanorac Dior.avif" alt="<?= htmlspecialchars(lang('name_product_7')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_7')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 700 MDL</p>
            <button class="buy-button" data-key="name_product_7" data-price="700">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 7;">
            <img src="images/produse/Hanorac Weekday.avif" alt="<?= htmlspecialchars(lang('name_product_8')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_8')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 900 MDL</p>
            <button class="buy-button" data-key="name_product_8" data-price="900">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 8;">
            <img src="images/produse/Hanorac Adidas.avif" alt="<?= htmlspecialchars(lang('name_product_9')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_9')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 1200 MDL</p>
            <button class="buy-button" data-key="name_product_9" data-price="1200">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 9;">
            <img src="images/produse/NewBalance 9060.avif" alt="<?= htmlspecialchars(lang('name_product_10')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_10')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 3400 MDL</p>
            <button class="buy-button" data-key="name_product_10" data-price="3400">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 10;">
            <img src="images/produse/Nike V2K Run.avif" alt="<?= htmlspecialchars(lang('name_product_11')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_11')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 2500 MDL</p>
            <button class="buy-button" data-key="name_product_11" data-price="2500">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>

        <div class="product" style="--order: 11;">
            <img src="images/produse/Adidas Samba.avif" alt="<?= htmlspecialchars(lang('name_product_12')) ?>">
            <h1><?= htmlspecialchars(lang('name_product_12')) ?></h1>
            <p><?= htmlspecialchars(lang('price')) ?> 2000 MDL</p>
            <button class="buy-button" data-key="name_product_12" data-price="2000">
                <?= htmlspecialchars(lang('add_to_cart')) ?>
            </button>
        </div>
    </div>

    <script>
    window.productNames = {
        "name_product_1":  "<?= addslashes(lang('name_product_1')) ?>",
        "name_product_2":  "<?= addslashes(lang('name_product_2')) ?>",
        "name_product_3":  "<?= addslashes(lang('name_product_3')) ?>",
        "name_product_4":  "<?= addslashes(lang('name_product_4')) ?>",
        "name_product_5":  "<?= addslashes(lang('name_product_5')) ?>",
        "name_product_6":  "<?= addslashes(lang('name_product_6')) ?>",
        "name_product_7":  "<?= addslashes(lang('name_product_7')) ?>",
        "name_product_8":  "<?= addslashes(lang('name_product_8')) ?>",
        "name_product_9":  "<?= addslashes(lang('name_product_9')) ?>",
        "name_product_10": "<?= addslashes(lang('name_product_10')) ?>",
        "name_product_11": "<?= addslashes(lang('name_product_11')) ?>",
        "name_product_12": "<?= addslashes(lang('name_product_12')) ?>"
    };
    </script>

    <div id="chrome-footer">
        <footer class="Va6oZkc" data-testid="footer">
            <div class="yoIGaVj">
                <div class="zXWf5qx">
                    <ul class="SJ0iPNG" data-testid="social-links-bar">
                        <li class="st34raq J8Sftp9"><a class="cYGKXSQ" href="#" target="_blank" rel="noopener noreferrer" data-testid="social-link"><span class="HURX2dz AQi6YMD"></span></a></li>
                        <li class="st34raq mbXMayF"><a class="cYGKXSQ" href="#" target="_blank" rel="noopener noreferrer" data-testid="social-link"><span class="HURX2dz AQi6YMD"></span></a></li>
                        <li class="st34raq CDRgBJC"><a class="cYGKXSQ" href="#" target="_blank" rel="noopener noreferrer" data-testid="social-link"><span class="HURX2dz AQi6YMD"></span></a></li>
                    </ul>
                    <ul class="jvbvX2Y">
                        <li class="CDnbQF2"><img src="https://images.asos-media.com/navigation/visa-png" alt="VISA"></li>
                        <li class="CDnbQF2"><img src="https://images.asos-media.com/navigation/mastercard-png" alt="Mastercard"></li>
                        <li class="CDnbQF2"><img src="https://images.asos-media.com/navigation/pay-pal-png" alt="PayPal"></li>
                        <li class="CDnbQF2"><img src="https://images.asos-media.com/navigation/american-express-png" alt="American Express"></li>
                        <li class="CDnbQF2"><img src="https://images.asos-media.com/navigation/visa-electron-png" alt="VISA Electron"></li>
                    </ul>
                </div>
                <div class="pdPlC2x"></div>
                <div class="fAAF5ET">
                    <div class="x1ETIN7" data-testid="footer-links-group">
                        <section class="hnrGAO8" open="" data-testid="footer-links">
                            <h3 class="eGtGJSX fVdHxMU">Help &amp; Information</h3>
                            <ul>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Help</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Track order</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Delivery &amp; returns</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Sitemap</a></li>
                            </ul>
                        </section>
                        <section class="hnrGAO8" open="" data-testid="footer-links">
                            <h3 class="eGtGJSX fVdHxMU">About Maison Lure</h3>
                            <ul>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">About us</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#" target="_blank">Careers at Maison Lure</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#" target="_blank">Corporate responsibility</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#" target="_blank">Investors' site</a></li>
                            </ul>
                        </section>
                        <section class="hnrGAO8" open="" data-testid="footer-links">
                            <h3 class="eGtGJSX fVdHxMU">More From Maison Lure</h3>
                            <ul>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Mobile and Maison Lure apps</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Gift vouchers</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#">Black Friday</a></li>
                                <li><a class="XWRSfDm fVdHxMU TYb4J9A" href="#" target="_blank">Maison Lure x Thrift+</a></li>
                            </ul>
                        </section>
                        <div class="eLMg6Nh">
                            <h3 class="qljN_kD TNLlZ7K london3">Shopping from:</h3>
                            <div class="RxHz4Yh EGfsISy" data-testid="country-selector">
                                <button class="breiRmE TYb4J9A" data-testid="country-selector-btn" type="button" aria-label="You're in Moldova, Republic of Change">
                                    <span class="z6TXMJ2 fVdHxMU">You're in</span>
                                    <img src="https://assets.asosservices.com/storesa/images/flags/md.png" alt="Moldova, Republic of" class="Oqkee2R">
                                    <div class="DOXKUAb jFyrDfG"><span class="XKJ5IQs" aria-hidden="true"></span><span>Change</span></div>
                                </button>
                            </div>
                            <div class="KqOvkLQ">
                                <h4 id="chrome-international-sites">Some of our international sites:</h4>
                                <ul aria-labelledby="chrome-international-sites">
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/es.png" alt="Spain" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/de.png" alt="Germany" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/au.png" alt="Australia" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/fr.png" alt="France" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/us.png" alt="United States" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/dk.png" alt="Denmark" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/it.png" alt="Italy" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/nl.png" alt="Netherlands" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/pl.png" alt="Poland" class="Oqkee2R"></a></li>
                                    <li><a href="#"><img src="https://assets.asosservices.com/storesa/images/flags/se.png" alt="Sweden" class="Oqkee2R"></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="qHG1XBy fVdHxMU">
                <div class="IA4t6xg" data-testid="legalbar">
                    <p class="p4s35il">© 2026 Maison Lure</p>
                    <ul class="zikOExT">
                        <li><a href="#">Maison Lure details</a><span class="E1LOrCT" aria-hidden="true"></span></li>
                        <li><a href="#">Privacy &amp; Cookies</a><span class="E1LOrCT" aria-hidden="true"></span></li>
                        <li><a href="#">Ts&amp;Cs</a><span class="E1LOrCT" aria-hidden="true"></span></li>
                        <li class="RgP909u"><a href="#">Accessibility</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>

    <script src="javascript.js"></script>
</body>

</html>