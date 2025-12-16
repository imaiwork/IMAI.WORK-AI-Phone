<template>
    <view class="w-full h-full" @click="toDetail">
        <view class="flex items-center justify-between">
            <view class="font-bold break-all line-clamp-1">{{ item.name }}</view>
            <view class="flex items-center font-bold gap-x-1">
                <text>{{ item.success_puzzle_count }}</text>
                <text class="text-[#B2B2B2]">张</text>
                <u-icon name="arrow-right" color="#B2B2B2" :size="20"></u-icon>
            </view>
        </view>
        <view class="grid grid-cols-4 gap-1 mt-[18rpx]" v-if="[3, 5].includes(item.status)">
            <image
                v-for="(image, imageIndex) in item.puzzle_url"
                :key="imageIndex"
                :src="image"
                lazy
                class="w-full h-[156rpx] rounded-[10rpx]"
                mode="aspectFill"
                @click.stop="previewImage(item.puzzle_url, imageIndex)"></image>
        </view>
        <view v-else class="bg-[#F1F2F5] rounded-[16rpx] h-[156rpx] flex flex-col items-center justify-center mt-2">
            <view v-if="[0, 1, 2].includes(item.status)" class="flex items-center gap-x-2">
                <view class="gen-loading"></view>
                <view class="gen-loading-text">拼图中...</view>
            </view>
            <template v-else class="">
                <view class="flex items-center gap-x-2">
                    <image src="@/packages/static/icons/stop.svg" class="w-[28rpx] h-[28rpx]"></image>
                    <text class="text-[#F12A46] font-bold">拼图失败</text>
                </view>
                <view class="text-center text-[20rpx] text-[#00000066] w-[70%] mt-1">{{ item.remark }}</view>
            </template>
        </view>
        <view class="flex items-center justify-between mt-[26rpx]">
            <view class="text-[22rpx] text-[#0000004d]"> {{ item.create_time }} </view>
            <view class="flex items-center gap-x-1" @click.stop="handleDelete(item.id)">
                <image src="/static/images/icons/delete.svg" class="w-[28rpx] h-[28rpx]"></image>
                <text class="text-[#0000004d]">删除</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { deletePuzzleTask } from "@/api/drawing";

const props = defineProps<{
    item: any;
}>();

const emit = defineEmits(["delete"]);

const handleDelete = (id: any) => {
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
                    await deletePuzzleTask({ id });
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                    emit("delete", id);
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

const toDetail = () => {
    uni.navigateTo({
        url: `/ai_modules/drawing/pages/puzzle_detail/puzzle_detail?id=${props.item.id}`,
    });
};

const previewImage = (urls: string[], index: number) => {
    uni.previewImage({
        urls: urls,
        current: index,
    });
};
</script>

<style scoped lang="scss">
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
