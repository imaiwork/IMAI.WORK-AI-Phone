<template>
    <view class="h-screen flex flex-col relative bg-[#F3F4FB]">
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :background="{
                    background: 'transparent',
                }"
                title="音色克隆"
                title-bold>
            </u-navbar>
        </view>
        <view class="grow min-h-0 relative z-30">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pb-[100rpx]">
                    <!-- 声音名称 -->
                    <view class="flex items-center gap-1">
                        <text class="text-[#E33C64] text-xl font-bold">*</text>
                        <text class="text-[30rpx] font-bold">声音名称</text>
                    </view>
                    <view class="mt-2">
                        <view class="bg-white rounded-[16rpx] px-[34rpx] py-1">
                            <u-input
                                v-model="formData.name"
                                placeholder="请输入音色名称"
                                maxlength="30"
                                clearable></u-input>
                        </view>
                    </view>

                    <!-- 音色性别和使用模型 -->
                    <view class="mt-[40rpx]">
                        <view class="flex flex-col gap-4">
                            <!-- 音色性别 -->
                            <view>
                                <view class="flex items-center gap-1">
                                    <text class="text-[#E33C64] text-xl font-bold">*</text>
                                    <text class="text-[30rpx] font-bold">音色性别</text>
                                </view>
                                <view class="flex items-center gap-2 mt-2">
                                    <view
                                        class="flex-1 flex items-center justify-center gap-2 border border-solid rounded-lg p-2 h-[80rpx] font-bold bg-white"
                                        :class="[
                                            formData.gender === item.value
                                                ? 'border-[#0065FB] text-primary'
                                                : 'border-[transparent] text-[#00000080]',
                                        ]"
                                        v-for="(item, index) in toneOptions"
                                        :key="index"
                                        @click="selectGender(item.value)">
                                        <image
                                            v-if="formData.gender === item.value"
                                            :src="item.activeIcon"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                        <image v-else :src="item.icon" class="w-[28rpx] h-[28rpx]"></image>
                                        <view>
                                            {{ item.name }}
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <!-- 使用模型 -->
                            <view class="flex items-center gap-1">
                                <text class="text-[#E33C64] text-xl font-bold">*</text>
                                <text class="text-[30rpx] font-bold">使用模型</text>
                            </view>
                            <view
                                class="bg-white rounded-[16rpx] px-[16rpx] py-[28rpx] flex items-center justify-between"
                                @click="showChooseModel = true">
                                <view class="ml-[16rpx]">
                                    {{ selectedModel?.name || "请选择" }}
                                </view>
                                <u-icon name="arrow-right" color="#B2B2B2" :size="20"></u-icon>
                            </view>
                        </view>
                    </view>

                    <!-- 音频文件 -->
                    <view class="mt-[40rpx]">
                        <view class="text-[30rpx] font-bold mb-[18rpx]">音频文件</view>
                        <view v-if="formData.url">
                            <!-- 已上传音频显示 -->
                            <view class="bg-white rounded-[16rpx] px-[26rpx] h-[170rpx] flex items-center gap-x-2">
                                <view class="flex items-center gap-x-3 flex-1">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/audio_icon.png"
                                        class="w-[68rpx] h-[68rpx] flex-shrink-0"></image>
                                    <view class="line-clamp-1 break-all"> {{ fileName }} </view>
                                </view>
                                <view
                                    class="flex items-center justify-center gap-x-1 bg-[#EBF3FE] rounded-[10rpx] flex-shrink-0 w-[116rpx] h-[60rpx]"
                                    @click="toggleAudioPlayback()">
                                    <image
                                        v-if="!isPlaying"
                                        src="@/ai_modules/digital_human/static/icons/play2.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>
                                    <image
                                        v-else
                                        src="@/ai_modules/digital_human/static/icons/stop.svg"
                                        class="w-[24rpx] h-[24rpx]"></image>
                                    <text class="text-xs text-primary">{{ isPlaying ? "暂停" : "试听" }}</text>
                                </view>
                            </view>
                            <view
                                class="mt-[50rpx] text-center text-[26rpx] font-bold text-[#00000080]"
                                @click="resetAudio"
                                >重新录音</view
                            >
                        </view>

                        <!-- 上传音频选项 -->
                        <template v-else>
                            <view class="flex items-center gap-x-2">
                                <view
                                    class="rounded-lg flex flex-col items-center justify-center bg-white p-4"
                                    @click="openRecorder">
                                    <view
                                        class="w-[80rpx] h-[80rpx] flex items-center justify-center rounded-[14rpx] bg-primary">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/microphone.svg"
                                            class="w-[50rpx] h-[50rpx]"></image>
                                    </view>
                                    <view class="text-[30rpx] mt-[32rpx] font-bold">录制自己的声音</view>
                                    <view class="text-[22rpx] text-[#B4B4B4] mt-[26rpx] text-center">
                                        点击录制语音，建议录制15-60秒的长度
                                    </view>
                                    <view
                                        class="mt-[40rpx] w-[200rpx] h-[60rpx] flex items-center justify-center rounded-[14rpx] bg-[#0065fb1a] text-primary text-[26rpx] font-bold">
                                        去录制
                                    </view>
                                </view>
                                <view
                                    class="rounded-lg flex flex-col items-center justify-center bg-white p-4"
                                    @click="uploadFromWeChat">
                                    <view
                                        class="w-[80rpx] h-[80rpx] flex items-center justify-center rounded-[14rpx] bg-[#28C445]">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/wechat.svg"
                                            class="w-[50rpx] h-[50rpx]"></image>
                                    </view>
                                    <view class="text-[30rpx] mt-[32rpx] font-bold">微信上传语音</view>
                                    <view class="text-[22rpx] text-[#B4B4B4] mt-[26rpx] text-center">
                                        选择微信聊天记录里时长30秒以上的语音上传
                                    </view>
                                    <view
                                        class="mt-[40rpx] w-[200rpx] h-[60rpx] flex items-center justify-center rounded-[14rpx] bg-[#00c08c1a] text-[#00C08E] text-[26rpx] font-bold">
                                        去上传
                                    </view>
                                </view>
                            </view>

                            <!-- 声音克隆要求 -->
                            <view class="mt-5">
                                <view class="text-[30rpx] font-bold">声音克隆要求</view>
                                <view class="leading-6 mt-2">
                                    <view class="flex gap-x-4">
                                        <view class="font-bold text-[#00000080] py-2">音频时长</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2"
                                            >建议为30秒以上</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-bold text-[#00000080] py-2">文件大小</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2"
                                            >20MB以内</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-bold text-[#00000080] py-2">文件格式</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2"
                                            >mp3、m4a、wav</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-bold text-[#00000080] py-2 flex-shrink-0">录制说明</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2"
                                            >尽量在同一声学环境下录制，避免过于喧哗的背景音和噪音。录制过程中不要长时间不说话，尽量保持语速平稳，不要声音语调时高时低，需保持音量均衡。避免多人同时说话，说话人发音及音质越清晰，克隆的质量越高</view
                                        >
                                    </view>
                                </view>
                            </view>
                        </template>
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 开始克隆按钮 -->
        <view class="mt-4 mx-4 p-4">
            <view
                class="h-[100rpx] w-[90%] mx-auto rounded-md bg-black text-white text-[30rpx] font-bold flex items-center justify-center"
                @click="startVoiceCloning()">
                开始克隆<template v-if="tokensRequired">（消耗{{ tokensRequired }}算力）</template>
            </view>
        </view>
    </view>

    <!-- 弹窗组件 -->
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <choose-model v-model:show="showChooseModel" @confirm="handleModelSelection" />
    <popup-bottom
        v-model:show="showRecorder"
        title="录制声音"
        custom-class="bg-[#F6F6F6]"
        :show-footer="false"
        height="80%"
        @close="resetAudio">
        <template #content>
            <view class="flex flex-col h-full">
                <view class="grow min-h-0">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[26rpx]">
                            <view class="bg-white px-5 py-[32rpx] rounded-[20rpx]">
                                <view class="pb-[26rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                    <view class="font-bold text-[30rpx]"> 参考阅读文案 </view>
                                    <view class="text-xs text-[#0000004d] mt-1">
                                        如果没想好录音说什么，可以挑选示例文案录音
                                    </view>
                                </view>
                                <view class="mt-[32rpx]">
                                    {{ cratedVoiceCopywriter[currCopywriterIndex] }}
                                </view>
                                <view class="flex items-center gap-x-1 mt-5" @click="generateRandomCopywriter">
                                    <u-icon name="reload" color="#0065FB"></u-icon>
                                    <text class="text-primary font-bold">随机</text>
                                </view>
                            </view>
                            <view class="mt-4 flex flex-col items-center justify-center">
                                <view
                                    class="font-bold text-[44rpx] flex items-center gap-x-2 h-[40rpx] leading-[40rpx]"
                                    v-if="isRecording">
                                    {{ formatAudioTime(recordDuration) }}
                                </view>
                                <view class="text-xs text-[#00000080] h-[40rpx] leading-[40rpx]" v-else>
                                    建议录制时长为: 15-60秒
                                </view>
                                <view class="flex items-center gap-6 mt-[70rpx]">
                                    <view v-if="!isRecording" class="flex flex-col" @click="startRecording">
                                        <view class="transcribe-start">
                                            <image
                                                src="@/ai_modules/digital_human/static/icons/microphone_white.svg"
                                                class="w-[96rpx] h-[96rpx]"></image>
                                        </view>
                                        <view class="mt-[20rpx] text-center text-[30rpx] font-bold">开始录音</view>
                                    </view>
                                    <view v-else class="flex flex-col" @click="stopRecording">
                                        <view class="transcribe-stop">
                                            <view class="w-[42rpx] h-[42rpx] rounded-[10rpx] bg-white"></view>
                                        </view>
                                        <view class="mt-[20rpx] text-center text-[30rpx] font-bold">结束录音</view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view
                    class="mt-2 mb-5 w-[330rpx] h-[90rpx] rounded-[50rpx] bg-white mx-auto text-[30rpx] flex items-center justify-center text-[#00000080]"
                    @click="cancelRecording"
                    >取消</view
                >
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { ChooseResult, chooseFile } from "@/components/file-upload/choose-file";
import { uploadFile } from "@/api/app";
import { voiceClone } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";
import { useRecorder } from "@/hooks/useRecorder";
import { formatAudioTime } from "@/utils/util";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import ChooseModel from "@/ai_modules/digital_human/components/choose-model/choose-model.vue";
import ManIcon from "@/ai_modules/digital_human/static/icons/man.svg";
import ManActiveIcon from "@/ai_modules/digital_human/static/icons/man_s.svg";
import WomanIcon from "@/ai_modules/digital_human/static/icons/woman.svg";
import WomanActiveIcon from "@/ai_modules/digital_human/static/icons/woman_s.svg";
import { DigitalHumanModelVersionEnum } from "../../enums";
import { cratedVoiceCopywriter } from "../../config/copywriter";

// Store
const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// 计算属性
const selectedModel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.find((item: any) => item.id == formData.model_version);
    }
    return {};
});

