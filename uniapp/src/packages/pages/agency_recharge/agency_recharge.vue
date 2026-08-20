<template>
    <view class="h-screen relative flex flex-col bg-[#f4f8ff]">
        <view class="w-full h-[400rpx] absolute top-0 left-0 bg-primary">
            <view
                class="absolute bottom-0 left-0 w-full h-[200rpx]"
                style="background: linear-gradient(to bottom, transparent, #f4f8ff)"></view>
        </view>

        <u-navbar
            :is-fixed="false"
            back-icon-color="#ffffff"
            :border-bottom="false"
            :background="{ background: 'transparent' }">
        </u-navbar>

        <view class="relative grow min-h-0 flex flex-col">
            <view class="px-[40rpx] pt-[10rpx] pb-[24rpx]">
                <view class="text-[40rpx] font-bold text-white">充值流水详情</view>
                <view class="text-xs mt-[8rpx] text-[#ffffff]/60 line-clamp-1">
                    {{ summary.nickname || "--" }}
                    <text v-if="summary.mobile"> · {{ summary.mobile }}</text>
                </view>
            </view>

            <view class="px-[26rpx]">
                <view
                    class="rounded-[28rpx] px-[40rpx] py-[32rpx] bg-white"
                    style="box-shadow: 0 8px 32px rgba(0, 101, 251, 0.12)">
                    <view class="flex items-center">
                        <view class="flex-1">
                            <view class="text-xs text-[#adc0d8] mb-[10rpx]">累计充值</view>
                            <view class="flex items-baseline gap-[6rpx]">
                                <text class="text-primary font-bold text-[28rpx]">￥</text>
                                <text class="font-bold text-primary text-[48rpx] leading-none">
                                    {{ summary.self_recharge_amount }}
                                </text>
                            </view>
                        </view>
                        <view class="w-[1rpx] h-[80rpx] bg-[#f0f5ff]"></view>
                        <view class="flex-1 pl-[32rpx]">
                            <view class="text-xs text-[#adc0d8] mb-[10rpx]">充值笔数</view>
                            <text class="font-bold text-[#0a1f44] text-[48rpx] leading-none">
                                {{ summary.self_recharge_count }}
                            </text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="grow min-h-0 mt-[28rpx]">
                <z-paging ref="pagingRef" v-model="dataList" :fixed="false" @query="queryList">
                    <view class="px-[26rpx] flex flex-col gap-[16rpx] pb-[40rpx]">
                        <view
                            v-for="item in dataList"
                            :key="item.id"
                            class="rounded-[24rpx] bg-white p-[28rpx]"
                            style="box-shadow: 0 2px 16px rgba(0, 101, 251, 0.07)">
                            <view class="flex items-start justify-between gap-[16rpx]">
                                <view class="flex-1 min-w-0">
                                    <view class="text-[30rpx] font-bold line-clamp-1 text-[#0a1f44]">
                                        {{ item.package_name || "算力充值" }}
                                    </view>
                                    <view class="text-[22rpx] mt-[8rpx] text-[#adc0d8]">
                                        {{ item.package_tokens || 0 }} 点算力 · {{ item.pay_way_desc || "--" }}
                                    </view>
                                </view>
                                <text class="shrink-0 font-bold text-[34rpx] text-primary">
                                    ￥{{ item.order_amount }}
                                </text>
                            </view>
                            <view
                                class="flex items-center justify-between mt-[20rpx] pt-[18rpx] border-[0] border-t border-solid border-[#f4f8ff]">
                                <text class="text-[22rpx] text-[#adc0d8] line-clamp-1 flex-1 min-w-0">
                                    单号 {{ item.sn }}
                                </text>
                                <text class="text-[22rpx] text-[#c8daf0] shrink-0 ml-[16rpx]">{{ item.pay_time }}</text>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty text="暂无充值记录" />
                    </template>
                </z-paging>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getAgentSubRechargeList, getAgentSubSummary } from "@/api/user";
import { onLoad } from "@dcloudio/uni-app";

const userId = ref(0);
const pagingRef = ref();
const dataList = ref<any[]>([]);

const summary = ref<Record<string, any>>({
    nickname: "",
    mobile: "",
    self_recharge_amount: 0,
    self_recharge_count: 0,
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAgentSubRechargeList({
            page_no,
            page_size,
            user_id: userId.value,
        });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const getSummary = async () => {
    try {
        summary.value = await getAgentSubSummary({ user_id: userId.value });
    } catch (error) {
        // 概要失败不影响流水列表展示
    }
};

onLoad((options: any) => {
    userId.value = Number(options?.user_id ?? 0);
    getSummary();
});
</script>
