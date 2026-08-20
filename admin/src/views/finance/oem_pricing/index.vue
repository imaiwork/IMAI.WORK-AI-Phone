<template>
    <div class="oem-pricing">
        <el-card class="!border-none" shadow="never">
            <div class="flex items-center gap-3 mb-1">
                <span class="text-lg font-bold">OEM 收费配置</span>
                <el-tag type="info" size="small" effect="light">企业升级 OEM 的定价</el-tag>
            </div>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-form :model="form" label-width="140px" class="max-w-[560px] pt-2">
                <el-form-item label="售价（算力）" required>
                    <el-input v-model="form.oem_upgrade_price" v-number-input="{ decimal: 2 }" class="!w-[240px]" />
                    <div class="text-xs text-gray-400 mt-1">企业用户升级 OEM 需支付的算力，你的定价</div>
                </el-form-item>
                <el-form-item>
                    <el-button v-perms="['oem.oem/saveOemPricing']" type="primary" :loading="saving" @click="onSave">
                        保存配置
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="oemPricing">
import { getOemPricing, saveOemPricing } from "@/api/marketing/oem";
import feedback from "@/utils/feedback";

const form = reactive({ oem_cost_price: 0, oem_upgrade_price: 5000, oem_charge_cost: 1 });
const saving = ref(false);
const profit = computed(() => Number(form.oem_upgrade_price || 0) - Number(form.oem_cost_price || 0));

const load = async () => {
    const res: any = await getOemPricing();
    form.oem_cost_price = Number(res.oem_cost_price) || 0;
    form.oem_upgrade_price = Number(res.oem_upgrade_price) || 5000;
    form.oem_charge_cost = Number(res.oem_charge_cost) ?? 1;
};

const onSave = async () => {
    if (form.oem_upgrade_price <= 0) return feedback.msgError("售价必须大于0");
    if (form.oem_upgrade_price < form.oem_cost_price) return feedback.msgError("售价不得低于成本价");
    saving.value = true;
    try {
        await saveOemPricing({ ...form });
        feedback.msgSuccess("保存成功");
    } finally {
        saving.value = false;
    }
};

onMounted(load);
</script>
