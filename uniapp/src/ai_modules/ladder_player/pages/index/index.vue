<template>
    <view class="h-screen bg-[#F9FAFD] flex flex-col">
        <view class="fixed top-0 left-0 right-0 z-50">
            <u-navbar
                :is-fixed="false"
                :border-bottom="false"
                :background="{
                    background: 'transparent',
                }">
            </u-navbar>
        </view>
        <view class="relative">
            <image src="@/ai_modules/ladder_player/static/images/home/cover.png" class="w-full h-[588rpx]"></image>
            <view class="absolute bottom-[60rpx] left-6">
                <navigator
                    hover-class="none"
                    class="rounded-full py-2 px-3 text-primary text-xs font-bold bg-[#DEE3F9]"
                    url="/ai_modules/ladder_player/pages/report/report">
                    查看我的练习报告
                </navigator>
            </view>
        </view>

        <view class="grow min-h-0 flex flex-col">
            <view class="flex items-center gap-10 mx-4 mb-3">
                <view
                    v-for="(item, index) in previewTabs"
                    :key="index"
                    class="relative preview-tab"
                    @click="handlePreviewTab(item.value)">
                    <view
                        class="tab-item"
                        :class="{
                            active: queryParams.common == item.value,
                        }"
                        >{{ item.label }}</view
                    >
                    <view v-if="item.value == 1" class="absolute -top-[6rpx] -right-[24rpx]">
                        <image
                            src="@/ai_modules/ladder_player/static/images/common/ai.png"
                            class="w-[20rpx] h-[16rpx]"></image>
                    </view>
                </view>
            </view>
            <view
                class="mt-4 grow min-h-0"
                :style="{
                    paddingBottom: queryParams.common == 2 ? '150rpx' : '0',
                }">
                <z-paging
                    v-model="lists"
                    ref="pagingRef"
                    :auto="false"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="card-box">
                        <view
                            v-for="(item, index) in lists"
                            :key="index"
                            class="card-item"
                            @click="handleSceneIntro(item.id)">
                            <view class="flex-shrink-0">
                                <image :src="item.logo" lazy-load class="w-[144rpx] h-[144rpx]"></image>
                            </view>
                            <view>
                                <view class="text-xl font-bold">
                                    {{ item.name }}
                                </view>
                                <view class="text-sm text-[#ACABB9] mt-2 leading-[40rpx]">
                                    {{ item.description }}
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
        <view class="fixed bottom-4 left-0 w-full z-40" v-if="queryParams.common == 2">
            <view>
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{
                        width: '556rpx',
                        height: '104rpx',
                    }"
                    @click="handleCreateScene">
                    <view class="flex items-center gap-2">
                        <image
                            class="w-[48rpx] h-[48rpx]"
                            src="@/ai_modules/ladder_player/static/icons/tips.svg"></image>
                        <text class="text-xl font-bold">创作我的专属练习场景</text>
                    </view>
                </u-button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { lpSceneLists } from "@/api/ladder_player";

const previewTabs = [
    {
        value: 1,
        label: "公共场景",
    },
    {
        value: 2,
        label: "我的",
    },
];

const handlePreviewTab = (value: number) => {
    queryParams.common = value;
    pagingRef.value?.reload();
};

const lists = ref<any[]>([]);

const queryParams = reactive<any>({
    common: 1,
});

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    uni.showLoading({
        title: "加载中",
    });
    try {
        const { lists } = await lpSceneLists({
            page_no,
            page_size,
            ...queryParams,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    } finally {
        uni.hideLoading();
    }
};

const handleCreateScene = () => {
    uni.navigateTo({
        url: "/ai_modules/ladder_player/pages/scene_create/scene_create",
    });
};

const handleSceneIntro = (id: string) => {
    uni.$u.route({
        url: `/ai_modules/ladder_player/pages/scene_intro/scene_intro`,
        params: {
            id,
        },
    });
};

onShow(async () => {
    await nextTick();
    setTimeout(() => {
        pagingRef.value?.reload();
    }, 300);
});
</script>

<style scoped lang="scss">
.preview-tab {
    .tab-item {
        @apply text-xl text-[#717189] relative;
        &.active {
            @apply text-black font-bold;
            &::after {
                content: "";
                transform: translateX(-50%);
                @apply absolute -bottom-2 left-[50%] w-[50%] rounded-md h-[6rpx] bg-primary;
            }
        }
    }
}
.card-box {
    @apply flex flex-col gap-3 mx-4;
    .card-item {
        @apply bg-white rounded-lg p-3 flex gap-2;
        box-shadow: 2rpx 2rpx 8rpx 2rpx rgba(224, 224, 224, 0.25);
    }
}
</style>
