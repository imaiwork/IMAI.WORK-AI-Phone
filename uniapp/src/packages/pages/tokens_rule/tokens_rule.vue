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
                :style="{
                    backgroundImage: `url(${config.baseUrl}static/images/tokens_rule_card_bg.png)`,
                }">
                <view class="text-white text-center pt-[123rpx]"> 算力规则 </view>
                <view class="grow min-h-0 py-[40rpx] px-[20rpx] container">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[20rpx]">
                            <view
                                v-for="(item, index) in tokensConfig"
                                :key="index"
                                class="border-b border-solid border-[#151924] border-0 h-[92rpx] flex items-center justify-between gap-x-2">
                                <view class="text-white flex items-center gap-x-1 flex-1">
                                    <view
                                        class="rounded-full bg-[#1F222E] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                        >{{ index + 1 }}</view
                                    >
                                    <text class="text-white break-all">{{ item.name }}</text>
                                </view>
                                <view class="flex-shrink-0 flex items-center justify-end">
                                    <view
                                        class="flex items-center h-[40rpx] rounded-full bg-[#16f49f1a] p-[4rpx] relative border border-solid border-[#16f49f33]">
                                        <image
                                            src="@/packages/static/icons/tokens.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <view
                                            class="text-[#16F49F] flex-1 text-center mx-[4rpx] break-all flex-shrink-0">
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
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import config from "@/config";

const userStore = useUserStore();
const { isLogin, tokensConfig } = toRefs(userStore);

const handleOpenBill = () => {
    if (!isLogin.value) {
        uni.$u.route({
            url: "/packages/pages/login/login",
        });
    }
    uni.$u.route({
        url: "/packages/pages/user_balance/user_balance",
    });
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
