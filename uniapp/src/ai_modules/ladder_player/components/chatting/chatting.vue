<template>
	<scroll-view class="h-full" scroll-y :scroll-top="scrollTop">
		<view class="content-box">
			<view
				v-for="(item, index) in contentList"
				:key="index"
				class="pb-5"
				ref="scrollRef">
				<view v-if="item.type == 2">
					<chat-msg-item
						ref="chatMsgItemRef"
						:loading="item.loading"
						:type="item.type"
						:logo="item.logo"
						:link="item.link"
						:duration="item.duration"
						:content="item.reply"
						:auto="item.auto"></chat-msg-item>
				</view>
				<view v-if="item.type == 1" class="flex justify-end">
					<chat-msg-item
						ref="chatMsgItemRef"
						:type="item.type"
						:link="item.link"
						:loading="item.loading"
						:duration="item.duration"
						:content="item.message"
						:tips="item.tips"></chat-msg-item>
				</view>
			</view>
		</view>
	</scroll-view>
</template>

<script setup lang="ts">
import ChatMsgItem from "../chat-msg-item/chat-msg-item.vue";
import { getRect } from "@/utils/util";

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

const chatMsgItemRef = shallowRef<InstanceType<typeof ChatMsgItem>[]>([]);

const scrollTop = ref<number>(0);
const { proxy }: any = getCurrentInstance();
const scrollToBottom = async () => {
	await nextTick();
	getRect(".content-box", false, proxy).then((res: any) => {
		scrollTop.value = res.height;
	});
};

const pauseAll = () => {
	if (chatMsgItemRef.value && chatMsgItemRef.value?.length > 0) {
		chatMsgItemRef.value.forEach((item: any) => item.pause());
	}
};

defineExpose({
	scrollToBottom,
	pauseAll,
});
</script>

<style scoped></style>
