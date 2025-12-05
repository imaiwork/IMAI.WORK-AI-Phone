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
import {
    drawingTextToImage,
    drawingImageToImage,
    drawingTextToImageVolc,
    drawingImageToImageVolc,
} from "@/api/drawing";
import { ModelEnum, drawTypeEnumMap, DrawTypeEnum } from "../../_enums";
import useCreateForm from "../../_hooks/useCreateForm";
import useDrawingTask, { ResultItem } from "../../_hooks/useDrawingTask";
import ResultContent from "../../_components/result-content.vue";

enum FormTypeEnum {
    TXT2IMAGE = drawTypeEnumMap[DrawTypeEnum.TXT2IMAGE],
    IMAGE2IMAGE = drawTypeEnumMap[DrawTypeEnum.IMAGE2IMAGE],
}

const userStore = useUserStore();
const { onEvent, setFormData } = useCreateForm();

const resultLists = ref<any[]>([]);
const isAllTasksCompleted = ref(false);

onEvent("update:formData", async (data: any) => {
    if (isAllTasksCompleted.value) {
        feedback.msgWarning("请等待上一次任务完成");
        return;
    }
    isAllTasksCompleted.value = true;
    const { type, type_name, model, resolution, model_name, params } = data;

    const resultData = reactive({
        date: dayjs().format("YYYY-MM-DD HH:mm"),
        prompt: params.prompt,
        images: [] as ResultItem[],
        formData: data,
        tags: [model_name, resolution, type_name],
    });
    resultLists.value.unshift(resultData);
    try {
        if (model == ModelEnum.HIDREAMAI) {
            resultData.images = new Array(parseInt(params.img_count)).fill({
                url: "",
                loading: true,
                progress: 0,
                status: 0,
                error: false,
            });
            const apiCall = type == FormTypeEnum.TXT2IMAGE ? drawingTextToImage : drawingImageToImage;
            const { result } = await apiCall(params);

            const { processDrawingTask } = useDrawingTask({
                type,
                task_id: result.task_id,
                dataLists: resultData.images,
            });
            await processDrawingTask({
                callback: (data) => {
                    resultData.images = data;
                },
            });
        } else if (model == ModelEnum.GENERAL || model == ModelEnum.SEEDREAM) {
            resultData.images = [{ url: "", loading: true, progress: 0, status: 0, error: false }];
            const apiCall = type == FormTypeEnum.TXT2IMAGE ? drawingTextToImageVolc : drawingImageToImageVolc;
            const { result } = await apiCall(params);
            resultData.images = [result.image_urls].map((item) => ({
                url: item,
                loading: false,
                progress: 100,
                status: 1,
                error: false,
            }));
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
