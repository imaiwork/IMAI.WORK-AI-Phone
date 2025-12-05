import { defineConfig } from "vite";
import uni from "@dcloudio/vite-plugin-uni";
import Optimization from "@uni-ku/bundle-optimizer";
import AutoImport from "unplugin-auto-import/vite";
import tailwindcss from "tailwindcss";
import autoprefixer from "autoprefixer";
import { UnifiedViteWeappTailwindcssPlugin as uvwt } from "weapp-tailwindcss/vite";
import uniRouter from "unplugin-uni-router/vite";

const isH5 = process.env.UNI_PLATFORM === "h5";
const isApp = process.env.UNI_PLATFORM === "app";
const weappTailwindcssDisabled = isH5 || isApp;
const postcssPlugin = [autoprefixer(), tailwindcss()];

export default defineConfig({
    plugins: [
        uni(),
        uniRouter({
            includes: ["style"],
        }),
        uvwt({
            rem2rpx: true,
            disabled: weappTailwindcssDisabled,
        }),
        AutoImport({
            imports: ["vue", "uni-app", "pinia"],
            dts: "./src/auto-imports.d.ts",
            eslintrc: {
                enabled: true,
            },
        }),
        Optimization({
            // 插件功能开关，默认为true，即开启所有功能
            enable: {
                optimization: true,
                "async-import": true,
                "async-component": true,
            },
        }),
    ],
    css: {
        postcss: {
            plugins: postcssPlugin,
        },
    },
    server: {
        port: 8991,
    },
});
