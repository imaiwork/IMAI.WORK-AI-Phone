<template>
	<el-scrollbar class="h-full">
		<div class="content-box">
			<div
				v-for="(item, index) in contentList"
				:key="index"
				class="pb-5"
				ref="scrollRef">
				<div v-if="item.type == 2">
					<chat-msg-item
						ref="chatMsgItemRef"
						:loading="item.loading"
						:type="item.type"
						:logo="item.logo"
						:link="item.link"
						:duration="item.duration"
						:content="item.reply"
						:auto="item.auto"></chat-msg-item>
				</div>
				<div v-if="item.type == 1" class="flex justify-end">
					<chat-msg-item
						ref="chatMsgItemRef"
						:type="item.type"
						:link="item.link"
						:duration="item.duration"
						:content="item.message"
						:tips="item.tips"></chat-msg-item>
				</div>
			</div>
		</div>
	</el-scrollbar>
</template>

<script setup lang="ts">
import ChatMsgItem from "../chat-msg-item/chat-msg-item.vue";

const props = defineProps({
	contentList: {
		type: Array as unknown as any[],
		default: () => [],
	},
	sendDisabled: {
		type: Boolean,
		default: false,
	},
	isLog: {
		type: Boolean,
		default: false,
	},
});
</script>

<style scoped></style>
