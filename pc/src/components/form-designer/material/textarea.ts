import type { WidgetMeta } from "../material";
import { createField, createIsRequired, createTitle } from "./_public";
import { FieldEnum, FieldTypeMap } from "../form-enums";

const meta: WidgetMeta = {
	name: FieldEnum.TEXTAREA,
	title: FieldTypeMap[FieldEnum.TEXTAREA],
	props: [
		createField(),
		createTitle(),
		{
			name: "placeholder",
			label: "占位文字",
			type: "string",
			setter: {
				name: "String",
				props: {
					placeholder: "请输入",
				},
			},
		},
		{
			name: "rows",
			label: "默认行数",
			type: "number",
			setter: {
				name: "Number",
				props: {
					min: 0,
				},
			},
			defaultValue: 4,
		},
		{
			name: "maxlength",
			label: "最大输入长度",
			type: "number",
			setter: {
				name: "Number",
				props: {
					min: 0,
				},
			},
			defaultValue: 200,
		},
		createIsRequired(),
	],
	sort: 2,
};

export default meta;
