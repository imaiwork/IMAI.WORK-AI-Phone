<template>
	<div class="h-full flex items-center px-4 gap-8">
		<div class="flex items-center gap-4">
			<div>
				<el-tooltip content="后退5秒">
					<div @click="handleTime(-5)">
						<Icon
							name="local-icon-replay5"
							color="var(--el-color-primary)"
							:size="24"></Icon>
					</div>
				</el-tooltip>
			</div>
			<button
				class="w-9 h-9 flex items-center justify-center bg-primary rounded-full"
				@click="toggle()">
				<Icon
					:name="
						isPlaying
							? 'local-icon-music_pause'
							: 'local-icon-music_play'
					"
					color="#ffffff"
					:size="18"></Icon>
			</button>
			<div>
				<el-tooltip content="快进5秒">
					<div @click="handleTime(5)">
						<Icon
							name="local-icon-neplay5"
							color="var(--el-color-primary)"
							:size="24"></Icon>
					</div>
				</el-tooltip>
			</div>
		</div>
		<div class="grow relative">
			<div
				class="absolute -bottom-[20px] left-0 text-[#27264d73] text-xs">
				{{ currentTime }}
			</div>
			<div
				class="relative py-2 group"
				@mousemove.stop="getCurrentTime"
				@click="clickProgress">
				<div
					class="w-full h-1 relative cursor-pointer progress-bar bg-primary-light-7 group">
					<div class="absolute top-0 left-0 w-full h-full flex">
						<div
							v-for="(item, index) in sections"
							:key="index"
							class="h-full absolute top-0 hover:h-[6px] z-50"
							:style="getParagraphStyle(item, index)"
							@mousemove="showSectionTooltip = true"
							@mouseleave="showSectionTooltip = false">
							<div
								class="bg-primary-light-6 min-w-[4px] mx-[2px] relative h-full">
								<div
									class="absolute top-0 left-0 h-full bg-primary max-w-full"
									:style="{
										width:
											sections.length == 1
												? `${progress}%`
												: item.width,
									}"></div>
							</div>
						</div>
					</div>
					<div
						class="absolute -top-[20px] -translate-x-1/2 -translate-y-full"
						v-if="showSectionTooltip && sections.length > 1"
						ref="sectionTooltipRef">
						<div
							class="rounded-lg p-4 bg-primary-light-7 min-w-[400px] text-xs">
							<div class="flex gap-1">
								<span class="text-[#585A73]">{{
									timeTooltip
								}}</span>
								<span class="text-primary font-bold">
									{{ currentSection.Headline }}
								</span>
							</div>
							<div class="text-[#585A73] mt-2 leading-[20px]">
								{{ currentSection.Summary }}
							</div>
						</div>
					</div>
				</div>
				<el-tooltip :content="timeTooltip" placement="top">
					<div
						class="absolute h-[16px] w-[2px] bg-primary -top-[4px] invisible group-hover:visible"
						:class="{
							'z-[8888]': sections.length == 1,
						}"
						ref="timeTooltipRef"
						@mousemove.stop="showSectionTooltip = false"></div>
				</el-tooltip>
			</div>
			<div
				class="absolute -bottom-[20px] right-0 text-[#27264d73] text-xs">
				{{ duration }}
			</div>
		</div>
		<div class="min-w-[50px]">
			<el-popover placement="top" popper-class="!w-[90px] !min-w-[90px]">
				<template #reference>
					<div class="text-primary cursor-pointer">
						{{ speed == SpeedEnum.SPEED_1 ? "倍速" : `${speed}x` }}
					</div>
				</template>
				<div class="flex flex-col justify-center items-center gap-4">
					<div
						class="hover:text-primary cursor-pointer"
						:class="{
							'text-primary': speed == item,
						}"
						v-for="item in speedList"
						@click="handleSpeedClick(item)">
						{{ item }}x
					</div>
				</div>
			</el-popover>
		</div>
	</div>
	<audio
		ref="audio"
		v-if="music"
		class="hidden"
		@ended="end"
		@timeupdate="updateTime"
		@canplay="onCanPlay"
		@loadedmetadata="onLoadedMetadata"
		@error="onError"></audio>
