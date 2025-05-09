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

    // (entries)
    'editor-style',
    'admin',
    'index',
];

// JS
const jsFiles = [
    // (components)

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
                    const name = assetInfo.name || '';

                    if (name.endsWith('.css')) {
                        return name.includes('_vendor') ? 'css/_vendor.css' : `[name].css`;
                    }

                    if (/\.(woff2?|ttf|otf|eot)$/i.test(name)) {
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
