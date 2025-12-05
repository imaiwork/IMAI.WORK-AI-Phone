import type { WidgetMeta } from "../material";
import {
	createField,
	createIsRequired,
	createTitle,
	createOptions,
} from "./_public";

const meta: WidgetMeta = {
	name: "WidgetFile",
	title: "文件上传",
	props: [
		{
			name: "field",
			label: "字段值",
			type: "string",
			setter: {
				name: "String",
				props: {
					disabled: true,
				},
			},
			defaultValue: "file",
		},
		createTitle(),
		createIsRequired(),
	],
	sort: 5,
};

export default meta;
