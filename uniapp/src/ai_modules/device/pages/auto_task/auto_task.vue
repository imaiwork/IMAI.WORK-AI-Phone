<template>
    <view class="auto-task-page" v-if="!loading">
        <view class="relative z-[9999]">
            <u-navbar
                title="24h自动任务配置"
                title-bold
                :border-bottom="false"
                :is-fixed="false"
                :background="{
                    background: 'transparent',
                }">
            </u-navbar>
        </view>
        <view class="mx-4">
            <view
                class="rounded-[20rpx] bg-[#ffffff80] px-[36rpx] py-[22rpx] relative"
                :class="{ 'opacity-50': !isCompleteConfig }">
                <view class="flex items-center justify-between">
                    <view class="font-medium text-[30rpx]">社媒账号</view>
                    <view class="flex items-center gap-x-1">
                        <template v-if="!isCompleteConfig || isAllGetSuccess">
                            <image
                                src="@/ai_modules/device/static/icons/success.svg"
                                class="w-[28rpx] h-[28rpx]"></image>
                            <view class="text-[#00C08E] font-medium">已获取</view>
                        </template>
                        <view v-else class="absolute right-3 top-0">
                            <view
                                class="py-[32rpx] flex justify-center items-center gap-x-2"
                                @click="showGetAccountPopup = true">
                                <text class="text-primary font-medium">详情</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-x-2 mt-[22rpx]">
                    <view v-for="(item, index) in sortedPlatform" :key="index">
                        <image
                            :src="!isCompleteConfig ? item.activeIcon : item.icon"
                            class="w-[48rpx] h-[48rpx]"></image>
                    </view>
                </view>
            </view>
            <navigator
                :url="`/ai_modules/device/pages/create_auto_task/create_auto_task?device_code=${deviceCode}`"
                class="rounded-[20rpx] bg-[#ffffff80] px-[36rpx] py-4 flex items-center justify-between mt-[12rpx]">
                <view class="font-medium text-[30rpx]">运营策略方案</view>
                <view class="flex items-center gap-x-1 text-[#000000]/40">
                    查看<u-icon name="arrow-right" size="20" color="#9DA5B0"></u-icon>
                </view>
            </navigator>
        </view>
        <view class="px-4 mt-4 flex justify-end">
            <view class="flex items-center gap-x-1" @click="showFeePopup = true">
                <text class="text-xs text-[#535354]">如何扣费</text>
                <u-icon name="question-circle" color="#535354"></u-icon>
            </view>
        </view>
        <view
            class="grow min-h-0 mt-[28rpx] py-[30rpx] bg-[#F3F4FB] rounded-tl-[40rpx] rounded-tr-[40rpx] flex flex-col">
            <view class="px-[42rpx] flex items-center justify-between">
                <view class="flex items-center gap-x-1">
                    <view class="font-medium text-[30rpx]">24h任务列表</view>
                    <!-- <image src="@/ai_modules/device/static/icons/tips.svg" class="w-[24rpx] h-[24rpx]"></image> -->
                </view>
                <view class="flex items-center gap-x-1">
                    <template v-if="autoTaskDetail.is_config == 1">
                        <image src="@/ai_modules/device/static/icons/success.svg" class="w-[28rpx] h-[28rpx]"></image>
                        <view class="text-[#00C08E] font-medium">可执行</view>
                    </template>
                    <view v-else class="text-[#FF2442] text-xs font-medium">无法执行，配置未完整</view>
                </view>
            </view>
            <view class="grow min-h-0 mt-2">
                <z-paging
                    ref="pagingRef"
                    v-model="taskTimeConfig"
                    :auto="false"
                    :fixed="false"
                    :loading-more-enabled="false"
                    @query="queryList">
                    <view class="px-[34rpx] flex flex-col gap-y-4">
                        <view
                            v-for="(item, index) in taskTimeConfig"
                            :key="index"
                            class="flex items-center gap-x-2"
                            @click="toTaskConfig(item)">
                            <view
                                class="flex-shrink-0 flex flex-col items-center justify-center w-[100rpx] gap-y-[10rpx]">
                                <view class="text-[#00000080] font-medium">{{ item.time[0] }}</view>
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="w-[5rpx] h-[8rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                    <view class="w-[5rpx] h-[10rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                    <view class="w-[5rpx] h-[8rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                </view>
                                <view class="text-[#00000080] font-medium">{{ item.time[1] }}</view>
                            </view>
                            <view class="bg-white flex-1 relative rounded-[20rpx] px-[40rpx] py-[30rpx]">
                                <view
                                    class="absolute top-[50%] left-0 h-[100rpx] w-[6rpx] rounded-[20rpx]"
                                    :style="{
                                        background: item.color,
                                        transform: 'translateY(-50%)',
                                    }"></view>
                                <view class="flex items-center justify-between">
                                    <view class="flex items-center gap-x-[4rpx]">
                                        <image
                                            v-for="(val, index) in item.platform"
                                            :key="index"
                                            :src="val.activeIcon"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                    </view>
                                    <view
                                        v-if="!item.disabled"
                                        class="rounded-[10rpx] h-[44rpx] px-2 flex items-center justify-center gap-x-1 bg-[#FDF3E3]"
                                        @click.stop="handleDemo(item)">
                                        <image
                                            src="@/ai_modules/device/static/icons/window.svg"
                                            class="w-[20rpx] h-[20rpx]"></image>
                                        <text class="text-[#BA6F0D]">立即执行</text>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between mt-2">
                                    <view class="flex items-center gap-x-1">
                                        <image
                                            src="@/ai_modules/device/static/icons/task.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <text class="text-xs text-[#0000004d] font-medium">任务类型</text>
                                    </view>
                                </view>
                                <view class="mt-[6rpx] flex items-center gap-x-[10rpx]">
                                    <text class="text-[34rpx] font-medium">
                                        {{ item.name }}
                                    </text>
                                    <!-- <image
                                        src="@/ai_modules/device/static/icons/tips.svg"
                                        class="w-[24rpx] h-[24rpx]"></image> -->
                                </view>
                                <view class="h-[1rpx] bg-[#F2F2F2] my-[24rpx]"></view>
                                <view class="flex items-center justify-between">
                                    <view
                                        class="font-medium"
                                        :class="[
                                            item.disabled
                                                ? 'text-[#0000004d]'
                                                : item.status == 1 || item.status == 3 || !isCompleteConfig
                                                ? 'text-[#00C08E]'
                                                : item.status == 0
                                                ? 'text-[#FF2442]'
                                                : item.status == 2
                                                ? 'text-primary'
                                                : '',
                                        ]">
                                        <template v-if="item.disabled">敬请期待 </template>
                                        <template v-else>
                                            <template v-if="item.status == 1 || !isCompleteConfig"> 已配置 </template>
                                            <template v-else-if="item.status == 0"> 未配置 </template>
                                            <template v-else-if="item.status == 2"> 部分配置 </template>
                                            <template v-else-if="item.status == 3">已预设，无需配置 </template>
                                        </template>
                                    </view>
                                    <view
                                        class="flex items-center gap-x-[4rpx]"
                                        v-if="item.status != 3 && !item.disabled">
                                        <text class="text-[#00000066]">去配置</text>
                                        <u-icon name="arrow-right" size="20" color="#0000004d"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </z-paging>
            </view>
        </view>
        <view class="absolute h-full bottom-0 left-0 w-full z-[88] flex flex-col justify-end" v-if="!isCompleteConfig">
            <view
                class="h-full w-full pt-[200rpx]"
                style="background: linear-gradient(360deg, #f3f4fb 30%, transparent 100%)">
                <view class="flex flex-col items-center absolute bottom-[15vh] w-full">
                    <view class="font-medium text-primary text-[40rpx]"> 请完善首次设置 </view>
                    <view
                        class="mt-[46rpx] w-[240rpx] h-[90rpx] flex items-center justify-center font-medium text-white text-[30rpx] bg-primary rounded-lg"
                        @click="toPage()"
                        >立即设置</view
                    >
                    <view class="mt-10 flex items-center gap-x-1" @click="showFeePopup = true">
                        <text class="text-[28rpx] text-[#535354]">如何扣费</text>
                        <u-icon name="question-circle" color="#535354"></u-icon>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <account-get
        v-if="showGetAccountPopup"
        v-model="showGetAccountPopup"
        :sorted-platform="sortedPlatform"
        @get-account="handleGetAccount(deviceCode, false)" />
    <u-popup v-model="showChooseApp" mode="center" border-radius="20" width="80%">
        <view class="bg-white p-6 rounded-2xl">
            <text class="text-xl font-medium mb-2 block">选择平台</text>
            <text class="text-sm text-[#9ca3af] mb-5 block">请选择您要发布的平台</text>

            <view class="space-y-3">
                <view
                    v-for="platform in chooseAppPlatforms"
                    :key="platform.id"
                    class="flex items-center p-4 border-2 rounded-xl gap-x-3 transition-all duration-200"
                    :class="[
                        selectedPlatform?.type === platform.type
                            ? 'border-[#3b82f6] bg-[#eff6ff] shadow-md'
                            : 'border-[#f3f4f6] bg-[#f9fafb] hover:border-[#e5e7eb]',
                    ]"
                    @click="selectPlatform(platform)">
                    <view
                        class="w-[80rpx] h-[80rpx] rounded-xl flex items-center justify-center"
                        :class="selectedPlatform?.type === platform.type ? 'bg-[#dbeafe]' : 'bg-white'">
                        <image :src="platform.activeIcon" class="w-[48rpx] h-[48rpx]"></image>
                    </view>
                    <view class="flex-1">
                        <text class="font-semibold text-base block">{{ platform.name }}</text>
                        <text class="text-xs text-[#9ca3af]" v-if="platform.desc">{{ platform.desc }}</text>
                    </view>
                    <view
                        v-if="selectedPlatform?.type === platform.type"
                        class="w-[40rpx] h-[40rpx] rounded-full bg-[#3b82f6] flex items-center justify-center">
                        <u-icon name="checkmark" color="#fff" size="24rpx"></u-icon>
                    </view>
                </view>
            </view>

            <view class="mt-8 flex gap-3">
                <u-button
                    :custom-style="{
                        height: '90rpx',
                        fontWeight: 'bold',
                        borderRadius: '20rpx',
                    }"
                    @click="showChooseApp = false">
                    取消
                </u-button>
                <u-button
                    type="primary"
                    :custom-style="{
                        height: '90rpx',
                        fontWeight: 'bold',
                        borderRadius: '20rpx',
                    }"
                    @click="confirmSelection()">
                    确认选择
                </u-button>
            </view>
        </view>
    </u-popup>
    <popup-bottom v-model="showFeePopup" title="24小时自动任务算力消耗明细" custom-class="bg-[#F7F8FA]">
        <template #content>
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pb-[50rpx] pt-2 space-y-3">
                    <view
                        v-for="(item, index) in getTaskCostConfig"
                        :key="index"
                        class="bg-white rounded-[20rpx] p-4 flex items-center justify-between relative overflow-hidden">
                        <view class="flex items-center gap-3 flex-1 mr-4">
                            <view class="flex flex-col">
                                <text class="text-[30rpx] font-medium text-slate-800 mb-1">{{ item.name }}</text>
                                <text class="text-[24rpx] text-slate-400 leading-snug line-clamp-1">
                                    {{ item.description }}
                                </text>
                            </view>
                        </view>

                        <view class="flex flex-col items-end shrink-0">
                            <view class="flex items-baseline gap-0.5">
                                <text class="text-[36rpx] font-medium text-primary">{{ item.score }}</text>
                                <text class="text-[22rpx] text-slate-500 font-medium">{{ item.unit }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <confirm-dialog
        v-model="showConfirmDemoDialog"
        title="提示"
        content="当前暂无真实数据，将使用模拟数据进行演示。模拟数据仅用于展示效果，不会影响后续实际使用。是否确认进入演示模式?"
        @confirm="startDemoTask"></confirm-dialog>
</template>

<script setup lang="ts">
import { getDeviceDetail, getAutoTaskDetail, checkRealTask, createDemoTask } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { useUserStore } from "@/stores/user";
import { AppTypeEnum } from "@/enums/appEnums";
import CircleIcon from "@/ai_modules/device/static/images/common/circle.png";
import SphIcon from "@/static/images/common/sph_s.png";
import PhoneIcon from "@/ai_modules/device/static/images/common/phone.png";
import AccountGet from "@/ai_modules/device/components/account-get/account-get.vue";

enum TaskKeyEnum {
    CLUES_SETTING = "clues_setting",
    TAKEOVER_SETTING = "takeover_setting",
    PUBLISH_SETTING = "publish_setting",
    TOUCH_SETTING = "touch_setting",
    ADD_WECHAT_SETTING = "add_wechat_setting",
    AUTO_ACCOUNT = "auto_account",
    CIRCLE_INTERACTION = "circle_like_reply_setting",
    CIRCLE_RELEASE = "wechat_circle_setting",
}

const userStore = useUserStore();

const { platform, sortedPlatform, connectWebSocket, initializePlatform, handleGetAccount } = useDevice({
    onAccountsUpdated: async () => {
        getDetail();
    },
});

const pagingRef = ref<any>(null);

const loading = ref(true);
const deviceCode = ref("");
const deviceDetail = ref<any>({});
const autoTaskDetail = ref<any>({});

const showChooseApp = ref(false);
const showGetAccountPopup = ref(false);
const showConfirmDemoDialog = ref(false);
// 是否完成配置
const isCompleteConfig = ref(false);
const showFeePopup = ref(false);
const taskMap: any = {
    // 关键词获客
    keyword_customer: {
        key: TaskKeyEnum.CLUES_SETTING,
        name: "关键词获客",
        status: 0,
        platform: [platform.value[AppTypeEnum.SPH]],
        color: "#CADEFD",
    },
    // 私信接管
    private_message_takeover: {
        key: TaskKeyEnum.TAKEOVER_SETTING,
        name: "私信接管",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
        ],
        color: "#CADEFD",
    },
    // 社媒平台发布内容
    social_media_content: {
        key: TaskKeyEnum.PUBLISH_SETTING,
        name: "社媒平台发布内容",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
            { activeIcon: SphIcon, name: "视频号", type: AppTypeEnum.SPH },
        ],
        color: "#BCFFB5",
    },
    // 朋友圈发布
    circle_release: {
        key: TaskKeyEnum.CIRCLE_RELEASE,
        name: "朋友圈发布",
        status: 0,
        platform: [{ activeIcon: CircleIcon, type: AppTypeEnum.WECHAT }],
        color: "#BCFFB5",
    },
    // 朋友圈互动
    circle_interaction: {
        key: TaskKeyEnum.CIRCLE_INTERACTION,
        name: "朋友圈互动",
        status: 4,
        platform: [{ activeIcon: CircleIcon }],
        color: "#DAD4FF",
    },
    // 评论区获客
    comment_area_customer: {
        key: TaskKeyEnum.TOUCH_SETTING,
        name: "评论区获客",
        status: 0,
        platform: [
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.SPH],
        ],
        color: "#FFE4C1",
    },
    // 自动加微
    auto_add_wechat: {
        key: TaskKeyEnum.ADD_WECHAT_SETTING,
        name: "自动加微",
        status: 0,
        platform: [platform.value[AppTypeEnum.WECHAT]],
        color: "#DAD4FF",
    },
    // 自动养号
    auto_account: {
        key: TaskKeyEnum.AUTO_ACCOUNT,
        name: "自动养号",
        status: 3,
        platform: [
            platform.value[AppTypeEnum.XHS],
            platform.value[AppTypeEnum.DOUYIN],
            platform.value[AppTypeEnum.KUAISHOU],
            // platform.value[AppTypeEnum.SPH],
        ],
        color: "#FFE4C1",
    },
};

