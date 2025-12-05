import type { WidgetMeta } from "../material";
import {
	createField,
	createIsRequired,
	createOptions,
	createTitle,
} from "./_public";
const meta: WidgetMeta = {
	name: "WidgetSelect",
	title: "下拉单选",
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
