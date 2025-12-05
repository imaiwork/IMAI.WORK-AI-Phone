<template>
	<div
		class="flex items-center cursor-pointer hover:text-primary"
		:class="{ 'text-primary': isPlaying }"
		@click="togglePlay">
		<div class="line-clamp-1">{{ name }}</div>
		<span
			class="flex ml-[10px]"
			v-if="url"
			:class="{ '!opacity-100': isPlaying }">
			<Icon
				:name="`el-icon-${isPlaying ? 'VideoPause' : 'VideoPlay'}`"
				:size="18" />
		</span>
	</div>
</template>

<script lang="ts" setup>
import { useAudio } from "@/hooks/useAudioPlay";
const props = defineProps<{
	name: string;
	url: string;
}>();

const { pause, play, isPlaying } = useAudio();
const togglePlay = () => {
	if (!props.url) return;
	if (isPlaying.value) {
		pause();
	} else {
		play(props.url);
	}
};
</script>
