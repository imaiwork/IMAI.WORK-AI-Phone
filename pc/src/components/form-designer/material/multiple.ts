import type { WidgetMeta } from "../material";
import {
	createField,
	createIsRequired,
	createTitle,
	createOptions,
} from "./_public";
import { FieldEnum, FieldTypeMap } from "../form-enums";

const meta: WidgetMeta = {
	name: FieldEnum.MULTIPLE,
	title: FieldTypeMap[FieldEnum.MULTIPLE],
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
		createOptions(),
		createIsRequired(),
	],
	sort: 4,
};

export default meta;
