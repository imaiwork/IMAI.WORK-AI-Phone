<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="title" @back="$router.back()" />
        </el-card>
        <el-form
            class="ls-form mt-4"
            ref="formRef"
            :rules="rules"
            :model="formData"
            label-width="120px"
            v-loading="loading">
            <el-card shadow="never" class="!border-none">
                <div class="font-medium mb-[20px]">套餐信息</div>
                <el-form-item label="套餐价格" prop="price">
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.price"
                            type="number"
                            clearable
                            :min="0.01"
                            placeholder="请输入实际售价">
                            <template #append>元</template>
                        </el-input>
                    </div>
                </el-form-item>
                <el-form-item label="套餐名称" prop="name">
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.name"
                            type="text"
                            clearable
                            maxlength="20"
                            placeholder="请输入套餐名称">
                        </el-input>
                    </div>
                </el-form-item>
            </el-card>
            <el-card shadow="never" class="!border-none mt-4">
                <div class="font-medium mb-[20px]">套餐内容</div>
                <el-form-item label="算力值数量" prop="package_info.tokens">
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.package_info.tokens"
                            type="number"
                            clearable
                            :min="1"
                            placeholder="不填写默认为0">
                            <template #append>算力值</template>
                        </el-input>
                    </div>
                </el-form-item>
                <el-form-item label="排序">
                    <div class="w-[380px]">
                        <el-input-number v-model="formData.sort"> </el-input-number>
                        <div class="form-tips">默认为0，数值越大排越前面</div>
                    </div>
                </el-form-item>
            </el-card>
        </el-form>
        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { rechargeEdit, rechargeAdd, getRechargeDetail } from "@/api/marketing/recharge";
import feedback from "@/utils/feedback";
const formRef = shallowRef<FormInstance>();
const { query } = useRoute();
const router = useRouter();
const title = computed(() => {
    return query.mode == "edit" ? "编辑充值套餐" : "新增充值套餐";
});

//表单数据
const formData = reactive<any>({
    id: "",
    type: 1,
    price: "",
    sort: 0,
    name: "",
    package_info: {
        tokens: 1,
    },
});

//表单校验规则
const rules = {
    name: [
        {
            required: true,
            message: "请输入套餐名称",
        },
    ],
    price: [
        {
            required: true,
            message: "请输入套餐价格",
        },
    ],
    "package_info.tokens": [
        {
            required: true,
            message: "请输入算力值数量",
        },
    ],
};

//提交
const handleSave = async () => {
    await formRef.value?.validate();
    query.mode == "edit" ? await rechargeEdit(formData) : await rechargeAdd(formData);
    router.back();
};
const loading = ref(false);
const getDetail = async () => {
    if (!query.id) return;
    loading.value = true;
    const data = await getRechargeDetail({
        id: query.id,
    });
    Object.keys(formData).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key];
    });
    loading.value = false;
};

getDetail();
</script>
