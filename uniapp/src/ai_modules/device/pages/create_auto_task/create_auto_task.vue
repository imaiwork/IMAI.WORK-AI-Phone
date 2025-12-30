<template>
    <view class="h-screen device-bg flex flex-col" v-if="!loading">
        <u-navbar
            title-bold
            title="自动任务配置"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="common-step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <scroll-view v-if="step == 1" class="h-full" scroll-y>
                <view class="px-4 pb-[100rpx]">
                    <view class="text-[30rpx] font-bold text-center">获取社媒账号</view>
                    <view class="text-center text-[#00000066] mt-2">
                        需先在AI手机上登录: 微信 / 小红书 / 抖音 / 快手
                    </view>
                    <view
                        class="bg-primary mx-auto w-[240rpx] h-[84rpx] flex justify-center items-center gap-x-2 mt-[38rpx] rounded-[16rpx]"
                        @click="handleGetAccount(isAllGetSuccess)">
                        <image src="@/ai_modules/device/static/icons/refresh.svg" class="w-[28rpx] h-[28rpx]"></image>
                        <text class="text-white font-bold">{{ isAllGetSuccess ? "重新获取" : "一键获取" }}</text>
                    </view>
                    <view class="bg-white rounded-[20rpx] px-[50rpx] mt-[26rpx]">
                        <view class="py-[50rpx] flex flex-col gap-y-6">
                            <view v-for="(item, index) in sortedPlatformLogo" :key="index">
                                <view class="flex items-center gap-x-2">
                                    <image :src="item.activeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                    <text class="font-bold">{{ item.name }}账号</text>
                                </view>
                                <view class="bg-[#F6F6F6] p-[44rpx] flex rounded-[20rpx] mt-[18rpx]">
                                    <view
                                        class="flex items-center justify-between w-full"
                                        v-if="item.active || item.status == 2">
                                        <view class="flex items-center gap-x-2">
                                            <image :src="item.avatar" class="w-[80rpx] h-[80rpx] rounded-full"></image>
                                            <view>
                                                <view class="text-[30rpx] font-bold break-all line-clamp-1">
                                                    {{ item.nickname }}
                                                </view>
                                                <view
                                                    class="text-xs text-[#0000004d] font-bold break-all line-clamp-1 mt-[6rpx]">
                                                    {{ item.account }}
                                                </view>
                                            </view>
                                        </view>
                                        <view class="flex items-center gap-x-1">
                                            <image
                                                src="@/ai_modules/device/static/icons/success2.svg"
                                                class="w-[28rpx] h-[28rpx]"></image>
                                            <text class="text-[#00C08E] font-bold">已完成</text>
                                        </view>
                                    </view>
                                    <view class="flex gap-x-4" v-else>
                                        <image
                                            :src="item.icon"
                                            class="w-[60rpx] h-[60rpx] rounded-full flex-shrink-0"></image>
                                        <text class="text-primary font-bold mt-[10rpx]" v-if="item.status == 1"
                                            >获取中，请等待...</text
                                        >
                                        <view v-else-if="item.status == 3">
                                            <text class="text-[#FF2442] font-bold">获取失败：{{ item.error }}</text>
                                            <view
                                                class="w-[150rpx] h-[64rpx] bg-primary text-white rounded-[10rpx] flex items-center justify-center mt-[18rpx]"
                                                @click="handleGetAccount(false)">
                                                重新获取</view
                                            >
                                        </view>
                                        <text class="text-[#0000004d] font-bold mt-[10rpx]" v-else>等待获取</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="h-[1rpx] bg-[#0000000d]"></view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view v-if="step == 2" class="h-full" scroll-y>
                <view class="px-4 flex flex-col gap-y-[50rpx] pb-[400rpx]">
                    <view>
                        <view class="font-bold text-[30rpx]"
                            ><text class="text-[#FF2442]">*</text>您想要获取什么方向的客资</view
                        >
                        <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[24rpx]">
                            <textarea
                                class="w-full"
                                v-model="formData.clue"
                                maxlength="1000"
                                placeholder-style="font-size: 26rpx;"
                                placeholder="请输入，如：我想获取火锅店用品的客资"
                                :disable-default-padding="true"
                                :show-confirm-bar="false"
                                :adjust-position="false"
                                :auto-height="false" />
                            <view class="text-end text-xs text-[#0000004d]"> {{ formData.clue.length }}/1000 </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-[30rpx]"
                            ><text class="text-[#FF2442]">*</text>您创作的视频营销主题是</view
                        >
                        <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[24rpx]">
                            <textarea
                                class="w-full"
                                v-model="formData.videoTheme"
                                maxlength="1000"
                                placeholder-style="font-size: 26rpx;"
                                placeholder="请输入，如：我想创作牙科诊所的营销视频"
                                :disable-default-padding="true"
                                :show-confirm-bar="false"
                                :adjust-position="false"
                                :auto-height="false" />
                            <view class="text-end text-xs text-[#0000004d]">
                                {{ formData.videoTheme.length }}/1000
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="font-bold text-[30rpx]"
                            ><text class="text-[#FF2442]">*</text>您创作的图文营销主题是</view
                        >
                        <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[24rpx] flex flex-col">
                            <textarea
                                class="w-full"
                                v-model="formData.imageTheme"
                                maxlength="1000"
                                placeholder-style="font-size: 26rpx;"
                                placeholder="请输入，如：我想创作牙科诊所的营销图片和文案"
                                :disable-default-padding="true"
                                :adjust-position="false"
                                :show-confirm-bar="false"
                                :auto-height="false" />
                            <view class="text-end text-xs text-[#0000004d]">
                                {{ formData.imageTheme.length }}/1000
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view v-if="step == 3" scroll-y class="h-full">
                <view class="px-4 flex flex-col gap-y-[50rpx] pb-[100rpx]">
                    <view>
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold">
                                <text class="text-[#FF2442]">*</text>
                                数字人形象({{ anchorList.length }})
                            </view>
                            <view class="text-xs font-bold text-primary" @click="toPage('anchor_material')">
                                添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                            <view v-if="anchorList.length > 0" class="grid grid-cols-3 gap-x-[20rpx]">
                                <view
                                    v-for="(item, index) in anchorList.slice(0, 3)"
                                    :key="index"
                                    class="h-[250rpx] relative">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                        <image
                                            src="/static/images/icons/play.svg"
                                            class="w-[48rpx] h-[48rpx]"
                                            @click="previewVideo(item)"></image>
                                    </view>
                                </view>
                            </view>
                            <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                <view class="text-center text-[#0000004d]">你还没有添加数字人形象</view>
                                <view class="text-primary font-bold" @click="toPage('anchor_material')"> 去添加 </view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold">
                                <text class="text-[#FF2442]">*</text>
                                视频剪辑素材({{ videoList.length }})
                            </view>
                            <view class="text-xs font-bold text-primary" @click="toPage('video_material')">
                                添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                            <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="videoList.length > 0">
                                <view
                                    v-for="(item, index) in videoList.slice(0, 3)"
                                    :key="index"
                                    class="h-[250rpx] relative overflow-hidden">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] flex items-center justify-center z-[88]">
                                        <image
                                            v-if="item.type === 'image'"
                                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <image
                                            v-else
                                            src="@/ai_modules/digital_human/static/icons/video.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                    </view>
                                    <view
                                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                        <image
                                            src="/static/images/icons/play.svg"
                                            class="w-[48rpx] h-[48rpx]"
                                            @click="previewVideo(item)"></image>
                                    </view>
                                </view>
                            </view>
                            <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                <view class="text-center text-[#0000004d]">你还没有添加视频剪辑素材</view>
                                <view class="text-primary font-bold" @click="toPage('video_material')"> 去添加 </view>
                            </view>
                        </view>
                    </view>
                    <view>
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold">
                                <text class="text-[#FF2442]">*</text>
                                图文剪辑素材({{ imageList.length }})
                            </view>
                            <view class="text-xs font-bold text-primary" @click="toPage('image_material')">
                                添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                            <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="imageList.length > 0">
                                <view
                                    class="h-[200rpx] rounded-[20rpx]"
                                    v-for="(item, index) in imageList.slice(0, 3)"
                                    :key="index">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[20rpx]"
                                        mode="aspectFill"></image>
                                </view>
                            </view>
                            <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                <view class="text-center text-[#0000004d]">你还没有添加图文剪辑素材</view>
                                <view class="text-primary font-bold" @click="toPage('image_material')"> 去添加 </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center px-4 h-[140rpx]" :class="[step == 1 ? 'justify-end' : 'justify-between']">
                <template v-if="step != steps.length">
                    <view
                        v-if="step != 1"
                        class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                        @click="handleStep(step, 'prev')">
                        上一步
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateTask">
                        完成设置
                    </view>
                </template>
            </view>
        </view>
    </view>
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import {
    getDeviceDetail,
    getAutoTaskDetail as getAutoTaskDetailApi,
    addDeviceAccount,
    updateDeviceAccount,
    createAutoTask,
    createAutoTaskPublishConfig,
} from "@/api/device";
import { AppTypeEnum, DeviceCmdEnum } from "@/enums/appEnums";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useMaterialStore from "@/ai_modules/device/stores/material";

