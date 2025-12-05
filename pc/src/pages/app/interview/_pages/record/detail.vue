<template>
    <ElDrawer
        v-model="visible"
        title="面试记录详情"
        ref="popupRef"
        :async="true"
        size="1000px"
        confirm-button-text=""
        cancel-button-text=""
        @close="handleClose">
        <div class="pb-10">
            <ElCard>
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
                <ElDescriptions :column="4" border>
                    <ElDescriptionsItem label="姓名">
                        {{ detailData.cv?.name }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="性别">
                        {{ detailData.cv?.sex == 1 ? "男" : " 女" }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="年龄">
                        {{ detailData.cv?.age }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="联系方式">
                        {{ detailData.cv?.mobile }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="工作年份">
                        {{ detailData.cv?.work_years }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="毕业院校">
                        {{ detailData.cv?.school }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="学历">
                        {{ detailData.cv?.degree }}
                    </ElDescriptionsItem>
                </ElDescriptions>
                <ElDescriptions border :column="1">
                    <ElDescriptionsItem label="工作经历">
                        <markdown :content="formatMarkdown(detailData.cv?.work_ex)" :typing="false" />
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="项目经历">
                        <markdown :content="formatMarkdown(detailData.cv?.project_ex)" :typing="false" />
                    </ElDescriptionsItem>
                </ElDescriptions>
            </ElCard>
            <ElCard class="mt-3">
                <template #header>
                    <div class="text-xl font-bold">AI面试分析报告</div>
                </template>
                <ElDescriptions :column="4" border>
                    <ElDescriptionsItem label="面试岗位">
                        {{ detailData.ai?.job_name }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="面试得分">
                        {{ detailData.ai.score || 0 }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="重面次数">
                        {{ detailData.dialogs.length }}
                    </ElDescriptionsItem>
                    <ElDescriptionsItem label="面试时长">
                        {{ detailData.ai.duration }}
                    </ElDescriptionsItem>
                </ElDescriptions>
                <ElDescriptions border :column="1">
                    <ElDescriptionsItem label="面试分析">
                        <markdown :content="detailData.ai.analyze || '暂无'" :typing="false" />
                    </ElDescriptionsItem>
                </ElDescriptions>
                <ElDescriptions border :column="1">
                    <ElDescriptionsItem label="侧重考察点">
                        <markdown :content="detailData.ai.inspection_point || '暂无'" :typing="false" />
                    </ElDescriptionsItem>
                </ElDescriptions>
                <ElDescriptions border :column="1">
                    <ElDescriptionsItem label="面试结果">
                        <markdown :content="detailData.ai.comment || '暂无'" :typing="false" />
                    </ElDescriptionsItem>
                </ElDescriptions>
            </ElCard>
            <ElCard class="mt-3" v-for="(item, index) in detailData.dialogs" :key="index">
                <template #header>
                    <div class="text-xl font-bold">对话记录({{ item.out_reason }})</div>
                </template>
                <div>
                    <div class="flex flex-col gap-4">
                        <div v-for="(data, dIndex) in item.list" class="flex flex-col gap-4">
                            <div class="flex gap-2">
                                <div class="flex-shrink-0">
                                    <div class="bg-gray-100 py-2.5 px-4 rounded-lg">面试官</div>
                                </div>
                                <div class="bg-gray-100 p-2 rounded-lg">
                                    <markdown :content="data.question" :typing="false" />
                                </div>
                                <div class="flex-shrink-0" v-if="data.question_url">
                                    <div class="bg-gray-100 p-2 rounded-lg">
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
            </ElCard>
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-3 bg-white border-t border-gray-200">
            <div class="flex justify-end">
                <ElButton @click="handleClose">取消</ElButton>
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
    setFormData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in detailData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            detailData[key] = data[key];
        }
    }
};

const formatMarkdown = (data: string) => {
    if (!data) return "";
    return data.replace(/^\["|"\]$/g, "").replace(/","/g, "<br/>");
};

defineExpose({
    open,
    getDetail,
    setFormData,
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
</style>
