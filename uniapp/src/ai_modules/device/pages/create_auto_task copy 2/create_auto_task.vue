<template>
    <view class="h-screen flex flex-col">
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4 pb-[150rpx]" v-if="step === 1">
                    <view v-for="(item, index) in contentList" :key="index">
                        <view class="flex items-start mb-6" v-if="item.role === 'assistant'">
                            <view class="w-10 h-10 rounded-full bg-[#D9E6FC] mr-3 overflow-hidden">
                                <image
                                    src="@/ai_modules/device/static/images/common/analysis_avatar.png"
                                    class="w-full h-full"
                                    mode="widthFix" />
                            </view>

                            <view
                                class="max-w-[75%] bg-white p-4 rounded-tr-[30rpx] rounded-br-[30rpx] rounded-bl-[30rpx] shadow-sm border border-[#F1F5F9]">
                                <text class="text-[28rpx] text-[#1E293B] leading-relaxed">
                                    {{ item.content }}
                                </text>
                            </view>
                        </view>

                        <view class="flex items-start justify-end mb-6" v-else>
                            <view
                                class="max-w-[75%] bg-primary p-4 rounded-tl-[30rpx] rounded-bl-[30rpx] rounded-br-[30rpx] shadow-lg shadow-[#0065fb]/20">
                                <text class="text-[28rpx] text-white leading-relaxed">{{ item.content }}</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="p-4 pb-[350rpx]" v-if="step === 2">
                    <view class="mb-6">
                        <text class="text-[34rpx] font-bold text-primary">完善运营方案</text>
                        <view class="text-[#000000]/50 text-xs mt-1">
                            基于对话已生成运营方案，你可修改补充。根据你的运营信息，AI自动填充到24h任务设置里
                        </view>
                    </view>

                    <view v-for="(item, index) in formConfigs" :key="index" class="mb-8">
                        <view class="flex flex-col mb-3">
                            <text class="text-[32rpx] font-black text-[#1E293B]"
                                ><text class="text-[#FF4D4F]">*</text>{{ item.label }}</text
                            >
                            <text class="text-[24rpx] text-[#94A3B8] mt-1">{{ item.subLabel }}</text>
                        </view>

                        <view class="bg-white rounded-[24rpx] px-4 py-2 border border-[#F1F5F9]">
                            <u-input
                                type="textarea"
                                v-model="formData[item.key]"
                                placeholder-style="color: #94A3B8; font-size: 24rpx;"
                                :placeholder="item.placeholder"
                                :auto-height="true"
                                :maxlength="500"
                                :custom-style="{
                                    fontSize: '28rpx',
                                    color: '#1E293B',
                                    backgroundColor: 'transparent',
                                }" />
                            <view class="flex justify-end mt-2">
                                <text class="text-[#CBD5E0] text-[22rpx]">{{ formData[item.key].length }}/500</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view class="p-4 pb-[150rpx]" v-if="step === 3">
                    <view class="rounded-[20rpx] bg-white px-[36rpx] py-[22rpx] relative">
                        <view class="flex items-center justify-between">
                            <view class="font-bold text-[30rpx]">社媒账号</view>
                            <view class="text-[#000000]/50"
                                >详情
                                <u-icon name="arrow-right" color="#9DA5B0" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center gap-x-2 mt-[22rpx]">
                            <view v-for="(item, index) in sortedPlatformLogo" :key="index">
                                <image :src="item.icon" class="w-[48rpx] h-[48rpx]"></image>
                            </view>
                        </view>
                    </view>
                    <view
                        class="mt-2 rounded-[20rpx] bg-white px-[36rpx] py-4 flex items-center justify-between"
                        @click="step = 2">
                        <view class="font-bold text-[30rpx]"> 运营策略方案 </view>
                        <view class="text-[#000000]/50"
                            >查看
                            <u-icon name="arrow-right" color="#9DA5B0" size="20"></u-icon>
                        </view>
                    </view>
                    <view class="mt-4 flex flex-col gap-y-[50rpx] pb-[100rpx]">
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
                                    <view class="text-primary font-bold" @click="toPage('anchor_material')">
                                        去添加
                                    </view>
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
                                    <view class="text-primary font-bold" @click="toPage('video_material')">
                                        去添加
                                    </view>
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
                                    <view class="text-primary font-bold" @click="toPage('image_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="fixed bottom-0 left-0 right-0 z-[999]">
            <view class="footer-container bg-[#ffffff] p-4 border-t border-solid border-[#F1F5F9]">
                <view class="flex items-center gap-4" v-if="step === 1">
                    <view
                        class="shrink-0 flex flex-col items-center justify-center active:opacity-60 transition-all"
                        @click="showSkipDialog = true">
                        <text class="text-[#94A3B8] text-[22rpx] mt-1 font-medium">跳过对话</text>
                    </view>
                    <view
                        class="main-record-btn flex-1 h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-lg shadow-[#0065fb]/20 active:scale-[0.97] transition-all"
                        @click="openRecorder">
                        <view class="pulse-ring"></view>
                        <u-icon name="mic" color="#ffffff" size="32"></u-icon>
                        <text class="text-white text-[32rpx] font-black tracking-wide ml-2">点击说话</text>
                    </view>
                </view>

                <view class="flex items-center justify-between gap-4" v-if="step === 2">
                    <view
                        class="flex items-center gap-2 px-6 h-[96rpx] rounded-full bg-[#F1F5F9] active:bg-[#E2E8F0] transition-all"
                        @click="resetDialog">
                        <u-icon name="mic" color="#64748B" size="32"></u-icon>
                        <text class="text-[#64748B] text-[28rpx] font-bold">重新对话</text>
                    </view>

                    <view class="flex-1">
                        <u-button
                            type="primary"
                            shape="circle"
                            :ripple="true"
                            :custom-style="{
                                height: '96rpx',
                                fontSize: '30rpx',
                                fontWeight: '900',
                                backgroundColor: '#0065fb',
                                border: 'none',
                                boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                            }"
                            @click="handleConfirmStep2">
                            确认方案并下一步
                            <u-icon name="arrow-right" color="#fff" size="28" class="ml-2"></u-icon>
                        </u-button>
                    </view>
                </view>
                <view v-if="step === 3">
                    <u-button
                        type="primary"
                        shape="circle"
                        :ripple="true"
                        :custom-style="{
                            height: '96rpx',
                            fontSize: '30rpx',
                            fontWeight: '900',
                            backgroundColor: '#0065fb',
                            border: 'none',
                            boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                        }"
                        @click="handleConfirmStep3">
                        确定保存
                    </u-button>
                </view>
            </view>
        </view>
    </view>
    <recorder-control
        v-model="showRecorder"
        ref="recorderRef"
        @close="showRecorder = false"
        @success="recorderSuccess" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>

    <confirm-dialog
        v-model="showSkipDialog"
        center
        content="跳过对话会造成信息不完善，导致方案不完整，需要你手动完善信息，是否确定跳过对话？"
        @confirm="handleConfirmSkipDialog"></confirm-dialog>
    <u-popup
        v-model="showAnalysisResult"
        mode="center"
        width="90%"
        border-radius="30rpx"
        negative-top="50rpx"
        :mask-close-able="false"
        :custom-style="{ backgroundColor: 'transparent' }">
        <view class="relative pt-[130rpx]">
            <view class="absolute top-0 right-0">
                <view class="h-[300rpx] overflow-hidden">
                    <image
                        src="@/ai_modules/device/static/images/common/analysis_avatar.png"
                        class="w-[280rpx]"
                        mode="widthFix"></image>
                </view>
            </view>
            <view class="analysis-container flex flex-col items-center justify-center py-6">
                <view class="px-[72rpx] w-full">
                    <view class="flex flex-col">
                        <text class="text-[40rpx] font-black text-[#1E293B]">Ai助理分析中..</text>
                        <text class="text-[26rpx] text-[#94A3B8] mt-1">请等待1-2分钟</text>
                    </view>
                </view>

                <view class="steps-box w-full px-[50rpx] mt-6">
                    <scroll-view scroll-y class="max-h-[80vh]">
                        <transition-group name="step-list" tag="view" class="flex flex-col items-center">
                            <block v-for="(item, index) in visibleSteps" :key="item.id">
                                <view v-if="index > 0" class="arrow-box py-2">
                                    <image
                                        src="@/ai_modules/device/static/images/common/arrow_down.png"
                                        class="w-[20rpx] h-[20rpx]"
                                        mode="widthFix"></image>
                                </view>

                                <view
                                    class="step-card flex items-center w-full p-4 bg-[#F5FBFF] rounded-[24rpx] border border-[#F1F5F9]">
                                    <view class="icon-wrap mr-4">
                                        <image
                                            src="@/ai_modules/device/static/images/common/check_circle.png"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                    </view>
                                    <view class="flex-1">
                                        <view class="text-[30rpx] font-bold text-[#1E293B]">{{ item.title }}</view>
                                        <view class="text-[24rpx] text-[#64748B] mt-0.5">{{ item.desc }}</view>
                                    </view>
                                </view>
                            </block>
                        </transition-group>
                    </scroll-view>

                    <view v-if="!isAnalyzingFinished" class="mt-8 flex justify-center">
                        <u-loading mode="flower" color="#0065fb" size="36"></u-loading>
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
    <popup-bottom v-model="showGetAccountPopup" title="获取社媒账号" height="80%">
        <template #content>
            <view class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="py-[50rpx] flex flex-col gap-y-6 px-4">
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
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
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
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useDeviceWs from "@/ai_modules/device/hooks/useDeviceWs";
import useMaterialStore from "@/ai_modules/device/stores/material";
import RecorderControl from "@/ai_modules/device/components/recorder-control/recorder-control.vue";

