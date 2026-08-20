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
            label-width="140px"
            v-loading="loading">
            <el-card shadow="never" class="!border-none">
                <div class="font-medium mb-[20px]">套餐信息</div>
                <el-form-item label="套餐价格" prop="price">
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.price"
                            v-number-input="{ min: 0.01, max: 100000000, decimal: 2 }"
                            clearable
                            placeholder="请输入实际售价">
                            <template #append>元</template>
                        </el-input>
                        <div v-if="isVirtualPay" class="form-tips">
                            当前为小程序虚拟支付：此处价格（元）须与微信小程序后台「虚拟支付 → 道具」中该道具售价保持一致（微信侧单位为分，如 100 元对应 10000 分）；修改后请同步更新微信道具定价并重新发布，否则会支付失败。
                        </div>
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
                <el-form-item
                    v-if="isVirtualPay"
                    label="虚拟支付产品ID"
                    prop="product_id"
                    required>
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.product_id"
                            clearable
                            maxlength="64"
                            placeholder="请输入微信小程序虚拟支付产品ID" />
                        <div class="form-tips">
                            需与小程序后台「虚拟支付 → 道具」中的 productId 完全一致；上方套餐价格也必须与该道具售价同步
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>
            <el-card shadow="never" class="!border-none mt-4">
                <div class="font-medium mb-[20px]">套餐内容</div>
                <el-form-item label="算力值数量" prop="package_info.tokens">
                    <div class="w-[380px]">
                        <el-input
                            v-model="formData.package_info.tokens"
                            v-number-input="{ min: 1, max: 100000000, decimal: 2 }"
                            clearable
                            placeholder="不填写默认为0">
                            <template #append>算力值</template>
                        </el-input>
                    </div>
                </el-form-item>
                <el-form-item label="排序">
                    <div class="w-[380px]">
                        <el-input-number v-model="formData.sort" :min="0" :max="9999"> </el-input-number>
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
import type { FormInstance, FormRules } from "element-plus";
import { rechargeEdit, rechargeAdd, getRechargeDetail } from "@/api/marketing/recharge";
import { getPayConfig, getPayConfigLists } from "@/api/setting/pay";

const WECHAT_PAY_WAY = 2;
const MNP_PAY_TYPE_VIRTUAL = 2;

const formRef = shallowRef<FormInstance>();
const { query } = useRoute();
const router = useRouter();
const title = computed(() => {
    return query.mode == "edit" ? "编辑充值套餐" : "新增充值套餐";
});

const isVirtualPay = ref(false);

const formData = reactive<any>({
    id: "",
    type: 1,
    price: "",
    sort: 0,
    name: "",
    product_id: "",
    status: 1,
    package_info: {
        tokens: 1,
    },
});

const rules = computed<FormRules>(() => ({
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
    product_id: [
        {
            validator: (_rule, value, callback) => {
                if (!isVirtualPay.value) {
                    callback();
                    return;
                }
                if (value === undefined || value === null || String(value).trim() === "") {
                    callback(new Error("请输入虚拟支付产品ID"));
                    return;
                }
                callback();
            },
            trigger: ["blur", "change"],
        },
    ],
    "package_info.tokens": [
        {
            required: true,
            message: "请输入算力值数量",
        },
    ],
}));

const handleSave = async () => {
    await formRef.value?.validate();
    const payload = {
        ...formData,
        product_id: String(formData.product_id || "").trim(),
    };
    query.mode == "edit" ? await rechargeEdit(payload) : await rechargeAdd(payload);
    router.back();
};

const loading = ref(false);

const loadVirtualPayFlag = async () => {
    try {
        const { lists } = await getPayConfigLists();
        const wechat = (lists || []).find((item: any) => Number(item.pay_way) === WECHAT_PAY_WAY);
        if (!wechat?.id) {
            isVirtualPay.value = false;
            return;
        }
        const detail = await getPayConfig({ id: wechat.id });
        isVirtualPay.value = Number(detail?.config?.mnp_pay_type) === MNP_PAY_TYPE_VIRTUAL;
    } catch {
        isVirtualPay.value = false;
    }
};

const getDetail = async () => {
    if (!query.id) return;
    loading.value = true;
    try {
        const data = await getRechargeDetail({
            id: query.id,
        });
        Object.keys(formData).forEach((key) => {
            if (data[key] != null && data[key] != undefined) {
                //@ts-ignore
                formData[key] = data[key];
            }
        });
        formData.product_id = data.product_id || "";
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await Promise.all([loadVirtualPayFlag(), getDetail()]);
});
</script>
