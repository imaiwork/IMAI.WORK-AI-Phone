<template>
    <view class="report-page">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            title="我的练习报告"
            title-bold>
        </u-navbar>
        <view class="mt-4 mx-4 bg-[#FAFAFA] p-3 rounded-xl relative">
            <view class="absolute top-4 right-6" v-if="workbenchData.pending_scene_count > 0">
                <view class="flex items-center gap-2">
                    <view class="flex items-center">
                        <view
                            class="w-[48rpx] h-[48rpx] p-1 rounded-full bg-white -ml-[26rpx]"
                            v-for="(item, index) in workbenchData.scene_logos"
                            :key="index">
                            <image :src="item" class="w-full h-full rounded-full"></image>
                        </view>
                    </view>
                    <view class="text-xs text-[#C6C5CA]">
                        <text class="text-[#8EC0FA]">{{ workbenchData.pending_scene_count }}</text
                        >个场景等待学习
                    </view>
                </view>
            </view>
            <view class="card">
                <view class="flex items-center gap-1 pt-3 pl-4">
                    <image
                        src="@/ai_modules/ladder_player/static/images/common/beautify_img1.png"
                        class="w-[42rpx] h-[42rpx]"></image>
                    <view class="font-bold text-xs">
                        已创建<text class="text-primary">{{ workbenchData.scene_count }}</text
                        >个场景
                    </view>
                </view>
                <view class="flex items-center gap-2 mt-3 mx-3">
                    <view class="h-[162rpx] w-[242rpx] flex flex-col items-center justify-center number-card">
                        <view class="text-[48rpx] font-bold">
                            {{ workbenchData.practice_scene_count }}
                        </view>
                        <view class="text-[#4F5259] text-xs mt-2">总共练习次数</view>
                    </view>
                    <view class="flex flex-col items-center justify-center flex-1">
                        <view class="text-[36rpx] font-bold">
                            {{ workbenchData.scene_count }}
                        </view>
                        <view class="text-[#4F5259] text-xs mt-2">场景数</view>
                    </view>
                    <view class="flex flex-col items-center justify-center flex-1">
                        <view class="text-[36rpx] font-bold">
                            {{ workbenchData.average_score }}
                        </view>
                        <view class="text-[#4F5259] text-xs mt-2">评价分数</view>
                    </view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-4">
            <z-paging v-model="lists" ref="pagingRef" :fixed="false" :safe-area-inset-bottom="true" @query="queryList">
                <view class="lists-card">
                    <view v-for="(item, index) in lists" :key="index" class="card-item" @click="handleClick(item)">
                        <view class="text-white relative z-10">
                            <view class="font-bold">
                                {{ item.scene_name }}
                            </view>
                            <view class="font-bold text-xl mt-1">
                                {{ formatDurationTime(item.duration) }}
                            </view>
                            <view class="text-xs mt-[12rpx]">{{ formatStartTime(item.start_time) }}</view>
                        </view>
                        <view class="flex items-center gap-2 flex-shrink-0" v-if="item.status == 2">
                            <view class="font-bold text-white"> {{ item.total_score || 0 }}分 </view>
                            <view>
                                <u-icon name="arrow-right" color="#ffffff"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center gap-2 flex-shrink-0" v-else-if="item.status == 3">
                            <view class="font-bold text-white"> 分析失败 </view>
                            <view @click.stop="handleRetry(item.id)">
                                <u-icon name="reload" color="#ffffff"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center justify-center w-[25%]" v-else>
                            <view class="loader-box">
                                <image
                                    src="@/ai_modules/ladder_player/static/images/common/loader.png"
                                    class="w-[64rpx] h-[64rpx]"></image>
                            </view>
                        </view>
                        <view class="absolute h-full right-[200rpx] top-0 z-[0]">
                            <image
                                src="@/ai_modules/ladder_player/static/images/common/katong.png"
                                class="h-[172rpx] w-[168rpx]"></image>
                        </view>
                        <view class="absolute top-0 right-0 z-[888]" v-if="item.status == 2">
                            <view class="p-2" @click.stop="handleMore(item.id)">
                                <image
                                    src="@/ai_modules/ladder_player/static/icons/more_white.svg"
                                    class="w-[32rpx] h-[32rpx]"></image>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty text="暂无场景" />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import { lpAnalysisData, lpAnalysisLists, lpAnalysisRetry, lpAnalysisDelete } from "@/api/ladder_player";

