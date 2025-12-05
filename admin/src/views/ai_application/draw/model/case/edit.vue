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
                <template v-if="props.caseType == 0">
                    <el-form-item label="类型" prop="case_type">
                        <el-select v-model="formData.case_type" placeholder="请选择类型" @change="handleChangeType">
                            <el-option label="上下装" :value="0" />
                            <el-option label="连衣裙" :value="1" />
                        </el-select>
                    </el-form-item>
                    <template v-if="formData.case_type == 0">
                        <el-form-item label="上衣" prop="params.images[0]">
                            <material-picker v-model="formData.params.images[0]" :limit="1" />
                        </el-form-item>
                        <el-form-item label="下装" prop="params.images[1]">
                            <material-picker v-model="formData.params.images[1]" :limit="1" />
                        </el-form-item>
                        <el-form-item label="模特" prop="params.images[2]">
                            <material-picker v-model="formData.params.images[2]" :limit="1" />
                        </el-form-item>
                    </template>
                    <template v-if="formData.case_type == 1">
                        <el-form-item label="连衣裙" prop="params.images[0]">
                            <material-picker v-model="formData.params.images[0]" :limit="1" />
                        </el-form-item>
                        <el-form-item label="模特" prop="params.images[1]">
                            <material-picker v-model="formData.params.images[1]" :limit="1" />
                        </el-form-item>
                    </template>
                </template>
                <el-form-item label="案例图片" prop="result_image">
                    <material-picker v-model="formData.result_image" :limit="1" />
                </el-form-item>
                <el-form-item label="案例描述" prop="params.text" v-if="props.caseType == 3">
                    <el-input
                        v-model="formData.params.text"
                        type="textarea"
                        class="w-[300px]"
                        rows="5"
                        placeholder="请输入案例描述" />
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

const props = defineProps({
    caseType: {
        type: Number,
        default: 0,
    },
});

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
    case_type: 0,
    params: {
        text: "",
        images: ["", ""],
    },
    result_image: "",
    status: "1", //状态 1-开启 0-关闭
});
//表单校验规则
const rules = {
    params: {
        images: [
            { required: true, message: "请上传图片", trigger: "blur" },
            { required: true, message: "请上传图片", trigger: "blur" },
            { required: true, message: "请上传图片", trigger: "blur" },
        ],
        text: [{ required: true, message: "请输入案例描述", trigger: "blur" }],
    },
    result_image: [{ required: true, message: "请上传图片", trigger: "blur" }],
};

const handleChangeType = (value: number) => {
    formData.value.case_type = value;
    formData.value.params.images = ["", "", ""];
};

//提交表单
const handleSubmit = async () => {
    try {
        if (props.caseType == 3) {
            formData.value.case_type = props.caseType;
        }
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
            case_type: 0,
            params: {
                text: "",
                images: ["", ""],
            },
            result_image: "",
            status: 1, //状态 1-开启 0-关闭
        };
        popupTitle.value = "新增优秀案例";
    } else if (type == "edit") {
        Object.keys(formData.value).map((item) => {
            formData.value[item] = value[item];
        });
        popupTitle.value = "编辑优秀案例";
    }
    popupRef.value?.open();
};

defineExpose({
    open,
});
</script>
