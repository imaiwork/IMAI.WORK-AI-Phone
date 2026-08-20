<template>
    <view class="h-screen flex flex-col relative bg-[#F3F4FB]">
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :background="{
                    background: 'transparent',
                }"
                title=""
                title-bold>
            </u-navbar>
        </view>
        <view class="grow min-h-0 relative z-30">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pb-[100rpx]">
                    <view class="flex items-center gap-1">
                        <text class="text-[#E33C64] text-xl font-medium">*</text>
                        <text class="text-[30rpx] font-medium">名称</text>
                    </view>
                    <view class="mt-2">
                        <view class="bg-white rounded-[16rpx] px-[34rpx] py-1">
                            <u-input
                                v-model="formData.name"
                                placeholder="请输入音色名称"
                                maxlength="50"
                                clearable></u-input>
                        </view>
                    </view>

                    <view class="mt-[40rpx]">
                        <view class="flex flex-col gap-4">
                            <view class="flex items-center gap-1">
                                <text class="text-[#E33C64] text-xl font-medium">*</text>
                                <text class="text-[30rpx] font-medium">使用模型</text>
                            </view>
                            <view
                                class="bg-white rounded-[16rpx] px-[16rpx] py-[28rpx] flex items-center justify-between"
                                @click="showChooseModel = true">
                                <view class="ml-[16rpx]">
                                    {{ getSelectedModelName }}
                                </view>
                                <u-icon name="arrow-right" color="#B2B2B2" :size="20"></u-icon>
                            </view>
                        </view>
                    </view>

                    <view class="mt-[40rpx]">
                        <view class="text-[30rpx] font-medium mb-[18rpx]">音频文件</view>
                        <view v-if="formData.url">
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
                                class="mt-[50rpx] text-center font-medium text-[#00000080]"
                                @click="handleDeleteAudio">
                                重新录音
                            </view>
                        </view>

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
                                    <view class="text-[30rpx] mt-[32rpx] font-medium">录制自己的声音</view>
                                    <view class="text-[22rpx] text-[#B4B4B4] mt-[26rpx] text-center">
                                        点击录制语音，建议录制15-60秒的长度
                                    </view>
                                    <view
                                        class="mt-[40rpx] w-[200rpx] h-[60rpx] flex items-center justify-center rounded-[14rpx] bg-[#0065fb1a] text-primary font-medium">
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
                                    <view class="text-[30rpx] mt-[32rpx] font-medium">微信上传音频</view>
                                    <view class="text-[22rpx] text-[#B4B4B4] mt-[26rpx] text-center">
                                        选择微信聊天记录里时长30秒以上的音频上传
                                    </view>
                                    <view
                                        class="mt-[40rpx] w-[200rpx] h-[60rpx] flex items-center justify-center rounded-[14rpx] bg-[#00c08c1a] text-[#00C08E] font-medium">
                                        去上传
                                    </view>
                                </view>
                            </view>

                            <view class="mt-5">
                                <view class="text-[30rpx] font-medium">要求</view>
                                <view class="leading-6 mt-2">
                                    <view class="flex gap-x-4">
                                        <view class="font-medium text-[#00000080] py-2">音频时长</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2">
                                            建议为{{ minDuration }}秒以上，{{ maxDuration }}秒以内</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-medium text-[#00000080] py-2">文件大小</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2">
                                            20MB以内</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-medium text-[#00000080] py-2">文件格式</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2">
                                            {{ getUploadAudioFormat().join("、") }}</view
                                        >
                                    </view>
                                    <view class="flex gap-x-4">
                                        <view class="font-medium text-[#00000080] py-2 flex-shrink-0">录制说明</view>
                                        <view
                                            class="flex-1 text-[#00000080] border-[0] border-b-[1rpx] border-solid border-[#0000000d] py-2">
                                            尽量在同一声学环境下录制，避免过于喧哗的背景音和噪音。录制过程中不要长时间不说话，尽量保持语速平稳，不要声音语调时高时低，需保持音量均衡。避免多人同时说话，说话人发音及音质越清晰，克隆的质量越高
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </template>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="mt-4 mx-4 p-4">
            <view
                class="flex items-center justify-center mb-[24rpx]"
                @click.stop="isAgreedVoiceprint = !isAgreedVoiceprint">
                <u-icon
                    :name="isAgreedVoiceprint ? 'checkmark-circle-fill' : 'checkmark-circle'"
                    :color="isAgreedVoiceprint ? '#0065FB' : '#B2B2B2'"
                    :size="32"></u-icon>
                <text class="text-xs text-[#00000080] ml-1">我已阅读并同意</text>
                <text class="text-xs text-primary" @click.stop="showVoiceprintAgreement = true">
                    《{{ voiceprintAgreementTitle }}》
                </text>
            </view>
            <view
                class="h-[100rpx] w-[90%] mx-auto rounded-[100rpx] bg-primary text-white text-[30rpx] font-medium flex items-center justify-center"
                @click="startVoiceCloning()">
                开始克隆<template v-if="tokensRequired">（消耗{{ tokensRequired }}算力）</template>
            </view>
        </view>
    </view>

    <agreement
        v-model="showVoiceprintAgreement"
        :title="voiceprintAgreementTitle"
        :content="voiceprintAgreementContent"
        @agree="agreeVoiceprint"
        @close="showVoiceprintAgreement = false" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <choose-model v-model="showChooseModel" @confirm="handleModelSelection" />
    <popup-bottom
        v-model="showRecorder"
        title="录制声音"
        custom-class="bg-[#F6F6F6]"
        :show-footer="false"
        height="80%"
        @close="resetAudio">
        <template #content>
            <view class="flex flex-col h-full pt-4">
                <view class="grow min-h-0">
                    <scroll-view class="h-full" scroll-y>
                        <view class="px-[26rpx]">
                            <view class="bg-white px-5 py-[32rpx] rounded-[20rpx]">
                                <view class="pb-[26rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                    <view class="font-medium text-[30rpx]"> 参考阅读文案 </view>
                                    <view class="text-xs text-[#0000004d] mt-1">
                                        如果没想好录音说什么，可以挑选示例文案录音
                                    </view>
                                </view>
                                <view class="mt-[32rpx]">
                                    {{ cratedVoiceCopywriter[currCopywriterIndex] }}
                                </view>
                                <view class="flex items-center gap-x-1 mt-5" @click="generateRandomCopywriter">
                                    <u-icon name="reload" color="#0065FB"></u-icon>
                                    <text class="text-primary font-medium">随机</text>
                                </view>
                            </view>
                            <view class="mt-4 flex flex-col items-center justify-center">
                                <view
                                    class="font-medium text-[44rpx] flex items-center gap-x-2 h-[40rpx] leading-[40rpx]"
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
                                        <view class="mt-[20rpx] text-center text-[30rpx] font-medium">开始录音</view>
                                    </view>
                                    <view v-else class="flex flex-col" @click="stopRecording">
                                        <view class="transcribe-stop">
                                            <view class="w-[42rpx] h-[42rpx] rounded-[10rpx] bg-white"></view>
                                        </view>
                                        <view class="mt-[20rpx] text-center text-[30rpx] font-medium">结束录音</view>
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
import { voiceClone, shanjianVoiceClone, minimaxAudioUpload, minimaxVoiceClone } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";
import { useRecorder } from "@/hooks/useRecorder";
import { formatAudioTime } from "@/utils/util";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { cratedVoiceCopywriter } from "../../config/copywriter";
import { voiceprintAgreementTitle, voiceprintAgreementContent } from "../../config/voiceprint-agreement";
import ChooseModel from "@/ai_modules/digital_human/components/choose-model/choose-model.vue";
import Agreement from "@/ai_modules/digital_human/components/agreement/agreement.vue";

// Store
const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// 计算属性
const getSelectedModelName = computed(() => {
    const channel = appStore.getAiModelConfig.humanModels;
    return channel?.find((item: any) => item.model_version == formData.model_version)?.name || "请选择";
});

const tokensRequired = computed(() => {
    const tokenMap: any = {
        [DigitalHumanModelVersionEnum.CHANJING]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_CHANJING)?.score,
        [DigitalHumanModelVersionEnum.SHANJIAN]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN)?.score,
        [DigitalHumanModelVersionEnum.MINIMAX_HD]: userStore.getTokenByScene(TokensSceneEnum.HUMAN_VOICE_MINIMAX_HD)
            ?.score,
        [DigitalHumanModelVersionEnum.MINIMAX_TURBO]: userStore.getTokenByScene(
            TokensSceneEnum.HUMAN_VOICE_MINIMAX_TURBO,
        )?.score,
    };
    console.log("tokenMap", tokenMap[formData.model_version]);
    return parseFloat(tokenMap[formData.model_version]);
});

