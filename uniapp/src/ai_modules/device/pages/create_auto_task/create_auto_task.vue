<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="grow min-h-0">
            <scroll-view ref="chatScrollRef" scroll-y class="h-full" :scroll-top="scrollTop" v-show="step === 1">
                <view class="p-4 content-box">
                    <view v-for="(item, index) in contentList" :key="index">
                        <view class="flex items-start mb-6" v-if="item.type == 2">
                            <view class="w-10 h-10 rounded-full bg-[#D9E6FC] mr-3 overflow-hidden">
                                <image
                                    src="@/ai_modules/device/static/images/common/analysis_avatar.png"
                                    class="w-full h-full"
                                    mode="widthFix" />
                            </view>

                            <view
                                class="max-w-[75%] bg-white p-4 rounded-tr-[30rpx] rounded-br-[30rpx] rounded-bl-[30rpx] shadow-sm border border-[#F1F5F9]">
                                <text class="text-[28rpx] text-[#1E293B] leading-relaxed" v-if="!item.loading">
                                    {{ item.reply }}
                                </text>
                                <view class="chat-loader" v-else> </view>
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
            </scroll-view>
            <scroll-view
                ref="formScrollRef"
                scroll-y
                class="h-full"
                v-show="step === 2"
                :scroll-into-view="formScrollIntoView">
                <view class="p-4 pb-[350rpx]">
                    <view class="mb-6">
                        <text class="text-[34rpx] font-bold text-primary">完善运营方案</text>
                        <view class="text-[#000000]/50 text-xs mt-1">
                            基于对话已生成运营方案，你可修改补充。根据你的运营信息，AI自动填充到24h任务设置里
                        </view>
                    </view>

                    <view v-for="(item, index) in formConfigs" :key="index" class="mb-8" :id="item.key">
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
            </scroll-view>
            <scroll-view scroll-y class="h-full" v-show="step === 3">
                <view class="p-4 pb-[150rpx]">
                    <view class="rounded-[20rpx] bg-white px-[36rpx] py-[22rpx] relative">
                        <view class="flex items-center justify-between">
                            <view class="font-bold text-[30rpx]">社媒账号</view>
                            <view class="text-[#000000]/50" @click="showGetAccountPopup = true"
                                >详情
                                <u-icon name="arrow-right" color="#9DA5B0" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center gap-x-2 mt-[22rpx]">
                            <view v-for="(item, index) in sortedPlatform" :key="index">
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
                                        class="aspect-[3/4] rounded-[20rpx] overflow-hidden relative">
                                        <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
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
                                        class="aspect-[3/4] rounded-[20rpx] relative overflow-hidden">
                                        <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
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
                                        class="aspect-[3/4] rounded-[20rpx]"
                                        v-for="(item, index) in imageList.slice(0, 3)"
                                        :key="index">
                                        <image
                                            :src="item.url"
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
        <view class="shrink-0">
            <!-- <u-input v-model="contentVal" placeholder="请输入或粘贴您的文案 ..." :auto-height="true" :maxlength="500" />
            <u-button @click="contentPost(contentVal)">发送</u-button> -->
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

                <view class="steps-box w-full mt-6">
                    <scroll-view ref="stepsScrollRef" scroll-y class="h-[50vh]" :scroll-top="stepsScrollTop">
                        <transition-group
                            name="step-list"
                            tag="view"
                            class="flex flex-col items-center steps-container px-[50rpx]">
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
                                            v-if="item.status === 1"
                                            src="@/ai_modules/device/static/images/common/check_circle.png"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                        <image
                                            v-else-if="item.status === 3"
                                            src="@/ai_modules/device/static/images/common/circle_error.svg"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                        <image
                                            v-else-if="item.status === 2"
                                            src="@/ai_modules/device/static/images/common/circle_loading.svg"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                    </view>
                                    <view class="flex-1">
                                        <view class="text-[30rpx] font-bold text-[#1E293B]">{{ item.title }}</view>
                                        <view
                                            class="text-[24rpx] mt-0.5"
                                            :class="item.status === 3 ? 'text-[#FF2442]' : 'text-[#64748B]'"
                                            >{{ item.desc }}</view
                                        >
                                    </view>
                                </view>
                            </block>
                        </transition-group>
                    </scroll-view>

                    <view
                        v-if="!isAnalyzingFinished && visibleSteps.every((item) => item.status != 3)"
                        class="mt-8 flex justify-center">
                        <u-loading mode="flower" color="#0065fb" size="36"></u-loading>
                    </view>
                </view>
                <view class="px-4 mt-4" v-if="analysisError">
                    <u-button
                        type="error"
                        ripple
                        :custom-style="{ borderRadius: '10rpx', fontSize: '26rpx', fontWeight: '900' }"
                        @click="handleRetryAnalysis">
                        重新分析
                    </u-button>
                    <u-button plain @click="showAnalysisResult = false"> 关闭 </u-button>
                </view>
            </view>
        </view>
    </u-popup>
    <account-get
        v-if="showGetAccountPopup"
        v-model="showGetAccountPopup"
        :sorted-platform="sortedPlatform"
        @get-account="handleGetAccount(deviceCode, false)" />
    <confirm-dialog
        v-if="showSkipDialog"
        v-model="showSkipDialog"
        center
        content="跳过对话会造成信息不完善，导致方案不完整，需要你手动完善信息，是否确定跳过对话？"
        @confirm="handleConfirmSkipDialog"></confirm-dialog>
    <confirm-dialog
        v-if="showCreateSuccessDialog"
        v-model="showCreateSuccessDialog"
        center
        content="创建成功，返回上一页面？"
        @cancel="handleCancelCreateSuccess"
        @confirm="handleConfirmCreateSuccess"></confirm-dialog>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import {
    getDeviceDetail,
    getAutoTaskDetail as getAutoTaskDetailApi,
    createAutoTask as createAutoTaskApi,
    createAutoTaskPublishConfig,
    marketingChat,
    marketingAnalysis,
} from "@/api/device";
import { useUserStore } from "@/stores/user";
import { getRect, setFormData } from "@/utils/util";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useMaterialStore from "@/ai_modules/device/stores/material";
import RecorderControl from "@/ai_modules/device/components/recorder-control/recorder-control.vue";
import AccountGet from "@/ai_modules/device/components/account-get/account-get.vue";

