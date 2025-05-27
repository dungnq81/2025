document.addEventListener('DOMContentLoaded', function () {
    const loginform = document.getElementById('loginform');
    if (loginform) {
        loginform.classList.add('otp-loginform');

        const el = document.querySelector("input[name='pwd']");
        if (el) {
            el.setAttribute("autocomplete", "off");
            el.setAttribute("readonly", true);
            setTimeout(() => el.removeAttribute("readonly"), 500);
        }

        const rememberBox = document.getElementById("rememberme");
        if (rememberBox) {
            rememberBox.checked = false;
            rememberBox.closest("p")?.remove();
        }
    }

    // Enforce numeric-only input for numeric inputmode elements.
    const form = document.querySelector('#loginform'),
        inputEl = document.querySelector('input.authcode[inputmode="numeric"]'),
        expectedLength = Number(inputEl?.dataset.digits) || 0;

    if (inputEl) {
        inputEl.addEventListener('input', function () {
                let value = this.value.replace(/[^0-9 ]/g, '').trimStart();
                this.value = value;

                // Auto-submit if it's the expected length.
                if (expectedLength && value.replace(/ /g, '').length === expectedLength) {
                    if (undefined !== form.requestSubmit) {
                        form.requestSubmit();
                        form.submit.disabled = "disabled";
                    }
                }
            }
        );
    }
});
