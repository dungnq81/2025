import * as path from 'path';
import {viteStaticCopy} from 'vite-plugin-static-copy';
import {sharedConfig} from '../../../../vite.config.shared';

const dir = path.resolve(__dirname).replace(/\\/g, '/');
const resources = `${dir}/resources`;
const assets = `${dir}/assets`;

// COPY
const directoriesToCopy = [
    {src: `${resources}/img`, dest: ''}
];

// SASS
const sassFiles = [
    'login',
    'addon',
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
    build: {
        ...sharedConfig.build,
        outDir: `${assets}`,
        assetsDir: '',
        rollupOptions: {
            input: Object.fromEntries([
                ...sassFiles.map((file) => [`css/${file}`, `${resources}/sass/${file}.scss`]),
                ...jsFiles.map((file) => [`${file}`, `${resources}/js/${file}.js`]),
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
                    if (id.includes('node_modules') || id.includes('js/3rd') || id.includes('sass/3rd')) {
                        return '_vendor';
                    }
                },
            },
        },
    },
};
