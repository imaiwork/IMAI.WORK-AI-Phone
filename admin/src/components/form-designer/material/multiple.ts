import type { WidgetMeta } from "../material";
import {
	createField,
	createIsRequired,
	createTitle,
	createOptions,
} from "./_public";

const meta: WidgetMeta = {
	name: "WidgetMultiple",
	title: "下拉多选",
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
