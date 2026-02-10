<template>
    <div class="real-time-page">
        <ElTooltip content="返回">
            <div class="absolute top-6 left-8 z-30">
                <div
                    @click="back"
                    class="w-10 h-10 rounded-full bg-[#FFFFFF] border border-[#E2E8F0] flex items-center justify-center cursor-pointer shadow-sm hover:border-primary transition-all group">
                    <Icon name="el-icon-Back" :size="18" color="#64748B" />
                </div>
            </div>
        </ElTooltip>
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="w-[880px] mx-auto pb-20">
                    <div class="text-center">
                        <h1 class="text-[36px] font-[900] text-[#0F172A] tracking-tight">会议实时记录</h1>
                        <p class="mt-2 text-[#64748B] font-medium">语音即刻转文字 · 多语种翻译 · 智能区分发言人</p>
                    </div>

                    <div
                        ref="recorderContainerRef"
                        class="mt-10 bg-[#FFFFFF] rounded-[32px] p-12 shadow-[0_20px_50px_rgba(0,0,0,0.08)] border border-[#F1F5F9]">
                        <template v-if="nextStep == 1">
                            <div class="space-y-10">
                                <div class="grid gap-8">
                                    <div class="flex items-start">
                                        <div
                                            class="w-[140px] pt-2 text-[#64748B] font-black text-sm uppercase tracking-wider">
                                            音频语种
                                        </div>
                                        <div class="flex-1 flex flex-wrap gap-3">
                                            <div
                                                v-for="item in languageList"
                                                :key="item.code"
                                                class="px-6 py-2 rounded-xl border-2 transition-all cursor-pointer font-medium text-sm"
                                                :class="
                                                    formData.language === item.code
                                                        ? 'bg-primary border-primary text-white shadow-light shadow-[#0065fb]/30'
                                                        : 'bg-[#F9FAFB] border-[transparent] text-[#64748B] hover:bg-[#FFFFFF] hover:border-[#E2E8F0]'
                                                "
                                                @click="formData.language = item.code">
                                                {{ item.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <div
                                            class="w-[140px] text-[#64748B] font-black text-sm uppercase tracking-wider">
                                            翻译目标
                                        </div>
                                        <div class="flex-1 max-w-[320px]">
                                            <ElSelect
                                                v-model="formData.translation"
                                                class="custom-select"
                                                placeholder="不开启翻译"
                                                :show-arrow="false">
                                                <ElOption
                                                    v-for="item in targetLanguageList"
                                                    :key="item.code"
                                                    :label="item.name"
                                                    :value="item.code" />
                                            </ElSelect>
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <div
                                            class="w-[140px] text-[#64748B] font-black text-sm uppercase tracking-wider">
                                            区分发言人
                                        </div>
                                        <div class="flex-1 flex gap-4">
                                            <div
                                                v-for="item in speakerOptions"
                                                :key="item.value"
                                                class="px-8 py-2 rounded-xl border-2 transition-all cursor-pointer font-medium text-sm"
                                                :class="
                                                    formData.speaker === item.value
                                                        ? 'bg-[#F1F0FF] border-primary text-primary'
                                                        : 'bg-[#F9FAFB] border-[transparent] text-[#64748B] hover:bg-[#FFFFFF] hover:border-[#E2E8F0]'
                                                "
                                                @click="formData.speaker = item.value">
                                                {{ item.label }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6 flex flex-col items-center gap-6">
                                    <ElTooltip placement="top">
                                        <template #content>
                                            <span class="font-medium"
                                                >{{ tokensValue.score }} {{ tokensValue.unit }} / 分钟</span
                                            >
                                        </template>
                                        <button
                                            class="h-16 w-[240px] bg-primary rounded-full text-white font-black text-lg flex items-center justify-center gap-3 hover:scale-105 transition-transform active:scale-95 shadow-light shadow-[#0065fb]/40"
                                            @click="handleStartRecord">
                                            <div
                                                class="w-8 h-8 rounded-full bg-[#ffffff33] flex items-center justify-center">
                                                <Icon name="el-icon-Mic" color="#FFFFFF" :size="18" />
                                            </div>
                                            <span>开始实时录制</span>
                                        </button>
                                    </ElTooltip>

                                    <div
                                        class="flex items-center gap-2 px-4 py-2 bg-slate-50 rounded-lg border border-[#F1F5F9]">
                                        <Icon name="el-icon-InfoFilled" color="#94A3B8" :size="14" />
                                        <span class="text-xs text-[#64748B] font-medium">
                                            当前算力预计可录制
                                            <span class="text-primary">{{ userRecordTimeLimit }}</span> 分钟
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-if="nextStep == 2" class="bg-slate-50 p-8 rounded-2xl border border-[#F1F5F9]">
                            <RecorderControl
                                ref="recorderControlRef"
                                :disabled="isRecorderDisabled"
                                :is-error="isCreateError"
                                @change="getAudio" />

                            <div class="mt-10 flex justify-center gap-4" v-if="isCreateError">
                                <ElButton
                                    class="!h-12 !px-8 !rounded-xl !font-medium"
                                    type="primary"
                                    :loading="isLock"
                                    @click="lockCreateTask"
                                    >重新上传</ElButton
                                >
                                <ElButton
                                    class="!h-12 !px-8 !rounded-xl !bg-[#FEF2F2] !text-[#EF4444] !border-[#FEE2E2] !font-medium"
                                    @click="reloadRecord"
                                    >放弃并重新录音</ElButton
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-20">
                        <div class="flex items-center justify-between mb-6">
                            <div class="text-xl font-[900] text-[#0F172A]">最近录音记录</div>
                            <div
                                class="text-sm font-medium text-primary cursor-pointer hover:underline"
                                @click="router.push('/history')">
                                查看全部
                            </div>
                        </div>

                        <div v-loading="pager.loading">
                            <template v-if="pager.lists.length">
                                <div class="grid grid-cols-3 gap-6">
                                    <div
                                        v-for="(item, index) in pager.lists"
                                        :key="index"
                                        class="bg-[#FFFFFF] border border-[#F1F5F9] rounded-2xl p-5 h-[140px] flex flex-col justify-between group relative hover:shadow-xl hover:border-[#4F46E533] transition-all duration-300">
                                        <div class="flex items-start justify-between">
                                            <div class="flex flex-col flex-1 overflow-hidden">
                                                <span class="font-black text-[#0F172A] truncate text-sm mb-1">{{
                                                    formatName(item.name)
                                                }}</span>
                                                <div class="flex items-center gap-1.5">
                                                    <div
                                                        class="w-1.5 h-1.5 rounded-full"
                                                        :class="
                                                            item.status == TurnStatus.SUCCESS
                                                                ? 'bg-[#10B981]'
                                                                : 'bg-[#F59E0B]'
                                                        "></div>
                                                    <span class="text-[11px] font-medium text-[#64748B]">
                                                        {{
                                                            item.status == TurnStatus.SUCCESS
                                                                ? item.task_type == 1
                                                                    ? "音频导入"
                                                                    : "实时录音"
                                                                : "处理中..."
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center shrink-0">
                                                <Icon
                                                    :name="item.task_type == 1 ? 'el-icon-Headset' : 'el-icon-Mic'"
                                                    color="#94A3B8"
                                                    :size="16" />
                                            </div>
                                        </div>

                                        <div
                                            class="flex justify-between items-center border-t border-[#F8FAFC] pt-3 text-[11px] font-medium text-[#94A3B8]">
                                            <span>{{ dayjs(item.create_time).format("YYYY/MM/DD") }}</span>
                                            <span>{{ dayjs(item.create_time).format("HH:mm") }}</span>
                                        </div>

                                        <div
                                            class="absolute inset-0 bg-[#FFFFFFFA] opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl flex items-center justify-center gap-2">
                                            <ElPopover
                                                placement="top"
                                                :show-arrow="false"
                                                popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                                                <template #reference>
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center hover:bg-[#F1F0FF] transition-colors cursor-pointer">
                                                        <Icon
                                                            name="el-icon-MoreFilled"
                                                            color="var(--color-primary)"
                                                            :size="16" />
                                                    </div>
                                                </template>
                                                <div class="flex flex-col p-1 gap-1">
                                                    <div
                                                        v-if="item.status == TurnStatus.ERROR"
                                                        class="table-action-item"
                                                        @click="handleAgain(item.id)">
                                                        <Icon name="el-icon-Refresh" :size="14" />
                                                        <span>重新尝试</span>
                                                    </div>
                                                    <div
                                                        class="h-[1px] bg-[#F1F5F9] my-1"
                                                        v-if="
                                                            item.status == TurnStatus.ERROR ||
                                                            item.status == TurnStatus.SUCCESS
                                                        "></div>
                                                    <div
                                                        class="table-action-item !text-red-500 hover:!bg-red-50"
                                                        @click="handleDelete(item.id)">
                                                        <Icon
                                                            name="el-icon-Delete"
                                                            :size="14"
                                                            color="var(--color-red)" />
                                                        <span>删除记录</span>
                                                    </div>
                                                </div>
                                            </ElPopover>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div
                                v-else
                                class="py-12 bg-[#FFFFFF] rounded-2xl border border-[#F1F5F9] flex flex-col items-center">
                                <ElEmpty description="暂无历史录音" />
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <KnbBind v-if="showKnbBind" ref="knbBindRef" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import Recorder from "recorder-core";
import { meetingMinutesCreate } from "@/api/meeting_minutes";
import { dayjs } from "element-plus";
import RecorderControl from "../detail/_components/recorder-control.vue";
import { TurnStatus } from "../_enums";
import useHandleApi from "../_hooks/useHandleApi";
import KnbBind from "@/components/knb-bind/index.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();
const {
    pager,
    userTokens,
    tokensValue,
    speakerOptions,
    languageList,
    targetLanguageList,
    getLists,
    handleAgain,
    handleDelete,
    handleTrain,
    formatName,
} = useHandleApi();

// 计算当前用户能录音多长时间
const userRecordTimeLimit = computed(() => {
    return Math.floor(userTokens.value / 3);
});

const formData = reactive<any>({
    language: "cn",
    speaker: 0,
    translation: 0,
    task_type: 1,
});

const recorderContainerRef = ref<HTMLDivElement>();
const recorderControlRef = ref<InstanceType<typeof RecorderControl>>();

const nextStep = ref(1);

const recorder = ref<any>(null);
const isCreateError = ref<boolean>(false);
const handleStartRecord = async () => {
    isCreateError.value = false;
    await nextTick();
    recorderControlRef.value?.resetRecord();
    if (tokensValue.value.score <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    recorder.value = Recorder();
    recorder.value.open(
        () => {
            nextStep.value = 2;
        },
        (msg: string, isUserNotAllow: any) => {
            feedback.msgWarning((isUserNotAllow ? "UserNotAllow，" : "") + "无法录音:" + msg);
        }
    );
};

const reloadRecord = async () => {
    await nuxtApp.$confirm({
        message: "确定要重新录音吗？",
        onConfirm: async () => {
            isCreateError.value = false;
            recorderControlRef.value?.resetRecord();
            recorderControlRef.value?.openRecorder();
        },
    });
};

const isRecorderDisabled = computed(() => {
    return isLock.value || isCreateError.value;
});

// 获取录音回调
const getAudio = async (result: any) => {
    const fileName = `${dayjs().format("YYYY-MM-DD HH:mm:ss")} 记录`;
    const { uri } = result;
    formData.url = uri;
    formData.name = fileName;
    feedback.closeLoading();
    lockCreateTask();
};

const showKnbBind = ref(false);
const knbBindRef = ref<InstanceType<typeof KnbBind>>();
const openKnbBind = async (item: any) => {
    handleTrain(item, async (result: any) => {
        showKnbBind.value = true;
        await nextTick();
        knbBindRef.value?.open();
        knbBindRef.value?.setFormData(result);
    });
};

const createTask = async () => {
    feedback.loading("创建中...", recorderContainerRef.value);
    try {
        await meetingMinutesCreate({
            ...formData,
            translation: formData.translation == 0 ? "" : formData.translation,
        });
        feedback.msgSuccess("创建成功,即将返回列表");

        setTimeout(() => {
            router.back();
        }, 1000);
    } catch (error) {
        isCreateError.value = true;
        feedback.msgError(error || "创建失败");
    } finally {
        feedback.closeLoading();
    }
};

const { lockFn: lockCreateTask, isLock } = useLockFn(createTask);

const back = () => {
    if (nextStep.value == 2 && !isCreateError.value) {
        nuxtApp.$confirm({
            message: "确定结束录音吗？结束后无法在本记录继续录音",
            onConfirm: async () => {
                feedback.loading("结束录音中...");
                recorderControlRef.value?.stopRecord();
            },
        });
    } else {
        router.back();
    }
};

getLists();

definePageMeta({
    title: "会议实时记录转写",
    layout: false,
});
</script>

<style scoped lang="scss">
.real-time-page {
    @apply w-full h-full relative pt-[100px] flex flex-col;

    &::after {
        content: "";
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background: url("../_assets/images/home_bg.png");
        background-size: 100% auto;
        background-repeat: no-repeat;
        z-index: -1;
    }
}
</style>