const { platformLogo } = useDevice();
const { send, onEvent, close, isConnected } = useDeviceWs();
const { dynamicHeight, hideKeyboard } = useKeyboardHeight();
const materialStore = useMaterialStore();
const { anchorList, videoList, imageList } = storeToRefs(materialStore);

const loading = ref(true);
const deviceCode = ref();
const detail = ref<any>({});
const step = ref(1);
const steps = ref([
    { step: 1, title: "获取账号" },
    { step: 2, title: "运营方向" },
    { step: 3, title: "素材预设" },
]);

const formData = reactive<{
    clue: string;
    videoTheme: string;
    imageTheme: string;
}>({
    clue: "",
    videoTheme: "",
    imageTheme: "",
});

const sortedPlatformLogo = ref<any[]>(Object.values(platformLogo));
const showUpdateProgress = ref(false);
const platformsToUpdate = ref<any[]>([]);
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

const isAllGetSuccess = computed(() => {
    return sortedPlatformLogo.value.every((item) => item.active);
});

const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return sortedPlatformLogo.value.some((item) => item.active);
        case 2:
            return formData.clue && formData.videoTheme && formData.imageTheme;
        case 3:
            return true;
        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    if (type === "prev") {
        step.value--;
        return;
    }
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请获取账号",
                2: "请输入相关关键词",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }
    if (targetStep === step.value) return;
    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleGetAccount = (forceRefetch: boolean = false) => {
    let accountsToFetchTypes: AppTypeEnum[];
    if (forceRefetch) {
        accountsToFetchTypes = sortedPlatformLogo.value.map((item) => item.type);
    } else {
        accountsToFetchTypes = sortedPlatformLogo.value.filter((item) => !item.active).map((item) => item.type);
    }

    platformsToUpdate.value = accountsToFetchTypes;

    // 重置状态
    sortedPlatformLogo.value.forEach((p) => {
        if (platformsToUpdate.value.includes(p.type)) {
            p.status = 0; // 待处理
            if (forceRefetch) {
                p.active = false;
            }
        }
    });

    showUpdateProgress.value = true;
    processNextAccount();
};

