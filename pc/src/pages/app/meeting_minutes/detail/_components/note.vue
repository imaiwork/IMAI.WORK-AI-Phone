<template>
    <div class="h-full w-full">
        <Editor v-model="value" />
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: string | undefined | null;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const value = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});
</script>
