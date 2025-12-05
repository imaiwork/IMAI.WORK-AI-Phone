<template>
	<div class="edit-popup">
		<popup
			ref="popupRef"
			title="修改模块"
			:async="true"
			width="550px"
			@confirm="handleSubmit"
			@close="handleClose">
			<el-form
				class="ls-form"
				ref="formRef"
				:rules="rules"
				:model="formData"
				label-width="90px">
				<el-form-item label="名称" prop="name">
					<el-input
						class="w-[300px]"
						v-model="formData.name"
						placeholder="请输入名称"
						clearable />
				</el-form-item>
				<el-form-item label="图标" prop="pic">
					<material-picker v-model="formData.pic" :limit="1" />
				</el-form-item>
				<el-form-item label="类别" prop="type">
					<el-select
						v-model="formData.type"
						placeholder="请选择类别"
						class="!w-[300px]"
						@change="options = []">
						<el-option label="助理" value="1" />
						<el-option label="员工" value="2" />
					</el-select>
				</el-form-item>
				<el-form-item label="数据" prop="data_id">
					<el-select
						v-model="formData.data_id"
						filterable
						remote
						reserve-keyword
						placeholder="请输入关键字"
						remote-show-suffix
						:remote-method="remoteMethod"
						:loading="loading"
						class="!w-[300px]"
						@change="handleSelect">
						<el-option
							v-for="item in options"
							:key="item.value"
							:label="item.name"
							:value="`${item.id}`" />
					</el-select>
				</el-form-item>
			</el-form>
		</popup>
	</div>
</template>
<script lang="ts" setup>
import { getApplicationLists } from "@/api/decoration/application";
import { getAssistantModelList } from "@/api/ai_assistant/model";
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { useDictOptions } from "@/hooks/useDictOptions";
import { data } from "autoprefixer";
const emit = defineEmits(["success", "close"]);
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();

//表单数据
const formData = reactive({
	name: "",
	pic: "",
	type: "1",
	data_id: "",
	ast_name: "",
});

//校验规则
const rules = {
	name: [
		{
			required: true,
			message: "请输入名称",
			trigger: ["blur"],
		},
	],
	pic: [{ required: true, message: "请上传图片", trigger: ["blur"] }],
	data_id: [{ required: true, message: "请选择数据", trigger: ["blur"] }],
};
//提交
const handleSubmit = async () => {
	await formRef.value?.validate();
	popupRef.value?.close();
	emit("success", formData);
};

const handleClose = () => {
	emit("close");
};

const open = () => {
	popupRef.value?.open();
};

const setFormData = async (data: Record<any, any>) => {
	for (const key in formData) {
		if (data[key] != null && data[key] != undefined) {
			//@ts-ignore
			formData[key] = data[key];
		}
	}
	const { ast_name } = data;
	const { lists } = await getOptionApi.value({ name: ast_name });
	options.value = lists;
};

const options = ref<any[]>([]);
const loading = ref<boolean>(false);

const getOptionApi = computed(() => {
	return formData.type == "1" ? getAssistantModelList : getApplicationLists;
});

const remoteMethod = async (query: string) => {
	loading.value = true;
	const api = getOptionApi.value;
	const { lists } = await api({
		name: query,
	});
	options.value = lists;
	loading.value = false;
	formData.ast_name = "";
};

const handleSelect = () => {
	const data = options.value.find((item) => item.id == formData.data_id);
	if (formData.type == "2") {
		formData.ast_name = data.key;
	} else {
		formData.ast_name = data.name;
	}
};

defineExpose({
	open,
	setFormData,
});
</script>
