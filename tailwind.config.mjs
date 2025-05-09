/** @type {import('tailwindcss').Config} */

import {addDynamicIconSelectors} from '@iconify/tailwind';

export default {
    content: ['./src/**/*.{html,js,php,scss,json}'],
    darkMode: 'selector',
    theme: {
        extend: {},
        // container: {
        //   // you can configure the container to be centered
        //   center: true,
        //   padding: '1rem',
        // },
    },
    plugins: [addDynamicIconSelectors()],
};
