<template>
    <popup ref="popupRef" :title="title" async width="500px" @confirm="handleSave" @close="close">
        <el-form class="ls-form" ref="formRef" :rules="rules" :model="formData" label-width="120px">
            <el-form-item label="套餐名称" prop="name">
                <div class="w-[380px]">
                    <el-input v-model="formData.name" type="text" clearable maxlength="20" placeholder="请输入套餐名称">
                    </el-input>
                </div>
            </el-form-item>
            <el-form-item label="算力值数量" prop="tokens">
                <div class="w-[380px]">
                    <el-input v-model="formData.tokens" type="number" clearable :min="1" placeholder="不填写默认为0">
                        <template #append>算力值</template>
                    </el-input>
                </div>
            </el-form-item>
            <el-form-item label="状态" prop="status">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="排序">
                <div class="w-[380px]">
                    <el-input-number v-model="formData.sort" :min="0" :max="9999"> </el-input-number>
                    <div class="form-tips">默认为0，数值越大排越前面</div>
                </div>
            </el-form-item>
        </el-form>
    </popup>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import { addAgentPackage, editAgentPackage } from "@/api/marketing/agent";
import { setFormData } from "@/utils/util";

const emit = defineEmits(["close", "success"]);

const formRef = shallowRef<FormInstance>();
const { query } = useRoute();
const router = useRouter();
const title = computed(() => {
    return query.mode == "edit" ? "编辑充值套餐" : "新增充值套餐";
});

const mode = ref("add");

//表单数据
const formData = reactive<any>({
    id: "",
    sort: 0,
    status: 1,
    name: "",
    tokens: 1,
});

//表单校验规则
const rules = {
    name: [
        {
            required: true,
            message: "请输入套餐名称",
        },
    ],
    tokens: [
        {
            required: true,
            message: "请输入算力值数量",
        },
    ],
};

//提交
const handleSave = async () => {
    await formRef.value?.validate();
    mode.value == "edit" ? await editAgentPackage(formData) : await addAgentPackage(formData);
    emit("success");
    close();
};

const popupRef = ref();

const open = (type: string) => {
    mode.value = type;
    popupRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    close,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>
