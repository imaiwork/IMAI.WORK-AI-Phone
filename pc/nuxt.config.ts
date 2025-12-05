import { URL, fileURLToPath } from "node:url";
import { createSvgIconsPlugin } from "vite-plugin-svg-icons";
import { getEnvConfig } from "./nuxt/env";
const envConfig = getEnvConfig();
export default defineNuxtConfig({
    srcDir: "src/",
    css: ["@/assets/styles/index.scss"],
    modules: ["@pinia/nuxt", "@nuxtjs/tailwindcss", "@element-plus/nuxt"],
    app: {
        baseURL: envConfig.baseUrl,
    },
    spaLoadingTemplate: "spa-loading.html",
    runtimeConfig: {
        public: {
            ...envConfig,
        },
    },
    ssr: !!envConfig.ssr,

    vite: {
        plugins: [
            createSvgIconsPlugin({
                iconDirs: [fileURLToPath(new URL("./src/assets/icons", import.meta.url))],
                symbolId: "local-icon-[dir]-[name]",
            }),
        ],
    },
});
