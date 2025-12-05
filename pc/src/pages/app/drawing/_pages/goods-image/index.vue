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
import { drawingGoods } from "@/api/drawing";
import { drawTypeEnumMap, DrawTypeEnum } from "../../_enums";
import useCreateForm from "../../_hooks/useCreateForm";
import useDrawingTask, { ResultItem } from "../../_hooks/useDrawingTask";
import ResultContent from "../../_components/result-content.vue";

enum FormTypeEnum {
    GOODS_IMAGE = drawTypeEnumMap[DrawTypeEnum.GOODS_IMAGE],
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
    const { type_name, style_name, params } = data;
    const resultData = reactive({
        date: dayjs().format("YYYY-MM-DD HH:mm"),
        prompt: params.prompt,
        tags: [params.resolution?.join("*"), type_name, style_name, params.template_name_zh],
        images: [] as ResultItem[],
        formData: data,
    });
    resultLists.value.unshift(resultData);

    try {
        resultData.images = new Array(parseInt(params.img_count)).fill({
            url: "",
            loading: true,
            progress: 0,
            status: 0,
            error: false,
        });
        const { result } = await drawingGoods(params);

        const { processDrawingTask } = useDrawingTask({
            type: FormTypeEnum.GOODS_IMAGE,
            task_id: result.task_id,
            dataLists: resultData.images,
        });
        await processDrawingTask({
            callback: (data) => {
                resultData.images = data;
            },
        });
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
