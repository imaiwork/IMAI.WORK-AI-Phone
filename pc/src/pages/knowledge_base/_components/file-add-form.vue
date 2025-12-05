<template>
    <div>
        <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top">
            <ElFormItem label="切割策略" prop="name">
                <div class="flex gap-4 w-full">
                    <div
                        class="rounded-lg py-4 px-6 w-full relative border cursor-pointer"
                        v-for="item in chunkStrategyList"
                        :key="item.id"
                        :class="
                            formData.strategy == item.id
                                ? 'border-primary-light-5 bg-primary-light-9'
                                : 'border-[#e6e6e6]'
                        "
                        @click="formData.strategy = item.id">
                        <div
                            class="absolute top-2 right-2 w-4 h-4 rounded-full flex items-center justify-center"
                            :class="
                                formData.strategy == item.id
                                    ? 'bg-primary'
                                    : 'bg-white shadow-[0_0_0px_1px_rgba(0,0,0,0.1)]'
                            ">
                            <div class="w-[6px] h-[6px] bg-white rounded-full"></div>
                        </div>
                        <div class="flex items-center gap-x-4">
                            <img :src="item.image" class="w-6 h-6" />
                            <div class="text-lg font-bold">
                                {{ item.title }}
                            </div>
                        </div>
                        <div class="text-xs leading-5 mt-4">
                            {{ item.desc }}
                        </div>
                    </div>
                </div>
            </ElFormItem>
            <template v-if="formData.strategy == 2">
                <ElFormItem label="切割符号" prop="separator">
                    <ElSelect v-model="formData.separator" multiple clearable placeholder="请选择切割符号">
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
                            min: 1,
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
            <ElFormItem label="上传本地文件" prop="documents">
                <kn-upload-file
                    ref="fileUploadRef"
                    class="w-full"
                    v-model="fileLists"
                    drag
                    multiple
                    type="file"
                    :data="{
                        indexid: formData.index_id,
                    }"
                    :limit="uploadFileLimit"
                    :accept="uploadFileTypes"
                    @on-progress="handleUploadProgress"
                    @update:model-value="handleUploadChange">
                    <div class="h-[200px] bg-primary-light-9 flex flex-col items-center justify-center">
                        <Icon name="local-icon-upload_cloud" :size="48" color="#67656E"></Icon>
                        <div class="mt-2 text-[#67656E]">拽上传文件或<span class="text-primary">点击上传</span></div>
                        <div class="w-[70%] mt-4 text-center text-xs text-[#524B6B] leading-5 break-all">
                            支持{{ uploadFileTypes }}格式单文档最大限制10MB，单图片最大限制10MB最多支持（{{
                                fileLists.length
                            }}/ {{ uploadFileLimit }}）个
                        </div>
                    </div>
                </kn-upload-file>
            </ElFormItem>
        </ElForm>
    </div>
</template>

<script setup lang="ts">
import { ElForm } from "element-plus";
import { punctuationOptions } from "@/config/common";
import AutoChunk from "@/assets/images/auto_chunk.png";
import CustomChunk from "@/assets/images/custom_chunk.png";
import KnUploadFile from "./upload-file.vue";

const props = withDefaults(
    defineProps<{
        modelValue: Record<string, any>;
    }>(),
    {
        modelValue: () => ({}),
    }
);

const emit = defineEmits<{
    (event: "update:modelValue", value: Record<string, any>);
}>();

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const rules = {
    documents: [{ required: true, message: "请输入上传文件", trigger: "blur" }],
    separator: [{ required: true, message: "请选择切割符号", trigger: "blur" }],
    chunk_size: [{ required: true, message: "请输入切割长度", trigger: "blur" }],
    overlap_size: [{ required: true, message: "请输入切割重叠度", trigger: "blur" }],
};

const chunkStrategyList = [
    {
        id: 1,
        title: "智能切分",
        image: AutoChunk,
        desc: "在通用文档上的最优chunk切分方法，经过评测可在多数文档上获得最佳的检索效果",
    },
    {
        id: 2,
        title: "自定义切分",
        image: CustomChunk,
        desc: "完全开放的chunk切分配置，按照实际文档情况自由配置，通过调试获得更好的检索效果",
    },
];

// 上传文件数量限制
const uploadFileLimit = 20;
const uploadFileTypes = ".pdf,.docx,.doc,.txt,.md,.pptx,.ppt,.xlsx,.xls,.png,.jpg,.jpeg,.bmp,.gif";
const fileLists = ref<any[]>([]);

const fileUploadRef = ref<InstanceType<typeof KnUploadFile>>();
const handleUploadProgress = (result: any) => {
    fileLists.value = result.fileList;
};

const handleUploadChange = (fileLists: any[]) => {
    formData.value.documents = fileLists;
};

const validateForm = () => {
    return new Promise((resolve, reject) => {
        const isUploading = fileLists.value.some((item: any) => item.status == "uploading");
        if (isUploading) {
            feedback.msgError("文件上传中，请稍后再试");
            return;
        }
        formRef.value?.validate().then(resolve).catch(reject);
    });
};

const clearUploadFile = () => {
    fileUploadRef.value?.clear();
};

defineExpose({
    validateForm,
    clearUploadFile,
});
</script>

<style scoped></style>
