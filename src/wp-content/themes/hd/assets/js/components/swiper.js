import { n as nanoid, S as Swiper } from "../_vendor.js";
const initializeSwiper = (el, swiperClass, options) => {
  if (!(el instanceof Element)) {
    console.error("Error: The provided element is not a valid DOM element.");
    return;
  }
  if (el.classList.contains("swiper-initialized") || el.dataset.swiperInitialized) return;
  el.dataset.swiperInitialized = "true";
  const swiper = new Swiper(swiperClass, options);
  el.addEventListener("mouseover", () => {
    var _a;
    return (_a = swiper.autoplay) == null ? void 0 : _a.stop();
  });
  el.addEventListener("mouseout", () => {
    var _a;
    return options.autoplay && ((_a = swiper.autoplay) == null ? void 0 : _a.start());
  });
  return swiper;
};
const generateClasses = () => {
  const rand = nanoid(10);
  return {
    rand,
    swiperClass: `swiper-${rand}`,
    nextClass: `next-${rand}`,
    prevClass: `prev-${rand}`,
    paginationClass: `pagination-${rand}`,
    scrollbarClass: `scrollbar-${rand}`
  };
};
const getDefaultOptions = () => ({
  grabCursor: true,
  allowTouchMove: true,
  threshold: 5,
  autoHeight: false,
  loop: false,
  hashNavigation: false,
  direction: "horizontal",
  freeMode: false,
  cssMode: false,
  centeredSlides: false,
  slidesPerView: "auto"
});
const parseOptions = (el) => {
  try {
    return JSON.parse(el.dataset.options) || {};
  } catch (e) {
    console.error("Invalid JSON in data-options", e);
    return {};
  }
};
const initializeSwipers = () => {
  const swiperElements = document.querySelectorAll(".w-swiper");
  swiperElements.forEach((el) => {
    var _a, _b, _c, _d, _e;
    if (el.classList.contains("swiper-initialized")) return;
    const classes = generateClasses();
    el.classList.add(classes.swiperClass);
    const container = el.closest(".swiper-container");
    let controls = container == null ? void 0 : container.querySelector(".swiper-controls");
    if (!controls) {
      controls = document.createElement("div");
      controls.classList.add("swiper-controls");
      el.after(controls);
    }
    let options = parseOptions(el);
    let swiperOptions = { ...getDefaultOptions() };
    [
      "autoHeight",
      "loop",
      "freeMode",
      "cssMode",
      "mousewheel",
      "parallax",
      "hashNavigation"
    ].forEach((key) => options[key] && (swiperOptions[key] = true));
    swiperOptions.wrapperClass = String(options.wrapperClass || "swiper-wrapper");
    swiperOptions.slideClass = String(options.slideClass || "swiper-slide");
    swiperOptions.slideActiveClass = String(options.slideActiveClass || "swiper-slide-active");
    swiperOptions.direction = String(options.direction || "horizontal");
    swiperOptions.slidesPerView = options.slidesPerView || "auto";
    swiperOptions.spaceBetween = parseInt(options.spaceBetween, 10) || 0;
    swiperOptions.speed = parseInt(options.speed, 10) || 300;
    if (options.grid) {
      swiperOptions.grid = {
        rows: Math.max(parseInt((_a = options.grid) == null ? void 0 : _a.rows) || 1, 1),
        fill: ((_b = options.grid) == null ? void 0 : _b.fill) || "row"
      };
      if (options.loop) swiperOptions.loopAddBlankSlides = true;
    }
    if (options.autoplay) {
      swiperOptions.autoplay = {
        delay: parseInt((_c = options.autoplay) == null ? void 0 : _c.delay) || 3e3,
        disableOnInteraction: ((_d = options.autoplay) == null ? void 0 : _d.disableOnInteraction) || true,
        reverseDirection: ((_e = options.autoplay) == null ? void 0 : _e.reverseDirection) || false
      };
    }
    if (options.navigation) {
      let btnPrev = container == null ? void 0 : container.querySelector(".swiper-button-prev");
      let btnNext = container == null ? void 0 : container.querySelector(".swiper-button-next");
      if (!btnPrev) {
        btnPrev = document.createElement("div");
        btnPrev.classList.add("swiper-button", "swiper-button-prev");
        btnPrev.setAttribute("data-fa", "");
        controls.append(btnPrev);
      }
      if (!btnNext) {
        btnNext = document.createElement("div");
        btnNext.classList.add("swiper-button", "swiper-button-next");
        btnNext.setAttribute("data-fa", "");
        controls.append(btnNext);
      }
      btnPrev.classList.add(classes.prevClass);
      btnNext.classList.add(classes.nextClass);
      swiperOptions.navigation = {
        nextEl: `.${classes.nextClass}`,
        prevEl: `.${classes.prevClass}`
      };
    }
    if (options.pagination) {
      let pagination = container == null ? void 0 : container.querySelector(".swiper-pagination");
      if (!pagination) {
        pagination = document.createElement("div");
        pagination.classList.add("swiper-pagination");
        controls.appendChild(pagination);
      }
      pagination.classList.add(classes.paginationClass);
      const paginationType = String(options.pagination);
      swiperOptions.pagination = {
        el: `.${classes.paginationClass}`,
        clickable: true,
        ...paginationType === "bullets" && { dynamicBullets: true, type: "bullets" },
        ...paginationType === "fraction" && { type: "fraction" },
        ...paginationType === "progressbar" && { type: "progressbar" },
        ...paginationType === "custom" && {
          renderBullet: (index, className) => `<span class="${className}">${index + 1}</span>`
        }
      };
    }
    if (options.scrollbar) {
      let scrollbar = container == null ? void 0 : container.querySelector(".swiper-scrollbar");
      if (!scrollbar) {
        scrollbar = document.createElement("div");
        scrollbar.classList.add("swiper-scrollbar");
        controls.appendChild(scrollbar);
      }
      scrollbar.classList.add(classes.scrollbarClass);
      swiperOptions.scrollbar = {
        el: `.${classes.scrollbarClass}`,
        hide: true,
        draggable: true
      };
    }
    if (options._observer) {
      swiperOptions.observer = true;
      swiperOptions.observeParents = true;
      swiperOptions.observeSlideChildren = true;
    }
    if (options._centered) {
      swiperOptions.centeredSlides = true;
      swiperOptions.centeredSlidesBounds = true;
    }
    if (options._marquee) {
      swiperOptions.centeredSlides = false;
      swiperOptions.autoplay = {
        delay: 1,
        disableOnInteraction: true
      };
      swiperOptions.loop = true;
      swiperOptions.speed = 6e3;
      swiperOptions.allowTouchMove = true;
    }
    if (options._gap) {
      swiperOptions.spaceBetween = 20;
      swiperOptions.breakpoints = {
        768: { spaceBetween: 28 }
      };
    }
    if (options._breakpoints) {
      swiperOptions.breakpoints = {
        0: (options == null ? void 0 : options._mobile) || {},
        768: (options == null ? void 0 : options._tablet) || {},
        1024: (options == null ? void 0 : options._desktop) || {}
      };
    }
    initializeSwiper(el, `.${classes.swiperClass}`, swiperOptions);
  });
};
document.addEventListener("DOMContentLoaded", initializeSwipers);
//# sourceMappingURL=swiper.js.map
