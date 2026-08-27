<template>
    <view class="h-screen flex flex-col overflow-hidden bg-[#EDF2FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :is-custom-back-icon="true"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="absolute top-0 left-0 right-0 z-[1] bg-image-container">
            <image :src="mebgUrl" class="w-full h-[600rpx] object-cover" mode="aspectFill" />
        </view>
        <view class="grow min-h-0 relative z-[20]">
            <scroll-view scroll-y class="h-full">
                <view class="relative min-h-full pb-[180rpx]">
                    <view class="relative z-10 mt-4 px-[40rpx] pb-[92rpx]">
                        <view class="relative z-10 flex items-center gap-[22rpx]">
                            <view class="relative flex-shrink-0">
                                <view
                                    class="h-[132rpx] w-[132rpx] overflow-hidden rounded-full border-[4rpx] border-[rgba(255,255,255,0.85)] bg-[#e8eef8] shadow-[0_4rpx_24rpx_rgba(0,0,0,0.12)]">
                                    <image :src="avatarUrl" class="h-full w-full" mode="aspectFill" />
                                </view>
                                <view
                                    v-if="isLogin"
                                    class="absolute bottom-0 right-0 flex h-[40rpx] w-[40rpx] items-center justify-center rounded-full bg-[#1a1a2e] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.3)]"
                                    @click.stop="handleAvatarEdit">
                                    <image :src="userIcon('plus')" class="h-[24rpx] w-[24rpx]" />
                                </view>
                            </view>

                            <view class="min-w-0 flex-1">
                                <view class="mb-[10rpx] flex min-w-0 items-center gap-[12rpx]">
                                    <text class="block truncate text-[34rpx] font-extrabold leading-[48rpx]">
                                        {{ userNickname }}
                                    </text>
                                    <view
                                        v-if="isLogin && memberLabel"
                                        class="flex flex-shrink-0 items-center gap-[6rpx] rounded-full bg-[#3B4A8A] px-[14rpx] py-[6rpx]">
                                        <image :src="userIcon('crown')" class="h-[20rpx] w-[20rpx] flex-shrink-0" />
                                        <text class="whitespace-nowrap text-[22rpx] font-bold text-white">
                                            {{ memberLabel }}
                                        </text>
                                    </view>
                                </view>
                                <view v-if="isLogin">
                                    <view class="flex max-w-full items-center gap-[8rpx] text-[22rpx] text-[#6B7280]">
                                        <text class="min-w-0 line-clamp-1" @click="copyUserSn">
                                            ID:{{ userInfo.sn || "-" }}
                                        </text>
                                        <text v-if="userInfo.mobile" class="flex-shrink-0">|</text>
                                        <text
                                            v-if="userInfo.mobile"
                                            class="min-w-0 line-clamp-1"
                                            @click="copy(userInfo.mobile)">
                                            {{ userInfo.mobile }}
                                        </text>
                                    </view>
                                    <text
                                        v-if="memberExpiryText"
                                        class="mt-[4rpx] block text-[22rpx] text-[#6B7280] line-clamp-1">
                                        会员到期：{{ memberExpiryText }}
                                    </text>
                                </view>
                                <view
                                    v-else
                                    class="inline-flex rounded-full bg-primary px-[28rpx] py-[14rpx] text-sm font-semibold text-white"
                                    @click="handleLogin">
                                    立即登录
                                </view>
                            </view>

                            <view
                                v-if="isReleaseVersion() && isLogin"
                                class="flex-shrink-0 rounded-full bg-[#FCD34D] px-[22rpx] py-[14rpx] text-[26rpx] font-bold text-[#7A4800] shadow-[0_4rpx_20rpx_rgba(252,211,77,0.35)]"
                                @click="openMembership">
                                会员订阅
                            </view>
                        </view>
                    </view>
                    <view class="relative z-20 mt-[-60rpx] px-[32rpx]">
                        <view
                            class="stat-card relative overflow-hidden rounded-[40rpx] border-[2rpx] border-solid border-white">
                            <image
                                :src="hengtiaoUrl"
                                class="pointer-events-none absolute inset-0 h-full w-full"
                                mode="aspectFill" />
                            <view class="relative z-10 grid" :style="statCardGridStyle">
                                <view
                                    v-for="(item, index) in statItems"
                                    :key="item.label"
                                    class="relative flex min-w-0 items-center justify-center"
                                    @click="handleStat(item)">
                                    <view class="flex min-w-0 max-w-full flex-col items-center py-[42rpx]">
                                        <view class="flex max-w-full items-center justify-center gap-[6rpx] px-[6rpx]">
                                            <image
                                                v-if="item.icon"
                                                :src="item.icon"
                                                class="h-[26rpx] w-[26rpx] flex-shrink-0" />
                                            <text
                                                class="block min-w-0 text-center font-semibold leading-none"
                                                :class="getStatValueClass(item)">
                                                {{ item.value }}
                                            </text>
                                        </view>
                                        <view class="mt-[12rpx] flex items-center justify-center gap-[4rpx]">
                                            <text class="text-xs text-[#94A3B8]">{{ item.label }}</text>
                                            <u-icon name="arrow-right" size="18" color="#94A3B8"></u-icon>
                                        </view>
                                    </view>
                                    <view
                                        v-if="index < statItems.length - 1"
                                        class="pointer-events-none absolute right-0 top-[50%] mt-[-32rpx] h-[64rpx] w-[2rpx] bg-[#E8EEF8]" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 我的团队 -->
                    <view v-if="isLogin" class="mx-[32rpx] mt-[28rpx] overflow-hidden rounded-[40rpx] bg-white">
                        <view class="flex items-center gap-[28rpx] px-[36rpx] py-[32rpx]" @click="handleTeamCardClick">
                            <view
                                class="team-entry-icon flex h-[90rpx] w-[90rpx] flex-shrink-0 items-center justify-center rounded-[28rpx]">
                                <image :src="userIcon('users')" class="h-[44rpx] w-[44rpx]" />
                            </view>
                            <view class="min-w-0 flex-1">
                                <view class="mb-[6rpx] flex min-w-0 items-center gap-[12rpx]">
                                    <text class="block min-w-0 truncate text-[30rpx] font-bold text-[#0F172A]">
                                        {{ teamCardTitle }}
                                    </text>
                                    <view
                                        v-if="inTeam"
                                        class="flex-shrink-0 rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold"
                                        :class="teamRoleTagClass">
                                        {{ teamRoleLabel }}
                                    </view>
                                </view>
                                <text class="block text-[24rpx] text-[#94A3B8] line-clamp-1">
                                    {{ teamCardSub }}
                                </text>
                            </view>
                            <view
                                class="flex flex-shrink-0 items-center gap-[8rpx] rounded-full bg-[#EEF4FF] px-[26rpx] py-[14rpx] text-[24rpx] font-semibold text-primary"
                                @click.stop="openTeamSwitch">
                                <image :src="userIcon('arrow_left_right')" class="h-[26rpx] w-[26rpx]" />
                                <text>切换</text>
                            </view>
                        </view>
                    </view>

                    <view v-if="isReleaseVersion()" class="grid grid-cols-2 gap-[16rpx] px-[32rpx] pt-[28rpx]">
                        <view
                            v-for="item in promotionCards"
                            :key="item.type"
                            class="flex items-center gap-[20rpx] rounded-[30rpx] bg-white px-[26rpx] py-[32rpx]"
                            @click="handleUtils(item.type)">
                            <view
                                class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center rounded-[24rpx] bg-[rgba(37,99,235,0.08)]">
                                <image :src="item.icon" class="h-[44rpx] w-[44rpx]" />
                            </view>
                            <view class="min-w-0">
                                <text class="block text-sm font-bold line-clamp-1">{{ item.label }}</text>
                                <text class="mt-[4rpx] block text-[22rpx] text-[#94A3B8] line-clamp-1">
                                    {{ item.desc }}
                                </text>
                            </view>
                        </view>
                    </view>
                    <template v-if="isReleaseVersion()">
                        <view class="section-hd">
                            <view class="section-bar" />
                            <text class="section-title">我的 AI 手机</text>
                        </view>

                        <view class="mx-[32rpx] rounded-[40rpx] bg-white overflow-hidden">
                            <view
                                v-if="deviceList.length === 0"
                                class="flex flex-col items-center gap-[28rpx] px-[40rpx] py-[56rpx]">
                                <view
                                    class="flex h-[128rpx] w-[128rpx] items-center justify-center rounded-[40rpx] bg-[#EEF4FF]">
                                    <image :src="userIcon('smartphone')" class="h-[60rpx] w-[60rpx]" />
                                </view>
                                <view class="text-center">
                                    <text class="block text-base font-bold">暂无绑定手机</text>
                                    <text class="mt-[12rpx] block text-xs leading-[40rpx] text-[#94A3B8]">
                                        添加你的 AI 手机，开启智能运营之旅
                                    </text>
                                </view>
                                <view
                                    class="flex items-center gap-[14rpx] rounded-full bg-primary px-[64rpx] py-[22rpx] text-sm font-semibold text-white shadow-[0_8rpx_28rpx_rgba(37,99,235,0.32)]"
                                    @click="openAddPhonePopup">
                                    <image :src="userIcon('plus')" class="h-[30rpx] w-[30rpx]" />
                                    <text>添加手机</text>
                                </view>
                            </view>

                            <view v-else>
                                <view
                                    v-for="(device, index) in deviceList"
                                    :key="device.device_code || index"
                                    class="mx-[40rpx] flex items-center gap-[24rpx] py-[28rpx]"
                                    :class="
                                        index < deviceList.length - 1
                                            ? 'border-[0] border-b-[2rpx] border-solid border-[#F0F4FB]'
                                            : ''
                                    "
                                    @click="handleDeviceDetail(device)">
                                    <view
                                        class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center rounded-[28rpx] bg-[rgba(37,99,235,0.08)]">
                                        <image :src="userIcon('smartphone')" class="h-[38rpx] w-[38rpx]" />
                                    </view>
                                    <view class="min-w-0 flex-1">
                                        <text class="block text-sm font-semibold line-clamp-1">
                                            {{ device.device_name || device.device_code || "-" }}
                                        </text>
                                        <text class="mt-[4rpx] block text-[22rpx] text-[#94A3B8] line-clamp-1">
                                            <template v-if="isDeviceActivated(device)">
                                                人设：{{ device.persona?.persona_name || "未绑定" }}
                                            </template>
                                            <template v-else>AI手机未激活，功能暂不可用</template>
                                        </text>
                                    </view>
                                    <template v-if="isDeviceActivated(device)">
                                        <view
                                            class="mr-[12rpx] flex flex-shrink-0 items-center gap-[8rpx] rounded-full px-[18rpx] py-[6rpx] text-[22rpx] font-semibold"
                                            :class="getDeviceStatusClass(device.status)">
                                            <view
                                                class="h-[10rpx] w-[10rpx] rounded-full"
                                                :class="getDeviceDotClass(device.status)" />
                                            <text>{{ getDeviceStatusText(device.status) }}</text>
                                        </view>
                                        <u-icon name="arrow-right" size="22" color="#CBD5E1"></u-icon>
                                    </template>
                                    <view
                                        v-if="!isDeviceActivated(device)"
                                        class="flex-shrink-0 rounded-full bg-primary px-[28rpx] py-[14rpx] text-[22rpx] font-semibold text-white shadow-[0_6rpx_20rpx_rgba(37,99,235,0.28)]"
                                        @click.stop="handleDeviceActivate(device)">
                                        激活AI手机
                                    </view>
                                </view>

                                <view class="px-[32rpx] pb-[22rpx] pt-[10rpx]">
                                    <view
                                        class="flex items-center justify-center gap-[12rpx] rounded-full bg-[#EEF4FF] py-[22rpx] text-sm font-semibold text-primary"
                                        @click="handleDeviceManage">
                                        <image :src="userIcon('smartphone')" class="h-[28rpx] w-[28rpx]" />
                                        <text>查看全部 {{ deviceTotal }} 台设备</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                    <view class="section-hd">
                        <view class="section-bar" />
                        <text class="section-title">常用设置</text>
                    </view>

                    <view class="mx-[32rpx] rounded-[40rpx] bg-white overflow-hidden">
                        <view
                            v-for="(item, index) in settingItems"
                            :key="item.label"
                            class="mx-[40rpx] flex items-center gap-[28rpx] py-[32rpx]"
                            :class="
                                index < settingItems.length - 1
                                    ? 'border-[0] border-b-[2rpx] border-solid border-[#F0F4FB]'
                                    : ''
                            "
                            @click="handleSettingItem(item)">
                            <view
                                class="flex h-[76rpx] w-[76rpx] flex-shrink-0 items-center justify-center rounded-[24rpx] bg-[rgba(37,99,235,0.08)]">
                                <image :src="item.icon" class="h-[34rpx] w-[34rpx]" />
                            </view>
                            <view class="min-w-0 flex-1">
                                <text class="block text-sm font-medium line-clamp-1">{{ item.label }}</text>
                                <text v-if="item.desc" class="mt-[4rpx] block text-[22rpx] text-[#94A3B8] line-clamp-1">
                                    {{ item.desc }}
                                </text>
                            </view>
                            <u-switch
                                v-if="item.action === 'notice'"
                                v-model="noticeEnabled"
                                size="40"
                                active-color="#2563EB"
                                inactive-color="#D9E0EE"
                                @click.stop />
                            <u-icon v-else name="arrow-right" size="22" color="#CBD5E1"></u-icon>
                        </view>
                    </view>

                    <view class="px-[32rpx] pt-[40rpx]">
                        <view
                            v-if="isLogin"
                            class="rounded-[32rpx] bg-[#FFF1F0] py-[28rpx] text-center text-base font-semibold text-[#EF4444]"
                            @click="logout">
                            退出登录
                        </view>
                        <view
                            v-else
                            class="rounded-[32rpx] bg-primary py-[28rpx] text-center text-base font-semibold text-white"
                            @click="handleLogin">
                            立即登录
                        </view>
                    </view>

                    <view class="py-[28rpx] text-center text-[22rpx] text-[#B0BCCE]">
                        <view v-if="byName">{{ byName }}</view>
                        <view v-if="copyrightConfig.length" class="mt-[8rpx]">
                            <view v-for="(item, index) in copyrightConfig" :key="index" class="mb-[4rpx]">
                                {{ item.key }}
                            </view>
                        </view>
                        <view class="mt-[8rpx]">
                            <text>Version {{ config.version }}</text>
                            <text v-if="isLogin"> · UID {{ userInfo.sn || "-" }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <update-user-info
            v-model:show="showUpdateUserPopup"
            :logo="siteShopLogo"
            :title="siteName"
            :userInfo="userInfo"
            @update="handleUpdateUser" />
        <add-phone-popup
            :model-value="showAddPhonePopup"
            :show-config-confirm="false"
            @update:model-value="showAddPhonePopup = $event"
            @bound="handleAddPhoneBound"
            @legacy-bound="handleDeviceManage" />
        <membership-subscription-popup
            v-model="showMembershipPopup"
            :plan-name="membershipPlanName"
            :plan-description="membershipPlanDescription"
            :expiry-text="membershipExpiryLabel"
            :is-member="!!memberQuota?.is_member"
            :has-superior="hasSuperior"
            :crown-icon="userIcon('crown')"
            :usage-items="membershipUsageItems"
            :submitting="membershipRedeemSubmitting"
            @redeem="handleMembershipRedeem"
            @subscribe="handleMembershipSubscribe" />
        <team-switch-popup
            v-model="showTeamSwitchPopup"
            :teams="myTeams"
            :is-personal-current="!inTeam"
            :personal-tokens="personalTokens"
            :users-icon="userIcon('users')"
            :log-in-icon="userIcon('log_in')"
            @select="handleSwitchTeam"
            @create="openTeamCreate"
            @join="openTeamJoin" />
        <switch-space-confirm-popup
            :model-value="showSwitchConfirmPopup"
            :from-name="switchConfirmFromName"
            :target-name="switchConfirmTargetName"
            :loading="switchConfirmSubmitting"
            @update:model-value="onSwitchConfirmVisible"
            @confirm="confirmSwitchSpace"
            @cancel="cancelSwitchConfirm" />
        <team-join-popup
            v-model="showTeamJoinPopup"
            v-model:code="joinCode"
            :submitting="teamSubmitting"
            :info-icon="userIcon('info')"
            @back="openTeamSwitch"
            @submit="handleJoinTeam" />
        <team-create-popup
            v-model="showTeamCreatePopup"
            v-model:name="createTeamName"
            :submitting="teamSubmitting"
            @submit="handleCreateTeam" />
        <tabbar
            v-show="
                !showMembershipPopup &&
                !showService &&
                !showTeamSwitchPopup &&
                !showSwitchConfirmPopup &&
                !showTeamJoinPopup &&
                !showTeamCreatePopup
            " />
    </view>

    <popup-bottom
        v-model="showService"
        :title="servicePopupTitle"
        height="58%"
        custom-class="bg-white"
        :mask-close-able="true">
        <template #content>
            <view class="px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <view class="flex flex-col items-center gap-[28rpx] pt-[16rpx]">
                    <view class="flex h-[320rpx] w-[320rpx] items-center justify-center rounded-[40rpx] bg-[#F4F7FD]">
                        <image
                            v-if="getServiceQrcode"
                            :src="getServiceQrcode"
                            show-menu-by-longpress
                            class="h-[280rpx] w-[280rpx] rounded-[28rpx]"
                            mode="aspectFill" />
                        <image v-else :src="userIcon('qr-code')" class="h-[160rpx] w-[160rpx]" />
                    </view>
                    <view class="text-center">
                        <text class="block text-sm font-semibold">{{ servicePopupHint }}</text>
                        <text class="mt-[10rpx] block text-xs text-[#94A3B8]">
                            工作日{{ getCustomerService.time || "09:00-22:00" }} 在线解答
                        </text>
                    </view>
                    <view
                        class="w-full rounded-[32rpx] bg-primary py-[28rpx] text-center text-base font-semibold text-white"
                        @click="saveQrcode">
                        保存二维码相册
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom
        v-model="showPolicyPopup"
        :title="policyTitle"
        height="78%"
        custom-class="bg-[#F9FAFB]"
        :mask-close-able="true">
        <template #content>
            <view class="h-full px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                <view v-if="policyLoading" class="flex h-full items-center justify-center text-xs text-[#94A3B8]">
                    加载中...
                </view>
                <scroll-view v-else scroll-y class="h-full">
                    <view class="min-h-full rounded-[28rpx] bg-white p-[32rpx]">
                        <mp-html v-if="policyContent" :content="policyContent" />
                        <view v-else class="py-[80rpx] text-center text-xs text-[#94A3B8]">暂无内容</view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import config from "@/config";
import { getPolicy } from "@/api/app";
import { getDeviceList } from "@/api/device";
import { getPersonList } from "@/api/person";
import { getAgentUserParentQrcode } from "@/api/user";
import { createTeam, getMyTeams, getTeamInfo, joinTeam, switchTeam } from "@/api/team";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { updateUser } from "@/api/account";
import { useRedeemCode } from "@/api/recharge";
import { isIOS, isAndroid } from "@/utils/client";
import { isReleaseVersion } from "@/utils/env";
import UpdateUserInfo from "@/packages/pages/login/components/update-user-info.vue";
import { useCopy } from "@/hooks/useCopy";
import AddPhonePopup from "@/components/add-phone-popup/add-phone-popup.vue";
import MembershipSubscriptionPopup from "./components/popups/membership-subscription-popup.vue";
import TeamSwitchPopup from "./components/popups/team-switch-popup.vue";
import SwitchSpaceConfirmPopup from "./components/popups/switch-space-confirm-popup.vue";
import TeamJoinPopup from "./components/popups/team-join-popup.vue";
import TeamCreatePopup from "./components/popups/team-create-popup.vue";
import mebgUrl from "../../static/images/user_center/mebg.png";
import hengtiaoUrl from "../../static/images/user_center/hengtiao.png";

type HandleUtilType = "recharge" | "agency" | "agency_invite_poster";

type StatItem = {
    label: string;
    value: string | number;
    icon?: string;
    action: "persona" | "device" | "recharge";
};

type SettingItem = {
    label: string;
    desc?: string;
    icon: string;
    action: "service" | "tutorial" | "order" | "notice" | "privacy" | "agreement";
};

type PolicyType = "service" | "privacy";

type CustomerService = {
    wx_image?: string;
    title?: string;
    time?: string;
    phone?: string;
};

// 会员订阅弹窗展示的配额项(对齐 /user/memberQuota 返回)
const MEMBERSHIP_ENTITY_DEFS = [
    { key: "robots", label: "智能体", limitKey: "max_robots", usageKey: "robots" },
    { key: "knowledges", label: "知识库", limitKey: "max_knowledges", usageKey: "knowledges" },
    { key: "personas", label: "IP 人设", limitKey: "max_personas", usageKey: "personas" },
    { key: "mobiles", label: "绑定手机", limitKey: "max_mobiles", usageKey: "mobiles" },
    { key: "digital_humans", label: "数字人形象", limitKey: "max_digital_humans", usageKey: "digital_humans" },
    { key: "voices", label: "音色克隆", limitKey: "max_voices", usageKey: "voices" },
] as const;

const userStore = useUserStore();
const { userInfo, isLogin, userTokens, personalTokens } = toRefs(userStore);
const { copy } = useCopy();

const appStore = useAppStore();
const websiteConfig = computed(() => appStore.getWebsiteConfig);
// OEM 站点用 OEM 品牌,主站回落平台配置
const siteShopLogo = computed(() => appStore.getSiteShopLogo);
const siteName = computed(() => appStore.getSiteName);
const rechargeConfig = computed(() => appStore.getRechargeConfig);
const cardCodeConfig = computed(() => appStore.getCardCodeConfig);
const copyrightConfig = computed(() => appStore.getCopyRightConfig);
const byName = computed(() => appStore.getByName);

const userIconMap = import.meta.glob("../../static/icons/user_center/*.svg", {
    eager: true,
    import: "default",
    query: "?url",
}) as Record<string, string>;
const userIcon = (name: string): string => {
    const fileName = `${name.replace(/-/g, "_")}.svg`;
    const entry = Object.entries(userIconMap).find(([path]) => path.endsWith(`/${fileName}`));
    return entry?.[1] ?? "";
};

const showUpdateUserPopup = ref(false);
const showService = ref(false);
const showAddPhonePopup = ref(false);
const showMembershipPopup = ref(false);
const showTeamSwitchPopup = ref(false);
const showSwitchConfirmPopup = ref(false);
const switchConfirmTarget = ref<any>(null);
const switchConfirmSubmitting = ref(false);
const showTeamJoinPopup = ref(false);
const showTeamCreatePopup = ref(false);
const teamInfo = ref<any>(null);
const myTeams = ref<any[]>([]);
const joinCode = ref("");
const createTeamName = ref("");
const teamSubmitting = ref(false);

const inTeam = computed(() => Number(teamInfo.value?.in_team) === 1 && !!teamInfo.value?.name);
const teamRoleLabel = computed(() => {
    const role = Number(teamInfo.value?.team_role);
    if (role === 2) return "创始人";
    if (role === 3) return "管理员";
    return "成员";
});
const teamRoleTagClass = computed(() => {
    const role = Number(teamInfo.value?.team_role);
    if (role === 2) return "bg-[#DBEAFE] text-[#1D4ED8]";
    if (role === 3) return "bg-[#EDE9FE] text-[#6D28D9]";
    return "bg-[#F1F5F9] text-[#64748B]";
});
const teamCardTitle = computed(() => (inTeam.value ? teamInfo.value.name : "我的团队"));
const teamCardSub = computed(() => {
    if (!inTeam.value) return "创建或加入团队，协作使用算力";
    const count = Number(teamInfo.value?.member_count);
    const countText = Number.isFinite(count) && count > 0 ? `${count} 名成员` : "已加入";
    const mine = Number(teamInfo.value?.team_role) === 2 ? "我创建的" : "已加入";
    return Number.isFinite(count) && count > 0 ? `${countText} · ${mine}` : mine;
});
const hasOwnedTeam = computed(() => myTeams.value.some((t) => Number(t.is_owner) === 1));
/** 从会员订阅入口打开客服弹窗时，文案引导获取兑换码 */
const serviceFromMembership = ref(false);
const memberQuota = computed(() => appStore.memberQuota);
const showPolicyPopup = ref(false);
const policyTitle = ref("");
const policyContent = ref("");
const policyLoading = ref(false);
const noticeEnabled = ref(true);
const personaTotal = ref(0);
const deviceTotal = ref(0);
const deviceList = ref<any[]>([]);
const agentUserParentQrcode = ref("");
const membershipRedeemSubmitting = ref(false);

const avatarUrl = computed(() => {
    if (isLogin.value) {
        return userInfo.value.avatar || siteShopLogo.value;
    }
    return siteShopLogo.value;
});
const userNickname = computed(() => (isLogin.value ? userInfo.value.nickname || "未命名用户" : "未登录"));
const memberLabel = computed(() => userInfo.value.level_name);
const memberExpiryText = computed(() => {
    const expiry =
        userInfo.value.member_expire_time ??
        userInfo.value.level_expire_time ??
        userInfo.value.vip_expire_time ??
        userInfo.value.expire_time;
    if (!expiry) return "";
    return `${expiry}`.split(" ")[0];
});
const formatMemberDate = (ts: number) => {
    if (!ts) return "";
    const d = new Date(ts * 1000);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

const membershipPlanName = computed(() => memberQuota.value?.level_name || memberLabel.value || "会员套餐");
const membershipPlanDescription = computed(() =>
    memberQuota.value?.is_member ? "尊享全部会员权益，畅享 AI 创作能力" : "升级会员，解锁更多创作与资源权益",
);
const membershipExpiryLabel = computed(() => {
    if (memberQuota.value?.is_member && memberQuota.value?.end_time) {
        return `至 ${formatMemberDate(memberQuota.value.end_time)}`;
    }
    return memberExpiryText.value ? `至 ${memberExpiryText.value}` : "尚未开通会员";
});
const membershipUsageItems = computed(() => {
    const quota = memberQuota.value?.quota ?? {};
    const usage = memberQuota.value?.usage ?? {};
    return MEMBERSHIP_ENTITY_DEFS.map((e) => {
        const limit = Number(quota[e.limitKey] ?? 0);
        return {
            key: e.key,
            label: e.label,
            used: Number(usage[e.usageKey] ?? 0),
            limit,
            limitText: limit === -1 ? "禁止" : limit === 0 ? "不限" : String(limit),
        };
    });
});

const getCustomerService = computed<CustomerService>(() => {
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
// 会员订阅引导：以 userInfo.has_parent_agent 区分联系上级 / 平台客服
const hasSuperior = computed(() => {
    const v = userInfo.value?.has_parent_agent;
    return v === true || Number(v) === 1;
});
const getServiceQrcode = computed(() => {
    if (serviceFromMembership.value) {
        return hasSuperior.value ? agentUserParentQrcode.value || "" : getCustomerService.value.wx_image || "";
    }
    return agentUserParentQrcode.value || getCustomerService.value.wx_image || "";
});
const servicePopupTitle = computed(() => {
    if (!serviceFromMembership.value) return "联系客服";
    return hasSuperior.value ? "联系上级获取兑换码" : "联系客服获取兑换码";
});
const servicePopupHint = computed(() => {
    if (!serviceFromMembership.value) return "扫码添加专属客服微信";
    return hasSuperior.value ? "扫码添加上级微信，获取会员兑换码" : "扫码添加平台客服微信，获取会员兑换码";
});

const statItems = computed<StatItem[]>(() => {
    const items: StatItem[] = [
        {
            label: "IP人设",
            value: formatStatValue(personaTotal.value),
            action: "persona",
        },
        {
            label: "AI手机",
            value: formatStatValue(deviceTotal.value),
            action: "device",
        },
        {
            label: "算力余额",
            value: formatStatValue(userTokens.value),
            icon: userIcon("zap"),
            action: "recharge",
        },
    ];
    return isReleaseVersion() ? items : items.filter((item) => !["recharge", "device"].includes(item.action));
});

const promotionCards = [
    {
        label: "代理中心",
        desc: "推广与激活",
        type: "agency" as const,
        icon: userIcon("git_branch"),
    },
    {
        label: "邀请好友",
        desc: "共拓商机",
        type: "agency_invite_poster" as const,
        icon: userIcon("user_plus"),
    },
];

const settingItems = computed<SettingItem[]>(() => {
    const list: SettingItem[] = [
        {
            label: "联系客服",
            icon: userIcon("headphones"),
            action: "service",
        },
        {
            label: "使用教程",
            icon: userIcon("book_open"),
            action: "tutorial",
        },
        {
            label: "我的订单",
            icon: userIcon("receipt"),
            action: "order",
        },
        // {
        //     label: "小程序消息通知",
        //     desc: "接收订单、算力消耗等消息",
        //     icon: userIcon("bell"),
        //     action: "notice",
        // },
        {
            label: "隐私政策",
            icon: userIcon("shield"),
            action: "privacy",
        },
        {
            label: "用户协议",
            icon: userIcon("file_text"),
            action: "agreement",
        },
    ];
    return isReleaseVersion() ? list : list.filter((item) => !["service", "order"].includes(item.action));
});

const isDeviceActivated = (device: Record<string, any>): boolean => {
    const value =
        device.auth_status ??
        device.is_active ??
        device.is_activated ??
        device.activate_status ??
        device.activation_status ??
        device.active_status;
    if (typeof value === "boolean") return value;
    if (typeof value === "number") return value === 1;
    return false;
};

const formatStatValue = (value: string | number | undefined) => {
    if (!isLogin.value) return "-";
    return value || "0";
};

const getStatDigitLength = (value: string | number) => `${value}`.replace(/\D/g, "").length;

const rechargeStatDigitLength = computed(() => {
    const rechargeStat = statItems.value.find((item) => item.action === "recharge");
    return getStatDigitLength(rechargeStat?.value ?? "");
});

const statCardGridStyle = computed(() => ({
    "grid-template-columns": rechargeStatDigitLength.value > 6 ? "0.86fr 0.86fr 1.28fr" : "1fr 1fr 1fr",
}));

const getStatValueClass = (item: StatItem) => {
    if (item.action !== "recharge") return "max-w-full text-[48rpx]";
    if (getStatDigitLength(item.value) >= 9) return "max-w-[200rpx] truncate text-[36rpx]";
    if (getStatDigitLength(item.value) > 5) return "max-w-[300rpx] text-[36rpx]";
    return "max-w-full text-[48rpx]";
};

const handleLogin = () => {
    uni.$u.route({
        url: "/packages/pages/login/login",
    });
};

const handleAvatarEdit = () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    showUpdateUserPopup.value = true;
};

const copyUserSn = () => {
    if (userInfo.value.sn) {
        copy(userInfo.value.sn);
    }
};

const handleStat = (item: StatItem) => {
    if (item.action === "persona") {
        // 企业空间成员已过期:禁止进入创建 IP(与团队卡片拦截一致)
        if (inTeam.value && Number(teamInfo.value?.expired) === 1) {
            uni.$u.toast("当前空间成员资格已过期，无法使用");
            return;
        }
        uni.$u.route({ url: "/ai_modules/person/pages/index/index" });
        return;
    }
    if (item.action === "device") {
        handleDeviceManage();
        return;
    }
    handleRecharge();
};

const handleSettingItem = (item: SettingItem) => {
    switch (item.action) {
        case "service":
            openService();
            break;
        case "tutorial":
            uni.$u.route({ url: "/packages/pages/tutorial/tutorial" });
            break;
        case "order":
            uni.$u.route({ url: "/packages/pages/user_balance/user_balance?type=order" });
            break;
        case "privacy":
            openPolicyPopup("privacy");
            break;
        case "agreement":
            openPolicyPopup("service");
            break;
        case "notice":
            break;
    }
};

const handleUpdateUser = async (value: any) => {
    await updateUser(value, { token: userInfo.value.token });
    userStore.getUser();
    showUpdateUserPopup.value = false;
};

const handleRecharge = () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    // OEM 站点统一进算力中心(页内二维码+兑换，不走套餐购买)
    if (appStore.isOemSite) {
        appStore.openRecharge();
        return;
    }
    let pathUrl = "/packages/pages/redeem/redeem";
    if (cardCodeConfig.value.is_open == 1) {
        if (
            (isIOS() && rechargeConfig.value.is_ios_open == 1) ||
            (isAndroid() && rechargeConfig.value.is_and_open == 1)
        ) {
            pathUrl = "/packages/pages/recharge/recharge";
        }
    } else {
        pathUrl = "/packages/pages/recharge/recharge";
    }

    uni.$u.route({ url: pathUrl });
};

const handleUtils = (type: HandleUtilType) => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }

    if (type === "recharge") {
        handleRecharge();
        return;
    }

    if (userInfo.value.is_distribution_agent != 1) {
        uni.$u.toast("您不是代理，无法进入代理中心");
        return;
    }

    uni.$u.route({
        url:
            type === "agency"
                ? "/packages/pages/agency/agency"
                : "/packages/pages/agency_invite_poster/agency_invite_poster",
    });
};

const handleDeviceManage = () => {
    uni.$u.route({ url: "/ai_modules/device/pages/index/index" });
};

const handleDeviceDetail = (device: any) => {
    if (!isDeviceActivated(device)) {
        uni.$u.toast("设备未激活");
        return;
    }
    if (!device?.device_code) {
        handleDeviceManage();
        return;
    }
    uni.$u.route({
        url: `/ai_modules/device/pages/detail/detail?device_code=${device.device_code}`,
    });
};

const handleDeviceActivate = (device: any) => {
    if (!device?.device_code) {
        handleDeviceManage();
        return;
    }
    uni.$u.route({
        url: `/ai_modules/device/pages/detail/detail?device_code=${device.device_code}&activate=1`,
    });
};

const getDeviceStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: "离线",
        1: "空闲",
        2: "运行中",
    };
    return map[status] || "运行中";
};

