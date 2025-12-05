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
                <el-form-item label="分类描述" prop="description">
                    <el-input
                        class="ls-input"
                        v-model="formData.description"
                        placeholder="请输入分类描述"
                        maxlength="20"
                        show-word-limit
                        clearable />
                </el-form-item>
                <el-form-item label="分类logo" prop="logo" required>
                    <material-picker :limit="1" v-model="formData.logo"></material-picker>
                </el-form-item>

                <el-form-item label="分类父级" prop="pid" v-if="!isParent">
                    <el-select class="ls-input" v-model="formData.pid" placeholder="请选择" clearable>
                        <el-option
                            v-for="(item, index) in optionsData.categoryLists"
                            :key="index"
                            :disabled="formData.id == item.id"
                            :value="item.id"
                            :label="item.name"></el-option>
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
import { getAssistantCategoryList, assistantCategoryAdd, assistantCategoryEdit } from "@/api/ai_assistant/category";
import { useDictOptions } from "@/hooks/useDictOptions";
import Popup from "@/components/popup/index.vue";
const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const mode = ref("add");
//弹框标题
const popupTitle = computed(() => {
    return mode.value == "edit" ? "编辑类别" : "新增类别";
});
//表单数据
const formData = reactive<any>({
    id: "",
    pid: "",
    logo: "",
    name: "",
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
    mode.value == "edit" ? await assistantCategoryEdit(formData) : await assistantCategoryAdd(formData);
    popupRef.value?.close();
    emit("success");
};

// 是否父级
const isParent = ref(false);

const handleClose = () => {
    emit("close");
};

const open = (type = "add") => {
    mode.value = type;
    popupRef.value?.open();
};

const { optionsData } = useDictOptions<{ categoryLists: any[] }>({
    categoryLists: {
        api: getAssistantCategoryList,
        params: {
            pageSize: 99999,
        },
        transformData: (data) => data.lists,
    },
});

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    if (formData.pid == 0) {
        formData.pid = "";
        isParent.value = true;
    }
};

defineExpose({
    open,
    setFormData,
});
</script>
