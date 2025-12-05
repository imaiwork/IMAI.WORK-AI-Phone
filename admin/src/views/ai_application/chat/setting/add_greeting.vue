<template>
	<div class="edit-popup">
		<popup
			ref="popupRef"
			:title="popupTitle"
			:async="true"
			width="550px"
			@confirm="handleSubmit">
			<el-form
				class="ls-form"
				ref="formRef"
				:rules="rules"
				:model="formData"
				label-width="90px">
				<el-form-item label="图标" prop="logo">
					<material-picker v-model="formData.logo" />
				</el-form-item>
				<el-form-item label="内容" prop="value">
					<el-input
						class="ls-input w-[300px]"
						v-model="formData.value"
						placeholder="请输入内容"
						type="textarea"
						:rows="4"
						clearable />
				</el-form-item>
			</el-form>
		</popup>
	</div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";

import Popup from "@/components/popup/index.vue";

const emit = defineEmits(["success"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = ref("");
//模式
const mode = ref("");
//表单数据
const formData = reactive({
	logo: "",
	value: "",
});
//表单校验规则
const rules = {
	logo: [
		{
			required: true,
			message: "请选择图标",
			trigger: ["blur", "change"],
		},
	],
	value: [
		{
			required: true,
			message: "请输入内容",
			trigger: ["blur", "change"],
		},
	],
};

//提交表单
const handleSubmit = async () => {
	try {
		await formRef.value?.validate();
		popupRef.value?.close();
		emit("success", {
			data: formData,
			mode: mode.value,
		});
	} catch (error) {
		return error;
	}
};

//打开弹框
const open = (type: string, row?: any) => {
	mode.value = type;
	formData.logo = row?.logo;
	formData.value = row?.value;
	popupRef.value?.open();
};

defineExpose({
	open,
});
</script>
