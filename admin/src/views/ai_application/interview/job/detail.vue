<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-page-header content="岗位详情" @back="$router.back()" />
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<el-form :model="formData" label-width="140px" disabled>
				<el-form-item label="岗位名称">
					<el-input v-model="formData.name" class="w-[380px]" />
				</el-form-item>
				<el-form-item label="岗位头像">
					<material-picker
						v-model="formData.avatar"
						:limit="1"
						disabled />
				</el-form-item>
				<el-form-item label="招聘企业">
					<el-input v-model="formData.company" class="w-[380px]" />
				</el-form-item>
				<el-form-item label="岗位介绍">
					<el-input v-model="formData.desc" class="w-[380px]" />
				</el-form-item>
				<el-form-item label="面试方式">
					<el-radio-group v-model="formData.type">
						<el-radio label="文字" :value="1" />
						<el-radio label="语音" :value="2" />
					</el-radio-group>
				</el-form-item>
				<el-form-item label="岗位JD">
					<el-input
						v-model="formData.jd"
						type="textarea"
						:rows="4"
						class="w-[380px]" />
				</el-form-item>
				<el-form-item label="附加考察">
					<el-input
						v-model="formData.extra"
						type="textarea"
						:rows="4"
						class="w-[380px]" />
				</el-form-item>
				<el-form-item label="面试关注" prop="attention">
					<div class="w-[380px] flex flex-col gap-2">
						<div
							v-for="(item, index) in formData.attention"
							class="w-full flex items-center gap-2">
							<el-input v-model="formData.attention[index]" />
						</div>
					</div>
				</el-form-item>
				<el-form-item label="高级设置">
					<el-card shadow="never" class="w-[580px]">
						<div>
							<div>通用回复（自动回复</div>
							<div class="mt-4">
								<el-input
									v-model="formData.name"
									type="textarea"
									:rows="6" />
							</div>
						</div>
						<div class="mt-4">
							<div>特定招呼（特定发起招呼）</div>
							<div class="flex gap-4 mt-4">
								<div class="flex gap-4">
									<div class="whitespace-nowrap">学历</div>
									<div>
										<el-select
											v-model="formData.name"
											class="w-[120px]"
											multiple></el-select>
									</div>
								</div>
								<div class="flex gap-4">
									<div class="whitespace-nowrap">
										年龄范围
									</div>
									<div class="flex items-center gap-2">
										<el-input v-model="formData.name" />
										<div>——</div>
										<el-input v-model="formData.name" />
									</div>
								</div>
								<div class="flex gap-4">
									<div class="whitespace-nowrap">性别</div>
									<div class="flex items-center gap-2">
										<el-input v-model="formData.name" />
									</div>
								</div>
							</div>
							<div class="flex gap-4 mt-4">
								<div class="flex gap-4">
									<div class="whitespace-nowrap">
										期望工作地
									</div>
									<div>
										<el-select
											v-model="formData.name"
											class="w-[120px]"
											multiple></el-select>
									</div>
								</div>
								<div class="flex gap-4">
									<div class="whitespace-nowrap">经验</div>
									<div>
										<el-select
											v-model="formData.name"
											class="w-[120px]"
											multiple></el-select>
									</div>
								</div>
							</div>
							<div class="flex gap-4 mt-4">
								<div class="flex gap-4">
									<div class="whitespace-nowrap">
										薪资待遇
									</div>
									<div>
										<el-select
											v-model="formData.name"
											class="w-[120px]"
											multiple></el-select>
									</div>
								</div>
								<div class="flex gap-4">
									<div class="whitespace-nowrap">
										求职意向
									</div>
									<div>
										<el-select
											v-model="formData.name"
											class="w-[120px]"
											multiple></el-select>
									</div>
								</div>
							</div>
							<div class="mt-4">
								<el-input
									v-model="formData.name"
									type="textarea"
									:rows="6" />
							</div>
						</div>
					</el-card>
				</el-form-item>
			</el-form>
		</el-card>
	</div>
</template>

<script setup lang="ts">
import { getInterviewJobDetail } from "@/api/ai_application/interview/job";

const route = useRoute();

const formData = ref<any>({});

const getDetail = async () => {
	const data = await getInterviewJobDetail({ id: route.query.id });
	formData.value = data;
};

onMounted(() => {
	getDetail();
});
</script>

<style scoped></style>