</template>

<script setup lang="ts">
import feedback from "@/utils/feedback";
import { formatAudioTime } from "@/utils/util";

const emit = defineEmits([
	"play",
	"pause",
	"next",
	"prev",
	"end",
	"update-time",
]);

// 定义组件属性类型
interface Props {
	music?: string | undefined;
	contentList?: any;
}

// 定义Props和Emit事件
const props = withDefaults(defineProps<Props>(), {
	music: "",
	contentList: [],
});

const sections = computed(() => {
	const { contentList } = props;
	return contentList.map((item: any) => {
		return {
			...item,
			width: 0,
		};
	});
});

const timeTooltipRef = ref<HTMLElement | null>(null);
const sectionTooltipRef = ref<HTMLElement | null>(null);
const showSectionTooltip = ref<boolean>(false);

// 倍速枚举
enum SpeedEnum {
	SPEED_2 = "2.0",
	SPEED_1_5 = "1.5",
	SPEED_1_25 = "1.25",
	SPEED_1 = "1.0",
	SPEED_0_75 = "0.75",
}
// 倍速
const speed = ref<SpeedEnum>(SpeedEnum.SPEED_1);
// 倍数列表
const speedList = [
	SpeedEnum.SPEED_2,
	SpeedEnum.SPEED_1_5,
	SpeedEnum.SPEED_1_25,
	SpeedEnum.SPEED_1,
	SpeedEnum.SPEED_0_75,
];

// 获取总时长
const getTotalTime = computed(() => {
	return sections.value.reduce((total: number, item: any) => {
		return total + (item.End - item.Start);
	}, 0);
});

// 获取段落宽度
const getParagraphStyle = (item: any, index: number) => {
	if (item) {
		const itemWidth = item.End - item.Start;
		// itemWidth 获取的是毫秒 要换算成进度宽度
		const progressWidth = (itemWidth / getTotalTime.value) * 100;
		// 获取段落left距离, 第一个段落left为0， 其他段落left为前面段落的End - 前面段落的Start的累加
		const left =
			(sections.value
				.slice(0, index)
				.reduce((total: number, item: any) => {
					return total + (item.End - item.Start);
				}, 0) /
				getTotalTime.value) *
			100;

		return {
			width: `${progressWidth}%`,
			left: `${left}%`,
		};
	}
	return {};
};

const musicVal = ref<string>(props.music);

const audio = ref<HTMLAudioElement | null>(null);
const progress = ref<number>(0);
const seekTime = ref<number>(0);
const isPlaying = ref<boolean>(false);
const currentTime = ref<string>("00:00");
const duration = ref<string>("00:00");
const isLoad = ref<boolean>(false);
const isError = ref<boolean>(false);

// 当前章节
const currentSection = ref<any>(null);

const timeTooltip = ref<string>("00:00");

// 初始化音频
const initAudio = async () => {
	await nextTick();
	if (audio.value) {
		audio.value.src = musicVal.value;
		audio.value.load();
	}
};

// 设置音频
const setAudio = async (url: string) => {
	musicVal.value = url;
	await nextTick();
	initAudio();
};

// 音频元数据加载事件处理
const onLoadedMetadata = () => {
	if (audio.value) {
		isLoad.value = true;
		duration.value = formatAudioTime(audio.value.duration);
	}
};

// 音频可以播放事件处理
const onCanPlay = () => {
	if (isPlaying.value && audio.value) {
		audio.value.play();
	}
	isLoad.value = false;
};

// 音频错误事件处理
const onError = () => {
	isError.value = true;
	isLoad.value = false;
};

const isErrorMusic = () => {
	if (isError.value || !musicVal.value) {
		feedback.msgError("无法播放音乐");
		isPlaying.value = false;
		return false;
	}
	if (isLoad.value) {
		feedback.msgError("播放器正在加载，请稍等");
		isPlaying.value = false;
		return false;
	}
	if (!audio.value) {
		feedback.msgError("播放器初始化失败");
		initAudio();
		isPlaying.value = false;
		return false;
	}
	return true;
};