// 表单数据
const formData = reactive<{
    url: string;
    name: string;
    model_version: DigitalHumanModelVersionEnum;
    file_id: string;
}>({
    url: "",
    name: "",
    model_version: DigitalHumanModelVersionEnum.SHANJIAN,
    file_id: "",
});

const fileName = ref("");

// 上传音频格式
const getUploadAudioFormat = () => {
    return formData.model_version == DigitalHumanModelVersionEnum.SHANJIAN ? ["mp3", "wav"] : ["mp3", "wav", "m4a"];
};

// 弹窗控制
const showChooseModel = ref(false);
const showRecorder = ref(false);

// 声纹授权协议
const showVoiceprintAgreement = ref(false);
const isAgreedVoiceprint = ref(false);

const agreeVoiceprint = () => {
    isAgreedVoiceprint.value = true;
    showVoiceprintAgreement.value = false;
    startVoiceCloning();
};

// 录音相关
const currCopywriterIndex = ref(0);
const recordDurationTimer = ref<any>(null);
const recordDuration = ref<number>(0);
const isCancel = ref(false);

// 音频播放 hook
const { setUrl, isPlaying, play, pause, destroy } = useAudio({
    onError: (error) => {
        if (error.type == "error" && error.errMsg.includes("Unable to decode audio data")) {
            uni.$u.toast("音频资源异常，建议请重新上传");
        }
    },
});