interface AnalysisStep {
    id: number;
    title: string;
    desc: string;
    status: 0 | 1 | 2 | 3; //  0: 未开始, 1: 已完成, 2: 进行中 3: 失败
}

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const allSteps: AnalysisStep[] = [
    { id: 1, title: "回顾对话", desc: "已整理你的对话内容", status: 0 },
    { id: 2, title: "识别行业", desc: "已识别你的行业与业务", status: 0 },
    { id: 3, title: "分析客户群体", desc: "已分析你的目标客户画像", status: 0 },
    { id: 4, title: "提取痛点", desc: "已提取客户核心痛点与关注点", status: 0 },
    { id: 5, title: "推断运营内容", desc: "已推断适合你的内容/获客方向等", status: 0 },
    { id: 6, title: "生成运营方案", desc: "已整理并生成初步方案", status: 0 },
];

const formData = reactive<any>({
    operation_persona: "", //运营人设
    business_type: "", //业务类型
    account_stage: "", //账号阶段
    target_audience: "", //客户对象
    core_pain: "", //客户核心痛点
    main_platform: "", //主要运营平台
    platform_focus: "", //平台侧重点
    content_style: "", //内容风格倾向
    main_block: "", //当前最大运营卡点
    risk_tolerance: "", //账号风险承受度
    benchmark_account: "", //对标账号
});

