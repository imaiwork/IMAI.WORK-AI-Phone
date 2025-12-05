<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="90px">
                <el-form-item label="分类名称" prop="name">
                    <el-input class="ls-input" v-model="formData.name" placeholder="请输入分类名称" clearable />
                </el-form-item>
                <!-- <el-form-item label="分类描述" prop="description">
                    <el-input
                        class="ls-input"
                        v-model="formData.description"
                        placeholder="请输入分类描述"
                        maxlength="20"
                        show-word-limit
                        clearable />
                </el-form-item> -->
                <!-- <el-form-item label="分类logo" prop="logo" required>
                    <material-picker :limit="1" v-model="formData.logo"></material-picker>
                </el-form-item> -->
                <!-- type -->
                <el-form-item label="分类类型" prop="type">
                    <el-select v-model="formData.type" placeholder="请选择分类类型">
                        <el-option label="AI智能体" :value="1"></el-option>
                        <el-option label="Coze智能体" :value="2"></el-option>
                        <el-option label="Coze工作流" :value="3"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input class="ls-input" v-model="formData.sort" />
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
import { addCate, editCate } from "@/api/ai_application/agent/cate";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";
const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const mode = ref("add");
//弹框标题
const popupTitle = computed(() => {
    return mode.value == "edit" ? "编辑分类" : "新增分类";
});
//表单数据
const formData = reactive<any>({
    id: "",
    logo: "",
    name: "",
    type: 1,
    description: "",
    sort: 0,
    status: 1,
});

//校验规则
const rules = {
    name: [
        {
            required: true,
            message: "请输入名称",
            trigger: ["blur"],
        },
    ],
    description: [
        {
            required: true,
            message: "请输入描述",
            trigger: ["blur"],
        },
    ],
    logo: [
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
    mode.value == "edit" ? await editCate(formData) : await addCate(formData);
    popupRef.value?.close();
    emit("success");
};

const handleClose = () => {
    emit("close");
};

const open = (type = "add") => {
    mode.value = type;
    popupRef.value?.open();
};

defineExpose({
    open,
    setFormData: (data: Record<any, any>) => setFormData(data, formData),
});
</script>
