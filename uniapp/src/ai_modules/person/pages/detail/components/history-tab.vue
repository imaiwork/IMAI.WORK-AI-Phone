<template>
    <view>
        <view class="bg-[#F3F4F6] rounded-[28rpx] p-[8rpx] flex mb-[24rpx]">
            <view
                v-for="tab in state.historyTabs"
                :key="tab.key"
                class="flex-1 py-[18rpx] rounded-[22rpx] text-center"
                :class="
                    state.activeHistoryTab === tab.key ? 'bg-white shadow-sm' : 'bg-[transparent]'
                "
                @click="actions.switchHistoryTab(tab.key)"
            >
                <text
                    class="text-xs font-semibold"
                    :class="
                        state.activeHistoryTab === tab.key ? 'text-[#1F2937]' : 'text-[#9CA3AF]'
                    "
                >
                    {{ tab.label }}
                </text>
            </view>
        </view>

        <template v-if="state.activeHistoryTab === 'videos'">
            <view v-if="state.videoList.length">
                <view v-for="group in state.videoList" :key="group.date" class="mb-[22rpx]">
                    <view class="flex items-center gap-[14rpx] py-[8rpx]">
                        <view
                            class="flex items-center gap-[10rpx] bg-[#EEF0F4] rounded-full px-[18rpx] py-[8rpx] shrink-0"
                        >
                            <view
                                class="w-[10rpx] h-[10rpx] rounded-full"
                                :class="group.date === '今天' ? 'bg-[#60A5FA]' : 'bg-[#9CA3AF]'"
                            ></view>
                            <text class="text-[20rpx] font-semibold text-[#4B5563]">{{
                                group.date
                            }}</text>
                        </view>
                        <view class="flex-1 h-[2rpx] bg-[#ECEEF3]"></view>
                    </view>

                    <view
                        v-for="item in group.items"
                        :key="item.video_setting_id || item.id"
                        class="bg-white rounded-[28rpx] overflow-hidden detail-card-shadow mb-[18rpx] active:opacity-90"
                        @click="actions.videoClick(item)"
                    >
                        <view class="flex gap-[24rpx] p-[24rpx]">
                            <view
                                class="relative w-[136rpx] h-[180rpx] rounded-[22rpx] overflow-hidden bg-[#F4F5F7] shrink-0"
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
                                    class="w-full h-full flex items-center justify-center bg-[#EEF3FF]"
                                >
                                    <u-icon
                                        :name="
                                            item.status === state.videoFailed
                                                ? 'close-circle'
                                                : 'video-camera'
                                        "
                                        :color="
                                            item.status === state.videoFailed
                                                ? '#EF4444'
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
                                <view class="flex items-center gap-[8rpx] mb-[10rpx]">
                                    <text
                                        class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-full"
                                        :class="state.getRecordStatusClass(item.status)"
                                    >
                                        {{ state.getRecordStatusLabel(item.status) }}
                                    </text>
                                    <text class="text-[20rpx] text-[#9CA3AF] ml-auto">
                                        {{ state.formatRecordTime(item.update_time) }}
                                    </text>
                                </view>
                                <text
                                    class="text-xs font-bold text-[#1F2937] leading-relaxed line-clamp-2"
                                >
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
                                <view class="flex items-center gap-[16rpx] mt-[20rpx]">
                                    <view
                                        v-if="item.status === state.videoFailed"
                                        class="flex items-center gap-[6rpx] text-[22rpx] text-primary"
                                        @click.stop="actions.retryRecord(item)"
                                    >
                                        <u-icon name="reload" color="#0065FB" size="22"></u-icon>
                                        <text>重试生成</text>
                                    </view>
                                    <view
                                        v-if="item.status === state.videoFailed"
                                        class="flex items-center gap-[6rpx] text-[22rpx] text-[#EF4444]"
                                        @click.stop="actions.viewFailReason(item)"
                                    >
                                        <u-icon
                                            name="info-circle"
                                            color="#EF4444"
                                            size="22"
                                        ></u-icon>
                                        <text>查看原因</text>
                                    </view>
                                    <view
                                        class="flex items-center gap-[6rpx] text-[22rpx] text-[#9CA3AF]"
                                        @click.stop="actions.deleteRecord(item.video_setting_id)"
                                    >
                                        <u-icon name="trash" color="#9CA3AF" size="22"></u-icon>
                                        <text>删除</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <empty v-else-if="!state.videoLoading" text="暂无生成视频记录" />
            <view class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading
                    v-if="state.videoLoading"
                    mode="circle"
                    size="28"
                    color="#999999"
                ></u-loading>
                <text v-if="state.videoLoading" class="text-xs text-[#9CA3AF]">加载中...</text>
                <text
                    v-else-if="state.videoFinished && state.videoList.length"
                    class="text-[22rpx] text-[#C4C9D4]"
                >
                    已加载全部
                </text>
            </view>
        </template>

        <template v-else>
            <view v-if="state.imageList.length">
                <view v-for="group in state.imageList" :key="group.date" class="mb-[22rpx]">
                    <view class="flex items-center gap-[14rpx] py-[8rpx]">
                        <view
                            class="flex items-center gap-[10rpx] bg-[#EEF0F4] rounded-full px-[18rpx] py-[8rpx] shrink-0"
                        >
                            <view
                                class="w-[10rpx] h-[10rpx] rounded-full"
                                :class="group.date === '今天' ? 'bg-[#60A5FA]' : 'bg-[#9CA3AF]'"
                            ></view>
                            <text class="text-[20rpx] font-semibold text-[#4B5563]">
                                {{ group.date }}
                            </text>
                        </view>
                        <view class="flex-1 h-[2rpx] bg-[#ECEEF3]"></view>
                    </view>
                    <view
                        v-for="item in group.items"
                        :key="item.id || item.record_id || item.create_time"
                        class="bg-white rounded-[28rpx] overflow-hidden detail-card-shadow mb-[18rpx] active:opacity-90"
                        @click="actions.imageClick(item)"
                    >
                        <view class="p-[28rpx]">
                            <!-- 图片区：1 张全宽 / 最多 3 张横排，超出第三张叠 +N -->
                            <view
                                v-if="state.getImageUrls(item).length === 1"
                                class="rounded-[20rpx] overflow-hidden h-[192rpx] mb-[20rpx] bg-[#F4F5F7]"
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
                                    class="relative flex-1 rounded-[16rpx] overflow-hidden h-[160rpx] bg-[#F4F5F7]"
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
                                class="rounded-[20rpx] overflow-hidden h-[192rpx] mb-[20rpx] bg-[#EEF3FF] flex items-center justify-center"
                            >
                                <u-icon name="photo" color="#0065FB" size="40"></u-icon>
                            </view>

                            <text
                                class="text-xs font-bold text-[#1F2937] leading-snug line-clamp-2"
                            >
                                {{ state.getImageTitle(item) }}
                            </text>

                            <view
                                v-if="state.getImageTags(item).length"
                                class="flex flex-wrap gap-[8rpx] mt-[12rpx]"
                            >
                                <text
                                    v-for="tag in state.getImageTags(item).slice(0, 4)"
                                    :key="tag"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-full bg-[#FDF2F8] text-[#EC4899]"
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
                                <text class="text-[20rpx] text-[#9CA3AF] ml-auto">
                                    {{ state.formatRecordTime(state.getImageRecordTime(item)) }}
                                </text>
                            </view>
                            <view class="flex items-center gap-[16rpx] mt-[16rpx]">
                                <view
                                    class="flex items-center gap-[6rpx] text-[22rpx] text-[#9CA3AF]"
                                    @click.stop="actions.deleteImageRecord(item.id)"
                                >
                                    <u-icon name="trash" color="#9CA3AF" size="22"></u-icon>
                                    <text>删除</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <empty v-else-if="!state.imageLoading" text="暂无自动生成的图片" />
            <view class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading
                    v-if="state.imageLoading"
                    mode="circle"
                    size="28"
                    color="#999999"
                ></u-loading>
                <text v-if="state.imageLoading" class="text-xs text-[#9CA3AF]">加载中...</text>
                <text
                    v-else-if="state.imageFinished && state.imageList.length"
                    class="text-[22rpx] text-[#C4C9D4]"
                >
                    已加载全部
                </text>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { formatAudioTime } from '@/utils/util'

interface TagConfig {
    label: string
    bg: string
    color: string
}

interface PlatformBadge {
    label: string
    bg: string
}

defineProps<{
    state: {
        activeHistoryTab: string
        historyTabs: readonly { key: string; label: string }[]
        videoList: { date: string; items: any[] }[]
        videoLoading: boolean
        videoFinished: boolean
        imageList: { date: string; items: any[] }[]
        imageLoading: boolean
        imageFinished: boolean
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
    }
}>()
</script>

<style scoped lang="scss">
.detail-card-shadow {
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}
</style>