const getDeviceStatusClass = (status: number) => {
    if (status === 0) return "bg-[#F8FAFC] text-[#94A3B8]";
    if (status === 1) return "bg-[#F0FDF4] text-[#16A34A]";
    return "bg-primary-light-9 text-primary";
};

const getDeviceDotClass = (status: number) => {
    if (status === 0) return "bg-[#CBD5E1]";
    if (status === 1) return "bg-[#22C55E]";
    return "bg-primary";
};

const openAddPhonePopup = () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    showAddPhonePopup.value = true;
};

const loadTeamInfo = async () => {
    if (!isLogin.value) {
        teamInfo.value = null;
        return;
    }
    try {
        teamInfo.value = await getTeamInfo();
    } catch {
        teamInfo.value = null;
    }
};

const loadMyTeams = async () => {
    if (!isLogin.value) {
        myTeams.value = [];
        return;
    }
    try {
        myTeams.value = (await getMyTeams()) || [];
    } catch {
        myTeams.value = [];
    }
};

const openTeamSwitch = async () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    showTeamJoinPopup.value = false;
    showTeamCreatePopup.value = false;
    await loadMyTeams();
    showTeamSwitchPopup.value = true;
};

const openTeamJoin = () => {
    showTeamSwitchPopup.value = false;
    joinCode.value = "";
    showTeamJoinPopup.value = true;
};

