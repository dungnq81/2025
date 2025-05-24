import { a as SocialShare } from "../_vendor.js";
const DEFAULT_OPTIONS = {
  layout: "h",
  intents: [
    "facebook",
    "x",
    "linkedin",
    "threads",
    "bluesky",
    "reddit",
    "mastodon",
    "quora",
    "whatsapp",
    "messenger",
    "telegram",
    "skype",
    "viber",
    "line",
    "snapchat",
    "send-email",
    "copy-link",
    "web-share",
    "print"
  ],
  onIntent: (self, event, intent, data) => {
    return intent === "print" && setTimeout(window.print, 200);
  }
};
function initSocialShare(element, customOptions = {}) {
  const ele = document.querySelector(element);
  if (!ele) return;
  const layout = customOptions.layout || ele.dataset.layout || DEFAULT_OPTIONS.layout;
  const options = {
    ...DEFAULT_OPTIONS,
    ...customOptions,
    layout
  };
  new SocialShare(ele, options);
  if (options.intents.includes("print")) {
    observePrintButton();
  }
}
function observePrintButton() {
  const buttons = [
    { selector: ".share-intent-print", title: "Print" }
  ];
  const observer = new MutationObserver(() => {
    buttons.forEach(({ selector, title }) => {
      const button = document.querySelector(selector);
      if (button && (!button.title || button.title === "undefined")) {
        button.setAttribute("title", title);
      }
    });
    if (buttons.every(({ selector }) => {
      var _a;
      return (_a = document.querySelector(selector)) == null ? void 0 : _a.title;
    })) {
      observer.disconnect();
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });
}
export {
  initSocialShare as i
};
//# sourceMappingURL=social-share.js.map
