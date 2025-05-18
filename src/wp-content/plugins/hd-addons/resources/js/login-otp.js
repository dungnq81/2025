document.addEventListener('DOMContentLoaded', function () {
    const countdownEl = document.getElementById('countdown');
    const resendBtn = document.getElementById('resendBtn');
    let seconds = 300;

    const interval = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            resendBtn.disabled = false;
            clearInterval(interval);
        }
    }, 1000);
});
