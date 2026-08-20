<!-- uniapp vue3 markdown解析 -->
<template>
    <view class="ua__markdown">
        <template v-for="(block, index) in renderBlocks" :key="index">
            <mp-html
                v-if="block.type === 'html'"
                :selectable="true"
                :scrollTable="true"
                :content="block.content"
                :copy-link="false"
                :tag-style="tagStyle"
                @link-tap="handleLinkTap"
            />
            <view v-else-if="block.type === 'code'" class="code-block">
                <view class="code-block__header">
                    <text class="code-block__lang">{{ block.lang }}</text>
                    <view class="code-block__copy" @tap.stop="copyCode(block.raw)">
                        <text class="code-block__copy-text">{{
                            copiedIndex === index ? '已复制 ✓' : '复制'
                        }}</text>
                    </view>
                </view>
                <scroll-view scroll-x class="code-block__scroll">
                    <mp-html
                        :selectable="true"
                        :content="block.content"
                        :copy-link="false"
                        :tag-style="codeTagStyle"
                    />
                </scroll-view>
            </view>
        </template>
    </view>
</template>

<script setup>
import MarkdownIt from './lib/markdown-it.min.js'
// #ifdef APP-NVUE
import parseHtml from './lib/html-parser.js'
// #endif
import markdownItMath from '@iktakahiro/markdown-it-katex'
import MpHtml from '@/ai_modules/interview/uni_modules/mp-html/components/mp-html/mp-html.vue'
import { useCopy } from '@/hooks/useCopy'
import { ref, computed } from 'vue'

const props = defineProps({
    content: String,
    showLine: { type: [Boolean, String], default: true }
})

// ===================== 复制状态 =====================
const copiedIndex = ref(-1)

const copyCode = (raw) => {
    if (!raw) return

    // #ifdef H5
    uni.setClipboardData({
        data: raw,
        showToast: false,
        success() {
            uni.showToast({ title: '复制成功', icon: 'none' })
        }
    })
    // #endif

    // #ifndef H5
    const { copy } = useCopy()
    copy(raw)
    // #endif
}

// ===================== 样式配置 =====================
const tagStyle = {
    img: `max-width:100%; border-radius:8px; margin:16rpx 0; display:block;`,
    p: `margin:0 0 16rpx 0; padding:0; line-height:1.9; color:#3c3c3c; font-size:30rpx; word-break:break-word;`,
    h1: `font-size:44rpx; font-weight:bold; color:#1a1a1a; margin:32rpx 0 16rpx; line-height:1.4;`,
    h2: `font-size:38rpx; font-weight:bold; color:#1a1a1a; margin:32rpx 0 14rpx; line-height:1.4; border-bottom:2px solid #eee; padding-bottom:10rpx;`,
    h3: `font-size:34rpx; font-weight:bold; color:#222; margin:28rpx 0 12rpx; line-height:1.4;`,
    h4: `font-size:30rpx; font-weight:bold; color:#333; margin:20rpx 0 8rpx;`,
    a: `color:#007AFF; text-decoration:none;`,
    code: `background:#f0f0f0; color:#e83e8c; padding:4rpx 12rpx; border-radius:6rpx; font-size:26rpx; font-family:monospace;`,
    blockquote: `border-left:6rpx solid #007AFF; background:#f4f8ff; margin:20rpx 0; padding:16rpx 24rpx; color:#555; border-radius:0 8rpx 8rpx 0;`,
    ul: `padding-left:44rpx; margin:8rpx 0 16rpx;`,
    ol: `padding-left:0; margin:8rpx 0 16rpx;`,
    li: `margin:0 0 10rpx 0; line-height:1.9; font-size:30rpx; color:#3c3c3c;`,
    hr: `border:none; border-top:1px solid #eee; margin:32rpx 0;`,
    table: `border-collapse:collapse; width:100%; margin:16rpx 0; font-size:26rpx;`,
    th: `background:#f5f7fa; color:#333; font-weight:bold; border:1px solid #dde1e7; padding:12rpx 16rpx; text-align:left;`,
    td: `border:1px solid #dde1e7; padding:10rpx 16rpx; color:#444;`,
    strong: `font-weight:bold; color:#1a1a1a;`,
    em: `font-style:italic; color:#555;`,
    pre: `margin:0; padding:0; background:none; border:none;`
}

// 代码块内部专用样式
const codeTagStyle = {
    pre: `margin:0; padding:0; background:none; border:none;`,
    code: `background:none; padding:0; border-radius:0; color:#2c2c2c; font-size:26rpx; font-family:'Courier New',Courier,monospace; display:block;`
}

