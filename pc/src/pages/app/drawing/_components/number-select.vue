<template>
    <div class="flex items-center justify-between mb-4 px-1">
        <div class="text-[13px] font-[900] text-[#64748B] uppercase tracking-widest">生成数量 Count</div>
        <span class="text-xs font-black text-primary bg-[#F5F7FF] px-2 py-0.5 rounded-md">{{ count }} 张</span>
    </div>
    <div class="px-1">
        <ElSlider v-model="count" :min="1" :max="4" :step="1" tooltip-class="custom-slider-tooltip" />
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: number): void;
}>();

const count = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});
</script>

<style scoped></style>
