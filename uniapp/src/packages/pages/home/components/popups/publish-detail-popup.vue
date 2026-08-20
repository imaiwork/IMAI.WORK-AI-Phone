<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-[#f4f5f9]"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)"
    >
        <template #header>
            <view
                class="bg-white px-[40rpx] pt-[24rpx] pb-[28rpx] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.06)]"
            >
                <view class="w-[80rpx] h-[8rpx] mx-auto mb-[28rpx] bg-[#e5e7eb] rounded-full" />
                <view class="flex items-center justify-between">
                    <text class="text-base font-bold text-[#1f2937]">
                        {{ item ? `${item.platform_name} · 发布详情` : '发布详情' }}
                    </text>
                    <view
                        class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center"
                        @click="emit('update:modelValue', false)"
                    >
                        <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view v-if="item" class="p-[32rpx] pb-[60rpx] flex flex-col gap-[24rpx]">
                    <!-- 状态条 -->
                    <view
                        class="rounded-[24rpx] py-[24rpx] flex items-center justify-center gap-[12rpx]"
                        :class="statusBarClass"
                    >
                        <u-icon :name="statusIcon" :color="statusIconColor" :size="36"></u-icon>
                        <text class="text-sm font-bold" :class="statusTextClass">
                            {{ item.task_status_text }}
                        </text>
                    </view>

                    <!-- 失败原因 -->
                    <view
                        v-if="isFailed && item.remark"
                        class="bg-error-light-9 rounded-[24rpx] px-[28rpx] py-[20rpx] flex items-start gap-[12rpx]"
                    >
                        <u-icon
                            name="info-circle"
                            color="#f56c6c"
                            :size="28"
                            class="flex-shrink-0 mt-[4rpx]"
                        ></u-icon>
                        <view class="flex-1 min-w-0">
                            <text class="block text-xs font-bold text-error mb-[6rpx]"
                                >失败原因</text
                            >
                            <text class="block text-xs text-error leading-[36rpx] break-all">
                                {{ item.remark }}
                            </text>
                        </view>
                    </view>

                    <!-- 发布内容 -->
                    <view class="bg-white rounded-[32rpx] p-[28rpx] flex flex-col gap-[24rpx]">
                        <view>
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]">
                                发布内容（{{ item.media_type_text }}）
                            </text>
                            <!-- 视频：封面 + 播放 -->
                            <view
                                v-if="isVideo"
                                class="relative h-[300rpx] rounded-[24rpx] overflow-hidden bg-[#f3f4f6]">
                                <image
                                    v-if="item.pic"
                                    :src="item.pic"
                                    mode="aspectFill"
                                    class="absolute inset-0 w-full h-full" />
                                <view class="absolute inset-0 flex items-center justify-center">
                                    <view
                                        v-if="canPlayVideo"
                                        class="w-[96rpx] h-[96rpx] rounded-full bg-[rgba(0,0,0,0.45)] flex items-center justify-center"
                                        @click="showVideo = true">
                                        <u-icon name="play-right-fill" color="#ffffff" :size="40"></u-icon>
                                    </view>
                                    <view
                                        v-else
                                        class="px-[24rpx] py-[12rpx] rounded-[16rpx] bg-[rgba(0,0,0,0.5)]">
                                        <text class="text-xs text-white font-medium">视频正在生成中…</text>
                                    </view>
                                </view>
                            </view>
                            <!-- 图文：material_url 逗号分隔多图 -->
                            <view
                                v-else-if="imageUrls.length"
                                class="grid grid-cols-3 gap-[8rpx] rounded-[24rpx] overflow-hidden">
                                <view
                                    v-for="(url, i) in imageUrls.slice(0, IMAGE_GRID_LIMIT)"
                                    :key="i"
                                    class="relative w-full h-[192rpx] overflow-hidden"
                                    @click="previewImages(i)">
                                    <image :src="url" class="w-full h-full" mode="aspectFill" />
                                    <view
                                        v-if="showImageMoreMask(i)"
                                        class="absolute inset-0 flex items-center justify-center bg-[rgba(0,0,0,0.5)]">
                                        <text class="text-[32rpx] font-bold text-white">
                                            +{{ imageUrls.length - IMAGE_GRID_LIMIT }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                            <view
                                v-else
                                class="h-[300rpx] rounded-[24rpx] bg-[#f3f4f6] flex items-center justify-center">
                                <u-icon name="photo" :size="48" color="#d1d5db"></u-icon>
                            </view>
                        </view>

                        <view v-if="item.material_title || item.material_subtitle">
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]">发布标题 & 文案</text>
                            <view class="bg-[#f9fafb] rounded-[20rpx] px-[24rpx] py-[20rpx]">
                                <text
                                    v-if="item.material_title"
                                    class="block text-sm font-bold text-[#1f2937] mb-[8rpx] break-all">
                                    {{ item.material_title }}
                                </text>
                                <text class="block text-xs text-[#4b5563] leading-[40rpx] break-all">
                                    {{ item.material_subtitle }}
                                </text>
                            </view>
                        </view>

                        <view v-if="tags.length">
                            <text class="block text-xs text-[#9ca3af] mb-[12rpx]"># 话题</text>
                            <view class="flex flex-wrap gap-[12rpx]">
                                <text
                                    v-for="tag in tags"
                                    :key="tag"
                                    class="text-xs text-primary bg-primary-light-9 rounded-[12rpx] px-[16rpx] py-[6rpx]">
                                    #{{ tag }}
                                </text>
                            </view>
                        </view>

                        <view class="flex items-center gap-[8rpx] text-[20rpx] text-[#9ca3af]">
                            <u-icon name="clock" :size="22" color="#9ca3af"></u-icon>
                            <text>发布时间：{{ item.publish_time || "-" }}</text>
                        </view>
                    </view>

                    <!-- 步骤截图（logs） -->
                    <view class="bg-white rounded-[32rpx] p-[28rpx]">
                        <text class="block text-xs text-[#9ca3af] mb-[16rpx]">步骤截图</text>
                        <scroll-view
                            v-if="steps.length"
                            scroll-x
                            class="whitespace-nowrap"
                            show-scrollbar="false"
                        >
                            <view class="inline-flex gap-[16rpx]">
                                <view
                                    v-for="(log, idx) in steps"
                                    :key="log.id || idx"
                                    class="inline-flex flex-col w-[180rpx] rounded-[20rpx] overflow-hidden border-[2rpx] border-[#f3f4f6] bg-white"
                                    @click="previewStep(log)"
                                >
                                    <view class="relative w-full h-[280rpx] bg-[#f3f4f6]">
                                        <image
                                            v-if="log.image"
                                            :src="log.image"
                                            mode="aspectFill"
                                            class="w-full h-full"
                                        />
                                        <view
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <u-icon
                                                name="photo"
                                                :size="40"
                                                color="#d1d5db"
                                            ></u-icon>
                                        </view>
                                        <text
                                            class="absolute top-[12rpx] left-[12rpx] text-[18rpx] font-bold text-white bg-[rgba(0,0,0,0.5)] rounded-full px-[12rpx] py-[2rpx]"
                                        >
                                            {{ idx + 1 }}
                                        </text>
                                    </view>
                                    <text
                                        class="block text-[20rpx] text-center text-[#374151] py-[12rpx] px-[8rpx] line-clamp-1"
                                    >
                                        {{ log.message }}
                                    </text>
                                </view>
                            </view>
                        </scroll-view>
                        <view
                            v-else
                            class="bg-[#f9fafb] rounded-[20rpx] py-[48rpx] flex flex-col items-center gap-[16rpx]"
                        >
                            <u-icon
                                :name="emptyStepsIcon"
                                :size="44"
                                :color="emptyStepsIconColor"
                            ></u-icon>
                            <text class="text-xs text-[#9ca3af]">{{ emptyStepsText }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>

    <video-preview v-model="showVideo" title="视频预览" :video-url="videoUrl" />
</template>

<script setup lang="ts">
import VideoPreview from '@/components/video-preview/video-preview.vue'
import type { PublishItem, PublishLogItem } from './publish-types'

// media_type: 1=短视频
const VIDEO_MEDIA_TYPE = 1
const IMAGE_GRID_LIMIT = 9

const props = defineProps<{
    modelValue: boolean
    item: PublishItem | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v)
})

