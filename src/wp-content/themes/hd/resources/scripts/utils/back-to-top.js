// back-to-top.js

class BackToTop {
    constructor(selector = '.back-to-top', smoothScrollEnabled = true, defaultScrollSpeed = 400) {
        this.buttonSelector = selector;
        this.smoothScrollEnabled = smoothScrollEnabled;
        this.defaultScrollSpeed = defaultScrollSpeed;
        this.init();
    }

    init() {
        if (!('querySelector' in document && 'addEventListener' in window)) {
            return;
        }

        this.goTopBtn = document.querySelector(this.buttonSelector);
        if (!this.goTopBtn) {
            return;
        }

        this.scrollThreshold = parseInt(this.goTopBtn.getAttribute('data-scroll-start'), 10) || 300;

        // Add event listeners
        window.addEventListener('scroll', this.trackScroll.bind(this));
        this.goTopBtn.addEventListener('click', this.scrollToTop.bind(this), false);
    }

    trackScroll() {
        const scrolled = window.scrollY;

        if (scrolled > this.scrollThreshold) {
            this.goTopBtn.classList.add('back-to-top__show');
        } else {
            this.goTopBtn.classList.remove('back-to-top__show');
        }
    }

    scrollToTop(event) {
        event.preventDefault();

        if (this.smoothScrollEnabled) {
            const duration = parseInt(this.goTopBtn.getAttribute('data-scroll-speed'), 10) || this.defaultScrollSpeed;
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
        if (t < 1) return (c / 2) * t * t + b;
        t--;
        return (-c / 2) * (t * (t - 2) - 1) + b;
    }
}

setTimeout(() => {
    new BackToTop();
}, 100);

export default BackToTop;
