<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="编辑等级名称"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="90px">
                <el-form-item label="等级名称" prop="name">
                    <el-input
                        class="ls-input"
                        v-model="formData.name"
                        placeholder="请输入等级名称"
                        clearable
                        maxlength="10" />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { setAgentGradeConfig } from "@/api/marketing/agent";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";

const emit = defineEmits(["success", "close"]);

const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const gradeData = ref<any[]>([]);

// 表单数据
const formData = reactive<any>({
    level: "",
    name: "",
});

// 校验规则
const rules = {
    name: [
        {
            required: true,
            message: "请输入等级名称",
            trigger: ["blur"],
        },
    ],
};

// 提交
const handleSubmit = async () => {
    await formRef.value?.validate();
    gradeData.value.forEach((item) => {
        if (item.level == formData.level) {
            item.name = formData.name;
        }
    });

    await setAgentGradeConfig({
        config: gradeData.value,
    });
    popupRef.value?.close();
    emit("success");
};

const handleClose = () => {
    emit("close");
};

const open = (data: any[]) => {
    gradeData.value = data;
    popupRef.value?.open();
};

defineExpose({
    open,
    setFormData: (data: Record<any, any>) => {
        setFormData(data, formData);
    },
});
</script>
