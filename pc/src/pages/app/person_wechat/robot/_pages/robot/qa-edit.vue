<template>
	<popup
		ref="editPopRef"
		async
		width="550px"
		:title="popupTitle"
		:confirm-loading="isLock"
		@confirm="lockConfirm"
		@close="close">
		<ElForm :model="formData" :rules="formRules" label-position="top">
			<ElFormItem label="提问案例" prop="question">
				<ElInput
					v-model="formData.question"
					placeholder="点击输入提问案例" />
			</ElFormItem>
			<ElFormItem label="回答格式" prop="answer">
				<ElInput
					v-model="formData.answer"
					placeholder="点击输入机器人回答的规范格式" />
			</ElFormItem>
		</ElForm>
	</popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";

const emit = defineEmits(["close", "success"]);
const mode = ref("add");
const popupTitle = computed(() => {
	return mode.value === "add" ? "新增问答话术" : "编辑问答话术";
});

const formData = reactive({
	question: "",
	answer: "",
});

const formRules = {
	question: [{ required: true, message: "请输入问题" }],
	answer: [{ required: true, message: "请输入答案" }],
};

const editPopRef = ref<InstanceType<typeof Popup>>();

const open = (row?: any) => {
	mode.value = row ? "edit" : "add";
	editPopRef.value?.open();
};

const close = () => {
	emit("close");
};

const handleConfirm = async () => {
	console.log("确认");
};

const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

defineExpose({
	open,
});
</script>

<style scoped></style>
