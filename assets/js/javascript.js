document.addEventListener("DOMContentLoaded", () => {
    let cart = readCart();
    const cartDisplay = document.getElementById("cart-count");
    const cartItems = document.getElementById("cart-items");
    const cartTotal = document.getElementById("cart-total");
    const itemCount = document.getElementById("item-count");
    const cartContent = document.querySelector(".cart-content");
    const currentLang = document.documentElement.lang || "ro";
    const MAX_CART_QUANTITY = 99;

    const translations = {
        ro: { added: "Produsul a fost adaugat in cos.", cleared: "Cosul a fost golit.", quantity: "Cantitate" },
        en: { added: "Product added to cart.", cleared: "Cart cleared.", quantity: "Quantity" },
        ru: { added: "Товар добавлен в корзину.", cleared: "Корзина очищена.", quantity: "Количество" }
    };

    function readCart() {
        try {
            const value = JSON.parse(localStorage.getItem("cart"));
            return Array.isArray(value)
                ? value
                    .filter(item => item && typeof item === "object" && !Array.isArray(item) && typeof item.key === "string" && item.key !== "")
                    .map(item => ({
                        ...item,
                        quantity: normalizeQuantity(item.quantity),
                        price: normalizePrice(item.price)
                    }))
                : [];
        } catch {
            return [];
        }
    }

    function normalizeQuantity(value) {
        const quantity = Number(value);
        return Number.isInteger(quantity) && quantity > 0
            ? Math.min(quantity, MAX_CART_QUANTITY)
            : 1;
    }

    function normalizePrice(value) {
        const price = Number(value);
        return Number.isFinite(price) && price >= 0 ? price : 0;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
        })[char]);
    }

    function showNotification(message) {
        document.querySelector(".notification")?.remove();
        const notification = document.createElement("div");
        notification.className = "notification";
        notification.textContent = message;
        document.body.appendChild(notification);
        requestAnimationFrame(() => notification.classList.add("show"));
        setTimeout(() => {
            notification.classList.remove("show");
            setTimeout(() => notification.remove(), 250);
        }, 2200);
    }

    function updateCart() {
        const totalQuantity = cart.reduce((sum, item) => sum + normalizeQuantity(item.quantity), 0);
        const total = cart.reduce((sum, item) => sum + normalizePrice(item.price) * normalizeQuantity(item.quantity), 0);

        if (cartDisplay) {
            cartDisplay.textContent = totalQuantity;
            cartDisplay.classList.toggle("hidden", totalQuantity === 0);
        }
        if (itemCount) itemCount.textContent = totalQuantity;
        if (cartTotal) cartTotal.textContent = total.toFixed(0);
        document.getElementById("cart-empty")?.style.setProperty("display", totalQuantity ? "none" : "block");

        if (cartItems) {
            cartItems.innerHTML = cart.map(item => {
                const name = escapeHtml(item.name || window.productNames?.[item.key] || "Produs");
                const key = escapeHtml(item.key || "");
                const image = escapeHtml(item.image || "");
                const quantity = normalizeQuantity(item.quantity);
                const price = normalizePrice(item.price) * quantity;
                return `<li>
                    <img src="${image}" alt="${name}">
                    <div class="item-details">
                        <span class="item-name">${name}</span>
                        <span class="item-quantity">${translations[currentLang]?.quantity || "Quantity"}: ${quantity}</span>
                    </div>
                    <span class="item-price">${price.toFixed(0)} MDL</span>
                    <div class="quantity-controls">
                        <button class="decrease-quantity" data-key="${key}" type="button" aria-label="Decrease">−</button>
                        <button class="remove-item" data-key="${key}" type="button" aria-label="Remove">×</button>
                    </div>
                </li>`;
            }).join("");
        }

        localStorage.setItem("cart", JSON.stringify(cart));
    }

    document.querySelector(".container")?.addEventListener("click", event => {
        const button = event.target.closest(".buy-button");
        if (!button) return;

        const product = button.closest(".product");
        const key = button.dataset.key;
        const existing = cart.find(item => item.key === key);

        if (existing) {
            existing.quantity = Math.min(normalizeQuantity(existing.quantity) + 1, MAX_CART_QUANTITY);
        } else {
            cart.push({
                key,
                price: Number(button.dataset.price || 0),
                image: product?.querySelector("img")?.src || "",
                name: product?.querySelector("h3")?.textContent.trim() || "",
                quantity: 1
            });
        }

        updateCart();
        showNotification(translations[currentLang]?.added || translations.ro.added);
    });

    cartItems?.addEventListener("click", event => {
        const button = event.target.closest("button[data-key]");
        if (!button) return;
        const index = cart.findIndex(item => item.key === button.dataset.key);
        if (index < 0) return;

        const quantity = normalizeQuantity(cart[index].quantity);
        if (button.classList.contains("decrease-quantity") && quantity > 1) {
            cart[index].quantity = quantity - 1;
        } else {
            cart.splice(index, 1);
        }
        updateCart();
    });

    document.querySelector(".cart-button")?.addEventListener("click", event => {
        event.stopPropagation();
        cartContent?.classList.toggle("active");
    });
    cartContent?.addEventListener("click", event => event.stopPropagation());
    document.addEventListener("click", () => cartContent?.classList.remove("active"));

    document.querySelector(".clear-cart")?.addEventListener("click", () => {
        cart = [];
        updateCart();
        showNotification(translations[currentLang]?.cleared || translations.ro.cleared);
    });

    const searchInput = document.querySelector('input[name="search"]');
    const productGrid = document.querySelector(".collection .container");
    const noResults = document.querySelector(".no-results");

    function normalizeSearchText(value) {
        return String(value)
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLocaleLowerCase()
            .trim();
    }

    searchInput?.addEventListener("input", event => {
        const term = normalizeSearchText(event.target.value);
        let resultCount = 0;

        productGrid?.querySelectorAll(".product").forEach(product => {
            const searchableText = normalizeSearchText([
                product.querySelector("h3")?.textContent,
                product.querySelector(".product-category")?.textContent,
                product.querySelector(".product-tag")?.textContent
            ].join(" "));
            const visible = searchableText.includes(term);

            product.hidden = !visible;
            if (visible) resultCount++;
        });

        noResults?.classList.toggle("active", resultCount === 0);
    });

    updateCart();
});
