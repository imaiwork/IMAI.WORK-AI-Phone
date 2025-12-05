import type { WidgetMeta } from "../material";
import { createField, createIsRequired, createTitle } from "./_public";
import { FieldEnum, FieldTypeMap } from "../form-enums";

const meta: WidgetMeta = {
	name: FieldEnum.INPUT,
	title: FieldTypeMap[FieldEnum.INPUT],
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
	sort: 1,
};

export default meta;
