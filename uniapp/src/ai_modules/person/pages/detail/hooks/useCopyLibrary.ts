import {
    addCopywritingLibrary,
    batchAddCopywritingLibrary,
    deleteCopywritingLibrary,
    getCopywritingLibraryList,
    importCopywritingLibrary,
    updateCopywritingLibrary,
} from "@/api/person";
import { uploadFile } from "@/api/app";
import { chooseFile } from "@/components/file-upload/choose-file";

// 文案库类型：1 视频驱动文案 / 2 发布文案
export enum CopyLibraryTypeEnum {
    DRIVE = 1,
    PUBLISH = 2,
}

// 视频驱动文案的驱动类型；发布文案固定 0
export enum CopyDriverTypeEnum {
    PUBLISH = 0,
    NEWS = 1,
    VOICEOVER = 2,
    CLIPS = 3,
}

export interface CopyItem {
    id: number;
    user_id?: number;
    persona_id?: number;
    library_type: number;
    driver_type: number;
    title: string;
    content: string;
    topic: string;
    source?: number;
    sort: number;
    status: number;
    use_count?: number;
    last_used_time?: number;
    is_used?: number;
    create_time?: string;
    update_time?: string;
}

export interface CopyFormData {
    id?: number;
    title: string;
    content: string;
    topic: string;
}

export const copyTabs = [
    { key: CopyLibraryTypeEnum.DRIVE, label: "视频驱动文案" },
    { key: CopyLibraryTypeEnum.PUBLISH, label: "发布文案" },
];

export const copyDriveTypes = [
    { key: CopyDriverTypeEnum.NEWS, label: "新闻体" },
    { key: CopyDriverTypeEnum.VOICEOVER, label: "口播文案" },
    { key: CopyDriverTypeEnum.CLIPS, label: "素材混剪口播" },
];

// 新闻体仅标题；口播 / 混剪 = 标题 + 内容；发布文案 = 标题 + 内容 + 话题
export const isNewsCopy = (libraryType: number, driverType: number): boolean =>
    libraryType === CopyLibraryTypeEnum.DRIVE && driverType === CopyDriverTypeEnum.NEWS;

export const isPublishCopy = (libraryType: number): boolean => libraryType === CopyLibraryTypeEnum.PUBLISH;

// 视频文案字数限制，与 digital_human 保持一致：
// 新闻体混剪文案 1000（NEWS_BODY_COPYWRITER_LIMIT）、口播 / 素材混剪口播文案 600（COPYWRITER_LIMIT）
export const COPY_NEWS_LIMIT = 1000;
export const COPY_VOICEOVER_LIMIT = 600;
// 标题 / 发布正文 / 话题：文案库自有字段（非视频驱动文案），取合理上限
export const COPY_TITLE_LIMIT = 50;
export const COPY_PUBLISH_CONTENT_LIMIT = 1000;
export const COPY_TOPIC_LIMIT = 200;

export interface CopyFieldLimits {
    title: number;
    content: number;
    topic: number;
}

// content / topic 为 0 表示该类型不展示对应字段
export const getCopyLimits = (libraryType: number, driverType: number): CopyFieldLimits => {
    if (isPublishCopy(libraryType)) {
        return { title: COPY_TITLE_LIMIT, content: COPY_PUBLISH_CONTENT_LIMIT, topic: COPY_TOPIC_LIMIT };
    }
    if (driverType === CopyDriverTypeEnum.NEWS) {
        return { title: COPY_NEWS_LIMIT, content: 0, topic: 0 };
    }
    return { title: COPY_TITLE_LIMIT, content: COPY_VOICEOVER_LIMIT, topic: 0 };
};

const COPY_IMPORT_FILE_ACCEPT = ["xlsx", "xls", "csv"];

