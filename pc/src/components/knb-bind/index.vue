<template>
    <popup
        ref="popupRef"
        title="选择要关联的知识库"
        async
        width="500px"
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <ElForm :model="formData" label-position="top" :rules="rules" ref="formRef">
            <ElFormItem label="知识库" prop="id">
                <ElSelect v-model="formData.id" placeholder="请选择知识库" filterable clearable>
                    <ElOption
                        v-for="item in optionsData.knbLists"
                        :key="item.index_id"
                        :label="item.name"
                        :value="item.index_id" />
                </ElSelect>
            </ElFormItem>
            <ElFormItem label="切割策略" prop="strategy">
                <ElSelect v-model="formData.strategy" placeholder="请选择切割策略">
                    <ElOption label="智能切分" :value="1"></ElOption>
                    <ElOption label="自定义切分" :value="2"></ElOption>
                </ElSelect>
            </ElFormItem>
            <template v-if="formData.strategy === 2">
                <ElFormItem label="切割符号" prop="separator">
                    <ElSelect v-model="formData.separator" placeholder="请选择切割符号" multiple>
                        <ElOption
                            v-for="item in punctuationOptions"
                            :key="item.value"
                            :label="`${item.label}：${item.value}`"
                            :value="item.value">
                            <span>{{ item.label }}：</span>
                            <span class="font-bold">{{ item.value }}</span>
                        </ElOption>
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="切割长度" prop="chunk_size">
                    <ElInput
                        v-number-input="{
                            decimalPlaces: 0,
                            max: 1024,
                        }"
                        v-model="formData.chunk_size"
                        type="number"
                        placeholder="请输入切割长度" />
                </ElFormItem>
                <ElFormItem label="切割重叠度" prop="overlap_size">
                    <ElInput
                        v-number-input="{
                            decimalPlaces: 0,
                            min: 1,
                            max: 2048,
                        }"
                        v-model="formData.overlap_size"
                        type="number"
                        placeholder="请输入切割重叠度" />
                </ElFormItem>
                <ElFormItem label="相似度阈值" prop="rerank_min_score">
                    <ElSlider
                        v-model="formData.rerank_min_score"
                        :min="0"
                        :max="1"
                        :step="0.01"
                        size="small"
                        show-input />
                    <div class="absolute -top-[33px] left-[90px]">
                        <ElTooltip popper-class="w-[200px]">
                            <div class="absolute top-0 right-0 cursor-pointer">
                                <Icon name="el-icon-QuestionFilled" color="#858585" />
                            </div>
                            <template #content>
                                <div>
                                    设定最低分数标准，只有达到或超过这个阈值的检索结果才会被考虑用于后续的排序和生成过程
                                </div>
                            </template>
                        </ElTooltip>
                    </div>
                </ElFormItem>
            </template>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import { knowledgeBaseLists, knowledgeBaseFileAdd, knowledgeBaseFileUpload } from "@/api/knowledge_base";
import { punctuationOptions } from "@/config/common";
const emit = defineEmits(["close", "success"]);

const popupRef = ref<InstanceType<typeof Popup>>(null);

const formData = reactive({
    id: "",
    category_id: "",
    strategy: 1,
    separator: "",
    chunk_size: 600,
    overlap_size: 100,
    rerank_min_score: 0.5,
});

const rules = reactive({
    id: [{ required: true, message: "请选择知识库", trigger: "blur" }],
    separator: [{ required: true, message: "请选择切割符号", trigger: "blur" }],
});

const formRef = ref<InstanceType<typeof ElForm>>(null);

const { optionsData } = useDictOptions<{
    knbLists: any[];
}>({
    knbLists: {
        api: knowledgeBaseLists,
        params: { page_size: 25000 },
        transformData: (data) => data.lists,
    },
});

const currKnbData = computed(() => {
    return optionsData.knbLists.find((item) => item.index_id === formData.id);
});

const handleConfirm = async () => {
    await formRef.value?.validate();
    const { type, fileName, content } = uploadData.value;
    if (type === "txt") {
        const encoder = new TextEncoder();
        const contentBytes = encoder.encode(content);
        const file = new File([contentBytes], `${fileName}.txt`, {
            type: "text/plain;charset=utf-8",
        });
        const fileFormData = new FormData();
        fileFormData.append("file", file);
        fileFormData.append("indexid", formData.id);
        try {
            const uploadRes = await knowledgeBaseFileUpload(fileFormData);

            const { id, category_id } = currKnbData.value;
            await knowledgeBaseFileAdd({
                ...formData,
                id,
                category_id,
                documents: [uploadRes],
            });
            feedback.msgSuccess("操作成功");
            popupRef.value?.close();
            emit("success");
        } catch (error) {
            feedback.msgError(error || "操作失败");
        }
    }
};

const { lockFn, isLock } = useLockFn(handleConfirm);

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

interface OpenData {
    type: "txt";
    fileName: string;
    content: string;
}

const uploadData = ref<OpenData | null>(null);

const setFormData = (data: OpenData) => {
    uploadData.value = data;
};

defineExpose({
    open,
    setFormData,
});
</script>

<style scoped></style>
