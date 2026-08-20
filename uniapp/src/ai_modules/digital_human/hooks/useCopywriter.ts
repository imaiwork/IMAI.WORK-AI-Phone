import { lpSceneSpeechToText } from "@/api/ladder_player";
import { useAudio } from "@/hooks/useAudio";
import useUpload from "@/hooks/useUpload";
import type { ShallowRef } from "vue";
import RecorderControl from "@/ai_modules/digital_human/components/recorder-control/recorder-control.vue";

export const COPYWRITER_LIMIT = 1000;

export const enum CopywriterTab {
    TEXT = 0,
    AUDIO = 1,
}

export const COPYWRITER_TABS = ["选择文案", "选择音频"] as const;

interface UseAudioOptions {
    formData: any;
    recorderRef: ShallowRef<InstanceType<typeof RecorderControl> | undefined>;
}

export function useCopywriter({ formData, recorderRef }: UseAudioOptions) {
    const copywriterTypeIndex = ref<CopywriterTab>(CopywriterTab.TEXT);
    const editCopywriterIndex = ref(-1);
    const currentAudioIndex = ref(-1);
    const showAudioType = ref(false);
    const showRecorder = ref(false);

    const { play, pause, isPlaying, destroy } = useAudio();

    // ─── 文案校验 ────────────────────────────────────────────────

    const isSingleCopywriterValid = (text: string): boolean =>
        text.trim().length >= 3 && text.length <= COPYWRITER_LIMIT;

    // ─── 跳转手动输入页 ──────────────────────────────────────────

    const handleShowCopywriter = (data?: any): void => {
        uni.navigateTo({
            url: "/ai_modules/digital_human/pages/montage_copywriter/montage_copywriter",
            success: (res) => {
                res.eventChannel.emit("sendData", {
                    content: data?.content,
                    title: data?.title,
                    limit: COPYWRITER_LIMIT,
                });
            },
        });
    };

    // ─── 点击已有文案卡片（编辑） ────────────────────────────────

    const handleSelectCopywriter = (index: number): void => {
        editCopywriterIndex.value = index;
        handleShowCopywriter(formData.copywriterList[index]);
    };

    // ─── 跳转 AI 生成文案页 ──────────────────────────────────────

    const handleSelectAgent = (res: any): void => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter",
            params: { agentData: JSON.stringify(res.data) },
        });
    };

    // ─── 删除文案 / 音频 ─────────────────────────────────────────

    const handleDeleteCopywriter = (index: number): void => {
        if (copywriterTypeIndex.value === CopywriterTab.TEXT) {
            formData.copywriterList.splice(index, 1);
        } else {
            formData.audio.splice(index, 1);
        }
    };

    // ─── 音频播放 ────────────────────────────────────────────────

    const handlePlayAudio = (url: string, index: number): void => {
        currentAudioIndex.value = index;
        isPlaying.value ? pause() : play(url);
    };

    // ─── 上传音频文件并识别文字 ──────────────────────────────────

    const {
        uploadAndProcessFiles: uploadAudio,
        showUploadProgress: showUploadAudioProgress,
        uploadMaterialList: uploadAudioMaterialList,
    } = useUpload({
        count: 1,
        isFetchVideoInfo: true,
        fileAccept: ["mp3", "wav", "m4a", "MP3", "WAV", "M4A"],
        fileSize: 100,
        onSuccess: async (res: any) => {
            const { url } = res[0];
            uni.showLoading({ title: "正在识别音频", mask: true });
            try {
                const { message, audio_duration } = await lpSceneSpeechToText({ audio: url });
                formData.audio.push({ content: message, url, duration: audio_duration });
                showAudioType.value = false;
            } catch (error: unknown) {
                const msg = typeof error === "string" ? error : "音频识别失败";
                uni.showToast({ title: msg, icon: "none", duration: 3000 });
            } finally {
                uni.hideLoading();
            }
        },
    });

    // ─── 录音 ────────────────────────────────────────────────────

    const openRecorder = async (): Promise<void> => {
        showAudioType.value = false;
        await recorderRef.value?.authorize(recorderRef.value.proxy);
        showRecorder.value = true;
    };

    const recorderSuccess = (res: any): void => {
        console.log(res);
        formData.audio.push({
            url: res.link,
            duration: res.duration,
            content: res.message,
        });
        showRecorder.value = false;
    };

    // ─── EventBus 回调：文案写入 ─────────────────────────────────

    /**
     * 供 onLoad 的 on("confirm") 调用
     * 处理手动输入 / AI 生成文案的回填
     */
    const onCopywriterConfirm = (data: any[]): void => {
        if (!data.length) return;
        if (editCopywriterIndex.value !== -1) {
            formData.copywriterList[editCopywriterIndex.value] = data[0];
            editCopywriterIndex.value = -1;
        } else {
            formData.copywriterList = formData.copywriterList.concat(data);
        }
    };

    return {
        // 状态
        copywriterTypeIndex,
        editCopywriterIndex,
        currentAudioIndex,
        showAudioType,
        showRecorder,
        isPlaying,
        // 上传
        showUploadAudioProgress,
        uploadAudioMaterialList,
        uploadAudio,
        // 方法
        isSingleCopywriterValid,
        handleShowCopywriter,
        handleSelectCopywriter,
        handleSelectAgent,
        handleDeleteCopywriter,
        handlePlayAudio,
        openRecorder,
        recorderSuccess,
        onCopywriterConfirm,
        destroy,
    };
}
