import * as path from 'path';
import { viteStaticCopy } from 'vite-plugin-static-copy';
//import postcssPurgecss from '@fullhuman/postcss-purgecss';
import { sharedConfig } from '../../../../vite.config.shared';

const dir = path.resolve(__dirname).replace(/\\/g, '/');
const resources = `${dir}/resources`;
const assets = `${dir}/assets`;
const node_modules = './node_modules';

// COPY
const directoriesToCopy = [
    { src: `${resources}/img`, dest: '' },
    { src: `${node_modules}/select2/dist/js/select2.full.min.js`, dest: 'js' },
];

// SASS
const sassFiles = [
    'login-css',
    'addon-css',
];

// JS
const jsFiles = [
    'login',
    'lazyload',
    'recaptcha',
    'sorting',
    'addon',
];

export default {
    ...sharedConfig,
    plugins: [
        ...sharedConfig.plugins,
        viteStaticCopy({
            targets: directoriesToCopy,
        }),
    ],
    css: {
        ...sharedConfig.css,
        postcss: {
            ...sharedConfig.css.postcss,
            plugins: [
                ...sharedConfig.css.postcss.plugins,
                // postcssPurgecss({
                //     content: [
                //         './**/*.php',
                //         './**/*.html',
                //         './assets/js/**/*.js',
                //     ],
                //     safelist: {
                //         standard: [ /wp-/, /is-/, /has-/, /align/, /screen-reader/ ],
                //         deep: [ /is-/, /has-/ ],
                //         greedy: [ /^menu-/, /^nav-/, /^wp-/ ],
                //     }
                // }),
            ],
        },
    },
    build: {
        ...sharedConfig.build,
        outDir: `${assets}`,
        assetsDir: '',
        rollupOptions: {
            input: [
                ...sassFiles.map((file) => `${resources}/sass/${file}.scss`),
                ...jsFiles.map((file) => `${resources}/js/${file}.js`),
            ],
            output: {
                entryFileNames: `js/[name].js`,
                chunkFileNames: `js/[name].js`,
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return `css/[name].[ext]`;
                    }
                    if (assetInfo.name && /\.(woff2?|ttf|otf|eot)$/i.test(assetInfo.name)) {
                        return 'fonts/[name].[ext]';
                    }
                    return `img/[name].[ext]`;
                },
                manualChunks(id) {
                    if (id.includes('node_modules') || id.includes('js/3rd') || id.includes('sass/3rd')) {
                        return '_vendor';
                    }
                },
            },
        },
    },
};
