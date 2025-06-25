// global.js (IIFE)

(function () {
    // update rel
    document.querySelectorAll('a._blank, a.blank, a[target="_blank"]').forEach((el) => {
        if (!el.hasAttribute('target') || el.getAttribute('target') !== '_blank') {
            el.setAttribute('target', '_blank');
        }

        const relValue = el?.getAttribute('rel');
        if (!relValue || !relValue.includes('noopener') || !relValue.includes('nofollow')) {
            const newRelValue = (relValue ? relValue + ' ' : '') + 'noopener noreferrer nofollow';
            el.setAttribute('rel', newRelValue);
        }
    });

    // Error handling for images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xNTAgMTAwQzEyNy45MSAxMDAgMTEwIDExNy45MSAxMTAgMTQwQzExMCAxNjIuMDkgMTI3LjkxIDE4MCAxNTAgMTgwQzE3Mi4wOSAxODAgMTkwIDE2Mi4wOSAxOTAgMTQwQzE5MCAxMTcuOTEgMTcyLjA5IDEwMCAxNTAgMTAwWiIgZmlsbD0iI0Q5RERFMSIvPgo8L3N2Zz4K';
            this.alt = 'Not found';
        });
    });

    // MutationObserver
    const observer = new MutationObserver(() => {
        document.querySelectorAll('ul.sub-menu[role="menubar"]').forEach(menu => {
            menu.setAttribute('role', 'menu');
        });

        document.querySelectorAll('[aria-hidden="true"] a, [aria-hidden="true"] button').forEach(el => {
            el.setAttribute('tabindex', '-1');
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
