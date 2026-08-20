<template>
    <view
        class="bg-white rounded-[24rpx] px-[24rpx] py-[24rpx]"
        style="box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08)"
        @click="emit('detail')">
        <!-- 小红书图文卡 -->
        <template v-if="isImageText">
            <view class="flex items-start gap-[12rpx]">
                <view
                    class="inline-flex items-center gap-[6rpx] px-[12rpx] py-[4rpx] rounded-[8rpx] flex-shrink-0"
                    :style="{ background: meta.bg }">
                    <image :src="meta.icon" mode="aspectFit" class="w-[20rpx] h-[20rpx]" />
                    <text class="text-[20rpx] text-white font-semibold">{{ meta.label }}</text>
                </view>
                <text class="text-[30rpx] font-bold text-[#111827] leading-snug flex-1 min-w-0 line-clamp-2">
                    {{ displayTitle }}
                </text>
            </view>

            <!-- 流程进度（与视频卡共用 #steps） -->
            <view class="mt-[20rpx]">
                <slot name="steps" />
            </view>

            <!-- 不用 scroll-view：H5 + keep-alive 返回时 activated 会写 null.scrollLeft 导致整页报错 -->
            <view class="img-strip mt-[8rpx]">
                <view class="flex gap-[12rpx] pr-[8rpx]" style="width: max-content">
                    <view
                        v-for="(url, i) in previewImages.slice(0, 6)"
                        :key="i"
                        class="w-[140rpx] h-[176rpx] rounded-[12rpx] overflow-hidden bg-[#E5E7EB] flex-shrink-0">
                        <image :src="url" mode="aspectFill" lazy-load class="w-full h-full" />
                    </view>
                    <view
                        v-if="imageCount > 0"
                        class="w-[140rpx] h-[176rpx] rounded-[12rpx] bg-[#F9FAFB] border border-dashed border-[#E5E7EB] flex-shrink-0 flex flex-col items-center justify-center gap-[6rpx]">
                        <u-icon name="photo" color="#D1D5DB" size="28"></u-icon>
                        <text class="text-[20rpx] text-[#9CA3AF]">共{{ imageCount }}张</text>
                    </view>
                </view>
            </view>

            <view class="flex items-center gap-[12rpx] mt-[20rpx]">
                <view class="px-[16rpx] py-[6rpx] rounded-[8rpx]" :class="statusStyle.wrap">
                    <text class="text-[22rpx] font-medium" :class="statusStyle.text">{{ statusLabel }}</text>
                </view>
                <text class="text-xs text-[#9CA3AF]">{{ task.create_time }}</text>
            </view>

            <!-- 失败原因外露，无需进详情才能看到 -->
            <view
                v-if="isFailed && failReason"
                class="mt-[16rpx] px-[20rpx] py-[16rpx] rounded-[12rpx] bg-[#FEF2F2]">
                <text class="text-[22rpx] text-[#EF4444] leading-relaxed">提示：{{ failReason }}</text>
            </view>

            <view class="flex items-center gap-[16rpx] mt-[24rpx]">
                <view
                    v-if="isSelecting"
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F59E0B] flex items-center justify-center"
                    @click.stop="emit('detail')">
                    <text class="text-white font-semibold">去选图确认</text>
                </view>
                <template v-else-if="isDone">
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                        @click.stop="emit('preview')">
                        <text class="text-[#374151] font-medium">预览图文</text>
                    </view>
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center"
                        @click.stop="emit('publish')">
                        <text class="text-white font-semibold">一键发布</text>
                    </view>
                </template>
                <template v-else-if="isFailed">
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                        @click.stop="emit('detail')">
                        <text class="text-[#6B7280] font-medium">查看详情</text>
                    </view>
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center"
                        :class="retrying ? 'opacity-60' : ''"
                        @click.stop="emit('retry')">
                        <u-loading v-if="retrying" mode="circle" size="24" color="#ffffff"></u-loading>
                        <text v-else class="text-white font-semibold">重试</text>
                    </view>
                </template>
                <view
                    v-else
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center gap-[10rpx]">
                    <u-loading mode="circle" size="24" color="#6B7280"></u-loading>
                    <text class="text-[#6B7280] font-medium">{{ runningLabel }}</text>
                </view>
                <view
                    class="w-[80rpx] h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                    @click.stop="emit('more')">
                    <u-icon name="more-dot-fill" color="#6B7280" size="32"></u-icon>
                </view>
            </view>
        </template>

        <!-- 抖音视频：已完成 -->
        <template v-else-if="isVideoDone">
            <view class="flex items-start gap-[20rpx] mb-[24rpx]">
                <view class="relative w-[128rpx] h-[160rpx] rounded-[12rpx] overflow-hidden bg-[#E5E7EB] flex-shrink-0">
                    <image
                        v-if="task.thumbnail"
                        :src="task.thumbnail"
                        mode="aspectFill"
                        lazy-load
                        class="w-full h-full" />
                    <view
                        class="absolute bottom-[8rpx] right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-black/50 flex items-center justify-center">
                        <u-icon name="play-right-fill" color="#fff" size="18"></u-icon>
                    </view>
                </view>
                <view class="flex-1 min-w-0">
                    <view class="flex items-start gap-[12rpx] mb-[16rpx]">
                        <view
                            class="inline-flex items-center gap-[6rpx] px-[12rpx] py-[4rpx] rounded-[8rpx] flex-shrink-0"
                            :style="{ background: meta.bg }">
                            <image :src="meta.icon" mode="aspectFit" class="w-[20rpx] h-[20rpx]" />
                            <text class="text-[20rpx] text-white font-semibold">{{ meta.label }}</text>
                        </view>
                        <text
                            class="text-[30rpx] font-bold text-[#111827] leading-snug flex-1 min-w-0 line-clamp-2">
                            {{ displayTitle }}
                        </text>
                    </view>
                    <view class="flex items-center gap-[12rpx]">
                        <view class="px-[16rpx] py-[6rpx] rounded-[8rpx] bg-[#ECFDF5]">
                            <text class="text-[22rpx] text-[#059669] font-medium">已完成</text>
                        </view>
                        <text class="text-xs text-[#9CA3AF]">{{ task.create_time }}</text>
                    </view>
                </view>
            </view>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                    @click.stop="emit('preview')">
                    <text class="text-[#374151] font-medium">预览视频</text>
                </view>
                <view
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center"
                    @click.stop="emit('publish')">
                    <text class="text-white font-semibold">一键发布</text>
                </view>
                <view
                    class="w-[80rpx] h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                    @click.stop="emit('more')">
                    <u-icon name="more-dot-fill" color="#6B7280" size="32"></u-icon>
                </view>
            </view>
        </template>

        <!-- 抖音视频：进行中 / 失败（失败态对齐图文卡） -->
        <template v-else>
            <view class="flex items-start gap-[12rpx]">
                <view
                    class="inline-flex items-center gap-[6rpx] px-[12rpx] py-[4rpx] rounded-[8rpx] flex-shrink-0"
                    :style="{ background: meta.bg }">
                    <image :src="meta.icon" mode="aspectFit" class="w-[20rpx] h-[20rpx]" />
                    <text class="text-[20rpx] text-white font-semibold">{{ meta.label }}</text>
                </view>
                <text class="text-[30rpx] font-bold text-[#111827] leading-snug flex-1 min-w-0 line-clamp-2">
                    {{ displayTitle }}
                </text>
            </view>

            <view class="mt-[20rpx]">
                <slot name="steps" />
            </view>

            <view class="flex items-center gap-[12rpx] mt-[20rpx]">
                <view class="px-[16rpx] py-[6rpx] rounded-[8rpx]" :class="statusStyle.wrap">
                    <text class="text-[22rpx] font-medium" :class="statusStyle.text">{{ statusLabel }}</text>
                </view>
                <text class="text-xs text-[#9CA3AF]">{{ task.create_time }}</text>
            </view>

            <view
                v-if="isFailed && failReason"
                class="mt-[16rpx] px-[20rpx] py-[16rpx] rounded-[12rpx] bg-[#FEF2F2]">
                <text class="text-[22rpx] text-[#EF4444] leading-relaxed">提示：{{ failReason }}</text>
            </view>

            <view class="flex items-center gap-[16rpx] mt-[24rpx]">
                <template v-if="isFailed">
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                        @click.stop="emit('detail')">
                        <text class="text-[#6B7280] font-medium">查看详情</text>
                    </view>
                    <view
                        class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center"
                        :class="retrying ? 'opacity-60' : ''"
                        @click.stop="emit('retry')">
                        <u-loading v-if="retrying" mode="circle" size="24" color="#ffffff"></u-loading>
                        <text v-else class="text-white font-semibold">重试</text>
                    </view>
                </template>
                <view
                    v-else
                    class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center gap-[10rpx]">
                    <u-loading mode="circle" size="24" color="#6B7280"></u-loading>
                    <text class="text-[#6B7280] font-medium">进行中…</text>
                </view>
                <view
                    class="w-[80rpx] h-[80rpx] rounded-[16rpx] bg-[#F3F4F6] flex items-center justify-center"
                    @click.stop="emit('more')">
                    <u-icon name="more-dot-fill" color="#6B7280" size="32"></u-icon>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
