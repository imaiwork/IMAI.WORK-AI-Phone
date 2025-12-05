<template>
	<div class="upload">
		<el-upload
			v-model:file-list="fileList"
			class="w-[inherit]"
			ref="uploadRefs"
			:drag="drag"
			:action="action"
			:multiple="multiple"
			:limit="limitLength"
			:disabled="disabled"
			:show-file-list="showFileList"
			:list-type="listType"
			:headers="headers"
			:data="data"
			:on-exceed="handleExceed"
			:accept="accept"
			:on-error="handleError"
			:on-success="handleSuccess"
			:before-upload="handleBefore"
			:on-remove="emitUpdateValue"
			:on-progress="handleProgress">
			<slot></slot>
		</el-upload>
	</div>
</template>

<script lang="ts" setup>
import { computed, defineComponent, ref, shallowRef } from "vue";
import { useUserStore } from "@/stores/user";
import { getApiPrefix, getApiUrl, getVersion } from "~/utils/env";
import feedback from "@/utils/feedback";
import type { ElUpload, UploadProps, UploadRawFile } from "element-plus";
import { genFileId } from "element-plus";
import { RequestCodeEnum } from "@/enums/requestEnums";
const props = defineProps({
	type: {
		type: String,
		default: "image",
	},
	modelValue: {
		type: [Array, Object],
		default: () => [],
	},
	// 是否拖拽上传
	drag: {
		type: Boolean,
		default: false,
	},
	// 上传文件类型
	accept: {
		type: String,
	},
	// 是否支持多选
	multiple: {
		type: Boolean,
		default: false,
	},
	// 多选时最多选择几条
	limit: {
		type: Number,
		default: 1,
	},
	// 上传时的额外参数
	data: {
		type: Object,
		default: () => ({}),
	},
	disabled: {
		type: Boolean,
		default: false,
	},
	listType: {
		type: String as PropType<"text" | "picture" | "picture-card">,
		default: "picture",
	},
	returnType: {
		type: String as PropType<"object" | "array">,
		default: "object",
	},
	showFileList: {
		type: Boolean,
		default: true,
	},
	size: {
		type: Number,
		default: Infinity,
	},
});

const emit = defineEmits(["update:modelValue", "on-progress", "success"]);
const userStore = useUserStore();
const uploadRefs = shallowRef<InstanceType<typeof ElUpload>>();
const action = computed(
	() => `${getApiUrl()}${getApiPrefix()}/upload/${props.type}`
);
const headers = computed(() => ({
	token: userStore.token,
	version: getVersion(),
}));
const fileList = ref<any[]>([]);
const limitLength = computed(() => {
	if (props.returnType === "object") {
		return 1;
	}
	if (!props.limit) {
		return 1;
	}
	return props.limit;
});

const emitUpdateValue = () => {
	let value: any = {};
	if (props.returnType === "object") {
		const [item] = fileList.value;
		if (item && item.status === "success") {
			value = {
				url: item.url,
				name: item.name,
				uri: item.response.data.url,
				id: item.response.data?.id,
				audio_id: item.response.data?.audio_id,
			};
		}
	} else {
		const data = fileList.value.filter((item) => item.status === "success");
		value = data.map((item) => ({
			url: item.url,
			uri: item.uri,
			name: item.name,
			id: item.id,
		}));
	}
	emit("update:modelValue", value);
	emit("success", value);
};

const handleBefore: UploadProps["beforeUpload"] = (rawFile) => {
	const { size } = props;
	const fileSizeInMB = rawFile.size / 1024 / 1024;
	const limitUnit = size < 1 ? "KB" : "MB";
	const limitSize = size < 1 ? size * 1000 : size;

	if (fileSizeInMB > size) {
		feedback.msgError(`上传文件超过${limitSize}${limitUnit}`);
		return false;
	}
	return true;
};

const handleSuccess = (response: any, file: any, fileLists: any[]) => {
	if (response.code == RequestCodeEnum.SUCCESS) {
		file.url = response.data.uri;
		emitUpdateValue();
	} else {
		response.msg && feedback.msgError(`上传失败：${response.msg}`);
		uploadRefs.value.handleRemove(file);
	}
};
const handleExceed: UploadProps["onExceed"] = (files) => {
	if (limitLength.value == 1) {
		uploadRefs.value!.clearFiles();
		const file = files[0] as UploadRawFile;
		file.uid = genFileId();
		uploadRefs.value!.handleStart(file);
		uploadRefs.value!.submit();
	} else {
		feedback.msgError(`超出上传上限${limitLength.value}，请重新上传`);
	}
};

const handleError = (event: any, file: any) => {
	feedback.msgError(`${file.name}文件上传失败`);
};
const setValueItem = (item: any) => {
	if (!item.url) return;
	const isInFiles = fileList.value.some((file) => file.url == item.url);
	if (!isInFiles) {
		fileList.value.push({ ...item });
	}
};

const handleProgress = (event: any) => {
	emit("on-progress", event.percent);
};
watch(
	() => props.modelValue,
	(newVal) => {
		if (Array.isArray(newVal)) {
			if (!newVal.length) {
				uploadRefs.value?.clearFiles();
				return;
			}
			newVal.forEach((item) => {
				setValueItem(item);
			});
		} else {
			if (!newVal.url) {
				uploadRefs.value?.clearFiles();
				return;
			}
			setValueItem(newVal);
		}
	},
	{
		immediate: true,
	}
);
</script>

<style lang="scss" scoped>
.upload {
	:deep() {
		.el-upload {
			width: inherit;
		}

		.el-upload-list--picture {
			.el-upload-list__item-thumbnail {
				width: 38px;
				height: 38px;
			}
		}
	}
}
</style>