const openTeamCreate = () => {
    if (hasOwnedTeam.value) {
        const mine = myTeams.value.find((t) => Number(t.is_owner) === 1);
        uni.$u.toast(`每人仅可创建 1 个团队，你已创建「${mine?.name || ""}」`);
        return;
    }
    showTeamSwitchPopup.value = false;
    createTeamName.value = "";
    showTeamCreatePopup.value = true;
};

const handleTeamCardClick = () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    if (inTeam.value) {
        if (Number(teamInfo.value?.expired) === 1) {
            uni.$u.toast("当前空间成员资格已过期，无法进入");
            return;
        }
        uni.$u.route({ url: "/packages/pages/team/team" });
        return;
    }
    openTeamSwitch();
};

const refreshTeamState = async () => {
    await Promise.all([loadTeamInfo(), loadMyTeams(), userStore.getUser()]);
};

const switchConfirmFromName = computed(() => (inTeam.value ? String(teamInfo.value?.name || "当前空间") : "个人空间"));
const switchConfirmTargetName = computed(() =>
    String(
        switchConfirmTarget.value?.name || (Number(switchConfirmTarget.value?.team_id) === 0 ? "个人空间" : "该空间"),
    ),
);

const handleSwitchTeam = (item: any) => {
    const teamId = Number(item?.team_id);
    const toPersonal = teamId === 0;
    // 成员资格已过期的团队不允许进入(与 PC 端一致)
    if (!toPersonal && Number(item?.expired) === 1) {
        uni.$u.toast("该团队成员资格已过期，无法进入");
        return;
    }
    // 个人空间没有 is_current 字段，用 inTeam 判断是否已在个人空间
    if (toPersonal ? !inTeam.value : Number(item?.is_current) === 1) {
        showTeamSwitchPopup.value = false;
        return;
    }
    switchConfirmTarget.value = item;
    showTeamSwitchPopup.value = false;
    showSwitchConfirmPopup.value = true;
};

