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

            <view v-if="!isRuleTab" class="grow min-h-0">
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
                                            <!-- 团队扣费：与 PC 一致展示企业来源 -->
                                            <view
                                                v-if="isTeamRow(item)"
                                                class="mt-[8rpx] inline-flex max-w-full items-center rounded-[8rpx] bg-[#FFF7ED] px-[12rpx] py-[4rpx]">
                                                <text class="truncate text-[20rpx] font-bold text-[#EA580C]">
                                                    {{
                                                        item.team_name
                                                            ? "企业·" + item.team_name
                                                            : "企业空间"
                                                    }}
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                    <!-- 右侧：金额 + 剩余（订阅记录的微信/虚拟支付不展示剩余） -->
                                    <view class="text-right shrink-0">
                                        <view class="text-[32rpx] font-semibold text-[#FF4D4F]">
                                            {{ item.change_amount_desc }}
                                        </view>
                                        <view
                                            v-if="shouldShowLeftTokens(item)"
                                            class="mt-[6rpx] flex items-center justify-end gap-[8rpx]">
                                            <view
                                                v-if="isTeamRow(item)"
                                                class="inline-flex items-center rounded-[6rpx] bg-[#FFF7ED] px-[8rpx] py-[2rpx]">
                                                <text class="text-[18rpx] font-bold text-[#EA580C]">团队</text>
                                            </view>
                                            <text class="text-[22rpx] text-[#9CA3AF]">
                                                剩余 {{ item.left_tokens }}
                                            </text>
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

            <view v-else class="grow min-h-0">
                <scroll-view scroll-y class="h-full">
                    <view class="px-[24rpx] pt-[24rpx] pb-[40rpx]">
                        <view class="rounded-[24rpx] bg-white overflow-hidden">
                            <view
                                v-for="(item, index) in tokensConfig"
                                :key="index"
                                class="flex items-center justify-between gap-[20rpx] px-[32rpx] py-[28rpx]"
                                :class="
                                    index < tokensConfig.length - 1
                                        ? 'border-b border-0 border-solid border-[#F0F2F6]'
                                        : ''
                                ">
                                <view class="flex items-center gap-[16rpx] flex-1 min-w-0">
                                    <view
                                        class="w-[52rpx] h-[52rpx] rounded-full bg-[#EEF4FF] flex items-center justify-center shrink-0">
                                        <text class="text-[24rpx] font-semibold text-primary">{{ index + 1 }}</text>
                                    </view>
                                    <text class="text-[28rpx] font-medium text-[#1a1a1a] break-all">
                                        {{ item.name }}
                                    </text>
                                </view>
                                <view
                                    class="flex items-center h-[48rpx] rounded-full bg-[#16f49f1a] px-[10rpx] border border-solid border-[#16f49f33] shrink-0">
                                    <image src="@/packages/static/icons/tokens.svg" class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[#16F49F] text-[24rpx] font-medium ml-[6rpx]">
                                        {{ item.score }}{{ item.unit }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <view v-if="tokensConfig.length === 0" class="mt-[120rpx]">
                            <empty />
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { accountLog } from "@/api/user";
import { useUserStore } from "@/stores/user";

const tabs = [
    { name: "消耗记录", action: 2 },
    { name: "订阅记录", action: 1 },
    { name: "算力规则", action: 0 },
];

const current = ref(0);

const change = (e: any) => {
    current.value = e;
    if (isRuleTab.value) return;
    queryParams.action = tabs[e].action;
    pagingRef.value?.reload();
};

const isRuleTab = computed(() => tabs[current.value].name === "算力规则");

const userStore = useUserStore();
const { tokensConfig } = toRefs(userStore);

const balanceLists = ref<any[]>([]);
const pagingRef = shallowRef();
const queryParams = reactive({
    type: "tokens",
    action: 2,
});

/** 团队扣费行（与 PC balance 一致） */
const isTeamRow = (item: any) => Number(item?.is_team) === 1 || Number(item?.team_id) > 0;

/** 订阅记录中微信/虚拟支付不展示「剩余」；消耗记录始终展示 */
const shouldShowLeftTokens = (item: any) => {
    if (Number(queryParams.action) !== 1) {
        return true;
    }
    if (item?.show_left_tokens === false) {
        return false;
    }
    if (item?.record_type === "device_auth") {
        return false;
    }
    const payWay = String(item?.pay_way || item?.extra?.["支付方式"] || "");
    if (payWay.includes("虚拟") || payWay.includes("微信") || payWay.includes("支付宝")) {
        return false;
    }
    return true;
};

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

onShow(() => {
    userStore.getTokensConfig();
});
</script>

<style scoped></style>
