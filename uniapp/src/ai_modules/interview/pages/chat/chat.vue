<template>
    <view class="h-screen flex flex-col bg-[#F9F9F9]" v-if="detail">
        <u-navbar :title="detail?.name" title-bold :border-bottom="false" :custom-back="handleCustomBack"></u-navbar>
        <view class="grow min-h-0 flex flex-col">
            <view
                class="px-[40rpx] bg-white h-[120rpx] flex items-center justify-between flex-shrink-0 gap-2 shadow-md">
                <view>
                    <view class="text-[#101828]">{{ detail.company }}</view>
                    <view class="flex items-center gap-2 mt-1">
                        <text
                            class="w-[10rpx] h-[10rpx] rounded-full"
                            :style="{
                                backgroundColor:
                                    interviewStatus === 'interviewing'
                                        ? '#4EC133'
                                        : interviewStatus === 'finished'
                                        ? '#F54747'
                                        : '',
                            }"></text>
                        <text class="text-[#524B6B] text-xs">AIHR</text>
                    </view>
                </view>
                <view v-if="interviewStatus === 'interviewing'" class="flex items-center gap-x-2">
                    <button class="reset-btn action-btn" @click="handleResetInterview">重新开始</button>
                </view>
            </view>
            <view class="grow min-h-0 pt-4">
                <chatting :chat-type="interviewType" :content-list="contentList" ref="chattingRef" />
            </view>
        </view>
        <view class="pb-6 pt-2 px-[40rpx] shadow-[0_-1px_20rpx_0_rgba(0,0,0,0.05)]">
            <view class="flex items-end gap-2" v-if="interviewStatus === 'interviewing'">
                <template v-if="interviewType === 'text'">
                    <view class="grow mr-6">
                        <textarea
                            class="!w-full max-h-[300rpx] overflow-auto text-xs bg-[#FFFFFF] py-[25rpx] px-[24rpx] rounded-[30rpx] shadow-lg"
                            ref="textareaRef"
                            v-model="userInput"
                            placeholder="在此输入您的回答"
                            :auto-height="true"
                            :adjust-position="false"
                            :show-confirm-bar="false"
                            :disable-default-padding="true"
                            confirm-type="done"
                            maxlength="-1"
                            placeholder-style="color:#AAA6B9;font-size:24rpx"
                            hold-keyboard></textarea>
                    </view>
                    <view class="rounded-[30rpx] shadow-lg">
                        <button class="send-btn" :disabled="!userInput || isReceiving" @click="sendText">
                            <image src="@/ai_modules/interview/static/icons/send.svg" class="w-[32rpx] h-[32rpx]" />
                        </button>
                    </view>
                </template>
                <template v-else>
                    <view
                        class="bg-white w-full rounded-[32rpx] h-[96rpx] flex gap-2 items-center justify-center"
                        style="box-shadow: 0rpx 8rpx 124rpx #99abc62d"
                        @click="openRecorder">
                        <image src="@/ai_modules/interview/static/icons/mic.svg" class="w-[40rpx] h-[40rpx]"></image>
                        <text class="text-primary font-bold text-xl">点击说话</text>
                    </view>
                </template>
            </view>
            <view v-if="interviewStatus === 'finished'">
                <view class="flex gap-2">
                    <image
                        src="@/ai_modules/interview/static/icons/tip.svg"
                        class="w-[48rpx] h-[48rpx] flex-shrink-0"></image>
                    <view class="text-[#150B3D]">
                        本轮面试已结束，请提交面试内容并等待面试官的回复，如您有任何意见或改进建议，欢迎向我们提出。
                    </view>
                </view>
                <view class="w-full rounded-[16rpx] bg-white p-[40rpx] mt-4">
                    <textarea
                        v-model="contentFeedback"
                        class="!h-[336rpx]"
                        placeholder="请在此输入您的建议，诚挚的邀请您对我们进行评价。"
                        placeholder-style="color:#AAA6B9;font-size:24rpx"></textarea>
                </view>
                <view class="mt-4">
                    <button class="content-submit-btn" :disabled="!contentFeedback" @click="handleContentSubmit">
                        确认提交
                    </button>
                </view>
            </view>
        </view>
        <view class="flex-shrink-0" :style="{ height: dynamicHeight + 'px' }"></view>
    </view>
    <u-popup
        v-model="showResetInterview"
        mode="center"
        width="90%"
        :border-radius="16"
        @close="showResetInterview = false">
        <view class="p-4 rounded-lg">
            <view class="text-center">
                <image src="@/ai_modules/interview/static/icons/warning.svg" class="w-[96rpx] h-[96rpx]"></image>
            </view>
            <view class="text-[#000000A5] text-base mt-2"
                >您发起了一次重新面试的请求，请在输入框填写本次重新面试的原因。注意：本次面试的对话依然会被记录。</view
            >
            <view class="mt-4 border border-[#D4D4D4] border-solid rounded-[16rpx] p-3">
                <textarea
                    v-model="resetInterviewReason"
                    class="!h-[336rpx]"
                    placeholder="请在此输入您的建议，诚挚的邀请您对我们进行评价。"
                    placeholder-style="color:#AAA6B9;font-size:24rpx"></textarea>
            </view>
            <view class="mt-4 flex gap-2">
                <button
                    class="bg-[#EDEDED] h-[86rpx] flex-1 flex items-center justify-center text-base rounded-[32rpx]"
                    @click="showResetInterview = false">
                    取消
                </button>
                <button
                    class="bg-primary h-[86rpx] flex-1 flex items-center justify-center text-base text-white rounded-[32rpx]"
                    @click="handleResetInterviewConfirm">
                    确认
                </button>
            </view>
        </view>
    </u-popup>

    <Recorder :show="showRecorder" ref="recorderRef" @close="showRecorder = false" @success="recorderSuccess" />