const onSwitchConfirmVisible = (v: boolean) => {
    showSwitchConfirmPopup.value = v;
    // 遮罩关闭：回到空间列表（成功确认会先清空 target，避免误开）
    if (!v && !switchConfirmSubmitting.value && switchConfirmTarget.value) {
        switchConfirmTarget.value = null;
        showTeamSwitchPopup.value = true;
    }
};

const cancelSwitchConfirm = () => {
    if (switchConfirmSubmitting.value) return;
    switchConfirmTarget.value = null;
    showSwitchConfirmPopup.value = false;
    showTeamSwitchPopup.value = true;
};

const confirmSwitchSpace = async () => {
    const item = switchConfirmTarget.value;
    if (!item || switchConfirmSubmitting.value) return;
    const teamId = Number(item.team_id);
    const targetName = switchConfirmTargetName.value;
    switchConfirmSubmitting.value = true;
    try {
        await switchTeam({ team_id: teamId });
        uni.$u.toast(`已切换到「${targetName}」`);
        switchConfirmTarget.value = null;
        showSwitchConfirmPopup.value = false;
        await refreshTeamState();
        queryPersonaCount();
        queryDeviceList();
    } catch (e: any) {
        uni.$u.toast(typeof e === "string" ? e : e?.msg || "切换失败");
    } finally {
        switchConfirmSubmitting.value = false;
    }
};

