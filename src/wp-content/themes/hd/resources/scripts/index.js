import * as utils from './utils/global.js';
import './utils/back-to-top.js';
import './utils/script-loader.js';
import {initMenu} from './utils/menu.js';

import {initSocialShare} from './components/social-share.js';

// Styles
import '../styles/3rd/_tailwind.css';
import '../styles/3rd/_index.scss';

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initMenu('nav.nav', '.main-nav');
    initSocialShare('[data-social-share]', {intents: ['facebook', 'x', 'print', 'send-email', 'copy-link', 'web-share']});
});
