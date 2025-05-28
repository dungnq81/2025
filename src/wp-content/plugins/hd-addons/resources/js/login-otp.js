document.addEventListener('DOMContentLoaded', function () {
    const loginform = document.querySelector('#loginform');
    if (loginform) {
        loginform.classList.add('otp-loginform');

        const el = document.querySelector("input[name='pwd']");
        if (el) {
            el.setAttribute("autocomplete", "off");
            el.setAttribute("readonly", true);
            setTimeout(() => el.removeAttribute("readonly"), 500);
        }

        const rememberBox = document.querySelector("#rememberme");
        if (rememberBox) {
            rememberBox.checked = false;
            rememberBox.closest("p")?.remove();
        }
    }

    // Enforce numeric-only input for numeric inputmode elements.
    const inputEl = document.querySelector('input.authcode[inputmode="numeric"]');
    const expectedLength = Number(inputEl?.dataset.digits) || 0;

    if (inputEl) {
        inputEl?.addEventListener('input', function () {
                let value = this.value.replace(/[^0-9 ]/g, '').trimStart();
                this.value = value;

                // Auto-submit if it's the expected length.
                if (expectedLength && value.replace(/ /g, '').length === expectedLength && !loginform.submit.disabled) {
                    if (undefined !== loginform.requestSubmit) {
                        loginform.requestSubmit();
                        loginform.submit.disabled = true;
                    }
                }
            }
        );
    }

    // Countdown
    const timer = document.querySelector('#countdown');
    if (!timer) return;

    let remaining = Number(timer.dataset.time) || 0;
    const render = () => {
        const mm = String(Math.floor(remaining / 60)).padStart(2, '0');
        const ss = String(remaining % 60).padStart(2, '0');
        timer.textContent = `${mm}:${ss}`;
    };

    const start = () => {
        if (remaining <= 0) {
            render();
            loginform.submit.disabled = true;
            return;
        }
        render();

        const id = setInterval(() => {
            remaining--;
            render();
            if (remaining <= 0) {
                clearInterval(id);
                loginform.submit.disabled = true;
            }
        }, 1000);
    };

    start();
});
