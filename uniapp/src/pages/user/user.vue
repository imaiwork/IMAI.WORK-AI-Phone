<template>
    <view class="h-screen flex flex-col relative user-page">
        <u-navbar
            is-custom-back-icon
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
            <view class="text-[30rpx] font-bold text-white">个人中心</view>
        </u-navbar>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] pb-5">
                    <view class="flex justify-center">
                        <view class="mt-[32rpx]">
                            <view class="w-[128rpx] h-[128rpx] rounded-full mx-auto relative">
                                <template v-if="isLogin">
                                    <image :src="userInfo.avatar" class="w-full h-full rounded-full"></image>
                                    <view class="absolute -bottom-1 -right-1" @click="showUpdateUserPopup = true">
                                        <image
                                            src="/static/images/icons/user_edit.svg"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                    </view>
                                </template>
                                <!-- #ifndef APP-PLUS -->
                                <navigator class="w-full h-full" url="/pages/login/login" hover-class="none" v-else>
                                    <image :src="websiteConfig.shop_logo" class="w-full h-full rounded-full"></image>
                                </navigator>
                                <!-- #endif -->
                                <!-- #ifdef APP-PLUS -->
                                <navigator class="w-full h-full" url="/pages/login/mobile" hover-class="none" v-else>
                                    <image :src="websiteConfig.shop_logo" class="w-full h-full rounded-full"></image>
                                </navigator>
                                <!-- #endif -->
                            </view>
                            <view
                                class="text-white font-bold mt-[40rpx] h-[40rpx] text-center"
                                style="text-shadow: 0px 1px 2px rgba(0, 0, 0, 0.3)">
                                <!-- #ifndef APP-PLUS -->
                                <navigator v-if="!isLogin" url="/pages/login/login" hover-class="none">
                                    点击登录后，体验更多 AI功能
                                </navigator>
                                <!-- #endif -->
                                <!-- #ifdef APP-PLUS -->
                                <navigator v-if="!isLogin" url="/pages/login/mobile" hover-class="none">
                                    点击登录后，体验更多 AI功能
                                </navigator>
                                <!-- #endif -->
                                <text>{{ userInfo.nickname }}</text>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[48rpx]">
                        <view class="main-card">
                            <view class="border border-solid border-[#B0A99D] rounded-[28rpx] p-[3rpx]">
                                <view class="tokens-card">
                                    <view class="flex-1">
                                        <view class="flex items-center">
                                            <view class="tokens-value text-[32rpx] font-bold">
                                                当前剩余算力：{{ isLogin ? userTokens : "-" }}
                                            </view>
                                            <image
                                                src="/static/images/icons/shandian.svg"
                                                class="w-[16rpx] h-[24rpx] ml-2 mt-[4rpx]"></image>
                                        </view>
                                        <view class="tokens-desc text-[22rpx] mt-1"> 充值算力，助你抢占先机 </view>
                                    </view>
                                    <view class="absolute right-[80rpx]" @click="handleUtils('recharge')">
                                        <image
                                            src="/static/images/common/recharge-btn.png"
                                            class="h-[100rpx] w-[172rpx]"></image>
                                    </view>
                                </view>
                            </view>
                            <view class="mx-2">
                                <view class="mt-[20rpx] text-[40rpx] font-bold text-[#4A2F21]">
                                    <view>{{ websiteConfig.shop_name }}算力驱动未来</view>
                                    <view>引擎全开，撑起无限的可能。</view>
                                </view>
                                <view class="opacity-50 text-[22rpx] mt-[16rpx]">
                                    解锁更强大、更敏捷、更智慧的人工智能体验，始于核心算力的每一次跃升。
                                </view>
                            </view>
                            <view class="grid grid-cols-3 mt-[28rpx] gap-x-[20rpx]">
                                <router-navigate class="menu-link" to="/packages/pages/creation_record/creation_record">
                                    <image
                                        src="/static/images/common/creation_record_card.png"
                                        class="w-full h-full"></image>
                                </router-navigate>
                                <router-navigate class="menu-link" to="/packages/pages/tokens_rule/tokens_rule">
                                    <image
                                        src="/static/images/common/tokens_rule_card.png"
                                        class="w-full h-full"></image>
                                </router-navigate>
                                <view class="menu-link" @click="openService()">
                                    <image src="/static/images/common/service_card.png" class="w-full h-full"></image>
                                </view>
                            </view>
                            <view class="mt-[40rpx] flex gap-x-2">
                                <view class="flex items-center gap-x-2">
                                    <image
                                        src="/static/images/icons/success_badge.svg"
                                        class="w-[28rpx] h-[28rpx]"></image>
                                    <text class="opacity-30 text-[22rpx]">最受欢迎的智能工具之一</text>
                                </view>
                                <view class="flex items-center gap-x-2">
                                    <image
                                        src="/static/images/icons/success_badge.svg"
                                        class="w-[28rpx] h-[28rpx]"></image>
                                    <text class="opacity-30 text-[22rpx]">智能体验深度探索助力企业</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[32rpx]">
                        <router-navigate
                            to="/packages/pages/setting/setting"
                            hover-class="none"
                            class="rounded-[24rpx] bg-white p-[18rpx] flex justify-between items-center leading-[0]">
                            <view class="leading-[0] flex items-center gap-x-2">
                                <image src="/static/images/icons/menu_setting.svg" class="w-[72rpx] h-[72rpx]"></image>
                                <text class="text-[26rpx]">相关设置</text>
                            </view>
                            <view class>
                                <image src="/static/images/icons/more.svg" class="w-[28rpx] h-[28rpx]"></image>
                            </view>
                        </router-navigate>
                    </view>
                </view>
            </scroll-view>
        </view>
        <update-user-info
            v-model:show="showUpdateUserPopup"
            :logo="websiteConfig.shop_logo"
            :title="websiteConfig.shop_name"
            :userInfo="userInfo"
            @update="handleUpdateUser" />
        <tabbar />
    </view>
    <popup-bottom
        v-model:show="showService"
        title="了解更多"
        height="80%"
        :custom-style="{
            background: 'linear-gradient(180deg, #E4F5FF 6.21%, #F9FAFB 64.71%)',
        }">
        <template #content>
            <view class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="flex flex-col items-center">
                        <view class="mt-[60rpx] flex items-center gap-x-2">
                            <view class="text-[#299FD7] font-bold"> 专属客服全程陪伴 </view>
                            <view
                                class="h-[36rpx] w-[72rpx] flex items-center justify-center border border-solid border-white rounded-[24rpx] rounded-bl-[0] bg-primary">
                                <text class="text-[20rpx] text-white font-bold">官方</text>
                            </view>
                        </view>
                        <view class="mt-4">
                            <image src="/static/images/common/service_text.png" class="h-[90rpx]"></image>
                        </view>
                        <view class="mt-[12rpx] opacity-50"> 实时响应、技术专家协同 </view>
                        <view class="mt-[72rpx]">
                            <image
                                :src="getCustomerService.wx_image"
                                show-menu-by-longpress
                                class="w-[400rpx] h-[400rpx] rounded-[24rpx]"></image>
                        </view>
                        <view class="mt-[72rpx]">
                            <u-button
                                type="primary"
                                shape="circle"
                                :custom-style="{
                                    width: '606rpx',
                                    height: '90rpx',
                                    fontWeight: 'bold',
                                    fontSize: '26rpx',
                                }"
                                @click="saveQrcode"
                                >保存二维码相册</u-button
                            >
                        </view>
                        <view class="flex items-center mt-[72rpx] gap-x-2">
                            <view style="width: 40rpx; height: 2rpx; background-color: #00000008"></view>
                            <view class="opacity-50 text-[26rpx]"> 我们的专属客服服务时间为： </view>
                            <view style="width: 40rpx; height: 2rpx; background-color: #00000008"></view>
                        </view>
                        <view class="font-bold text-[30rpx] mt-[32rpx]">
                            <text
                                >服务时间：<text class="text-primary">工作日{{ getCustomerService.time }}</text
                                >（GMT+8）</text
                            >
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { updateUser } from "@/api/account";
import { isIOS } from "@/utils/client";
import UpdateUserInfo from "@/pages/login/components/update-user-info.vue";

