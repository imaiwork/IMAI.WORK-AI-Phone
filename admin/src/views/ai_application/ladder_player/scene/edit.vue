<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-page-header
				:content="$route.query.id as string ? '编辑场景' : '新增场景'"
				@back="$router.back()" />
		</el-card>

		<el-card class="!border-none mt-4" shadow="never">
			<el-form
				:model="formData"
				:rules="rules"
				ref="formRef"
				label-width="150px">
				<!-- 场景头像 -->
				<el-form-item label="场景头像" prop="logo">
					<material-picker v-model="formData.logo" :limit="1" />
				</el-form-item>
				<!-- 场景名称 -->
				<el-form-item label="场景名称" prop="name">
					<el-input
						v-model="formData.name"
						class="w-[380px]"
						placeholder="请输入场景名称" />
				</el-form-item>
				<!-- 场景简介 -->
				<el-form-item label="场景简介" prop="description">
					<el-input
						v-model="formData.description"
						type="textarea"
						:rows="4"
						class="w-[380px]"
						placeholder="请输入场景简介" />
				</el-form-item>
				<!-- 练习目标 -->
				<el-form-item
					label="练习目标"
					prop="training_target"
					v-if="is_admin">
					<div
						class="w-[380px] rounded border border-[rgba(220,223,230,1)] p-4">
						<div class="flex flex-col gap-2">
							<div
								v-for="(
									item, index
								) in formData.training_target"
								:key="index"
								class="flex items-center gap-4">
								<div class="flex items-center gap-2 grow">
									<el-input
										v-model="
											formData.training_target[index]
										"
										placeholder="请输入练习目标" />
									<div v-if="index > 0">
										<el-button
											type="danger"
											:icon="Delete"
											size="small"
											@click="
												handleTargetDelete(index)
											"></el-button>
									</div>
								</div>
							</div>
							<div>
								<el-button
									type="primary"
									size="small"
									@click="handleTargetAdd"
									>添加</el-button
								>
							</div>
						</div>
					</div>
				</el-form-item>
				<!-- 温馨提示 -->
				<el-form-item label="温馨提示" prop="tips" v-if="is_admin">
					<div
						class="w-[380px] rounded border border-[rgba(220,223,230,1)] p-4">
						<div class="flex flex-col gap-2">
							<div
								v-for="(item, index) in formData.tips"
								:key="index"
								class="flex items-center gap-4">
								<div class="flex items-center gap-2 grow">
									<el-input
										v-model="formData.tips[index]"
										class="!flex-1"
										placeholder="请输入温馨提示" />
									<div v-if="index > 0">
										<el-button
											type="danger"
											:icon="Delete"
											size="small"
											@click="
												handleTipsDelete(index)
											"></el-button>
									</div>
								</div>
							</div>
							<div>
								<el-button
									type="primary"
									size="small"
									@click="handleTipsAdd"
									>添加</el-button
								>
							</div>
						</div>
					</div>
				</el-form-item>
				<!-- 陪练者名称 -->
				<el-form-item label="陪练者名称" prop="coach_name">
					<el-input
						v-model="formData.coach_name"
						placeholder="请输入陪练者名称"
						class="w-[380px]" />
				</el-form-item>
				<!-- 陪练者人设 -->
				<el-form-item label="陪练者人设" prop="coach_persona">
					<el-input
						v-model="formData.coach_persona"
						placeholder="请输入陪练者人设"
						type="textarea"
						:rows="4"
						class="w-[380px]" />
				</el-form-item>
				<!-- 陪练者情感 -->
				<el-form-item label="陪练者情感" prop="coach_emotion">
					<el-select
						v-model="formData.coach_emotion"
						class="!w-[380px]"
						placeholder="请选择陪练者情感">
						<el-option
							v-for="item in config.emotions"
							:key="item.value"
							:label="item.name"
							:value="item.value" />
					</el-select>
				</el-form-item>
				<!-- 陪练者情感投入程度 -->
				<el-form-item label="陪练者情感投入程度" prop="coach_intensity">
					<el-select
						v-model="formData.coach_intensity"
						class="!w-[380px]"
						placeholder="请选择陪练者情感投入程度">
						<el-option
							v-for="item in intensityList"
							:key="item.value"
							:label="item.name"
							:value="item.value" />
					</el-select>
				</el-form-item>
				<!-- 陪练者音色 -->
				<el-form-item label="陪练者音色" prop="coach_voice">
					<el-select
						v-model="formData.coach_voice"
						class="!w-[380px]"
						placeholder="请选择陪练者音色">
						<el-option
							v-for="item in config.voice"
							:key="item.code"
							:label="item.name"
							:value="item.code" />
					</el-select>
				</el-form-item>
				<!-- 练习者扮演人设 -->
				<el-form-item
					label="练习者扮演人设"
					prop="practitioner_persona">
					<el-input
						v-model="formData.practitioner_persona"
						type="textarea"
						:rows="4"
						class="w-[380px]"
						placeholder="请输入练习者扮演人设" />
				</el-form-item>
				<!-- 分析报告配置 -->
				<el-form-item
					label="分析报告配置"
					prop="analysis_report_config"
					v-if="is_admin">
					<div
						class="w-[380px] rounded border border-[rgba(220,223,230,1)] p-4">
						<div class="flex flex-col gap-2">
							<div
								v-for="(
									item, index
								) in formData.analysis_report_config"
								:key="index"
								class="flex items-center gap-4 grow">
								<div>分析考察方向{{ index + 1 }}</div>
								<div class="flex-1">
									<el-input
										v-model="
											formData.analysis_report_config[
												index
											]
										"
										placeholder="请输入分析考察方向" />
								</div>
							</div>
						</div>
					</div>
				</el-form-item>
				<!-- 场景排序 -->
				<el-form-item label="场景排序" prop="sort">
					<div>
						<el-input-number v-model="formData.sort" />
						<div class="form-tips">默认为0，数值越大越排前面</div>
					</div>
				</el-form-item>
				<el-form-item label="状态" prop="sort">
					<el-switch
						v-model="formData.status"
						:active-value="1"
						:inactive-value="0" />
				</el-form-item>
			</el-form>
		</el-card>
	</div>
	<footer-btns>
		<el-button type="primary" :loading="isLock" @click="lockSubmit"
			>保存</el-button
		>
	</footer-btns>
