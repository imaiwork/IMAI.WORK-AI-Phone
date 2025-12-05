<template>
    <view class="pt-[32rpx] flex flex-col h-screen">
        <view class="grow min-h-0">
            <view class="text-[26rpx] mx-[32rpx] flex flex-col gap-y-[16rpx]">
                <view class="rounded-[24rpx] bg-white px-[32rpx]">
                    <view
                        class="py-[40rpx] flex justify-between items-center leading-[0]"
                        @click="handleMenu(HandleType.EDIT_PROFILE)">
                        <text>{{ handleTypeMap[HandleType.EDIT_PROFILE] }}</text>
                        <image src="/static/images/icons/more.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                </view>
                <view class="rounded-[24rpx] bg-white px-[32rpx]">
                    <view
                        class="py-[40rpx] flex justify-between items-center leading-[0]"
                        @click="handleMenu(HandleType.USER_AGREEMENT)">
                        <text>
                            {{ handleTypeMap[HandleType.USER_AGREEMENT] }}
                        </text>
                        <image src="/static/images/icons/more.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                    <u-line></u-line>
                    <view
                        class="py-[40rpx] flex justify-between items-center leading-[0]"
                        @click="handleMenu(HandleType.PRIVACY_POLICY)">
                        <text>
                            {{ handleTypeMap[HandleType.PRIVACY_POLICY] }}
                        </text>
                        <image src="/static/images/icons/more.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                </view>
                <view class="rounded-[24rpx] bg-white px-[32rpx]">
                    <view
                        class="py-[40rpx] flex justify-between items-center leading-[0]"
                        @click="handleMenu(HandleType.ABOUT)">
                        <text>
                            {{ handleTypeMap[HandleType.ABOUT] }}
                        </text>
                        <image src="/static/images/icons/more.svg" class="w-[28rpx] h-[28rpx]"></image>
                    </view>
                </view>
            </view>
        </view>
        <view class="mx-[60rpx] mb-[100rpx]">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{
                    height: '90rpx',
                    'box-shadow': '0px 6px 12px 0px rgba(0,101,251,0.2)',
                }"
                @click="logout()">
                <view class="text-[26rpx]">退出登录</view>
            </u-button>
        </view>
    </view>
    <popup-bottom
        v-model:show="showPopup"
        :title="handleTypeMap[currHandleType]"
        :custom-class="{
            'bg-[#F9FAFB]': currHandleType == HandleType.EDIT_PROFILE,
        }">
        <template #content>
            <view class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="h-full flex flex-col py-4">
                        <view
                            v-if="currHandleType == HandleType.ABOUT"
                            class="grow min-h-0 flex flex-col w-full px-[60rpx]">
                            <view class="flex flex-col items-center justify-center w-full grow min-h-0">
                                <image :src="websiteConfig.shop_logo" class="w-[140rpx] h-[136rpx]" />
                                <text class="font-bold text-[30rpx] mt-[40rpx]">
                                    {{ websiteConfig.shop_name }}
                                </text>
                                <text class="opacity-50 text-[26rpx] mt-[20rpx]"> 专为企业打造的下一代 AI 工具 </text>
                                <view
                                    class="flex-shrink-0 rounded-full border border-solid border-[#ededed] mt-[60rpx] h-[100rpx] flex items-center justify-center w-full gap-x-4">
                                    <u-icon name="/static/images/icons/right.svg" :size="28"></u-icon>
                                    <text class="opacity-30 text-[26rpx]">
                                        {{ domain }}
                                    </text>
                                    <view class="leading-[0]" style="transform: rotate(180deg)">
                                        <u-icon name="/static/images/icons/right.svg" :size="28"></u-icon>
                                    </view>
                                </view>
                            </view>
                            <view class="flex-shrink-0">
                                <view class="text-[26rpx] text-center mb-[36rpx]">
                                    <label class="opacity-50">当前版本：</label>Version
                                    {{ config.version }}
                                </view>
                                <u-line />
                                <view class="text-[#0000004d] text-[22rpx] mt-[30rpx]">
                                    <view
                                        v-for="(item, index) in copyrightConfig"
                                        :key="index"
                                        class="text-center mb-1">
                                        {{ item.key }}
                                    </view>
                                </view>
                            </view>
                            <view class="text-[22rpx] text-center text-[#0000004d] mb-2">
                                {{ byName }}
                            </view>
                        </view>
                        <view
                            v-if="[HandleType.USER_AGREEMENT, HandleType.PRIVACY_POLICY].includes(currHandleType)"
                            class="grow min-h-0 px-[64rpx] text-[26rpx]">
                            <scroll-view scroll-y class="h-full">
                                <mp-html :content="agreementContent" />
                            </scroll-view>
                        </view>
                        <view v-if="currHandleType == HandleType.EDIT_PROFILE" class="grow min-h-0 user-info">
                            <view class="w-[128rpx] h-[128rpx] rounded-full mx-auto relative mt-[60rpx]">
                                <image :src="userInfo.avatar" class="w-full h-full rounded-full"></image>
                                <view
                                    class="absolute -bottom-1 -right-1"
                                    v-if="isLogin"
                                    @click="showUpdateUserPopup = true">
                                    <image src="/static/images/icons/user_edit.svg" class="w-[40rpx] h-[40rpx]"></image>
                                </view>
                            </view>
                            <view class="px-[32rpx] mt-[60rpx]">
                                <view class="rounded-[24rpx] bg-white px-[32rpx] text-[26rpx]">
                                    <view class="flex justify-between items-center h-[100rpx] gap-x-2">
                                        <view>用户昵称：</view>
                                        <view class="flex-1">
                                            <u-input
                                                v-model="nickname"
                                                placeholder="请输入昵称"
                                                placeholder-style="text-align: end; color: rgba(0,0,0,.2);font-size: 26rpx"
                                                @blur="updateNickname"></u-input>
                                        </view>
                                        <view>
                                            <image
                                                src="/static/images/icons/edit_pen.svg"
                                                class="w-[36rpx] h-[36rpx]"></image>
                                        </view>
                                    </view>
                                    <u-line></u-line>
                                    <view class="flex justify-between items-center h-[100rpx] gap-x-2">
                                        <view>账号ID:</view>
                                        <view class="flex-1 text-end">
                                            {{ userInfo.sn }}
                                        </view>
                                        <view @click="copy(userInfo.sn)">
                                            <image
                                                src="/static/images/icons/copy.svg"
                                                class="w-[36rpx] h-[36rpx]"></image>
                                        </view>
                                    </view>
                                    <u-line></u-line>
                                    <view class="flex justify-between items-center h-[100rpx] gap-x-2">
                                        <view>注册时间:</view>
                                        <view class="flex-1 text-end">
                                            {{ userInfo.create_time }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
    <update-user-info
        v-model:show="showUpdateUserPopup"
        :logo="websiteConfig.shop_logo"
        :title="websiteConfig.shop_name"
        :userInfo="userInfo"
        @update="handleUpdateUser" />
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { getPolicy } from "@/api/app";
import { userEdit } from "@/api/user";
import { updateUser } from "@/api/account";
import { useCopy } from "@/hooks/useCopy";
import UpdateUserInfo from "@/pages/login/components/update-user-info.vue";

import config from "@/config";

const appStore = useAppStore();
const userStore = useUserStore();
const { userInfo, isLogin } = toRefs(userStore);

const domain = computed(() => appStore.config.domain);
const websiteConfig = computed(() => appStore.getWebsiteConfig);
const copyrightConfig = computed(() => appStore.getCopyRightConfig);
const byName = computed(() => appStore.getByName);
enum HandleType {
    // 编辑资料
    EDIT_PROFILE = "edit_profile",
    // 用户协议
    USER_AGREEMENT = "service",
    // 隐私政策
    PRIVACY_POLICY = "privacy",

    // 关于我们
    ABOUT = "about",
}

const handleTypeMap: any = {
    [HandleType.EDIT_PROFILE]: "编辑资料",
    [HandleType.USER_AGREEMENT]: "用户协议",
    [HandleType.PRIVACY_POLICY]: "隐私政策",
    [HandleType.ABOUT]: "关于我们",
};

const currHandleType = ref<HandleType | any>();
const showPopup = ref(false);
const showUpdateUserPopup = ref(false);

const nickname = ref();

const handleMenu = (type: HandleType) => {
    currHandleType.value = type;
    showPopup.value = true;
    if ([HandleType.USER_AGREEMENT, HandleType.PRIVACY_POLICY].includes(type)) {
        getData(type);
    }
    if (HandleType.EDIT_PROFILE == type) {
        if (!isLogin.value) {
            uni.$u.route({
                url: "/pages/login/login",
            });
            return;
        }
        nickname.value = userInfo.value.nickname;
    }
};

const handleUpdateUser = async (value: any) => {
    await updateUser(value, { token: userInfo.value.token });
    await userStore.getUser();
    nickname.value = userInfo.value.nickname;
    showUpdateUserPopup.value = false;
};

const updateNickname = async () => {
    if (!nickname.value) return;
    await userEdit({ field: "nickname", value: nickname.value });
    userStore.getUser();
};

const { copy } = useCopy();

const agreementType = ref(""); // 协议类型
const agreementContent = ref(""); // 协议内容

const getData = async (type: HandleType) => {
    const { content } = await getPolicy({ type });
    agreementContent.value = content;
};

const logout = () => {
    uni.showModal({
        title: "提示",
        content: "确定要退出登录吗？",
        success: async (res) => {
            if (res.confirm) {
                await userStore.logout();
                uni.$u.route({
                    url: "/pages/user/user",
                    type: "reLaunch",
                });
            }
        },
    });
};
</script>

<style scoped lang="scss">
.user-info {
    :deep(.uni-input-input) {
        text-align: end;
        font-size: 26rpx;
    }
}
</style>
