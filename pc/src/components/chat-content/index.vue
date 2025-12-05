<template>
    <div class="chat-content">
        <div class="chat-text">
            <div v-if="error">
                {{ error }}
            </div>
            <template v-else>
                <div v-if="reasoningContent" class="bg-primary-light-9 rounded-xl rounded-tl-none p-2 mb-4">
                    <div
                        class="flex items-center justify-between gap-x-4 p-2 rounded-xl hover:bg-[#eaedf6] cursor-pointer"
                        @click="isHide = !isHide">
                        <div class="flex items-center gap-2">
                            <span
                                class="deep-icon"
                                :class="{
                                    'is-animate': !isReasoningFinished,
                                }">
                                <Icon name="local-icon-deep" :size="16"></Icon>
                            </span>
                            <span>{{ isReasoningFinished ? "推理完成" : "正在推理搜索..." }}</span>
                        </div>
                        <Icon name="el-icon-ArrowDown" :size="16"></Icon>
                    </div>
                    <div
                        class="ml-[14px] pl-4 pb-2 border-l-2 border-[#cccfd3] mt-2 reasoning-markdown"
                        v-show="!isHide">
                        <Markdown
                            v-if="useMarkdown"
                            class="text-[#5e6772]"
                            :content="reasoningContent"
                            :typing="!isReasoningFinished"></Markdown>
                    </div>
                </div>
                <Markdown v-if="useMarkdown" :content="content" :typing="loading"> </Markdown>
                <div
                    v-else
                    class="whitespace-pre-line"
                    :class="{
                        'wait-typing': loading,
                    }">
                    {{ content }}
                </div>
            </template>
        </div>
    </div>
</template>

<script lang="ts" setup>
const props = defineProps({
    useMarkdown: {
        type: Boolean,
        default: false,
    },
    content: {
        type: String,
        default: "",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    type: {
        type: Number,
        default: 1,
    },
    reasoningContent: {
        type: [String],
        default: "",
    },
    isReasoningFinished: {
        type: Boolean,
        default: false,
    },
    file: {
        type: Object,
        default: () => ({}),
    },
    error: {
        type: String,
        default: "",
    },
});

const isHide = ref(false);
</script>

<style lang="scss" scoped>
.chat-content {
    .deep-icon {
        display: inline-block;
        &.is-animate {
            animation: rotate 3s linear infinite;
        }
    }
    @keyframes rotate {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
}
</style>
