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
            rememberBox.closest("p").remove();
        }
    }
});