/// 任务时间配置表
const taskTimeConfig = ref<any[]>([
    // 关键词获客
    {
        ...taskMap.keyword_customer,
        time: ["01:00", "06:00"],
        platform: [{ activeIcon: SphIcon, type: AppTypeEnum.SPH }],
    },
    // 私信接管
    {
        ...taskMap.private_message_takeover,
        time: ["06:00", "07:30"],
    },
    // 社媒平台发布内容
    {
        ...taskMap.social_media_content,
        time: ["08:00", "08:30"],
    },
    // 朋友圈发布
    {
        ...taskMap.circle_release,
        time: ["08:30", "09:00"],
    },
    // 朋友圈互动
    {
        ...taskMap.circle_interaction,
        time: ["09:00", "10:00"],
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["10:00", "12:30"],
        platform: [platform.value[AppTypeEnum.DOUYIN]],
    },
    // 社媒平台发布内容
    {
        ...taskMap.social_media_content,
        time: ["13:00", "13:30"],
    },
    // 朋友圈互动
    {
        ...taskMap.circle_interaction,
        time: ["14:00", "15:00"],
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["15:30", "17:30"],
        platform: [platform.value[AppTypeEnum.XHS]],
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["17:30", "18:00"],
        status: 4,
        platform: [{ activeIcon: SphIcon, type: AppTypeEnum.SPH }],
        disabled: true,
    },
    // 社媒平台发布内容
    {
        ...taskMap.social_media_content,
        time: ["18:00", "18:30"],
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["18:30", "20:30"],
        platform: [platform.value[AppTypeEnum.KUAISHOU]],
        disabled: true,
    },
    // 自动加微
    {
        ...taskMap.auto_add_wechat,
        time: ["20:30", "21:30"],
    },
    // 自动养号
    {
        ...taskMap.auto_account,
        time: ["21:30", "23:00"],
    },
]);

