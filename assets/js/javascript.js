document.addEventListener("DOMContentLoaded", () => {
    const MAX_CART_QUANTITY = 99;
    let cart = readCart();
    const cartDisplay = document.getElementById("cart-count");
    const cartItems = document.getElementById("cart-items");
    const cartTotal = document.getElementById("cart-total");
    const itemCount = document.getElementById("item-count");
    const cartContent = document.querySelector(".cart-content");
    const currentLang = document.documentElement.lang || "ro";
    const siteHeader = document.querySelector(".site-header");
    const categoryBar = document.querySelector(".category-bar");
    const megaMenuTriggers = [...document.querySelectorAll(".category-menu-trigger")];
    const megaMenus = [...document.querySelectorAll(".mega-menu")];
    const megaMenuCloseButtons = [...document.querySelectorAll("[data-close-menu]")];
    let activeMenuTrigger = null;

    const translations = {
        ro: { added: "Produsul a fost adaugat in cos.", cleared: "Cosul a fost golit.", quantity: "Cantitate" },
        en: { added: "Product added to cart.", cleared: "Cart cleared.", quantity: "Quantity" },
        ru: { added: "Товар добавлен в корзину.", cleared: "Корзина очищена.", quantity: "Количество" }
    };

    function readCart() {
        try {
            const value = JSON.parse(localStorage.getItem("cart"));
            if (!Array.isArray(value)) return [];

            const itemsByKey = new Map();
            value.forEach(item => {
                if (!item || typeof item !== "object" || Array.isArray(item) || typeof item.key !== "string" || item.key === "") {
                    return;
                }

                const quantity = normalizeQuantity(item.quantity);
                const existing = itemsByKey.get(item.key);
                if (existing) {
                    existing.quantity = Math.min(existing.quantity + quantity, MAX_CART_QUANTITY);
                } else {
                    itemsByKey.set(item.key, {
                        ...item,
                        quantity,
                        price: normalizePrice(item.price)
                    });
                }
            });

            return [...itemsByKey.values()];
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
        let totalQuantity = 0;
        let total = 0;
        cart.forEach(item => {
            const quantity = normalizeQuantity(item.quantity);
            totalQuantity += quantity;
            total += normalizePrice(item.price) * quantity;
        });

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

    function setMegaMenuOpen(trigger = null, restoreFocus = false) {
        if (!categoryBar) return;

        const isOpen = Boolean(trigger);
        activeMenuTrigger = trigger;
        categoryBar.classList.toggle("is-open", isOpen);
        document.body.classList.toggle("mega-menu-open", isOpen);
        megaMenuTriggers.forEach(item => {
            const controlsMenu = item.dataset.menu === trigger?.dataset.menu;
            item.setAttribute("aria-expanded", String(isOpen && controlsMenu));
        });
        megaMenus.forEach(menu => {
            menu.classList.toggle("is-active", isOpen && menu.id === trigger?.dataset.menu);
        });

        if (restoreFocus && trigger) {
            trigger.focus();
        }
    }

    megaMenuTriggers.forEach(trigger => {
        trigger.addEventListener("click", event => {
            if (!window.matchMedia("(max-width: 800px)").matches) return;
            event.stopPropagation();
            const isSameOpenMenu = categoryBar?.classList.contains("is-open") && activeMenuTrigger === trigger;
            setMegaMenuOpen(isSameOpenMenu ? null : trigger);
        });
    });

    megaMenuCloseButtons.forEach(button => button.addEventListener("click", event => {
        event.preventDefault();
        event.stopPropagation();
        const trigger = activeMenuTrigger;
        setMegaMenuOpen(null);
        trigger?.focus({ preventScroll: true });
    }));

    megaMenus.forEach(menu => {
        menu.addEventListener("click", event => {
            if (!event.target.closest("a")) {
                event.stopPropagation();
            }
        });
    });

    document.addEventListener("click", event => {
        if (
            window.matchMedia("(max-width: 800px)").matches &&
            categoryBar?.classList.contains("is-open") &&
            !categoryBar.contains(event.target)
        ) {
            setMegaMenuOpen(null);
        }
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && categoryBar?.classList.contains("is-open")) {
            const trigger = activeMenuTrigger;
            setMegaMenuOpen(null);
            trigger?.focus();
        }
    });

    window.addEventListener("resize", () => {
        if (!window.matchMedia("(max-width: 800px)").matches) {
            setMegaMenuOpen(null);
        }
    });

    document.querySelector(".clear-cart")?.addEventListener("click", () => {
        cart = [];
        updateCart();
        showNotification(translations[currentLang]?.cleared || translations.ro.cleared);
    });

    const searchInput = document.querySelector('input[name="search"]');
    const productGrid = document.querySelector(".collection .container");
    const noResults = document.querySelector(".no-results");
    const searchableProducts = productGrid
        ? [...productGrid.querySelectorAll(".product")].map(product => ({
            element: product,
            text: normalizeSearchText([
                product.querySelector("h3")?.textContent,
                product.querySelector(".product-category")?.textContent,
                product.querySelector(".product-tag")?.textContent
            ].join(" "))
        }))
        : [];

    function normalizeSearchText(value) {
        return String(value)
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLocaleLowerCase()
            .trim();
    }

    let searchFrame = 0;
    searchInput?.addEventListener("input", event => {
        const value = event.target.value;
        cancelAnimationFrame(searchFrame);
        searchFrame = requestAnimationFrame(() => {
            const term = normalizeSearchText(value);
            let resultCount = 0;

            searchableProducts.forEach(({ element, text }) => {
                const visible = text.includes(term);
                element.hidden = !visible;
                if (visible) resultCount++;
            });

            noResults?.classList.toggle("active", resultCount === 0);
        });
    });

    if (siteHeader && categoryBar) {
        let lastScrollY = window.scrollY;
        let ticking = false;
        const scrollDelta = 8;

        function updateHeaderVisibility() {
            const currentScrollY = Math.max(window.scrollY, 0);
            const isScrollingDown = currentScrollY > lastScrollY + scrollDelta;
            const isScrollingUp = currentScrollY < lastScrollY - scrollDelta;
            const hasOpenOverlay = Boolean(
                cartContent?.classList.contains("active") ||
                categoryBar.matches(":hover") ||
                categoryBar.matches(":focus-within")
            );

            if (currentScrollY <= 12 || hasOpenOverlay || isScrollingUp) {
                document.body.classList.remove("header-hidden");
            } else if (isScrollingDown && currentScrollY > siteHeader.offsetHeight) {
                document.body.classList.add("header-hidden");
            }

            if (isScrollingDown || isScrollingUp) {
                lastScrollY = currentScrollY;
            }

            ticking = false;
        }

        window.addEventListener("scroll", () => {
            if (!ticking) {
                window.requestAnimationFrame(updateHeaderVisibility);
                ticking = true;
            }
        }, { passive: true });
    }

    updateCart();
});