const tokensRequired = computed(() => {
    const tokenMap: any = {
        [DigitalHumanModelVersionEnum.STANDARD]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE)?.score,
        [DigitalHumanModelVersionEnum.SUPER]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_PRO)?.score,
        [DigitalHumanModelVersionEnum.ADVANCED]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_ADVANCED)?.score,
        [DigitalHumanModelVersionEnum.ELITE]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_ELITE)?.score,
        [DigitalHumanModelVersionEnum.CHANJING]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_CHANJING)?.score,
    };
    return parseFloat(tokenMap[formData.model_version]);
});

// 表单数据
const formData = reactive({
    url: "",
    name: "",
    gender: "male" as "male" | "female",
    model_version: "",
});

const fileName = ref("");

// 音色选项
const toneOptions: any[] = [
    { name: "男声", value: "male", icon: ManIcon, activeIcon: ManActiveIcon },
    { name: "女声", value: "female", icon: WomanIcon, activeIcon: WomanActiveIcon },
];

// 弹窗控制
const showChooseModel = ref(false);
const showRecorder = ref(false);

// 录音相关
const currCopywriterIndex = ref(0);
const recordDurationTimer = ref<any>(null);
const recordDuration = ref<number>(0);
const isCancel = ref(false);

// 音频播放hook
const { setUrl, isPlaying, play, pause, destroy } = useAudio();