// ===================== 工具函数 =====================
const minifyHtml = (html) => {
    return html
        .replace(/<!--[\s\S]*?-->/g, '')
        .replace(/>\s+</g, '><')
        .replace(/\s{2,}/g, ' ')
        .trim()
}

// ===================== Markdown 初始化（不再处理代码块高亮） =====================
const markdown = MarkdownIt({
    html: true,
    breaks: true,
    typographer: true,
    linkify: true,
    // ✅ 不在这里处理代码块，交给 renderBlocks 拆分处理
    highlight: null
})

markdown.use(markdownItMath)

// ===================== 核心：将 content 拆分为 html块 + code块 =====================
const renderBlocks = computed(() => {
    const value = props.content
    if (!value) return []

    const blocks = []
    // 匹配 ```lang\ncode\n``` 格式
    const codeReg = /```(\w*)\n([\s\S]*?)```/g
    let lastIndex = 0
    let match

    while ((match = codeReg.exec(value)) !== null) {
        // 代码块之前的普通文本
        if (match.index > lastIndex) {
            const htmlPart = value.slice(lastIndex, match.index)
            const rendered = renderHtml(htmlPart)
            if (rendered) blocks.push({ type: 'html', content: rendered })
        }

        // 代码块本身
        const lang = match[1] || 'code'
        const raw = match[2]
        const langLabel = lang.charAt(0).toUpperCase() + lang.slice(1)
        const codeHtml = buildCodeHtml(raw)
        blocks.push({
            type: 'code',
            lang: langLabel,
            raw: raw,
            content: codeHtml
        })

        lastIndex = match.index + match[0].length
    }

    // 剩余普通文本
    if (lastIndex < value.length) {
        const htmlPart = value.slice(lastIndex)
        const rendered = renderHtml(htmlPart)
        if (rendered) blocks.push({ type: 'html', content: rendered })
    }

    return blocks
})

// 渲染普通 markdown 为 html
const renderHtml = (text) => {
    if (!text.trim()) return ''
    const t = text.replace(/<br>|<br\/>|<br \/>/g, '\n').replace(/&nbsp;/g, ' ')
    let html = markdown.render(t)
    html = html.replace(/<p>\s*(<div[\s\S]*?<\/div>)\s*<\/p>/g, '$1')
    html = minifyHtml(html)
    html = html
        .replace(/<table/g, `<table class="table"`)
        .replace(/<tr/g, `<tr class="tr"`)
        .replace(/<th>/g, `<th class="th">`)
        .replace(/<td/g, `<td class="td"`)
        .replace(/<hr>|<hr\/>|<hr \/>/g, `<hr class="hr">`)
    return html
}

// 将原始代码转为 mp-html 可渲染的 html
const buildCodeHtml = (raw) => {
    const escaped = raw.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    const lines = escaped.split('\n')
    if (lines[lines.length - 1] === '') lines.pop()
    const codeLines = lines
        .map(
            (item) =>
                `<div style="min-height:1.7em;line-height:1.7;font-size:26rpx;color:#2c2c2c;font-family:'Courier New',Courier,monospace;white-space:pre;word-break:normal;">${
                    item || ' '
                }</div>`
        )
        .join('')
    return minifyHtml(
        `<pre style="margin:0;padding:0;background:none;"><code style="display:block;">${codeLines}</code></pre>`
    )
}

// ===================== 普通链接点击 =====================
const handleLinkTap = (e) => {
    // 普通链接处理（如需要可在此扩展）
    console.log('link-tap', e)
}
</script>

<style lang="scss" scoped>
.ua__markdown {
    width: 100%;
    box-sizing: border-box;
    padding: 0 4rpx;
    font-size: 30rpx;
    color: #3c3c3c;
    line-height: 1.9;
    word-break: break-word;
}

.code-block {
    margin: 16rpx 0 24rpx;
    border-radius: 12rpx;
    border: 1px solid #e8e8e8;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);

    &__header {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 16rpx 24rpx;
        border-bottom: 1px solid #f0f0f0;
        background: #fafafa;
    }

    &__lang {
        font-size: 26rpx;
        font-weight: 600;
        color: #1a1a1a;
        font-family: monospace;
    }

    &__copy {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 8rpx 20rpx;
        background: #f0f0f0;
        border-radius: 8rpx;

        &:active {
            opacity: 0.7;
        }
    }

    &__copy-text {
        font-size: 24rpx;
        color: #007aff;
    }

    &__scroll {
        padding: 24rpx;
        background: #ffffff;
    }
}
</style>
