import {nanoid} from 'nanoid';
import Swiper from 'swiper/bundle';

function isEmpty(value) {
    if (value == null) return true;
    if (Array.isArray(value) || typeof value === 'string') return value.length === 0;
    if (typeof value === 'object') return Object.keys(value).length === 0;
    return false;
}

// Initialize Swiper instances
const initializeSwiper = (el, swiper_class, options) => {
    if (!(el instanceof Element)) {
        console.error('Error: The provided element is not a DOM element.');
        return;
    }

    const swiper = new Swiper(swiper_class, options);

    el.addEventListener('mouseover', () => {
        swiper.autoplay.stop();
    });

    el.addEventListener('mouseout', () => {
        if (options.autoplay) {
            swiper.autoplay.start();
        }
    });

    return swiper;
};

// Generate unique class names
const generateClasses = () => {
    const rand = nanoid(9);
    return {
        rand: rand,
        swiperClass: 'swiper-' + rand,
        nextClass: 'next-' + rand,
        prevClass: 'prev-' + rand,
        paginationClass: 'pagination-' + rand,
        scrollbarClass: 'scrollbar-' + rand,
    };
};

// Default Swiper options
const getDefaultOptions = () => ({
    grabCursor: !0,
    allowTouchMove: !0,
    threshold: 5,
    hashNavigation: !1,
    mousewheel: !1,
    wrapperClass: 'swiper-wrapper',
    slideClass: 'swiper-slide',
    slideActiveClass: 'swiper-slide-active',
});

// Utility to generate random integers
const random = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;

//
// swipers single
//
const initializeSwipers = () => {
    const swiperElements = document.querySelectorAll('.w-swiper');

    swiperElements.forEach((el, index) => {
        const classes = generateClasses();
        el.classList.add(classes.swiperClass);

        let controls = el.closest('.swiper-section')?.querySelector('.swiper-controls');
        if (!controls) {
            controls = document.createElement('div');
            controls.classList.add('swiper-controls');
            el.after(controls);
        }

        const swiperWrapper = el?.querySelector('.swiper-wrapper');
        let options = JSON.parse(swiperWrapper.dataset.options) || {};

        if (isEmpty(options)) {
            options = {
                autoview: !0,
                autoplay: !0,
                navigation: !0,
            };
        }

        let swiperOptions = {...getDefaultOptions()};

        if (options.autoview) {
            swiperOptions.slidesPerView = 'auto';
            if (options.gap) {
                swiperOptions.spaceBetween = 20;
                swiperOptions.breakpoints = {
                    768: {spaceBetween: 20},
                };
            }
        } else {
            swiperOptions.breakpoints = {
                0: options.mobile || {},
                768: options.tablet || {},
                1024: options.desktop || {},
            };
        }

        if (options.observer) {
            swiperOptions.observer = !0;
            swiperOptions.observeParents = !0;
        }

        if (options.effect) {
            swiperOptions.effect = String(options.effect);
            if (swiperOptions.effect === 'fade') {
                swiperOptions.fadeEffect = {crossFade: !0};
            }
        }

        if (options.autoheight) swiperOptions.autoHeight = !0;
        if (options.loop) swiperOptions.loop = !0;
        if (options.parallax) swiperOptions.parallax = !0;
        if (options.direction) swiperOptions.direction = String(options.direction);
        if (options.freemode) swiperOptions.freeMode = !0;
        if (options.cssmode) swiperOptions.cssMode = !0;

        if (options.centered) {
            swiperOptions.centeredSlides = !0;
            swiperOptions.centeredSlidesBounds = !0;
        }

        swiperOptions.speed = options.speed ? parseInt(options.speed) : random(300, 900);

        if (options.autoplay) {
            swiperOptions.autoplay = {
                disableOnInteraction: !1,
                delay: options.delay ? parseInt(options.delay) : random(3000, 6000),
            };
            if (options.reverse) swiperOptions.reverseDirection = !0;
        }

        // Navigation
        if (options.navigation) {
            const section = el.closest('.swiper-section');
            let btnPrev = section?.querySelector('.swiper-button-prev');
            let btnNext = section?.querySelector('.swiper-button-next');

            if (btnPrev && btnNext) {
                btnPrev.classList.add(classes.prevClass);
                btnNext.classList.add(classes.nextClass);
            } else {
                btnPrev = document.createElement('div');
                btnNext = document.createElement('div');
                btnPrev.classList.add('swiper-button', 'swiper-button-prev', classes.prevClass);
                btnNext.classList.add('swiper-button', 'swiper-button-next', classes.nextClass);
                controls.append(btnPrev, btnNext);

                btnPrev.setAttribute('data-fa', '');
                btnNext.setAttribute('data-fa', '');
            }

            swiperOptions.navigation = {
                nextEl: '.' + classes.nextClass,
                prevEl: '.' + classes.prevClass,
            };
        }

        // Pagination
        if (options.pagination) {
            const section = el.closest('.swiper-section');
            let pagination = section?.querySelector('.swiper-pagination');
            if (pagination) {
                pagination.classList.add(classes.paginationClass);
            } else {
                pagination = document.createElement('div');
                pagination.classList.add('swiper-pagination', classes.paginationClass);
                controls.appendChild(pagination);
            }

            const paginationType = options.pagination;
            swiperOptions.pagination = {
                el: '.' + classes.paginationClass,
                clickable: !0,
                ...(paginationType === 'bullets' && {dynamicBullets: !0, type: 'bullets'}),
                ...(paginationType === 'fraction' && {type: 'fraction'}),
                ...(paginationType === 'progressbar' && {type: 'progressbar'}),
                ...(paginationType === 'custom' && {
                    renderBullet: (index, className) => `<span class="${className}">${index + 1}</span>`,
                }),
            };
        }

        // Scrollbar
        if (options.scrollbar) {
            const section = el.closest('.swiper-section');
            let scrollbar = section?.querySelector('.swiper-scrollbar');
            if (scrollbar) {
                scrollbar.classList.add(classes.scrollbarClass);
            } else {
                scrollbar = document.createElement('div');
                scrollbar.classList.add('swiper-scrollbar', classes.scrollbarClass);
                controls.appendChild(scrollbar);
            }

            swiperOptions.scrollbar = {
                el: '.' + classes.scrollbarClass,
                hide: !0,
                draggable: !0,
            };
        }

        // Marquee
        if (options.marquee) {
            swiperOptions.centeredSlides = !1;
            swiperOptions.autoplay = {
                delay: 1,
                disableOnInteraction: !0,
            };
            swiperOptions.loop = !0;
            swiperOptions.speed = 6000;
            swiperOptions.allowTouchMove = !0;
        }

        // rows
        if (options.rows) {
            swiperOptions.direction = 'horizontal';
            swiperOptions.loop = !1;
            swiperOptions.grid = {
                rows: parseInt(options.rows),
                fill: 'row',
            };
        }

        initializeSwiper(el, '.' + classes.swiperClass, swiperOptions);
    });
};

