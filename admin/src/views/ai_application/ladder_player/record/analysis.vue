<template>
	<el-scrollbar>
		<div class="h-full flex flex-col w-[480px] mx-auto">
			<div
				class="bg-[#F5F8FF] rounded-tl-xl rounded-tr-xl p-3 mt-[48rpx] pb-[100rpx]">
				<div class="bg-white rounded-xl p-3">
					<div>
						<div class="text-[#97969C] font-bold">总得分</div>
						<div>
							<text class="text-primary font-bold text-[36px]">{{
								detail.total_score || 0
							}}</text>
							<text class="text-[#97969C]">/100</text>
						</div>
					</div>
					<div class="w-full mx-auto mt-2">
						<pie-chat :indicator="indicator"></pie-chat>
					</div>
					<div class="rounded-xl bg-[#FAFAFA] p-3 mt-[12rpx]">
						<div class="flex items-center gap-2">
							<Icon name="local-icon-time" size="20"></Icon>
							<text class="text-xs text-[#545358]"
								>您本次的练习时长：{{
									formatTime(detail.duration)
								}}</text
							>
						</div>
						<div class="flex gap-2 mt-3">
							<Icon name="local-icon-edit" size="20"></Icon>
							<text
								class="text-xs text-[#545358] leading-[44rpx] whitespace-pre-line">
								{{ detail.total_response }}
							</text>
						</div>
					</div>
				</div>
				<div
					class="bg-white rounded-xl p-3 mt-4 column"
					:id="item.id"
					v-for="item in indicator"
					:key="item.id">
					<div class="text-primary font-bold text-xl">
						{{ item.name }}
					</div>
					<div class="flex items-center -mt-2">
						<div class="w-[178px] mt-2">
							<el-divider />
						</div>
						<div class="ml-[32px]">
							<text class="text-primary font-bold text-[24px]">{{
								item.score || 0
							}}</text>
							<text class="text-[#97969C]">/20</text>
						</div>
					</div>
					<div class="flex justify-between">
						<div class="flex items-center gap-2">
							<Icon name="local-icon-tongdian" size="20"></Icon>
							<text class="text-xs font-bold text-[#60636B]"
								>痛点分析</text
							>
						</div>
						<div class="flex items-center mt-2 gap-[2px]">
							<div v-for="index in 5" :key="index">
								<Icon
									name="local-icon-score_star_fill"
									v-if="getStar(item.score) >= index"></Icon>
								<Icon
									name="local-icon-score_star"
									v-else></Icon>
							</div>
						</div>
					</div>
					<div
						class="rounded-xl bg-[#F8F9FE] p-3 mt-3 leading-[44rpx] text-xs break-words">
						{{ item.content }}
					</div>
				</div>
			</div>
		</div>
	</el-scrollbar>
</template>

<script setup lang="ts">
import PieChat from "../components/pie-chat/pie-chat.vue";

const props = defineProps({
	detail: {
		type: Object,
		default: () => ({}),
	},
});

const indicator = ref([
	{
		id: "sfl",
		name: "说服力",
		top: 0,
		height: 0,
		score: 0,
		max: 20,
		content: "",
	},
	{
		id: "lld",
		name: "流利度",
		top: 0,
		height: 0,
		score: 0,
		max: 20,
		content: "",
	},
	{
		id: "yybd",
		name: "语言表达",
		top: 0,
		height: 0,
		score: 0,
		max: 20,
		content: "",
	},
	{
		id: "zqx",
		name: "准确性",
		top: 0,
		height: 0,
		score: 0,
		max: 20,
		content: "",
	},
	{
		id: "yyzz",
		name: "语言组织能力",
		top: 0,
		height: 0,
		score: 0,
		max: 20,
		content: "",
	},
]);

const formatTime = (time: any) => {
	if (time > 0) {
		const minutes = Math.floor(time / 60);
		const remainingSeconds = time % 60;
		return `${minutes}"${remainingSeconds}'`;
	}
	return "0";
};

const getStar = (score: number) => {
	return (score / 20) * 5;
};

watch(
	() => props.detail,
	(data) => {
		if (data && data.model_response) {
			data.model_response.forEach((item: any, index: number) => {
				indicator.value[index].score = item.score;
				indicator.value[index].content = item.improvement_suggestions;
			});
		}
	},
	{ immediate: true }
);
</script>

<style scoped></style>