const getTaskCostConfig = computed(() => {
    const getScene = (scene: string) => {
        return userStore.getTokenByScene(scene);
    };
    return [
        "automation_social_media_released",
        "automation_shut_off_comments",
        "automation_shut_off_obtain",
        "automation_shut_off_private_letter",
        "automation_friends_circle_comments",
        "automation_friends_circle_released",
        "automation_friends_circle_praise",
        "automation_wechat_add_friend",
        "automation_social_media_obtain",
        "automation_social_media_nursing",
        "automation_ocr_local",
        "automation_ocr_img",
    ].map(getScene);
});

// 判断是不是全部获取成功
const isAllGetSuccess = computed(() => {
    return sortedPlatform.value.every((item) => item.active);
});

const queryList = () => {
    getTaskConfig();
    pagingRef.value?.complete(taskTimeConfig.value);
};

const toTaskConfig = (item: any) => {
    const { key, disabled, platform } = item;

    // 1. 检查任务是否被禁用
    if (disabled) {
        uni.$u.toast("该功能敬请期待~");
        return;
    }

    // 2. 账号平台权限检查 - 前置条件
    if (!checkAccountPlatformPermission(item)) {
        // 弹窗提示获取账号
        showGetAccountPopup.value = true;
        return;
    }

    // 3. 原有逻辑：URL映射和页面跳转
    const urls: any = {
        [TaskKeyEnum.CLUES_SETTING]: "/ai_modules/device/pages/setting_clue/setting_clue",
        [TaskKeyEnum.PUBLISH_SETTING]: "/ai_modules/device/pages/setting_publish/setting_publish",
        [TaskKeyEnum.TAKEOVER_SETTING]: "/ai_modules/device/pages/setting_private_take/setting_private_take",
        [TaskKeyEnum.CIRCLE_RELEASE]: "/ai_modules/device/pages/setting_circle/setting_circle",
        [TaskKeyEnum.CIRCLE_INTERACTION]:
            "/ai_modules/device/pages/setting_circle_interact_auto/setting_circle_interact_auto",
        [TaskKeyEnum.TOUCH_SETTING]: "/ai_modules/device/pages/setting_ca/setting_ca",
        [TaskKeyEnum.ADD_WECHAT_SETTING]: "/ai_modules/device/pages/setting_add_wechat/setting_add_wechat",
    };

    const params: any = {
        id: deviceDetail.value.id,
        type: key,
        device_code: deviceCode.value,
    };

    if (key === TaskKeyEnum.TOUCH_SETTING) {
        params.account_type = item.platform[0].type;
    }

    if (urls[key]) {
        uni.$u.route({
            url: urls[key],
            params,
        });
    }
};