// 录音 hook
const maxDuration = 120;
const minDuration = 10;
const { authorize, isRecording, start, stop, close } = useRecorder(
    {
        onstart: () => {
            startCountTime();
        },
        onstop: async (result: any) => {
            if (isCancel.value) return;
            const { tempFilePath, fileSize } = result;
            if (recordDuration.value < minDuration) return;
            if (!validateAudioSize(fileSize)) return;
            clearInterval(recordDurationTimer.value);
            await uploadAudio(tempFilePath);
            setUrl(tempFilePath);
        },
        onerror: () => {
            resetRecordDuration();
        },
    },
    {
        duration: 1000 * maxDuration,
    },
);

// 组件引用
const rechargePopupRef = ref();

// 选择模型
const handleModelSelection = (modelVersion: DigitalHumanModelVersionEnum) => {
    formData.model_version = modelVersion;
    // 切换模型时清空已上传的音频，避免 file_id 与模型不匹配
    handleDeleteAudio();
};

// 打开录音弹窗
const openRecorder = () => {
    formData.url = "";
    fileName.value = "";
    showRecorder.value = true;
    isCancel.value = false;
    pause();
    stop();
    destroy();
};

// 微信上传音频
const uploadFromWeChat = async () => {
    await handleUploadAudio();
    if (formData.url) {
        setUrl(formData.url);
    }
};

// 删除已上传音频
const handleDeleteAudio = () => {
    formData.url = "";
    formData.file_id = "";
    fileName.value = "";
    pause();
    destroy();
    resetAudio();
};

// 验证音频大小
const maxFileSize = 10; // MB
const validateAudioSize = (size: number): boolean => {
    if (size > maxFileSize * 1024 * 1024) {
        uni.$u.toast(`音频文件大小不能超过${maxFileSize}M`);
        return false;
    }
    return true;
};

