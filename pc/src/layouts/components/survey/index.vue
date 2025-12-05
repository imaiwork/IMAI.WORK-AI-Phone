<template>
	<div class="survey-dialog">
		<ElDialog
			v-model="showSurvey"
			width="730"
			top="10vh"
			:show-close="false"
			:append-to-body="false">
			<div
				class="relative overflow-hidden px-[48px] py-8 after:content-[''] after:absolute after:top-0 after:left-0 after:w-full after:bg-[linear-gradient(139.44deg,_rgba(239,237,252,0.5)_0%,_rgba(241,214,214,0.5)_100%)]">
				<div class="absolute top-4 right-4">
					<div class="cursor-pointer" @click="showSurvey = false">
						<Icon name="el-icon-Close" :size="24"></Icon>
					</div>
				</div>
				<div class="flex items-center">
					<div
						class="flex justify-center h-[72px] w-[72px] p-2 rounded-full bg-white shadow-[0px_2px_15px_1px_rgba(249,239,239,1)]">
						<img src="@/assets/images/shou.png" />
					</div>
					<div class="ml-10">
						<div class="flex items-center gap-4">
							<div class="text-[32px] font-bold">欢迎使用</div>
							<img :src="pc_logo" class="h-[46px]" />
						</div>
						<div class="text-[#7D7D7D] mt-1">
							很高兴见到您，为了给您提供最佳的使用体验，请花一点时间告诉我们关于您的信息
						</div>
					</div>
				</div>
				<div class="mt-10">
					<div class="mt-4">
						<div class="text-[18px] font-bold">
							贵公司的规模有多大？
						</div>
						<div class="mt-4">
							<ElRadioGroup
								v-model="formData.company_size"
								size="large">
								<div class="flex flex-wrap gap-4">
									<ElRadio
										v-for="item in companySizeList"
										:key="item.value"
										:label="item.value"
										:value="item.value"
										border>
										{{ item.label }}
									</ElRadio>
								</div>
							</ElRadioGroup>
						</div>
					</div>
					<div class="mt-8">
						<div class="text-[18px] font-bold">贵公司的名称：</div>
						<div class="mt-4">
							<ElInput
								v-model="formData.company_name"
								class="!h-[48px]"
								placeholder="请输入贵公司名称" />
						</div>
					</div>
				</div>
				<div class="mt-8 flex justify-center">
					<ElButton
						type="primary"
						class="w-[336px] !h-[48px] mx-auto"
						:loading="isLock"
						@click="lockFn">
						确认提交
					</ElButton>
				</div>
			</div>
		</ElDialog>
	</div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { submitSurvey } from "@/api/app";
const appStore = useAppStore();
const { pc_logo } = appStore.getWebsiteConfig;

const showSurvey = computed({
	get() {
		return appStore.showSurvey;
	},
	set(value) {
		appStore.showSurvey = value;
	},
});

const formData = reactive({
	company_size: "1",
	company_name: "",
});

const companySizeList = [
	{ label: "只有我", value: "1" },
	{ label: "1-10人", value: "1-10" },
	{ label: "11-50人", value: "11-50" },
	{ label: "51-200人", value: "51-200" },
	{ label: "201-1000人", value: "201-1000" },
	{ label: "1000+", value: "1000+" },
];

const { lockFn, isLock } = useLockFn(async () => {
	try {
		if (!formData.company_name && formData.company_size > "1") {
			feedback.msgError("请输入贵公司名称");
			return;
		}
		await submitSurvey(formData);
		showSurvey.value = false;
		feedback.msgSuccess("提交成功，感谢您的反馈！");
	} catch (error) {
		feedback.msgError(error || "提交失败");
	}
});
</script>

<style scoped lang="scss">
.survey-dialog {
	:deep() {
		.el-dialog {
			padding: 0;
			overflow: hidden;
		}
		.el-dialog__header {
			display: none;
			padding: 0;
		}
	}
}
:deep() {
	.el-radio {
		background-color: #f6f9fe;
		border-color: transparent !important;
		&.is-checked {
			background-color: #f6f9fe;
			border-color: var(--el-color-primary) !important;
		}
	}
}
</style>
