import {
    drawGenerateImage,
    drawOptimizeImagePrompt,
    getDrawAssetUrls,
} from "@/api/draw";
import { useAppStore } from "@/stores/app";
import {
    IMAGE_COUNT_OPTIONS,
    IMAGE_RATIO_OPTIONS,
    IMAGE_RATIO_PRESETS,
    IMAGE_REF_MAX,
    IMAGE_RESOLUTION_OPTIONS,
} from "../enums/workbench";
import { DRAW_POLL_ABORTED, useDrawTaskPoll } from "./useDrawTaskPoll";

function scaleByResolution(base: [number, number], resKey: string): [number, number] {
    const [bw, bh] = base;
    if (resKey !== "4k") return [bw, bh];
    const scale = 2160 / 1440;
    const snap = (n: number) => Math.max(16, Math.round((n * scale) / 16) * 16);
    return [snap(bw), snap(bh)];
}

/** 中台对这些模型只出 1 张，多要也只回 1 张，张数入口直接锁死 */
const SINGLE_IMAGE_MODELS = ["seedream4.0", "image-2"];

function isSingleImageModel(m: any): boolean {
    const keys = [m?.alias, m?.name].map((v) => String(v || "").trim().toLowerCase());
    return SINGLE_IMAGE_MODELS.some((k) => keys.includes(k));
}

