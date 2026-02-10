<template>
    <ElDrawer v-model="visible" title="面试报告详情" size="800px" body-class="!p-0" @close="handleClose">
        <template #header>
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-[rgba(0,101,251,0.2)]">
                    <Icon name="el-icon-Document" color="#FFFFFF" :size="24" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-[900] text-[#0F172A]">面试评估报告</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs font-medium text-[#94A3B8]">ID: {{ detailData.ai?.id || "-" }}</span>
                        <div class="w-1 h-1 rounded-full bg-[#CBD5E1]"></div>
                        <span class="text-xs font-medium text-primary">{{ detailData.ai?.job_name }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="h-full flex flex-col">
            <div class="grow min-h-0 bg-slate-50">
                <ElScrollbar>
                    <div class="space-y-8 pb-20 px-8">
                        <div class="grid grid-cols-3 gap-6">
                            <div
                                class="col-span-2 bg-white rounded-[32px] p-8 border border-[#F1F5F9] shadow-sm flex items-center justify-between">
                                <div class="flex flex-col gap-2">
                                    <span class="text-sm font-black text-[#94A3B8] uppercase tracking-wider"
                                        >AI 综合评分</span
                                    >
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-[56px] font-[900] text-primary leading-none">{{
                                            detailData.ai.score || 0
                                        }}</span>
                                        <span class="text-lg font-medium text-[#CBD5E1]">/ 100</span>
                                    </div>
                                    <div
                                        class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-[#F0FDF4] text-[#16A34A] rounded-lg text-xs font-medium w-fit">
                                        <Icon name="el-icon-CircleCheckFilled" :size="14" />
                                        <span>符合岗位能力标准</span>
                                    </div>
                                </div>
                                <div class="h-24 w-[1px] bg-[#F1F5F9]"></div>
                                <div class="grid grid-cols-2 gap-x-12 gap-y-4">
                                    <div v-for="info in aiInfoList" :key="info.label" class="flex flex-col">
                                        <span class="text-[11px] font-medium text-[#94A3B8]">{{ info.label }}</span>
                                        <span class="text-base font-black text-[#0F172A]">{{ info.value }}</span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="bg-primary rounded-[32px] p-8 flex flex-col items-center justify-center text-center shadow-xl shadow-[rgba(0,101,251,0.2)]">
                                <div
                                    class="w-16 h-16 rounded-2xl bg-[rgba(255,255,255,0.2)] flex items-center justify-center mb-4">
                                    <Icon name="el-icon-Download" color="#FFFFFF" :size="32" />
                                </div>
                                <span class="text-[#FFFFFF] font-black text-lg">附件下载</span>
                                <p class="text-[rgba(255,255,255,0.7)] text-xs mt-1 mb-4">保存面试者原始简历文件</p>
                                <ElButton
                                    class="!w-full !rounded-xl !border-none !bg-white !text-primary !font-black !h-12 hover:!scale-105 transition-transform"
                                    :loading="isDownloadResumeLock"
                                    @click="downloadResumeLockFn(detailData.cv?.word_url)">
                                    立即导出简历
                                </ElButton>
                            </div>
                        </div>

                        <section>
                            <div class="flex items-center gap-2 mb-4 px-2">
                                <div class="w-1.5 h-4 rounded-full bg-primary"></div>
                                <h3 class="text-lg font-[900] text-[#0F172A]">候选人画像</h3>
                            </div>
                            <div class="bg-white rounded-[24px] border border-[#F1F5F9] overflow-hidden">
                                <div class="grid grid-cols-4 bg-slate-50 border-b border-[#F1F5F9]">
                                    <div
                                        v-for="cv in cvBasicInfo"
                                        :key="cv.label"
                                        class="p-5 border-r border-[#F1F5F9] last:border-r-0">
                                        <span class="text-[11px] font-medium text-[#94A3B8] uppercase block mb-1">{{
                                            cv.label
                                        }}</span>
                                        <span class="text-sm font-black text-[#475569]">{{ cv.value }}</span>
                                    </div>
                                </div>
                                <div class="p-8 space-y-8">
                                    <div class="resume-section">
                                        <label>工作经历</label>
                                        <markdown
                                            :content="formatMarkdown(detailData.cv?.work_ex)"
                                            :typing="false"
                                            class="markdown-body" />
                                    </div>
                                    <div class="resume-section">
                                        <label>项目经验</label>
                                        <markdown
                                            :content="formatMarkdown(detailData.cv?.project_ex)"
                                            :typing="false"
                                            class="markdown-body" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center gap-2 mb-4 px-2">
                                <div class="w-1.5 h-4 rounded-full bg-primary"></div>
                                <h3 class="text-lg font-[900] text-[#0F172A]">AI 深度分析报告</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div
                                    v-for="ai in aiAnalyzeList"
                                    :key="ai.label"
                                    class="bg-white p-6 rounded-[24px] border border-[#F1F5F9]">
                                    <div class="flex items-center gap-2 mb-3">
                                        <Icon :name="ai.icon" color="#0065FB" :size="18" />
                                        <span class="font-black text-[#0F172A] text-sm">{{ ai.label }}</span>
                                    </div>
                                    <div class="pl-7">
                                        <markdown :content="ai.value" :typing="false" class="markdown-body text-sm" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center gap-2 mb-4 px-2">
                                <div class="w-1.5 h-4 rounded-full bg-primary"></div>
                                <h3 class="text-lg font-[900] text-[#0F172A]">面试复盘记录</h3>
                            </div>

                            <div v-for="(item, index) in detailData.dialogs" :key="index" class="space-y-6 mb-10">
                                <div class="flex justify-center">
                                    <span
                                        class="px-4 py-1 bg-[#F1F5F9] rounded-full text-[11px] font-medium text-[#94A3B8]">
                                        轮次 {{ index + 1 }} · {{ item.out_reason }}
                                    </span>
                                </div>

                                <div v-for="(data, dIndex) in item.list" :key="dIndex" class="space-y-6">
                                    <div class="flex gap-4 max-w-[85%]">
                                        <div
                                            class="w-10 h-10 rounded-full bg-[#F1F5F9] flex items-center justify-center shrink-0">
                                            <Icon name="el-icon-UserFilled" color="#94A3B8" :size="20" />
                                        </div>
                                        <div class="relative group">
                                            <div
                                                class="bg-white p-4 rounded-2xl rounded-tl-none border border-[#F1F5F9] shadow-sm">
                                                <markdown :content="data.question" :typing="false" class="text-sm" />
                                            </div>
                                            <div v-if="data.question_url" class="mt-2">
                                                <button
                                                    @click="toggleAudio(data.question_url, data.id)"
                                                    class="audio-pills">
                                                    <Icon
                                                        :name="
                                                            currAudioId == data.id && isPlaying
                                                                ? 'el-icon-VideoPause'
                                                                : 'el-icon-VideoPlay'
                                                        "
                                                        :size="14" />
                                                    <span>播放面试官语音</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-row-reverse gap-4 ml-auto max-w-[85%]" v-if="data.answer">
                                        <div
                                            class="w-10 h-10 rounded-full bg-primary flex items-center justify-center shrink-0 shadow-lg shadow-[rgba(0,101,251,0.2)]">
                                            <Icon name="el-icon-Mic" color="#FFFFFF" :size="20" />
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <div
                                                class="bg-primary p-4 rounded-2xl rounded-tr-none text-white shadow-md shadow-[rgba(0,101,251,0.1)]">
                                                {{ data.answer }}
                                            </div>
                                            <div v-if="data.answer_url" class="mt-2">
                                                <button
                                                    @click="toggleAudio(data.answer_url, data.id)"
                                                    class="audio-pills border-primary text-primary bg-[#F1F6FF]">
                                                    <Icon
                                                        :name="
                                                            currAudioId == data.id && isPlaying
                                                                ? 'el-icon-VideoPause'
                                                                : 'el-icon-VideoPlay'
                                                        "
                                                        :size="14" />
                                                    <span>播放回答原音</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </ElScrollbar>
            </div>
            <div class="p-6 bg-[#FFFFFF] border-t border-[#F1F5F9] flex justify-end items-center gap-4 flex-shrink-0">
                <span class="text-xs font-medium text-[#94A3B8]"
                    >面试起止时间：{{ detailData.start_time_text }} - {{ detailData.end_time_text }}</span
                >
                <ElButton class="!rounded-xl !px-8 !h-11 !font-black !bg-slate-50" @click="handleClose"
                    >关闭报告</ElButton
                >
            </div>
        </div>
    </ElDrawer>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { getInterviewRecordDetail } from "@/api/interview";
import { isJson } from "@/utils/validate";
import { downloadFile } from "@/utils/util";
const emit = defineEmits<{
    (event: "success"): void;
    (event: "close"): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const visible = ref(false);

const detailData = reactive<Record<string, any>>({
    cv: {},
    ai: {},
    dialogs: [],
    start_time: 0,
    end_time: 0,
    score: 0,
});

const { lockFn: downloadResumeLockFn, isLock: isDownloadResumeLock } = useLockFn(async (url: string) => {
    await downloadFile(url);
});

const aiInfoList = computed(() => [
    { label: "面试岗位", value: detailData.ai?.job_name || "-" },
    { label: "面试时长", value: detailData.ai?.duration || "-" },
    { label: "重面/轮次", value: detailData.dialogs?.length || 0 },
    { label: "录音状态", value: "解析正常" },
]);

const cvBasicInfo = computed(() => [
    { label: "姓名", value: detailData.cv?.name || "-" },
    { label: "性别", value: detailData.cv?.sex == 1 ? "男" : " 女" },
    { label: "学历/年龄", value: `${detailData.cv?.degree || "-"} / ${detailData.cv?.age || "-"}岁` },
    { label: "联系方式", value: detailData.cv?.mobile || "-" },
]);

const aiAnalyzeList = computed(() => [
    { label: "面试深度分析", value: detailData.ai.analyze || "暂无", icon: "el-icon-TrendCharts" },
    { label: "侧重考察点评估", value: detailData.ai.inspection_point || "暂无", icon: "el-icon-Aim" },
    { label: "录用结果建议", value: detailData.ai.comment || "暂无", icon: "el-icon-Checked" },
]);

const { play, pause, pauseAll, setUrl, isPlaying } = useAudio();

const currAudioId = ref<number>();
const toggleAudio = (url: string, id: number) => {
    // 如果当前有音频在播放且不是目标音频,则停止播放
    if (isPlaying.value && currAudioId.value !== id) {
        pauseAll();
    }

    // 如果当前没有音频在播放
    if (!isPlaying.value) {
        // 如果目标音频与当前音频不同,需要重新设置音频源
        if (currAudioId.value !== id) {
            setUrl(url);
        }
        play();
        currAudioId.value = id;
    } else {
        // 如果当前有音频在播放,则暂停
        pause();
    }
};

const open = () => {
    visible.value = true;
};

const handleClose = () => {
    visible.value = false;
    emit("close");
};

const getDetail = async (id: number) => {
    const data = await getInterviewRecordDetail({
        id,
    });
    setFormData(data, detailData);
};

const formatMarkdown = (data: string) => {
    if (!data) return "";
    return data.replace(/^\["|"\]$/g, "").replace(/","/g, "<br/>");
};

defineExpose({
    open,
    getDetail,
    setFormData: (data: any) => setFormData(data, detailData),
});
</script>

<style scoped lang="scss">
.resume-section {
    @apply space-y-3;
    label {
        @apply flex items-center gap-2 text-xs font-black text-[#94A3B8] uppercase tracking-widest;
        &::after {
            content: "";
            @apply flex-1 h-[1px] bg-[#F1F5F9];
        }
    }
}

.audio-pills {
    @apply flex items-center gap-2 px-3 py-1.5 rounded-full border border-[#E2E8F0] text-[11px] font-black text-[#64748B] hover:bg-white transition-all active:scale-95;
}

.rounded-tl-none {
    border-top-left-radius: 4px !important;
}
.rounded-tr-none {
    border-top-right-radius: 4px !important;
}

:deep(.markdown-body) {
    @apply leading-relaxed;
    p {
        margin-bottom: 0;
    }
}
</style>
