import {
    drawPptChapters,
    drawPptFollowup,
    drawPptSubmitSlides,
    getDrawAssetUrls,
} from "@/api/draw";
import { useAppStore } from "@/stores/app";
import {
    PPT_PAGE_RANGE_OPTIONS,
    PPT_SCENES,
    resolvePptPageCount,
} from "../enums/workbench";
import { DRAW_POLL_ABORTED, useDrawTaskPoll } from "./useDrawTaskPoll";

export interface PptSlideItem {
    page: number;
    title: string;
    content: string;
    url?: string;
    loading?: boolean;
    error?: string;
}

export interface PptFollowupResult {
    description: string;
    pptType: string;
    fields: any[];
}

export interface PptGenerateOptions {
    topic: string;
    pageCount?: number;
    pptScene?: string;
    summary?: Record<string, string>;
    pptType?: string;
    audience?: string;
    style?: string;
    /** 逐页更新时回调（同一 slides 引用） */
    onSlidesUpdate?: (slides: PptSlideItem[]) => void;
}

function pickStyleAudience(summary?: Record<string, string>) {
    const s = summary || {};
    return {
        style: s["PPT风格偏好"] ?? s["风格偏好"] ?? "",
        audience: s["汇报对象"] ?? s["目标观众"] ?? "",
    };
}

function normalizeChapters(raw: any): Array<{ page: number; title: string; content: string }> {
    const list = Array.isArray(raw?.pages)
        ? raw.pages
        : Array.isArray(raw?.chapters)
          ? raw.chapters
          : Array.isArray(raw?.slides)
            ? raw.slides
            : Array.isArray(raw)
              ? raw
              : [];
    return list.map((item: any, index: number) => ({
        page: Number(item?.page || index + 1),
        title: String(item?.title || `第 ${index + 1} 页`),
        content: String(item?.content || item?.summary || ""),
    }));
}

function pickTaskNo(res: any): { taskNo: string; error: string } {
    const hit = Array.isArray(res?.tasks) ? res.tasks[0] : null;
    const taskNo = String(hit?.task_no || res?.task_no || res?.task?.task_no || "").trim();
    const error = String(hit?.error || res?.error || "").trim();
    return { taskNo, error };
}