// 视频驱动文案 → 复用视频 AI 文案页（ai_copywriter）的生成类型 montageType
// 取值对应 digital_human MontageTypeEnum：REAL_PERSON_MIX=1 / MATERIAL_MIX=3 / NEWS_BODY=4
const COPY_MONTAGE_TYPE: Record<CopyDriverTypeEnum, number> = {
    [CopyDriverTypeEnum.NEWS]: 4,
    [CopyDriverTypeEnum.VOICEOVER]: 1,
    [CopyDriverTypeEnum.CLIPS]: 3,
    [CopyDriverTypeEnum.PUBLISH]: 1,
};

// AI 生成弹窗（choose-agent）可选系统智能体，与数字人各混剪页保持一致：
// 新闻体（montage_news_create）仅 [2, 6]；口播混剪 / 素材混剪沿用默认全量
const COPY_AGENT_SYSTEM_IDS: Record<CopyDriverTypeEnum, number[]> = {
    [CopyDriverTypeEnum.NEWS]: [2, 6],
    [CopyDriverTypeEnum.VOICEOVER]: [0, 1, 3, 4, 5, 6],
    [CopyDriverTypeEnum.CLIPS]: [0, 1, 3, 4, 5, 6],
    [CopyDriverTypeEnum.PUBLISH]: [0, 1, 3, 4, 5, 6],
};

