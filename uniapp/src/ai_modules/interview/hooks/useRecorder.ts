import Recorder from "recorder-core";
import "recorder-core/src/engine/mp3";
import "recorder-core/src/engine/mp3-engine";
import "recorder-core/src/extensions/waveview";
// import "recorder-core/src/extensions/wavesurfer.view";
import RecordApp from "recorder-core/src/app-support/app";
import "../static/Recorder-UniCore/app-uni-support.js";
// #ifdef MP-WEIXIN
import "recorder-core/src/app-support/app-miniProgram-wx-support.js";
// #endif

Recorder.inInstal = true;

interface RecordOptions {
	type?: "mp3" | "wav";
	bitRate?: number;
	sampleRate?: number;
}

type Options = RecordOptions & UniApp.RecorderManagerStartOptions;
interface RecorderResult {
	tempFilePath: string;
	duration: number;
}

interface DataResult {
	pcmData: Int16Array;
	powerLevel: number;
	sampleRate: number;
	duration: number;
}
interface callbacks {
	onstart?(): void;
	onstop?(result: RecorderResult): void;
	onpause?(): void;
	onresume?(): void;
	ondata?(result: DataResult): void;
	ondrawAudio?(cb: Function, recorder: any): void;
	onerror?(error: any): void;
}

export default function useRecorder(options?: Options, callbacks?: callbacks) {
	options = options || {
		type: "mp3",
		sampleRate: 16000,
		bitRate: 32,
		duration: 5 * 60 * 100,
		numberOfChannels: 1, //录音通道数
		encodeBitRate: 64000,
		format: "mp3", //音频格式，有效值 aac/mp3 等
		frameSize: 1200, //指定帧大小，单位 KB
	};

	const isRecording = ref(false);
	const recordTime = ref<number>(0);
	/**
	 * @description 获取录音权限
	 * @returns
	 */
	const authorize = (): Promise<void> => {
		return new Promise(async (resolve, reject) => {
			RecordApp.RequestPermission(
				() => {
					resolve();
				},
				async (msg: string) => {
					const res = uni.showModal({
						title: "开启麦克风权限",
						content: "为了正常使用语音输入功能，请开启麦克风权限",
						confirmText: "去设置",
						success: async (res: any) => {
							if (res.confirm) {
								const { authSetting } = await uni.openSetting();
								if (authSetting["scope.record"]) {
									resolve();
								}
							}
						},
					});
				}
			);
		});
	};
	/**
	 *
	 * @param options
	 * @returns
	 * @description 开始录音
	 */
	const start = async () => {
		// 注册事件
		try {
			RecordApp.Start(
				{
					type: "mp3",
					bitRate: 16,
					sampleRate: 16000,
					onProcess: (
						buffers: any,
						powerLevel: any,
						bufferDuration: any,
						bufferSampleRate: any,
						newBufferIdx: any,
						asyncEnd: any
					) => {
						// 记录录音时长
						recordTime.value = bufferDuration;
						if (
							options?.duration &&
							recordTime.value > options?.duration
						) {
							stop();
							return;
						}
						callbacks?.ondata?.({
							pcmData: buffers[buffers.length - 1],
							powerLevel: powerLevel,
							sampleRate: bufferSampleRate,
							duration: bufferDuration,
						});
					},
				},
				async () => {
					const findCanvas = RecordApp.UniFindCanvas;
					callbacks?.onstart?.();
					callbacks?.ondrawAudio?.(findCanvas, Recorder);
				}
			);
		} catch (error) {
			return Promise.reject(error);
		}
	};
	const stop = () => {
		RecordApp.Stop((aBuf: any, duration: any, mime: any) => {
			RecordApp.UniSaveLocalFile(
				"recorder.mp3",
				aBuf,
				async (result: any) => {
					callbacks?.onstop?.({
						tempFilePath: result,
						duration: recordTime.value,
					});
				},
				(err: any) => {
					console.log(err);
				}
			);
			isRecording.value = false;
		});
	};

	const pause = () => {
		RecordApp.Pause(() => {
			callbacks?.onpause?.();
		});
	};

	const resume = () => {
		RecordApp.Resume(() => {
			callbacks?.onresume?.();
		});
	};

	onBeforeUnmount(() => {
		stop();
	});

	return {
		isRecording,
		recordTime,
		start,
		authorize,
		stop,
		resume,
		pause,
	};
}
