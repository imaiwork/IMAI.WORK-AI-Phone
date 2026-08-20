<template>
    <div class="quota-input flex items-center gap-3 flex-wrap">
        <el-radio-group v-model="mode" @change="onModeChange">
            <el-radio value="custom">自定义数量</el-radio>
            <el-radio value="unlimited">不限制</el-radio>
            <el-radio value="banned">禁止</el-radio>
        </el-radio-group>
        <el-input-number
            v-if="mode === 'custom'"
            v-model="customValue"
            :min="1"
            :max="999999"
            :precision="0"
            controls-position="right"
            @change="onCustomChange" />
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{ modelValue: number }>();
const emit = defineEmits<{ (e: "update:modelValue", v: number): void }>();

const mode = ref<"custom" | "unlimited" | "banned">("unlimited");
const customValue = ref<number>(1);

const syncFromValue = (v: number) => {
    const n = Number(v);
    if (n === -1) {
        mode.value = "banned";
    } else if (n === 0 || isNaN(n)) {
        mode.value = "unlimited";
    } else {
        mode.value = "custom";
        customValue.value = Math.max(1, Math.floor(n));
    }
};

watch(() => props.modelValue, syncFromValue, { immediate: true });

const emitValue = () => {
    if (mode.value === "banned") emit("update:modelValue", -1);
    else if (mode.value === "unlimited") emit("update:modelValue", 0);
    else emit("update:modelValue", Math.max(1, Math.floor(customValue.value || 1)));
};
const onModeChange = () => {
    if (mode.value === "custom" && (!customValue.value || customValue.value < 1)) {
        customValue.value = 1;
    }
    emitValue();
};
const onCustomChange = () => emitValue();
</script>