const processNextAccount = () => {
    uni.showLoading({ title: "获取中...", mask: true });
    const platformToProcess = sortedPlatformLogo.value.find(
        (p) => platformsToUpdate.value.includes(p.type) && p.status === 0
    );
    if (platformToProcess) {
        platformToProcess.status = 1; // 进行中
        sendGetAccountCmd(platformToProcess.type);
    }
};

const sendGetAccountCmd = (type: AppTypeEnum) => {
    send({
        type: DeviceCmdEnum.GET_USER_INFO,
        content: { deviceId: deviceCode.value },
        deviceId: deviceCode.value,
        appType: type,
    });
};

const previewVideo = (item: any) => {
    playData.value = { url: item.url, pic: item.pic };
    showVideoPreview.value = true;
};

const toPage = (page: string) => {
    const urls = {
        anchor_material: "/ai_modules/device/pages/anchor_material/anchor_material",
        video_material: "/ai_modules/device/pages/video_material/video_material",
        image_material: "/ai_modules/device/pages/image_material/image_material",
    };
    uni.$u.route({ url: urls[page as keyof typeof urls] });
};

const handleCreateTask = async () => {
    if (!materialStore.anchorList.length) {
        uni.$u.toast("请选择形象");
        return;
    }
    if (!materialStore.videoList.length) {
        uni.$u.toast("请选择视频素材");
        return;
    }
    if (!materialStore.imageList.length) {
        uni.$u.toast("请选择图文素材");
        return;
    }
    uni.showLoading({ title: "创建中...", mask: true });
    try {
        const params = {
            device_code: deviceCode.value,
            text_theme: formData.imageTheme,
            video_theme: formData.videoTheme,
            human_image: materialStore.anchorList.map((item: any) => ({
                ...item.anchor_ids,
                ...item.extra_info,
                id: item.id,
                pic: item.pic,
                voice_id: item.extra_info.shanjian_voice_id,
                anchor_url: item.result_url,
            })),
            clip_material: materialStore.videoList.map((item: any) => ({
                type: item.type,
                fileUrl: item.url,
                cover: item.pic,
            })),
            image_material: materialStore.imageList.map((item: any) => item.url),
        };
        const data = await createAutoTask({
            ...params,
            clue_theme: formData.clue,
        });
        await createAutoTaskPublishConfig({
            ...params,
            device_config_id: data.id,
        });
        uni.hideLoading();
        uni.showToast({ title: "创建成功", icon: "none", duration: 3000 });
        setTimeout(() => {
            uni.$u.route({
                url: "/ai_modules/device/pages/auto_task/auto_task",
                type: "reLaunch",
                params: { device_code: deviceCode.value },
            });
        }, 3000);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "创建失败", icon: "none", duration: 3000 });
    }
};

