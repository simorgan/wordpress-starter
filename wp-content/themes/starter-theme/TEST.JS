import {defineConfig} from "vite";
import path from "path";
import fullReload from "vite-plugin-full-reload";
import wp from "vite-plugin-wordpress";
import postcssImport from "postcss-import";
import tailwindNesting from "tailwindcss/nesting";
import tailwindcss from "tailwindcss";
import autoprefixer from "autoprefixer";
import postcssCachebuster from "postcss-cachebuster";
import postcssPrefixSelector from "postcss-prefix-selector";

export default defineConfig({
    root: ".", // theme root
    build: {
        outDir: "dist",
        emptyOutDir: true,
        rollupOptions: {
            input: {

                app: path.resolve(__dirname, "resources/js/app.js"),
                appCss: path.resolve(__dirname, "resources/css/app.css"),
                gutenberg: path.resolve(__dirname, "resources/css/gutenberg-style.css"),
                editor: path.resolve(__dirname, "resources/css/editor-style.css"),
            },
            output: {
                entryFileNames: "assets/js/[name].js",
                chunkFileNames: "assets/js/[name].js",
                assetFileNames: ({name}) => {
                    if (/\.(css)$/.test(name ?? "")) {
                        return "assets/css/[name].[ext]";
                    }
                    return "assets/[name].[ext]";
                },
            },
        },
    },
    css: {
        postcss: {
            plugins: [
                postcssImport(),
                tailwindNesting(),
                tailwindcss(),
                postcssCachebuster({
                    imagesPath: "/../../../",
                    cssPath: "/../../../",
                }),
                autoprefixer(),
                // Prefix Gutenberg CSS
                postcssPrefixSelector({
                    prefix: ".editor-styles-wrapper",
                    exclude: ["body.yp-frontend", ".wp-block.acf-block-preview"],
                }),
                // Prefix Editor CSS (separate entry, Vite handles splitting)
                postcssPrefixSelector({
                    prefix: ".editor-styles-wrapper .acf-block-preview",
                    exclude: ["body.yp-frontend", ".wp-block.acf-block-preview"],
                }),
            ],
        },
    },
    plugins: [
        wp({
            watch: ["**/*.php", "**/*.twig"], // Watch theme PHP/Twig
            publicPath: "/wp-content/themes/starter-theme",
        }),
        fullReload(["**/*.php", "views/**/*.twig"]),
    ],
    server: {
        port: 5173,
        strictPort: true,
        origin: "http://wordpress-starter.localhost", // WordPress site URL
        cors: true,
        hmr: {
            host: "wordpress-starter.localhost", // match dev site hostname
            protocol: "http",
        },
        watch: {
            usePolling: true,
            interval: 100,
        },
    },
});