interface AnalysisStep {
    id: number;
    title: string;
    desc: string;
}

const allSteps: AnalysisStep[] = [
    { id: 1, title: "回顾对话", desc: "已整理你的对话内容" },
    { id: 2, title: "识别行业", desc: "已识别你的行业与业务" },
    { id: 3, title: "分析客户群体", desc: "已分析你的目标客户画像" },
    { id: 4, title: "提取痛点", desc: "已提取客户核心痛点与关注点" },
    { id: 5, title: "推断运营内容", desc: "已推断适合你的内容/获客方向等" },
    { id: 6, title: "生成运营方案", desc: "已整理并生成初步方案" },
];

const formData = reactive<any>({
    industry: "",
    businessType: "",
    accountInfo: "",
    customerBase: "",
    painPoints: "",
});

const formConfigs = [
    {
        key: "industry",
        label: "您的行业定位",
        subLabel: "用于任务中：生成视频选题和私聊",
        placeholder: "请输入，如：我是做教育培训行业的，主要专注于K12方向的垂直领域等",
    },
    {
        key: "businessType",
        label: "您的业务类型",
        subLabel: "用于任务中：生成视频选题和私聊",
        placeholder: "请输入，如：我有线下加盟店等",
    },
    {
        key: "accountInfo",
        label: "您运营账号情况",
        subLabel: "用于任务中：生成视频选题和私聊",
        placeholder: "请输入，如：运营过小红书账号，但效果一般",
    },
    {
        key: "customerBase",
        label: "您服务的客户群体",
        subLabel: "用于任务中：生成视频选题和私聊",
        placeholder: "请输入，如：我想获取初中学生的家长群体客户",
    },
    {
        key: "painPoints",
        label: "客户核心痛点",
        subLabel: "用于任务中：生成视频选题和私聊",
        placeholder: "请输入，如：师资与教学的运营成本高，家长信任度低",
    },
];

