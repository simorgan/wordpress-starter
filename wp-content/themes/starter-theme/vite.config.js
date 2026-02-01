import {defineConfig} from "vite";
import path from "path";
import wp from "vite-plugin-wordpress";
import prefixEditorCss from "./cssPrefixer.config.js";

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
    plugins: [
        wp({
            watch: ["**/*.php", "**/*.twig"], // CSS handled by HMR
            publicPath: "/wp-content/themes/starter-theme",
        }),
        prefixEditorCss(),
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
