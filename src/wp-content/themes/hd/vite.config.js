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

// SASS
const sassFiles = [
    // (components)
    'components/home',
    'components/swiper',
    'components/woocommerce',

    // (entries)
    'editor-style',
    'admin',
    'index',
];

// JS
const jsFiles = [
    // (components)
    'components/home',
    'components/preload-polyfill',
    'components/social-share',
    'components/swiper',
    'components/tabs',
    'components/woocommerce',

    // (entries)
    'admin',
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
                ...sassFiles.map((file) => [`css/${file}`, `${resources}/styles/${file}.scss`]),
                ...jsFiles.map((file) => [`${file}`, `${resources}/scripts/${file}.js`]),
            ]),
            output: {
                entryFileNames: `js/[name].js`,
                chunkFileNames: `js/[name].js`,
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return assetInfo.name.includes('_vendor') ? 'css/_vendor.css' : `[name].css`;
                    }

                    if (assetInfo.name && /\.(woff2?|ttf|otf|eot)$/i.test(assetInfo.name)) {
                        return `fonts/[name].[ext]`;
                    }

                    return `img/[name].[ext]`;
                },
                manualChunks(id) {
                    if (id.includes('node_modules') || id.includes('scripts/3rd') || id.includes('styles/3rd')) {
                        return '_vendor';
                    }
                },
            },
        },
    },
};
