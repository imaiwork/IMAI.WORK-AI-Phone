export enum FieldTypeEnum {
	TEXT = "WidgetInput",
	TEXTAREA = "WidgetTextarea",
	SELECT = "WidgetSelect",
	MULTIPLE = "WidgetMultiple",
}

export const FieldTypeMap = {
	[FieldTypeEnum.TEXT]: "单行文本",
	[FieldTypeEnum.TEXTAREA]: "多行文本",
	[FieldTypeEnum.SELECT]: "下拉选择（单选）",
	[FieldTypeEnum.MULTIPLE]: "下拉选择（多选）",
};
