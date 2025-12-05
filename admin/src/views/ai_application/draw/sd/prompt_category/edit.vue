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
				<el-form-item label="分类名称" prop="title">
					<el-input
						class="w-64"
						v-model="formData.title"
						maxlength="10"
						show-word-limit
						placeholder="请输入分类名称"
						clearable />
				</el-form-item>
				<el-form-item label="排序">
					<div class="w-64">
						<el-input class="" v-model="formData.sort" />
						<div class="form-tips">默认为0，数值越大排越前面</div>
					</div>
				</el-form-item>
				<el-form-item label="状态">
					<el-switch
						v-model="formData.status"
						:active-value="1"
						:inactive-value="0" />
				</el-form-item>
			</el-form>
		</popup>
	</div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import {
	addDrawCategory,
	editDrawCategory,
} from "@/api/ai_application/draw/draw_sd";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";

const emit = defineEmits(["success"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//弹框标题
const popupTitle = ref("");
//表单数据
const formData: any = ref({
	id: "",
	title: "",
	sort: 0,
	status: "",
});
//表单校验规则
const rules = {
	title: [
		{
			required: true,
			message: "请输入名称",
			trigger: ["blur"],
		},
	],
};

//提交表单
const handleSubmit = async () => {
	try {
		await formRef.value?.validate();
		formData.value.id
			? await editDrawCategory(formData.value)
			: await addDrawCategory(formData.value);
		popupRef.value?.close();
		emit("success");
	} catch (error) {}
};

//打开弹框
const open = (type: string, value: any) => {
	//初始化数据
	if (type == "add") {
		formData.value = {
			id: "",
			title: "",
			sort: "0",
			status: 1,
		};
		popupTitle.value = "新增分类";
	} else if (type == "edit") {
		Object.keys(formData.value).map((item) => {
			formData.value[item] = value[item] ?? 0;
		});
		popupTitle.value = "编辑分类";
	}
	popupRef.value?.open();
};

defineExpose({
	open,
});
</script>
