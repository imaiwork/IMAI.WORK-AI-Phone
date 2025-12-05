<template>
    <template v-if="isMarkdown">
        <ua-markdown :content="content"></ua-markdown>
    </template>
    <template v-else>
        <view v-if="error" class="text-red-500">
            {{ error }}
        </view>
        <text v-else user-select class="whitespace-pre-line leading-[40rpx] select-text">
            {{ content }}
        </text>
    </template>
    <view v-if="!loading && type == 2 && !error" class="mt-4">
        <u-line />
        <view class="flex items-center justify-between w-full gap-x-5 mt-2">
            <view>
                <view v-if="Object.keys(consumeTokens).length > 0" class="text-xs text-[#808080]"
                    >消耗TOKENS：{{ (consumeTokens.total_tokens || 0) + (consumeTokens.knowledge_tokens || 0) }}</view
                >
            </view>
            <view
                v-if="showCopyBtn && content"
                class="text-xs text-[#808080] flex items-center gap-1"
                @click="copy(content)">
                <text>复制内容</text>
            </view>
        </view>
    </view>
</template>
<script lang="ts">
export default {
    options: {
        virtualHost: true,
    },
    externalClasses: ["class"],
};
</script>
<script setup lang="ts">
import { useCopy } from "@/hooks/useCopy";

const props = withDefaults(
    defineProps<{
        type: number;
        content: string;
        error?: string;
        isMarkdown: boolean;
        loading?: boolean;
        showCopyBtn?: boolean;
        showVoiceBtn?: boolean;
        recordId?: number | string;
        index?: number;
        consumeTokens?: any;
    }>(),
    {
        type: 1,
        showCopyBtn: false,
        loading: false,
        showVoiceBtn: false,
        consumeTokens: null,
    }
);
const { copy } = useCopy();
// const { play, audioPlaying, pause, audioLoading } = useAudioPlay({
//     api: getChatBroadcast,
//     dataTransform(data) {
//         return data.file_url
//     },
//     params: {
//         records_id: props.recordId,
//         content: props.index,
//         type: 1
//     }
// })
</script>
