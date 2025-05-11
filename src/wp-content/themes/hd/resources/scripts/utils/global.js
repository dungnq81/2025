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

//---------------------------------------------