// 播放音频
const play = () => {
	const isValid = isErrorMusic();
	if (!isValid) return;
	isPlaying.value = true;
	audio.value?.play();
	emit("play");
};

// 暂停音频
const pause = () => {
	emit("pause");
	isPlaying.value = false;
	audio.value?.pause();
};

// 切换播放/暂停状态
const toggle = () => {
	if (isPlaying.value) {
		pause();
	} else {
		play();
	}
};

// 重置播放器
const resetPlayer = () => {
	if (audio.value) {
		audio.value.src = props.music;
		audio.value.load();
	}
	isPlaying.value = true;
	isError.value = false;
	isLoad.value = false;
	currentTime.value = "00:00";
	duration.value = "00:00";
	progress.value = 0;
	isErrorMusic();
};

// 音频结束事件处理
const end = () => {
	isPlaying.value = false;
	emit("end");
};

// 获取当前音频时长
const getCurrentTime = (event: MouseEvent) => {
	const progressBar = document.querySelector(".progress-bar");
	if (progressBar) {
		const rect = progressBar.getBoundingClientRect();
		const x = event.clientX - rect.left;
		const currTime = (x / rect.width) * (audio.value?.duration || 0);
		currentSection.value =
			sections.value.find((item: any) => {
				return (
					currTime >= item.Start / 1000 && currTime < item.End / 1000
				);
			}) || {};
		timeTooltip.value = formatAudioTime(currTime);
		if (timeTooltipRef.value) {
			timeTooltipRef.value.style.left = `${x}px`;
		}
		if (sectionTooltipRef.value) {
			sectionTooltipRef.value.style.left = `${x}px`;
		}
	}
};

// 点击进度条更新进度
const clickProgress = (event: MouseEvent) => {
	const progressBar = document.querySelector(".progress-bar");
	if (progressBar) {
		const rect = progressBar.getBoundingClientRect();
		const x = event.clientX - rect.left;
		const percentage = (x / rect.width) * 100;
		progress.value = percentage;
		const seekTime = (progress.value / 100) * (audio.value?.duration || 0);
		calculateParagraphWidth(seekTime);
		seek(seekTime);
	}
};

// 计算段落宽度
const calculateParagraphWidth = (seekTime: number) => {
	sections.value.forEach((item: any) => {
		let { Start, End } = item;
		Start = Math.floor(Start / 1000);
		End = Math.ceil(End / 1000);
		if (seekTime >= End) {
			item.width = "100%";
		} else if (seekTime >= Start && seekTime < End) {
			item.width = `${((seekTime - Start) / (End - Start)) * 100}%`;
		} else {
			item.width = "0%";
		}
	});
};

// 松开进度条时跳转到指定位置
const seek = (value: number) => {
	if (audio.value) {
		audio.value.currentTime = value;
		currentTime.value = formatAudioTime(value);
	}
};

// 更新播放时间
const updateTime = () => {
	if (audio.value) {
		currentTime.value = formatAudioTime(audio.value.currentTime);
		if (sections.value.length > 1) {
			calculateParagraphWidth(audio.value.currentTime);
		} else {
			progress.value =
				(audio.value.currentTime / (audio.value?.duration || 0)) *
					100 || 0;
		}

		emit("update-time", audio.value.currentTime * 1000);
	}
};

// 更新播放时间
const handleTime = (value: number) => {
	if (audio.value) {
		audio.value.currentTime += value;
	} else {
		feedback.msgError("当前音频未加载");
	}
};

// 倍速点击事件
const handleSpeedClick = (item: SpeedEnum) => {
	if (audio.value) {
		speed.value = item;
		audio.value.playbackRate = Number(item);
	} else {
		feedback.msgError("当前音频未加载");
	}
};

watch(
	() => props.music,
	(value) => {
		if (value) {
			setAudio(value);
		}
	},
	{
		immediate: true,
	}
);

defineExpose({
	seek,
});
</script>

<style scoped></style>