const handleJoinTeam = async () => {
    if (teamSubmitting.value) return;
    const code = joinCode.value.trim();
    if (code.length < 4) {
        uni.$u.toast("请输入邀请码");
        return;
    }
    teamSubmitting.value = true;
    try {
        await joinTeam({ code });
        uni.$u.toast("加入成功");
        showTeamJoinPopup.value = false;
        await refreshTeamState();
    } catch (e: any) {
        uni.$u.toast(typeof e === "string" ? e : e?.msg || "加入失败");
    } finally {
        teamSubmitting.value = false;
    }
};

const handleCreateTeam = async () => {
    if (teamSubmitting.value) return;
    const name = createTeamName.value.trim();
    if (!name) {
        uni.$u.toast("请输入团队名称");
        return;
    }
    teamSubmitting.value = true;
    try {
        await createTeam({ name });
        uni.$u.toast("团队创建成功");
        showTeamCreatePopup.value = false;
        await refreshTeamState();
    } catch (e: any) {
        uni.$u.toast(typeof e === "string" ? e : e?.msg || "创建失败");
    } finally {
        teamSubmitting.value = false;
    }
};

const handleAddPhoneBound = () => {
    queryDeviceList();
};

const openService = () => {
    serviceFromMembership.value = false;
    showService.value = true;
};

