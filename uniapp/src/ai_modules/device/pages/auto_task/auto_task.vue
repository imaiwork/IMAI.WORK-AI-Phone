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
                    <view class="font-bold text-[30rpx]">社媒账号</view>
                    <view class="flex items-center gap-x-1">
                        <template v-if="!isCompleteConfig || isAllGetSuccess">
                            <image
                                src="@/ai_modules/device/static/icons/success.svg"
                                class="w-[28rpx] h-[28rpx]"></image>
                            <view class="text-[#00C08E] font-bold">已获取</view>
                        </template>
                        <view v-else class="absolute right-3 top-0">
                            <view class="py-[32rpx] flex justify-center items-center gap-x-2" @click="handleGetAccount">
                                <image
                                    src="@/ai_modules/device/static/icons/refresh_primary.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                                <text class="text-primary font-bold">一键获取</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-x-2 mt-[22rpx]">
                    <view v-for="(item, index) in sortedPlatformLogo" :key="index">
                        <image :src="item.icon" class="w-[48rpx] h-[48rpx]"></image>
                    </view>
                </view>
            </view>
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
                    <view class="font-bold text-[30rpx]">24h任务列表</view>
                    <!-- <image src="@/ai_modules/device/static/icons/tips.svg" class="w-[24rpx] h-[24rpx]"></image> -->
                </view>
                <view class="flex items-center gap-x-1">
                    <template v-if="autoTaskDetail.is_config == 1">
                        <image src="@/ai_modules/device/static/icons/success.svg" class="w-[28rpx] h-[28rpx]"></image>
                        <view class="text-[#00C08E] font-bold">可执行</view>
                    </template>
                    <view v-else class="text-[#FF2442] text-xs font-bold">无法执行，配置未完整</view>
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
                                <view class="text-[#00000080] font-bold">{{ item.time[0] }}</view>
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="w-[5rpx] h-[8rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                    <view class="w-[5rpx] h-[10rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                    <view class="w-[5rpx] h-[8rpx] rounded-[50rpx] bg-[#0000004d]"></view>
                                </view>
                                <view class="text-[#00000080] font-bold">{{ item.time[1] }}</view>
                            </view>
                            <view class="bg-white flex-1 relative rounded-[20rpx] px-[40rpx] py-[30rpx] h-[238rpx]">
                                <view
                                    class="absolute top-[50%] left-0 h-[100rpx] w-[6rpx] rounded-[20rpx]"
                                    :style="{ background: item.color, transform: 'translateY(-50%)' }"></view>
                                <view class="flex items-center justify-between">
                                    <view class="flex items-center gap-x-1">
                                        <image
                                            src="@/ai_modules/device/static/icons/task.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <text class="text-xs text-[#0000004d] font-bold">任务类型</text>
                                    </view>
                                    <view class="flex items-center gap-x-[4rpx]">
                                        <image
                                            v-for="(val, index) in item.platform"
                                            :key="index"
                                            :src="val.activeIcon"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                    </view>
                                </view>
                                <view class="mt-[6rpx] flex items-center gap-x-[10rpx]">
                                    <text class="text-[34rpx] font-bold">
                                        {{ item.name }}
                                    </text>
                                    <!-- <image
                                        src="@/ai_modules/device/static/icons/tips.svg"
                                        class="w-[24rpx] h-[24rpx]"></image> -->
                                </view>
                                <view class="h-[1rpx] bg-[#F2F2F2] my-[24rpx]"></view>
                                <view class="flex items-center justify-between">
                                    <view
                                        class="font-bold"
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
                    <view class="font-bold text-primary text-[40rpx]"> 请完善首次设置 </view>
                    <view
                        class="mt-[46rpx] w-[240rpx] h-[90rpx] flex items-center justify-center font-bold text-white text-[30rpx] bg-primary rounded-lg"
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
    <u-popup
        v-model="showUpdateProgress"
        mode="center"
        border-radius="20"
        width="80%"
        :mask-close-able="false"
        @close="onProgressPopupClose">
        <view class="rounded-[20rpx] bg-white px-5 py-[78rpx]">
            <view class="flex flex-col gap-y-3 w-[70%] mx-auto">
                <view v-for="(item, index) in updateAccountSteps" :key="index" class="flex gap-x-[28rpx]">
                    <view class="flex-shrink-0 mt-[4rpx] relative">
                        <view class="w-[28rpx] h-[28rpx]">
                            <view
                                v-if="item.status == 0"
                                class="w-full h-full rounded-full border border-solid border-[#0000001a]">
                            </view>
                            <view
                                v-if="item.status == 1 || item.status == 3"
                                class="w-full h-full rounded-full border border-solid border-primary-light-8 flex items-center justify-center">
                                <view class="w-[12rpx] h-[12rpx] rounded-full bg-primary"></view>
                            </view>
                            <view
                                v-if="item.status == 2"
                                class="w-full h-full rounded-full flex items-center justify-center border border-solid border-primary">
                                <u-icon name="checkmark" color="#0065FB" size="16"></u-icon>
                            </view>
                        </view>
                        <view
                            class="absolute top-[60%] left-[14rpx] w-[2rpx] h-[60%]"
                            :class="[item.status == 2 ? 'bg-primary' : 'bg-[#0000001a]']"
                            v-if="index !== updateAccountSteps.length - 1"></view>
                    </view>
                    <view class="h-[80rpx]">
                        <view
                            class="font-bold"
                            :class="{
                                'text-[#0000004d]': item.status == 0,
                            }">
                            {{ item.title }}
                        </view>
                        <view class="mt-1">
                            <text class="text-primary font-bold text-xs" v-if="item.status == 1">获取中...</text>
                            <text class="text-[#FF2442] font-bold text-xs" v-if="item.status == 3">获取失败</text>
                        </view>
                    </view>
                </view>
            </view>
            <view class="mt-2 flex flex-col gap-y-2">
                <u-button
                    v-if="isExecuteComplete"
                    type="primary"
                    :custom-style="{ height: '90rpx', width: '100%', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="onProgressPopupClose"
                    >确认</u-button
                >
                <u-button
                    :custom-style="{ height: '90rpx', fontWeight: 'bold', borderRadius: '20rpx' }"
                    @click="onProgressPopupClose"
                    >取消</u-button
                >
            </view>
        </view>
    </u-popup>
    <popup-bottom
        v-model="showFeePopup"
        title="24小时自动任务算力消耗明细（内测期间暂不收费）"
        custom-class="bg-[#F6F6F6]">
        <template #content>
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 pb-[50rpx] mt-4">
                    <view class="bg-white rounded-[20rpx] px-[40rpx]">
                        <view
                            v-for="(item, index) in freeList"
                            :key="index"
                            class="py-[26rpx] border-[0] border-b border-solid border-[#0000000d]"
                            :class="{ 'border-b-0': index === freeList.length - 1 }">
                            <view class="flex items-center justify-between">
                                <view>
                                    {{ item.name }}
                                </view>
                                <view class="text-[#696969] text-end">
                                    {{ item.description }}
                                </view>
                            </view>
                            <view class="flex items-center justify-end mt-[24rpx]">
                                <text class="text-primary">{{ item.price }}</text
                                >{{ item.unit }}
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceDetail, getAutoTaskDetail, addDeviceAccount, updateDeviceAccount } from "@/api/device";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { AppTypeEnum, DeviceCmdEnum } from "@/enums/appEnums";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import CircleIcon from "@/ai_modules/device/static/images/common/circle.png";
import SphIcon from "@/static/images/common/sph_s.png";
import PhoneIcon from "@/ai_modules/device/static/images/common/phone.png";