export function useWorkbenchPpt() {
    const appStore = useAppStore();
    const { pollTask, clearAllPolling } = useDrawTaskPoll();

    /** 可为预设区间（如 15-25页）或自定义（如 12页），对齐 PC */
    const pageRange = ref<string>("15-25页");
    const scene = ref<(typeof PPT_SCENES)[number] | string>("通用");
    /** 对齐 PC：智能追问默认关闭 */
    const followupOn = ref(false);
    const selectedModelId = ref("");
    /** 兼容旧绑定：解析后的页数 */
    const pageCount = computed({
        get: () => resolvePptPageCount(pageRange.value),
        set: (n: number) => {
            const num = Math.floor(Number(n));
            if (!Number.isFinite(num) || num < 1) return;
            const clamped = Math.min(99, num);
            const hit = PPT_PAGE_RANGE_OPTIONS.find((label) => resolvePptPageCount(label) === clamped);
            pageRange.value = hit || `${clamped}页`;
        },
    });

    const conversationId = ref<number>(0);
    const slides = ref<PptSlideItem[]>([]);
    const topic = ref("");
    let submitEpoch = 0;
    let turnKey = "";

    /** PPT 生图仅 image-2 / gpt-image-2（对齐 PC） */
    const pptModels = computed(() => {
        const list = (appStore.getDrawModel || []) as any[];
        return list.filter((m) => {
            if (String(m.status) !== "1") return false;
            const name = String(m.name || "")
                .trim()
                .toLowerCase();
            const alias = String(m.alias || "")
                .trim()
                .toLowerCase();
            return name === "image-2" || alias === "gpt-image-2";
        });
    });

    const currentModel = computed(
        () =>
            pptModels.value.find((m) => String(m.id) === String(selectedModelId.value)) ||
            pptModels.value[0] ||
            null,
    );

    watch(
        pptModels,
        (list) => {
            if (!list.length) {
                selectedModelId.value = "";
                return;
            }
            if (!list.some((m) => String(m.id) === String(selectedModelId.value))) {
                selectedModelId.value = String(list[0].id);
            }
        },
        { immediate: true },
    );

    const assertNotCancelled = (epoch: number) => {
        if (epoch !== submitEpoch) throw new Error(DRAW_POLL_ABORTED);
    };

    const normalizeFollowupOptions = (raw: any): string[] => {
        let list: any = raw;
        if (typeof list === "string") {
            const s = list.trim();
            if (!s) return [];
            try {
                list = JSON.parse(s);
            } catch {
                list = s.split(/[,，、\n|/]/).map((x) => x.trim()).filter(Boolean);
            }
        }
        if (!Array.isArray(list)) return [];
        return list
            .map((o) => {
                if (o == null) return "";
                if (typeof o === "string" || typeof o === "number" || typeof o === "boolean") {
                    return String(o).trim();
                }
                if (typeof o === "object") {
                    return String(o.label ?? o.value ?? o.name ?? o.text ?? o.title ?? "").trim();
                }
                return String(o).trim();
            })
            .filter(Boolean);
    };

    const normalizeFollowupFields = (fields: any[]): any[] => {
        return (Array.isArray(fields) ? fields : []).map((q: any, idx: number) => {
            const typeRaw = String(q?.field_type ?? q?.type ?? "input")
                .trim()
                .toLowerCase();
            let field_type = typeRaw;
            if (typeRaw === "single_choice") field_type = "radio";
            else if (typeRaw === "multi_choice" || typeRaw === "multiple_choice") field_type = "checkbox";
            else if (typeRaw === "dropdown" || typeRaw === "select_list") field_type = "select";
            else if (typeRaw === "int" || typeRaw === "integer") field_type = "number";
            else if (typeRaw === "long_text" || typeRaw === "multiline") field_type = "textarea";
            else if (typeRaw === "string" || typeRaw === "short_text" || typeRaw === "text") {
                field_type = "input";
            }
            const options = normalizeFollowupOptions(
                q?.options ?? q?.choices ?? q?.enum ?? q?.items ?? q?.option,
            );
            return {
                id: q?.id ?? q?.field ?? q?.name ?? `q_${idx}`,
                label: q?.label ?? q?.question ?? q?.title ?? `问题 ${idx + 1}`,
                description: q?.description ?? "",
                field_type,
                default_value: q?.default_value ?? "",
                options,
                placeholder: q?.placeholder ?? "",
                required: q?.required ?? false,
                max_length: q?.max_length,
            };
        });
    };

    const fetchFollowup = async (inputTopic: string): Promise<PptFollowupResult> => {
        const epoch = submitEpoch;
        const t = inputTopic.trim();
        if (!t) throw new Error("请输入 PPT 主题");
        const res: any = await drawPptFollowup({ topic: t });
        assertNotCancelled(epoch);
        return {
            description: String(res?.description || ""),
            pptType: String(res?.ppt_type || ""),
            fields: normalizeFollowupFields(res?.fields),
        };
    };

    const generateSlideImage = async (
        epoch: number,
        opts: {
            topic: string;
            slide: PptSlideItem;
            index: number;
            total: number;
            pptType?: string;
            audience?: string;
            style?: string;
            writeUser: boolean;
        },
    ) => {
        if (!currentModel.value) throw new Error("暂无可用模型");
        const res: any = await drawPptSubmitSlides({
            model:
                currentModel.value.alias ||
                currentModel.value.name ||
                currentModel.value.id,
            topic: opts.topic,
            slides: [
                {
                    page: opts.slide.page,
                    title: opts.slide.title,
                    content: opts.slide.content,
                },
            ],
            total_pages: opts.total,
            is_cover: opts.index === 0,
            ppt_type: opts.pptType || undefined,
            audience: opts.audience || undefined,
            style: opts.style || undefined,
            conversation_id: conversationId.value || undefined,
            turn_key: turnKey || undefined,
            write_user: opts.writeUser,
        });
        assertNotCancelled(epoch);

        const cid = Number(res?.conversation_id || conversationId.value || 0);
        if (cid > 0) conversationId.value = cid;

        const { taskNo, error } = pickTaskNo(res);
        let assetUrls = getDrawAssetUrls(res?.task || res);
        if (taskNo) {
            const task = await pollTask(taskNo);
            assertNotCancelled(epoch);
            assetUrls = getDrawAssetUrls(task);
        } else if (error) {
            throw new Error(error);
        }
        opts.slide.url = assetUrls[0] || "";
        opts.slide.loading = false;
        if (!opts.slide.url) {
            opts.slide.error = error || "未返回图片";
        }
    };

    const generateFromOptions = async (opts: PptGenerateOptions) => {
        const epoch = submitEpoch;
        const t = opts.topic.trim();
        if (!t) throw new Error("请输入 PPT 主题");
        if (!currentModel.value) throw new Error("暂无可用模型（需启用 image-2）");

        topic.value = t;
        const count = Math.max(1, opts.pageCount || pageCount.value);
        const pptScene = opts.pptScene || scene.value;
        const summary = opts.summary;
        const { style, audience } = pickStyleAudience(summary);
        const finalStyle = opts.style || style;
        const finalAudience = opts.audience || audience;

        const chapterRes: any = await drawPptChapters({
            topic: t,
            page_count: count,
            ppt_scene: pptScene,
            summary,
        });
        assertNotCancelled(epoch);

        let chapterList = normalizeChapters(chapterRes);
        if (!chapterList.length) throw new Error("未能生成章节大纲");
        if (chapterList.length > count) chapterList = chapterList.slice(0, count);

        const nextSlides: PptSlideItem[] = chapterList.map((item) => ({
            ...item,
            loading: true,
            url: "",
            error: "",
        }));
        slides.value = nextSlides;
        opts.onSlidesUpdate?.(nextSlides);

        turnKey = `ppt_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
        const urls: string[] = [];

        for (let i = 0; i < nextSlides.length; i++) {
            assertNotCancelled(epoch);
            const slide = nextSlides[i];
            try {
                await generateSlideImage(epoch, {
                    topic: t,
                    slide,
                    index: i,
                    total: nextSlides.length,
                    pptType: opts.pptType,
                    audience: finalAudience,
                    style: finalStyle,
                    writeUser: i === 0,
                });
                if (slide.url) urls.push(slide.url);
            } catch (error: any) {
                if (String(error?.message || "").includes(DRAW_POLL_ABORTED)) throw error;
                slide.loading = false;
                slide.error = error?.message || error || "生成失败";
            }
            opts.onSlidesUpdate?.(nextSlides);
        }

        assertNotCancelled(epoch);
        return {
            topic: t,
            slides: nextSlides,
            urls,
            conversationId: conversationId.value,
            pageCount: count,
            pptScene,
            summary,
            pptType: opts.pptType || "",
        };
    };

    /** 兼容旧调用：直接用当前工具栏配置生成 */
    const submit = async (inputTopic: string) => {
        return generateFromOptions({
            topic: inputTopic,
            pageCount: pageCount.value,
            pptScene: scene.value,
        });
    };

    const regenerateAll = async (opts: PptGenerateOptions) => {
        return generateFromOptions(opts);
    };

    const regenerateSlide = async (opts: {
        topic: string;
        slides: PptSlideItem[];
        index: number;
        pptType?: string;
        summary?: Record<string, string>;
    }) => {
        const epoch = submitEpoch;
        const slide = opts.slides[opts.index];
        if (!slide) throw new Error("幻灯片不存在");
        if (!currentModel.value) throw new Error("暂无可用模型");

        const { style, audience } = pickStyleAudience(opts.summary);
        slide.url = "";
        slide.error = "";
        slide.loading = true;

        try {
            await generateSlideImage(epoch, {
                topic: opts.topic,
                slide,
                index: opts.index,
                total: opts.slides.length,
                pptType: opts.pptType,
                audience,
                style,
                writeUser: false,
            });
        } catch (error: any) {
            if (String(error?.message || "").includes(DRAW_POLL_ABORTED)) throw error;
            slide.loading = false;
            slide.error = error?.message || error || "生成失败";
            throw error;
        }
        return slide;
    };

    const resetConversation = () => {
        conversationId.value = 0;
        slides.value = [];
        topic.value = "";
        turnKey = "";
    };

    const cancelPending = () => {
        submitEpoch += 1;
        clearAllPolling();
    };

    return {
        pageRange,
        pageCount,
        scene,
        followupOn,
        selectedModelId,
        conversationId,
        slides,
        topic,
        pptModels,
        currentModel,
        fetchFollowup,
        generateFromOptions,
        submit,
        regenerateAll,
        regenerateSlide,
        cancelPending,
        resetConversation,
        PPT_PAGE_RANGE_OPTIONS,
        PPT_SCENES,
    };
}
