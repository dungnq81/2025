// script-loader.js

const scriptLoader = (timeout = 3000, scriptSelector = 'script[data-type="lazy"]') => {
    const userInteractionEvents = [ 'mouseover', 'keydown', 'touchstart', 'touchmove', 'wheel' ];
    const loadScriptsTimer = setTimeout(loadScripts, timeout);

    // Attach event listeners to trigger script loading on user interaction
    userInteractionEvents.forEach((event) => {
        window.addEventListener(event, triggerScriptLoader, { once: true, passive: true });
    });

    function triggerScriptLoader() {
        loadScripts();
        clearTimeout(loadScriptsTimer); // Clear timeout if triggered by interaction
    }

    function loadScripts() {
        document.querySelectorAll(scriptSelector).forEach((elem) => {
            const dataSrc = elem.getAttribute('data-src');
            if (dataSrc) {
                elem.setAttribute('src', dataSrc);
                elem.removeAttribute('data-src');
                elem.removeAttribute('data-type');
            }
        });
    }
};

scriptLoader();

export default scriptLoader;
