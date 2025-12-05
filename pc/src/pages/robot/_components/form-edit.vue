<template>
	<div class="edit-popup">
		<popup
			ref="popupRef"
			:title="popupTitle"
			:async="true"
			width="550px"
			@confirm="handleSubmit"
			@close="handleClose">
			<ElForm
				ref="formRef"
				:model="formData"
				label-width="100px"
				label-position="top"
				:rules="formRules">
				<ElFormItem label="字段类型">
					<ElSelect
						v-model="formData.name"
						placeholder="请输入字段类型"
						clearable>
						<ElOption
							v-for="(value, key) in FieldTypeMap"
							:key="key"
							:label="value"
							:value="key"></ElOption>
					</ElSelect>
				</ElFormItem>
				<ElFormItem label="字段名称" prop="title">
					<ElInput
						v-model="formData.title"
						placeholder="请输入字段名称"
						maxlength="100"
						clearable />
				</ElFormItem>
				<ElFormItem label="字段值" prop="field">
					<ElInput
						v-model="formData.field"
						placeholder="字段值允许包含数字、字母、下划线_、美元符号$，且必须唯一"
						maxlength="100"
						clearable />
				</ElFormItem>
				<ElFormItem label="占位文字" prop="placeholder">
					<ElInput
						v-model="formData.placeholder"
						placeholder="请输入占位文字"
						maxlength="100"
						clearable />
				</ElFormItem>
				<ElFormItem
					label="默认行数"
					v-if="formData.name == FieldTypeEnum.TEXTAREA">
					<ElInput
						v-model="formData.rows"
						type="number"
						placeholder="请输入默认行数"
						:max="20" />
				</ElFormItem>
				<ElFormItem
					label="选项"
					v-if="
						[FieldTypeEnum.MULTIPLE, FieldTypeEnum.SELECT].includes(
							formData.name
						)
					">
					<div class="flex gap-2">
						<ElTag
							v-for="tag in formData.options"
							:key="tag"
							closable
							:disable-transitions="false"
							@close="handleOptionClose(tag)">
							{{ tag }}
						</ElTag>
						<ElInput
							v-if="inputVisible"
							ref="InputRef"
							v-model="inputValue"
							size="small"
							@keyup.enter="handleInputConfirm"
							maxlength="50"
							@blur="handleInputConfirm" />
						<ElButton v-else size="small" @click="showInput">
							+ 添加选项
						</ElButton>
					</div>
				</ElFormItem>
				<ElFormItem label="最大输入长度" prop="maxlength" v-else>
					<ElInput
						v-model="formData.maxlength"
						type="number"
						placeholder="请输入最大输入长度"
						:max="200" />
				</ElFormItem>
				<ElFormItem label="是否必填">
					<ElSwitch v-model="formData.isRequired"></ElSwitch>
				</ElFormItem>
			</ElForm>
		</popup>
	</div>
</template>
<script lang="ts" setup>
import { ElInput, type FormInstance, FormRules } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { FieldTypeEnum, FieldTypeMap } from "./formEnums";
const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const mode = ref("add");
const popupTitle = computed(() => {
	return mode.value == "edit" ? "编辑表单" : "新增表单";
});

const tableValue = ref([]);
const formData = reactive({
	name: FieldTypeEnum.TEXT,
	field: "",
	title: "",
	placeholder: "",
	rows: 4,
	options: [],
	maxlength: 200,
	isRequired: false,
});

const fieldValueReg = /^(?!^\d)(?!.*?_$)[A-Za-z_$][A-Za-z\d_$]*$/;
const formRules: FormRules = {
	field: [
		{
			required: true,
			message: "请输入字段值",
			trigger: ["blur"],
		},
		{
			pattern: fieldValueReg,
			message: "字段值格式不正确",
			trigger: ["change"],
		},
		{
			validator(rule: any, value: any, callback: any) {
				const flag = tableValue.value.some(
					(item) => item.field == formData.field
				);
				if (flag) {
					callback(new Error("字段不能重复，请修改"));
				} else {
					callback();
				}
			},
			trigger: "change",
		},
	],
	title: [
		{
			required: true,
			message: "请输入字段名称",
			trigger: ["blur"],
		},
	],
};

const inputValue = ref("");
const inputVisible = ref(false);
const InputRef = ref<InstanceType<typeof ElInput>>();

const handleOptionClose = (tag: string) => {
	formData.options.splice(formData.options.indexOf(tag), 1);
};

const showInput = () => {
	inputVisible.value = true;
	nextTick(() => {
		InputRef.value!.input!.focus();
	});
};

const handleInputConfirm = () => {
	if (inputValue.value) {
		formData.options.push(inputValue.value);
	}
	inputVisible.value = false;
	inputValue.value = "";
};

const handleSubmit = async () => {
	await formRef.value?.validate();
	popupRef.value?.close();
	emit("success", {
		data: formData,
		type: mode.value,
	});
};

const open = (value: any) => {
	const { data, lists } = value;
	tableValue.value = lists;
	mode.value = data ? "edit" : "add";
	if (data) {
		Object.keys(formData).forEach((key) => {
			formData[key] = data[key];
		});
	}
	popupRef.value?.open();
};

const handleClose = () => {
	emit("close");
};

defineExpose({
	open,
});
</script>
