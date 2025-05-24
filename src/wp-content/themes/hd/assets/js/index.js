import { i as initSocialShare } from "./components/social-share.js";
import "./_vendor.js";
(function() {
  document.querySelectorAll('a._blank, a.blank, a[target="_blank"]').forEach((el) => {
    if (!el.hasAttribute("target") || el.getAttribute("target") !== "_blank") {
      el.setAttribute("target", "_blank");
    }
    const relValue = el == null ? void 0 : el.getAttribute("rel");
    if (!relValue || !relValue.includes("noopener") || !relValue.includes("nofollow")) {
      const newRelValue = (relValue ? relValue + " " : "") + "noopener noreferrer nofollow";
      el.setAttribute("rel", newRelValue);
    }
  });
  const observer = new MutationObserver(() => {
    document.querySelectorAll('ul.sub-menu[role="menubar"]').forEach((menu) => {
      menu.setAttribute("role", "menu");
    });
    document.querySelectorAll('[aria-hidden="true"] a, [aria-hidden="true"] button').forEach((el) => {
      el.setAttribute("tabindex", "-1");
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();
class BackToTop {
  constructor(selector = ".back-to-top", smoothScrollEnabled = true, defaultScrollSpeed = 400) {
    this.buttonSelector = selector;
    this.smoothScrollEnabled = smoothScrollEnabled;
    this.defaultScrollSpeed = defaultScrollSpeed;
    this.init();
  }
  init() {
    if (!("querySelector" in document && "addEventListener" in window)) {
      return;
    }
    this.goTopBtn = document.querySelector(this.buttonSelector);
    if (!this.goTopBtn) {
      return;
    }
    this.scrollThreshold = parseInt(this.goTopBtn.getAttribute("data-scroll-start"), 10) || 300;
    window.addEventListener("scroll", this.trackScroll.bind(this));
    this.goTopBtn.addEventListener("click", this.scrollToTop.bind(this), false);
  }
  trackScroll() {
    const scrolled = window.scrollY;
    if (scrolled > this.scrollThreshold) {
      this.goTopBtn.classList.add("back-to-top__show");
    } else {
      this.goTopBtn.classList.remove("back-to-top__show");
    }
  }
  scrollToTop(event) {
    event.preventDefault();
    if (this.smoothScrollEnabled) {
      const duration = parseInt(this.goTopBtn.getAttribute("data-scroll-speed"), 10) || this.defaultScrollSpeed;
      this.smoothScroll(duration);
    } else {
      window.scrollTo(0, 0);
    }
  }
  smoothScroll(duration) {
    const startLocation = window.scrollY;
    const distance = -startLocation;
    let startTime = null;
    const animateScroll = (currentTime) => {
      if (!startTime) startTime = currentTime;
      const timeElapsed = currentTime - startTime;
      const run = this.easeInOutQuad(timeElapsed, startLocation, distance, duration);
      window.scrollTo(0, run);
      if (timeElapsed < duration) {
        requestAnimationFrame(animateScroll);
      }
    };
    requestAnimationFrame(animateScroll);
  }
  easeInOutQuad(t, b, c, d) {
    t /= d / 2;
    if (t < 1) return c / 2 * t * t + b;
    t--;
    return -c / 2 * (t * (t - 2) - 1) + b;
  }
}
setTimeout(() => {
  new BackToTop();
}, 100);
const scriptLoader = (timeout = 3e3, scriptSelector = 'script[data-type="lazy"]') => {
  const userInteractionEvents = ["mouseover", "keydown", "touchstart", "touchmove", "wheel"];
  const loadScriptsTimer = setTimeout(loadScripts, timeout);
  userInteractionEvents.forEach((event) => {
    window.addEventListener(event, triggerScriptLoader, { once: true, passive: true });
  });
  function triggerScriptLoader() {
    loadScripts();
    clearTimeout(loadScriptsTimer);
  }
  function loadScripts() {
    document.querySelectorAll(scriptSelector).forEach((elem) => {
      const dataSrc = elem.getAttribute("data-src");
      if (dataSrc) {
        elem.setAttribute("src", dataSrc);
        elem.removeAttribute("data-src");
        elem.removeAttribute("data-type");
      }
    });
  }
};
scriptLoader();
function initMenu(containerSelector, menuSelector) {
  const container = document.querySelector(containerSelector);
  const menu = document.querySelector(menuSelector);
  if (!container || !menu) return;
  function adjustMenu() {
    let more = menu.querySelector(".more");
    if (!more) {
      more = document.createElement("li");
      more.classList.add("menu-item", "more");
      more.innerHTML = '<a href="#"></a><ul class="sub-menu dropdown"></ul>';
      menu.appendChild(more);
    }
    const dropdown = more.querySelector(".dropdown");
    dropdown.innerHTML = "";
    more.style.display = "none";
    let items = [...menu.children].filter((li) => li !== more);
    items.forEach((li) => li.style.display = "block");
    if (menu.scrollWidth <= container.clientWidth) {
      removeOverflowHidden();
      return;
    }
    let hiddenItems = [];
    for (let i = items.length - 1; i >= 0; i--) {
      if (menu.scrollWidth > container.clientWidth) {
        hiddenItems.unshift(items[i]);
        items[i].style.display = "none";
      } else {
        break;
      }
    }
    if (hiddenItems.length > 0) {
      hiddenItems.forEach((item) => {
        let clone = item.cloneNode(true);
        clone.style.display = "block";
        dropdown.appendChild(clone);
      });
      more.style.display = "block";
    }
    removeOverflowHidden();
  }
  function setOverflowHidden() {
    container.style.overflow = "hidden";
  }
  function removeOverflowHidden() {
    container.style.overflow = "visible";
  }
  function reinitializeFoundationDropdown() {
    if (typeof Foundation !== "undefined") {
      let mainNav = $(menuSelector);
      if (mainNav.length) {
        new Foundation.DropdownMenu(mainNav);
      }
    }
  }
  let resizeTimeout;
  window.addEventListener("resize", function() {
    setOverflowHidden();
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      adjustMenu();
      reinitializeFoundationDropdown();
    }, 100);
  });
  adjustMenu();
}
document.addEventListener("DOMContentLoaded", () => {
  initMenu("nav.nav", ".main-nav");
  initSocialShare("[data-social-share]", { intents: ["facebook", "x", "print", "send-email", "copy-link", "web-share"] });
});
//# sourceMappingURL=index.js.map
