document.addEventListener("DOMContentLoaded", function () {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    const cartDisplay = document.getElementById("cart-count");
    const cartItems = document.getElementById("cart-items");
    const cartTotal = document.getElementById("cart-total");
    const itemCount = document.getElementById("item-count");
    const cartButton = document.querySelector(".cart-button");
    const cartContent = document.querySelector(".cart-content");
    const clearCartButton = document.querySelector(".clear-cart");

    window.currentLang = document.documentElement.lang || 'ro';

    function t(key) {
        const translations = {
            ro: { 
                cart: "Coș de cumpărături", 
                empty_cart: "Coșul tău este gol. Adaugă produse pentru a începe!", 
                products: "Produse", 
                total: "Total", 
                clear_cart: "Golește Coșul", 
                added_to_cart: "Produsul a fost adăugat în coș!", 
                quantity: "Cantitate" 
            },
            en: { 
                cart: "Shopping Cart", 
                empty_cart: "Your cart is empty. Add products to get started!", 
                products: "Products", 
                total: "Total", 
                clear_cart: "Clear Cart", 
                added_to_cart: "Product added to cart!", 
                quantity: "Quantity" 
            },
            ru: { 
                cart: "Корзина", 
                empty_cart: "Ваша корзина пуста. Добавьте товары!", 
                products: "Товары", 
                total: "Итого", 
                clear_cart: "Очистить корзину", 
                added_to_cart: "Товар добавлен в корзину!", 
                quantity: "Количество" 
            }
        };
        return translations[window.currentLang]?.[key] || key;
    }

    function getProductName(key) {
        
        if (window.productNames && window.productNames[key]) {
            return window.productNames[key];
        }
        
        const button = document.querySelector(`.buy-button[data-key="${key}"]`);
        if (button) {
            const product = button.closest(".product");
            if (product) {
                const h1 = product.querySelector("h1");
                if (h1 && h1.textContent) {
                    return h1.textContent.trim();
                }
            }
        }
        
        const fallbackNames = {
            "name_product_1": "Tricou Nike Premium",
            "name_product_2": "Tricou Polo",
            "name_product_3": "Tricou Negru",
            "name_product_4": "Blugi Largi",
            "name_product_5": "Pantaloni Jack & Jones",
            "name_product_6": "Blugi Collusion",
            "name_product_7": "Hanorac Dior",
            "name_product_8": "Hanorac Weekday",
            "name_product_9": "Hanorac Adidas",
            "name_product_10": "New Balance 9060",
            "name_product_11": "Nike V2K Run",
            "name_product_12": "Adidas Samba"
        };
        
        return fallbackNames[key] || "Produs";
    }

    function showNotification(message) {

        const existingNotification = document.querySelector(".notification");
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement("div");
        notification.className = "notification";
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add("show"), 10);
        setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function updateCart() {
        const totalQuantity = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
        if (cartDisplay) cartDisplay.textContent = totalQuantity;
        if (itemCount) itemCount.textContent = totalQuantity;
        if (cartDisplay) cartDisplay.classList.toggle("hidden", totalQuantity === 0);

        if (cartItems) {
            cartItems.innerHTML = "";
        }
        let total = 0;

        const cartEmpty = document.getElementById("cart-empty");
        if (cartEmpty) {
            cartEmpty.style.display = totalQuantity === 0 ? "block" : "none";
        }
        if (cartItems) {
            cartItems.style.display = totalQuantity === 0 ? "none" : "block";
        }

        cart.forEach(item => {
            const currentName = getProductName(item.key);
            total += (item.price || 0) * (item.quantity || 0);

            const li = document.createElement("li");
            li.innerHTML = `
                <img src="${item.image || ''}" alt="${currentName.replace(/"/g, '&quot;')}">
                <div class="item-details">
                    <span class="item-name">${currentName.replace(/</g, '&lt;')}</span>
                    <span class="item-quantity">${t('quantity')}: ${item.quantity || 1}</span>
                </div>
                <span class="item-price">${((item.price || 0) * (item.quantity || 1)).toFixed(0)} MDL</span>
                <div class="quantity-controls">
                    <button class="decrease-quantity" data-key="${item.key}">−</button>
                    <button class="remove-item" data-key="${item.key}">×</button>
                </div>
            `;
            if (cartItems) cartItems.appendChild(li);
        });

        if (cartTotal) cartTotal.textContent = total.toFixed(0);
        localStorage.setItem("cart", JSON.stringify(cart));
    }

    document.querySelectorAll(".buy-button").forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const key = this.getAttribute("data-key");
            if (!key) return;

            const price = parseFloat(this.getAttribute("data-price")) || 0;
            const productCard = this.closest(".product");
            const image = productCard ? productCard.querySelector("img")?.src || "" : "";

            const existing = cart.findIndex(item => item.key === key);
            if (existing !== -1) {
                cart[existing].quantity = (cart[existing].quantity || 0) + 1;
            } else {
                cart.push({ key, price, image, quantity: 1 });
            }

            updateCart();
            showNotification(t('added_to_cart'));
        });
    });

    if (cartItems) {
        cartItems.addEventListener("click", function (e) {
            const key = e.target.getAttribute("data-key");
            if (!key) return;

            if (e.target.classList.contains("decrease-quantity")) {
                const index = cart.findIndex(i => i.key === key);
                if (index !== -1) {
                    if (cart[index].quantity > 1) {
                        cart[index].quantity--;
                    } else {
                        cart.splice(index, 1);
                    }
                }
            } else if (e.target.classList.contains("remove-item")) {
                cart = cart.filter(i => i.key !== key);
            }
            updateCart();
        });
    }

    if (cartButton) {
        cartButton.addEventListener("click", e => { 
            e.stopPropagation(); 
            if (cartContent) cartContent.classList.toggle("active"); 
        });
    }
    
    document.addEventListener("click", () => {
        if (cartContent) cartContent.classList.remove("active");
    });
    
    if (cartContent) {
        cartContent.addEventListener("click", e => e.stopPropagation());
    }

    if (clearCartButton) {
        clearCartButton.addEventListener("click", () => { 
            cart = []; 
            updateCart(); 
            showNotification("Coșul a fost golit");
        });
    }

    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase().trim();
            const products = document.querySelectorAll('.product');
            let hasResults = false;
            
            products.forEach(p => {
                const name = p.querySelector('h1')?.textContent.toLowerCase() || '';
                const isVisible = term === '' || name.includes(term);
                p.style.display = isVisible ? 'flex' : 'none';
                if (isVisible) hasResults = true;
            });
            
            let noResultsMsg = document.querySelector('.no-results');
            if (!noResultsMsg && products.length > 0) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results';
                noResultsMsg.textContent = 'Nu s-au găsit produse.';
                document.querySelector('.container')?.appendChild(noResultsMsg);
            }
            if (noResultsMsg) {
                noResultsMsg.classList.toggle('active', term !== '' && !hasResults);
            }
        });
    }

    updateCart();
});