// 录音hook
const { authorize, isRecording, start, stop, close } = useRecorder(
    {
        onstart: () => {
            startCountTime();
        },
        onstop: async (result: any) => {
            if (isCancel.value) return;
            const { tempFilePath, fileSize } = result;
            if (!validateAudioSize(fileSize)) {
                return;
            }
            clearInterval(recordDurationTimer.value);
            await uploadAudio(tempFilePath);
            setUrl(tempFilePath);
        },
        onerror: (error) => {
            resetRecordDuration();
        },
    },
    {
        duration: 1000 * 60, // 60秒
    }
);

// 组件引用
const rechargePopupRef = ref();

// 方法定义
const selectGender = (gender: "male" | "female") => {
    formData.gender = gender;
};

const handleModelSelection = (modelId: any) => {
    formData.model_version = modelId;
};

const openRecorder = () => {
    showRecorder.value = true;
    isCancel.value = false;
};

const uploadFromWeChat = async () => {
    await handleUploadAudio();
    setUrl(formData.url);
};

// 验证音频大小
const maxFileSize = 20; // MB
const validateAudioSize = (size: number): boolean => {
    if (size > maxFileSize * 1024 * 1024) {
        uni.$u.toast(`音频文件大小不能超过${maxFileSize}M`);
        return false;
    }
    return true;
};

// 上传音频文件
const handleUploadAudio = async () => {
    try {
        const filesResult = await chooseFile({
            type: "file",
            extension: ["mp3", "wav", "m4a"],
        });
        await processSelectedFile(filesResult);
    } catch (error) {
        console.error("选择文件出错:", error);
    }
};

