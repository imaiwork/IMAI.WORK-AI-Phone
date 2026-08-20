<template>
    <!-- 仅非 chat 模式渲染；弹窗挂在 chat.vue 根级，避免被 tabbar 遮盖 -->
    <view v-if="mode !== WorkbenchMode.Chat" class="wb-mode-wrap">
        <!-- 图像生成专用工具栏 -->
        <scroll-view v-if="mode === WorkbenchMode.Image" scroll-x class="w-full">
            <view class="wb-row whitespace-nowrap">
                <view
                    class="wb-pill wb-pill--active"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('change', WorkbenchMode.Chat)">
                    <u-icon name="photo" :size="24" color="#2563EB"></u-icon>
                    <text>图像生成</text>
                    <u-icon name="close" :size="20" color="#2563EB"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('open-model-sheet')">
                    <view class="wb-avatar">
                        <text class="text-white text-[18rpx] font-bold">{{ modelInitial }}</text>
                    </view>
                    <text class="max-w-[160rpx] truncate">{{ currentModelName }}</text>
                    <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('open-size-sheet')">
                    <u-icon name="grid" :size="22" color="#6B7280"></u-icon>
                    <text>{{ sizeLabel }}</text>
                    <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    :class="{ 'wb-pill--active': imageOptimize }"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('update:imageOptimize', !imageOptimize)">
                    <view class="wb-switch" :class="{ 'wb-switch--on': imageOptimize }">
                        <view class="wb-knob"></view>
                    </view>
                    <text>提示词优化</text>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('show-history')">
                    <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                    <text>历史</text>
                </view>
                <view
                    class="wb-pill wb-pill--dashed"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="pickRefImage">
                    <u-icon name="plus" :size="22" color="#9CA3AF"></u-icon>
                    <text>参考图上传</text>
                </view>
            </view>
        </scroll-view>

        <!-- 地图获客 -->
        <scroll-view v-if="mode === WorkbenchMode.Map" scroll-x class="w-full">
            <view class="wb-row whitespace-nowrap">
                <view
                    class="wb-pill wb-pill--active"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('change', WorkbenchMode.Chat)">
                    <u-icon name="map" :size="24" color="#2563EB"></u-icon>
                    <text>地图获客</text>
                    <u-icon name="close" :size="20" color="#2563EB"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('show-history')">
                    <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                    <text>历史</text>
                </view>
                <view
                    v-if="mapCanLoadMore"
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('map-more')">
                    继续获取
                </view>
                <view
                    v-if="mapCanExport"
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('map-export')">
                    导出 Excel
                </view>
            </view>
        </scroll-view>

        <!-- PPT -->
        <scroll-view v-if="mode === WorkbenchMode.Ppt" scroll-x class="w-full">
            <view class="wb-row whitespace-nowrap">
                <view
                    class="wb-pill wb-pill--active"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('change', WorkbenchMode.Chat)">
                    <text>PPT生成</text>
                    <u-icon name="close" :size="20" color="#2563EB"></u-icon>
                </view>
                <view
                    v-for="n in PPT_PAGE_OPTIONS"
                    :key="n"
                    class="wb-chip"
                    :class="{ 'wb-chip--on': pptPageCount === n }"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('update:pptPageCount', n)">
                    {{ n }}页
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('show-history')">
                    <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                    <text>历史</text>
                </view>
            </view>
        </scroll-view>

        <!-- 视频 -->
        <scroll-view v-if="mode === WorkbenchMode.Video" scroll-x class="w-full">
            <view class="wb-row whitespace-nowrap">
                <view
                    class="wb-pill wb-pill--active"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('change', WorkbenchMode.Chat)">
                    <text>视频生成</text>
                    <u-icon name="close" :size="20" color="#2563EB"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('open-size-sheet')">
                    <u-icon name="grid" :size="22" color="#6B7280"></u-icon>
                    <text>{{ videoSizeLabel }}</text>
                    <u-icon name="arrow-down" size="16" color="#9CA3AF"></u-icon>
                </view>
                <view
                    class="wb-pill"
                    :class="{ 'wb-pill--active': videoOptimize }"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('update:videoOptimize', !videoOptimize)">
                    <view class="wb-switch" :class="{ 'wb-switch--on': videoOptimize }">
                        <view class="wb-knob"></view>
                    </view>
                    <text>提示词优化</text>
                </view>
                <view
                    class="wb-pill"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="emit('show-history')">
                    <u-icon name="/static/images/icons/clock.svg" :size="24"></u-icon>
                    <text>历史</text>
                </view>
                <view
                    class="wb-pill wb-pill--dashed"
                    hover-class="opacity-70"
                    :hover-stay-time="80"
                    @click="pickRefImage">
                    <u-icon name="plus" :size="22" color="#9CA3AF"></u-icon>
                    <text>参考图上传</text>
                </view>
            </view>
        </scroll-view>

        <!-- 参考图缩略条 -->
        <view
            v-if="(mode === WorkbenchMode.Image || mode === WorkbenchMode.Video) && refImages.length"
            class="mt-[4rpx]">
            <scroll-view scroll-x class="w-full">
                <view class="flex items-center gap-x-[16rpx] py-[4rpx]">
                    <view v-for="(url, idx) in refImages" :key="url + idx" class="wb-ref-thumb">
                        <image :src="url" mode="aspectFill" class="w-full h-full" />
                        <view class="wb-ref-rm" @click.stop="emit('remove-ref', idx)">
                            <u-icon name="close" color="#fff" :size="14"></u-icon>
                        </view>
                    </view>
                    <view
                        v-if="mode === WorkbenchMode.Image && refImages.length < IMAGE_REF_MAX"
                        class="wb-ref-thumb wb-ref-thumb--add"
                        hover-class="opacity-70"
                        :hover-stay-time="80"
                        @click="pickRefImage">
                        <u-icon name="plus" :size="28" color="#9CA3AF"></u-icon>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
