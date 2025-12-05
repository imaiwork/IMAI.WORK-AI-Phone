<template>
	<div class="p-3">
		<div v-if="type === 1" class="flex">
			<div class="flex flex-col gap-2 items-end">
				<div
					v-if="loading"
					class="px-4 flex items-center justify-center h-full rounded-[32rpx] rounded-tr-none p-4 bg-primary text-white">
					<div class="chat-loader"></div>
				</div>
				<template v-else>
					<div
						class="bg-primary rounded-3xl px-2 h-8 w-28 flex items-center gap-1 justify-end cursor-pointer"
						@click="togglePlay()"
						v-if="Number(duration) > 0">
						<span class="text-xs text-white font-bold"
							>{{ duration }}″</span
						>
						<div
							class="w-[16px] h-[16px]"
							style="transform: rotateY(180deg)">
							<img
								src="@/assets/images/sound_white.png"
								class="w-full h-full"
								v-if="!isPlaying" />
							<img
								src="@/assets/images/sound_play_white.gif"
								class="w-full h-full"
								v-else />
						</div>
					</div>
					<div
						class="bg-primary rounded-xl rounded-tr-none px-4 py-3">
						<div class="text-white leading-[44rpx]">
							{{ content }}
						</div>
						<div v-if="tips" class="bg-white rounded-xl p-3 mt-3">
							<div>
								<div class="flex items-center gap-x-1">
									<Icon
										name="local-icon-star_primary"
										size="20"></Icon>
									<span class="text-xs">改进建议</span>
								</div>
								<div
									class="text-xs text-[#828282] leading-[20px] mt-2 whitespace-pre-line">
									{{ tips.performance }}
								</div>
							</div>
							<div class="my-2">
								<el-divider />
							</div>
							<div>
								<div class="flex items-center gap-x-1">
									<Icon
										name="local-icon-tips_primary"
										size="20" />
									<span class="text-xs">话术提示</span>
								</div>
								<div
									class="text-xs text-[#828282] leading-[40rpx] mt-2 whitespace-pre-line">
									{{ tips.speechcraft }}
								</div>
							</div>
						</div>
					</div>
				</template>
			</div>
		</div>
		<div class="flex gap-2" v-if="type === 2">
			<div class="flex-shrink-0 mt-1">
				<div class="w-6 h-6 flex items-center justify-center">
					<img :src="logo" class="w-full h-full rounded-full" />
				</div>
			</div>
			<div class="flex flex-col gap-2">
				<div
					v-if="loading"
					class="px-4 flex items-center justify-center h-full rounded-[32rpx] rounded-tl-none p-4 bg-[#FAEEE4]">
					<div class="chat-loader"></div>
				</div>
				<template v-else>
					<div
						class="bg-[#FAEEE4] rounded-3xl px-2 h-8 max-w-28 cursor-pointer"
						@click="togglePlay()"
						v-if="Number(duration) > 0">
						<div class="w-28 flex items-center gap-1 h-full">
							<img
								src="@/assets/images/sound_chat.png"
								class="w-[16px] h-[16px]"
								v-if="!isPlaying" />
							<img
								src="@/assets/images/sound_play_chat.gif"
								class="w-[16px] h-[16px]"
								v-else />
							<span class="text-xs text-[#8C582D] font-bold"
								>{{ duration }}″</span
							>
						</div>
					</div>
					<div
						class="bg-[#FAEEE4] text-[#524B6B] rounded-xl rounded-tl-none px-4 py-3 leading-[22px]">
						{{ content }}
					</div>
				</template>
			</div>
		</div>
	</div>
</template>

<script lang="ts" setup>
import { useAudio } from "@/hooks/useAudioPlay";

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
	recordId: {
		type: [String, Number],
	},
	auto: {
		type: Boolean,
		default: false,
	},
	tips: {
		type: Object,
		default: null,
	},
});

const { play, pause, pauseAll, isPlaying } = useAudio();

const togglePlay = () => {
	if (props.loading) return;
	pauseAll();
	if (isPlaying.value) {
		pause();
	} else {
		play(props.link);
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
</script>
