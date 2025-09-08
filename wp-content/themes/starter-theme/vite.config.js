import {defineConfig} from "vite";
import path from "path";
import fullReload from "vite-plugin-full-reload";
import wp from "vite-plugin-wordpress";

// Custom plugin that works in both dev and build
function editorCssPrefix() {
    return {
        name: 'editor-css-prefix',

        // For build mode
        generateBundle(options, bundle) {
            const editorCssAsset = Object.keys(bundle).find(key =>
                key.includes('editor') && key.endsWith('.css')
            );

            if (editorCssAsset && bundle[editorCssAsset]) {
                console.log('Found editor CSS asset:', editorCssAsset);

                const postcss = require('postcss');
                const postcssPrefixSelector = require('postcss-prefix-selector');

                const result = postcss([
                    postcssPrefixSelector({
                        prefix: '.editor-styles-wrapper .acf-block-preview',
                        transform: (prefix, selector, prefixedSelector) => {
                            if (selector.startsWith('html') || selector.startsWith('body')) return prefix;
                            return prefixedSelector;
                        },
                    })
                ]).process(bundle[editorCssAsset].source, {from: undefined});

                bundle[editorCssAsset].source = result.css;
                console.log('Applied prefix to editor CSS (build)');
            }
        },

        // For dev mode
        transform(code, id) {
            // Check if this is CSS and related to editor
            if (id.includes('.css') && (id.includes('editor') || code.includes('/* editor-css */'))) {
                console.log('Processing editor CSS in dev mode:', id);

                const postcss = require('postcss');
                const postcssPrefixSelector = require('postcss-prefix-selector');

                try {
                    const result = postcss([
                        postcssPrefixSelector({
                            prefix: '.editor-styles-wrapper .acf-block-preview',
                            transform: (prefix, selector, prefixedSelector) => {
                                if (selector.startsWith('html') || selector.startsWith('body')) return prefix;
                                return prefixedSelector;
                            },
                        })
                    ]).process(code, {from: id});

                    console.log('Applied prefix to editor CSS (dev)');
                    return result.css;
                } catch (error) {
                    console.error('Error processing editor CSS:', error);
                    return code;
                }
            }
        }
    };
}

export default defineConfig({
    root: ".",
    build: {
        outDir: "dist",
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, "resources/js/app.js"),
                editor: path.resolve(__dirname, "resources/js/editor.js"),
            },
            output: {
                entryFileNames: "assets/js/[name].js",
                chunkFileNames: "assets/js/[name].js",
                assetFileNames: ({name}) =>
                    /\.(css)$/.test(name ?? "") ? "assets/css/[name].[ext]" : "assets/[name].[ext]",
            },
        },
    },
    css: undefined,
    plugins: [
        wp({
            watch: ["**/*.php", "**/*.twig"],
            publicPath: "/wp-content/themes/starter-theme",
        }),
        fullReload(["**/*.php", "views/**/*.twig"]),
        editorCssPrefix(),
    ],
    server: {
        port: 5173,
        strictPort: true,
        origin: "http://wordpress-starter.localhost",
        cors: true,
        hmr: {host: "wordpress-starter.localhost", protocol: "http"},
        watch: {usePolling: true, interval: 100},
    },
});