import { n as nanoid, S as Swiper } from "../_vendor.js";
function isEmpty(value) {
  if (value == null) return true;
  if (Array.isArray(value) || typeof value === "string") return value.length === 0;
  if (typeof value === "object") return Object.keys(value).length === 0;
  return false;
}
const initializeSwiper = (el, swiper_class, options) => {
  if (!(el instanceof Element)) {
    console.error("Error: The provided element is not a DOM element.");
    return;
  }
  const swiper = new Swiper(swiper_class, options);
  el.addEventListener("mouseover", () => {
    swiper.autoplay.stop();
  });
  el.addEventListener("mouseout", () => {
    if (options.autoplay) {
      swiper.autoplay.start();
    }
  });
  return swiper;
};
const generateClasses = () => {
  const rand = nanoid(9);
  return {
    rand,
    swiperClass: "swiper-" + rand,
    nextClass: "next-" + rand,
    prevClass: "prev-" + rand,
    paginationClass: "pagination-" + rand,
    scrollbarClass: "scrollbar-" + rand
  };
};
const getDefaultOptions = () => ({
  grabCursor: true,
  allowTouchMove: true,
  threshold: 5,
  hashNavigation: false,
  mousewheel: false,
  wrapperClass: "swiper-wrapper",
  slideClass: "swiper-slide",
  slideActiveClass: "swiper-slide-active"
});
const random = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
const initializeSwipers = () => {
  const swiperElements = document.querySelectorAll(".w-swiper");
  swiperElements.forEach((el, index) => {
    var _a;
    const classes = generateClasses();
    el.classList.add(classes.swiperClass);
    let controls = (_a = el.closest(".swiper-section")) == null ? void 0 : _a.querySelector(".swiper-controls");
    if (!controls) {
      controls = document.createElement("div");
      controls.classList.add("swiper-controls");
      el.after(controls);
    }
    const swiperWrapper = el == null ? void 0 : el.querySelector(".swiper-wrapper");
    let options = JSON.parse(swiperWrapper.dataset.options) || {};
    if (isEmpty(options)) {
      options = {
        autoview: true,
        autoplay: true,
        navigation: true
      };
    }
    let swiperOptions = { ...getDefaultOptions() };
    if (options.autoview) {
      swiperOptions.slidesPerView = "auto";
      if (options.gap) {
        swiperOptions.spaceBetween = 20;
        swiperOptions.breakpoints = {
          768: { spaceBetween: 20 }
        };
      }
    } else {
      swiperOptions.breakpoints = {
        0: options.mobile || {},
        768: options.tablet || {},
        1024: options.desktop || {}
      };
    }
    if (options.observer) {
      swiperOptions.observer = true;
      swiperOptions.observeParents = true;
    }
    if (options.effect) {
      swiperOptions.effect = String(options.effect);
      if (swiperOptions.effect === "fade") {
        swiperOptions.fadeEffect = { crossFade: true };
      }
    }
    if (options.autoheight) swiperOptions.autoHeight = true;
    if (options.loop) swiperOptions.loop = true;
    if (options.parallax) swiperOptions.parallax = true;
    if (options.direction) swiperOptions.direction = String(options.direction);
    if (options.freemode) swiperOptions.freeMode = true;
    if (options.cssmode) swiperOptions.cssMode = true;
    if (options.centered) {
      swiperOptions.centeredSlides = true;
      swiperOptions.centeredSlidesBounds = true;
    }
    swiperOptions.speed = options.speed ? parseInt(options.speed) : random(300, 900);
    if (options.autoplay) {
      swiperOptions.autoplay = {
        disableOnInteraction: false,
        delay: options.delay ? parseInt(options.delay) : random(3e3, 6e3)
      };
      if (options.reverse) swiperOptions.reverseDirection = true;
    }
    if (options.navigation) {
      const section = el.closest(".swiper-section");
      let btnPrev = section == null ? void 0 : section.querySelector(".swiper-button-prev");
      let btnNext = section == null ? void 0 : section.querySelector(".swiper-button-next");
      if (btnPrev && btnNext) {
        btnPrev.classList.add(classes.prevClass);
        btnNext.classList.add(classes.nextClass);
      } else {
        btnPrev = document.createElement("div");
        btnNext = document.createElement("div");
        btnPrev.classList.add("swiper-button", "swiper-button-prev", classes.prevClass);
        btnNext.classList.add("swiper-button", "swiper-button-next", classes.nextClass);
        controls.append(btnPrev, btnNext);
        btnPrev.setAttribute("data-fa", "");
        btnNext.setAttribute("data-fa", "");
      }
      swiperOptions.navigation = {
        nextEl: "." + classes.nextClass,
        prevEl: "." + classes.prevClass
      };
    }
    if (options.pagination) {
      const section = el.closest(".swiper-section");
      let pagination = section == null ? void 0 : section.querySelector(".swiper-pagination");
      if (pagination) {
        pagination.classList.add(classes.paginationClass);
      } else {
        pagination = document.createElement("div");
        pagination.classList.add("swiper-pagination", classes.paginationClass);
        controls.appendChild(pagination);
      }
      const paginationType = options.pagination;
      swiperOptions.pagination = {
        el: "." + classes.paginationClass,
        clickable: true,
        ...paginationType === "bullets" && { dynamicBullets: true, type: "bullets" },
        ...paginationType === "fraction" && { type: "fraction" },
        ...paginationType === "progressbar" && { type: "progressbar" },
        ...paginationType === "custom" && {
          renderBullet: (index2, className) => `<span class="${className}">${index2 + 1}</span>`
        }
      };
    }
    if (options.scrollbar) {
      const section = el.closest(".swiper-section");
      let scrollbar = section == null ? void 0 : section.querySelector(".swiper-scrollbar");
      if (scrollbar) {
        scrollbar.classList.add(classes.scrollbarClass);
      } else {
        scrollbar = document.createElement("div");
        scrollbar.classList.add("swiper-scrollbar", classes.scrollbarClass);
        controls.appendChild(scrollbar);
      }
      swiperOptions.scrollbar = {
        el: "." + classes.scrollbarClass,
        hide: true,
        draggable: true
      };
    }
    if (options.marquee) {
      swiperOptions.centeredSlides = false;
      swiperOptions.autoplay = {
        delay: 1,
        disableOnInteraction: true
      };
      swiperOptions.loop = true;
      swiperOptions.speed = 6e3;
      swiperOptions.allowTouchMove = true;
    }
    if (options.rows) {
      swiperOptions.direction = "horizontal";
      swiperOptions.loop = false;
      swiperOptions.grid = {
        rows: parseInt(options.rows),
        fill: "row"
      };
    }
    initializeSwiper(el, "." + classes.swiperClass, swiperOptions);
  });
};
const spgSwipers = () => {
  const swiperElements = document.querySelectorAll(".swiper-product-gallery");
  swiperElements.forEach((el, index) => {
    const classes = generateClasses();
    el.classList.add(classes.swiperClass);
    const w_images = el == null ? void 0 : el.querySelector(".swiper-images");
    const w_thumbs = el == null ? void 0 : el.querySelector(".swiper-thumbs");
    let swiper_images = false;
    let swiper_thumbs = false;
    if (w_thumbs) {
      w_thumbs == null ? void 0 : w_thumbs.querySelector(".swiper-button-prev").classList.add("prev-thumbs-" + classes.rand);
      w_thumbs == null ? void 0 : w_thumbs.querySelector(".swiper-button-next").classList.add("next-thumbs-" + classes.rand);
      w_thumbs.classList.add("thumbs-" + classes.rand);
      let thumbs_options = { ...getDefaultOptions() };
      thumbs_options.breakpoints = {
        0: {
          spaceBetween: 5,
          slidesPerView: 4
        },
        768: {
          spaceBetween: 10,
          slidesPerView: 5
        },
        1024: {
          spaceBetween: 10,
          slidesPerView: 6
        }
      };
      thumbs_options.navigation = {
        prevEl: ".prev-thumbs-" + classes.rand,
        nextEl: ".next-thumbs-" + classes.rand
      };
      swiper_thumbs = initializeSwiper(w_thumbs, ".thumbs-" + classes.rand, thumbs_options);
    }
    if (w_images) {
      w_images == null ? void 0 : w_images.querySelector(".swiper-button-prev").classList.add("prev-images-" + classes.rand);
      w_images == null ? void 0 : w_images.querySelector(".swiper-button-next").classList.add("next-images-" + classes.rand);
      w_images.classList.add("images-" + classes.rand);
      let images_options = { ...getDefaultOptions() };
      images_options.slidesPerView = "auto";
      images_options.spaceBetween = 10;
      images_options.watchSlidesProgress = true;
      images_options.navigation = {
        prevEl: ".prev-images-" + classes.rand,
        nextEl: ".next-images-" + classes.rand
      };
      if (swiper_thumbs) {
        images_options.thumbs = {
          swiper: swiper_thumbs
        };
      }
      swiper_images = initializeSwiper(w_images, ".images-" + classes.rand, images_options);
    }
    let firstImage = w_images == null ? void 0 : w_images.querySelector(".swiper-images-first img");
    firstImage.removeAttribute("srcset");
    let firstImageSrc = firstImage.getAttribute("src");
    let imagePopupSrc = w_images == null ? void 0 : w_images.querySelector(".swiper-images-first .image-popup");
    let firstThumb = false;
    let firstThumbSrc = false;
    let dataLargeImage = false;
    if (swiper_thumbs) {
      firstThumb = w_thumbs == null ? void 0 : w_thumbs.querySelector(".swiper-thumbs-first img");
      firstThumb.removeAttribute("srcset");
      firstThumbSrc = firstThumb.getAttribute("src");
      dataLargeImage = firstThumb.getAttribute("data-large_image");
    }
    const variations_form = jQuery("form.variations_form");
    if (variations_form) {
      variations_form.on("found_variation", function(event, variation) {
        if (variation.image.src) {
          firstImage.setAttribute("src", variation.image.src);
          imagePopupSrc.setAttribute("data-src", variation.image.full_src);
          if (swiper_thumbs) {
            firstThumb.setAttribute("src", variation.image.gallery_thumbnail_src);
          }
          swiper_images.slideTo(0);
        }
      });
      variations_form.on("reset_image", function() {
        firstImage.setAttribute("src", firstImageSrc);
        imagePopupSrc.setAttribute("data-src", dataLargeImage);
        if (swiper_thumbs) {
          firstThumb.setAttribute("src", firstThumbSrc);
        }
        swiper_images.slideTo(0);
      });
    }
  });
};
document.addEventListener("DOMContentLoaded", initializeSwipers);
document.addEventListener("DOMContentLoaded", spgSwipers);
//# sourceMappingURL=swiper.js.map