// 固定话术
const fixedContent =
    "你好，我是你的AI智能运营顾问，先让我们进行简单的沟通，让我帮助你解决现在的运营难点吧。你现在做内容，是更想拿客户，还是把账号先做起来？";
const contentList = ref<any[]>([
    {
        content: fixedContent,
        role: "assistant",
    },
]);
const step = ref<number>(3);
const deviceCode = ref<string>("");
const detail = ref<any>({});
const showRecorder = ref(false);
const isReceiving = ref(false);
const recorderRef = ref<InstanceType<typeof RecorderControl> | null>(null);

const visibleSteps = ref<AnalysisStep[]>([]);
const isAnalyzingFinished = ref(false);
const showAnalysisResult = ref(false);
const showSkipDialog = ref(false);

const { platformLogo } = useDevice();
const { send, onEvent, close, isConnected } = useDeviceWs();

const showGetAccountPopup = ref(false);
const platformsToUpdate = ref<any[]>([]);
const sortedPlatformLogo = ref<any[]>(
    Object.values(platformLogo).map((item: any) => {
        return {
            ...item,
            icon: item.activeIcon,
        };
    })
);
const materialStore = useMaterialStore();
const { anchorList, videoList, imageList } = storeToRefs(materialStore);

const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

const contentPost = async (res: any) => {};

