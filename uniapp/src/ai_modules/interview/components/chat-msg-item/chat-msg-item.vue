<template>
	<view class="px-3">
		<view v-if="type === 1" class="flex">
			<view class="flex flex-col gap-2 items-end mt-1">
				<view
					v-if="loading"
					class="px-4 flex items-center justify-center h-full rounded-[32rpx] rounded-tr-none p-4 bg-primary text-white">
					<view class="chat-loader"></view>
				</view>
				<template v-else>
					<view
						class="bg-primary rounded-3xl px-2 h-8 w-28 flex items-center gap-1 justify-end"
						@click="togglePlay()"
						v-if="link">
						<text class="text-xs text-white font-bold"
							>{{ duration }}″</text
						>
						<view
							class="w-[32rpx] h-[32rpx]"
							style="transform: rotateY(180deg)">
							<image
								src="@/ai_modules/interview/static/images/common/sound_white.png"
								class="w-full h-full"
								v-if="!isPlaying" />
							<image
								src="@/ai_modules/interview/static/images/common/sound_play_white.gif"
								class="w-full h-full"
								v-else />
						</view>
					</view>
					<view
						class="bg-primary rounded-[32rpx] rounded-tr-none px-4 py-3"
						v-if="content">
						<view class="text-white leading-[44rpx]">
							{{ content }}
						</view>
					</view>
				</template>
			</view>
		</view>
		<view class="flex gap-2" v-if="type === 2">
			<view class="flex-shrink-0 mt-1">
				<view
					class="w-[64rpx] h-[64rpx] flex items-center justify-center">
					<image
						:src="logo"
						class="w-full h-full rounded-full shadow shadow-light"
						mode="aspectFill"></image>
				</view>
			</view>
			<view class="flex flex-col gap-2 mt-1">
				<view
					v-if="loading"
					class="px-4 flex items-center justify-center h-full rounded-[32rpx] rounded-tl-none p-4 bg-[#FAEEE4]">
					<view class="chat-loader"></view>
				</view>
				<template v-else>
					<view
						class="bg-[#FAEEE4] rounded-3xl px-2 h-8 max-w-28"
						@click="togglePlay()"
						v-if="link">
						<view class="w-28 flex items-center gap-1 h-full">
							<image
								src="@/ai_modules/interview/static/images/common/sound_chat.png"
								class="w-[32rpx] h-[32rpx]"
								v-if="!isPlaying" />
							<image
								src="@/ai_modules/interview/static/images/common/sound_play_chat.gif"
								class="w-[32rpx] h-[32rpx]"
								v-else />
							<text class="text-xs text-[#8C582D] font-bold">
								{{ duration }}″</text
							>
						</view>
					</view>
					<view
						class="bg-[#FAEEE4] text-[#524B6B] rounded-[32rpx] rounded-tl-none px-4 py-3 leading-[44rpx]"
						v-if="content">
						<ua-markdown :content="content" />
					</view>
				</template>
			</view>
		</view>
	</view>
</template>

<script lang="ts" setup>
import { useAudio } from "@/hooks/useAudio";

const props = defineProps({
	content: {
		type: String,
		default: "",
	},
	loading: {
		type: Boolean,
		default: false,
	},
	type: {
		type: Number,
	},
	duration: {
		type: [String, Number],
		default: "",
	},
	link: {
		type: String,
		default: "",
	},
	logo: {
		type: String,
		default: "",
	},
	auto: {
		type: Boolean,
		default: false,
	},
});

const { play, setUrl, pause, pauseAll, isPlaying } = useAudio();

const togglePlay = () => {
	if (props.loading) return;
	setUrl(props.link);
	if (isPlaying.value) {
		pause();
	} else {
		play();
	}
};

watch(
	() => props.auto,
	(val) => {
		if (val) {
			togglePlay();
		}
	}
);

defineExpose({
	play,
	pause,
	pauseAll,
	isPlaying,
});
</script>
