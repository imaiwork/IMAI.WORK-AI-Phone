<template>
	<el-form label-width="90px">
		<el-card class="!border-none" shadow="never">
			<div class="font-medium text-xl">人工客服</div>
			<div class="mt-4">
				<el-form-item label="人工客服">
					<div>
						<el-switch
							v-model="formData.manual_kf.status"
							:active-value="1"
							:inactive-value="0" />
						<div class="form-tips">开启后，将在前台悬浮显示</div>
					</div>
				</el-form-item>
				<el-form-item label="悬浮图标">
					<div>
						<material-picker v-model="formData.manual_kf.icons" />
					</div>
				</el-form-item>
				<el-form-item label="客服标题">
					<el-input
						placeholder="请输入客服标题"
						class="w-[400px]"
						v-model="formData.manual_kf.title.value" />
					<div class="ml-2">
						<el-checkbox
							true-label="1"
							false-label="0"
							v-model="formData.manual_kf.title.status"
							label="显示" />
					</div>
				</el-form-item>
				<el-form-item label="服务时间">
					<el-input
						placeholder="请输入服务时间"
						class="w-[400px]"
						v-model="formData.manual_kf.service_time.value" />
					<div class="ml-2">
						<el-checkbox
							true-label="1"
							false-label="0"
							v-model="formData.manual_kf.service_time.status"
							label="显示" />
					</div>
				</el-form-item>
				<el-form-item label="联系电话">
					<el-input
						placeholder="请输入联系电话"
						class="w-[400px]"
						v-model="formData.manual_kf.phone.value" />
					<div class="ml-2">
						<el-checkbox
							true-label="1"
							false-label="0"
							v-model="formData.manual_kf.phone.status"
							label="显示" />
					</div>
				</el-form-item>
				<el-form-item label="客服二维码">
					<div>
						<material-picker v-model="formData.manual_kf.qr_code" />
						<div class="form-tips">
							建议图片尺寸：200*200像素；图片格式：jpg、png、jpeg
						</div>
					</div>
				</el-form-item>
			</div>
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<div class="font-medium text-xl">在线客服</div>
			<div class="mt-4">
				<el-form-item label="在线客服">
					<div>
						<el-switch
							v-model="formData.online_kf.status"
							:active-value="1"
							:inactive-value="0" />
						<div class="form-tips">
							开启后，将在前台侧边栏悬浮显示，并且弹出在线客服弹窗
						</div>
					</div>
				</el-form-item>
				<el-form-item label="悬浮图标">
					<div>
						<material-picker v-model="formData.online_kf.icons" />
					</div>
				</el-form-item>
				<el-form-item label="客服链接">
					<div>
						<el-input
							type="textarea"
							placeholder="请输入客服链接"
							class="w-[400px]"
							:rows="3"
							resize="none"
							v-model="formData.online_kf.link" />
						<div class="form-tips">
							需要在前台创建并发布机器人，拿到的分享链接填入既可
						</div>
					</div>
				</el-form-item>
			</div>
		</el-card>
		<footer-btns class="mt-4">
			<el-button type="primary" @click="setData">保存</el-button>
		</footer-btns>
	</el-form>
</template>

<script setup lang="ts">
import { getconsumerConfig, setconsumerConfig } from "@/api/setting/consumer";
import { isArray } from "lodash-es";

//表单数据
const formData = reactive({
	manual_kf: {
		title: {
			value: "",
			status: 0,
		},
		service_time: {
			value: "",
			status: 0,
		},
		phone: {
			value: "",
			status: 0,
		},
		qr_code: "",
		status: 0,
		icons: "",
	},
	online_kf: {
		status: 0,
		link: "",
		icons: "",
	},
});

const getData = async () => {
	const res = await getconsumerConfig();
	Object.keys(formData).map((item) => {
		//@ts-ignore
		formData[item] = isArray(res[item]) ? {} : res[item];
	});
};

const setData = async () => {
	await setconsumerConfig({ ...formData });
	getData();
};

onMounted(() => {
	getData();
});
</script>

<style lang="scss" scoped></style>