</template>

<script setup lang="ts">
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";
import { Delete } from "@element-plus/icons-vue";
import type { FormInstance } from "element-plus";
import useAppStore from "@/stores/modules/app";
import {
	lpSceneEdit,
	lpSceneAdd,
	lpSceneDetail,
} from "@/api/ai_application/ladder_player/scene";
import useMultipleTabs from "@/hooks/useMultipleTabs";

const router = useRouter();
const route = useRoute();
const appStore = useAppStore();

const { removeTab } = useMultipleTabs();

const config = computed(
	() =>
		appStore.config.lianlian || {
			voice: [],
			emotions: [],
			intensity: [],
		}
);

const intensityList = computed(() =>
	config.value.intensity.map((item: any) => ({
		...item,
		value: parseInt(item.value),
	}))
);

const is_admin = ref(true);

const formData = reactive<any>({
	id: "",
	logo: "",
	name: "",
	description: "",
	training_target: [],
	tips: [],
	coach_name: "",
	coach_persona: "",
	coach_emotion: "",
	coach_intensity: "",
	coach_voice: "",
	practitioner_persona: "",
	analysis_report_config: config.value.directions,
	sort: 0,
	status: 1,
});

const formRef = shallowRef<FormInstance>();

const rules = reactive({
	name: [{ required: true, message: "请输入场景名称", trigger: "blur" }],
	logo: [{ required: true, message: "请上传场景头像", trigger: "blur" }],
	description: [
		{ required: true, message: "请输入场景简介", trigger: "blur" },
	],
	training_target: [
		{ required: true, message: "请输入练习目标", trigger: "blur" },
	],
	tips: [{ required: true, message: "请输入温馨提示", trigger: "blur" }],
	coach_name: [
		{ required: true, message: "请输入陪练者名称", trigger: "blur" },
	],
	coach_persona: [
		{ required: true, message: "请输入陪练者人设", trigger: "blur" },
	],
	coach_emotion: [
		{ required: true, message: "请选择陪练者情感", trigger: "blur" },
	],
	coach_intensity: [
		{
			required: true,
			message: "请选择陪练者情感投入程度",
			trigger: "blur",
		},
	],
	coach_voice: [
		{ required: true, message: "请选择陪练者音色", trigger: "blur" },
	],
	practitioner_persona: [
		{ required: true, message: "请输入练习者扮演人设", trigger: "blur" },
	],
	analysis_report_config: [
		{ required: true, message: "请输入分析报告配置", trigger: "blur" },
		// 分析报告配置数据都不能为空
		{
			validator: (rule: any, value: any, callback: any) => {
				const emptyIndex = value.findIndex((item: any) => !item);
				if (emptyIndex !== -1) {
					callback(new Error(`请输入分析报告配置${emptyIndex + 1}`));
					return;
				}
				callback();
			},
			trigger: "blur",
		},
	],
});

const handleTargetDelete = (index: number) => {
	formData.training_target.splice(index, 1);
};

const handleTargetAdd = () => {
	// 判断上个元素是否为空, 否则提示
	if (
		!formData.training_target[formData.training_target.length - 1] &&
		formData.training_target.length > 0
	) {
		feedback.msgError("请先填写上个元素");
		return;
	}
	formData.training_target.push("");
};

const handleTipsDelete = (index: number) => {
	formData.tips.splice(index, 1);
};

const handleTipsAdd = () => {
	// 判断上个元素是否为空, 否则提示
	if (!formData.tips[formData.tips.length - 1] && formData.tips.length > 0) {
		feedback.msgError("请先填写上个元素");
		return;
	}
	formData.tips.push("");
};

const handleAnalysisReportAdd = () => {
	// 判断上个元素是否为空, 否则提示
	if (
		!formData.analysis_report_config[
			formData.analysis_report_config.length - 1
		] &&
		formData.analysis_report_config.length > 0
	) {
		feedback.msgError("请先填写上个元素");
		return;
	}
	formData.analysis_report_config.push("");
};

const handleSubmit = async () => {
	await formRef.value?.validate();
	formData.id ? await lpSceneEdit(formData) : await lpSceneAdd(formData);
	removeTab();
	router.back();
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit);

const getDetail = async () => {
	const data = await lpSceneDetail({
		id: route.query.id,
	});
	setFormData(data);
	is_admin.value = data.user_id == 0;
};

const setFormData = async (data: any) => {
	for (const key in formData) {
		if (data[key] != null && data[key] != undefined) {
			//@ts-ignore
			formData[key] = data[key];
		}
	}
};

onMounted(() => {
	if (route.query.id) {
		getDetail();
	}
});
</script>

<style scoped></style>