export function useWorkbenchImage() {
    const appStore = useAppStore();
    const { pollTask, clearAllPolling } = useDrawTaskPoll();

    const ratio = ref<string>("9:16");
    const resolution = ref<string>("2k");
    const count = ref(1);
    const optimizePrompt = ref(false);
    const conversationId = ref<number>(0);
    const selectedModelId = ref("");
    const refImages = ref<string[]>([]);
    /** 取消令牌：cancelPending 后进行中的 submit 应丢弃结果 */
    let submitEpoch = 0;

    const sizeWH = computed(() => {
        const base = IMAGE_RATIO_PRESETS[ratio.value] || IMAGE_RATIO_PRESETS["9:16"];
        return scaleByResolution(base, resolution.value);
    });

    // 对齐 PC：config.draw_model.channel，按 media_type + status 过滤
    const imageModels = computed(() => {
        const list = (appStore.getDrawModel || []) as any[];
        return list.filter((m) => m.media_type === "image" && String(m.status) === "1");
    });

    const currentModel = computed(
        () =>
            imageModels.value.find((m) => String(m.id) === String(selectedModelId.value)) ||
            imageModels.value[0] ||
            null,
    );

    const imageMaxCount = computed(() => (isSingleImageModel(currentModel.value) ? 1 : 9));

    const sizeLabel = computed(() => {
        const r = IMAGE_RATIO_OPTIONS.find((o) => o.key === ratio.value);
        const res = IMAGE_RESOLUTION_OPTIONS.find((o) => o.key === resolution.value);
        const ratioText = r?.label || ratio.value;
        const resText = res?.short || "高清2K";
        const n = Math.max(1, Math.min(imageMaxCount.value, Number(count.value) || 1));
        return n > 1 ? `${ratioText} · ${resText} · ${n}张` : `${ratioText} · ${resText}`;
    });

    const clampCount = (n: number) => {
        const max = imageMaxCount.value;
        const next = Math.max(1, Math.min(max, Math.floor(Number(n) || 1)));
        count.value = next;
        return next;
    };

    watch(
        imageModels,
        (list) => {
            if (!list.length) return;
            if (!list.some((m) => String(m.id) === String(selectedModelId.value))) {
                selectedModelId.value = String(list[0].id);
            }
        },
        { immediate: true },
    );

    watch(
        currentModel,
        (m) => {
            if (isSingleImageModel(m) && count.value > 1) {
                count.value = 1;
            }
        },
        { immediate: true },
    );

    const addRefImage = (url: string) => {
        const u = String(url || "").trim();
        if (!u || refImages.value.includes(u)) return;
        if (refImages.value.length >= IMAGE_REF_MAX) {
            uni.$u.toast(`最多添加 ${IMAGE_REF_MAX} 张参考图`);
            return;
        }
        refImages.value.push(u);
    };

    const removeRefImage = (index: number) => {
        refImages.value.splice(index, 1);
    };

    /** 使用案例：参考图替换为案例图（对齐 PC chooseCaseImage） */
    const applyCaseRef = (pic: string) => {
        const u = String(pic || "").trim();
        refImages.value = u ? [u] : [];
    };

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

    /** 调用优化接口，返回可展示/可编辑的提示词（对齐 HTML 优化卡） */
    const optimizeKeywords = async (keywords: string) => {
        const epoch = submitEpoch;
        const raw = keywords.trim();
        if (!raw) throw new Error("请输入图片提示词");
        const opt: any = await drawOptimizeImagePrompt({ keywords: raw });
        assertNotCancelled(epoch);
        return pickOptimizeText(opt, raw);
    };

    /** 直接生图（不再静默优化；优化流程由聊天卡片确认后再调用） */
    const submit = async (prompt: string, refs?: string[]) => {
        const epoch = submitEpoch;
        const finalPrompt = prompt.trim();
        if (!finalPrompt) throw new Error("请输入图片提示词");
        if (!currentModel.value) throw new Error("暂无可用图片模型");

        const [width, height] = sizeWH.value;
        const resLabel =
            IMAGE_RESOLUTION_OPTIONS.find((o) => o.key === resolution.value)?.label || "高清 2K";

        const n = clampCount(count.value);
        // 对齐 PC：参考图走 attachments（会话展示）+ params.image（中台图生图）。
        // 之前用顶层 reference_images，服务端中台透传不认该字段，导致生成结果与参考图无关。
        const refList = (refs ?? refImages.value)
            .map((u) => String(u || "").trim())
            .filter(Boolean);
        const res: any = await drawGenerateImage({
            model: currentModel.value.alias || currentModel.value.name || currentModel.value.id,
            prompt: finalPrompt,
            conversation_id: conversationId.value || undefined,
            attachments: refList,
            params: {
                prompt: finalPrompt,
                ratio: ratio.value,
                resolution: resLabel,
                width,
                height,
                n,
                ...(refList.length ? { image: refList } : {}),
            },
        });
        assertNotCancelled(epoch);

        const taskNo = String(res?.task_no || res?.task?.task_no || "");
        const cid = Number(res?.conversation_id || conversationId.value || 0);
        if (cid > 0) conversationId.value = cid;
        if (!taskNo) {
            const urls = getDrawAssetUrls(res?.task || res);
            return { prompt: finalPrompt, urls, conversationId: conversationId.value };
        }

        const task = await pollTask(taskNo);
        assertNotCancelled(epoch);
        return {
            prompt: finalPrompt,
            urls: getDrawAssetUrls(task),
            conversationId: conversationId.value,
            task,
        };
    };

    const resetConversation = () => {
        conversationId.value = 0;
        refImages.value = [];
    };

    /** 取消进行中的优化/生图/轮询（退出页 / 新建会话 / 切历史时调用） */
    const cancelPending = () => {
        submitEpoch += 1;
        clearAllPolling();
    };

    return {
        ratio,
        resolution,
        count,
        imageMaxCount,
        optimizePrompt,
        conversationId,
        selectedModelId,
        imageModels,
        currentModel,
        refImages,
        sizeLabel,
        sizeWH,
        clampCount,
        addRefImage,
        removeRefImage,
        applyCaseRef,
        optimizeKeywords,
        submit,
        cancelPending,
        resetConversation,
        IMAGE_RATIO_OPTIONS,
        IMAGE_COUNT_OPTIONS,
        IMAGE_RESOLUTION_OPTIONS,
        IMAGE_REF_MAX,
    };
}
