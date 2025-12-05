<template>
	<view class="widget-select">
		<data-select
			class="w-full select-box"
			v-model="value"
			:placeholder="placeholder"
			:multiple="multiple"
			:localdata="normalizedOptions"></data-select>
	</view>
</template>

<script setup lang="ts">
const props = withDefaults(
	defineProps<{
		defaultValue: string;
		modelValue: any;
		options: string[];
		placeholder?: string;
		multiple?: boolean;
	}>(),
	{
		modelValue: "",
		placeholder: "请输入",
		multiple: false,
		options: () => [],
	}
);

const emit = defineEmits<{
	(event: "update:modelValue", value: any): void;
}>();
const value = computed({
	get() {
		return props.modelValue;
	},
	set(value) {
		emit("update:modelValue", value);
	},
});
const normalizedOptions = computed(() => {
	return props.options.map((item) => ({
		value: item,
		text: item,
	}));
});

const handleSelect = (e: any) => {
	value.value = e[0].value;
};

onBeforeMount(() => {
	if (props.defaultValue) {
		value.value = props.defaultValue;
	}
});
</script>
<style lang="scss" scoped>
.widget-select {
	background-color: #f7fbff;
}
</style>
