import path from "node:path";
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
const isMpWeixin = process.env.UNI_PLATFORM === "mp-weixin";
const isDev = process.env.NODE_ENV !== "production";
// mp 开发 watch 内存压力大：保留分包优化，关闭跨包异步分析与 dts 生成
const lightenMpDev = isMpWeixin && isDev;
const weappTailwindcssDisabled = isH5 || isApp;
const postcssPlugin = [autoprefixer(), tailwindcss()];
// 控制微信开发者工具（envVersion === 'develop'）是否按正式版处理，默认 true 保持原行为
const developAsRelease = process.env.UNI_DEVELOP_AS_RELEASE !== "false";

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
            imports: ["vue", "uni-app", "pinia", { "@/utils/power": ["powerInsufficientTip"] }],
            dts: "./src/auto-imports.d.ts",
            eslintrc: {
                enabled: true,
            },
        }),
        Optimization({
            enable: {
                optimization: true,
                "async-import": !lightenMpDev,
                "async-component": !lightenMpDev,
            },
            dts: !lightenMpDev,
        }),
    ],
    define: {
        __DEVELOP_AS_RELEASE__: JSON.stringify(developAsRelease),
    },
    css: {
        postcss: {
            plugins: postcssPlugin,
        },
    },
    server: {
        port: 8991,
    },
});
