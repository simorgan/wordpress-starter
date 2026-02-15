import {defineConfig} from "vite";
import tailwindcss from '@tailwindcss/vite'
import path from "path";
import wp from "vite-plugin-wordpress";
// import phpTwigReloadPlugin from "./vite.page-reloader.js";
import prefixEditorCss from "./vite.cssPrefixer.config.js";

export default defineConfig({
    root: ".",
    build: {
        outDir: "dist",
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, "resources/js/app.js"),
                editor: path.resolve(__dirname, "resources/js/editor.js"),
            },
            output: {
                entryFileNames: "assets/js/[name].[hash].js",
                chunkFileNames: "assets/js/[name].[hash].js",
                assetFileNames: ({name}) =>
                    /\.css$/.test(name ?? "")
                        ? "assets/css/[name].[hash][extname]"
                        : "assets/[name].[hash][extname]",
            },
        },
    },
    plugins: [
        wp({
            watch: ["**/*.php", "**/*.twig"], // CSS handled by HMR
            publicPath: "/wp-content/themes/starter-theme",
        }),
        prefixEditorCss(),
        tailwindcss(),
    ],
    server: {
        port: 5173,
        strictPort: true,
        origin: "http://wordpress-starter.localhost",
        cors: true,
        hmr: {host: "wordpress-starter.localhost", protocol: "http"},
        watch: {usePolling: false, interval: 1000},
    },
});
