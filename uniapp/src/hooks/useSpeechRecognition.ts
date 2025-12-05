// useSpeechRecognition.ts

import { ref, onMounted, onUnmounted } from "vue";
import Crypto from "crypto-js";

interface Params {
	signCallback?: Function | null;
	secretkey?: string;
	secretid?: string;
	appid?: string;
	duration?: number;
	frameSize?: number;
	engine_model_type?: string;
	needvad?: number;
	voice_format?: number;
	hotword_id?: string;
	filter_dirty?: number;
	filter_modal?: number;
	filter_punc?: number;
	convert_num_mode?: number;
	word_info?: number;
	vad_silence_time?: number;
}

interface SpeechRecognitionOptions {
	params?: Params;
	onRecognitionStart?: (res: any) => void;
	onSentenceBegin?: (res: any) => void;
	onRecognitionResultChange?: (res: any) => void;
	onSentenceEnd?: (res: any) => void;
	onRecognitionComplete?: (res: any) => void;
	onError?: (res: any) => void;
	onRecorderStop?: (res: any) => void;
}

export default function useSpeechRecognition({
	params: initialParams = {},
	onRecognitionStart,
	onSentenceBegin,
	onRecognitionResultChange,
	onSentenceEnd,
	onRecognitionComplete,
	onError,
	onRecorderStop,
}: SpeechRecognitionOptions) {
	const plugin = requirePlugin("QCloudAIVoice");
	const speechRecognizerManager = plugin.speechRecognizerManager();

	const recognitionStarted = ref(false);
	const userParams = ref<Params>(initialParams);
	const defaultParams: Params = {
		duration: 5 * 1000 * 60,
		engine_model_type: "16k_zh",
		needvad: 1,
		voice_format: 8,
		filter_dirty: 2,
	};

	const params: Params = {
		...defaultParams,
		...userParams,
	};

	function toUint8Array(wordArray: any) {
		// Shortcuts
		const words = wordArray.words;
		const sigBytes = wordArray.sigBytes;

		// Convert
		const u8 = new Uint8Array(sigBytes);
		for (let i = 0; i < sigBytes; i++) {
			u8[i] = (words[i >>> 2] >>> (24 - (i % 4) * 8)) & 0xff;
		}
		return u8;
	}

	// 签名函数示例
	function signCallback(signStr: string) {
		const secretKey = params.secretkey;
		const hash = Crypto.HmacSHA1(signStr, secretKey);
		const bytes = toUint8Array(hash);
		return wx.arrayBufferToBase64(bytes);
	}

	const setParams = (params: Params) => {
		userParams.value = { ...defaultParams, ...params };
	};

	const speechStart = () => {
		speechRecognizerManager.start(userParams.value);
		recognitionStarted.value = true;
	};

	const speechStop = () => {
		speechRecognizerManager.stop();
		recognitionStarted.value = false;
	};

	onMounted(() => {
		speechRecognizerManager.OnRecognitionStart = (res: any) => {
			console.log("开始识别", res);
			if (onRecognitionStart) onRecognitionStart(res);
		};

		speechRecognizerManager.OnSentenceBegin = (res: any) => {
			console.log("一句话开始", res);
			if (onSentenceBegin) onSentenceBegin(res);
		};

		speechRecognizerManager.OnRecognitionResultChange = (res: any) => {
			console.log("识别变化时", res);
			if (onRecognitionResultChange) onRecognitionResultChange(res);
		};

		speechRecognizerManager.OnSentenceEnd = (res: any) => {
			console.log("一句话结束", res);
			if (onSentenceEnd) onSentenceEnd(res);
		};

		speechRecognizerManager.OnRecognitionComplete = (res: any) => {
			console.log("识别结束", res);
			if (onRecognitionComplete) onRecognitionComplete(res);
		};

		speechRecognizerManager.OnError = (res: any) => {
			if (onError) onError(res);
		};

		speechRecognizerManager.OnRecorderStop = (res: any) => {
			console.log("录音结束", res);
			if (onRecorderStop) onRecorderStop(res);
		};
	});

	onUnmounted(() => {
		speechStop();
	});

	return {
		speechStart,
		speechStop,
		setParams,
		recognitionStarted,
		onRecognitionStart,
		onSentenceBegin,
		onRecognitionResultChange,
		onSentenceEnd,
		onRecognitionComplete,
		onError,
		onRecorderStop,
	};
}
