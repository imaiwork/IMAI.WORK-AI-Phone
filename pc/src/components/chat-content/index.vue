<template>
    <div class="chat-content">
        <div class="chat-text">
            <div v-if="error" class="italic text-[#444746]">
                {{ error }}
            </div>
            <div v-if="stopReply" class="italic text-[#444746]">
                {{ stopReply }}
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
                <!-- 这里超过5行就显示省略号，然后需要有一个按钮展开 -->
                <div
                    v-else
                    class="break-all"
                    :class="{
                        'wait-typing': loading,
                        'line-clamp-5': !isContentExpanded && showExpandButton,
                    }">
                    {{ content }}
                </div>
                <ElTooltip :content="isContentExpanded ? '收起' : '展开'" placement="top" v-if="showExpandButton">
                    <div
                        class="text-sm text-[#8A939D] cursor-pointer text-end w-fit ml-auto p-1 leading-[0] -mb-2"
                        @click="isContentExpanded = !isContentExpanded">
                        <Icon :name="isContentExpanded ? 'el-icon-ArrowUp' : 'el-icon-ArrowDown'" :size="16"></Icon>
                    </div>
                </ElTooltip>
            </template>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, computed } from "vue";

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
    stopReply: {
        type: String,
        default: "",
    },
});

const isHide = ref(false);
const isContentExpanded = ref(false);

const showExpandButton = computed(() => {
    return !props.useMarkdown && props.content.length > 200;
});
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