const getDetail = async () => {
    uni.showLoading({ title: "加载中...", mask: true });
    try {
        const data = await getDeviceDetail({ device_code: deviceCode.value });
        await getAutoTaskDetail();
        detail.value = data;
        const { accounts } = data;
        sortedPlatformLogo.value = Object.values(platformLogo)
            .sort((a: any, b: any) => {
                const aIsActive = accounts.some((item: any) => item.type === a.type);
                const bIsActive = accounts.some((item: any) => item.type === b.type);
                if (aIsActive && !bIsActive) return -1;
                if (!aIsActive && bIsActive) return 1;
                return 0;
            })
            .map((item: any) => {
                const account = accounts.find((val: any) => val.type == item.type);
                return {
                    ...item,
                    ...account,
                    active: !!account,
                    status: account ? 2 : 0, // 2: success, 0: pending
                };
            });
    } finally {
        loading.value = false;
        uni.hideLoading();
    }
};

const getAutoTaskDetail = async () => {
    const data = await getAutoTaskDetailApi({ device_code: deviceCode.value });
    formData.clue = data.clue_theme;
    formData.videoTheme = data.video_theme;
    formData.imageTheme = data.text_theme;
    materialStore.anchorList = data.human_image;
    materialStore.videoList = data.clip_material;
    materialStore.imageList = data.image_material;
};

onEvent("success", async (data: any) => {
    const { type, content, deviceId, appType } = data;
    if (type !== DeviceCmdEnum.GET_USER_INFO) return;

    const platform = sortedPlatformLogo.value.find((p) => p.type === appType);
    if (platform && platform.status === 1) {
        const { account, account_no, extra, avatar, nickname } = content;
        const existingAccount = detail.value.accounts?.find((acc: any) => acc.type === appType);
        const params = {
            account,
            account_no,
            avatar,
            device_code: deviceId,
            type: appType,
            nickname,
            extra: JSON.stringify(extra),
        };

        try {
            if (existingAccount) {
                await updateDeviceAccount({ ...params, id: existingAccount.id });
            } else {
                await addDeviceAccount(params);
            }
            platform.status = 2; // 成功
            platform.active = true;
            platform.account = account;
            platform.account_no = account_no;
            platform.avatar = avatar;
            platform.nickname = nickname;
            platform.extra = extra;
        } catch (error) {
            platform.status = 3; // 如果API调用失败，也标记为失败
        }
    }

    const isFinished = !sortedPlatformLogo.value.some(
        (p) => platformsToUpdate.value.includes(p.type) && (p.status === 0 || p.status === 1)
    );

    if (!isFinished) {
        processNextAccount();
    } else {
        uni.hideLoading();
        await getDetail();
    }
});

onEvent("error", (error: any) => {
    uni.hideLoading();
    const platformInProgress = sortedPlatformLogo.value.find((p) => p.status === 1);
    if (platformInProgress) {
        platformInProgress.error = error.error;
        platformInProgress.status = 3; // 失败
        processNextAccount(); // 即使失败也尝试下一个
    }
});

onLoad((options: any) => {
    if (options.device_code) {
        deviceCode.value = options.device_code;
    }
    if (options.is_auto) {
        onEvent("open", () => {
            setTimeout(() => {
                handleGetAccount(true);
            }, 1000);
        });
    }
    getDetail();
});

onUnload(() => {
    close();
    hideKeyboard();
    materialStore.clearMaterial();
});
</script>