const formConfigs = [
    {
        label: "运营人设",
        subLabel: "用于生成运营人设",
        placeholder: "请输入，如：IP智能运营大师",
        key: "operation_persona",
    },
    {
        key: "business_type",
        label: "业务类型",
        subLabel: "用于判断内容侧重（本地/线上）及转化路径设计",
        placeholder: "请输入，如：线上电商、本地服务、知识付费等",
    },
    {
        key: "account_stage",
        label: "账号阶段",
        subLabel: "用于调整内容难度和运营节奏建议",
        placeholder: "请输入，如：运营过小红书账号，但效果一般",
    },
    {
        key: "target_audience",
        label: "客户对象",
        subLabel: "用于生成更贴近目标人群的内容和话术",
        placeholder: "请输入，如：初中学生家长群体",
    },
    {
        key: "core_pain",
        label: "客户核心痛点",
        subLabel: "用于生成直击需求的选题和私聊引导话术",
        placeholder: "请输入，如：师资与教学的运营成本高，家长信任度低",
    },
    {
        key: "main_platform",
        label: "主要运营平台",
        subLabel: "用于适配不同平台的内容结构和表达方式",
        placeholder: "请输入，如：抖音、快手、小红书等",
    },
    {
        key: "platform_focus",
        label: "平台侧重点",
        subLabel: "用于确定内容分发重心和产出比例",
        placeholder: "请输入，如：抖音侧重短视频，快手侧重直播",
    },
    {
        key: "content_style",
        label: "内容风格倾向",
        subLabel: "用于生成符合你偏好的文案语气和表达风格",
        placeholder: "请输入，如：轻松易懂、专业干货、幽默风趣等",
    },
    {
        key: "main_block",
        label: "当前最大运营卡点",
        subLabel: "用于优先解决最关键的问题并调整生成策略",
        placeholder: "请输入，如：涨粉困难、内容创作瓶颈、变现路径不清晰",
    },
    {
        key: "risk_tolerance",
        label: "账号风险承受度",
        subLabel: "用于控制内容尺度和避免违规风险",
        placeholder: "请输入，如：低风险、中风险、高风险",
    },
    {
        key: "benchmark_account",
        label: "对标账号",
        subLabel: "用于参考成熟内容结构和选题方向（如有）",
        placeholder: "请输入，如：xxx账号、xxx账号等",
    },
];

// 固定话术
const fixedContent =
    "你好，我是你的AI智能运营顾问，先让我们进行简单的沟通，让我帮助你解决现在的运营难点吧。你现在做内容，是更想拿客户，还是把账号先做起来？";
const contentList = ref<any[]>([
    {
        reply: fixedContent,
        type: 2,
    },
]);

const contentVal = ref<string>("");

const step = ref<number>(1);
const loading = ref(false);
const deviceCode = ref<string>("");
const detail = ref<any>({});
const showRecorder = ref(false);
const isReceiving = ref(false);
const chatConversationId = ref<string>("");
const scrollTop = ref<number>(0);
const chatScrollRef = shallowRef();
const recorderRef = ref<InstanceType<typeof RecorderControl> | null>(null);
const visibleSteps = ref<AnalysisStep[]>([]);
const stepsScrollRef = ref<any>(null);
const stepsScrollTop = ref<number>(0);

const isAnalyzingFinished = ref(false);
const analysisError = ref(false);
const showAnalysisResult = ref(false);
const showSkipDialog = ref(false);

const formScrollRef = ref<any>(null);
const formScrollIntoView = ref<string>("");

const { sortedPlatform, initializePlatform, handleGetAccount } = useDevice();
const showGetAccountPopup = ref(false);

const showCreateSuccessDialog = ref(false);
const rechargePopupRef = shallowRef();

const materialStore = useMaterialStore();
const { anchorList, videoList, imageList } = storeToRefs(materialStore);

const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });
// 流式请求读取器
let streamReader: any = null;
const contentPost = async (content: any) => {
    if (isReceiving.value) return;

    contentList.value.push({
        content: content,
        type: 1,
    });

    const result = reactive({
        type: 2,
        loading: true,
        reply: "",
    });
    contentList.value.push(result);
    isReceiving.value = true;
    scrollToBottom();

    try {
        await marketingChat(
            {
                content: content,
                conversation_id: chatConversationId.value,
            },
            {
                onstart: (reader: any) => {
                    streamReader = reader;
                },
                onmessage: (data: any) => {
                    handleStreamMessage(data, result);
                },
                onclose() {
                    result.loading = false;
                    isReceiving.value = false;
                    scrollToBottom();
                },
            }
        );
    } catch (error: any) {
        result.loading = false;
        result.reply = error || "对话失败";
        isReceiving.value = false;
        uni.$u.toast(result.reply);
    }
};

const handleStreamMessage = (value: string, result: any) => {
    value
        .trim()
        .split("data:")
        .forEach((text) => {
            if (!text) return;
            try {
                const { object, content, conversation_id } = JSON.parse(text);
                if (content && object === "loading") {
                    result.reply += content;
                }
                if (object === "finished") {
                    result.loading = false;
                    if (textSimilarityLCS(result.reply, "好的，那本次谈话就到这里，我已经大概了解你的情况了。") > 0.6) {
                        startAnalysis();
                    }
                    chatConversationId.value = conversation_id;
                    return;
                }
                scrollToBottom();
            } catch (error) {
                console.error("解析流式消息失败:", error);
            }
        });
};

