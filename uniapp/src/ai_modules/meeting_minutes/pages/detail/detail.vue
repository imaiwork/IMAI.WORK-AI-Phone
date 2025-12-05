<template>
    <view class="detail-page">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            :title="detail.name"
            title-bold>
        </u-navbar>
        <template v-if="!loading">
            <view class="px-[32rpx] my-3">
                <AudioControl
                    :url="detail.url"
                    v-if="detail.url"
                    ref="audioControlRef"
                    @update-time="handleAudioUpdateTime" />
            </view>
            <view class="grow min-h-0 bg-[#F5F8FF] rounded-tl-[24rpx] rounded-tr-[24rpx] py-4 flex flex-col">
                <view class="flex items-center gap-10 px-4">
                    <view
                        v-for="(item, index) in previewTabs"
                        :key="index"
                        class="relative preview-tab"
                        @click="previewActive = item.value">
                        <view
                            class="tab-item"
                            :class="{
                                active: previewActive == item.value,
                            }"
                            >{{ item.label }}</view
                        >
                        <view v-if="item.value == 1" class="absolute -top-[6rpx] -right-[24rpx]">
                            <image
                                src="@/ai_modules/meeting_minutes/static/images/common/ai.png"
                                class="w-[20rpx] h-[16rpx]"></image>
                        </view>
                    </view>
                </view>
                <view class="mt-[48rpx] min-h-0 grow">
                    <Preview v-show="previewActive == 1" :detail="detail" />
                    <Content
                        v-show="previewActive == 2"
                        ref="contentRef"
                        :show="previewActive == 2"
                        :detail="detail"
                        @update-time="handleContentUpdateTime" />
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { meetingMinutesDetail } from "@/api/meeting_minutes";
import AudioControl from "./audio-control.vue";
import Preview from "./preview.vue";
import Content from "./content.vue";

const audioControlRef = shallowRef<InstanceType<typeof AudioControl> | null>();
const contentRef = shallowRef<InstanceType<typeof Content> | null>();
const state = reactive({
    id: "",
});

const detail = ref<any>({});

const previewActive = ref(1);
const previewTabs = [
    {
        value: 1,
        label: "智能速览",
    },
    {
        value: 2,
        label: "原文",
    },
];

const handleAudioUpdateTime = (time: any) => {
    time = (time * 1000).toFixed(0);
    contentRef.value?.contentScroll(time);
};

const handleContentUpdateTime = (time: number) => {
    audioControlRef.value?.seek(time);
    audioControlRef.value?.pause();
};

const loading = ref<boolean>(true);

const getDetail = async () => {
    const data = await meetingMinutesDetail({ id: state.id });
    detail.value = data;
};

const init = async () => {
    uni.showLoading({
        title: "加载中",
        mask: true,
    });
    try {
        await getDetail();
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options: any) => {
    state.id = options.id;
    if (state.id) {
        init();
    }
});
</script>

<style scoped lang="scss">
.detail-page {
    background-image: url("@/ai_modules/meeting_minutes/static/images/common/mask_bg.png");
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
    @apply h-screen flex flex-col;
    .preview-tab {
        .tab-item {
            @apply text-xl text-[#717189] relative;
            &.active {
                @apply text-black font-bold;
                &::after {
                    content: "";
                    transform: translateX(-50%);
                    @apply absolute -bottom-2 left-[50%] w-[50%] rounded-md h-[6rpx] bg-primary;
                }
            }
        }
    }
}
</style>
