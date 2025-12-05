interface Options {
	onPlay?: () => void;
	onStop?: () => void;
	onPause?: () => void;
	onError?: (e: any) => void;
	onCanplay?: () => void;
}

const audioSet = new Set<HTMLAudioElement>();
export const useAudio = (options?: Options) => {
	const audio = shallowRef<HTMLAudioElement | null>(null);
	const isPlaying = ref(false);
	const duration = ref(0);
	const currentTime = ref(0);
	let intervalId: number | null = null;

	const onPlay = () => {
		isPlaying.value = true;
		startUpdatingCurrentTime();
		options?.onPlay?.();
	};
	const onStop = () => {
		isPlaying.value = false;
		stopUpdatingCurrentTime();
		options?.onStop?.();
	};

	const onPause = () => {
		isPlaying.value = false;
		stopUpdatingCurrentTime();
		options?.onPause?.();
	};

	const onError = (e: any) => {
		isPlaying.value = false;
		options?.onError?.(e);
	};

	const onCanplay = () => {
		duration.value = audio.value?.duration || 0;
		if (duration.value == 0) {
			setTimeout(() => {
				duration.value = audio.value?.duration || 0;
			}, 100);
		}
		options?.onCanplay?.();
	};

	const setUrl = (src: string) => {
		if (!audio.value) {
			createAudio();
		}

		audio.value!.src = src;
	};

	const play = async (src?: string) => {
		if (!audio.value) {
			createAudio();
		}
		if (src) {
			setUrl(src);
		}
		audio.value?.play();
	};
	const pause = () => {
		audio.value?.pause();
		isPlaying.value = false;
	};

	const pauseAll = () => {
		isPlaying.value = false;
		audioSet.forEach((audio) => {
			audio.pause();
			audio.currentTime = 0;
			//@ts-ignore
			audio.audioPlaying = false;
		});
	};
	const createAudio = () => {
		audio.value = new Audio();
		//@ts-ignore
		audio.value.audioPlaying = isPlaying.value;
		audioSet.add(audio.value);
		audio.value.onplay = () => {
			isPlaying.value = true;
		};
		audio.value.onended = () => {
			isPlaying.value = false;
		};
		audio.value.onerror = () => {
			isPlaying.value = false;
		};
	};

	const destroy = () => {
		if (audio.value) {
			audioSet.delete(audio.value);
			audio.value = null;
		}
	};

	const updateCurrentTime = () => {
		if (audio.value) {
			currentTime.value = audio.value.currentTime;
		}
	};

	const startUpdatingCurrentTime = () => {
		updateCurrentTime();
		if (intervalId === null) {
			// @ts-ignore
			intervalId = setInterval(updateCurrentTime, 1000); // 每秒更新一次
		}
	};

	const stopUpdatingCurrentTime = () => {
		if (intervalId !== null) {
			clearInterval(intervalId);
			intervalId = null;
		}
	};

	onBeforeUnmount(() => {
		if (audio.value?.src) {
			pauseAll();
		}
		if (audio.value) {
			audioSet.delete(audio.value);
			audio.value = null;
		}
	});

	return {
		pause,
		pauseAll,
		play,
		destroy,
		setUrl,
		stop,
		duration,
		isPlaying,
	};
};