</template>

<script setup lang="ts">
import {
    getInterviewJobDetail,
    getInterviewChatRecord,
    startInterviewChat,
    continueInterviewChat,
    interviewFeedback,
    interviewAgain,
    interviewCheck,
} from "@/api/interview";
import Chatting from "../../components/chatting/chatting.vue";
import { getRect } from "@/utils/util";
import { useLockFn } from "@/hooks/useLockFn";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import Recorder from "../../components/recorder/recorder.vue";

const state = reactive<Record<string, any>>({
    id: "",
    type: "",
    record_id: "",
});

const chattingRef = shallowRef<InstanceType<typeof Chatting>>();
const recorderRef = shallowRef<InstanceType<typeof Recorder>>();

const detail = ref<any>(null);

// 面试类型 音频和文字
const interviewType = ref<"audio" | "text">();

const isAudio = computed(() => interviewType.value === "audio");

const userInput = ref<string>("");
// 发送状态
const isSending = ref<boolean>(false);

// 面试id
const interviewId = ref<string>("");

// 面试状态
const interviewStatus = ref<"interviewing" | "finished">("interviewing");

// 重新开始弹窗
const showResetInterview = ref<boolean>(false);

// 重新开始原因
const resetInterviewReason = ref<string>("");

// 重新开始
const handleResetInterview = async () => {
    const { type, msg } = await interviewCheck({
        job_id: state.id,
    });
    if ([0, 7, 9].includes(type)) {
        uni.$u.toast(msg);
        return;
    } else if (isReceiving.value) {
        uni.$u.toast("目前正在对话中，请稍后再试");
        return;
    } else if (contentList.value.length <= 1) {
        uni.$u.toast("请先进行对话");
        return;
    }
    showResetInterview.value = true;
};

