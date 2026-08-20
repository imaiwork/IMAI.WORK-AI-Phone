<template>
    <el-dialog
        v-model="visible"
        title="设备转移用户"
        width="520px"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        @closed="handleClosed">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
            <el-form-item label="设备号">
                <span>{{ device?.device_code || "-" }}</span>
            </el-form-item>
            <el-form-item label="当前用户">
                <span>{{ device?.nickname || "-" }}</span>
            </el-form-item>
            <el-form-item label="目标用户" prop="to_user_id">
                <UserPicker
                    title="目标用户"
                    v-model="formData.to_user_id"
                    v-model:select-data="selectData"
                    type="single">
                    <template #popup>
                        <div class="flex items-center">
                            <span class="mr-2" v-if="selectData?.id">
                                {{ selectData.nickname || "" }}
                                <template v-if="selectData.sn || selectData.account">
                                    ({{ selectData.sn || selectData.account }})
                                </template>
                            </span>
                            <el-button type="primary" link>选择用户</el-button>
                        </div>
                    </template>
                </UserPicker>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="visible = false">取消</el-button>
            <el-button type="primary" :loading="submitting" @click="handleConfirm">确定转移</el-button>
        </template>
    </el-dialog>
</template>

<script lang="ts" setup>
import type { FormInstance, FormRules } from "element-plus";
import UserPicker from "@/components/user-picker/index.vue";
import { deviceTransfer } from "@/api/ai_application/device";
import feedback from "@/utils/feedback";

const props = defineProps<{
    modelValue: boolean;
    device?: Record<string, any> | null;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "success"): void;
}>();

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const formRef = shallowRef<FormInstance>();
const selectData = ref<Record<string, any>>({});
const submitting = ref(false);

const formData = reactive({
    to_user_id: "" as number | string,
});

const rules: FormRules = {
    to_user_id: [
        {
            required: true,
            validator: (_rule, value, callback) => {
                if (value === "" || value === null || value === undefined) {
                    callback(new Error("请选择目标用户"));
                    return;
                }
                callback();
            },
            trigger: "change",
        },
    ],
};

// UserPicker 为自定义组件，选中后不会触发 form-item 的 change，需手动清校验
watch(
    () => formData.to_user_id,
    (val) => {
        if (val === "" || val === null || val === undefined) return;
        nextTick(() => formRef.value?.clearValidate("to_user_id"));
    }
);

const handleConfirm = async () => {
    if (!props.device?.id) {
        feedback.msgWarning("设备信息缺失");
        return;
    }
    await formRef.value?.validate();
    try {
        submitting.value = true;
        await deviceTransfer({
            device_id: props.device.id,
            to_user_id: formData.to_user_id,
        });
        visible.value = false;
        emit("success");
    } finally {
        submitting.value = false;
    }
};

const handleClosed = () => {
    formData.to_user_id = "";
    selectData.value = {};
    formRef.value?.resetFields();
};

watch(
    () => props.modelValue,
    (val) => {
        if (!val) return;
        formData.to_user_id = "";
        selectData.value = {};
        nextTick(() => formRef.value?.clearValidate());
    },
);
</script>
