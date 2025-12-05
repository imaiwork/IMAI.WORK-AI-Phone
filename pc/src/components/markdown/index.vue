<template>
    <div class="markdown-card">
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

    pre {
        padding: 5px 0;
        &.hljs {
            overflow: auto;
        }
    }
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin-bottom: 15px;
        line-height: 24px;
    }
}
.mc-markdown-render {
    ul,
    ol {
        list-style-type: none !important;
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
