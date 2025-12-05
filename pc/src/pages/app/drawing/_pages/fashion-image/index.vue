<template>
    <div class="h-full relative w-full">
        <ElScrollbar>
            <div class="px-[10px]">
                <result-content
                    :result-lists="resultLists"
                    :is-all-tasks-completed="isAllTasksCompleted"
                    @retry="retry">
                    <template #add-content="{ item }">
                        <div class="z-[22] absolute top-4 left-0 w-full flex px-4">
                            <div class="flex flex-col items-center gap-5 mt-2">
                                <div class="create-image-item">
                                    <img
                                        :src="item.formData.params.upper_clothes"
                                        class="w-full h-full object-contain" />
                                </div>
                                <div
                                    class="create-image-item"
                                    v-if="item.formData.type == FashionImageTypeEnum.UPPER_LOWER_CLOTHES">
                                    <img
                                        :src="item.formData.params.lower_clothes"
                                        class="w-full h-full object-contain" />
                                </div>
                                <div class="create-image-item">
                                    <img :src="item.formData.params.persons[0]" class="w-full h-full object-contain" />
                                </div>
                            </div>
                        </div>
                    </template>
                </result-content>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { dayjs, ElScrollbar } from "element-plus";
import { drawingFitting } from "@/api/drawing";
import { drawTypeEnumMap, DrawTypeEnum, FashionImageTypeEnum } from "../../_enums";
import useCreateForm from "../../_hooks/useCreateForm";
import useDrawingTask, { ResultItem } from "../../_hooks/useDrawingTask";
import ResultContent from "../../_components/result-content.vue";

enum FormTypeEnum {
    FASHION_IMAGE = drawTypeEnumMap[DrawTypeEnum.FASHION_IMAGE],
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
    const { type_name, params } = data;
    const resultData = reactive({
        date: dayjs().format("YYYY-MM-DD HH:mm"),
        tags: [type_name],
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
        const { result } = await drawingFitting(params);

        const { processDrawingTask } = useDrawingTask({
            type: FormTypeEnum.FASHION_IMAGE,
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

<style scoped lang="scss">
.create-image-item {
    @apply w-16 h-16 rounded-md overflow-hidden shadow-[0px_6px_12px_0px_rgba(0,0,0,0.24)];
    backdrop-filter: blur(6px);
}
</style>
