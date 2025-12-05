import type { PropsItem } from "../props";

export const createField = (): PropsItem => ({
	name: "field",
	label: "字段值",
	type: "string",
	tip: "字段值允许包含数字、字母、下划线_、美元符号$，但不能以数字开头，且在同一个表单中必须唯一",
	setter: {
		name: "String",
		props: {
			placeholder: "请输入字段值",
		},
	},
	rules: [
		{
			required: true,
			message: "请输入字段值",
		},
		{
			pattern: /^[a-zA-Z_$][a-zA-Z0-9_$]*$/,
			message: "字段值格式不正确",
		},
	],
});

export const createTitle = (): PropsItem => ({
	name: "title",
	label: "字段标题",
	type: "string",
	setter: {
		name: "String",
		props: {
			placeholder: "请输入字段标题",
		},
	},
	rules: [
		{
			required: true,
			message: "请输入字段标题",
		},
	],
});

export const createOptions = (): PropsItem => ({
	name: "options",
	label: "选项",
	type: "array",
	setter: {
		name: "Tags",
		props: {
			placeholder: `添加选项`,
			type: "tags",
			options: [],
		},
	},
	getValue(props) {
		return props.options;
	},
	setValue(props, value) {
		props.options = value;
	},
	defaultValue: [],
});

export const createIsRequired = (): PropsItem => ({
	name: "isRequired",
	label: "是否必填",
	type: "boolean",
	setter: {
		name: "Bool",
	},
	defaultValue: false,
});