const { proxy }: any = getCurrentInstance();
const scrollToBottom = async () => {
    await nextTick();
    getRect(".content-box", false, proxy).then((res: any) => {
        scrollTop.value = res.height;
    });
};

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
    const { message } = res;
    contentPost(message);
};

// 统一提示“正在对话中”提取函数
const chattingBeginToast = () => {
    uni.$u.toast("当前还有对话中，请稍等");
};
const startAnalysis = async () => {
    showAnalysisResult.value = true;

    // 辅助函数：显示下一个步骤
    const showStep = async (index: number, duration = 800) => {
        if (index >= allSteps.length) return;

        allSteps[index].status = 2;
        visibleSteps.value.push({ ...allSteps[index] });
        scrollToStepsBottom();

        await new Promise((resolve) => setTimeout(resolve, duration));

        allSteps[index].status = 1;
        visibleSteps.value[index] = { ...allSteps[index] };
    };

    try {
        // 显示前3个快速步骤
        for (let i = 0; i < 3; i++) {
            await showStep(i, 2000);
        }

        // 显示第4、5步骤（稍慢）
        await showStep(3, 2500);
        await showStep(4, 1000);

        // 开始实际分析，显示最后一步
        allSteps[5].status = 2;
        visibleSteps.value.push({ ...allSteps[5] });
        scrollToStepsBottom();

        if (userTokens.value <= 0) {
            rechargePopupRef.value?.open();
            allSteps[5].status = 3;
            return;
        }

        const { result } = await marketingAnalysis({
            conversation_id: chatConversationId.value,
        });

        // 完成最后一步
        allSteps[5].status = 1;
        visibleSteps.value[5] = { ...allSteps[5] };

        isAnalyzingFinished.value = true;
        getAnalysisResult(result);
    } catch (error) {
        handleAnalysisError();
    }
};

// 错误处理函数
const handleAnalysisError = () => {
    analysisError.value = true;
    const lastIndex = visibleSteps.value.length - 1;
    if (lastIndex >= 0) {
        visibleSteps.value[lastIndex].desc = "获取分析结果失败，可以点击按钮重新分析";
        visibleSteps.value[lastIndex].status = 3;
    }
    uni.$u.toast("获取分析结果失败，可以点击按钮重新分析");
};
const handleRetryAnalysis = () => {
    visibleSteps.value = [];
    stepsScrollTop.value = 0;
    analysisError.value = false;
    startAnalysis();
};

// 获取分析结果
const getAnalysisResult = async (result: any) => {
    setFormData(result, formData);
    createAutoTask();
    uni.$u.toast("分析结果已生成，即将跳转至下一步");

    setTimeout(() => {
        scrollTop.value = 0;
        step.value = 2;
        showAnalysisResult.value = false;
    }, 3000);
};

const scrollToStepsBottom = async () => {
    await nextTick();
    getRect(".steps-container", false, proxy).then((res: any) => {
        stepsScrollTop.value = res.height;
    });
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
                        reply: fixedContent,
                        type: 2,
                    },
                ];
                Object.keys(formData).forEach((key) => {
                    formData[key] = "";
                });
                chatConversationId.value = "";
                visibleSteps.value = [];
                step.value = 1;
            }
        },
    });
};

const handleConfirmStep2 = async () => {
    // 校验表单
    const data = Object.entries(formData).find(([_, value]) => !value);
    if (data) {
        uni.$u.toast(`请填写${formConfigs.find((item: any) => item.key === data[0])?.label}表单`);
        formScrollIntoView.value = data[0];
        return;
    }
    uni.showLoading({
        title: "数据保存中...",
        mask: true,
    });
    try {
        await createAutoTask();
        scrollTop.value = 0;
        step.value = 3;
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "数据保存失败，请稍后重试",
            icon: "none",
            duration: 3000,
        });
    }
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

