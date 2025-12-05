<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-page-header
				:content="route.query.name || '应用详情'"
				@back="$router.back()" />
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<el-form
				class="ls-form"
				ref="formRef"
				:rules="rules"
				:model="formData"
				label-width="90px">
				<el-form-item label="应用名称" prop="name">
					<el-input
						class="!w-[300px]"
						v-model="formData.name"
						placeholder="请输入应用名称"
						maxlength="20"
						clearable />
				</el-form-item>
				<el-form-item label="应用图标" prop="pic">
					<material-picker v-model="formData.pic" :limit="1" />
				</el-form-item>
				<el-form-item label="应用标签" prop="tips">
					<div
						class="flex items-center gap-2 justify-between w-[300px]">
						<el-input
							class="flex-1"
							v-model="tags[0]"
							placeholder="请输入应用标签1"
							clearable
							maxlength="5"
							@change="handleTagChange" />
						<el-input
							class="flex-1"
							v-model="tags[1]"
							placeholder="请输入应用标签2"
							clearable
							maxlength="5"
							@change="handleTagChange" />
					</div>
				</el-form-item>
				<el-form-item label="应用简介" prop="brief">
					<el-input
						class="!w-[300px]"
						v-model="formData.brief"
						type="textarea"
						:rows="4"
						placeholder="请输入应用简介"
						clearable />
				</el-form-item>
				<el-form-item label="应用介绍" prop="content">
					<editor v-model="formData.content" height="500" />
				</el-form-item>
				<el-form-item label="排序" prop="sort">
					<div>
						<el-input class="" v-model="formData.sort" />
						<div class="form-tips">默认为0，数值越大排越前面</div>
					</div>
				</el-form-item>
				<!-- <el-form-item label="发布状态" prop="release_status">
					<el-switch
						v-model="formData.release_status"
						:active-value="1"
						:inactive-value="0" />
				</el-form-item> -->
				<el-form-item label="是否标新" prop="is_new">
					<el-switch
						v-model="formData.is_new"
						:active-value="1"
						:inactive-value="0" />
				</el-form-item>
				<el-form-item label="状态" prop="show_status">
					<el-switch
						v-model="formData.show_status"
						:active-value="1"
						:inactive-value="0" />
				</el-form-item>
			</el-form>
		</el-card>
		<footer-btns v-perms="['decoration.application/edit']">
			<el-button type="primary" @click="handleSubmit">保存</el-button>
		</footer-btns>
	</div>
</template>

<script setup lang="ts">
import type { FormInstance } from "element-plus";
import {
	getApplicationDetail,
	editApplication,
} from "@/api/decoration/application";
import feedback from "@/utils/feedback";
import useMultipleTabs from "@/hooks/useMultipleTabs";

const { removeTab } = useMultipleTabs();

const formRef = shallowRef<FormInstance>();

const route = useRoute();
const router = useRouter();

//表单数据
const formData = reactive<Record<string, any>>({
	id: "",
	name: "",
	pic: "",
	brief: "",
	tips: "",
	content: "",
	is_new: 0,
	release_status: 1,
	sort: 0,
	show_status: 1,
});

const tags = ref<any[]>(["", ""]);

//校验规则
const rules = {
	name: [
		{
			required: true,
			message: "请输入名称",
			trigger: ["blur"],
		},
	],
	tips: [
		{
			required: true,
			message: "请输入应用标签",
			trigger: ["blur"],
		},
	],
	pic: [
		{
			required: true,
			message: "请上传应用图标",
			trigger: ["blur"],
		},
	],
	brief: [
		{
			required: true,
			message: "请输入应用简介",
			trigger: ["blur"],
		},
	],
	content: [
		{
			required: true,
			message: "请输入应用介绍",
			trigger: ["blur"],
		},
	],
};

const setFormData = async (data: Record<any, any>) => {
	for (const key in formData) {
		if (data[key] != null && data[key] != undefined) {
			//@ts-ignore
			formData[key] = data[key];
		}
	}
	if (data.tips) {
		tags.value = data.tips;
	}
};

const handleTagChange = () => {
	formData.tips = tags.value;
};

const getDetail = async () => {
	const data = await getApplicationDetail({ id: route.query.id });
	setFormData(data);
};

const handleSubmit = async () => {
	feedback.loading("保存中");
	try {
		await formRef.value?.validate();
		await editApplication(formData);
		removeTab();
		router.back();
	} finally {
		feedback.closeLoading();
	}
};

getDetail();
</script>

<style scoped></style>
