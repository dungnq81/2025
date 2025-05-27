document.addEventListener("DOMContentLoaded", function() {
  var _a;
  const loginform = document.getElementById("loginform");
  if (loginform) {
    loginform.classList.add("otp-loginform");
    const el = document.querySelector("input[name='pwd']");
    if (el) {
      el.setAttribute("autocomplete", "off");
      el.setAttribute("readonly", true);
      setTimeout(() => el.removeAttribute("readonly"), 500);
    }
    const rememberBox = document.getElementById("rememberme");
    if (rememberBox) {
      rememberBox.checked = false;
      (_a = rememberBox.closest("p")) == null ? void 0 : _a.remove();
    }
  }
  const form = document.querySelector("#loginform"), inputEl = document.querySelector('input.authcode[inputmode="numeric"]'), expectedLength = Number(inputEl == null ? void 0 : inputEl.dataset.digits) || 0;
  if (inputEl) {
    inputEl.addEventListener(
      "input",
      function() {
        let value = this.value.replace(/[^0-9 ]/g, "").trimStart();
        this.value = value;
        if (expectedLength && value.replace(/ /g, "").length === expectedLength) {
          if (void 0 !== form.requestSubmit) {
            form.requestSubmit();
            form.submit.disabled = "disabled";
          }
        }
      }
    );
  }
});
//# sourceMappingURL=login-otp.js.map
