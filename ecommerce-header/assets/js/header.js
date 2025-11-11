(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function toggleMenu() {
        var toggle = document.querySelector('.echp-menu-toggle');
        var nav = document.querySelector('.echp-navigation');

        if (!toggle || !nav) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function updateCartCount(count) {
        var el = document.querySelector('[data-echp-cart-count]');
        if (!el) {
            return;
        }

        el.textContent = typeof count === 'number' ? count : 0;
    }

    function requestCartCount() {
        if (!window.echpHeader || !window.echpHeader.ajaxUrl) {
            return;
        }

        var formData = new URLSearchParams();
        formData.set('action', 'echp_cart_count');

        fetch(window.echpHeader.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: formData.toString()
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            })
            .then(function (payload) {
                if (payload && payload.success && payload.data && typeof payload.data.count !== 'undefined') {
                    updateCartCount(parseInt(payload.data.count, 10));
                }
            })
            .catch(function () {
                // Silently ignore failures and leave current count untouched.
            });
    }

    function bindCartEvents() {
        if (window.jQuery) {
            window.jQuery(function ($) {
                $(document.body).on('added_to_cart wc_fragments_refreshed', function () {
                    requestCartCount();
                });
            });
        }

        document.addEventListener('echp-refresh-cart', function () {
            requestCartCount();
        });
    }

    ready(function () {
        toggleMenu();
        bindCartEvents();
        requestCartCount();
    });
})();
