<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form ref="formRef" :rules="rules" :model="formData" label-width="100px">
                <el-form-item label="模特" prop="result_image">
                    <material-picker v-model="formData.result_image" :limit="1" />
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { addDrawCase, editDrawCase } from "@/api/ai_application/draw/draw_case";
import Popup from "@/components/popup/index.vue";
import { useDictOptions } from "@/hooks/useDictOptions";

const emit = defineEmits(["success", "close"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = ref("");
//表单数据
const formData: any = ref({
    id: "",
    case_type: 4,
    result_image: "",
    status: "1", //状态 1-开启 0-关闭
});
//表单校验规则
const rules = {
    result_image: [{ required: true, message: "请上传图片", trigger: "blur" }],
};

//提交表单
const handleSubmit = async () => {
    try {
        await formRef.value?.validate();
        formData.value.id ? await editDrawCase(formData.value) : await addDrawCase(formData.value);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const handleClose = () => {
    emit("close");
};

const open = (type: string, value?: any) => {
    //初始化数据
    if (type == "add") {
        formData.value = {
            id: "",
            case_type: 4,
            result_image: "",
            status: 1, //状态 1-开启 0-关闭
        };
        popupTitle.value = "新增模特";
    } else if (type == "edit") {
        Object.keys(formData.value).map((item) => {
            formData.value[item] = value[item];
        });
        popupTitle.value = "编辑模特";
    }
    popupRef.value?.open();
};

defineExpose({
    open,
});
</script>
