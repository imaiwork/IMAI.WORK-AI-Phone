<template>
    <u-popup v-model="showModel" @close="closePop" mode="bottom" :mask="false" closeable height="100%">
        <view class="h-full bg-white flex flex-col rounded-tl-2xl rounded-tr-2xl">
            <view class="pt-8">
                <view class="items-center flex justify-center flex-col">
                    <!-- <canvas type="2d" class="recwave-Histogram2 h-24 w-[80%]"></canvas> -->
                    <image
                        src="@/ai_modules/voice_chat/static/icons/voice_loading.svg"
                        class="h-16 leading-[0] w-full"></image>
                    <view class="text-white bg-[#F09E38] rounded-full font-bold text-xs mt-2 px-2 py-1">{{
                        formatAudioTime(recordDuration)
                    }}</view>
                </view>
            </view>
            <view class="flex items-center px-4 gap-4 mt-5">
                <button
                    class="basis-1/3 rounded-full border border-solid border-[#10A37F33] bg-[#10A37F33] text-voice h-8 text-xl flex items-center justify-center"
                    @click="reply()">
                    重录
                </button>
                <button
                    class="basis-2/3 rounded-full bg-voice text-white h-8 text-xl flex items-center justify-center"
                    @click="confirm()">
                    确认发送
                </button>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";
import { useRecorder } from "@/hooks/useRecorder";

const props = withDefaults(
    defineProps<{
        show: boolean;
    }>(),
    {
        show: false,
    }
);

const emit = defineEmits<{
    (event: "update:show", show: boolean): void;
    (event: "success", data: any): void;
    (event: "close"): void;
}>();

const showModel = computed({
    get() {
        return props.show;
    },
    set(value) {
        emit("update:show", value);
    },
});

const recordDurationTimer = ref<any>(null);
const recordDuration = ref<number>(0);
const recordMsg = ref<string>("");
const { start, stop, close, authorize } = useRecorder(
    {
        onstart() {
            startCountTime();
        },
        onstop(e) {
            close();
            clearInterval(recordDurationTimer.value);
        },
    },
    {
        duration: 5 * 60 * 1000,
    }
);
const isError = ref(false);

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

const reply = async () => {
    if (isError.value) return;
    const result = await uni.showModal({
        content: "确认重新录制么？",
        cancelText: "稍后重录",
        confirmText: "立即重录",
    });
    if (result.cancel) return;
    resetRecordDuration();
    stop();
    uni.showLoading({
        mask: true,
        title: "正在重新录制中",
    });
    setTimeout(() => {
        recordMsg.value = "";
        start();
        uni.hideLoading();
    }, 1500);
};

const isConfirm = ref(false);
const confirm = async () => {
    if (recordDuration.value < 1) {
        uni.$u.toast("说话时间太短");
        return;
    }
    isConfirm.value = true;
    stop();
};

const closePop = () => {
    showModel.value = false;
    isConfirm.value = false;
    recordMsg.value = "";
    emit("close");
    stop();
    resetRecordDuration();
};

watch(showModel, (newVal) => {
    if (newVal) {
        start();
    } else {
        stop();
    }
});

defineExpose({
    authorize,
});
</script>

<style scoped></style>
