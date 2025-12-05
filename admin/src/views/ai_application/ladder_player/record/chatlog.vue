<template>
	<view class="h-full flex flex-col w-[480px] mx-auto">
		<view class="grow min-h-0 bg-[#F5F8FF] rounded-xl p-4">
			<chatting :content-list="contentList" />
		</view>
	</view>
</template>

<script setup lang="ts">
import { getLpChatRecordLists } from "@/api/ai_application/ladder_player/record";
import useAppStore from "@/stores/modules/app";
import chatting from "../components/chatting/chatting.vue";

const props = defineProps<{
	id: any;
	sceneDetail: any;
}>();

const appStore = useAppStore();

const getCurrVoice = () => {
	const data = appStore.config?.lianlian?.voice || [];
	return (
		data.find((item: any) => item.code == props.sceneDetail?.coach_voice) ||
		{}
	);
};

const contentList = ref<any[]>([]);

const getChatRecordList = async () => {
	const { lists } = await getLpChatRecordLists({
		analysis_id: props.id,
		page: 1,
		page_size: 9999,
	});
	if (lists && lists.length > 0) {
		const voiceData = getCurrVoice();
		lists.forEach((item: any, index: number) => {
			if (item.preliminary_ask) {
				contentList.value.push({
					type: 2,
					reply: item.preliminary_ask,
					duration: item.preliminary_ask_audio_duration,
					logo: voiceData.logo,
					link: item.preliminary_ask_audio,
				});
			} else {
				contentList.value.push({
					type: 1,
					duration: item.ask_audio_duration,
					message: item.ask,
					link: item.ask_audio,
					tips: {
						performance: item.performance || "暂无内容",
						speechcraft: item.speechcraft || "暂无内容",
					},
				});
				contentList.value.push({
					type: 2,
					reply: item.reply,
					duration: item.reply_audio_duration,
					logo: voiceData.logo,
					link: item.reply_audio,
				});
			}
		});
	}
};

watch(
	() => props.sceneDetail,
	(data) => {
		if (Object.keys(data).length > 0) {
			getChatRecordList();
		}
	},
	{
		immediate: true,
	}
);
</script>

<style scoped></style>
