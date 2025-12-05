<template>
	<view class="flex flex-col items-center justify-center">
		<image
			src="@/ai_modules/meeting_minutes/static/images/common/empty.png"
			:style="{
				width: iconSize + 'rpx',
				height: iconSize + 'rpx',
			}"></image>
		<view class="text-[#ACABB9]">{{ title }}</view>
	</view>
</template>

<script setup lang="ts">
withDefaults(
	defineProps<{
		title?: string;
		iconSize?: number;
	}>(),
	{
		title: "暂无会议记录",
		iconSize: 192,
	}
);
</script>

<style scoped></style>