// 选择本地文件
const handleUploadAudio = async () => {
    try {
        const filesResult = await chooseFile({
            type: "file",
            extension: getUploadAudioFormat(),
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

    if (!fileType || !getUploadAudioFormat().includes(fileType)) {
        uni.$u.toast(`请上传${getUploadAudioFormat().join("、")}格式的音频文件`);
        return;
    }
    if (!validateAudioSize(size)) return;

    await uploadAudio(tempFiles[0].path);
};

// 上传音频到服务器，MINIMAX 模型额外调用 minimaxAudioUpload 获取 file_id
const uploadAudio = async (filePath: string) => {
    uni.showLoading({ title: "上传中", mask: true });
    try {
        const { uri, name }: any = await uploadFile("audio", { filePath });

        if (
            [DigitalHumanModelVersionEnum.MINIMAX_HD, DigitalHumanModelVersionEnum.MINIMAX_TURBO].includes(
                formData.model_version,
            )
        ) {
            try {
                const { file_id } = await minimaxAudioUpload({ audio_url: uri });
                formData.file_id = file_id;
            } catch (error) {
                handleDeleteAudio();
                uni.$u.toast(error || "上传失败");
                return;
            }
        }

        formData.url = uri;
        fileName.value = name;
        showRecorder.value = false;
    } catch (error: any) {
        uni.$u.toast(error || "上传失败");
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

// 录音控制
const startRecording = async () => {
    await authorize();
    if (!isRecording.value) start();
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
    close();
    resetRecordDuration();
};

// 音频播放控制
const toggleAudioPlayback = async () => {
    if (isPlaying.value) {
        pause();
    } else {
        play(formData.url);
    }
};

// 重置录音状态
const resetAudio = () => {
    isRecording.value = false;
    showRecorder.value = false;
    isCancel.value = false;
    stop();
    destroy();
    resetRecordDuration();
    close();
};

// 随机文案
const generateRandomCopywriter = () => {
    currCopywriterIndex.value = Math.floor(Math.random() * cratedVoiceCopywriter.length);
};

// 开始克隆（对齐 add-pop.vue 的多模型分发逻辑）
const startVoiceCloning = async () => {
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
    if (!isAgreedVoiceprint.value) {
        showVoiceprintAgreement.value = true;
        return;
    }
    if (userTokens.value < tokensRequired.value) {
        powerInsufficientTip();
        rechargePopupRef.value?.open();
        return;
    }

    uni.showLoading({ title: "克隆中", mask: true });
    try {
        const currModel = appStore.getAiModelConfig.humanModels?.find(
            (item: any) => item.model_version == formData.model_version,
        );
        const callMinimax = () =>
            minimaxVoiceClone({
                name: formData.name,
                model: currModel?.alias,
                file_id: formData.file_id,
                text: cratedVoiceCopywriter[currCopywriterIndex.value],
            });
        const apis: Partial<Record<DigitalHumanModelVersionEnum, () => Promise<any>>> = {
            [DigitalHumanModelVersionEnum.SHANJIAN]: () =>
                shanjianVoiceClone({
                    name: formData.name,
                    audio_url: formData.url,
                }),
            [DigitalHumanModelVersionEnum.CHANJING]: () => voiceClone(formData),
            [DigitalHumanModelVersionEnum.MINIMAX_HD]: () => callMinimax(),
            [DigitalHumanModelVersionEnum.MINIMAX_TURBO]: () => callMinimax(),
        };

        const apiFn = apis[formData.model_version];
        if (!apiFn) {
            uni.$u.toast("不支持的模型版本");
            return;
        }

        await apiFn();
        userStore.getUser();
        uni.hideLoading();
        uni.showToast({ title: "克隆成功，请在我的音色中查看", icon: "none", duration: 3000 });
        setTimeout(() => navigateToHome(), 1500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "克隆失败", icon: "none", duration: 3000 });
    }
};

// 导航到克隆管理页
const navigateToHome = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/clone_manage/clone_manage",
        type: "redirect",
    });
};

watch(
    () => appStore.getDigitalHumanConfig.channel,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            formData.model_version =
                newVal.find((item: any) => item.model_version == DigitalHumanModelVersionEnum.SHANJIAN)
                    ?.model_version ?? newVal[0].model_version;
        }
    },
    { immediate: true },
);

onLoad((options: any) => {
    if (options.model_version) {
        formData.model_version = parseInt(options.model_version) as DigitalHumanModelVersionEnum;
    }
});
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
