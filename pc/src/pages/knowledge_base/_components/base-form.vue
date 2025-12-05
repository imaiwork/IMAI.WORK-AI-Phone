<template>
    <div>
        <ElForm ref="formRef" :model="formData" label-position="top" :rules="rules">
            <ElFormItem label="知识库名称" prop="name">
                <ElInput
                    class="!h-11"
                    v-model="formData.name"
                    show-word-limit
                    maxlength="20"
                    placeholder="请输入知识库名称" />
            </ElFormItem>
            <ElFormItem label="知识库描述" prop="description">
                <ElInput
                    v-model="formData.description"
                    show-word-limit
                    maxlength="200"
                    type="textarea"
                    resize="none"
                    :rows="6"
                    placeholder="请输入知识库描述" />
            </ElFormItem>
            <ElFormItem label="知识库类型" prop="type" v-if="!isEdit">
                <ElSelect
                    class="!h-11"
                    v-model="formData.type"
                    placeholder="请选择知识库类型"
                    popper-class="kb-type-select"
                    :show-arrow="false">
                    <ElOption v-for="item in kbTypeLists" :key="item.id" :label="item.name" :value="item.id">
                        <div class="flex items-center gap-x-2 options-item">
                            <span class="item-icon">
                                <Icon :name="item.icon"></Icon>
                            </span>
                            <span>{{ item.name }}</span>
                        </div>
                    </ElOption>
                </ElSelect>
            </ElFormItem>
            <ElFormItem label="知识库封面" prop="cover">
                <upload
                    class="w-full"
                    accept="image/png,image/jpeg,image/jpg"
                    show-progress
                    drag
                    :show-file-list="false"
                    :max-size="imageSize"
                    @success="handleUploadSuccess">
                    <div class="h-[111px] rounded-md flex flex-col items-center justify-center relative leading-5">
                        <template v-if="formData.cover">
                            <img :src="formData.cover" class="h-full object-cover" />
                            <ElTooltip content="删除" placement="right">
                                <div
                                    class="absolute top-1 right-1 cursor-pointer w-6 h-6"
                                    @click.stop="formData.cover = ''">
                                    <close-btn />
                                </div>
                            </ElTooltip>
                            <div class="absolute bottom-3 w-full flex justify-center">
                                <div
                                    class="cursor-pointer px-[10px] text-white rounded-full border border-[#ffffff1a] bg-[#ffffff4d] shadow-[0px_6px_12px_0px_rgba(0,0,0,0.24)] h-[28px] flex items-center justify-center text-xs">
                                    更换图片
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <Icon name="local-icon-file_add" :size="28" color="#000000"></Icon>
                            <span class="text-gray-500 text-xs mt-2">点此上传图片 支持拖拽上传</span>
                            <div class="text-xs text-gray-500">
                                图片大小不超过{{ imageSize }}MB，规格为png、jpg、jpeg，分辨率推荐128*128
                            </div>
                        </template>
                    </div>
                </upload>
            </ElFormItem>
        </ElForm>
    </div>
</template>

<script setup lang="ts">
import { ElForm } from "element-plus";
import type { CreateFormData } from "./type";
import { KnTypeEnum } from "../_enums";

const props = withDefaults(
    defineProps<{
        modelValue: CreateFormData;
        isEdit?: boolean;
    }>(),
    {
        modelValue: () => ({
            name: "",
            cover: "",
            description: "",
        }),
        isEdit: false,
    }
);
const emit = defineEmits<{
    (e: "update:modelValue", value: CreateFormData): void;
}>();

const formData = computed({
    get() {
        return props.modelValue;
    },
    set(value: CreateFormData) {
        emit("update:modelValue", value);
    },
});

const formRef = shallowRef<InstanceType<typeof ElForm>>();

const rules = {
    name: [{ required: true, message: "请输入知识库名称", trigger: "blur" }],
    description: [{ required: true, message: "请输入知识库描述", trigger: "blur" }],
    cover: [{ required: true, message: "请上传知识库封面", trigger: "blur" }],
};

const imageSize = 5;

const kbTypeLists = [
    {
        id: KnTypeEnum.VECTOR,
        name: "向量知识库",
        icon: "local-icon-kb_type1",
    },
    {
        id: KnTypeEnum.RAG,
        name: "RAG知识库",
        icon: "local-icon-kb_type2",
    },
];

const handleUploadSuccess = (res: any) => {
    const { uri } = res.data;
    formData.value.cover = uri;
};

defineExpose({
    validateForm: () => formRef.value?.validate(),
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss">
:deep(.el-upload-dragger) {
    @apply border-solid p-0;
}
</style>
