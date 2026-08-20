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
                <el-form-item label="分类名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入分类名称" />
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input class="ls-input" v-model="formData.sort" :min="0" :max="9999" />
                        <div class="form-tips">默认为0，数值越大排越前面</div>
                    </div>
                </el-form-item>
                <el-form-item label="状态" prop="sort">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { addTaskTemplateCate, editTaskTemplateCate } from "@/api/ai_application/device/task_template";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";

const emit = defineEmits(["success", "close"]);
const mode = ref<"add" | "edit">("add");
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = computed(() => {
    return mode.value == "add" ? "新增分类" : "编辑分类";
});
//表单数据
const formData: any = reactive({
    id: "",
    name: "",
    sort: 0,
    status: 1, //状态 1-开启 0-关闭
});
//表单校验规则
const rules = {
    name: [{ required: true, message: "请输入分类名称", trigger: "blur" }],
};

//提交表单
const handleSubmit = async () => {
    try {
        await formRef.value?.validate();
        formData.id ? await editTaskTemplateCate(formData) : await addTaskTemplateCate(formData);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const handleClose = () => {
    emit("close");
};

const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value?.open();
};

defineExpose({
    open,
    setFormData: (data: any) => {
        setFormData(data, formData);
    },
});
</script>