const showVideo = ref(false)

// task_status: 0=待执行 1=执行中 2=已完成 3=失败
const isSuccess = computed(() => props.item?.task_status === 2)
const isFailed = computed(() => props.item?.task_status === 3)
const isRunning = computed(() => props.item?.task_status === 1)

const statusBarClass = computed(() => {
    if (isSuccess.value) return 'bg-success-light-9'
    if (isFailed.value) return 'bg-error-light-9'
    if (isRunning.value) return 'bg-primary-light-9'
    return 'bg-[#f3f4f6]'
})
const statusTextClass = computed(() => {
    if (isSuccess.value) return 'text-success'
    if (isFailed.value) return 'text-error'
    if (isRunning.value) return 'text-primary'
    return 'text-[#9ca3af]'
})
const statusIcon = computed(() => {
    if (isSuccess.value) return 'checkmark-circle'
    if (isFailed.value) return 'close-circle'
    return 'clock'
})
const statusIconColor = computed(() => {
    if (isSuccess.value) return '#52c41a'
    if (isFailed.value) return '#f56c6c'
    if (isRunning.value) return '#0065fb'
    return '#9ca3af'
})

const isVideo = computed(() => props.item?.media_type === VIDEO_MEDIA_TYPE)
// 视频：material_url 为单个视频地址；图文：逗号分隔多图
const videoUrl = computed(() => {
    if (!isVideo.value) return ""
    return String(props.item?.material_url || "").split(",")[0]?.trim() || ""
})
const canPlayVideo = computed(() => isVideo.value && !!videoUrl.value)

