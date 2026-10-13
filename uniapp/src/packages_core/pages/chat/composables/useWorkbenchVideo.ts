import {
    drawGenerateVideo,
    drawOptimizeVideoPrompt,
    getDrawVideoUrls,
} from "@/api/draw";
import { useAppStore } from "@/stores/app";
import { DRAW_POLL_ABORTED, useDrawTaskPoll } from "./useDrawTaskPoll";

/** 对齐 PC：视频固定 5 秒，无时长选择 */
const VIDEO_FIXED_SECONDS = 5;

function toVideoResolutionTier(raw?: string): "480p" | "720p" | "1080p" {
    const s = String(raw || "720p").toLowerCase();
    if (s.includes("1080") || s === "1080p") return "1080p";
    if (s.includes("480") || s === "480p") return "480p";
    return "720p";
}

function ratioToAspect(ratio: string): number {
    const m = String(ratio || "").match(/(\d+)\s*:\s*(\d+)/);
    if (!m) return 16 / 9;
    const w = Number(m[1]);
    const h = Number(m[2]);
    return w > 0 && h > 0 ? w / h : 16 / 9;
}

export function useWorkbenchVideo() {
    const appStore = useAppStore();
    const { pollTask, clearAllPolling } = useDrawTaskPoll();

    const conversationId = ref<number>(0);
    const selectedModelId = ref("");
    const optimizePrompt = ref(false);
    const ratio = ref("16:9");
    const resolution = ref("720p");
    const refImage = ref("");
    let submitEpoch = 0;

    const sizeLabel = computed(() => `${ratio.value} · ${resolution.value}`);
    const aspectRatio = computed(() => ratioToAspect(ratio.value));

    const videoModels = computed(() => {
        const list = (appStore.getDrawModel || []) as any[];
        return list.filter((m) => m.media_type === "video" && String(m.status) === "1");
    });

    const currentModel = computed(
        () =>
            videoModels.value.find((m) => String(m.id) === String(selectedModelId.value)) ||
            videoModels.value[0] ||
            null,
    );

    const currentModelName = computed(
        () => currentModel.value?.name || currentModel.value?.alias || "选择模型",
    );

    watch(
        videoModels,
        (list) => {
            if (!list.length) return;
            if (!list.some((m) => String(m.id) === String(selectedModelId.value))) {
                selectedModelId.value = String(list[0].id);
            }
        },
        { immediate: true },
    );

    const assertNotCancelled = (epoch: number) => {
        if (epoch !== submitEpoch) throw new Error(DRAW_POLL_ABORTED);
    };

    /** 后端 optimize* 返回 data.content（也可能是数组），兼容旧 prompt/keywords */
    const pickOptimizeText = (opt: any, fallback: string) => {
        const raw = opt?.content ?? opt?.prompt ?? opt?.keywords ?? "";
        if (Array.isArray(raw)) {
            const hit = raw.find((x) => String(x || "").trim());
            return String(hit || "").trim() || fallback;
        }
        return String(raw || "").trim() || fallback;
    };

    /** 优化提示词（对齐图像：由聊天卡片确认后再生视频） */
    const optimizeKeywords = async (keywords: string) => {
        const epoch = submitEpoch;
        const raw = keywords.trim();
        if (!raw) throw new Error("请输入视频提示词");
        const opt: any = await drawOptimizeVideoPrompt({ keywords: raw });
        assertNotCancelled(epoch);
        return pickOptimizeText(opt, raw);
    };

    /** 直接生视频（不再静默优化） */
    const submit = async (prompt: string) => {
        const epoch = submitEpoch;
        const finalPrompt = prompt.trim();
        if (!finalPrompt) throw new Error("请输入视频提示词");
        if (!currentModel.value) throw new Error("暂无可用视频模型");

        const hasRef = !!refImage.value;
        const resTier = toVideoResolutionTier(resolution.value);
        const res: any = await drawGenerateVideo({
            model: currentModel.value.alias || currentModel.value.name || currentModel.value.id,
            model_name: currentModel.value.name || "",
            prompt: finalPrompt,
            conversation_id: conversationId.value || undefined,
            attachments: hasRef ? [refImage.value] : [],
            params: {
                prompt: finalPrompt,
                aspect_ratio: ratio.value,
                resolution: resTier,
                seconds: String(VIDEO_FIXED_SECONDS),
                duration: VIDEO_FIXED_SECONDS,
                ...(hasRef ? { image: refImage.value } : {}),
                metadata: {
                    resolution: resTier,
                    ratio: hasRef ? "adaptive" : ratio.value,
                },
            },
        });
        assertNotCancelled(epoch);

        const taskNo = String(res?.task_no || res?.task?.task_no || "");
        const cid = Number(res?.conversation_id || conversationId.value || 0);
        if (cid > 0) conversationId.value = cid;
        if (!taskNo) {
            return {
                prompt: finalPrompt,
                urls: getDrawVideoUrls(res?.task || res),
                conversationId: conversationId.value,
                ratio: ratio.value,
                resolution: resTier,
            };
        }

        const task = await pollTask(taskNo);
        assertNotCancelled(epoch);
        return {
            prompt: finalPrompt,
            urls: getDrawVideoUrls(task),
            conversationId: conversationId.value,
            task,
            ratio: ratio.value,
            resolution: resTier,
        };
    };

    const resetConversation = () => {
        conversationId.value = 0;
        refImage.value = "";
    };

    const cancelPending = () => {
        submitEpoch += 1;
        clearAllPolling();
    };

    return {
        conversationId,
        selectedModelId,
        optimizePrompt,
        ratio,
        resolution,
        sizeLabel,
        aspectRatio,
        refImage,
        videoModels,
        currentModel,
        currentModelName,
        optimizeKeywords,
        submit,
        cancelPending,
        resetConversation,
    };
}
