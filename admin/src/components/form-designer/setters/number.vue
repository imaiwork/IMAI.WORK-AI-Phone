<script setup lang="ts">
import { computed } from "vue";
import { ElInputNumber } from "element-plus";
const props = defineProps<{
	modelValue?: number | string;
}>();
const emit = defineEmits<{
	(event: "update:modelValue", value: number | string): void;
}>();
const value = computed({
	get() {
		return props.modelValue;
	},
	set(value) {
		emit("update:modelValue", value!);
	},
});
</script>

<template>
	<ElInput v-model="value" type="number" />
</template>

<style lang="scss"></style>
