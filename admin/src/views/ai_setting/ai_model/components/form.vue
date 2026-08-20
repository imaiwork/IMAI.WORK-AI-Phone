<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="headerTitle" @back="$router.back()" />
        </el-card>
        <el-form class="mt-4" ref="formRef" :model="formData" label-width="120px" :rules="formRules">
            <el-card shadow="never" class="!border-none">
                <div class="text-xl font-medium mb-[20px]">基础配置</div>
                <el-form-item label="图标" prop="logo" v-if="type != '3'">
                    <material-picker v-model="formData.logo" />
                </el-form-item>
                <el-form-item label="AI名称" prop="name">
                    <div class="w-[460px]">
                        <el-input
                            v-model.trim="formData.name"
                            placeholder="请输入AI名称"
                            maxlength="30"
                            show-word-limit />
                    </div>
                </el-form-item>
                <el-form-item label="是否启用" prop="is_enable">
                    <el-switch v-model="formData.is_enable" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-card>
            <el-card
                shadow="never"
                class="!border-none mt-4"
                v-if="(type == '1' || type == '3') && formData.models && formData.models.length">
                <div class="text-xl font-medium mb-[20px]">价格配置</div>
                <div
                    v-for="(model, index) in formData.models"
                    :key="model.id ?? index"
                    class="flex flex-wrap items-start gap-x-[40px] gap-y-2">
                    <el-form-item label="成本价" :prop="`models.${index}.cost_price`">
                        <div>
                            <div>
                                <el-input-number
                                    v-model="model.cost_price"
                                    :min="0"
                                    :precision="4"
                                    :step="0.0001"
                                    :controls="false"
                                    disabled
                                    class="w-[200px]"
                                    placeholder="请输入成本价" />
                                <span class="ml-2">{{ priceUnitLabel(model) }}</span>
                            </div>
                            <div class="form-tips !text-[14px]" v-if="model.cost_price_desc">
                                {{ model.cost_price_desc }}
                            </div>
                        </div>
                    </el-form-item>
                    <el-form-item label="出售价" :prop="`models.${index}.sell_price`" :rules="sellPriceRules">
                        <div>
                            <div>
                                <el-input-number
                                    v-model="model.sell_price"
                                    :min="0"
                                    :precision="4"
                                    :step="0.0001"
                                    :controls="false"
                                    class="w-[200px]"
                                    placeholder="请输入出售价" />
                                <span class="ml-2">{{ priceUnitLabel(model) }}</span>
                            </div>
                            <div class="form-tips !text-[14px]" v-if="model.sell_price_desc">
                                {{ model.sell_price_desc }}
                            </div>
                        </div>
                    </el-form-item>
                </div>
            </el-card>
        </el-form>
    </div>
</template>

<script setup lang="ts">
import type { PropType } from "vue";
const props = defineProps({
    modelValue: {
        type: Object as PropType<Record<string, any>>,
        required: true,
    },
    type: {
        type: String,
    },
    currentId: {
        type: [Number, String],
    },
    headerTitle: {
        type: String,
    },
});

const emit = defineEmits<{
    (event: "update:modelValue", value: any): void;
}>();

const formRef = shallowRef();
const formData = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const formRules = {
    logo: [
        {
            required: true,
            message: "请选择图标",
        },
    ],
    name: [
        {
            required: true,
            message: "请输入AI名称",
        },
    ],
};

const priceValidator = (label: string) => (_rule: any, value: any, callback: (err?: Error) => void) => {
    if (value === undefined || value === null || (value as any) === "") {
        return callback(new Error(`请输入${label}`));
    }
    callback();
};

const sellPriceRules = [{ validator: priceValidator("出售价"), trigger: "blur" }];

/** 与中台 quota_type + media_type 对齐：token / 张 / 秒 / 次 */
function priceUnitLabel(model: Record<string, any>) {
    if (model?.price_unit_label) return String(model.price_unit_label);
    if (model?.bill_unit === "sheet" || model?.quota_type_desc === "按张") return "算力/张";
    if (model?.bill_unit === "second" || model?.quota_type_desc === "按秒") return "算力/秒";
    if (model?.media_type === "video") return "算力/秒";
    if (model?.media_type === "image") return "算力/张";
    if (Number(model?.quota_type) === 1) return "算力/次";
    return "算力";
}

const validate = () => {
    return Promise.all([formRef.value?.validate()]);
};

defineExpose({
    validate,
});
</script>

<style scoped lang="scss"></style>
