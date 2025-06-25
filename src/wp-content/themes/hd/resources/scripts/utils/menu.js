// menu.js

export function initMenu(containerSelector, menuSelector) {
    const container = document.querySelector(containerSelector);
    const menu = document.querySelector(menuSelector);

    if (!container || !menu) return;

    function adjustMenu() {
        let more = menu.querySelector('.more');

        if (!more) {
            more = document.createElement('li');
            more.classList.add('more');
            more.innerHTML = '<a href="#"></a><ul class="sub-menu dropdown"></ul>';
            menu.appendChild(more);
        }

        const dropdown = more.querySelector('.dropdown');
        dropdown.innerHTML = '';
        more.style.display = 'none';

        let items = [...menu.children].filter((li) => li !== more);
        items.forEach((li) => (li.style.display = 'block'));

        if (menu.scrollWidth <= container.clientWidth) {
            removeOverflowHidden();
            return;
        }

        let hiddenItems = [];
        for (let i = items.length - 1; i >= 0; i--) {
            if (menu.scrollWidth > container.clientWidth) {
                hiddenItems.unshift(items[i]);
                items[i].style.display = 'none';
            } else {
                break;
            }
        }

        if (hiddenItems.length > 0) {
            hiddenItems.forEach((item) => {
                let clone = item.cloneNode(true);
                clone.style.display = 'block';
                dropdown.appendChild(clone);
            });
            more.style.display = 'block';
        }

        removeOverflowHidden();
    }

    function setOverflowHidden() {
        container.style.overflow = 'hidden';
    }

    function removeOverflowHidden() {
        container.style.overflow = 'visible';
    }

    function reinitializeFoundationDropdown() {
        if (typeof Foundation !== 'undefined') {
            let mainNav = $(menuSelector);
            if (mainNav.length) {
                new Foundation.DropdownMenu(mainNav);
            }
        }
    }

    let resizeTimeout;

    function init() {
        setOverflowHidden();
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            adjustMenu();
            reinitializeFoundationDropdown();
        }, 100);
    }

    init();
    window.addEventListener('resize', function () {
        init();
    });
}