const userStore = useUserStore();
const { userInfo, isLogin, userTokens } = toRefs(userStore);

const appStore = useAppStore();
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const rechargeConfig = computed(() => appStore.getRechargeConfig);
const cardCodeConfig = computed(() => appStore.getCardCodeConfig);

const getCustomerService = computed(() => {
    if (websiteConfig.value.customer_service) {
        const { wx_image, title, time, phone } = websiteConfig.value.customer_service;
        return {
            wx_image,
            title,
            time,
            phone,
        };
    }
    return {};
});

const showUpdateUserPopup = ref(false);

const handleUpdateUser = async (value: any) => {
    await updateUser(value, { token: userInfo.value.token });
    userStore.getUser();
    showUpdateUserPopup.value = false;
};

const handleUtils = (type: string) => {
    let pathUrl;
    switch (type) {
        case "recharge":
            pathUrl = "/packages/pages/recharge/recharge";
            if (isIOS() && rechargeConfig.value.is_ios_open == 1 && cardCodeConfig.value.is_open == 1) {
                pathUrl = "/packages/pages/redeem/redeem";
            }

            break;
        case "creation_record":
            pathUrl = "/packages/pages/creation_record/creation_record";
            break;
    }
    uni.$u.route({
        url: pathUrl,
    });
};

