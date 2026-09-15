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
// mp 开发 watch 内存压力大：关闭跨包异步分析与 dts 生成
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
                // 分包优化会把「只被某个分包用到」的 node_modules 依赖挪进该分包的 common/vendor.js。
                // 注意：它按依赖的引用方判断归属，主包模块（src/hooks、src/utils 等）里 import 的
                // 三方库同样会被挪走，主包 require 分包文件在小程序里必然报 module is not defined。
                // 所以主包共享模块不要引入「只有单个分包会用」的三方库，该沉到分包的 hook 就沉下去。
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