const openRecorder = async () => {
    if (isReceiving.value) {
        chattingBeginToast();
        return;
    }
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    contentPost(res);
};

// 统一提示“正在对话中”提取函数
const chattingBeginToast = () => {
    uni.$u.toast("当前还有对话中，请稍等");
};

const startAnalysis = async () => {
    for (let i = 0; i < allSteps.length; i++) {
        const delay = Math.floor(Math.random() * 1800) + 1200;
        await new Promise((resolve) => setTimeout(resolve, delay));

        visibleSteps.value.push(allSteps[i]);
    }
    isAnalyzingFinished.value = true;
};

const handleConfirmSkipDialog = () => {
    step.value = 2;
    showSkipDialog.value = false;
    isAnalyzingFinished.value = true;
};

const resetDialog = () => {
    uni.showModal({
        title: "提示",
        content: "重新对话后，将清除当前对话内容，并重新开始对话",
        success: (res) => {
            if (res.confirm) {
                isAnalyzingFinished.value = false;
                contentList.value = [
                    {
                        content: fixedContent,
                        role: "assistant",
                    },
                ];
                formData.industry = "";
                formData.businessType = "";
                formData.accountInfo = "";
                formData.customerBase = "";
                formData.painPoints = "";
            }
        },
    });
};

const handleConfirmStep2 = () => {
    // 校验表单
    if (
        formData.industry === "" ||
        formData.businessType === "" ||
        formData.accountInfo === "" ||
        formData.customerBase === "" ||
        formData.painPoints === ""
    ) {
        uni.$u.toast("请填写完整表单");
        return;
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

const handleConfirmStep3 = () => {
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
};

const getDetail = async () => {
    const data = await getDeviceDetail({ device_code: deviceCode.value });
    const { accounts } = data;
    detail.value = data;
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
};

watch(
    () => isAnalyzingFinished.value,
    (newVal) => {
        uni.setNavigationBarTitle({
            title: newVal ? "完善运营方案 " : "AI运营顾问",
        });
    }
);

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
});
</script>

<style scoped lang="scss">
/* 核心按钮的呼吸效果 */
.main-record-btn {
    position: relative;
    overflow: hidden;
}

.pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 100rpx;
    animation: recording-pulse 2s infinite;
}
.analysis-container {
    @apply rounded-[30rpx];
    background: linear-gradient(180deg, rgba(255, 239, 226, 1) 0%, rgba(255, 255, 255, 1) 15%);
}

/* 步骤卡片样式 */
.step-card {
    transition: all 0.5s ease;

    &:active {
        transform: scale(0.98);
    }
}

.check-circle {
    width: 60rpx;
    height: 60rpx;
}

.step-list-enter-active {
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.step-list-enter-from {
    opacity: 0;
    transform: translateY(40rpx) scale(0.9);
}

.arrow-box {
    animation: fadeInDown 0.5s ease both;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10rpx);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
