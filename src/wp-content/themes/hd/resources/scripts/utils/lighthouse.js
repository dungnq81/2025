// lighthouse.js (IIFE)

(async () => {
    const DETECTION_CLASS = 'is-lighthouse';

    const indicators = {
        ua: false,
        backend: false
    };

    // UA
    indicators.ua = (
        navigator.userAgent.includes('Lighthouse') ||
        navigator.userAgent.includes('HeadlessChrome') ||
        navigator.webdriver === true
    );

    if (indicators.ua) {
        document.documentElement.classList.add(DETECTION_CLASS);
        return;
    }

    // ajax
    if (typeof window.hdConfig !== 'undefined') {
        try {
            const res = await fetch(window.hdConfig.restApiUrl + 'global/lighthouse', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': window.hdConfig.restToken
                }
            });
            const json = await res.json();
            indicators.backend = json.success && json.detected;
        } catch (err) {}

        if (indicators.backend) {
            document.documentElement.classList.add(DETECTION_CLASS);
        }
    }
})();
