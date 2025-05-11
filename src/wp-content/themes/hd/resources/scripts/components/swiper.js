import { nanoid } from 'nanoid';
import Swiper from 'swiper/bundle';

/**
 * Initialize a Swiper instance for a given element.
 * @param el
 * @param swiperClass
 * @param options
 */
const initializeSwiper = (el, swiperClass, options) => {
    if (!(el instanceof Element)) {
        console.error('Error: The provided element is not a valid DOM element.');
        return;
    }

    if (el.classList.contains('swiper-initialized') || el.dataset.swiperInitialized) return; // Prevent re-initialization
    el.dataset.swiperInitialized = 'true';

    const swiper = new Swiper(swiperClass, options);

    // Pause autoplay on hover, resume on mouse out
    el.addEventListener('mouseover', () => swiper.autoplay?.stop());
    el.addEventListener('mouseout', () => options.autoplay && swiper.autoplay?.start());

    return swiper;
};

/**
 * Generate unique class names for Swiper instance.
 * @returns {Object} - Object containing unique class names.
 */
const generateClasses = () => {
    const rand = nanoid(10);
    return {
        rand: rand,
        swiperClass: `swiper-${rand}`,
        nextClass: `next-${rand}`,
        prevClass: `prev-${rand}`,
        paginationClass: `pagination-${rand}`,
        scrollbarClass: `scrollbar-${rand}`,
    };
};

/**
 * Default Swiper options.
 * @returns {Object} - Default Swiper configuration.
 */
const getDefaultOptions = () => ({
    grabCursor: true,
    allowTouchMove: true,
    threshold: 5,
    autoHeight: false,
    loop: false,
    hashNavigation: false,
    direction: 'horizontal',
    freeMode: false,
    cssMode: false,
    centeredSlides: false,
    slidesPerView: 'auto',
});

/**
 * Parse options safely
 * @param el
 * @returns {{}|any|{}}
 */
const parseOptions = (el) => {
    try {
        return JSON.parse(el.dataset.options) || {};
    } catch (e) {
        console.error('Invalid JSON in data-options', e);
        return {};
    }
};

// Initialize Swipers
const initializeSwipers = () => {
    const swiperElements = document.querySelectorAll('.w-swiper');
    swiperElements.forEach((el) => {
        if (el.classList.contains('swiper-initialized')) return; // Prevent re-initialization

        const classes = generateClasses();
        el.classList.add(classes.swiperClass);

        const container = el.closest('.swiper-container');

        // Create or get control container
        let controls = container?.querySelector('.swiper-controls');
        if (!controls) {
            controls = document.createElement('div');
            controls.classList.add('swiper-controls');
            el.after(controls);
        }

        let options = parseOptions(el);
        let swiperOptions = { ...getDefaultOptions() };

        // Parse specific options
        [
            'autoHeight',
            'loop',
            'freeMode',
            'cssMode',
            'mousewheel',
            'parallax',
            'hashNavigation',
        ].forEach(key => options[key] && (swiperOptions[key] = true));

        swiperOptions.wrapperClass = String(options.wrapperClass || 'swiper-wrapper');
        swiperOptions.slideClass = String(options.slideClass || 'swiper-slide');
        swiperOptions.slideActiveClass = String(options.slideActiveClass || 'swiper-slide-active');

        swiperOptions.direction = String(options.direction || 'horizontal');
        swiperOptions.slidesPerView = options.slidesPerView || 'auto';
        swiperOptions.spaceBetween = parseInt(options.spaceBetween, 10) || 0;
        swiperOptions.speed = parseInt(options.speed, 10) || 300;

        // Grid settings
        if (options.grid) {
            swiperOptions.grid = {
                rows: Math.max(parseInt(options.grid?.rows) || 1, 1),
                fill: options.grid?.fill || 'row',
            };
            if (options.loop) swiperOptions.loopAddBlankSlides = true;
        }

        // Autoplay settings
        if (options.autoplay) {
            swiperOptions.autoplay = {
                delay: parseInt(options.autoplay?.delay) || 3000,
                disableOnInteraction: options.autoplay?.disableOnInteraction || true,
                reverseDirection: options.autoplay?.reverseDirection || false,
            };
        }

        // Navigation controls
        if (options.navigation) {
            let btnPrev = container?.querySelector('.swiper-button-prev');
            let btnNext = container?.querySelector('.swiper-button-next');

            if (!btnPrev) {
                btnPrev = document.createElement('div');
                btnPrev.classList.add('swiper-button', 'swiper-button-prev');
                btnPrev.setAttribute('data-fa', '');
                controls.append(btnPrev);
            }
            if (!btnNext) {
                btnNext = document.createElement('div');
                btnNext.classList.add('swiper-button', 'swiper-button-next');
                btnNext.setAttribute('data-fa', '');
                controls.append(btnNext);
            }

            btnPrev.classList.add(classes.prevClass);
            btnNext.classList.add(classes.nextClass);

            swiperOptions.navigation = {
                nextEl: `.${classes.nextClass}`,
                prevEl: `.${classes.prevClass}`,
            };
        }

        // Pagination controls
        if (options.pagination) {
            let pagination = container?.querySelector('.swiper-pagination');
            if (!pagination) {
                pagination = document.createElement('div');
                pagination.classList.add('swiper-pagination');
                controls.appendChild(pagination);
            }

            pagination.classList.add(classes.paginationClass);

            const paginationType = String(options.pagination);
            swiperOptions.pagination = {
                el: `.${classes.paginationClass}`,
                clickable: true,
                ...(paginationType === 'bullets' && { dynamicBullets: !0, type: 'bullets' }),
                ...(paginationType === 'fraction' && { type: 'fraction' }),
                ...(paginationType === 'progressbar' && { type: 'progressbar' }),
                ...(paginationType === 'custom' && {
                    renderBullet: (index, className) => `<span class="${className}">${index + 1}</span>`,
                }),
            };
        }

        // Scrollbar controls
        if (options.scrollbar) {
            let scrollbar = container?.querySelector('.swiper-scrollbar');
            if (!scrollbar) {
                scrollbar = document.createElement('div');
                scrollbar.classList.add('swiper-scrollbar');
                controls.appendChild(scrollbar);
            }

            scrollbar.classList.add(classes.scrollbarClass);

            swiperOptions.scrollbar = {
                el: `.${classes.scrollbarClass}`,
                hide: true,
                draggable: true,
            };
        }

        // observer
        if (options._observer) {
            swiperOptions.observer = !0;
            swiperOptions.observeParents = !0;
            swiperOptions.observeSlideChildren = !0;
        }

        // centeredSlides
        if (options._centered) {
            swiperOptions.centeredSlides = !0;
            swiperOptions.centeredSlidesBounds = !0;
        }

        // marquee
        if (options._marquee) {
            swiperOptions.centeredSlides = !1;
            swiperOptions.autoplay = {
                delay: 1,
                disableOnInteraction: !0,
            };
            swiperOptions.loop = !0;
            swiperOptions.speed = 6000;
            swiperOptions.allowTouchMove = !0;
        }

        // spaceBetween breakpoints
        if (options._gap) {
            swiperOptions.spaceBetween = 20;
            swiperOptions.breakpoints = {
                768: { spaceBetween: 28 },
            };
        }

        // breakpoints
        if (options._breakpoints) {
            swiperOptions.breakpoints = {
                0: options?._mobile || {},
                768: options?._tablet || {},
                1024: options?._desktop || {},
            };
        }

        initializeSwiper(el, `.${classes.swiperClass}`, swiperOptions);
    });
};

document.addEventListener('DOMContentLoaded', initializeSwipers);
