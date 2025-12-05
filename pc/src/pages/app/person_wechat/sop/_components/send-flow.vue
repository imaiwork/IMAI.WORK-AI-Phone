<template>
    <popup
        ref="popupRef"
        title="设置执行范围"
        width="500px"
        async
        :confirm-loading="isLock"
        @close="close"
        @confirm="lockFn">
        <div>
            <ElForm ref="formRef" :model="formData" label-position="top" :rules="rules">
                <ElFormItem label="请选择执行的客户流程（多选）" prop="flow_id">
                    <ElSelect v-model="formData.flow_id" placeholder="请选择执行的客户流程" filterable multiple>
                        <ElOption v-for="item in flowLists" :key="item.id" :label="item.flow_name" :value="item.id" />
                    </ElSelect>
                </ElFormItem>
            </ElForm>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { sopPushMemberAdd } from "@/api/person_wechat";
import Popup from "@/components/popup/index.vue";
import { ElForm } from "element-plus";
import useTask from "../_hooks/useTask";
import { SendWayEnum } from "../_enums";
const props = defineProps<{
    type: SendWayEnum;
}>();

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const popupRef = shallowRef<InstanceType<typeof Popup>>();

const formRef = shallowRef<InstanceType<typeof ElForm>>();
const formData = reactive({
    id: "",
    flow_id: [],
    push_type: 0,
});

const rules = {
    flow_id: [{ required: true, message: "请选择执行的客户流程", trigger: "blur" }],
};

const { flowLists, getFlowLists } = useTask();

const handleConfirm = async () => {
    await formRef.value?.validate();
    try {
        await sopPushMemberAdd(formData);
        emit("success");
        close();
    } catch (error) {
        feedback.msgError(error);
    }
};

const open = () => {
    popupRef.value?.open();
    getFlowLists();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(handleConfirm);

defineExpose({
    open,
    setFormData: (data) => {
        setFormData(data, formData);
        if (formData.flow_id) {
            formData.flow_id = formData.flow_id.map((item) => parseInt(item));
        }
    },
});
</script>
