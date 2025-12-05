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
                <el-form-item label="封面" prop="pic">
                    <material-picker v-model="formData.pic" :limit="1" />
                </el-form-item>
                <el-form-item label="关键词" prop="title">
                    <div class="w-80">
                        <el-input v-model="formData.title" show-word-limit placeholder="请输入关键词" />
                    </div>
                </el-form-item>
                <el-form-item label="所属类目" prop="cid">
                    <div class="flex items-center gap-2 flex-wrap w-80">
                        <div class="w-full">
                            <el-select class="!w-full" v-model="formData.cid" placeholder="请选择所属分类">
                                <el-option
                                    v-for="(item, index) in optionsData.categoryList"
                                    :key="index"
                                    :label="item.title"
                                    :value="item.id" />
                            </el-select>
                        </div>
                        <div class="flex items-center">
                            <el-button type="primary" link>
                                <router-link :to="getRoutePath('ai_application.draw_sd/prompt_category')"
                                    >新建分类</router-link
                                >
                            </el-button>
                            <el-divider direction="vertical"></el-divider>
                            <el-button type="primary" link @click="refresh"> 刷新 </el-button>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div class="w-80">
                        <el-input type="text" v-model="formData.sort" :min="0" :max="9999" />
                        <div class="form-tips">默认为0，数据越大越排前面</div>
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
import { addPrompt, editPrompt, getDrawCategoryList } from "@/api/ai_application/draw/draw_sd";
import Popup from "@/components/popup/index.vue";
import { useDictOptions } from "@/hooks/useDictOptions";
import { getRoutePath } from "@/router";

const emit = defineEmits(["success", "close"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = ref("");
//分类列表
const categoryList: any = ref([]);
//表单数据
const formData: any = ref({
    id: "",
    pic: "",
    cid: "", //分类
    title: "", //内容
    sort: 0, //排序
    status: "1", //状态 1-开启 0-关闭
});
//表单校验规则
const rules = {
    pic: [
        {
            required: true,
            message: "请上传模型图片",
        },
    ],
    title: [
        {
            required: true,
            message: "请输入关键词",
            trigger: ["blur"],
        },
    ],
    cid: [
        {
            required: true,
            message: "请选择所属类目",
            trigger: ["blur"],
        },
    ],
};

const { optionsData, refresh } = useDictOptions<{ categoryList: any[] }>({
    categoryList: {
        api: getDrawCategoryList,
        transformData(data) {
            return data.lists;
        },
    },
});

//提交表单
const handleSubmit = async () => {
    try {
        await formRef.value?.validate();
        formData.value.id ? await editPrompt(formData.value) : await addPrompt(formData.value);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const handleClose = () => {
    emit("close");
};

const open = (type: string, value: any) => {
    //初始化数据
    if (type == "add") {
        formData.value = {
            id: "",
            pic: "",
            cid: "", //分类
            title: "", //内容
            sort: 0, //排序
            status: 1, //状态 1-开启 0-关闭
        };
        popupTitle.value = "新增关键词";
    } else if (type == "edit") {
        Object.keys(formData.value).map((item) => {
            formData.value[item] = value[item];
        });
        popupTitle.value = "编辑关键词";
    }
    popupRef.value?.open();
};

defineExpose({
    open,
});
</script>