/**
 * 检查账号平台权限
 * @param {Object} taskItem - 任务配置项
 * @returns {boolean} - 是否具备权限
 */
const checkAccountPlatformPermission = (taskItem: any) => {
    const { platform } = taskItem;

    // 如果没有平台要求，直接通过
    if (!platform || platform.length === 0) {
        return true;
    }

    // 获取设备账号列表
    const deviceAccounts = deviceDetail.value.accounts || [];

    // 检查是否至少有一个所需平台有对应的账号
    const hasAtLeastOnePlatform = platform.some((requiredPlatform: any) => {
        // 如果平台没有type字段，认为该平台已满足（可能是特殊平台如朋友圈）
        if (!requiredPlatform.type) {
            return true;
        }

        // 在设备账号中查找匹配的平台类型
        return deviceAccounts.some((account: any) => {
            return account.type === requiredPlatform.type;
        });
    });

    return hasAtLeastOnePlatform;
};

const toPage = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/create_auto_task/create_auto_task",
        params: {
            device_code: deviceCode.value,
        },
    });
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const res = await getDeviceDetail({ device_code: deviceCode.value });
        deviceDetail.value = res;
        await getTaskConfig();
    } finally {
        loading.value = false;
        uni.hideLoading();
    }
};

const getTaskConfig = async () => {
    const data = await getAutoTaskDetail({ device_code: deviceCode.value });
    const { auto_setting, is_empty } = data;

    autoTaskDetail.value = data;
    isCompleteConfig.value = is_empty === 0;

    if (isCompleteConfig.value) {
        initializePlatform(deviceDetail.value.accounts);
    }

    taskTimeConfig.value.forEach((item: any) => {
        const { key } = item;
        if (auto_setting[key]) {
            item.status = auto_setting[key].is_config;
        }
    });
};
const chooseAppPlatforms = ref<any[]>([]);
const selectedPlatform = ref<any>({});
const currTaskKey = ref<any>(null);
const demoParams = ref<any>({
    device_code: deviceCode.value,
    account_type: null,
    source: null,
});

