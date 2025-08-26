import { defineConfig } from "vite";
import path from "path";
import fullReload from 'vite-plugin-full-reload';
import wp from "vite-plugin-wordpress";

export default defineConfig({
    root: ".", // theme root
    build: {
        outDir: "dist",
        emptyOutDir: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, "resources/js/app.js"),
            },
            output: {
                entryFileNames: "assets/js/[name].js",
                chunkFileNames: "assets/js/[name].js",
                assetFileNames: ({ name }) => {
                    if (/\.(css)$/.test(name ?? "")) {
                        return "assets/css/[name].[ext]";
                    }
                    return "assets/[name].[ext]";
                },
            },
        },
    },
    plugins: [
        wp({
            watch: ["**/*.php"], // Watch all PHP files in the theme
            publicPath: "/wp-content/themes/starter-theme", // adjust to your theme folder
        }),
        fullReload(
            [
                '**/*.php',
                'views/**/*.twig',

            ]
        ),
    ],
    server: {
        port: 5173,
        strictPort: true,
        origin: "http://wordpress-starter.localhost", // your WordPress site URL
        cors: true,
        hmr: {
            host: "wordpress-starter.localhost", // match your dev site hostname
            protocol: "http",
        },
        watch: {
            // Ensures Vite picks up PHP changes reliably
            usePolling: true,
            interval: 100,
        },
    },
});
