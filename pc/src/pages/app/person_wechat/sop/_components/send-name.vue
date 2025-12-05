<template>
    <popup ref="popupRef" title="设置名称" async :confirm-loading="isLock" @close="close" @confirm="lockFn">
        <div>
            <ElForm ref="formRef" :model="formData" label-position="top" :rules="rules">
                <ElFormItem label="任务名称" prop="push_name">
                    <ElInput v-model="formData.push_name" placeholder="请输入任务名称" />
                </ElFormItem>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { sopPushAdd, sopPushUpdate } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import { PushTypeEnum } from "../_enums";

const props = defineProps<{
    type: PushTypeEnum;
}>();

const emit = defineEmits<{
    (e: "success", result: any): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();

const formRef = shallowRef<InstanceType<typeof ElForm>>();
const formData = reactive({
    id: "",
    type: "",
    push_name: "",
    push_type: props.type,
    stage_id: "",
    flow_id: "",
});

const rules = {
    push_name: [{ required: true, message: "请输入名称", trigger: "blur" }],
};

const handleConfirm = async () => {
    await formRef.value?.validate();
    try {
        const result = formData.id ? await sopPushUpdate(formData) : await sopPushAdd(formData);
        emit("success", result);
        close();
    } catch (error) {
        feedback.msgError(error);
    }
};

const open = () => {
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(handleConfirm);

defineExpose({
    open,
    setFormData: (data) => setFormData(data, formData),
});
</script>
