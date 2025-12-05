<script setup lang="ts">
import { computed } from "vue";
import { ElInput } from "element-plus";
const props = defineProps<{
	modelValue?: string;
	disabled?: boolean;
}>();
const emit = defineEmits<{
	(event: "update:modelValue", value: string): void;
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
	<ElInput v-model="value" :disabled="disabled" />
</template>