const selectPlatform = (platform: any) => {
    selectedPlatform.value = platform;
};

const confirmSelection = () => {
    showChooseApp.value = false;
    const { type } = selectedPlatform.value;
    switch (currTaskKey.value) {
        case TaskKeyEnum.TAKEOVER_SETTING:
            demoParams.value.account_type = type;
            demoParams.value.source = 4;
            break;
        case TaskKeyEnum.PUBLISH_SETTING:
            demoParams.value.account_type = type;
            if (type == AppTypeEnum.XHS) {
                demoParams.value.source = 1;
            } else {
                demoParams.value.source = 2;
            }
            break;
        case TaskKeyEnum.AUTO_ACCOUNT:
            demoParams.value.account_type = type;
            demoParams.value.source = 8;
            break;
    }
    handleCheckRealTask();
};

const handleDemo = (item: any) => {
    uni.showModal({
        title: "提示",
        content: "检测有任务在执行中，演示任务会中断当前任务，是否确定继续演示任务？",
        success: (res) => {
            if (res.confirm) {
                demoParams.value.device_code = deviceCode.value;
                currTaskKey.value = item.key;
                switch (item.key) {
                    case TaskKeyEnum.CLUES_SETTING:
                        demoParams.value.account_type = 1;
                        demoParams.value.source = 3;
                        handleCheckRealTask();
                        break;
                    case TaskKeyEnum.TAKEOVER_SETTING:
                    case TaskKeyEnum.PUBLISH_SETTING:
                    case TaskKeyEnum.AUTO_ACCOUNT:
                        chooseAppPlatforms.value = item.platform;
                        selectedPlatform.value = chooseAppPlatforms.value[0];
                        showChooseApp.value = true;
                        break;
                    case TaskKeyEnum.TOUCH_SETTING:
                        demoParams.value.account_type = item.platform[0].type;
                        demoParams.value.source = 5;
                        handleCheckRealTask();
                        break;
                    case TaskKeyEnum.ADD_WECHAT_SETTING:
                        demoParams.value.account_type = item.platform[0].type;
                        demoParams.value.source = 7;
                        handleCheckRealTask();
                        break;
                    case TaskKeyEnum.CIRCLE_RELEASE:
                        demoParams.value.account_type = item.platform[0].type;
                        demoParams.value.source = 9;
                        handleCheckRealTask();
                        break;
                    case TaskKeyEnum.CIRCLE_INTERACTION:
                        demoParams.value.account_type = item.platform[0].type;
                        demoParams.value.source = 10;
                        handleCheckRealTask();
                }
            }
        },
    });
};

const handleCheckRealTask = async () => {
    uni.showLoading({
        title: "检查任务中...",
        mask: true,
    });
    try {
        const res = await checkRealTask(demoParams.value);
        uni.hideLoading();

        if (res.is_demo_data == 1) {
            showConfirmDemoDialog.value = true;
            return;
        }
        startDemoTask();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const startDemoTask = async () => {
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createDemoTask(demoParams.value);
        uni.hideLoading();
        uni.showToast({
            title: "创建成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

onShow(() => {
    getDetail();
});

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    connectWebSocket();
});

onUnload(() => {
    close();
});
</script>

<style scoped lang="scss">
.auto-task-page {
    background: linear-gradient(90deg, #e3f2fb 0%, #bad8fb 100%);
    @apply h-screen flex flex-col;
}
</style>
