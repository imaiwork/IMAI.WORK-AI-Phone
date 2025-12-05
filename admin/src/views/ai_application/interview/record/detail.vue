<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header content="岗位详情" @back="$router.back()" />
        </el-card>
        <div class="mt-4">
            <el-card class="!border-none" shadow="never">
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="text-xl font-bold">面试者简历</div>
                        <div v-if="detailData.cv?.word_url">
                            <ElButton
                                type="primary"
                                plain
                                :loading="isDownloadResumeLock"
                                @click="downloadResumeLockFn(detailData.cv?.word_url)">
                                导出简历附件
                            </ElButton>
                        </div>
                    </div>
                </template>
                <el-descriptions :column="3" border>
                    <el-descriptions-item label="姓名">
                        {{ detailData.cv?.name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="性别">
                        {{ detailData.cv?.sex == 1 ? "男" : " 女" }}
                    </el-descriptions-item>
                    <el-descriptions-item label="年龄">
                        {{ detailData.cv?.age }}
                    </el-descriptions-item>
                    <el-descriptions-item label="联系方式">
                        {{ detailData.cv?.mobile }}
                    </el-descriptions-item>
                    <el-descriptions-item label="工作年份">
                        {{ detailData.cv?.work_years }}
                    </el-descriptions-item>
                    <el-descriptions-item label="毕业院校">
                        {{ detailData.cv?.school }}
                    </el-descriptions-item>
                    <el-descriptions-item label="学历">
                        {{ detailData.cv?.degree }}
                    </el-descriptions-item>
                </el-descriptions>
                <el-descriptions border :column="1">
                    <el-descriptions-item label="工作经历">
                        <markdown :content="formatMarkdown(detailData.cv?.work_ex)" :typing="false" />
                    </el-descriptions-item>
                    <el-descriptions-item label="项目经历">
                        <markdown :content="formatMarkdown(detailData.cv?.project_ex)" :typing="false" />
                    </el-descriptions-item>
                </el-descriptions>
            </el-card>
            <el-card class="mt-3 !border-none" shadow="never">
                <template #header>
                    <div class="text-xl font-bold">AI面试分析报告</div>
                </template>
                <el-descriptions :column="4" border>
                    <el-descriptions-item label="面试岗位">
                        {{ detailData.ai?.job_name }}
                    </el-descriptions-item>
                    <el-descriptions-item label="面试得分">
                        {{ detailData.ai.score || 0 }}
                    </el-descriptions-item>
                    <el-descriptions-item label="重面次数">
                        {{ detailData.dialogs.length }}
                    </el-descriptions-item>
                    <el-descriptions-item label="面试时长">
                        {{ detailData.ai.duration }}
                    </el-descriptions-item>
                </el-descriptions>
                <el-descriptions border :column="1">
                    <el-descriptions-item label="面试分析">
                        <markdown :content="detailData.ai.analyze || '暂无'" :typing="false" />
                    </el-descriptions-item>
                </el-descriptions>
                <el-descriptions border :column="1">
                    <el-descriptions-item label="侧重考察点">
                        <markdown :content="detailData.ai.inspection_point || '暂无'" :typing="false" />
                    </el-descriptions-item>
                </el-descriptions>
                <el-descriptions border :column="1">
                    <el-descriptions-item label="面试结果">
                        <markdown :content="detailData.ai.comment || '暂无'" :typing="false" />
                    </el-descriptions-item>
                </el-descriptions>
            </el-card>
            <el-card class="mt-3 !border-none" shadow="never" v-for="(item, index) in detailData.dialogs" :key="index">
                <template #header>
                    <div class="text-xl font-bold">对话记录({{ item.out_reason }})</div>
                </template>
                <div>
                    <div class="flex flex-col gap-4">
                        <div v-for="(data, dIndex) in item.list" class="flex flex-col gap-4">
                            <div class="flex gap-2">
                                <div class="flex-shrink-0">
                                    <div class="bg-[#ececec] py-2.5 px-4 rounded-lg">面试官</div>
                                </div>
                                <div class="bg-[#ececec] p-2 rounded-lg">
                                    <markdown :content="data.question" :typing="false" />
                                </div>
                                <div class="flex-shrink-0" v-if="data.question_url">
                                    <div class="bg-[#ececec] p-2 rounded-lg">
                                        <div
                                            class="p-[7.5px] flex items-center justify-center bg-white rounded-full"
                                            @click="toggleAudio(data.question_url, data.id)">
                                            <Icon
                                                :name="
                                                    currAudioId == data.id && isPlaying
                                                        ? 'local-icon-music_pause'
                                                        : 'local-icon-music_play'
                                                "
                                                :size="10"></Icon>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-row-reverse gap-2" v-if="data.answer">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary-light-7 py-2.5 px-4 rounded-lg">面试者</div>
                                </div>
                                <div class="bg-primary-light-7 p-2 rounded-lg">
                                    <markdown :content="data.answer" :typing="false" />
                                </div>
                                <div class="flex-shrink-0" v-if="data.answer_url">
                                    <div class="bg-primary-light-7 p-2 rounded-lg">
                                        <div
                                            class="p-[7.5px] flex items-center justify-center bg-white rounded-full"
                                            @click="toggleAudio(data.answer_url, data.id)">
                                            <Icon
                                                :name="
                                                    currAudioId == data.id && isPlaying
                                                        ? 'local-icon-music_pause'
                                                        : 'local-icon-music_play'
                                                "
                                                :size="10"></Icon>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </el-card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getInterviewRecordDetail } from "@/api/ai_application/interview/record";
import { useAudio } from "@/hooks/useAudioPlay";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";

const route = useRoute();

const detailData = ref<any>({
    cv: {},
    ai: {},
    dialogs: [],
    start_time: 0,
    end_time: 0,
    score: 0,
});

const { lockFn: downloadResumeLockFn, isLock: isDownloadResumeLock } = useLockFn(async (url: string) => {
    if (url) {
        window.open(url, "_blank");
    } else {
        feedback.msgError("简历附件不存在");
    }
});

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

const formatMarkdown = (data: string) => {
    if (!data) return "";
    return data.replace(/^\["|"\]$/g, "").replace(/","/g, "<br/>");
};
const getDetail = async () => {
    const data = await getInterviewRecordDetail({ id: route.query.id });
    detailData.value = data;
};

onMounted(() => {
    getDetail();
});
</script>

<style scoped lang="scss">
:deep(.el-descriptions__label) {
    width: 100px;
}

:deep(.el-descriptions__body) {
    .el-descriptions__table {
        border-collapse: initial;
    }
    .el-descriptions__content {
        @apply break-all;
    }
}
:deep(.markdown-body) {
    font-size: 14px !important;
    background-color: transparent !important;
}
</style>