const imageUrls = computed(() => {
    if (isVideo.value) return [] as string[]
    const raw = String(props.item?.material_url || "").trim()
    const fromUrl = raw
        ? raw
              .split(",")
              .map((u) => u.trim())
              .filter(Boolean)
        : []
    if (fromUrl.length) return fromUrl
    return props.item?.pic ? [props.item.pic] : []
})

/** 超过 9 张时，最后一格显示「+N」蒙版 */
const showImageMoreMask = (index: number) =>
    index === IMAGE_GRID_LIMIT - 1 && imageUrls.value.length > IMAGE_GRID_LIMIT

const tags = computed<string[]>(() => {
    const raw = props.item?.material_tag as string[] | string | undefined
    if (Array.isArray(raw)) return raw.map(String).filter(Boolean)
    if (typeof raw === "string" && raw.trim()) {
        return raw
            .split(/[,，#\s]+/)
            .map((t) => t.trim())
            .filter(Boolean)
    }
    return []
})

const steps = computed<PublishLogItem[]>(() =>
    Array.isArray(props.item?.logs) ? props.item!.logs : []
)

// 无步骤截图时的占位文案/图标跟随发布状态
const emptyStepsText = computed(() => {
    if (isFailed.value) return "发布失败，未产生步骤截图"
    if (isSuccess.value) return "本次发布无步骤截图"
    if (isRunning.value) return "正在执行，步骤截图生成中…"
    return "发布未开始，执行时将实时记录每步截图"
})
const emptyStepsIcon = computed(() => {
    if (isFailed.value) return "close-circle"
    if (isSuccess.value) return "photo"
    return "clock"
})
const emptyStepsIconColor = computed(() => (isFailed.value ? "#f56c6c" : "#d1d5db"))

const previewImages = (index: number) => {
    const urls = imageUrls.value
    if (!urls.length) return
    uni.previewImage({
        urls,
        current: urls[Math.min(index, urls.length - 1)],
    })
}

const previewStep = (log: PublishLogItem) => {
    if (!log.image) return
    // 仅取有图的步骤参与预览，空图会导致小程序预览白屏；current 用索引定位，避免 URL 匹配失败
    const urls = steps.value.map((s) => s.image).filter((u): u is string => !!u)
    const current = Math.max(0, urls.indexOf(log.image))
    if (!urls.length) return
    uni.previewImage({ urls, current })
}
</script>
