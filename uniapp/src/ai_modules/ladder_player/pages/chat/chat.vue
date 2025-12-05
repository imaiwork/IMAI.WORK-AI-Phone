<template>
    <view class="chat-page" v-if="detail">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            title="AI陪练"
            title-bold></u-navbar>
        <view class="mx-4">
            <view class="flex items-center justify-between py-4">
                <view>
                    <view class="text-xs">{{ detail.name }}</view>
                    <view class="flex items-center gap-1 mt-[4rpx]">
                        <view class="w-[10rpx] h-[10rpx] bg-[#4EC133] rounded-full"></view>
                        <view class="text-[#524B6B] text-[22rpx]">{{ detail.coach_name }}</view>
                    </view>
                </view>
                <view>
                    <u-button
                        type="error"
                        size="mini"
                        :custom-style="{ fontWeight: 'bold' }"
                        @click="openEndPracticePop"
                        >结束练习</u-button
                    >
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-4 container">
            <view class="h-full">
                <chatting ref="chattingRef" :contentList="contentList" />
            </view>
        </view>
        <view class="mx-3 mt-2">
            <view class="pb-[50rpx] flex items-center gap-3">
                <view class="bg-white w-[96rpx] h-[96rpx] rounded-xl p-2 tips-btn" @click="openTipsPop">
                    <image
                        src="@/ai_modules/ladder_player/static/images/common/beautify_img2.png"
                        class="w-full h-full"></image>
                </view>
                <view
                    class="grow flex justify-center items-center gap-1 bg-white rounded-xl h-[96rpx] recorder-btn"
                    @click="openRecorder">
                    <image
                        src="@/ai_modules/ladder_player/static/icons/mic_fill.svg"
                        class="w-[40rpx] h-[40rpx]"></image>
                    <text class="text-primary text-xl font-bold">点击说话</text>
                </view>
            </view>
        </view>
    </view>
    <view v-if="showTipsPop" class="fixed left-0 bottom-[170rpx] w-full">
        <view class="h-full flex flex-col mx-4 tips-pop">
            <view class="flex items-center justify-between gap-2">
                <view class="flex items-center gap-1 rounded-full bg-white py-1 px-2">
                    <image
                        src="@/ai_modules/ladder_player/static/images/common/beautify_img2.png"
                        class="w-[36rpx] h-[36rpx]"></image>
                    <text class="text-[#508DC5] text-xs font-bold">话术提示</text>
                </view>
                <view class="text-white text-xs"> 尝试自己组织语言得分更高～ </view>
            </view>
            <view class="bg-white mt-2 rounded-[32rpx] p-2">
                <scroll-view class="h-[350rpx]" scroll-y>
                    <view class="p-1 text-[#524B6B] leading-[44rpx] whitespace-pre-line" v-if="!tipsLoading">
                        {{ tips }}
                    </view>
                    <view class="chat-loader p-2" v-else></view>
                </scroll-view>
            </view>
        </view>
    </view>
    <u-popup v-model="showEndPracticePop" mode="bottom" border-radius="40" height="25%">
        <view class="h-full flex flex-col">
            <view class="flex items-center justify-center gap-2 h-[112rpx] relative">
                <u-icon name="info-circle" color="#FE975F" :size="32"></u-icon>
                <text class="text-[#2C2C36] text-xl font-bold">确定结束练习吗？</text>
                <view class="absolute right-4" @click="showEndPracticePop = false">
                    <u-icon name="close" color="#2C2C36" :size="32"></u-icon>
                </view>
            </view>
            <view>
                <u-line />
            </view>
            <view class="mt-4 px-4 grow text-[#4C4B6A]"> 确认结束后，请在练习报告页面查看练习结果。 </view>
            <view class="flex justify-between gap-4 px-[56rpx] pb-[56rpx]">
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] rounded-full shadow-[0_0_0_2rpx_rgba(232,234,242,1)]"
                    @click="againPractice">
                    重新再练一次
                </view>
                <view
                    class="flex-1 flex items-center justify-center h-[88rpx] text-white rounded-full bg-[#EB2F2F]"
                    @click="endPractice">
                    结束对话
                </view>
            </view>
        </view>
    </u-popup>
    <Recorder :show="showRecorder" ref="recorderRef" @close="showRecorder = false" @success="recorderSuccess" />
</template>

<script setup lang="ts">
import {
    lpSceneDetail,
    lpSceneChatContinue,
    lpSceneChatEnd,
    lpSceneChatStart,
    lpRecordLists,
    lpSceneChatTips,
    lpScenePerformance,
} from "@/api/ladder_player";
import Chatting from "../../components/chatting/chatting.vue";
import Recorder from "../../components/recorder/recorder.vue";
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();

const getCurrVoice = computed(() => {
    const data = appStore.getLadderConfig?.voice || [];
    return data.find((item: any) => item.code == detail.value?.coach_voice) || {};
});

const state = reactive({
    scene_id: "",
    analysis_id: "",
});

const showEndPracticePop = ref(false);

const chattingRef = ref<InstanceType<typeof Chatting>>();
const detail = ref<any>(null);
const contentList = ref<any[]>([]);
const isReceiving = ref(false);

const showRecorder = ref(false);
const recorderRef = shallowRef();
const showTipsPop = ref(false);
const tips = ref<any>(null);
const tipsLoading = ref(false);

