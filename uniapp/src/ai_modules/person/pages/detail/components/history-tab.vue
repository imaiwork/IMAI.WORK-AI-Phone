<template>
    <view>
        <view class="hist-tabs">
            <view
                v-for="tab in state.historyTabs"
                :key="tab.key"
                class="hist-tab active:opacity-70"
                :class="state.activeHistoryTab === tab.key ? 'hist-tab--active' : ''"
                @click="actions.switchHistoryTab(tab.key)"
            >
                <text class="hist-tab-label">
                    {{ tab.label }}
                </text>
            </view>
        </view>

        <view v-if="isCloudMode" class="hist-cloud-note">
            <text>云端生成的成片可在这里查看、下载，发布需要自己完成。</text>
        </view>

        <template v-if="state.activeHistoryTab === 'videos'">
            <view v-if="state.videoList.length">
                <view v-for="group in state.videoList" :key="group.date" class="mb-[24rpx]">
                    <view class="hist-date">
                        <view class="flex items-center gap-[10rpx] shrink-0">
                            <view
                                class="w-[10rpx] h-[10rpx] rounded-full"
                                :class="group.date === '今天' ? 'bg-primary' : 'bg-content'"
                            ></view>
                            <text class="text-xs font-semibold text-[#647184]">{{
                                group.date
                            }}</text>
                        </view>
                        <view class="flex-1 h-[2rpx] bg-[#E2E2E2]"></view>
                    </view>

                    <view
                        v-for="item in group.items"
                        :key="item.video_setting_id || item.id"
                        class="hist-record active:opacity-90"
                        @click="actions.videoClick(item)"
                    >
                        <view class="flex gap-[22rpx] p-[32rpx]">
                            <view
                                class="relative w-[136rpx] h-[180rpx] rounded-[22rpx] overflow-hidden bg-[#F2F3F5] shrink-0"
                            >
                                <image
                                    v-if="item.pic"
                                    :src="item.pic"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    lazy-load
                                ></image>
                                <view
                                    v-else
                                    class="w-full h-full flex items-center justify-center bg-[#E6EFFF]"
                                >
                                    <u-icon
                                        :name="
                                            item.status === state.videoFailed
                                                ? 'close-circle'
                                                : 'play-circle'
                                        "
                                        :color="
                                            item.status === state.videoFailed
                                                ? '#F56C6C'
                                                : '#0065FB'
                                        "
                                        size="40"
                                    ></u-icon>
                                </view>
                                <view
                                    v-if="item.status === state.videoSuccess"
                                    class="absolute inset-0 flex items-center justify-center"
                                    style="background: rgba(0, 0, 0, 0.08)"
                                >
                                    <view
                                        class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                        style="background: rgba(0, 0, 0, 0.45)"
                                    >
                                        <u-icon
                                            name="play-right-fill"
                                            color="#FFFFFF"
                                            size="30"
                                        ></u-icon>
                                    </view>
                                </view>
                                <text
                                    v-if="item.duration"
                                    class="absolute right-[8rpx] bottom-[8rpx] text-[18rpx] text-white px-[8rpx] py-[2rpx] rounded-[6rpx]"
                                    style="background: rgba(0, 0, 0, 0.6)"
                                >
                                    {{ formatAudioTime(Number(item.duration)) }}
                                </text>
                            </view>

                            <view class="flex-1 min-w-0">
                                <view class="flex flex-wrap items-center gap-[10rpx] mb-[10rpx]">
                                    <text
                                        class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-full"
                                        :class="state.getRecordStatusClass(item.status)"
                                    >
                                        {{ state.getRecordStatusLabel(item.status) }}
                                    </text>
                                    <text class="hist-meta ml-auto">
                                        {{ state.formatRecordTime(item.update_time) }}
                                    </text>
                                </view>
                                <text class="hist-item-title line-clamp-2">
                                    {{ item.name || item.title || 'AI自动生成视频' }}
                                </text>
                                <view class="flex flex-wrap gap-[8rpx] mt-[16rpx]">
                                    <text
                                        v-for="tag in state.getVideoTagList(item)"
                                        :key="tag.label"
                                        class="text-[18rpx] font-semibold px-[10rpx] py-[4rpx] rounded-full"
                                        :style="`background:${tag.bg};color:${tag.color}`"
                                    >
                                        {{ tag.label }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <view class="hist-actions hist-actions--video">
                            <view
                                v-if="item.status === state.videoFailed"
                                class="hist-action hist-action--secondary"
                                @click.stop="actions.retryRecord(item)"
                            >
                                <u-icon class="hist-control-content" name="reload" color="#0065FB" size="22"></u-icon>
                                <text class="hist-control-content">重试生成</text>
                            </view>
                            <view
                                v-if="item.status === state.videoFailed"
                                class="hist-action"
                                @click.stop="actions.viewFailReason(item)"
                            >
                                <u-icon name="info-circle" color="#0761D9" size="22"></u-icon>
                                <text>查看原因</text>
                            </view>
                            <view
                                v-if="item.status === state.videoSuccess"
                                class="hist-action"
                                @click.stop="actions.downloadVideo(item)"
                            >
                                <u-icon name="download" color="#0065FB" size="22"></u-icon>
                                <text>下载视频</text>
                            </view>
                            <view
                                v-if="item.status === state.videoSuccess && publishContent(item)"
                                class="hist-action"
                                @click.stop="actions.copyPublishCopy(item)"
                            >
                                <u-icon name="file-text" color="#0761D9" size="22"></u-icon>
                                <text>发布文案</text>
                            </view>
                            <view
                                v-if="item.status === state.videoSuccess"
                                class="hist-action"
                                @click.stop="actions.copyVideoText(item)"
                            >
                                <u-icon name="file-text" color="#0761D9" size="22"></u-icon>
                                <text>口播文案</text>
                            </view>
                            <view
                                class="hist-action"
                                @click.stop="actions.deleteRecord(item.video_setting_id)"
                            >
                                <u-icon name="trash" color="#0761D9" size="22"></u-icon>
                                <text>删除</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <resource-empty-state
                v-else-if="!state.videoLoading"
                icon="play-circle"
                title="还没有生成的视频"
                :description="
                    isCloudMode
                        ? '完成需要的配置后，生成结果和处理状态会显示在这里。'
                        : state.nextRunLabel
                        ? `${state.nextRunLabel} 开始第一次自动生成，成片会出现在这里。`
                        : '配置齐了之后会按发布时段自动开始，成片会出现在这里。'
                "
            />
            <view class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading
                    v-if="state.videoLoading"
                    mode="circle"
                    size="28"
                    color="#999999"
                ></u-loading>
                <text v-if="state.videoLoading" class="hist-meta">加载中...</text>
                <text v-else-if="state.videoFinished && state.videoList.length" class="hist-meta">
                    已加载全部
                </text>
            </view>
        </template>

        <template v-else>
            <view v-if="state.imageList.length">
                <view v-for="group in state.imageList" :key="group.date" class="mb-[24rpx]">
                    <view class="hist-date">
                        <view class="flex items-center gap-[10rpx] shrink-0">
                            <view
                                class="w-[10rpx] h-[10rpx] rounded-full"
                                :class="group.date === '今天' ? 'bg-primary' : 'bg-content'"
                            ></view>
                            <text class="text-xs font-semibold text-[#647184]">
                                {{ group.date }}
                            </text>
                        </view>
                        <view class="flex-1 h-[2rpx] bg-[#E2E2E2]"></view>
                    </view>
                    <view
                        v-for="item in group.items"
                        :key="item.id || item.record_id || item.create_time"
                        class="hist-record active:opacity-90"
                        @click="actions.imageClick(item)"
                    >
                        <view class="p-[32rpx]">
                            <!-- 图片区：1 张全宽 / 最多 3 张横排，超出第三张叠 +N -->
                            <view
                                v-if="state.getImageUrls(item).length === 1"
                                class="rounded-[20rpx] overflow-hidden h-[192rpx] mb-[20rpx] bg-[#F2F3F5]"
                            >
                                <image
                                    :src="state.getImageUrls(item)[0]"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    lazy-load
                                ></image>
                            </view>
                            <view
                                v-else-if="state.getImageUrls(item).length > 1"
                                class="flex gap-[8rpx] mb-[20rpx]"
                            >
                                <view
                                    v-for="(url, index) in state.getImageUrls(item).slice(0, 3)"
                                    :key="`${url}-${index}`"
                                    class="relative flex-1 rounded-[16rpx] overflow-hidden h-[160rpx] bg-[#F2F3F5]"
                                >
                                    <image
                                        :src="url"
                                        class="w-full h-full"
                                        mode="aspectFill"
                                        lazy-load
                                    ></image>
                                    <view
                                        v-if="index === 2 && state.getImageUrls(item).length > 3"
                                        class="absolute inset-0 flex items-center justify-center"
                                        style="background: rgba(0, 0, 0, 0.5)"
                                    >
                                        <text class="text-white text-sm font-bold">
                                            +{{ state.getImageUrls(item).length - 3 }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                            <view
                                v-else
                                class="rounded-[20rpx] overflow-hidden h-[192rpx] mb-[20rpx] bg-[#E6EFFF] flex items-center justify-center"
                            >
                                <u-icon name="photo" color="#0065FB" size="40"></u-icon>
                            </view>

                            <text class="hist-item-title line-clamp-2">
                                {{ state.getImageTitle(item) }}
                            </text>

                            <view
                                v-if="state.getImageTags(item).length"
                                class="flex flex-wrap gap-[8rpx] mt-[12rpx]"
                            >
                                <text
                                    v-for="tag in state.getImageTags(item).slice(0, 4)"
                                    :key="tag"
                                    class="text-xs px-[12rpx] py-[4rpx] rounded-[10rpx] bg-primary-light-9 text-primary-dark-3"
                                >
                                    {{ tag }}
                                </text>
                            </view>

                            <view class="flex items-center gap-[12rpx] mt-[16rpx]">
                                <text
                                    v-if="state.getImagePlatformBadge(item)"
                                    class="text-[20rpx] font-bold text-white px-[12rpx] py-[4rpx] rounded-[8rpx]"
                                    :style="`background:${state.getImagePlatformBadge(item)?.bg}`"
                                >
                                    {{ state.getImagePlatformBadge(item)?.label }}
                                </text>
                                <text class="hist-meta ml-auto">
                                    {{ state.formatRecordTime(state.getImageRecordTime(item)) }}
                                </text>
                            </view>
                            <view class="hist-actions">
                                <view
                                    class="hist-action"
                                    @click.stop="actions.deleteImageRecord(item.id)"
                                >
                                    <u-icon name="trash" color="#0761D9" size="22"></u-icon>
                                    <text>删除</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <resource-empty-state
                v-else-if="!state.imageLoading"
                icon="photo"
                title="还没有生成的图片"
                description="图文内容会在发布图文的人设里出现，视频类人设这里通常是空的。"
            />
            <view class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading
                    v-if="state.imageLoading"
                    mode="circle"
                    size="28"
                    color="#999999"
                ></u-loading>
                <text v-if="state.imageLoading" class="hist-meta">加载中...</text>
                <text v-else-if="state.imageFinished && state.imageList.length" class="hist-meta">
                    已加载全部
                </text>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { formatAudioTime } from '@/utils/util'
import { isCloudRunMode } from '@/ai_modules/person/enums'
import ResourceEmptyState from './resource-empty-state.vue'

interface TagConfig {
    label: string
    bg: string
    color: string
}

interface PlatformBadge {
    label: string
    bg: string
}

const props = defineProps<{
    state: {
        runMode: number
        activeHistoryTab: string
        historyTabs: readonly { key: string; label: string }[]
        videoList: { date: string; items: any[] }[]
        videoLoading: boolean
        videoFinished: boolean
        imageList: { date: string; items: any[] }[]
        imageLoading: boolean
        imageFinished: boolean
        nextRunLabel: string
        videoFailed: number
        videoSuccess: number
        getRecordStatusLabel: (status: number) => string
        getRecordStatusClass: (status: number) => string
        getVideoTagList: (item: any) => TagConfig[]
        getImageUrls: (item: any) => string[]
        getImageTags: (item: any) => string[]
        getImageTitle: (item: any) => string
        getImageRecordTime: (item: any) => string
        getImagePlatformBadge: (item: any) => PlatformBadge | null
        formatRecordTime: (time: string) => string
    }
    actions: {
        switchHistoryTab: (tab: string) => void
        videoClick: (item: any) => void
        imageClick: (item: any) => void
        deleteRecord: (id: string) => void
        deleteImageRecord: (id: number | string) => void
        viewFailReason: (item: any) => void
        retryRecord: (item: any) => void
        downloadVideo: (item: any) => void
        copyPublishCopy: (item: any) => void
        copyVideoText: (item: any) => void
    }
}>()

const isCloudMode = computed(() => isCloudRunMode(props.state.runMode))

const publishContent = (item: any) =>
    String(item?.publish_title || "").trim() || String(item?.publish_content || "").trim()
</script>

<style scoped lang="scss">
.hist-tabs {
    @apply relative flex mb-[16rpx];
}

.hist-tabs::before {
    content: '';
    @apply absolute inset-x-0 top-[10rpx] bottom-[10rpx] rounded-[16rpx] bg-[#E9EDF3] pointer-events-none;
}

.hist-tab {
    @apply relative box-border flex-1 min-w-0 min-h-[88rpx] flex items-center justify-center px-[14rpx];
}

.hist-tab-label {
    @apply relative text-xs font-medium leading-[1.4] text-[#607087];
}

.hist-tab--active::before {
    content: '';
    @apply absolute inset-x-[4rpx] top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-white pointer-events-none;
}

.hist-tab--active .hist-tab-label {
    @apply font-[650] text-[#263B59];
}

.hist-cloud-note {
    @apply mb-[24rpx] text-xs leading-[1.65] text-[#647184];
}

.hist-meta {
    @apply text-xs leading-[1.65] text-[#647184];
}

.hist-item-title {
    @apply text-base font-[620] leading-[1.55] text-[#26364E];
}

.hist-date {
    @apply flex items-center gap-[16rpx] pt-[8rpx] pb-[20rpx];
}

.hist-record {
    @apply bg-white rounded-[36rpx] overflow-hidden mb-[24rpx];
}

.hist-actions {
    @apply flex flex-wrap items-center gap-[8rpx];
}

.hist-actions--video {
    @apply flex-nowrap px-[20rpx] pb-[32rpx] gap-[4rpx];
}

.hist-actions--video .hist-action {
    @apply px-[6rpx] gap-[6rpx];
}

.hist-action {
    @apply relative box-border min-h-[88rpx] min-w-[88rpx] inline-flex items-center justify-center gap-[8rpx] px-[10rpx]
        text-xs font-medium leading-[1.4]
        text-[#0761D9] active:opacity-70;
}

.hist-action--secondary {
    @apply px-[16rpx] text-[#075ECF];
}

.hist-action--secondary::before {
    content: '';
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-[#EDF4FF] pointer-events-none;
}

.hist-control-content {
    @apply relative;
}
</style>
