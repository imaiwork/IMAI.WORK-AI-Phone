<template>
    <view class="h-screen flex flex-col bg-white pt-2" v-if="!loading">
        <view class="px-4">
            <view class="font-bold">{{ detailData.name }}</view>
            <view class="text-[22rpx] text-[#0000004d] mt-1">{{ detailData.create_time }}</view>
        </view>
        <view class="px-[8rpx] mx-4 mt-4 bg-[#F1F2F5] rounded-[16rpx]">
            <view class="grid grid-cols-2 h-[84rpx] relative">
                <view
                    v-for="(item, index) in ['生成记录', '素材记录']"
                    :key="index"
                    class="type-item"
                    :class="{ active: index == currIndex }"
                    @click="currIndex = index">
                    {{ item }}
                </view>
                <view class="tab-slider" :style="{ transform: `translateX(${currIndex * 100}%)` }"></view>
            </view>
        </view>
        <view class="grow min-h-0 mt-4">
            <template v-if="currIndex == 0">
                <scroll-view scroll-y class="h-full" v-if="[3, 5].includes(detailData.status) && currIndex == 0">
                    <view class="px-4 grid grid-cols-3 gap-2 pb-[100rpx]">
                        <view
                            v-for="(image, imageIndex) in currIndex == 0 ? detailData.puzzle_url : detailData.material"
                            :key="imageIndex"
                            class="w-full"
                            @click="previewImage(detailData.puzzle_url, imageIndex)">
                            <image :src="image" class="h-[220rpx] w-full rounded-[20rpx]" mode="aspectFill"></image>
                        </view>
                    </view>
                </scroll-view>
                <view v-else class="mt-2 px-4">
                    <view
                        class="bg-[#F1F2F5] rounded-[16rpx] h-[156rpx] w-full flex flex-col items-center justify-center">
                        <view v-if="[0, 1, 2].includes(detailData.status)" class="flex items-center gap-x-2">
                            <view class="gen-loading"></view>
                            <view class="gen-loading-text">拼图中...</view>
                        </view>
                        <template v-else-if="detailData.status == 5" class="">
                            <view class="flex items-center gap-x-2">
                                <image src="@/packages/static/icons/stop.svg" class="w-[28rpx] h-[28rpx]"></image>
                                <text class="text-[#F12A46] font-bold">拼图失败</text>
                            </view>
                            <view class="text-center text-[20rpx] text-[#00000066] w-[70%] mt-1">{{
                                detailData.remark
                            }}</view>
                        </template>
                    </view>
                </view>
            </template>
            <scroll-view scroll-y class="h-full" v-if="currIndex == 1">
                <view class="px-4 grid grid-cols-3 gap-2 pb-[100rpx]">
                    <view
                        v-for="(image, imageIndex) in detailData.material"
                        :key="imageIndex"
                        class="w-full"
                        @click="previewImage(detailData.material, imageIndex)">
                        <image :src="image" class="h-[220rpx] w-full rounded-[20rpx]" mode="aspectFill"></image>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="px-4 pt-2 pb-4 flex items-center justify-between">
            <view class="flex items-center gap-x-2" @click="handleDelete">
                <image src="/static/images/icons/delete.svg" class="w-[28rpx] h-[28rpx]"></image>
                <text class="text-[#0000004d]">删除</text>
            </view>
            <view
                class="rounded-[16rpx] w-[450rpx] h-[90rpx] flex items-center justify-center bg-black text-white font-bold text-[30rpx]"
                @click="handleCreate"
                >创建发布图文</view
            >
        </view>
    </view>
    <popup-bottom
        v-model="showPublishSetting"
        title="设置发布图组数量"
        height="40%"
        @close="showPublishSetting = false">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="grow min-h-0 flex flex-col items-center justify-center">
                    <picker-view
                        :value="groupValue"
                        indicator-style="height: 100rpx;"
                        indicator-class="font-bold"
                        @change="changeGroupValue">
                        <picker-view-column>
                            <view
                                v-for="(item, index) in groups"
                                :key="index"
                                class="h-[100rpx] flex items-center justify-center"
                                >生成{{ item }}组</view
                            >
                        </picker-view-column>
                    </picker-view>
                </view>
                <view class="flex justify-around p-4 border-[0] border-t-[1rpx] border-solid border-[rgba(0,0,0,0.03)]">
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-[#F3F3F3] text-[30rpx] text-[#00000080] font-bold"
                        @click="showPublishSetting = false"
                        >取消</view
                    >
                    <view
                        class="w-[180rpx] h-[76rpx] flex items-center justify-center rounded-[10rpx] bg-primary text-[30rpx] text-white font-bold"
                        @click="toPublish"
                        >确定</view
                    >
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getPuzzleTaskDetail, deletePuzzleTask, getPuzzleTaskResultList } from "@/api/drawing";
import usePolling from "@/hooks/usePolling";

