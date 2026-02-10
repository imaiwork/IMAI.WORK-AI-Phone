<template>
    <popup
        ref="popupRef"
        :title="popupTitle"
        width="560px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div class="px-2 py-2">
            <ElForm ref="formRef" :model="formData" :rules="rules" label-position="top" class="custom-form">
                <div class="grid grid-cols-2 gap-x-6">
                    <ElFormItem label="匹配模式" prop="match_type">
                        <div class="radio-block-group">
                            <div
                                :class="['radio-block', formData.match_type === 0 ? 'active' : '']"
                                @click="formData.match_type = 0">
                                <span class="dot"></span> 模糊匹配
                            </div>
                            <div
                                :class="['radio-block', formData.match_type === 1 ? 'active' : '']"
                                @click="formData.match_type = 1">
                                <span class="dot"></span> 精确匹配
                            </div>
                        </div>
                    </ElFormItem>

                    <ElFormItem label="触发来源" prop="match_mode">
                        <div class="radio-block-group">
                            <div
                                :class="['radio-block', formData.match_mode === 0 ? 'active' : '']"
                                @click="formData.match_mode = 0">
                                <Icon name="local-icon-robot" :size="14" /> <span class="ml-1.5">AI回复</span>
                            </div>
                            <div
                                :class="['radio-block', formData.match_mode === 1 ? 'active' : '']"
                                @click="formData.match_mode = 1">
                                <Icon name="el-icon-User" :size="14" /> <span class="ml-1.5">客户回复</span>
                            </div>
                        </div>
                    </ElFormItem>
                </div>

                <ElFormItem prop="match_keywords" label="匹配关键词">
                    <div class="relative w-full">
                        <ElInput
                            v-model="formData.match_keywords"
                            type="textarea"
                            resize="none"
                            placeholder="请输入触发标签的关键词，多个关键词请用英文逗号 (,) 分隔"
                            maxlength="500"
                            :rows="5"
                            class="custom-textarea" />
                        <div class="absolute bottom-3 right-4 text-[11px] text-tx-placeholder font-mono">
                            {{ formData.match_keywords?.length || 0 }}/500
                        </div>
                    </div>
                </ElFormItem>

                <ElFormItem prop="tag_name" label="赋予标签名称">
                    <ElInput
                        v-model="formData.tag_name"
                        placeholder="例如：高意向客户、咨询售后等"
                        maxlength="50"
                        class="custom-input">
                        <template #prefix>
                            <Icon name="el-icon-PriceTag" />
                        </template>
                    </ElInput>
                </ElFormItem>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { addTag, updateTag } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";

const emit = defineEmits(["close", "success"]);
const mode = ref("add");
const popupTitle = computed(() => (mode.value == "add" ? "新增标签规则" : "编辑标签规则"));

const formRef = ref<InstanceType<typeof ElForm>>();
const formData = reactive<Record<string, any>>({
    id: "",
    match_type: 0,
    match_mode: 0,
    match_keywords: "",
    tag_name: "",
    wechat_id: "",
});

const rules = {
    match_keywords: [{ required: true, message: "请输入匹配关键词", trigger: "blur" }],
    tag_name: [{ required: true, message: "请输入匹配标签", trigger: "blur" }],
};

const popupRef = ref<InstanceType<typeof Popup>>();

const { lockFn, isLock } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        mode.value == "add" ? await addTag(formData) : await updateTag(formData);
        popupRef.value?.close();
        feedback.msgSuccess("保存成功");
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
});

const open = (type = "add") => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    // 重置表单
    formRef.value?.resetFields();
    emit("close");
};

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>

<style scoped lang="scss">
.radio-block-group {
    @apply flex bg-slate-100 p-1 rounded-xl w-full;
    .radio-block {
        @apply flex-1 flex items-center justify-center py-2 text-[13px] font-medium text-tx-regular cursor-pointer transition-all rounded-lg;
        .dot {
            @apply w-1.5 h-1.5 rounded-full bg-slate-300 mr-2 transition-all;
        }

        &:hover {
            @apply text-primary;
        }

        &.active {
            @apply bg-white text-primary shadow-light;
            .dot {
                @apply bg-primary;
            }
        }
    }
}
</style>
