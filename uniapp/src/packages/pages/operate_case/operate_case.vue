<template>
    <view class="flex flex-col bg-[#F4F7FA] h-screen">
        <u-navbar
            title="运营案例"
            title-bold
            :border-bottom="false"
            :background="{ background: 'transparent' }"></u-navbar>

        <view class="px-4 pt-4 pb-2 bg-[#F4F7FA]">
            <scroll-view scroll-x class="w-full" scroll-with-animation>
                <view class="flex gap-[12rpx] pb-[4rpx]" style="white-space: nowrap">
                    <view
                        v-for="(tab, index) in tabs"
                        :key="index"
                        class="flex-shrink-0 px-[28rpx] py-[10rpx] rounded-full text-[26rpx] font-medium"
                        :style="
                            currentTab === tab.key
                                ? 'background:#0066FF; color:#fff;'
                                : 'background:#fff; color:#374151; border:1px solid #E5E7EB'
                        "
                        @click="currentTab = tab.key">
                        {{ tab.label }}
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="grow min-h-0 pt-2">
            <scroll-view scroll-y class="h-full">
                <view v-if="filteredList.length === 0" class="flex flex-col items-center py-24 gap-3">
                    <text class="text-[#C0C4CC] text-[24rpx]">暂无相关案例</text>
                </view>

                <view class="px-4 pb-8">
                    <view
                        v-for="item in filteredList"
                        :key="item.id"
                        class="bg-white rounded-[24rpx] mb-[20rpx] px-[28rpx] pt-[28rpx] pb-[24rpx]"
                        style="box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05)">
                        <view class="flex items-start justify-between mb-[16rpx]">
                            <view class="flex items-center gap-[12rpx] flex-wrap flex-1 mr-3">
                                <view
                                    class="px-[16rpx] py-[4rpx] rounded-full bg-[#F1F5F9] text-[#64748B] text-[22rpx]">
                                    {{ item.category }}
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] px-[16rpx] py-[4rpx] rounded-full bg-[#EFF6FF] text-primary text-[22rpx]">
                                    <u-icon name="clock" color="#0066FB" size="22"></u-icon>
                                    <text>24H 运行</text>
                                </view>
                            </view>

                            <view
                                class="w-[72rpx] h-[72rpx] rounded-[18rpx] flex items-center justify-center flex-shrink-0"
                                :style="`background:${item.iconBg}`">
                                <u-icon :name="item.icon" :color="item.iconColor" size="40"></u-icon>
                            </view>
                        </view>

                        <text class="text-[30rpx] font-bold text-[#111827] leading-snug block mb-[12rpx]">
                            {{ item.title }}
                        </text>

                        <text class="text-[24rpx] text-[#6B7280] leading-[40rpx] block mb-[24rpx]">
                            {{ item.intro }}
                        </text>

                        <view class="flex items-end justify-between">
                            <view class="flex gap-[40rpx]">
                                <view class="flex flex-col gap-[4rpx]">
                                    <text class="text-[32rpx] font-bold text-[#111827]">{{ item.exposure }}</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]">曝光量</text>
                                </view>
                                <view class="flex flex-col gap-[4rpx]">
                                    <text class="text-[32rpx] font-bold text-[#111827]">{{ item.leads }}</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]">获取线索</text>
                                </view>
                            </view>
                            <view
                                class="px-[32rpx] py-[16rpx] rounded-[16rpx] bg-primary text-white text-[26rpx] font-semibold"
                                @click="handleUse(item)">
                                查看案例
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
    </view>
</template>

<script setup lang="ts">
import config from "@/config";

const currentTab = ref("all");

const tabs = [
    { key: "all", label: "全部" },
    { key: "企业服务", label: "企业服务" },
    { key: "个人IP", label: "个人IP" },
    { key: "本地生活", label: "本地生活" },
];

const templateList = ref<any[]>([]);

const getTemplateList = () => {
    uni.request({
        url: `${config.baseUrl}static/case/templates/case.json`,
        method: "GET",
        success: (res: any) => {
            templateList.value = res.data.list;
        },
    });
};

getTemplateList();

const filteredList = computed(() =>
    currentTab.value === "all"
        ? templateList.value
        : templateList.value.filter((item) => item.level1 == currentTab.value)
);

const handleUse = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/operate_case_detail/operate_case_detail?id=${item.id}`,
    });
};
</script>

<style scoped></style>
