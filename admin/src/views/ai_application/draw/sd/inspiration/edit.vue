<template>
    <div v-loading="loading">
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="$route.query.id as string ? '编辑模型' : '新增模型'" @back="$router.back()" />
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="120px">
                <el-form-item label="模型图片" prop="pic">
                    <material-picker v-model="formData.pic" :limit="1" />
                </el-form-item>
                <el-form-item label="提示词" prop="title">
                    <div class="w-[380px]">
                        <el-input placeholder="请输入提示词" type="textarea" :rows="5" v-model="formData.title" />
                    </div>
                </el-form-item>
                <el-form-item label="所属分类">
                    <div class="flex items-center gap-2 flex-wrap w-full">
                        <div class="flex gap-4 w-[280px]">
                            <el-select v-model="formData.cid" placeholder="请选择所属分类">
                                <el-option
                                    v-for="(item, index) in optionsData.categoryList"
                                    :key="index"
                                    :label="item.title"
                                    :value="item.id" />
                            </el-select>
                            <div class="flex items-center">
                                <el-button type="primary" link>
                                    <router-link to="/ai_application/sd/model-category">新建分类</router-link>
                                </el-button>
                                <el-divider direction="vertical"></el-divider>
                                <el-button type="primary" link @click="refresh"> 刷新 </el-button>
                            </div>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input-number v-model="formData.sort" />
                        <div class="form-tips">默认为0，数值越大越排前面</div>
                    </div>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </el-card>
        <footer-btns>
            <el-button type="primary" @click="lockSubmit" :loading="isLock">保存</el-button>
        </footer-btns>
    </div>
</template>
<script lang="ts" setup>
import { ElMessage, type FormInstance, type FormRules } from "element-plus";
import { addModel, editModel, getModelDetail, getModelCategoryList } from "@/api/ai_application/draw/draw_sd";
import { useDictOptions } from "@/hooks/useDictOptions";
import useMultipleTabs from "@/hooks/useMultipleTabs";
import { useLockFn } from "@/hooks/useLockFn";

const { removeTab } = useMultipleTabs();

const route = useRoute();
const router = useRouter();
const formRef = shallowRef<FormInstance>();
const loading = ref(false);

const formData = reactive<{
    id?: string | number;
    title: string;
    cid: string;
    sort: number;
    status: number;
    pic: string;
}>({
    id: "",
    title: "", //模型名称
    cid: "", // 分类id
    sort: 1, // 排序
    status: 1, // 状态
    pic: "", // 模型图片
});

//表单校验规则
const rules: FormRules = {
    title: [
        {
            required: true,
            message: "请选择提示词",
        },
    ],
    cid: [
        {
            required: true,
            message: "请选择所属类目",
        },
    ],
    pic: [
        {
            required: true,
            message: "请上传模型图片",
        },
    ],
};

const { optionsData, refresh } = useDictOptions<{ categoryList: any[] }>({
    categoryList: {
        api: getModelCategoryList,
        transformData(data) {
            return data.lists;
        },
    },
});

/**
 * 提交表单
 */
const handleSave = async () => {
    await formRef.value?.validate();
    route.query.id ? await editModel(formData) : await addModel(formData);
    removeTab();
    router.back();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSave);

/**
 * 获取模型详情
 */
const getDetails = async () => {
    loading.value = true;
    try {
        const data = await getModelDetail({
            id: route.query.id,
        });
        setFormData(data);
    } finally {
        loading.value = false;
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

onMounted(async () => {
    route.query.id && getDetails();
});
</script>