const handleConfirmStep3 = async () => {
    // 这里要判断平台有一个激活了,如果不是则弹窗拉取
    const isSomeActive = sortedPlatform.value.some((item) => item.status == 2);
    if (!isSomeActive) {
        uni.$u.toast("请先激活相关平台，再进行下一步操作");
        showGetAccountPopup.value = true;
        return;
    }
    if (materialStore.anchorList.length == 0) {
        uni.$u.toast("请选择形象");
        return;
    }
    if (!materialStore.videoList.length) {
        uni.$u.toast("请选择视频素材");
        return;
    }
    if (materialStore.imageList.length == 0) {
        uni.$u.toast("请选择图文素材");
        return;
    }
    uni.showLoading({
        title: "数据保存中...",
        mask: true,
    });
    try {
        const result = await createAutoTask();
        await createAutoTaskPublishConfig({
            text_theme: "",
            video_theme: "",
            device_code: deviceCode.value,
            device_config_id: result.id,
            ...getCreateAutoTaskParams(),
        });
        showCreateSuccessDialog.value = true;
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "数据保存失败，请稍后重试",
            icon: "none",
            duration: 3000,
        });
    }
};

// 提取createAutoTask的参数
const getCreateAutoTaskParams = () => {
    return {
        human_image: materialStore.anchorList.map((item: any) => ({
            ...item.anchor_ids,
            ...item.extra_info,
            id: item.id,
            pic: item.pic,
            voice_id: item.extra_info.shanjian_voice_id,
            anchor_url: item.url,
        })),
        clip_material: materialStore.videoList.map((item: any) => ({
            type: item.type,
            fileUrl: item.url,
            cover: item.pic,
        })),
        image_material: materialStore.imageList.map((item: any) => item.url),
    };
};

const createAutoTask = async (): Promise<any> => {
    return new Promise(async (resolve, reject) => {
        try {
            const params = {
                clue_theme: "",
                text_theme: "",
                video_theme: "",
                device_code: deviceCode.value,
                conversation_id: chatConversationId.value,
                ...formData,
                ...getCreateAutoTaskParams(),
            };
            const res = await createAutoTaskApi(params);
            resolve(res);
        } catch (error) {
            reject(error);
        }
    });
};

const handleConfirmCreateSuccess = () => {
    uni.$u.route({ url: "/pages/phone/phone", type: "reLaunch" });
};

const handleCancelCreateSuccess = () => {
    uni.$u.route({ url: "/ai_modules/device/pages/auto_task/auto_task", type: "reLaunch" });
};

const getDetail = async () => {
    const data = await getDeviceDetail({ device_code: deviceCode.value });
    const { accounts } = data;
    detail.value = data;
    initializePlatform(accounts);
};

const getAutoTaskDetail = async () => {
    const res = await getAutoTaskDetailApi({ device_code: deviceCode.value });

    if (res.conversation_id || Object.keys(formData).some((key) => res[key])) {
        setFormData(res, formData);
        chatConversationId.value = res.conversation_id;

        step.value = 2;
    }
    materialStore.anchorList = res.human_image.map((item: any) => ({
        ...item,
        extra_info: {
            shanjian_voice_id: item.shanjian_voice_id,
        },
        result_url: item.anchor_url,
        anchor_ids: {
            shanjian_anchor_id: item.shanjian_voice_id,
            weiju_anchor_id: item.weiju_anchor_id,
            chanjing_anchor_id: item.chanjing_anchor_id,
        },
    }));
    materialStore.videoList = res.clip_material.map((item: any) => ({
        ...item,
        type: item.type,
        url: item.fileUrl,
        pic: item.cover,
    }));
    materialStore.imageList = res.image_material.map((item: any) => ({
        url: item,
    }));
};

const init = async () => {
    uni.showLoading({
        title: "加载中...",
    });
    try {
        await Promise.allSettled([getDetail(), getAutoTaskDetail()]);
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

function textSimilarityLCS(text1: string, text2: string) {
    const clean1 = text1.replace(/[，。！？、；：""''（）《》【】\s]/g, "");
    const clean2 = text2.replace(/[，。！？、；：""''（）《》【】\s]/g, "");

    const lcsLength = getLCSLength(clean1, clean2);

    const minLength = Math.min(clean1.length, clean2.length);
    const similarity = minLength > 0 ? lcsLength / minLength : 0;

    return similarity;
}

function getLCSLength(str1: string, str2: string) {
    const m = str1.length;
    const n = str2.length;
    const dp = Array(m + 1)
        .fill(0)
        .map(() => Array(n + 1).fill(0));

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            if (str1[i - 1] === str2[j - 1]) {
                dp[i][j] = dp[i - 1][j - 1] + 1;
            } else {
                dp[i][j] = Math.max(dp[i - 1][j], dp[i][j - 1]);
            }
        }
    }

    return dp[m][n];
}

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
    init();
});
</script>

<style scoped lang="scss">
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