import {
    HOT_WRITE_PLATFORM_META,
    HotWriteTaskStatus,
    ImageRewriteStatus,
    getTaskPreviewImages,
    isImageTextTask,
} from "@/ai_modules/hot_write/enums";

const props = defineProps<{
    task: any;
    retrying?: boolean;
}>();

const emit = defineEmits<{
    (e: "detail"): void;
    (e: "preview"): void;
    (e: "publish"): void;
    (e: "more"): void;
    (e: "retry"): void;
}>();

const isImageText = computed(() => isImageTextTask(props.task));
const meta = computed(
    () =>
        HOT_WRITE_PLATFORM_META[Number(props.task?.platform_type ?? AppTypeEnum.DOUYIN)] ||
        HOT_WRITE_PLATFORM_META[AppTypeEnum.DOUYIN],
);
const displayTitle = computed(() => props.task?.title || props.task?.name || "提取文案中...");
const previewImages = computed(() => getTaskPreviewImages(props.task));
const imageCount = computed(
    () => previewImages.value.length || Number(props.task?.image_count || 0),
);

const isFailed = computed(() => Number(props.task?.status) === HotWriteTaskStatus.FAIL);
const isDone = computed(() => Number(props.task?.status) === HotWriteTaskStatus.SUCCESS);
const isSelecting = computed(
    () =>
        isImageText.value &&
        Number(props.task?.status) === HotWriteTaskStatus.WAIT_CONFIRM &&
        Number(props.task?.image_rewrite_status) === ImageRewriteStatus.SELECTING,
);
const isVideoDone = computed(
    () =>
        !isImageText.value &&
        Number(props.task?.publish_confirm) === 1 &&
        !!props.task?.video_url,
);

