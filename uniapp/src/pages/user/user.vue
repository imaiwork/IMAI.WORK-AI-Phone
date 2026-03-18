<template>
    <view class="h-screen flex flex-col relative user-bg">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="absolute top-0 left-0 right-0 z-[1] bg-image-container">
            <image
                :src="`${config.baseUrl}static/images/mp/user_top_bg.png`"
                class="w-full h-[600rpx] object-cover"
                mode="aspectFill" />
            <view class="absolute bottom-0 left-0 right-0 h-[200rpx] bg-blur-mask"></view>
        </view>

        <view class="grow min-h-0 relative z-[20]">
            <scroll-view scroll-y class="h-full">
                <view class="min-h-full flex flex-col">
                    <view class="pb-5 grow min-h-0">
                        <view class="px-[70rpx]">
                            <view class="pt-[32rpx] flex items-center space-x-[42rpx]">
                                <view class="w-[128rpx] h-[128rpx] rounded-full relative">
                                    <template v-if="isLogin">
                                        <image
                                            :src="userInfo.avatar"
                                            class="w-full h-full rounded-full"
                                            mode="aspectFill"></image>
                                        <view class="absolute -bottom-1 -right-1" @click="showUpdateUserPopup = true">
                                            <image
                                                src="/static/images/icons/user_edit.svg"
                                                class="w-[40rpx] h-[40rpx]"></image>
                                        </view>
                                    </template>
                                    <navigator class="w-full h-full" url="/pages/login/login" hover-class="none" v-else>
                                        <image
                                            :src="websiteConfig.shop_logo"
                                            class="w-full h-full rounded-full"></image>
                                    </navigator>
                                </view>
                                <view>
                                    <navigator url="/pages/login/login" hover-class="none" v-if="!isLogin">
                                        <view class="text-[36rpx] font-medium">未登录</view>
                                        <view
                                            class="text-[#000000]/40 flex items-center mt-2 text-[22rpx] font-medium gap-x-[6rpx]">
                                            <text>立即登录</text>
                                            <u-icon name="arrow-right" size="20" color="#00000040"></u-icon>
                                        </view>
                                    </navigator>
                                    <view v-else>
                                        <view class="text-[36rpx] font-medium">{{ userInfo.nickname }}</view>
                                        <view class="flex items-center gap-x-2 text-xs mt-2 text-[#000000]/40">
                                            <view @click="copy(userInfo.sn)">ID: {{ userInfo.sn }}</view>
                                            <view>|</view>
                                            <view @click="copy(userInfo.mobile)">{{ userInfo.mobile }}</view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="grid grid-cols-3 gap-x-[20rpx] mt-[40rpx]">
                                <view class="flex flex-col items-center">
                                    <view class="text-[34rpx] font-medium">{{
                                        userTokens || `${isLogin ? "0" : "-"}`
                                    }}</view>
                                    <view
                                        class="text-[#000000]/40 flex items-center mt-1 text-[22rpx] font-medium gap-x-[6rpx]"
                                        @click="handleUtils('recharge')">
                                        <text>可用算力</text>
                                        <u-icon name="arrow-right" size="20" color="#00000040"></u-icon>
                                    </view>
                                </view>
                                <view class="flex flex-col items-center">
                                    <view class="text-[34rpx] font-medium">{{
                                        videoCount || `${isLogin ? "0" : "-"}`
                                    }}</view>
                                    <view
                                        class="text-[#000000]/40 flex items-center mt-1 text-[22rpx] font-medium gap-x-[6rpx]">
                                        <text>我的创作</text>
                                    </view>
                                </view>
                                <view class="flex flex-col items-center">
                                    <view class="text-[34rpx] font-medium">{{
                                        anchorCount || `${isLogin ? "0" : "-"}`
                                    }}</view>
                                    <view
                                        class="text-[#000000]/40 flex items-center mt-1 text-[22rpx] font-medium gap-x-[6rpx]">
                                        <text>数字人</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="px-[26rpx]" v-if="isLogin">
                            <view class="mt-[62rpx]">
                                <view class="relative h-[260rpx] p-[20rpx] rounded-[30rpx] recharge-card">
                                    <view>
                                        <image
                                            :src="`${config.baseUrl}static/images/mp/tokens_badge.png`"
                                            class="w-[144rpx] h-[150rpx] absolute top-[-25rpx] right-[50rpx]"></image>
                                    </view>
                                    <view class="h-[200rpx] w-full relative mt-2">
                                        <view class="w-full absolute top-0 left-0">
                                            <image
                                                :src="`${config.baseUrl}static/images/mp/tokens_bg.png`"
                                                class="w-full"
                                                mode="widthFix"></image>
                                        </view>

                                        <view class="flex items-center justify-between relative z-10 pt-[70rpx] px-8">
                                            <view class="flex flex-col items-center" @click="handleUtils('recharge')">
                                                <image
                                                    :src="`${config.baseUrl}static/images/mp/user_tokens_1.png`"
                                                    class="w-[40rpx] h-[40rpx]"></image>
                                                <text class="mt-[12rpx] text-black">算力充值</text>
                                            </view>
                                            <view class="flex flex-col items-center" @click="handleUtils('rule')">
                                                <image
                                                    :src="`${config.baseUrl}static/images/mp/user_tokens_2.png`"
                                                    class="w-[40rpx] h-[40rpx]"></image>
                                                <text class="mt-[12rpx]">算力规则</text>
                                            </view>
                                            <view class="flex flex-col items-center" @click="handleUtils('card')">
                                                <image
                                                    :src="`${config.baseUrl}static/images/mp/user_tokens_3.png`"
                                                    class="w-[40rpx] h-[40rpx]"></image>
                                                <text class="mt-[12rpx]">卡密兑换</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[24rpx] bg-white rounded-[20rpx] p-[20rpx] grid grid-cols-2 gap-x-[20rpx]">
                                <div
                                    class="bg-[#FFF8F2] rounded-[20rpx] p-[32rpx] relative"
                                    @click="handleUtils('agent')">
                                    <view class="text-[#4F302B] font-bold text-[30rpx]">代理中心</view>
                                    <view class="text-[#4F302B]/50 text-xs font-medium mt-[6rpx]">用户和激活</view>
                                    <image
                                        :src="`${config.baseUrl}static/images/mp/agent_center.png`"
                                        class="w-[90rpx] h-[90rpx] absolute right-2 top-3"></image>
                                </div>
                                <view
                                    class="bg-[#FFF8F2] rounded-[20rpx] p-[32rpx] relative"
                                    @click="handleUtils('agent_invite_poster')">
                                    <view class="text-[#4F302B] font-bold text-[30rpx]">邀请好友</view>
                                    <view class="text-[#4F302B]/50 text-xs font-medium mt-[6rpx]">携手共拓商机</view>
                                    <image
                                        :src="`${config.baseUrl}static/images/mp/invite_friend.png`"
                                        class="w-[90rpx] h-[90rpx] absolute right-2 top-3"></image>
                                </view>
                            </view>
                            <view class="mt-[24rpx] bg-white rounded-[20rpx] px-[40rpx]">
                                <router-navigate
                                    to="/packages/pages/user_balance/user_balance"
                                    hover-class="none"
                                    class="h-[110rpx] flex justify-between items-center border-[0] border-b-[1rpx] border-solid border-[#F6F6F6]">
                                    <view class="leading-[0] flex items-center gap-x-2">
                                        <image
                                            src="/static/images/icons/record.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <text class="text-[26rpx]">算力消耗记录</text>
                                    </view>
                                    <view class>
                                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                                    </view>
                                </router-navigate>
                                <router-navigate
                                    to="/packages/pages/user_balance/user_balance?type=order"
                                    hover-class="none"
                                    class="h-[110rpx] flex justify-between items-center border-[0] border-b-[1rpx] border-solid border-[#F6F6F6]">
                                    <view class="leading-[0] flex items-center gap-x-2">
                                        <image src="/static/images/icons/order.svg" class="w-[32rpx] h-[32rpx]"></image>
                                        <text class="text-[26rpx]">我的订单</text>
                                    </view>
                                    <view class>
                                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                                    </view>
                                </router-navigate>
                                <view
                                    class="h-[110rpx] flex justify-between items-center border-[0] border-b-[1rpx] border-solid border-[#F6F6F6]"
                                    @click="openService">
                                    <view class="leading-[0] flex items-center gap-x-2">
                                        <image
                                            src="/static/images/icons/service.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <text class="text-[26rpx]">联系客服</text>
                                    </view>
                                    <view class>
                                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                                    </view>
                                </view>
                                <router-navigate
                                    to="/packages/pages/setting/setting"
                                    hover-class="none"
                                    class="h-[110rpx] flex justify-between items-center">
                                    <view class="leading-[0] flex items-center gap-x-2">
                                        <image
                                            src="/static/images/icons/setting3.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <text class="text-[26rpx]">设置与协议</text>
                                    </view>
                                    <view class>
                                        <u-icon name="arrow-right" size="20" color="#00000050"></u-icon>
                                    </view>
                                </router-navigate>
                            </view>
                        </view>
                    </view>
                    <view class="my-[50rpx]">
                        <view class="text-[22rpx] text-center text-[#0000004d]">
                            {{ byName }}
                        </view>
                        <view class="mt-1">
                            <view class="text-[#0000004d] text-[22rpx]">
                                <view v-for="(item, index) in copyrightConfig" :key="index" class="text-center mb-1">
                                    {{ item.key }}
                                </view>
                            </view>
                        </view>
                        <view class="text-[22rpx] text-center mb-1 text-[#0000004d]">
                            当前版本：Version <text class="font-medium">{{ config.version }}</text>
                        </view>
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
        v-model="showService"
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
                            <view class="text-[#299FD7] font-medium"> 专属客服全程陪伴 </view>
                            <view
                                class="h-[36rpx] w-[72rpx] flex items-center justify-center border border-solid border-white rounded-[24rpx] rounded-bl-[0] bg-primary">
                                <text class="text-[20rpx] text-white font-medium">官方</text>
                            </view>
                        </view>
                        <view class="mt-4">
                            <image src="/static/images/common/service_text.png" class="h-[90rpx]"></image>
                        </view>
                        <view class="mt-[12rpx] opacity-50"> 实时响应、技术专家协同 </view>
                        <view class="mt-[72rpx]">
                            <image
                                :src="getServiceQrcode"
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
                        <view class="font-medium text-[30rpx] mt-[32rpx]">
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
import config from "@/config";
import { getPublicAnchorList } from "@/api/digital_human";
import { getVideoCreationRecord } from "@/api/app";
import { getAgentUserParentQrcode } from "@/api/user";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { updateUser } from "@/api/account";
import { isIOS } from "@/utils/client";
import UpdateUserInfo from "@/pages/login/components/update-user-info.vue";
import { useCopy } from "@/hooks/useCopy";