const { platformLogo } = useDevice();

// 初始化WebSocket服务
const { send, onEvent, close } = useDeviceWs();

const sortedPlatformLogo = ref<any[]>(
    Object.values(platformLogo).map((item: any) => {
        return {
            ...item,
            icon: item.activeIcon,
        };
    })
);
const pagingRef = ref<any>(null);
const showUpdateProgress = ref(false);
const currentAccountIndex = ref(0);
const updateAccountSteps = ref<any[]>([]);

const loading = ref(true);
const deviceCode = ref("");
const deviceDetail = ref<any>({});
const autoTaskDetail = ref<any>({});
// 是否完成配置
const isCompleteConfig = ref(false);
const showFeePopup = ref(false);
const taskMap: any = {
    // 关键词获客
    keyword_customer: {
        key: "clues_setting",
        name: "关键词获客",
        status: 0,
        platform: [platformLogo[AppTypeEnum.SPH]],
        color: "#CADEFD",
    },
    // 私信接管
    private_message_takeover: {
        key: "takeover_setting",
        name: "私信接管",
        status: 0,
        platform: [platformLogo[AppTypeEnum.XHS], platformLogo[AppTypeEnum.DOUYIN], platformLogo[AppTypeEnum.KUAISHOU]],
        color: "#CADEFD",
    },
    // 社媒平台发布内容
    social_media_content: {
        key: "publish_setting",
        name: "社媒平台发布内容",
        status: 0,
        platform: [
            platformLogo[AppTypeEnum.XHS],
            platformLogo[AppTypeEnum.DOUYIN],
            platformLogo[AppTypeEnum.KUAISHOU],
            { activeIcon: SphIcon },
        ],
        color: "#BCFFB5",
    },
    // 朋友圈互动
    circle_interaction: {
        key: "circle_interaction",
        name: "朋友圈互动",
        status: 4,
        platform: [{ activeIcon: CircleIcon }],
        color: "#DAD4FF",
    },
    // 评论区获客
    comment_area_customer: {
        key: "touch_setting",
        name: "评论区获客",
        status: 0,
        platform: [
            platformLogo[AppTypeEnum.DOUYIN],
            platformLogo[AppTypeEnum.KUAISHOU],
            platformLogo[AppTypeEnum.XHS],
            platformLogo[AppTypeEnum.SPH],
        ],
        color: "#FFE4C1",
    },
    // 自动加微
    auto_add_wechat: {
        key: "add_wechat_setting",
        name: "自动加微",
        status: 0,
        platform: [platformLogo[AppTypeEnum.WECHAT]],
        color: "#DAD4FF",
    },
    // 自动养号
    auto_account: {
        key: "auto_account",
        name: "自动养号",
        status: 3,
        platform: [
            platformLogo[AppTypeEnum.XHS],
            platformLogo[AppTypeEnum.DOUYIN],
            platformLogo[AppTypeEnum.KUAISHOU],
            platformLogo[AppTypeEnum.SPH],
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
        platform: [{ activeIcon: SphIcon }],
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
    // 朋友圈互动
    {
        ...taskMap.circle_interaction,
        time: ["08:30", "10:00"],
        disabled: true,
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["10:00", "12:30"],
        platform: [platformLogo[AppTypeEnum.DOUYIN]],
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
        disabled: true,
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["15:30", "17:30"],
        platform: [platformLogo[AppTypeEnum.XHS]],
    },
    // 评论区获客
    {
        ...taskMap.comment_area_customer,
        time: ["17:30", "18:00"],
        status: 4,
        platform: [{ activeIcon: SphIcon }],
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
        platform: [platformLogo[AppTypeEnum.KUAISHOU]],
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

// 算力消耗明细
const freeList = [
    {
        scene: "auto_phone_sph_add_wechat",
        name: "视频号获客",
        description: " 按照识别执行线索数量进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/次",
        times: 999,
    },
    {
        scene: "auto_phone_matrix_publish",
        name: "社媒平台发布",
        description: "按照内容的发布条数进行扣费",
        cast_price: "10",
        price: "20",
        unit: "算力/条",
        times: 999,
    },
    {
        scene: "auto_phone_intercept",
        name: "截流私信/评论",
        description: "按照主动私信/评论时产生的token进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/1000token",
        times: 999,
    },
    {
        scene: "auto_phone_touch_reach",
        name: "截流触达",
        description: "按照找到的用户数进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/个",
        times: 999,
    },
    {
        scene: "auto_phone_comment",
        name: "朋友圈评论",
        description: "按照评论朋友圈时产生的token进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/1000token",
        times: 999,
    },
    {
        scene: "auto_phone_circle_publish",
        name: "朋友圈发布",
        description: "按照内容的发布条数进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/条",
        times: 999,
    },
    {
        scene: "auto_phone_matrix_publish",
        name: "朋友圈点赞",
        description: "按点赞朋友圈的次数进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/次",
        times: 999,
    },
    {
        scene: "auto_phone_add_wechat",
        name: "自动加微",
        description: "按发送好友申请的次数进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/个",
        times: 999,
    },
    {
        scene: "auto_phone_reply",
        name: "私信接管(社媒平台)",
        description: "按照自动回复时产生的token进行扣费）",
        cast_price: "1",
        price: "2",
        unit: "算力/1000token",
        times: 999,
    },
    {
        scene: "auto_phone_matrix_publish",
        name: "自动养号(社媒平台)",
        description: "按照自动养号运行时长进行扣费",
        cast_price: "1",
        price: "2",
        unit: "算力/分钟",
        times: 999,
    },
];

// 判断是不是全部获取成功
const isAllGetSuccess = computed(() => {
    return sortedPlatformLogo.value.every((item) => item.active);
});

const isExecuteComplete = computed(() => {
    return updateAccountSteps.value.some((step: any) => step.status === 2);
});

const queryList = () => {
    getTaskConfig();
    pagingRef.value?.complete(taskTimeConfig.value);
};

const handleGetAccount = () => {
    currentAccountIndex.value = 0;

    let platformsToProcess = sortedPlatformLogo.value;

    if (!isAllGetSuccess.value) {
        platformsToProcess = sortedPlatformLogo.value.filter((item) => !item.active);
    }

    const stepsToUpdate = platformsToProcess.map((item) => {
        const originalPlatform = Object.values(platformLogo).find((p: any) => p.type === item.type);
        return {
            status: 0,
            title: originalPlatform?.name || item.name,
            type: item.type,
        };
    });

    if (stepsToUpdate.length === 0) {
        uni.$u.toast("所有账号均已获取");
        return;
    }

    updateAccountSteps.value = stepsToUpdate;
    showUpdateProgress.value = true;
    if (updateAccountSteps.value.length > 0) {
        updateAccountSteps.value[currentAccountIndex.value].status = 1;
        sendGetAccountCmd(updateAccountSteps.value[currentAccountIndex.value].type);
    }
};

const onProgressPopupClose = () => {
    showUpdateProgress.value = false;
    currentAccountIndex.value = 0;
    getDetail();
};

// 发送获取账号指令
const sendGetAccountCmd = (type: AppTypeEnum) => {
    send({
        type: DeviceCmdEnum.GET_USER_INFO,
        content: { deviceId: deviceCode.value },
        deviceId: deviceCode.value,
        appType: type,
    });
};

const toTaskConfig = (item: any) => {
    const { key, disabled } = item;
    if (disabled) {
        uni.$u.toast("该功能敬请期待~");
        return;
    }
    const urls: any = {
        clues_setting: "/ai_modules/device/pages/setting_clue/setting_clue",
        active_setting: "/ai_modules/device/pages/setting_auto_account/setting_auto_account",
        publish_setting: "/ai_modules/device/pages/setting_publish/setting_publish",
        takeover_setting: "/ai_modules/device/pages/setting_private_take/setting_private_take",
        circle_interaction: "/ai_modules/device/pages/setting_circle/setting_circle",
        touch_setting: "/ai_modules/device/pages/setting_ca/setting_ca",
        add_wechat_setting: "/ai_modules/device/pages/setting_add_wechat/setting_add_wechat",
    };
    const params: any = {
        type: key,
        device_code: deviceCode.value,
    };
    if (key === "touch_setting") {
        params.account_type = item.platform[0].type;
    }
    if (urls[key]) {
        uni.$u.route({
            url: urls[key],
            params,
        });
    }
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
        sortedPlatformLogo.value = Object.values(platformLogo).map((item: any) => {
            const account = deviceDetail.value.accounts.find((val: any) => val.type == item.type);
            return {
                ...item,
                active: !!account,
                icon: account ? item.activeIcon : item.icon,
            };
        });
    }

    taskTimeConfig.value.forEach((item: any) => {
        const { key } = item;
        if (auto_setting[key]) {
            item.status = auto_setting[key].is_config;
        }
    });
};

onEvent("success", async (data: any) => {
    const { type, content, deviceId, appType } = data;

    if (type !== DeviceCmdEnum.GET_USER_INFO) return;

    if (updateAccountSteps.value[currentAccountIndex.value]) {
        updateAccountSteps.value[currentAccountIndex.value].status = 2;
    }

    const { account, account_no, extra, avatar, nickname } = content;

    const existingAccount = deviceDetail.value.accounts?.find((acc: any) => acc.type === appType);

    const params = {
        account,
        account_no,
        avatar,
        device_code: deviceId,
        type: appType,
        nickname,
        extra: JSON.stringify(extra),
    };

    if (existingAccount) {
        await updateDeviceAccount({ ...params, id: existingAccount.id });
    } else {
        await addDeviceAccount(params);
    }

    currentAccountIndex.value++;

    if (currentAccountIndex.value < updateAccountSteps.value.length) {
        updateAccountSteps.value[currentAccountIndex.value].status = 1;
        sendGetAccountCmd(updateAccountSteps.value[currentAccountIndex.value].type);
    } else {
        await getDetail();
    }
});

onEvent("error", (error: any) => {
    const index = currentAccountIndex.value;
    if (updateAccountSteps.value.length > 0 && updateAccountSteps.value[index]) {
        const updatedStep = { ...updateAccountSteps.value[index], status: 3 };
        updateAccountSteps.value.splice(index, 1, updatedStep);
    }
});

onShow(() => {
    getDetail();
});

onLoad((options: any) => {
    deviceCode.value = options.device_code;
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
