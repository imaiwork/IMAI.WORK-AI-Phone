<template>
	<u-form
		:model="formData"
		ref="formRef"
		v-bind="{ ...$attrs, ...props }"
		:error-type="['toast']">
		<u-form-item
			v-for="item in formLists"
			:key="item.id"
			:prop="item.props.field"
			:label="item.props.title"
			:borderBottom="borderBottom">
			<template v-if="item.name === 'WidgetInput'">
				<widget-input
					class="w-full"
					v-bind="item.props"
					v-model="formData[item.props.field]" />
			</template>
			<template v-else-if="item.name === 'WidgetTextarea'">
				<widget-textarea
					class="w-full"
					v-bind="item.props"
					v-model="formData[item.props.field]" />
			</template>
			<template
				v-else-if="
					['WidgetSelect', 'WidgetMultiple'].includes(item.name)
				">
				<widget-select
					class="w-full"
					v-bind="item.props"
					:multiple="item.name === 'WidgetMultiple'"
					v-model="formData[item.props.field]" />
			</template>
			<template v-else-if="item.name === 'WidgetFile'">
				<widget-file
					class="w-full"
					v-bind="item.props"
					v-model="formData[item.props.field]" />
			</template>
		</u-form-item>
	</u-form>
</template>

<script setup lang="ts">
import WidgetInput from "./widgets/input.vue";
import WidgetTextarea from "./widgets/textarea.vue";
import WidgetSelect from "./widgets/select.vue";
import WidgetFile from "./widgets/file.vue";

interface Props {
	formLists?: any[];
	modelValue: Record<string, any>;
	labelPosition?: "left" | "top";
	borderBottom?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
	formLists: () => [],
	labelPosition: "top",
	borderBottom: false,
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
const formRules = ref<any>({});

const validate = async () => {
	return new Promise((resolve, reject) => {
		formRef.value?.validate((valid: boolean) => {
			if (valid) {
				resolve(true);
			} else {
				reject();
			}
		});
	});
};

watch(
	() => props.formLists,
	(value) => {
		formRules.value = value?.reduce((prev: any, item: any) => {
			if (item.props.isRequired) {
				prev[item.props.field] = [
					{
						type: "any",
						required: true,
						message: `${item.props.title}不能为空`,
						trigger: "blur,change",
					},
				];
			}
			return prev;
		}, {});
		nextTick(() => {
			formRef.value?.setRules(formRules.value);
		});
	},
	{
		immediate: true,
	}
);

defineExpose({
	validate,
});
</script>