// 处理选中的文件
const processSelectedFile = async (filesResult: ChooseResult) => {
    const { tempFiles } = filesResult;
    const { size, name } = tempFiles[0];
    const fileType = name.split(".").pop()?.toLowerCase();

    // 验证文件类型
    if (!fileType || !["mp3", "m4a", "wav"].includes(fileType)) {
        uni.$u.toast("请上传mp3、m4a、wav格式的音频文件");
        return;
    }

    // 验证文件大小
    if (!validateAudioSize(size)) {
        return;
    }

    // 上传文件
    await uploadAudio(tempFiles[0].path);
};

// 上传音频到服务器
const uploadAudio = async (filePath: string) => {
    uni.showLoading({
        title: "上传中",
        mask: true,
    });

    try {
        const { uri, name }: any = await uploadFile("audio", {
            filePath: filePath,
        });
        formData.url = uri;
        fileName.value = name;
        showRecorder.value = false;
    } catch (error: any) {
        uni.showToast({
            title: error.message || "上传失败",
            icon: "none",
            duration: 3000,
        });
    } finally {
        uni.hideLoading();
    }
};

// 录音计时
const startCountTime = () => {
    resetRecordDuration();
    recordDurationTimer.value = setInterval(() => {
        recordDuration.value += 1;
    }, 1000);
};

const resetRecordDuration = () => {
    recordDuration.value = 0;
    clearInterval(recordDurationTimer.value);
};

// 录音控制方法
const startRecording = async () => {
    await authorize();
    if (!isRecording.value) {
        start();
    }
};

const stopRecording = () => {
    if (recordDuration.value < 15) {
        uni.$u.toast("录制时间不能少于15秒");
        return;
    }
    stop();
};

const cancelRecording = () => {
    isRecording.value = false;
    showRecorder.value = false;
    isCancel.value = true;
    stop();
    destroy();
    resetRecordDuration();
    close();
};

// 音频播放控制
const toggleAudioPlayback = async () => {
    if (isPlaying.value) {
        pause();
    } else {
        play();
    }
};

// 重置音频
const resetAudio = () => {
    isRecording.value = false;
    showRecorder.value = false;
    isCancel.value = false;
    formData.url = "";
    fileName.value = "";
    stop();
    destroy();
    resetRecordDuration();
    close();
};

// 随机文案
const generateRandomCopywriter = () => {
    currCopywriterIndex.value = Math.floor(Math.random() * cratedVoiceCopywriter.length);
};

// 开始克隆
const startVoiceCloning = async () => {
    // 表单验证
    if (!formData.name.trim()) {
        uni.$u.toast("请输入音色名称");
        return;
    }

    if (!formData.url) {
        uni.$u.toast("请先上传音频");
        return;
    }

    if (!formData.model_version) {
        uni.$u.toast("请选择使用模型");
        return;
    }

    // 算力检查
    if (userTokens.value < tokensRequired.value) {
        uni.$u.toast("算力不足，请充值！");
        rechargePopupRef.value?.open();
        return;
    }

    // 执行克隆
    try {
        uni.showLoading({
            title: "克隆中",
            mask: true,
        });

        await voiceClone(formData);
        userStore.getUser(); // 更新用户信息

        setTimeout(() => {
            uni.$u.toast("克隆成功，请在我的音色中查看");
            navigateToHome();
        }, 300);
    } catch (error: any) {
        uni.$u.toast(error.message || "克隆失败");
    } finally {
        uni.hideLoading();
    }
};

// 导航到首页
const navigateToHome = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/clone_manage/clone_manage",
        type: "redirect",
    });
};

// 监听模型配置变化
watch(
    () => appStore.getDigitalHumanConfig.channel,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            formData.model_version = newVal.find((item: any) => item.id == DigitalHumanModelVersionEnum.CHANJING)?.id;
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped lang="scss">
.transcribe-start {
    @apply w-[148rpx] h-[148rpx] rounded-full flex items-center justify-center;
    background: linear-gradient(90deg, #3663f4 0%, #5f82f1 100%);
}

.transcribe-stop {
    @apply w-[148rpx] h-[148rpx] rounded-full flex items-center justify-center;
    background: linear-gradient(90deg, #e44250 0%, #f47876 100%);
}

.confirm-btn {
    background: linear-gradient(132.89deg, rgba(145, 169, 249, 1) 0%, rgba(171, 188, 248, 1) 100%);
    &.active {
        background: linear-gradient(132.89deg, rgba(35, 83, 244, 1) 0%, rgba(115, 144, 240, 1) 100%);
    }
}
</style>
