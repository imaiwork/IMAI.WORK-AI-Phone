import { onBeforeUnmount, ref, shallowRef } from "vue";

interface Options {
	onPlay?: () => void;
	onStop?: () => void;
	onPause?: () => void;
	onError?: (e: any) => void;
	onCanplay?: () => void;
	onTimeUpdate?: (e: any) => void;
	onDuration?: (duration: number) => void;
}

const audioCtxs = new Set<UniApp.InnerAudioContext>();
export const useAudio = (options?: Options) => {
	const audioCtx = shallowRef<UniApp.InnerAudioContext | null>(null);
	const isPlaying = ref(false);
	const duration = ref(0);
	const currentTime = ref(0);

	const onPlay = () => {
		isPlaying.value = true;
		options?.onPlay?.();
	};
	const onStop = () => {
		isPlaying.value = false;
		options?.onStop?.();
	};

	const onPause = () => {
		isPlaying.value = false;
		options?.onPause?.();
	};

	const onError = (e: any) => {
		isPlaying.value = false;
		options?.onError?.(e);
	};
	const onCanplay = () => {
		duration.value = audioCtx.value?.duration || 0;
		if (duration.value == 0) {
			//处理微信小程序获取不到时长的bug
			setTimeout(() => {
				duration.value = audioCtx.value?.duration || 0;
				options?.onDuration?.(duration.value);
			}, 100);
		}
		options?.onCanplay?.();
		options?.onDuration?.(duration.value);
	};

	const onTimeUpdate = (e: any) => {
		currentTime.value = audioCtx.value?.currentTime || 0;
		options?.onTimeUpdate?.(currentTime.value);
	};

	const createAudio = () => {
		audioCtx.value = uni.createInnerAudioContext();
		audioCtxs.add(audioCtx.value);
		audioCtx.value.onCanplay(onCanplay);
		audioCtx.value.onPlay(onPlay);
		audioCtx.value.onEnded(onStop);
		audioCtx.value.onError(onError);
		audioCtx.value.onStop(onStop);
		audioCtx.value.onPause(onPause);
		audioCtx.value.onTimeUpdate(onTimeUpdate);
	};

	const destroy = () => {
		if (audioCtx.value) {
			audioCtx.value.destroy();
			audioCtxs.delete(audioCtx.value);
			audioCtx.value = null;
		}
	};
	const setUrl = (src: string) => {
		if (!audioCtx.value) {
			createAudio();
		}
		audioCtx.value!.src = src;
	};
	const play = async (src?: string) => {
		pauseAll();
		if (!audioCtx.value) {
			createAudio();
		}
		if (src) {
			setUrl(src);
		}
		audioCtx.value!.play();
	};
	const pause = () => {
		audioCtx.value?.pause();
	};

	const pauseAll = () => {
		audioCtxs.forEach((audio) => {
			if (!audio.paused) {
				audio.stop();
			}
		});
	};

	const stop = () => {
		audioCtx.value?.stop();
	};

	const seek = (time: number) => {
		audioCtx.value?.seek(time);
		currentTime.value = time;
		options?.onTimeUpdate?.(currentTime.value);
		// audioCtx.value?.play();
	};

	onBeforeUnmount(() => {
		if (isPlaying.value) {
			pause();
		}
		destroy();
	});
	return {
		pause,
		pauseAll,
		play,
		destroy,
		setUrl,
		stop,
		seek,
		duration,
		currentTime,
		isPlaying,
	};
};