const getRecordList = async () => {
    const { lists } = await lpRecordLists({
        analysis_id: state.analysis_id,
        page_size: 9999,
    });
    const transformedLists = lists
        .map((item: any) => {
            if (item.preliminary_ask) {
                return {
                    type: 2,
                    reply: item.preliminary_ask,
                    duration: item.preliminary_ask_audio_duration,
                    logo: getCurrVoice.value.logo,
                    link: item.preliminary_ask_audio,
                };
            } else {
                return [
                    {
                        type: 1,
                        duration: item.ask_audio_duration,
                        message: item.ask,
                        link: item.ask_audio,
                    },
                    {
                        type: 2,
                        reply: item.reply,
                        duration: item.reply_audio_duration,
                        logo: getCurrVoice.value.logo,
                        link: item.reply_audio,
                    },
                ];
            }
        })
        .flat();
    contentList.value = transformedLists;
    chattingRef.value?.scrollToBottom();
};

const createResult = (logo: string) =>
    reactive({
        id: "",
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
    result.auto = true;
    isReceiving.value = false;
    setTimeout(() => {
        chattingRef.value?.scrollToBottom();
    }, 300);
};

const startChat = async () => {
    const result = createResult(getCurrVoice.value?.logo || "");
    contentList.value.push(result);
    try {
        isReceiving.value = true;
        const data = await lpSceneChatStart({
            scene_id: state.scene_id,
        });
        state.analysis_id = data?.analysis_id || "";
        result.id = data?.id || "";
        result.link = data?.preliminary_ask_audio || "";
        result.reply = data?.preliminary_ask || "";
        setResult(result, data?.preliminary_ask_audio_duration || 0);
        getTips(data.id);
    } catch (error: any) {
        result.loading = false;
        result.reply = "发生错误";
        isReceiving.value = false;
    }
};

// 结束对话
const endChat = async () => {
    await lpSceneChatEnd({
        scene_id: state.scene_id,
        analysis_id: state.analysis_id,
    });
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

    const result = createResult(getCurrVoice.value?.logo || "");
    contentList.value.push(result);
    try {
        chattingRef.value?.scrollToBottom();
        const res = await lpSceneChatContinue({
            scene_id: state.scene_id,
            analysis_id: state.analysis_id,
            ask: data.message,
            ask_audio: data.link,
            ask_audio_duration: duration,
        });
        result.id = res?.id || "";
        result.link = res?.reply_audio || "";
        result.reply = res?.reply || "";
        setResult(result, res.reply_audio_duration || 0);
        getTips(res.id);
        getPerformance(res.id);
    } catch (error: any) {
        result.loading = false;
        result.reply = error || "发生错误";
        isReceiving.value = false;
    }
};

const getPerformance = async (id: string) => {
    await lpScenePerformance({
        scene_id: state.scene_id,
        analysis_id: state.analysis_id,
        chat_id: id,
    });
};

const openTipsPop = () => {
    if (isReceiving.value) {
        chattingBeginToast();
        return;
    }
    showTipsPop.value = !showTipsPop.value;
};

// 话术提示
const getTips = async (id: string) => {
    tipsLoading.value = true;
    try {
        const result = await lpSceneChatTips({
            scene_id: state.scene_id,
            analysis_id: state.analysis_id,
            chat_id: id,
        });
        tips.value = result.speechcraft;
    } finally {
        tipsLoading.value = false;
    }
};

const openRecorder = async () => {
    showTipsPop.value = false;
    if (isReceiving.value) {
        chattingBeginToast();
        return;
    }
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    chattingRef.value?.pauseAll();
    showRecorder.value = true;
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    contentPost(res);
};

const openEndPracticePop = () => {
    if (isReceiving.value) {
        chattingBeginToast();
        return;
    }
    showEndPracticePop.value = true;
};

const endPractice = async () => {
    uni.showLoading({
        title: "正在结束对话中...",
        mask: true,
    });
    try {
        showEndPracticePop.value = false;
        await endChat();
        uni.hideLoading();
        uni.showToast({
            icon: "none",
            title: "提交成功，3秒跳转到报告页面~",
            duration: 3000,
        });
        setTimeout(() => {
            uni.$u.route({
                url: "/ai_modules/ladder_player/pages/report/report",
                type: "redirect",
            });
        }, 3000);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "结束对话失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const againPractice = async () => {
    showEndPracticePop.value = false;
    contentList.value = [];
    uni.showLoading({
        title: "正在重新练习中...",
        mask: true,
    });
    try {
        await startChat();
    } catch (error: any) {
        uni.showToast({
            title: error || "重新练习失败",
            icon: "none",
            duration: 2000,
        });
    } finally {
        uni.hideLoading();
    }
};

// 统一提示“正在对话中”提取函数
const chattingBeginToast = () => {
    uni.$u.toast("当前还有对话中，请稍等");
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const data = await lpSceneDetail({
            id: state.scene_id,
        });
        detail.value = data || {};
        if (!state.analysis_id) {
            startChat();
        } else {
            getRecordList();
        }
    } catch (error: any) {
        uni.showToast({
            title: error || "获取场景详情失败",
            icon: "none",
            duration: 2000,
        });
    } finally {
        uni.hideLoading();
    }
};

onLoad((options: any) => {
    state.scene_id = options.id;
    if (options.analysis_id) {
        state.analysis_id = options.analysis_id;
    }
    getDetail();
});
</script>

<style scoped lang="scss">
.chat-page {
    background: linear-gradient(180deg, #dfe7fc 0%, #f9f9f9 18%);

    @apply h-screen flex flex-col;
}
.tips-btn,
.recorder-btn {
    box-shadow: 0rpx 2rpx 8rpx rgba(0, 0, 0, 0.06);
}
.tips-pop {
    background: linear-gradient(
        117.59deg,
        rgba(95, 144, 217, 1) 0%,
        rgba(148, 198, 255, 1) 38.9%,
        rgba(110, 176, 248, 1) 74.98%,
        rgba(113, 165, 248, 1) 100%
    );
    @apply rounded-[32rpx] p-2;
}
</style>
