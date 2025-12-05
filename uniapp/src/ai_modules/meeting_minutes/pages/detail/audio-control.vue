<template>
	<view class="flex items-center gap-2">
		<view class="w-[42rpx] h-[42rpx]" @click="togglePlay">
			<image
				src="@/ai_modules/meeting_minutes/static/icons/play.svg"
				class="w-full h-full"
				v-if="!isPlaying"></image>
			<image
				src="@/ai_modules/meeting_minutes/static/icons/pause.svg"
				class="w-full h-full"
				v-else></image>
		</view>
		<view class="flex items-center gap-3 grow">
			<view class="text-[#A1A2B4] text-xs flex-shrink-0">{{
				formatAudioTime(currentTime)
			}}</view>
			<view
				class="bg-white rounded-full flex-1 h-[6rpx] relative parent"
				@click="clickProgress">
				<view
					class="absolute top-0 left-0 w-full h-full bg-white"></view>
				<view
					class="bg-primary h-full rounded-full relative z-10"
					:style="{ width: `${progress}%` }"></view>
			</view>
			<view class="text-[#A1A2B4] text-xs flex-shrink-0">{{
				formatAudioTime(duration)
			}}</view>
		</view>
	</view>
</template>

<script setup lang="ts">
import { useAudio } from "@/hooks/useAudio";
import { formatAudioTime, getRect } from "@/utils/util";

const props = defineProps<{
	url: string;
}>();

const emit = defineEmits(["update-time"]);

const { setUrl, play, pause, seek, duration, currentTime, isPlaying } =
	useAudio({
		onTimeUpdate(time) {
			emit("update-time", time);
		},
	});

// 播放、暂停切换
const togglePlay = () => {
	if (isPlaying.value) {
		pause();
	} else {
		play();
	}
};

// 计算进度
const progress = computed(() => {
	if (currentTime.value && duration.value) {
		return Math.floor(
			Math.max(0, Math.min(100, currentTime.value / duration.value)) * 100
		);
	}
	return 0;
});

const { proxy }: any = getCurrentInstance();
// 点击进度条， 设置播放进度
const clickProgress = async (e: any) => {
	const clickX = e.detail.x;
	const res: any = await getRect(".parent", false, proxy);
	const progressStartX = res.left;
	const progressWidth = res.width;

	// 计算点击点相对于进度条的距离
	const relativeClickX = clickX - progressStartX;

	let percentage = (relativeClickX / progressWidth) * 100;
	const seconds = (percentage / 100) * duration.value;
	seek(seconds);
};

watch(
	() => props.url,
	(url) => {
		if (url) {
			setUrl(url);
		}
	},
	{
		immediate: true,
	}
);

defineExpose({
	seek,
	pause,
});
</script>

<style scoped></style>