const loading = ref(true);
const detailId = ref("");
const detailData = ref<any>({});

const showPublishSetting = ref(false);
const groups = [3, 5, 10, 15, 20, 30];
const groupValue = ref([Math.ceil(groups.length / 2)]);
const currIndex = ref(0);
const previewImage = (urls: any, index: number) => {
    uni.previewImage({
        urls: urls,
        current: index,
    });
};

const handleDelete = () => {
    uni.showModal({
        title: "提示",
        content: "删除后无法找回，是否确认删除？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    await deletePuzzleTask({ id: detailId.value });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    uni.navigateBack();
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error,
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};

const changeGroupValue = (e: any) => {
    const { value } = e.detail;
    groupValue.value = value;
};

const handleCreate = () => {
    if (![3, 5].includes(detailData.value.status)) {
        uni.$u.toast("拼图未生成完成");
        return;
    } else if (detailData.value.status == 4) {
        uni.$u.toast("拼图生成失败");
        return;
    }
    showPublishSetting.value = true;
};

const toPublish = () => {
    const count = groups[groupValue.value[0]];
    uni.$u.route({
        url: `/ai_modules/device/pages/create_task/create_task`,
        params: {
            type: 2,
            source: "puzzle",
            id: detailId.value,
            count: JSON.stringify(count),
        },
    });
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const res = await getPuzzleTaskDetail({ id: detailId.value });
        detailData.value = res;
        await getResult();
        if (![3, 5].includes(detailData.value.status)) {
            start();
        }
    } finally {
        loading.value = false;
        uni.hideLoading();
    }
};

const getResult = async () => {
    const { lists } = await getPuzzleTaskResultList({ puzzle_setting_id: detailId.value });
    if (lists.length > 0) {
        detailData.value.puzzle_url = lists.reduce((acc: any, curr: any) => {
            acc.push(...curr.puzzle_url);
            return acc;
        }, []);
        if ([3, 5].includes(detailData.value.status)) {
            end();
        }
    }
};

const { start, end } = usePolling(getResult, {
    time: 2000,
});

onLoad((options: any) => {
    detailId.value = options.id;
    getDetail();
});

onUnload(() => {
    end();
});
</script>

<style scoped lang="scss">
picker-view {
    width: 100%;
    height: 100%;
}

.type-item {
    @apply w-full flex items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500;
    &.active {
        @apply text-black font-bold relative;
    }
}

.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-[#F9FAFB] absolute top-[4rpx] left-0 transition-all duration-500;
    &::after {
        content: "";
        @apply absolute bottom-0 w-[20%] h-[4rpx] bg-black;
        // 让线居中
        left: 0;
        right: 0;
        margin: auto;
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
@keyframes text-gradient-flow {
    0% {
        background-position: 0% 50%;
    }
    100% {
        background-position: 100% 50%;
    }
}
.gen-loading {
    @apply w-[40rpx] h-[40rpx] rounded-full;
    background: linear-gradient(90deg, #9879df 0%, #706fd1 34.73%, #277ef2 73.61%, #2adbc8 101%);
    position: relative;
    animation: spin 1.2s linear infinite;
    &::before {
        content: "";
        position: absolute;
        top: 4rpx;
        left: 4rpx;
        right: 4rpx;
        bottom: 4rpx;
        background: #f1f2f5;
        border-radius: 50%;
    }
}
.gen-loading-text {
    @apply font-bold;
    background: linear-gradient(90deg, #9879df 0%, #706fd1 34.73%, #277ef2 73.61%, #2adbc8 101%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-size: 200% 100%;
    animation: text-gradient-flow 3s linear infinite;
}
</style>
