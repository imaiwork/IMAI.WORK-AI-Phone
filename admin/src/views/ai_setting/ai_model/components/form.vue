<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="headerTitle" @back="$router.back()" />
        </el-card>
        <el-form class="mt-4" ref="formRef" :model="formData" label-width="120px" :rules="formRules">
            <el-card shadow="never" class="!border-none">
                <div class="text-xl font-medium mb-[20px]">基础配置</div>
                <el-form-item label="图标" prop="logo" v-if="type == '4'">
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

const validate = () => {
    return Promise.all([formRef.value?.validate()]);
};

defineExpose({
    validate,
});
</script>

<style scoped lang="scss"></style>