const handleMembershipSubscribe = async () => {
    // 先关会员弹窗，避免与客服弹窗叠层
    showMembershipPopup.value = false;
    // 是代理：拉上级二维码；非代理：走平台客服码
    if (hasSuperior.value) {
        await getAgentParentQrcode();
    }
    serviceFromMembership.value = true;
    if (!getServiceQrcode.value) {
        serviceFromMembership.value = false;
        uni.$u.toast(hasSuperior.value ? "暂无上级二维码" : "暂无客服二维码");
        return;
    }
    showService.value = true;
};

const loadMemberQuota = () => appStore.ensureMemberQuota(true);

const openMembership = async () => {
    if (!isLogin.value) {
        handleLogin();
        return;
    }
    // 每次打开都实时请求最新会员配额/用量
    await loadMemberQuota();
    showMembershipPopup.value = true;
};

const handleMembershipRedeem = async (code: string) => {
    if (membershipRedeemSubmitting.value) return;
    membershipRedeemSubmitting.value = true;
    try {
        await useRedeemCode({ sn: code });
        uni.$u.toast("兑换成功");
        // getUser 内已 ensureMemberQuota(true)，勿再重复请求
        await userStore.getUser();
        showMembershipPopup.value = false;
    } catch (error) {
        uni.$u.toast(error || "兑换失败");
    } finally {
        membershipRedeemSubmitting.value = false;
    }
};

