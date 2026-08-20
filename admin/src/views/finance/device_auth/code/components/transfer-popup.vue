<template>
    <el-dialog
        v-model="visible"
        title="转移CDK"
        width="520px"
        append-to-body
        destroy-on-close
        :close-on-click-modal="false"
        @closed="handleClosed">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="100px">
            <el-form-item label="CDK">
                <span>{{ cdk?.code || "-" }}</span>
            </el-form-item>
            <el-form-item label="当前拥有者">
                <span>{{ cdk?.owner_user_name || "-" }}</span>
            </el-form-item>
            <el-form-item label="目标用户" prop="user_id">
                <UserPicker
                    title="目标用户"
                    v-model="formData.user_id"
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
import { deviceAuthCodeTransfer } from "@/api/ai_application/device_auth";
import feedback from "@/utils/feedback";

const props = defineProps<{
    modelValue: boolean;
    cdk?: Record<string, any> | null;
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
    user_id: "" as number | string,
});

const rules: FormRules = {
    user_id: [
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
    () => formData.user_id,
    (val) => {
        if (val === "" || val === null || val === undefined) return;
        nextTick(() => formRef.value?.clearValidate("user_id"));
    }
);

const handleConfirm = async () => {
    if (!props.cdk?.id) {
        feedback.msgWarning("CDK信息缺失");
        return;
    }
    await formRef.value?.validate();
    try {
        submitting.value = true;
        await deviceAuthCodeTransfer({
            id: props.cdk.id,
            user_id: formData.user_id,
        });
        visible.value = false;
        emit("success");
    } finally {
        submitting.value = false;
    }
};

const handleClosed = () => {
    formData.user_id = "";
    selectData.value = {};
    formRef.value?.resetFields();
};

watch(
    () => props.modelValue,
    (val) => {
        if (!val) return;
        formData.user_id = "";
        selectData.value = {};
        nextTick(() => formRef.value?.clearValidate());
    }
);
</script>
