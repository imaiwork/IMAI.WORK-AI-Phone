<template>
    <div class="edit-popup">
        <popup ref="popupRef" :title="popupTitle" :async="true" width="550px" @confirm="handleSubmit" @close="close">
            <el-form ref="formRef" :rules="rules" :model="formData" label-width="100px">
                <el-form-item label="名称" prop="name">
                    <div class="w-80">
                        <el-input v-model="formData.name" show-word-limit placeholder="请输入名称" maxlength="30" />
                    </div>
                </el-form-item>
                <el-form-item label="背景音乐" prop="url">
                    <material-picker v-model="formData.url" type="audio" :limit="1" />
                </el-form-item>
                <el-form-item label="所属风格" prop="style">
                    <div class="flex items-center gap-2 flex-wrap w-80">
                        <div class="w-full">
                            <el-select class="!w-full" v-model="formData.style" placeholder="请选择所属风格">
                                <el-option
                                    v-for="(item, index) in styleMap"
                                    :key="index"
                                    :label="item.name"
                                    :value="parseInt(item.value.toString())" />
                            </el-select>
                        </div>
                    </div>
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
import { addMaterialMusic, updateMaterialMusic } from "@/api/ai_setting/music";
import Popup from "@/components/popup/index.vue";

const styleMap = [
    {
        name: "科技",
        value: "1",
    },
    {
        name: "悬疑",
        value: "2",
    },
    {
        name: "抒情",
        value: "3",
    },
    {
        name: "欢快",
        value: "4",
    },
    {
        name: "古典",
        value: "5",
    },
    {
        name: "跳跃",
        value: "6",
    },
];
const emit = defineEmits(["success", "close"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = ref("");

//表单数据
const formData: any = reactive({
    id: "",
    url: "",
    style: 1, //风格
    name: "", //内容
    status: 1, //状态 1-开启 0-关闭
});
//表单校验规则
const rules = {
    url: [
        {
            required: true,
            message: "请上传背景音乐",
        },
    ],
    name: [
        {
            required: true,
            message: "请输入名称",
            trigger: ["blur"],
        },
    ],
};

const open = (type: string) => {
    popupTitle.value = type === "add" ? "新增背景音乐" : "编辑背景音乐";
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

//提交表单
const handleSubmit = async () => {
    try {
        await formRef.value?.validate();
        formData.id ? await updateMaterialMusic(formData) : await addMaterialMusic(formData);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const setFormData = async (row: any) => {
    for (const key in formData) {
        if (row[key] != null && row[key] != undefined) {
            //@ts-ignore
            formData[key] = row[key];
        }
    }
};

defineExpose({
    open,
    setFormData,
});
</script>
