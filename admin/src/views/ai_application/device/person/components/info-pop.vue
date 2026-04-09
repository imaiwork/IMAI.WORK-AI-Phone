<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="编辑IP信息"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="90px">
                <el-form-item label="头像" prop="avatar_url" required>
                    <material-picker :limit="1" v-model="formData.avatar_url"></material-picker>
                </el-form-item>
                <el-form-item label="IP名称" prop="persona_name">
                    <el-input class="ls-input" v-model="formData.persona_name" placeholder="请输入IP名称" clearable />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { updatePersona } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";
const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//表单数据
const formData = reactive<any>({
    id: "",
    persona_name: "",
    persona_type: "",
    avatar_url: "",
});

//校验规则
const rules = {
    persona_name: [
        {
            required: true,
            message: "请输入名称",
            trigger: ["blur"],
        },
    ],

    avatar_url: [
        {
            required: true,
            message: "请上传分类logo",
            trigger: ["blur"],
        },
    ],
};
//提交
const handleSubmit = async () => {
    await formRef.value?.validate();
    await updatePersona(formData);
    popupRef.value?.close();
    emit("success");
};

const handleClose = () => {
    emit("close");
};

const open = () => {
    popupRef.value?.open();
};

defineExpose({
    open,
    setFormData: (data: Record<any, any>) => setFormData(data, formData),
});
</script>