const openPolicyPopup = async (type: PolicyType) => {
    policyTitle.value = type === "service" ? "用户协议" : "隐私政策";
    policyContent.value = "";
    policyLoading.value = true;
    showPolicyPopup.value = true;
    try {
        const { content } = await getPolicy({ type });
        policyContent.value = content || "";
    } catch {
        uni.$u.toast("协议内容加载失败");
    } finally {
        policyLoading.value = false;
    }
};

const saveQrcode = () => {
    if (!getServiceQrcode.value) {
        uni.$u.toast("暂无客服二维码");
        return;
    }
    uni.downloadFile({
        url: getServiceQrcode.value,
        success: (result) => {
            uni.saveImageToPhotosAlbum({
                filePath: result.tempFilePath,
                success: () => {
                    uni.$u.toast("保存成功");
                },
                fail: () => {
                    uni.$u.toast("保存失败");
                },
            });
        },
        fail: () => {
            uni.$u.toast("保存失败");
        },
    });
};

const logout = () => {
    uni.showModal({
        title: "提示",
        content: "确定要退出登录吗？",
        success: async (res) => {
            if (res.confirm) {
                await userStore.logout();
                uni.$u.route({
                    url: "/packages/pages/user/user",
                    type: "reLaunch",
                });
            }
        },
    });
};

