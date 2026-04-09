<template>
    <view class="min-h-screen bg-[#F6F7F9] pb-[env(safe-area-inset-bottom)]">
        <view class="flex flex-col pb-6">
            <view v-for="(group, groupIndex) in videoList" :key="groupIndex" class="mb-3">
                <view class="flex items-center gap-[12rpx] px-[30rpx] py-[20rpx]">
                    <view class="w-[6rpx] h-[22rpx] rounded-full bg-[#D1D5DB]"></view>
                    <text class="text-[24rpx] text-[#9CA3AF] font-medium">{{ group.date }}</text>
                    <view class="flex-1 h-[1rpx] bg-[#EEEEEE] ml-1"></view>
                </view>

                <view class="grid grid-cols-3 gap-[3rpx] px-[3rpx]">
                    <view
                        v-for="item in group.items"
                        :key="item.id"
                        class="relative aspect-[3/4] bg-[#F4F5F7] overflow-hidden rounded-[4rpx]">
                        <template v-if="item.status === VideoStatus.videoSuccess">
                            <image
                                :src="item.pic"
                                class="w-full h-full object-cover"
                                mode="aspectFill"
                                :lazy-load="true">
                            </image>
                            <view
                                class="absolute bottom-0 left-0 w-full h-[120rpx]"
                                style="background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent)"></view>
                            <text
                                class="absolute bottom-[14rpx] left-[14rpx] text-white text-[22rpx] font-medium drop-shadow z-10">
                                {{ formatAudioTime(item.duration) }}
                            </text>
                            <view
                                class="absolute inset-0 flex items-center justify-center z-10"
                                @click.stop="handleVideoClick(item)">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-full bg-[#ffffff]/25 border border-solid border-[#ffffff]/50 flex items-center justify-center pl-[4rpx]">
                                    <u-icon name="play-right-fill" color="#ffffff" size="30"></u-icon>
                                </view>
                            </view>
                            <view
                                class="absolute top-[10rpx] right-[10rpx] z-20 w-[44rpx] h-[44rpx] rounded-full bg-[#000000]/40 flex items-center justify-center"
                                @click.stop="handleDelete(item.video_setting_id)">
                                <u-icon name="trash" color="#ffffff" size="22"></u-icon>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.videoFailed">
                            <view
                                class="w-full h-full flex flex-col items-center justify-center bg-[#FFF5F5] gap-y-[12rpx] p-[20rpx]">
                                <view class="relative">
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-full bg-[#FEE2E2] flex items-center justify-center">
                                        <u-icon name="close-circle-fill" color="#F87171" size="40"></u-icon>
                                    </view>
                                    <view
                                        class="absolute inset-[-6rpx] rounded-full border-[2rpx] border-[#FCA5A5]/50"></view>
                                </view>
                                <text class="text-[22rpx] text-[#EF4444] font-semibold">生成失败</text>
                                <view
                                    class="flex items-center gap-[6rpx] bg-[#FEE2E2] px-[16rpx] py-[6rpx] rounded-full active:bg-[#FECACA]"
                                    @click.stop="handleDelete(item.video_setting_id)">
                                    <u-icon name="trash" color="#EF4444" size="18"></u-icon>
                                    <text class="text-[20rpx] text-[#EF4444] font-medium">删除</text>
                                </view>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.pending">
                            <view
                                class="w-full h-full bg-[#F8F9FB] flex flex-col items-center justify-center gap-y-[14rpx]">
                                <view
                                    class="w-[68rpx] h-[68rpx] rounded-full bg-[#E9EBF0] flex items-center justify-center">
                                    <u-icon name="clock" color="#9CA3AF" size="32"></u-icon>
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF]">等待生成</text>
                                <view
                                    class="absolute top-[10rpx] right-[10rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#00000010] flex items-center justify-center active:bg-[#00000020]"
                                    @click.stop="handleDelete(item.video_setting_id)">
                                    <u-icon name="trash" color="#9CA3AF" size="20"></u-icon>
                                </view>
                            </view>
                        </template>

                        <template v-else-if="item.status === VideoStatus.videoQuery">
                            <view
                                class="w-full h-full bg-[#EEF3FF] flex flex-col items-center justify-center gap-y-[16rpx]">
                                <view class="relative w-[72rpx] h-[72rpx]">
                                    <view
                                        class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[#D0DEFF]"></view>
                                    <view
                                        class="absolute inset-0 rounded-full border-[4rpx] border-solid border-[transparent] border-t-primary animate-spin"></view>
                                    <view class="absolute inset-0 flex items-center justify-center">
                                        <u-icon name="video-camera" color="#0065fb" size="26"></u-icon>
                                    </view>
                                </view>
                                <text class="text-[22rpx] text-primary font-medium">生成中...</text>
                                <view class="flex items-center gap-[8rpx]">
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-100 animate-bounce"
                                        style="animation-delay: 0ms"></view>
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-70 animate-bounce"
                                        style="animation-delay: 150ms"></view>
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full bg-primary opacity-40 animate-bounce"
                                        style="animation-delay: 300ms"></view>
                                </view>
                            </view>
                        </template>
                    </view>
                </view>
            </view>

            <view
                v-if="!loading && videoList.length === 0"
                class="flex flex-col items-center justify-center py-[160rpx] gap-[24rpx]">
                <view class="w-[120rpx] h-[120rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center">
                    <u-icon name="video-camera" color="#D1D5DB" size="56"></u-icon>
                </view>
                <text class="text-[26rpx] text-[#9CA3AF]">暂无生成记录</text>
            </view>

            <view class="flex items-center justify-center py-[24rpx] gap-[12rpx]">
                <block v-if="loading">
                    <u-loading mode="circle" size="28" color="#999999"></u-loading>
                    <text class="text-[24rpx] text-[#9ca3af] ml-2">加载中...</text>
                </block>
                <block v-else-if="finished && videoList.length > 0">
                    <view class="h-[2rpx] w-[80rpx] bg-[#E5E7EB]"></view>
                    <text class="text-[22rpx] text-[#C4C9D4] mx-3">已加载全部</text>
                    <view class="h-[2rpx] w-[80rpx] bg-[#E5E7EB]"></view>
                </block>
            </view>
        </view>
    </view>

    <video-preview-v2 :show="showVideoPreview" :video-url="videoUrl" @update:show="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getGenerateRecordList } from "@/api/person";
import { deleteShanjianTaskRecord } from "@/api/digital_human";
import { formatAudioTime } from "@/utils/util";

enum VideoStatus {
    pending = 0,
    videoQuery = 1,
    videoFailed = 2,
    videoSuccess = 3,
}

const personId = ref<string>("");

const videoList = ref<{ date: string; items: any[] }[]>([]);
const total = ref(0);
const loading = ref(false);
const finished = ref(false);

const queryParams = reactive({
    page_no: 1,
    page_size: 20,
    persona_id: "",
});

const showVideoPreview = ref<boolean>(false);
const videoUrl = ref<string>("");

const formatDateLabel = (dateStr: string): string => {
    if (!dateStr) return "未知日期";
    const today = new Date();
    const target = new Date(dateStr);
    const toDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    const diffDays = Math.round((toDay(today) - toDay(target)) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return "今天";
    if (diffDays === 1) return "昨天";
    if (diffDays === 2) return "前天";
    const y = target.getFullYear();
    const m = String(target.getMonth() + 1).padStart(2, "0");
    const d = String(target.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
};

const mergeIntoGroups = (lists: any[]) => {
    lists.forEach((item) => {
        const rawDate = item.update_time || "";
        const label = formatDateLabel(rawDate);
        const existing = videoList.value.find((g) => g.date === label);
        if (existing) {
            existing.items.push(item);
        } else {
            videoList.value.push({ date: label, items: [item] });
        }
    });
};

const getLists = async () => {
    if (loading.value || finished.value) return;
    try {
        loading.value = true;
        const { lists, count } = await getGenerateRecordList(queryParams);
        mergeIntoGroups(lists || []);
        total.value = count || 0;
        const loadedCount = videoList.value.reduce((sum, g) => sum + g.items.length, 0);
        if (loadedCount >= total.value) {
            finished.value = true;
        }
    } catch (error) {
        console.error("获取生成记录失败：", error);
        finished.value = true;
    } finally {
        loading.value = false;
    }
};

const handleVideoClick = (item: any) => {
    if (item.status !== VideoStatus.videoSuccess) return;
    showVideoPreview.value = true;
    videoUrl.value = item.video_result_url;
};

const handleDelete = (id: string) => {
    uni.showModal({
        title: "删除记录",
        content: "确定删除该生成记录吗？删除后无法恢复",
        confirmColor: "#EF4444",
        confirmText: "删除",
        cancelText: "取消",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteShanjianTaskRecord({ id });
                    // 从分组中移除该条目
                    videoList.value = videoList.value
                        .map((group) => ({
                            ...group,
                            items: group.items.filter((item) => item.video_setting_id !== id),
                        }))
                        .filter((group) => group.items.length > 0);
                    total.value = Math.max(0, total.value - 1);
                    uni.hideLoading();
                    uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({ title: error?.message || "删除失败", icon: "none", duration: 3000 });
                }
            }
        },
    });
};

onLoad((options: any) => {
    personId.value = options?.id || "";
    queryParams.persona_id = personId.value;
    getLists();
});

onReachBottom(() => {
    if (loading.value || finished.value) return;
    queryParams.page_no += 1;
    getLists();
});
</script>