const workbenchData = reactive<any>({
    average_score: 0,
    pending_scene_count: 0,
    practice_scene_count: 0,
    scene_count: 0,
    total_scene_count: 0,
    scene_logos: [],
});

const getWorkbenchData = async () => {
    const data = await lpAnalysisData();
    workbenchData.average_score = data?.average_score || 0;
    workbenchData.pending_scene_count = data?.pending_scene_count || 0;
    workbenchData.practice_scene_count = data?.practice_scene_count || 0;
    workbenchData.scene_count = data?.scene_count || 0;
    workbenchData.total_scene_count = data?.total_scene_count || 0;
    workbenchData.scene_logos = data?.scene_logos || [];
};

const lists = ref<any[]>([]);

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await lpAnalysisLists({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleClick = (item: any) => {
    if (item.status == 2) {
        uni.$u.route({
            url: "/ai_modules/ladder_player/pages/report_detail/report_detail",
            params: {
                id: item.id,
            },
        });
    } else if (item.status == 3) {
        uni.$u.toast("数据分析失败，不可查看");
    } else {
        uni.$u.toast("数据分析中，请稍后再试");
    }
};

const handleMore = (id: string) => {
    let itemList = ["删除"];
    uni.showActionSheet({
        itemList,
        success: (res) => {
            if (res.tapIndex == 0) {
                handleDelete(id);
            }
        },
    });
};

const handleDelete = async (id: string) => {
    uni.showModal({
        title: "删除",
        content: "确定要删除吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中",
                    mask: true,
                });
                try {
                    await lpAnalysisDelete({
                        id,
                    });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    pagingRef.value?.reload();
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

const handleRetry = async (id: string) => {
    uni.showModal({
        title: "重新分析",
        content: "确定要重新分析吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "重新分析中",
                    mask: true,
                });
                try {
                    await lpAnalysisRetry({
                        id,
                    });
                    uni.hideLoading();
                    uni.showToast({
                        title: "重新分析成功",
                        icon: "none",
                        duration: 3000,
                    });
                    pagingRef.value?.reload();
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "重新分析失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

const formatDurationTime = (time: any) => {
    if (time > 0) {
        const minutes = Math.floor(time / 60);
        const remainingSeconds = time % 60;
        return `${minutes}"${remainingSeconds}'`;
    }
    return "0";
};

const formatStartTime = (time: any) => {
    const date = new Date(time);
    return uni.$u.timeFormat(date, "yyyy-mm-dd hh:MM");
};

onShow(() => {
    getWorkbenchData();
});
</script>

<style scoped lang="scss">
.report-page {
    background: linear-gradient(180deg, rgba(223, 231, 252, 1) 0.43%, rgba(247, 255, 252, 0) 100%);

    @apply h-screen flex flex-col;
}
.card {
    background: url("@/ai_modules/ladder_player/static/images/common/card.png") no-repeat center center / cover;
    @apply h-[288rpx];
}
.number-card {
    background: url("@/ai_modules/ladder_player/static/images/common/number_card.png") no-repeat center center / 100%;
}
.lists-card {
    @apply flex flex-col gap-3 mx-4;
    .card-item {
        background: linear-gradient(
            117.59deg,
            rgba(95, 144, 217, 1) 0%,
            rgba(148, 198, 255, 1) 38.9%,
            rgba(110, 176, 248, 1) 74.98%,
            rgba(113, 165, 248, 1) 100%
        );
        @apply rounded-lg px-4 py-3 relative flex justify-between;
    }
}
.loader-box {
    line-height: 0;
    animation: rotate 1s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
