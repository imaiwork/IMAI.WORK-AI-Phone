<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="生成卡密"
            :async="true"
            width="580px"
            :confirmLoading="isLock"
            @confirm="lockFn"
            @close="handleClose">
            <el-form ref="formRef" :rules="rules" :model="formData" label-width="110px">
                <el-form-item label="卡密类型" prop="type">
                    <el-radio-group v-model="formData.type">
                        <el-radio :label="3">算力值</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="算力值" prop="balance">
                    <el-input
                        class="w-full"
                        v-model="formData.balance"
                        type="number"
                        placeholder="请输入算力值"
                        :min="0"
                        :max="9999" />
                </el-form-item>
                <el-form-item label="卡密数量" prop="card_num">
                    <div class="flex-1">
                        <el-input
                            class="w-full"
                            v-model="formData.card_num"
                            placeholder="请输入卡密数量"
                            :min="0"
                            :max="500" />
                        <div class="form-tips !text-base">单次生成最多支持500张</div>
                    </div>
                </el-form-item>
                <el-form-item label="卡密生效时间" prop="valid_start_time">
                    <div class="w-full flex gap-x-2">
                        <daterange-picker
                            v-model:startTime="formData.valid_start_time"
                            v-model:endTime="formData.valid_end_time"
                            placeholder="请选择时间"
                            value-format="x"
                            type="daterange"
                            :second="true" />
                    </div>
                </el-form-item>
                <el-form-item label="生成规则" prop="type">
                    <el-radio-group v-model="formData.rule_type">
                        <el-radio :label="1">批次编号+随机字母</el-radio>
                        <el-radio :label="2">批次编号+随机数字</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input
                        class="w-full"
                        v-model="formData.remark"
                        type="textarea"
                        :autosize="{ minRows: 4, maxRows: 6 }"
                        placeholder="请输入备注"
                        maxlength="200"
                        show-word-limit />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { cardcodePackageLists, cardcodeAdd } from "@/api/marketing/redeem_code";
import type { CardCodeFormType } from "@/api/marketing/redeem_code";
import { useLockFn } from "@/hooks/useLockFn";

const emit = defineEmits(["success", "close"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//套餐列表
const packageList: any = ref({
    member_pckge: {},
    recharge_pckge: {},
});

const packageId = ref<number>();
//表单数据
const formData: any = ref<CardCodeFormType>({
    type: 3,
    card_num: "",
    valid_start_time: "",
    valid_end_time: "",
    remark: "",
    rule_type: 1,
    balance: "",
});
//表单校验规则
const rules = {
    balance: [
        {
            required: true,
            message: "请输入算力值",
            trigger: ["blur"],
        },
        {
            validator: (rule: any, value: any, callback: any) => {
                if (value > 9999) {
                    callback(new Error("算力值不能大于9999"));
                } else if (!/^\d+$/.test(value)) {
                    callback(new Error("算力值必须是纯数字"));
                } else {
                    callback();
                }
            },
            trigger: ["blur"],
        },
    ],
    card_num: [
        {
            required: true,
            message: "请输入卡密数量",
            trigger: ["blur"],
        },
    ],
    valid_start_time: [
        {
            required: true,
            message: "请选择生效时间",
            trigger: ["blur"],
        },
    ],
};

const priceList = computed(() => {
    if (packageId.value) {
        const i = packageList.value.member_pckge.findIndex((item: any) => item.id == packageId.value);
        return packageList.value.member_pckge[i].price_list;
    }
    return [];
});

//获取分类列表
const getPackageList = async () => {
    const data = await cardcodePackageLists();
    packageList.value = data;
};

//提交表单
const handleSubmit = async () => {
    try {
        await formRef.value?.validate();
        await cardcodeAdd(formData.value);
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const { lockFn, isLock } = useLockFn(handleSubmit);

const handleClose = () => {
    emit("close");
};

const open = () => {
    popupRef.value?.open();
    getPackageList();
};

defineExpose({ open });
</script>
