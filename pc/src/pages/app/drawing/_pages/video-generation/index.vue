<template>
    <div class="h-full relative w-full">
        <ElScrollbar>
            <div class="px-[10px]">
                <result-content
                    type="video"
                    :result-lists="resultLists"
                    :is-all-tasks-completed="isAllTasksCompleted"
                    @retry="retry" />
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { dayjs, ElScrollbar } from "element-plus";
import { useUserStore } from "@/stores/user";
import {
    drawingTextToVideo,
    drawingImageToVideo,
    drawingTextToVideoDoubao,
    drawingImageToVideoDoubao,
} from "@/api/drawing";
import useCreateForm from "../../_hooks/useCreateForm";
import { GenerateVideoTypeEnum, ModelEnum } from "../../_enums";
import useDrawingTask from "../../_hooks/useDrawingTask";
import ResultContent from "../../_components/result-content.vue";

const { onEvent, setFormData } = useCreateForm();
const userStore = useUserStore();
const resultLists = ref<any[]>([]);
const isAllTasksCompleted = ref(false);

onEvent("update:formData", async (data: any) => {
    if (isAllTasksCompleted.value) {
        feedback.msgWarning("请等待上一次任务完成");
        return;
    }
    isAllTasksCompleted.value = true;
    const { type, type_name, model, model_name, aspect_ratio, params } = data;
    const resultData = reactive({
        date: dayjs().format("YYYY-MM-DD HH:mm"),
        prompt: params.prompt,
        video: [] as any[],
        tags: [type_name, aspect_ratio, model_name],
        formData: data,
    });
    resultLists.value.unshift(resultData);
    try {
        if (model == ModelEnum.GENERAL) {
            resultData.video = [{ url: "", loading: true, progress: 0, status: 0, error: false }];
            const { data, request_id } =
                type == GenerateVideoTypeEnum.TXT2VIDEO
                    ? await drawingTextToVideo(params)
                    : await drawingImageToVideo(params);
            const { processDrawingTask } = useDrawingTask({
                type,
                model,
                task_id: data?.task_id || request_id,
                dataLists: resultData.video,
                drawType: "video",
            });
            await processDrawingTask({
                callback: (data) => {
                    resultData.video = data;
                },
            });
        } else if (model == ModelEnum.SEEDANCE) {
            resultData.video = [{ url: "", loading: true, progress: 0, status: 0, error: false }];
            const { task_id } =
                type == GenerateVideoTypeEnum.TXT2VIDEO
                    ? await drawingTextToVideoDoubao(params)
                    : await drawingImageToVideoDoubao(params);
            const { processDrawingTask } = useDrawingTask({
                type,
                model,
                task_id,
                dataLists: resultData.video,
                drawType: "video",
            });
            await processDrawingTask({
                callback: (data) => {
                    resultData.video = data;
                },
            });
        }
        userStore.getUser();
    } catch (error) {
        resultData.video = resultData.video.map((item) => ({
            ...item,
            loading: false,
            progress: 0,
            status: 4,
            error: true,
        }));
    } finally {
        isAllTasksCompleted.value = false;
    }
});

const retry = (formData: any) => {
    setFormData(formData);
};
</script>
