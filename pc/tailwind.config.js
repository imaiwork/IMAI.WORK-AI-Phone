/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./components/**/*.{vue,js}", "./layouts/**/*.vue", "./pages/**/*.vue", "./plugins/**/*.{js,ts}"],
    theme: {
        // ↓↓↓ 保持覆盖：避免现有 text-3xl..9xl 字号被 TW 默认（rem 制）替换
        fontSize: {
            xs: "var(--el-font-size-extra-small)",
            sm: "var(--el-font-size-small)",
            base: "var(--el-font-size-base)",
            lg: "var(--el-font-size-medium)",
            xl: "var(--el-font-size-large)",
            "2xl": "var(--el-font-size-extra-large)",
            "3xl": "20px",
            "4xl": "24px",
            "5xl": "28px",
            "6xl": "30px",
            "7xl": "36px",
            "8xl": "48px",
            "9xl": "60px",
        },
        // ↓↓↓ 保持覆盖：项目里有 82 处用 shadow-sm/md/lg/xl/2xl/inner，避免它们被 TW 默认值意外激活
        boxShadow: {
            DEFAULT: "var(--el-box-shadow)",
            light: "var(--el-box-shadow-light)",
            lighter: "var(--el-box-shadow-lighter)",
            dark: "var(--el-box-shadow-dark)",
        },
        extend: {
            colors: {
                // ===== 项目语义层（Element Plus 桥接 + 主题色，必须自己定义）=====
                primary: {
                    DEFAULT: "var(--el-color-primary)",
                    "light-1": "var(--el-color-primary-light-1)",
                    "light-2": "var(--el-color-primary-light-2)",
                    "light-3": "var(--el-color-primary-light-3)",
                    "light-4": "var(--el-color-primary-light-4)",
                    "light-5": "var(--el-color-primary-light-5)",
                    "light-6": "var(--el-color-primary-light-6)",
                    "light-7": "var(--el-color-primary-light-7)",
                    "light-8": "var(--el-color-primary-light-8)",
                    "light-9": "var(--el-color-primary-light-9)",
                    "dark-2": "var(--el-color-primary-dark-2)",
                },
                success: "var(--el-color-success)",
                warning: "var(--el-color-warning)",
                danger: "var(--el-color-danger)",
                error: "var(--el-color-error)",
                info: "var(--el-color-info)",
                body: "var(--el-bg-color)",
                page: "var(--el-bg-color-page)",
                "tx-primary": "var(--text-color-primary)",
                "tx-regular": "var(--el-text-color-regular)",
                "tx-secondary": "var(--el-text-color-secondary)",
                "tx-placeholder": "var(--el-text-color-placeholder)",
                "tx-disabled": "var(--el-text-color-disabled)",
                br: "var(--el-border-color)",
                "br-light": "var(--el-border-color-light)",
                "br-extra-light": "var(--el-border-color-extra-light)",
                "br-dark": "var(--el-border-color-dark)",
                fill: "var(--el-fill-color)",
                mask: "var(--el-mask-color)",
                overlay: "var(--el-overlay-color-light)",
                minor: "var(--color-minor)",
                "btn-text": "var(--color-btn-text, white)",
                // 注：gray + blue/sky/red/orange/green/emerald/purple/violet/amber/yellow/slate/rose
                // 全部走 TW 自带调色板，不在此声明。var.css 里 --gray-* 等 CSS 变量仍保留供 SCSS/内联样式使用。
            },
            fontFamily: {
                sans: ["PingFang SC", "Arial", "Hiragino Sans GB", "Microsoft YaHei", "sans-serif"],
            },
            // TW v3 内置 line-clamp-1..6，这里只补齐 7~9
            lineClamp: {
                7: "7",
                8: "8",
                9: "9",
            },
        },
    },
};