//
// Products slides
//
const spgSwipers = () => {
    const swiperElements = document.querySelectorAll('.swiper-product-gallery');

    swiperElements.forEach((el, index) => {
        const classes = generateClasses();
        el.classList.add(classes.swiperClass);

        const w_images = el?.querySelector('.swiper-images');
        const w_thumbs = el?.querySelector('.swiper-thumbs');

        let swiper_images = false;
        let swiper_thumbs = false;

        /** wpg thumbs */
        if (w_thumbs) {
            w_thumbs?.querySelector('.swiper-button-prev').classList.add('prev-thumbs-' + classes.rand);
            w_thumbs?.querySelector('.swiper-button-next').classList.add('next-thumbs-' + classes.rand);
            w_thumbs.classList.add('thumbs-' + classes.rand);

            let thumbs_options = {...getDefaultOptions()};
            thumbs_options.breakpoints = {
                0: {
                    spaceBetween: 5,
                    slidesPerView: 4,
                },
                768: {
                    spaceBetween: 10,
                    slidesPerView: 5,
                },
                1024: {
                    spaceBetween: 10,
                    slidesPerView: 6,
                },
            };

            thumbs_options.navigation = {
                prevEl: '.prev-thumbs-' + classes.rand,
                nextEl: '.next-thumbs-' + classes.rand,
            };

            swiper_thumbs = initializeSwiper(w_thumbs, '.thumbs-' + classes.rand, thumbs_options);
        }

        /** wpg images */
        if (w_images) {
            w_images?.querySelector('.swiper-button-prev').classList.add('prev-images-' + classes.rand);
            w_images?.querySelector('.swiper-button-next').classList.add('next-images-' + classes.rand);
            w_images.classList.add('images-' + classes.rand);

            let images_options = {...getDefaultOptions()};
            images_options.slidesPerView = 'auto';
            images_options.spaceBetween = 10;
            images_options.watchSlidesProgress = !0;

            images_options.navigation = {
                prevEl: '.prev-images-' + classes.rand,
                nextEl: '.next-images-' + classes.rand,
            };

            if (swiper_thumbs) {
                images_options.thumbs = {
                    swiper: swiper_thumbs,
                };
            }

            swiper_images = initializeSwiper(w_images, '.images-' + classes.rand, images_options);
        }

        /** Variation image */
        let firstImage = w_images?.querySelector('.swiper-images-first img');
        firstImage.removeAttribute('srcset');

        let firstImageSrc = firstImage.getAttribute('src');
        let imagePopupSrc = w_images?.querySelector('.swiper-images-first .image-popup');

        /** */
        let firstThumb = false;
        let firstThumbSrc = false;
        let dataLargeImage = false;

        if (swiper_thumbs) {
            firstThumb = w_thumbs?.querySelector('.swiper-thumbs-first img');
            firstThumb.removeAttribute('srcset');

            firstThumbSrc = firstThumb.getAttribute('src');
            dataLargeImage = firstThumb.getAttribute('data-large_image');
        }

        /** WC event */
        const variations_form = jQuery('form.variations_form');
        if (variations_form) {
            variations_form.on('found_variation', function (event, variation) {
                if (variation.image.src) {
                    firstImage.setAttribute('src', variation.image.src);
                    imagePopupSrc.setAttribute('data-src', variation.image.full_src);
                    if (swiper_thumbs) {
                        firstThumb.setAttribute('src', variation.image.gallery_thumbnail_src);
                    }

                    swiper_images.slideTo(0);
                }
            });

            variations_form.on('reset_image', function () {
                firstImage.setAttribute('src', firstImageSrc);
                imagePopupSrc.setAttribute('data-src', dataLargeImage);
                if (swiper_thumbs) {
                    firstThumb.setAttribute('src', firstThumbSrc);
                }

                swiper_images.slideTo(0);
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', initializeSwipers);
document.addEventListener('DOMContentLoaded', spgSwipers);
