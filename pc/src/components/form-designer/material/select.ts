import type { WidgetMeta } from "../material";
import {
	createField,
	createIsRequired,
	createOptions,
	createTitle,
} from "./_public";
import { FieldEnum, FieldTypeMap } from "../form-enums";

const meta: WidgetMeta = {
	name: FieldEnum.SELECT,
	title: FieldTypeMap[FieldEnum.SELECT],
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
	sort: 3,
};

export default meta;
