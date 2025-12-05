<template>
    <view class="chat-record-item">
        <view class="flex gap-2">
            <view class="flex-shrink-0" v-if="avatar">
                <u-icon class="rounded-full overflow-hidden" :name="avatar" :size="60" />
            </view>
            <view class="w-full" v-if="type == 1">
                <view class="flex justify-end w-full">
                    <view
                        class="bg-primary p-[24rpx] text-white rounded-tl-[32rpx] rounded-bl-[32rpx] rounded-br-[32rpx]"
                        v-if="content">
                        <text-item :type="1" :is-markdown="isMarkdown" :content="content" />
                    </view>
                </view>
                <view class="gap-2 mt-2 flex justify-end" v-if="fileList.length">
                    <view class="flex flex-col">
                        <view v-for="(file, index) in fileList" class="" :key="index">
                            <file-item :item="file" :show-del="false">
                                <template #image="{ url }">
                                    <view class="h-[400rpx]">
                                        <image :src="url" mode="aspectFill" class="h-full"></image>
                                    </view>
                                </template>
                            </file-item>
                        </view>
                    </view>
                </view>
            </view>
            <view
                class="bg-white p-[24rpx] rounded-tr-[32rpx] rounded-bl-[32rpx] rounded-br-[32rpx] overflow-hidden"
                v-if="type == 2">
                <view class="text-[28rpx] leading-6">
                    <view v-if="reasoningContent" class="bg-primary-light-8 rounded-xl rounded-tl-none p-2 mb-4">
                        <view
                            class="flex items-center justify-between gap-x-4 p-2 rounded-xl cursor-pointer"
                            @click="isHide = !isHide">
                            <view class="flex items-center gap-2">
                                <view
                                    class="deep-icon leading-none"
                                    :class="{
                                        'is-animate': !isReasoningFinished,
                                    }">
                                    <u-icon name="/static/images/icons/deep.svg" :size="28"></u-icon>
                                </view>
                                <view> {{ isReasoningFinished ? "推理完成" : "正在推理搜索..." }}</view>
                            </view>
                            <u-icon name="arrow-down" :size="24"></u-icon>
                        </view>
                        <div
                            class="ml-[24rpx] pl-4 pb-2 border-solid border-l-2 border-t-[0] border-b-[0] border-r-[0] border-[#cccfd3] mt-2"
                            v-show="!isHide">
                            <text-item :type="3" :is-markdown="true" :content="reasoningContent" />
                        </div>
                    </view>
                    <text-item
                        v-if="content"
                        :type="2"
                        :is-markdown="true"
                        :content="content"
                        :loading="loading"
                        :consume-tokens="consumeTokens"
                        :show-copy-btn="showCopyBtn" />
                    <view class="chat-loader" v-if="loading"></view>
                </view>
            </view>
            <slot name="footer"></slot>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { useCopy } from "@/hooks/useCopy";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import TextItem from "./text-item.vue";
import FileItem from "../chat-scroll-view/components/file-item.vue";
// import
const props = withDefaults(
    defineProps<{
        type: 1 | 2;
        content: string;
        showCopyBtn?: boolean;
        isMarkdown?: boolean;
        loading?: boolean;
        index?: number;
        avatar?: string;
        fileList?: any[];
        consumeTokens?: any;
        reasoningContent?: string;
        isReasoningFinished?: boolean;
    }>(),
    {
        showCopyBtn: true,
        content: "",
        loading: false,
        isMarkdown: false,
        fileList: () => [],
        consumeTokens: null,
        reasoningContent: "",
        isReasoningFinished: false,
    }
);

const emit = defineEmits<{
    (event: "close"): void;
    (event: "rewrite"): void;
    (event: "update", value: any): void;
    (event: "click-poster", value?: number | string): void;
}>();
const userStore = useUserStore();
const appStore = useAppStore();
const { copy } = useCopy();

const isHide = ref(false);
</script>

<style lang="scss" scoped>
@keyframes typingFade {
    0% {
        opacity: 0;
    }
    50% {
        opacity: 100%;
    }
    100% {
        opacity: 100%;
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
.chat-record-item {
    padding: 0 32rpx;
    margin-bottom: 20rpx;
    &__left,
    &__right {
        display: flex;
        align-items: flex-start;
        min-height: 80rpx;
        &-content {
            display: inline-block;
            padding: 20rpx;
            max-width: 100%;
            border-radius: 10rpx;
            position: relative;
            min-width: 70rpx;
            min-height: 80rpx;
            &::before {
                content: "";
                display: block;
                width: 0;
                height: 0;
                position: absolute;
                top: 24rpx;
                border: 16rpx solid transparent;
            }
        }
        .text-typing {
            display: inline-block;
            vertical-align: -8rpx;
            height: 34rpx;
            width: 6rpx;
            background-color: $u-type-primary;
            animation: typingFade 0.4s infinite alternate;
        }
    }
    &__right {
        flex-direction: row-reverse;
    }
    &__left-content {
        margin-left: 25rpx;
        background-color: $u-bg-color;
        &::before {
            left: -30rpx;
            border-right-color: $u-bg-color;
        }
    }
    &__right-content {
        color: #fff;
        background-color: #4073fa;
        margin-right: 20rpx;
        &::before {
            right: -30rpx;
            border-left-color: #4073fa;
        }
    }
    .deep-icon {
        display: inline-block;
        &.is-animate {
            transform-origin: center;
            animation: rotate 3s linear infinite;
        }
    }
}
</style>