</template>

<script setup lang="ts">
import useUpload from "@/hooks/useUpload";
import {
    WorkbenchMode,
    IMAGE_REF_MAX,
    PPT_PAGE_OPTIONS,
} from "../enums/workbench";

const props = withDefaults(
    defineProps<{
        mode: WorkbenchMode;
        imageOptimize?: boolean;
        currentModelName?: string;
        sizeLabel?: string;
        videoSizeLabel?: string;
        refImages?: string[];
        pptPageCount?: number;
        videoOptimize?: boolean;
        mapCanLoadMore?: boolean;
        mapCanExport?: boolean;
    }>(),
    {
        imageOptimize: false,
        currentModelName: "选择模型",
        sizeLabel: "9:16 · 高清2K",
        videoSizeLabel: "16:9 · 720p",
        refImages: () => [],
        pptPageCount: 8,
        videoOptimize: false,
        mapCanLoadMore: false,
        mapCanExport: false,
    },
);

const emit = defineEmits<{
    (e: "change", mode: WorkbenchMode): void;
    (e: "update:imageOptimize", val: boolean): void;
    (e: "update:pptPageCount", val: number): void;
    (e: "update:videoOptimize", val: boolean): void;
    (e: "add-ref", url: string): void;
    (e: "remove-ref", index: number): void;
    (e: "show-history"): void;
    (e: "map-more"): void;
    (e: "map-export"): void;
    (e: "open-model-sheet"): void;
    (e: "open-size-sheet"): void;
}>();

const modelInitial = computed(() => (props.currentModelName || "M").charAt(0).toUpperCase());

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    sourceType: ["album", "camera"],
    imageResolution: [4096, 4096],
    onSuccess: (materials) => {
        const url = String(materials?.[0]?.url || "").trim();
        if (url) emit("add-ref", url);
    },
});

const pickRefImage = async () => {
    if (props.mode === WorkbenchMode.Image && props.refImages.length >= IMAGE_REF_MAX) {
        uni.$u.toast(`最多添加 ${IMAGE_REF_MAX} 张参考图`);
        return;
    }
    if (props.mode === WorkbenchMode.Video && props.refImages.length >= 1) {
        uni.$u.toast("视频模式仅支持 1 张参考图");
        return;
    }
    try {
        await uploadAndProcessFiles("image");
    } catch {
        /* useUpload 内部已 toast */
    }
};

defineExpose({ pickRefImage });
</script>

<style lang="scss" scoped>
.wb-mode-wrap {
    @apply mb-0;
}
.wb-row {
    @apply flex items-center gap-x-[16rpx] py-[8rpx];
}
.wb-pill {
    box-sizing: border-box;
    @apply inline-flex items-center justify-center gap-x-[8rpx] h-[60rpx] px-[24rpx] rounded-full bg-[#F3F4F6] text-[#555555] text-xs flex-shrink-0 border border-solid border-[transparent];
}
.wb-pill--active {
    @apply bg-[#EFF6FF] text-primary border-[#BFDBFE];
}
.wb-pill--dashed {
    @apply bg-white text-[#6B7280];
    border-style: dashed;
    border-color: #d1d5db;
}
.wb-avatar {
    @apply w-[28rpx] h-[28rpx] rounded-full bg-[#111827] flex items-center justify-center flex-shrink-0;
}
.wb-switch {
    @apply relative w-[40rpx] h-[24rpx] rounded-full bg-[#D1D5DB] flex-shrink-0;
    transition: background 0.18s ease;
}
.wb-switch--on {
    @apply bg-primary;
}
.wb-knob {
    @apply absolute top-[2rpx] left-[2rpx] w-[20rpx] h-[20rpx] rounded-full bg-white;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    transition: transform 0.18s ease;
}
.wb-switch--on .wb-knob {
    transform: translateX(16rpx);
}
.wb-chip {
    box-sizing: border-box;
    @apply inline-flex items-center h-[60rpx] px-[18rpx] rounded-full bg-[#F3F4F6] text-[#6B7280] text-[22rpx] flex-shrink-0;
}
.wb-chip--on {
    @apply bg-[#EFF6FF] text-primary font-semibold;
}
.wb-ref-thumb {
    @apply relative w-[112rpx] h-[112rpx] rounded-[24rpx] overflow-hidden flex-shrink-0 border border-solid border-[#E5E7EB] bg-[#FAFAFA];
}
.wb-ref-thumb--add {
    @apply flex items-center justify-center;
    border-style: dashed;
    border-color: #cbd5e1;
}
.wb-ref-rm {
    @apply absolute top-[4rpx] right-[4rpx] w-[32rpx] h-[32rpx] rounded-full flex items-center justify-center;
    background: rgba(0, 0, 0, 0.55);
}
</style>