const userStore = useUserStore();
const { userInfo, isLogin, userTokens } = toRefs(userStore);

const { copy } = useCopy();

const appStore = useAppStore();
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const rechargeConfig = computed(() => appStore.getRechargeConfig);
const cardCodeConfig = computed(() => appStore.getCardCodeConfig);
const copyrightConfig = computed(() => appStore.getCopyRightConfig);
const byName = computed(() => appStore.getByName);
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

const getServiceQrcode = computed(() => {
    return agentUserParentQrcode.value || getCustomerService.value.wx_image;
});

const showUpdateUserPopup = ref(false);

const handleUpdateUser = async (value: any) => {
    await updateUser(value, { token: userInfo.value.token });
    userStore.getUser();
    showUpdateUserPopup.value = false;
};

const handleUtils = (type: string) => {
    if (!isLogin.value) {
        uni.$u.route({
            url: "/pages/login/login",
        });
        return;
    }
    let pathUrl;
    switch (type) {
        case "recharge":
            pathUrl = "/packages/pages/recharge/recharge";
            if (isIOS() && rechargeConfig.value.is_ios_open == 1 && cardCodeConfig.value.is_open == 1) {
                pathUrl = "/packages/pages/redeem/redeem";
            }

            break;
        case "card":
            pathUrl = "/packages/pages/redeem/redeem";
            break;
        case "rule":
            pathUrl = "/packages/pages/tokens_rule/tokens_rule";
            break;
        case "agent":
            if (userInfo.value.is_distribution_agent != 1) {
                uni.$u.toast("您不是代理，无法进入代理中心");
                return;
            }
            pathUrl = "/packages/pages/agent/agent";
            break;
        case "agent_invite_poster":
            if (userInfo.value.is_distribution_agent != 1) {
                uni.$u.toast("您不是代理，无法进入代理中心");
                return;
            }
            pathUrl = "/packages/pages/agent_invite_poster/agent_invite_poster";
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

const anchorCount = ref(0);
const getAnchorCount = async () => {
    const { count } = await getPublicAnchorList({
        page: 1,
        page_size: 1,
    });
    anchorCount.value = count;
};

const videoCount = ref(0);
const getVideoCount = async () => {
    const { count } = await getVideoCreationRecord({
        page: 1,
        page_size: 1,
    });
    videoCount.value = count;
};

const agentUserParentQrcode = ref("");
const getAgentParentQrcode = async () => {
    if (!isLogin.value) {
        return;
    }
    const res = await getAgentUserParentQrcode();
    agentUserParentQrcode.value = res.qr_code;
};

onShow(() => {
    userStore.getUser();
    getAnchorCount();
    getVideoCount();
    getAgentParentQrcode();
});
</script>

<style lang="scss" scoped>
.user-bg {
    background: linear-gradient(
        180deg,
        rgba(232, 236, 247, 1) 0%,
        rgba(235, 238, 245, 1) 40%,
        rgba(245, 247, 250, 1) 100%
    );
}

.bg-image-container {
    height: 600rpx;
    overflow: hidden;
}

.bg-blur-mask {
    background: linear-gradient(
        180deg,
        rgba(235, 238, 245, 0) 0%,
        rgba(235, 238, 245, 0.3) 30%,
        rgba(235, 238, 245, 0.7) 60%,
        rgba(235, 238, 245, 1) 100%
    );
    backdrop-filter: blur(8rpx);
    -webkit-backdrop-filter: blur(8rpx);
}

.recharge-card {
    background: linear-gradient(90deg, rgba(245, 220, 226, 1) 0%, rgba(217, 232, 250, 1) 100%);
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
    @apply w-[156rpx] h-[64rpx] flex items-center justify-center text-[#4A2F21] font-medium text-[26rpx];
}

.menu-link {
    line-height: 0;
    border-radius: 32rpx;
    box-shadow: 0px 4px 6px 3px rgba(0, 0, 0, 0.3);
    height: 250rpx;
}
</style>