const showService = ref(false);
const openService = () => {
    showService.value = true;
};

const saveQrcode = () => {
    uni.downloadFile({
        url: getCustomerService.value.wx_image,
        success: (result) => {
            uni.saveImageToPhotosAlbum({
                filePath: result.tempFilePath,
                success: () => {
                    uni.$u.toast("保存成功");
                },
                fail: (error) => {
                    uni.$u.toast("保存失败");
                },
            });
        },
        fail: (error) => {
            uni.$u.toast("保存失败");
        },
    });
};

onShow(() => {
    userStore.getUser();
});
</script>

<style lang="scss" scoped>
.user-page {
    background: linear-gradient(180deg, $u-type-primary 30.54%, #f9fafb 46.18%);
}

.main-card {
    @apply rounded-[48rpx] p-[26rpx];
    background: linear-gradient(0deg, #ffffff 60%, #ffe7c5 100%);
    box-shadow: 0px 6px 12px 4px rgba(0, 0, 0, 0.06);
}

.tokens-card {
    @apply rounded-[24rpx] p-[32rpx] flex items-center justify-between;
    background: linear-gradient(225deg, #ffe5c0 -174.4%, #252223 50.08%);
    box-shadow: 0px 6px 12px 0px rgba(0, 0, 0, 0.3);
}

.tokens-value {
    background: linear-gradient(270deg, #ffe8c7 00%, #fff 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.tokens-desc {
    background: linear-gradient(270deg, #ffe8c7 00%, #fff 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.recharge-btn {
    border-radius: 1000px;
    background: linear-gradient(270deg, #ffd6a9 0%, #ffeac9 100%);
    box-shadow: 0px 4px 6px 0px rgba(0, 0, 0, 0.3);
    @apply w-[156rpx] h-[64rpx] flex items-center justify-center text-[#4A2F21] font-bold text-[26rpx];
}

.menu-link {
    line-height: 0;
    border-radius: 32rpx;
    box-shadow: 0px 4px 6px 3px rgba(0, 0, 0, 0.3);
    height: 250rpx;
}
.gf-badge {
    box-shadow: 0px 1px 6px rgba(0, 101, 251, 0.25);
}
</style>