export const useCopyLibrary = (personId: Ref<string>, personaName: Ref<string>) => {
    const copyTab = ref<CopyLibraryTypeEnum>(CopyLibraryTypeEnum.DRIVE);
    const copyDriveType = ref<CopyDriverTypeEnum>(CopyDriverTypeEnum.NEWS);
    const copyList = ref<CopyItem[]>([]);
    const copyLoading = ref(false);

    const showCopyEdit = ref(false);
    const editingCopy = ref<CopyFormData | null>(null);

    // 批量删除
    const copyBatchMode = ref(false);
    const selectedCopyIds = ref<number[]>([]);
    const isCopySelected = (id: number): boolean => selectedCopyIds.value.includes(id);
    const selectedCopyCount = computed(() => selectedCopyIds.value.length);
    const isAllCopySelected = computed(
        () => copyList.value.length > 0 && copyList.value.every((item) => isCopySelected(item.id)),
    );

    // 当前列表的 driver_type：发布文案固定 0，视频驱动取子类型
    const currentDriverType = computed<CopyDriverTypeEnum>(() =>
        copyTab.value === CopyLibraryTypeEnum.PUBLISH ? CopyDriverTypeEnum.PUBLISH : copyDriveType.value,
    );

    const handleCancelCopyBatch = (): void => {
        copyBatchMode.value = false;
        selectedCopyIds.value = [];
    };

    const handleToggleCopyBatch = (): void => {
        copyBatchMode.value = !copyBatchMode.value;
        selectedCopyIds.value = [];
    };

    const handleToggleCopySelected = (id: number): void => {
        if (!copyBatchMode.value || !id) return;
        selectedCopyIds.value = isCopySelected(id)
            ? selectedCopyIds.value.filter((item) => item !== id)
            : selectedCopyIds.value.concat(id);
    };

    const handleToggleSelectAllCopy = (): void => {
        if (!copyBatchMode.value) return;
        selectedCopyIds.value = isAllCopySelected.value ? [] : copyList.value.map((item) => item.id);
    };

    const handleConfirmCopyBatchDelete = (): void => {
        if (!selectedCopyIds.value.length) {
            uni.showToast({ title: "请选择要删除的文案", icon: "none", duration: 2000 });
            return;
        }
        uni.showModal({
            title: "批量删除",
            content: `确定删除已选的 ${selectedCopyIds.value.length} 条文案吗？`,
            confirmColor: "#EF4444",
            confirmText: "删除",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteCopywritingLibrary({ ids: selectedCopyIds.value });
                    uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
                    handleCancelCopyBatch();
                    queryCopyList();
                } catch (error: any) {
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const queryCopyList = async (): Promise<void> => {
        if (!personId.value) return;
        try {
            copyLoading.value = true;
            const { lists = [] } = await getCopywritingLibraryList({
                persona_id: personId.value,
                library_type: copyTab.value,
                driver_type: currentDriverType.value,
                page_size: 9999,
            });
            copyList.value = lists;
        } catch {
            copyList.value = [];
        } finally {
            copyLoading.value = false;
        }
    };

    const handleSwitchCopyTab = (type: CopyLibraryTypeEnum): void => {
        if (copyTab.value === type) return;
        copyTab.value = type;
        handleCancelCopyBatch();
        queryCopyList();
    };

    const handleSwitchCopyDriveType = (type: CopyDriverTypeEnum): void => {
        if (copyDriveType.value === type) return;
        copyDriveType.value = type;
        handleCancelCopyBatch();
        queryCopyList();
    };

    const handleOpenAddCopy = (): void => {
        editingCopy.value = { title: "", content: "", topic: "" };
        showCopyEdit.value = true;
    };

    const handleOpenEditCopy = (item: CopyItem): void => {
        editingCopy.value = {
            id: item.id,
            title: item.title ?? "",
            content: item.content ?? "",
            topic: item.topic ?? "",
        };
        showCopyEdit.value = true;
    };

    const handleSubmitCopy = async (form: CopyFormData): Promise<void> => {
        const payload: Record<string, any> = {
            persona_id: personId.value,
            library_type: copyTab.value,
            driver_type: currentDriverType.value,
            title: form.title,
            content: form.content,
            topic: form.topic,
            sort: 0,
            status: 1,
        };
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            if (form.id) {
                await updateCopywritingLibrary({ ...payload, id: form.id });
            } else {
                await addCopywritingLibrary(payload);
            }
            uni.showToast({ title: "保存成功", icon: "none", duration: 2000 });
            showCopyEdit.value = false;
            queryCopyList();
        } catch (error: any) {
            uni.showToast({ title: error || "保存失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleRemoveCopy = (item: CopyItem): void => {
        uni.showModal({
            title: "提示",
            content: "确定删除该文案吗？",
            confirmColor: "#EF4444",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteCopywritingLibrary({ ids: [item.id] });
                    uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
                    queryCopyList();
                } catch (error: any) {
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    // 批量导入：微信端从聊天历史选文件 → 先上传拿链接 → 再调导入接口
    // ── AI 生成 ──
    // 视频驱动：选智能体 → ai_copywriter；发布文案：矩阵文案接口页
    const showChooseCopyAgent = ref(false);

    // 依据当前文案类型决定 choose-agent 可选系统智能体
    const copyAgentSystemIds = computed<number[]>(() => COPY_AGENT_SYSTEM_IDS[currentDriverType.value]);

    const normalizeTopicText = (topic: unknown): string => {
        if (Array.isArray(topic)) {
            return topic
                .map((item) => {
                    const text = String(item ?? "").trim();
                    if (!text) return "";
                    return text.startsWith("#") ? text : `#${text}`;
                })
                .filter(Boolean)
                .join(" ");
        }
        return String(topic ?? "").trim();
    };

    const handleOpenAiGenerate = (): void => {
        // 发布文案走矩阵文案生成（标题 + 正文 + 话题）
        if (copyTab.value === CopyLibraryTypeEnum.PUBLISH) {
            uni.$u.route({
                url: "/ai_modules/person/pages/publish_ai_copywriter/publish_ai_copywriter",
            });
            return;
        }
        showChooseCopyAgent.value = true;
    };

    const handleCopyAgentSelected = (res: any): void => {
        showChooseCopyAgent.value = false;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter",
            params: {
                agentData: JSON.stringify(res.data),
                montageType: COPY_MONTAGE_TYPE[currentDriverType.value],
                personaId: personId.value,
                personaName: encodeURIComponent(personaName.value || ""),
            },
        });
    };

    // AI 文案页通过 eventBus 回传生成结果，批量写入当前库
    const addGeneratedCopies = async (data: any[]): Promise<void> => {
        if (!Array.isArray(data) || !data.length) return;
        const isNews = currentDriverType.value === CopyDriverTypeEnum.NEWS;
        const isPublish = copyTab.value === CopyLibraryTypeEnum.PUBLISH;
        const items = data
            .map((item) => {
                if (isNews) {
                    const title = typeof item === "string" ? item : item?.title ?? "";
                    return { title, content: "", topic: "" };
                }
                return {
                    title: item?.title ?? "",
                    content: item?.content ?? "",
                    topic: isPublish ? normalizeTopicText(item?.topic) : "",
                };
            })
            .filter((item) => item.title || item.content);
        if (!items.length) return;
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await batchAddCopywritingLibrary({
                persona_id: personId.value,
                library_type: copyTab.value,
                driver_type: currentDriverType.value,
                items: items.map((item) => ({
                    title: item.title,
                    content: item.content,
                    topic: item.topic,
                    sort: 0,
                    status: 1,
                })),
            });
            uni.showToast({ title: "已保存生成文案", icon: "none", duration: 2000 });
            queryCopyList();
        } catch (error: any) {
            uni.showToast({ title: error || "保存失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    // 批量导入：选微信历史文件 → 先上传拿链接 → 再调导入接口
    const showCopyImport = ref(false);

    const handleOpenImport = (): void => {
        showCopyImport.value = true;
    };

    const handleImportPick = async (driverType: CopyDriverTypeEnum): Promise<void> => {
        showCopyImport.value = false;
        let picked: string | undefined;
        try {
            const { tempFiles } = await chooseFile({
                type: "file",
                count: 1,
                extension: COPY_IMPORT_FILE_ACCEPT,
            });
            const file = tempFiles?.[0];
            picked = file?.tempFilePath || file?.path;
        } catch (error: any) {
            // 用户取消选择不提示
            if (!error?.errMsg?.includes("cancel")) {
                uni.showToast({ title: error, icon: "none", duration: 2000 });
            }
            return;
        }
        if (!picked) return;

        uni.showLoading({ title: "上传中...", mask: true });
        try {
            const fileRes: any = await uploadFile("file", { filePath: picked });
            const fileUrl = fileRes?.uri || fileRes?.url;
            if (!fileUrl) {
                uni.showToast({ title: "文件上传失败", icon: "none", duration: 2000 });
                return;
            }
            uni.showLoading({ title: "导入中...", mask: true });
            await importCopywritingLibrary({
                file: fileUrl,
                persona_id: personId.value,
                library_type: copyTab.value,
                driver_type: driverType,
            });
            uni.showToast({ title: "导入成功", icon: "none", duration: 2000 });
            // 视频驱动：导入后切到对应类型，方便查看结果
            if (copyTab.value === CopyLibraryTypeEnum.DRIVE) {
                copyDriveType.value = driverType;
            }
            queryCopyList();
        } catch (error: any) {
            uni.showToast({ title: error || "导入失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleDownloadImportTemplate = (): void => {
        uni.$u.toast("模板下载功能开发中");
    };

    return {
        copyTab,
        copyDriveType,
        copyList,
        copyLoading,
        showCopyEdit,
        editingCopy,
        currentDriverType,
        queryCopyList,
        handleSwitchCopyTab,
        handleSwitchCopyDriveType,
        handleOpenAddCopy,
        handleOpenEditCopy,
        handleSubmitCopy,
        handleRemoveCopy,
        copyBatchMode,
        selectedCopyCount,
        isCopySelected,
        isAllCopySelected,
        handleToggleCopyBatch,
        handleToggleCopySelected,
        handleToggleSelectAllCopy,
        handleCancelCopyBatch,
        handleConfirmCopyBatchDelete,
        showCopyImport,
        handleOpenImport,
        handleImportPick,
        handleDownloadImportTemplate,
        showChooseCopyAgent,
        copyAgentSystemIds,
        handleOpenAiGenerate,
        handleCopyAgentSelected,
        addGeneratedCopies,
    };
};
