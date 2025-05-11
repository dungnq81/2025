// lighthouse.js

(async function detectLighthouse() {
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

    // BE
    if (typeof window.hdConfig !== 'undefined') {
        try {
            const res = await fetch(window.hdConfig._restApiUrl + 'global/lighthouse', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': window.hdConfig._restToken
                }
            });
            const json = await res.json();
            indicators.backend = json.success && json.detected;
        } catch (err) {}
    }

    // Apply detection
    const applyDetection = () => {
        const anyDetected = Object.values(indicators).some(Boolean);
        if (anyDetected) {
            document.documentElement.classList.add(DETECTION_CLASS);
        }
    };

    // Apply detection
    applyDetection();
})();
