<template>
    <div class="h-full relative w-full">
        <ElScrollbar>
            <div class="px-[10px]">
                <result-content
                    :result-lists="resultLists"
                    :is-all-tasks-completed="isAllTasksCompleted"
                    @retry="retry" />
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { dayjs, ElScrollbar } from "element-plus";
import { drawingTextToImage, drawingTextToImageVolc } from "@/api/drawing";
import { ModelEnum, drawTypeEnumMap, DrawTypeEnum } from "../../_enums";
import useCreateForm from "../../_hooks/useCreateForm";
import useDrawingTask, { ResultItem } from "../../_hooks/useDrawingTask";
import ResultContent from "../../_components/result-content.vue";

const { onEvent, setFormData } = useCreateForm();
const userStore = useUserStore();
const resultLists = ref<any[]>([]);
const isAllTasksCompleted = ref(false);

const handleHidreamaiTask = async (params: any, resultData: any) => {
    resultData.images = new Array(parseInt(params.img_count)).fill({
        url: "",
        loading: true,
        progress: 0,
        status: 0,
        error: false,
    });
    const { result } = await drawingTextToImage(params);

    const { processDrawingTask } = useDrawingTask({
        type: drawTypeEnumMap[DrawTypeEnum.TXT2IMAGE],
        task_id: result.task_id,
        dataLists: resultData.images,
    });
    await processDrawingTask({
        callback: (data) => {
            resultData.images = data;
        },
    });
};

const handleVolcTask = async (params: any, resultData: any) => {
    resultData.images = [{ url: "", loading: true, progress: 0, status: 0, error: false }];
    const { result } = await drawingTextToImageVolc(params);
    resultData.images = [result.image_urls].map((item) => ({
        url: item,
        loading: false,
        progress: 100,
        status: 1,
        error: false,
    }));
};

onEvent("update:formData", async (data: any) => {
    if (isAllTasksCompleted.value) {
        feedback.msgWarning("请等待上一次任务完成");
        return;
    }
    isAllTasksCompleted.value = true;
    const { model, model_name, params } = data;
    const resultData = reactive({
        date: dayjs().format("YYYY-MM-DD HH:mm"),
        prompt: params.poster_description,
        images: [] as ResultItem[],
        formData: data,
        tags: [
            model_name,
            params.poster_type,
            params.poster_color,
            params.poster_title,
            params.poster_subtitle,
        ],
    });
    resultLists.value.unshift(resultData);
    try {
        if (model == ModelEnum.HIDREAMAI) {
            await handleHidreamaiTask(params, resultData);
        } else if (model == ModelEnum.GENERAL || model == ModelEnum.SEEDREAM) {
            await handleVolcTask(params, resultData);
        }
        userStore.getUser();
    } catch (error) {
        resultData.images = resultData.images.map((item) => ({
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
