<template>
    <view
        class="h-screen flex flex-col relative rule-page"
        :style="{
            backgroundImage: `url(${config.baseUrl}static/images/tokens_rule_bg.png)`,
            backgroundPositionY: screenHeight < 600 ? '-80rpx' : '0',
        }">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            back-icon-color="#ffffff"
            title-color="#ffffff"
            title="规则明细"
            title-bold>
        </u-navbar>
        <view
            class="grow min-h-0 px-[40rpx] relative pt-[435rpx]"
            :class="screenHeight < 600 ? 'pt-[285rpx]' : 'pt-[435rpx]'">
            <view
                class="rule-card h-[770rpx] w-full flex flex-col"
                :style="{ backgroundImage: `url(${config.baseUrl}static/images/tokens_rule_card_bg.png)` }">
                <view class="text-white text-[26rpx] text-center pt-[123rpx]"> 算力规则 </view>
                <view class="grow min-h-0 py-[40rpx] px-[20rpx] container">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[20rpx]">
                            <view
                                v-for="(item, index) in tokensConfig"
                                :key="index"
                                class="border-b border-solid border-[#151924] border-0 h-[92rpx] flex items-center justify-between gap-x-2">
                                <view class="text-white text-[26rpx] flex items-center gap-x-1 flex-shrink-0">
                                    <view
                                        class="rounded-full bg-[#1F222E] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                        >{{ index + 1 }}</view
                                    >
                                    <text class="text-white">{{ item.name }}</text>
                                </view>
                                <view class="flex-1 flex items-center justify-end">
                                    <view
                                        class="flex items-center h-[40rpx] rounded-full bg-[#16f49f1a] p-[4rpx] relative border border-solid border-[#16f49f33]">
                                        <image
                                            src="@/packages/static/icons/tokens.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <view class="text-[#16F49F] text-[26rpx] flex-1 text-center mx-[4rpx]">
                                            {{ item.score }}{{ item.unit }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </view>
        <view class="mx-[60rpx] mb-[60rpx]">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{ fontSize: '26rpx', height: '90rpx' }"
                @click="handleOpenBill"
                >账单明细</u-button
            >
        </view>
    </view>
    <popup-bottom v-model:show="showBill" title="查看账单" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <u-tabs
                    :list="tabs"
                    :is-scroll="false"
                    :current="current"
                    :show-bar="false"
                    bg-color=""
                    @change="change"></u-tabs>
                <view class="grow min-h-0 rounded-[24rpx] mt-2">
                    <z-paging
                        ref="pagingRef"
                        v-model="balanceLists"
                        :fixed="false"
                        :default-page-size="20"
                        :safe-area-inset-bottom="true"
                        auto-show-back-to-top
                        @query="queryList">
                        <view class="px-[32rpx] flex flex-col gap-2">
                            <view
                                v-for="(item, index) in balanceLists"
                                :key="index"
                                class="p-4 rounded-[16rpx] bg-white">
                                <view class="flex items-center justify-between">
                                    <view>
                                        <view class="text-[26rpx]">{{ item.remark }}</view>
                                        <view class="opacity-30 text-[22rpx] mt-1">{{ item.create_time }}</view>
                                    </view>
                                    <view class="text-right">
                                        <view class="text-[26rpx] opacity-80">
                                            {{ item.change_amount_desc }}
                                        </view>
                                        <view class="opacity-30 text-[22rpx] mt-1">
                                            剩余算力：{{ item.left_tokens }}
                                        </view>
                                    </view>
                                </view>
                                <view class="my-2">
                                    <u-line></u-line>
                                </view>
                                <view class="text-[#8C8C8C] mt-1 flex gap-2 text-[20rpx]">
                                    <view v-for="(value, key) in item.extra" :key="key"> {{ key }}：{{ value }} </view>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import config from "@/config";

const userStore = useUserStore();
const { isLogin, tokensConfig } = toRefs(userStore);

import { accountLog } from "@/api/user";

const tabs = [
    {
        name: "消耗记录",
        action: 2,
    },
    {
        name: "订阅记录",
        action: 1,
    },
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

const showBill = ref(false);

const handleOpenBill = () => {
    if (!isLogin.value) {
        uni.$u.route({
            url: "/pages/login/login",
        });
    }
    showBill.value = true;
};

const { screenHeight } = uni.$u.sys();

onShow(() => {
    userStore.getTokensConfig();
});
</script>

<style scoped lang="scss">
.rule-page {
    background-repeat: no-repeat;
    background-size: 100%;
    background-color: #000000;
}
.rule-card {
    background-repeat: no-repeat;
    background-size: 100%;
    position: relative;
    .container {
        width: 100%;
        height: 100%;
        position: relative;
        &::after {
            content: "";
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 40%;
            background: linear-gradient(180deg, rgba(6, 8, 21, 0) 30.19%, #060815 93.18%);
            pointer-events: none;
        }
    }
}
</style>