// 重新开始确认
const handleResetInterviewConfirm = async () => {
    if (!resetInterviewReason.value) {
        uni.$u.toast("请输入重新面试的原因");
        return;
    }
    uni.showLoading({
        title: "重新面试中...",
        mask: true,
    });
    try {
        await interviewAgainFn(3, resetInterviewReason.value);
        state.record_id = "";
        uni.hideLoading();
        resetParams();
        startChat();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "重新面试失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const resetParams = () => {
    contentList.value = [];
    interviewStatus.value = "interviewing";
    resetInterviewReason.value = "";
    showResetInterview.value = false;
    chattingRef.value?.pauseAll();
};

const interviewAgainFn = async (type: number, reason: string) => {
    await interviewAgain({
        interview_id: interviewId.value || state.record_id,
        reason,
        type,
    });
};

const contentFeedback = ref<string>("");
const handleContentSubmit = async () => {
    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        await interviewFeedback({
            job_id: state.id,
            content: contentFeedback.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "反馈已提交，请等待面试官的回复",
            icon: "success",
            duration: 3000,
        });
        toIndex();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const toIndex = () => {
    uni.$u.route({
        url: "/ai_modules/interview/pages/index/index",
        params: {
            job_id: state.id,
            user_id: detail?.value?.user_id,
        },
        type: "reLaunch",
    });
};

const handleCustomBack = () => {
    if (interviewStatus.value === "interviewing") {
        uni.showModal({
            title: "提示",
            content: "当前面试还未结束，确定要离开吗？",
            success: async ({ confirm }) => {
                if (confirm) {
                    uni.showLoading({
                        title: "退出中...",
                        mask: true,
                    });
                    try {
                        await interviewAgainFn(3, "用户手动退出");
                        toIndex();
                    } catch (error: any) {
                        uni.showToast({
                            title: error || "退出失败",
                            icon: "none",
                        });
                    } finally {
                        uni.hideLoading();
                    }
                }
            },
        });
        return;
    }
    toIndex();
};

// 录音弹框显示
const showRecorder = ref<boolean>(false);
const contentRef = shallowRef();
const contentList = ref<any[]>([]);
const isReceiving = ref(false);
const keyboardHeight = ref<number>(0);

const textareaRef = shallowRef();
const inputBlur = () => {
    keyboardHeight.value = 0;
    textareaRef.value?.blur();
    uni.hideKeyboard();
};

const sendText = async () => {
    if (isSending.value) return;
    contentPost({
        message: userInput.value,
    });
    inputBlur();
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    contentPost(res);
};

const openRecorder = async () => {
    if (isReceiving.value) {
        uni.$u.toast("目前正在对话中，请稍后再试");
        return;
    }
    await recorderRef.value?.authorize();
    chattingRef.value?.pauseAll();
    showRecorder.value = true;
};

const createResult = (logo: string) =>
    reactive({
        type: 2,
        loading: true,
        reply: "",
        link: "",
        auto: false,
        logo,
        duration: 0,
    });

const setResult = (result: any, duration: number) => {
    result.duration = Number(duration.toFixed(0));
    result.loading = false;
    result.reply = result.reply;
    result.auto = isAudio.value;
    isReceiving.value = false;
    setTimeout(() => {
        chattingRef.value?.scrollToBottom();
    }, 300);
};

// 开始对话
const startChat = async () => {
    const result = createResult(detail.value.avatar);
    contentList.value.push(result);
    try {
        isReceiving.value = true;
        const { prologue, audio_duration, audio_url, id } = await startInterviewChat({
            job_id: state.id,
        });
        interviewId.value = id;
        result.reply = prologue || "";
        result.link = audio_url || "";
        setResult(result, audio_duration || 0);
    } catch (error: any) {
        result.loading = false;
        result.reply = error || "发生错误";
        isReceiving.value = false;
    }
};

const contentPost = async (data: any) => {
    if (isReceiving.value) return;
    isReceiving.value = true;
    const duration = Math.ceil(data.duration / 1000);
    contentList.value.push({
        type: 1,
        message: data.message,
        duration,
        link: data.link,
    });

    const result = createResult(detail.value.avatar);
    contentList.value.push(result);
    userInput.value = "";
    try {
        chattingRef.value?.scrollToBottom();
        const { audio_url, message, status } = await continueInterviewChat({
            id: interviewId.value,
            answer: data.message,
            answer_url: data.link,
        });
        if (isAudio.value) {
            result.link = audio_url || "";
        }

        result.reply = message || "";
        setResult(result, duration || 0);
        // 面试结束, 唤起反馈弹框
        if (status == 1) {
            interviewStatus.value = "finished";
            showResetInterview.value = status == 1;
        }
    } catch (error: any) {
        result.loading = false;
        result.reply = error || "发生错误";
        isReceiving.value = false;
    }
};

const { dynamicHeight } = useKeyboardHeight();

const getDetail = async () => {
    const data = await getInterviewJobDetail({
        id: state.id,
    });
    detail.value = data;
    interviewType.value = detail.value.type === 1 ? "text" : "audio";
    uni.setNavigationBarTitle({
        title: detail.value.name,
    });
    if (state.type == 3) {
        getChatRecord();
    } else {
        startChat();
    }
};

const getChatRecord = async () => {
    const data = await getInterviewChatRecord({
        interview_id: state.record_id,
    });
    if (data && data.length > 0) {
        data.forEach((item: any) => {
            contentList.value.push(
                {
                    type: 2,
                    reply: item.question,
                    duration: item.question_duration,
                    logo: detail.value.avatar,
                    link: item.question_url,
                },
                {
                    type: 1,
                    duration: item.answer_duration,
                    message: item.answer,
                    link: item.answer_url,
                }
            );
        });
    }
    await nextTick();
    chattingRef.value?.scrollToBottom();
};

onLoad((options: any) => {
    state.id = options.id;
    state.record_id = options.record_id;
    state.type = options.type;
    resetParams();
    getDetail();
});

onUnload(() => {
    interviewAgainFn(3, "意外退出");
});
</script>

<style scoped lang="scss">
.action-btn {
    @apply text-white flex items-center justify-center px-[16rpx] h-[56rpx] rounded-md text-xs;
}
.reset-btn {
    @apply bg-[#F54747];
}
.finish-btn {
    @apply bg-[#df8f2c];
}

.send-btn {
    @apply bg-[#0065FB] flex items-center justify-center w-[80rpx] h-[80rpx] rounded-[30rpx];
    &[disabled] {
        @apply bg-primary-light-5 text-white;
    }
}
.content-submit-btn {
    @apply bg-primary rounded-[32rpx] h-[100rpx] w-full text-white flex items-center justify-center text-base;
    &[disabled] {
        @apply bg-primary-light-5 text-white;
    }
}
// textarea {
// 	height: 22rpx;
// }
</style>
