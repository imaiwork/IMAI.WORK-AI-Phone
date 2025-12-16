<template>
    <view class="min-h-screen bg-[#E4E4EF] relative pb-[100rpx]">
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="w-full h-[460rpx] absolute top-0 left-0 bg-image-container">
            <image src="@/ai_modules/drawing/static/images/home/bg.jpg" class="w-full h-full"></image>
        </view>
        <view class="relative">
            <view class="px-[88rpx] mt-4">
                <view class="font-bold text-[50rpx] text-[#0076BA]">AI图片创作</view>
                <view class="font-bold text-[32rpx] text-[#0076bab3] mt-[12rpx]">一站式满足各种场景的图片设计</view>
            </view>
            <view class="mt-[76rpx] px-4 grid grid-cols-2 gap-[20rpx]">
                <view
                    class="bg-white rounded-[20rpx] px-[34rpx] py-[26rpx] relative h-[190rpx] overflow-hidden"
                    @click="toPage(PageKey.SMART_PUZZLE)">
                    <view class="text-[34rpx] font-bold">智能拼图</view>
                    <view class="text-xs text-[#0000004d] mt-[4rpx]">自动拼图包装</view>
                    <image
                        src="@/ai_modules/drawing/static/images/common/puzzle.jpg"
                        class="w-[130rpx] absolute right-0 bottom-0"
                        mode="widthFix"></image>
                </view>
                <view
                    class="bg-white rounded-[20rpx] px-[34rpx] py-[26rpx] relative h-[190rpx] overflow-hidden"
                    @click="toPage(PageKey.AI_DRAWING)">
                    <view class="text-[34rpx] font-bold">AI生图</view>
                    <view class="text-xs text-[#0000004d] mt-[4rpx]">即将上线</view>
                    <image
                        src="@/ai_modules/drawing/static/images/common/drawing.jpg"
                        class="w-[130rpx] absolute right-0 bottom-0"
                        mode="widthFix"></image>
                </view>
            </view>
            <view class="grid grid-cols-4 gap-x-2 mt-2 px-4">
                <view
                    v-for="(item, index) in navbarList"
                    :key="index"
                    class="flex flex-col items-center justify-center py-4"
                    @click="toPage(item.key)">
                    <image :src="item.icon" class="w-[40rpx] h-[40rpx]"></image>
                    <view class="relative mt-1">
                        <text class="font-bold">{{ item.name }}</text>
                        <image
                            v-if="item.disabled"
                            src="@/ai_modules/drawing/static/icons/lock.svg"
                            class="w-[16rpx] h-[16rpx] absolute right-[-20rpx] bottom-[20rpx]"></image>
                    </view>
                </view>
            </view>
            <view class="mt-[44rpx] px-4">
                <view class="flex items-center justify-between">
                    <view class="font-bold text-[30rpx]">拼图创作</view>
                    <view class="flex items-center gap-x-1" @click="toPage(PageKey.ME_CREATE)">
                        <text class="text-xs">全部</text>
                        <u-icon name="arrow-right" color="#727278"></u-icon>
                    </view>
                </view>
                <view class="mt-[24rpx] flex flex-col gap-y-2" v-if="puzzleList.length > 0">
                    <view v-for="(item, index) in puzzleList" :key="index" class="bg-white p-[26rpx] rounded-[20rpx]">
                        <puzzle-card :item="item" @delete="getLists"></puzzle-card>
                    </view>
                </view>
                <view v-else class="mt-4">
                    <empty />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPuzzleTaskList } from "@/api/drawing";
import GoodsIcon from "@/ai_modules/drawing/static/icons/goods.svg";
import PosterIcon from "@/ai_modules/drawing/static/icons/poster.svg";
import ModelIcon from "@/ai_modules/drawing/static/icons/model.svg";
import CreateIcon from "@/ai_modules/drawing/static/icons/create.svg";
import PuzzleCard from "@/ai_modules/drawing/components/puzzle-card/puzzle-card.vue";

enum PageKey {
    // 智能拼图
    SMART_PUZZLE = "smart_puzzle",
    // AI生图
    AI_DRAWING = "ai_drawing",
    // 商品图
    GOODS_IMAGE = "goods_image",
    // 海报图
    POSTER_IMAGE = "poster_image",
    // AI模特
    AI_MODEL = "ai_model",
    // 我的创作
    ME_CREATE = "me_create",
}

const navbarList = [
    { name: "AI商品图", key: PageKey.GOODS_IMAGE, icon: GoodsIcon, disabled: true },
    { name: "AI海报图", key: PageKey.POSTER_IMAGE, icon: PosterIcon, disabled: true },
    { name: "AI模特", key: PageKey.AI_MODEL, icon: ModelIcon, disabled: true },
    { name: "我的创作", key: PageKey.ME_CREATE, icon: CreateIcon },
];

const puzzleList = ref<any[]>([]);

const toPage = (key: PageKey) => {
    const pages = {
        [PageKey.SMART_PUZZLE]: "/ai_modules/drawing/pages/create_task/create_task",
        [PageKey.AI_DRAWING]: "",
        [PageKey.GOODS_IMAGE]: "",
        [PageKey.POSTER_IMAGE]: "",
        [PageKey.AI_MODEL]: "",
        [PageKey.ME_CREATE]: "/packages/pages/creation/creation?tab=2",
    };
    if (pages[key]) {
        uni.$u.route({
            url: pages[key],
        });
    } else {
        uni.$u.toast("敬请期待~");
    }
};

const getLists = async () => {
    const res = await getPuzzleTaskList({ page_no: 1, page_size: 10 });
    puzzleList.value = res.lists;
};

onShow(() => {
    getLists();
});
</script>

<style scoped>
.bg-image-container::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50%;
    background: linear-gradient(to top, #e4e4ef, transparent);
    pointer-events: none;
}
</style>
