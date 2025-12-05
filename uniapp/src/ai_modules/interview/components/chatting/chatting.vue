<template>
	<view class="h-full w-full flex">
		<scroll-view scroll-y ref="contentRef" :scroll-top="scrollTop">
			<view class="content-box pb-3">
				<view class="flex flex-col gap-4">
					<view v-for="item in contentList" :key="item.id">
						<view v-if="item.type == 2">
							<chat-msg-item
								ref="chatMsgItemRef"
								:type="item.type"
								:logo="item.logo"
								:content="item.reply"
								:auto="item.auto"
								:link="item.link"
								:loading="item.loading"
								:duration="item.duration"></chat-msg-item>
						</view>
						<view v-if="item.type == 1" class="flex justify-end">
							<chat-msg-item
								ref="chatMsgItemRef"
								:type="item.type"
								:content="item.message"
								:link="item.link"
								:duration="item.duration"></chat-msg-item>
						</view>
					</view>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script setup lang="ts">
import ChatMsgItem from "../chat-msg-item/chat-msg-item.vue";
import { getRect } from "@/utils/util";

const props = withDefaults(
	defineProps<{
		chatType: "text" | "audio";
		contentList: any[];
	}>(),
	{
		chatType: "text",
		contentList: () => [],
	}
);

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
