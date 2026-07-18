(function () {
    'use strict';

    const CONTENT_ID = 'ajax-content';

    function contentEl() {
        return document.getElementById(CONTENT_ID);
    }

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    // Browsers don't execute <script> tags inserted via innerHTML, so any
    // per-page inline script (e.g. settings page's add-row button) has to be
    // manually re-created to actually run after a swap.
    function reExecuteScripts(container) {
        Array.from(container.querySelectorAll('script')).forEach(function (oldScript) {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
    }

    function updateActiveNav(url) {
        const path = new URL(url, window.location.href).pathname;
        const matchers = {
            dashboard: /^\/dashboard/,
            albums: /^\/admin\/albums/,
            upload: /^\/admin\/upload/,
            settings: /^\/admin\/settings/,
            messages: /^\/admin\/messages/,
            users: /^\/admin\/users/,
        };

        document.querySelectorAll('a[data-nav-match]').forEach(function (link) {
            const key = link.getAttribute('data-nav-match');
            const matcher = matchers[key];
            link.classList.toggle('active', !!matcher && matcher.test(path));
        });
    }

    async function loadPage(url, pushState) {
        const current = contentEl();
        if (!current) {
            window.location.href = url;
            return;
        }

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newContent = doc.getElementById(CONTENT_ID);

            if (!newContent) {
                // Target page doesn't use this layout (e.g. the public site,
                // login screen) -- do a real navigation instead.
                window.location.href = url;
                return;
            }

            document.title = doc.title;
            current.innerHTML = newContent.innerHTML;
            reExecuteScripts(current);
            updateActiveNav(url);

            if (pushState) {
                history.pushState({ ajaxNav: true }, '', url);
            }

            window.scrollTo(0, 0);
            document.dispatchEvent(new CustomEvent('admin-nav:loaded'));
        } catch (err) {
            window.location.href = url;
        }
    }

    document.addEventListener('click', function (e) {
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        const link = e.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        if (!isSameOrigin(href)) {
            return;
        }

        e.preventDefault();
        loadPage(href, true);
    });

    window.addEventListener('popstate', function () {
        loadPage(window.location.href, false);
    });
})();
