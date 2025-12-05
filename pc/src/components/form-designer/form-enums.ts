export enum FieldEnum {
	INPUT = "WidgetInput",
	TEXTAREA = "WidgetTextarea",
	SELECT = "WidgetSelect",
	MULTIPLE = "WidgetMultiple",
	FILE = "WidgetFile",
}

export const FieldTypeMap = {
	[FieldEnum.INPUT]: "单行文本",
	[FieldEnum.TEXTAREA]: "多行文本",
	[FieldEnum.SELECT]: "下拉选择（单选）",
	[FieldEnum.MULTIPLE]: "下拉选择（多选）",
	[FieldEnum.FILE]: "文件上传",
};