const statusLabel = computed(() => {
    if (isFailed.value) return "失败";
    if (isDone.value) return "已完成";
    if (isSelecting.value) return "待选图";
    if (
        [ImageRewriteStatus.WAIT, ImageRewriteStatus.PROCESSING].includes(
            Number(props.task?.image_rewrite_status),
        )
    ) {
        return "生成中";
    }
    return "执行中";
});

const statusStyle = computed(() => {
    if (isFailed.value) return { wrap: "bg-[#FEF2F2]", text: "text-[#EF4444]" };
    if (isDone.value) return { wrap: "bg-[#ECFDF5]", text: "text-[#059669]" };
    if (isSelecting.value) return { wrap: "bg-[#FFFBEB]", text: "text-[#D97706]" };
    return { wrap: "bg-[#EFF6FF]", text: "text-[#2563EB]" };
});

const runningLabel = computed(() => {
    if (
        [ImageRewriteStatus.WAIT, ImageRewriteStatus.PROCESSING].includes(
            Number(props.task?.image_rewrite_status),
        )
    ) {
        return "图片生成中…";
    }
    return "解析进行中…";
});

const failReason = computed(() => String(props.task?.remarks || "").trim());
</script>

<style scoped lang="scss">
.img-strip {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    /* 隐藏滚动条，保留横滑 */
    scrollbar-width: none;
    &::-webkit-scrollbar {
        display: none;
    }
}
</style>
