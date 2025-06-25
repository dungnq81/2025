import * as path from 'path';
import {viteStaticCopy} from 'vite-plugin-static-copy';
import {sharedConfig} from '../../../../vite.config.shared';

// THEME
const dir = path.resolve(__dirname).replace(/\\/g, '/');
const resources = `${dir}/resources`;
const assets = `${dir}/assets`;

// COPY
const directoriesToCopy = [
    {src: `${resources}/img`, dest: ''},
];

// CSS
const cssFiles = [
    // (partials)
    'partials/admin',
    'partials/editor-style',
    'partials/home',
    'partials/layout',
    'partials/woocommerce',
];

// JS
const jsFiles = [
    // (partials)
    'partials/admin',
    'partials/home',
    'partials/preload-polyfill',
    'partials/swiper',
    'partials/woocommerce',

    // (entries)
    'index',
];

export default {
    ...sharedConfig,
    plugins: [
        ...sharedConfig.plugins,
        viteStaticCopy({
            targets: directoriesToCopy,
        }),
    ],
    build: {
        ...sharedConfig.build,
        outDir: `${assets}`,
        assetsDir: '',
        rollupOptions: {
            input: Object.fromEntries([
                ...cssFiles.map((file) => [`css/${file}`, `${resources}/styles/${file}.scss`]),
                ...jsFiles.map((file) => [`${file}`, `${resources}/scripts/${file}.js`]),
            ]),
            output: {
                entryFileNames: `js/[name].js`,
                chunkFileNames: `js/[name].js`,
                manualChunks(id) {
                    if (id.includes('node_modules') || id.includes('scripts/3rd') || id.includes('styles/3rd')) {
                        return '_vendor';
                    }
                },
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';

                    if (name.endsWith('.css')) {
                        const cssMap = {
                            _vendor: 'css/_vendor.css',
                            index: 'css/index.css',
                        };

                        const matched = Object.keys(cssMap).find(key => name.includes(key));
                        if (matched) return cssMap[matched];

                        return `[name].css`;
                    }

                    if (/\.(woff2?|ttf|otf|eot)$/i.test(name)) {
                        return 'fonts/[name][extname]';
                    }

                    return `img/[name].[ext]`;
                },
            },
        },
    },
};
