<template>
    <view class="h-screen flex flex-col relative bg-[#F5F6FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="变化记录"
            title-bold>
        </u-navbar>

        <view class="grow min-h-0 flex flex-col relative z-20">
            <!-- Tab -->
            <view class="px-[32rpx] pt-[8rpx]">
                <u-tabs :list="tabs" :is-scroll="false" :current="current" bg-color="transparent" @change="change">
                </u-tabs>
            </view>

            <view class="grow min-h-0">
                <z-paging
                    ref="pagingRef"
                    v-model="balanceLists"
                    :fixed="false"
                    :default-page-size="20"
                    :safe-area-inset-bottom="true"
                    auto-show-back-to-top
                    @query="queryList">
                    <view class="px-[24rpx] pt-[24rpx] flex flex-col gap-[20rpx] pb-[24rpx]">
                        <view
                            v-for="(item, index) in balanceLists"
                            :key="index"
                            class="bg-white rounded-[20rpx] overflow-hidden">
                            <!-- 卡片主体 -->
                            <view class="px-[32rpx] pt-[28rpx] pb-[24rpx]">
                                <!-- 第一行：标题 + 金额 -->
                                <view class="flex items-start justify-between gap-[24rpx]">
                                    <!-- 左侧：图标 + 标题 -->
                                    <view class="flex items-center gap-[16rpx] flex-1 min-w-0">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full bg-[#EEF4FF] flex items-center justify-center shrink-0">
                                            <u-icon name="integral" color="#0065fb" size="36"></u-icon>
                                        </view>
                                        <view class="flex-1 min-w-0">
                                            <view
                                                class="text-[28rpx] font-medium text-[#1a1a1a] leading-[1.4] line-clamp-2">
                                                {{ item.remark }}
                                            </view>
                                            <view class="text-[22rpx] text-[#9CA3AF] mt-[6rpx]">
                                                {{ item.create_time }}
                                            </view>
                                        </view>
                                    </view>
                                    <!-- 右侧：金额 + 剩余 -->
                                    <view class="text-right shrink-0">
                                        <view class="text-[32rpx] font-semibold text-[#FF4D4F]">
                                            {{ item.change_amount_desc }}
                                        </view>
                                        <view class="text-[22rpx] text-[#9CA3AF] mt-[6rpx]">
                                            剩余 {{ item.left_tokens }}
                                        </view>
                                    </view>
                                </view>

                                <!-- 分割线 -->
                                <view class="my-[20rpx]">
                                    <u-line></u-line>
                                </view>

                                <!-- 底部 extra 信息：标签式展示 -->
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="(value, key) in item.extra"
                                        :key="key"
                                        class="flex items-center bg-[#F5F6FA] rounded-[8rpx] px-[16rpx] py-[8rpx]">
                                        <text class="text-[#9CA3AF] text-[22rpx]">{{ key }}：</text>
                                        <text class="text-[#4B5563] text-[22rpx] font-medium">{{ value }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { accountLog } from "@/api/user";

const tabs = [
    { name: "消耗记录", action: 2 },
    { name: "订阅记录", action: 1 },
];

const current = ref(0);

const change = (e: any) => {
    current.value = e;
    queryParams.action = tabs[e].action;
    pagingRef.value?.reload();
};

const balanceLists = ref<any[]>([]);
const pagingRef = shallowRef();
const queryParams = reactive({
    type: "tokens",
    action: 2,
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await accountLog({
            page_no,
            page_size,
            ...queryParams,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};
</script>

<style scoped></style>
