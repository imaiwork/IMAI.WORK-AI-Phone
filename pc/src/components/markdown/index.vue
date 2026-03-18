<template>
    <div class="markdown-card" :class="['markdown-body']">
        <McMarkdownCard :content="content" :typing="typing" :typing-options="typingOptions" :md-plugins="mdPlugins">
            <!-- 头部 -->
            <template v-if="$slots.header" #header="{ codeBlockData }">
                <slot name="header" :codeBlockData="codeBlockData"></slot>
            </template>
            <!-- 操作区 -->
            <template v-if="$slots.actions" #actions="{ codeBlockData }">
                <slot name="actions" :codeBlockData="codeBlockData"></slot>
            </template>
            <!-- 内容区 -->
            <template #content="{ codeBlockData }">
                <div id="content-container" v-html="transfer(codeBlockData)"></div>
            </template>
        </McMarkdownCard>
    </div>
</template>

<script setup lang="ts">
// @ts-ignore
import { McMarkdownCard } from "@matechat/core";
import markdownIt from "markdown-it";
import hljs from "highlight.js";
import { katex } from "@mdit/plugin-katex";
import PlantUml from "markdown-it-plantuml";
import { EChartsPlugin, MermaidPlugIn } from "./plugins";

import "katex/dist/katex.min.css";
// 引入 github-markdown-css
import "github-markdown-css/github-markdown.css";
// 根据主题引入不同的 highlight.js 样式
import "highlight.js/styles/github.css";

const props = withDefaults(
    defineProps<{
        content: string;
        typing?: boolean;
        typingOptions?: any;
        theme?: "light" | "dark";
    }>(),
    {
        content: "",
        typing: false,
        theme: "light",
    }
);

const mdPlugins = ref([
    {
        plugin: PlantUml,
    },
    {
        plugin: katex,
    },
    {
        plugin: EChartsPlugin,
    },
    {
        plugin: MermaidPlugIn,
    },
]);

const mdt = markdownIt({
    breaks: true,
    linkify: true,
    typographer: true,
    highlight: (str, lang) => {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return highlightContent(str, lang);
            } catch (__) {}
        }
        const preCode = mdt.utils.escapeHtml(str);
        const lines = preCode.split(/\n/);
        let html = lines
            .map((item, index) => {
                return '<li><span class="line-num" data-line="' + (index + 1) + '"></span>' + item + "</li>";
            })
            .join("");
        html = "<ol>" + html + "</ol>";
        return '<pre class="hljs"><code>' + html + "</code></pre>";
    },
});

const highlightContent = (str, lang, className = "") => {
    const preCode = hljs.highlight(lang, str, true).value;
    const lines = preCode.split(/\n/);
    let html = lines
        .map((item, index) => {
            return '<li><span class="line-num" data-line="' + (index + 1) + '"></span>' + item + "</li>";
        })
        .join("");
    html = "<ol>" + html + "</ol>";
    return `<pre class="hljs ${className}"><code>${html}</code></pre>`;
};

const transfer = (codeBlockData) => {
    const { code, language } = codeBlockData;
    const codeBlockStr = "\`\`\`" + language + "\n" + code + "\`\`\`";
    return mdt.render(codeBlockStr);
};
</script>

<style lang="scss">
.markdown-card {
    box-sizing: border-box;
    max-width: 100%;
    p {
        margin-top: 0;
        margin-bottom: 16px !important;
        line-height: 1.8 !important;
        font-size: 15px !important;
        color: inherit;
        word-break: break-word;
    }

    a {
        color: #0969da;
        text-decoration: none;
        &:hover {
            text-decoration: underline;
        }
    }

    // ✅ 强调文本
    strong {
        font-weight: 600;
    }
    em {
        font-style: italic;
    }

    // 暗黑模式
    &[data-color-mode="dark"] {
        background-color: #0d1117;
        color: #e6edf3;

        --color-canvas-default: #0d1117;
        --color-canvas-subtle: #161b22;
        --color-border-default: #30363d;
        --color-fg-default: #e6edf3;
        --color-fg-muted: #8b949e;

        p {
            color: #e6edf3;
        }

        a {
            color: #58a6ff;
        }

        pre[class*="language-"] {
            background: #161b22 !important;
        }

        :not(pre) > code {
            background-color: rgba(110, 118, 129, 0.4) !important;
            color: #ff7b72 !important;
        }

        blockquote {
            border-left-color: #58a6ff;
            background-color: rgba(88, 166, 255, 0.08);
            color: #8b949e;
        }

        table th {
            background-color: #161b22;
        }
        table tr:nth-child(even) {
            background-color: #161b22;
        }
        table th,
        table td {
            border-color: #30363d;
        }

        h1,
        h2 {
            border-bottom-color: #30363d;
        }
    }

    // 标题
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin-bottom: 15px;
        line-height: 1.5;
        font-weight: 600;
    }
    h1 {
        font-size: 1.8em;
        border-bottom: 1px solid #eaecef;
        padding-bottom: 8px;
    }
    h2 {
        font-size: 1.5em;
        border-bottom: 1px solid #eaecef;
        padding-bottom: 6px;
    }
    h3 {
        font-size: 1.25em;
    }

    ol,
    ul {
        margin-bottom: 15px !important;
    }
    li + li {
        margin-top: 8px;
    }

    hr {
        margin: 15px 0;
    }

    // 代码块
    pre[class*="language-"] {
        border-radius: 8px;
        padding: 16px;
        overflow: auto;
        font-size: 14px !important;
        line-height: 1.6;
    }

    // 行内代码
    :not(pre) > code {
        background-color: rgba(175, 184, 193, 0.2);
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 0.9em !important;
        color: #cf222e;
    }

    // 引用块
    blockquote {
        border-left: 4px solid #0969da;
        background-color: rgba(9, 105, 218, 0.05);
        padding: 8px 16px;
        margin: 12px 0;
        border-radius: 0 6px 6px 0;
        color: #57606a;

        p {
            margin-bottom: 0;
        }
    }

    // 表格
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 16px 0;

        th {
            background-color: #f6f8fa;
            font-weight: 600;
            padding: 8px 12px;
            border: 1px solid #d0d7de;
        }
        td {
            padding: 8px 12px;
            border: 1px solid #d0d7de;
        }
        tr:nth-child(even) {
            background-color: #f6f8fa;
        }
    }
}

.mc-markdown-render {
    ul,
    ol {
        list-style-type: none !important;
    }
    // ⚠️ 去掉 * 的 font-size !important，避免覆盖 p 标签字号
    * {
        font-size: inherit;
    }
}

.echarts-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    color: #fff;
}
</style>
