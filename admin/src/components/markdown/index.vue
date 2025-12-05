<template>
    <div class="markdown-it-container markdown-body" @click="handleClick($event)" v-html="result" />
</template>

<script setup lang="ts">
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'
import 'highlight.js/styles/atom-one-dark.css'
import 'github-markdown-css'
import type { CodePluginOptions } from './codePlugin'
import { codePlugin } from './codePlugin'
import { useCopy } from '@/hooks/useCopy'
interface Options extends Partial<MarkdownIt.Options> {
    lineNumbers?: boolean
}

const props = withDefaults(
    defineProps<{
        content: string
        html?: boolean
        breaks?: boolean
        linkify?: boolean
        typographer?: boolean
        // 是否显示代码行
        lineNumbers?: boolean
    }>(),
    {
        content: '',
        html: true,
        breaks: true,
        typographer: true,
        linkify: true,
        lineNumbers: true
    }
)
const result = ref('')
const createMarkdown = (options: Options) => {
    const md = new MarkdownIt({
        ...options,
        langPrefix: 'language-',
        highlight(str: any, lang: any) {
            try {
                if (lang && hljs.getLanguage(lang)) {
                    return hljs.highlight(lang, str, true).value
                }
                return hljs.highlightAuto(str).value
            } catch (error) {
                return str
            }
        }
    })
    md.use<CodePluginOptions>(codePlugin, {
        lineNumbers: options.lineNumbers
    })
    return md
}
let md: MarkdownIt
watchEffect(() => {
    md = createMarkdown({
        html: props.html,
        breaks: props.breaks,
        typographer: props.typographer,
        linkify: props.linkify,
        lineNumbers: props.lineNumbers
    })
    result.value = md.render(props.content)
})

watch(
    () => props.content,
    (value) => {
        result.value = md?.render(value)
    },
    {
        immediate: true
    }
)
const { copy } = useCopy()

const handleClick = async (e: any) => {
    const target: HTMLElement = e.target
    if (target.className === 'code-copy-btn') {
        console.log(e)
        const text = e.target.parentElement.nextElementSibling.textContent
        await copy(text)
    }
}
</script>

<style lang="scss">
@use 'code.scss';
</style>