const queryPersonaCount = async () => {
    if (!isLogin.value) {
        personaTotal.value = 0;
        return;
    }
    try {
        const res = await getPersonList({ page_no: 1, page_size: 1 });
        personaTotal.value = res?.count ?? res?.total ?? 0;
    } catch {
        personaTotal.value = 0;
    }
};

const queryDeviceList = async () => {
    if (!isLogin.value) {
        deviceList.value = [];
        deviceTotal.value = 0;
        return;
    }
    try {
        const res = await getDeviceList({ page_no: 1, page_size: 2 });
        deviceList.value = res?.lists || [];
        deviceTotal.value = res?.count ?? res?.total ?? deviceList.value.length;
    } catch {
        deviceList.value = [];
        deviceTotal.value = 0;
    }
};

const getAgentParentQrcode = async () => {
    if (!isLogin.value) {
        agentUserParentQrcode.value = "";
        return;
    }
    try {
        const res = await getAgentUserParentQrcode();
        agentUserParentQrcode.value = res?.qr_code || "";
    } catch {
        agentUserParentQrcode.value = "";
    }
};

watch(showService, (visible) => {
    if (!visible) serviceFromMembership.value = false;
});

onShow(() => {
    userStore.getUser();
    queryPersonaCount();
    queryDeviceList();
    getAgentParentQrcode();
    loadTeamInfo();
    loadMyTeams();
});

onUnload(() => {
    showMembershipPopup.value = false;
    showService.value = false;
    serviceFromMembership.value = false;
    showTeamSwitchPopup.value = false;
    showTeamJoinPopup.value = false;
    showTeamCreatePopup.value = false;
});
</script>

<style lang="scss" scoped>
.user-hero-mask {
    background: linear-gradient(180deg, rgba(237, 242, 250, 0) 0%, rgba(237, 242, 250, 0.42) 32%, #edf2fa 100%);
}

.stat-card {
    box-shadow: 0 10rpx 40rpx rgba(60, 100, 200, 0.1);
}

.stat-card-cover {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.76) 0%, rgba(255, 255, 255, 0.9) 100%);
}

.section-hd {
    @apply flex items-center gap-[16rpx] px-[40rpx] pb-[20rpx] pt-[40rpx];
}

.section-bar {
    @apply h-[32rpx] w-[8rpx] flex-shrink-0 rounded-full bg-primary;
}

.section-title {
    @apply flex-1 text-base font-bold text-[#0F172A];
}

.team-entry-icon {
    background: linear-gradient(135deg, #2563eb, #4f8bff);
    box-shadow: 0 6rpx 20rpx rgba(37, 99, 235, 0.28);
}
</style>
