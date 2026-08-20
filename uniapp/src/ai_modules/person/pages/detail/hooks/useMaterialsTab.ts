import {
    addAvatar,
    addVoice,
    batchAddMaterial,
    batchDeleteMaterial,
    bindAvatarVoice,
    deleteAvatar,
    deleteFailedSlices,
    deleteMaterial,
    deleteVoice,
    getAvatarList as getAvatarListApi,
    getMaterialLibraryList,
    getVideoSliceStatistics,
    getVoiceList as getVoiceListApi,
    updateMaterial,
} from "@/api/person";
import { UploadAlbumTypeEnum, UploadCategoryEnum } from "@/enums/appEnums";
import { useMaterial } from "@/ai_modules/person/hooks/useMaterial";
import useUpload from "@/hooks/useUpload";
import usePolling from "@/hooks/usePolling";
import { useAudio } from "@/hooks/useAudio";
import cache from "@/utils/cache";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import { computed, nextTick, reactive, ref, toRef, type Ref } from "vue";

export enum MaterialTabEnum {
    COMPOSE = "compose",
    AVATAR = "avatar",
    VOICE = "voice",
    COPY = "copy",
    MUSIC = "music",
}

/** 人设素材类型：1 视频 / 2 图片 / 3 音乐 */
export enum PersonaMaterialTypeEnum {
    VIDEO = 1,
    IMAGE = 2,
    MUSIC = 3,
}

export type PublishMode = 1 | 2;

// 视频切割状态：切割中无可用链接；失败时 file_url 为空
export enum VideoSliceStatusEnum {
    NONE = 0,
    SLICING = 1,
    FAILED = 4,
}

const SLICE_POLLING_INTERVAL = 3000;
const VIDEO_SLICE_FILE_IDS_CACHE_KEY = "persona_video_slice_file_ids";

enum MaterialUploadScene {
    COMPOSE = "compose",
    DIRECT = "direct",
    MUSIC = "music",
}

const MUSIC_FILE_ACCEPT = ["mp3", "wav"] as const;
const DEFAULT_UPLOAD_CATEGORY_TIP =
    "支持同时选择图片与视频，特定场景仅支持选择视频或图片";
const MUSIC_UPLOAD_CATEGORY_TIP = "可从聊天记录、素材库或素材组选择 MP3 / WAV 音频文件";

export interface MaterialItem {
    id: string;
    material_name: string;
    thumbnail_url: string;
    file_url: string;
    duration: number;
    use_status: number;
    create_time: string;
    material_type: number;
    use_num: number;
    grab_type: number;
    slice_status?: number;
    slice_status_text?: string;
}

export interface SliceStatistics {
    total_count: number;
    pending_count: number;
    slicing_count: number;
    queue_count: number;
    finished_count: number;
    success_count: number;
    failed_count: number;
    total_slice_count: number;
    success_slice_count: number;
    item_count: number;
    sliced_count: number;
}

const sumSliceListField = (lists: any[], field: string): number =>
    lists.reduce((total, item) => total + (Number(item?.[field]) || 0), 0);

const toFiniteNumber = (value: unknown, fallback = 0): number => {
    const num = Number(value);
    return Number.isFinite(num) ? num : fallback;
};

/** 优先 summary，其次 lists 汇总，最后顶层字段 */
const pickSliceField = (raw: any, summary: any, lists: any[], field: string): number => {
    if (summary?.[field] != null && summary[field] !== "") return toFiniteNumber(summary[field]);
    const fromLists = sumSliceListField(lists, field);
    if (fromLists > 0) return fromLists;
    if (raw?.[field] != null && raw[field] !== "") return toFiniteNumber(raw[field]);
    return 0;
};

const normalizeSliceStatistics = (raw: any): SliceStatistics => {
    const lists = Array.isArray(raw?.lists) ? raw.lists : [];
    const summary = raw?.summary ?? {};
    const totalSliceCount = pickSliceField(raw, summary, lists, "total_slice_count");
    const successSliceCount = pickSliceField(raw, summary, lists, "success_slice_count");
    const itemCount = pickSliceField(raw, summary, lists, "item_count") || totalSliceCount;
    const totalCount =
        pickSliceField(raw, summary, lists, "total_count") || toFiniteNumber(raw?.total) || lists.length;

    return {
        total_count: totalCount,
        pending_count: pickSliceField(raw, summary, lists, "pending_count"),
        slicing_count: pickSliceField(raw, summary, lists, "slicing_count"),
        queue_count: pickSliceField(raw, summary, lists, "queue_count"),
        finished_count: pickSliceField(raw, summary, lists, "finished_count"),
        success_count: pickSliceField(raw, summary, lists, "success_count"),
        failed_count: pickSliceField(raw, summary, lists, "failed_count"),
        total_slice_count: totalSliceCount,
        success_slice_count: successSliceCount,
        item_count: itemCount,
        sliced_count: pickSliceField(raw, summary, lists, "sliced_count") || successSliceCount,
    };
};

/**
 * 切割是否仍在进行。
 * 本地切割失败时常见：failed_count 已增加，但 finished_count 不涨、pending 也不清零；
 * 必须先按「成功+失败」终态收口，不能只看 finished < total / pending > 0。
 */
export const isSliceTaskRunning = (stat: SliceStatistics): boolean => {
    if (stat.total_count <= 0 && stat.total_slice_count <= 0) {
        // 无总数时仅以进行中计数判断，避免空统计误显示
        return stat.pending_count + stat.slicing_count + stat.queue_count > 0;
    }
    // 成功 + 失败 均视为终态；finished 可能不含失败
    const settled = Math.max(stat.finished_count, stat.success_count + stat.failed_count);
    if (settled >= stat.total_count) return false;
    // 已有失败且无明确进行中时结束（兼容 total 不准）
    const inFlight = stat.pending_count + stat.slicing_count + stat.queue_count;
    if (stat.failed_count > 0 && inFlight <= 0) return false;
    return inFlight > 0 || settled < stat.total_count;
};

interface MaterialPayload {
    file_url: string;
    material_type: number;
    material_name: string;
    duration: number;
    thumbnail_url: string;
}

export interface AvatarItem {
    id: string;
    dh_id: string;
    pic: string;
    url: string;
    name: string;
    is_local?: boolean;
    voice_id?: string;
    voice_name?: string;
    bind_desc?: string;
    is_original_voice?: number;
    use_num?: number;
}

export interface VoiceItem {
    voice_id: string;
    id: string;
    name: string;
    url: string;
    is_local?: boolean;
    use_num?: number;
    create_time?: string;
}

export interface ChooseListRef {
    setChooseLists: (list: any[]) => void;
    setDisabledLists: (list: any[]) => void;
}

interface UseMaterialsTabOptions {
    personId: Ref<string>;
    publishMode: Ref<PublishMode>;
    playItem: Ref<{ url: string; pic: string }>;
    showVideoPreview: Ref<boolean>;
    chooseAnchorRef: Ref<ChooseListRef | null>;
    chooseVoiceRef: Ref<ChooseListRef | null>;
    avatarVoicePickerRef: Ref<ChooseListRef | null>;
}

export const materialSubTabs = [
    { key: MaterialTabEnum.COMPOSE, label: "素材内容" },
    { key: MaterialTabEnum.AVATAR, label: "数字人" },
    { key: MaterialTabEnum.VOICE, label: "音色" },
    { key: MaterialTabEnum.COPY, label: "文案库" },
    { key: MaterialTabEnum.MUSIC, label: "音乐库" },
];

// 素材库两种模式的说明横幅文案
export const publishModeBannerText: Record<PublishMode, string> = {
    1: "提供视频片段 / 数字人 / 音色 / 文案库，AI 自动剪辑合成后发布。建议单个视频素材 5-10 秒，>10 秒会被自动切片。",
    2: "跳过 AI 合成，直接发布已上传的成品视频 / 图片组，不消耗算力。每条素材只能使用一次。",
};

export const materialPublishModes = [
    {
        value: 1 as const,
        title: "剪辑原片库",
        desc: "素材池内容，AI自动合成后发布",
    },
    {
        value: 2 as const,
        title: "成品直发库",
        desc: "手动指定素材内容进发布",
    },
];

/** 素材内容「全部」：视频+图片（不含音乐），对应列表接口 material_type in 查询 */
export const COMPOSE_MATERIAL_TYPES = `${PersonaMaterialTypeEnum.VIDEO},${PersonaMaterialTypeEnum.IMAGE}`;

export const materialFilters = [
    { name: "全部", value: COMPOSE_MATERIAL_TYPES },
    { name: "视频", value: PersonaMaterialTypeEnum.VIDEO },
    { name: "图片", value: PersonaMaterialTypeEnum.IMAGE },
] as const;

export const useMaterialsTab = ({
    personId,
    publishMode,
    playItem,
    showVideoPreview,
    chooseAnchorRef,
    chooseVoiceRef,
    avatarVoicePickerRef,
}: UseMaterialsTabOptions) => {
    const getVideoSliceFileIdsCacheKey = (): string => `${VIDEO_SLICE_FILE_IDS_CACHE_KEY}_${personId.value}`;

    const getCachedVideoSliceFileIds = (): number[] => {
        const ids = cache.get(getVideoSliceFileIdsCacheKey());
        return Array.isArray(ids) ? ids.map(Number).filter(Boolean) : [];
    };

    const setCachedVideoSliceFileIds = (ids: number[]): void => {
        cache.set(getVideoSliceFileIdsCacheKey(), ids);
    };

    const clearCachedVideoSliceFileIds = (): void => {
        setCachedVideoSliceFileIds([]);
    };

    const getUploadedFileIds = (items: any[]): number[] =>
        items.map((item) => Number(item?.id || item?.file_id || item?.original_video_id)).filter(Boolean);

    const activeMaterialTab = ref<MaterialTabEnum>(MaterialTabEnum.COMPOSE);
    const activeMaterialFilter = ref(0);
    const materialList = ref<MaterialItem[]>([]);
    const materialLoading = ref(false);
    const materialFinished = ref(false);
    const materialParams = reactive({
        page_no: 1,
        page_size: 10,
    });
    const activeDirectMaterialFilter = ref(0);
    const directPagingRefreshKey = ref(0);

    const avatars = ref<AvatarItem[]>([]);
    const voices = ref<VoiceItem[]>([]);
    const musicList = ref<MaterialItem[]>([]);
    const musicLoading = ref(false);
    const musicFinished = ref(false);
    const musicParams = reactive({
        page_no: 1,
        page_size: 20,
    });
    const musicBatchMode = ref(false);
    const selectedMusicIds = ref<string[]>([]);
    const showUploadCategoryPanel = ref(false);
    const showDirectPublishPanel = ref(false);
    const showHistory = ref(false);
    const showMaterialLibrary = ref(false);
    /** choose-material 的 mode：all / list / group */
    const materialType = ref("");
    /** choose-material 的内容类型：all / image / video / audio */
    const chooseMaterialContentType = ref<"all" | "image" | "video" | "audio">("all");
    const replaceMaterialIndex = ref(-1);
    const batchDeleteMode = ref(false);
    const selectedMaterialIds = ref<string[]>([]);
    const uploadScene = ref<MaterialUploadScene>(MaterialUploadScene.COMPOSE);
    const pendingUploadScene = ref<MaterialUploadScene>(MaterialUploadScene.COMPOSE);

    const showChooseAnchor = ref(false);
    const showChooseVoice = ref(false);
    const showAvatarVoicePicker = ref(false);
    const currentBindingAvatar = ref<AvatarItem | null>(null);
    const currVoiceId = ref<string | null>(null);
    const currMusicId = ref<string | null>(null);
    const { isPlaying, play, pause, destroy } = useAudio();

    const hasOverusedMaterial = computed(() => materialList.value.some((m) => m.use_num >= 3));
    const selectedMaterialCount = computed(() => selectedMaterialIds.value.length);
    // 全选状态：当前已加载素材全部被选中（空列表不算全选）
    const isAllMaterialSelected = computed(
        () => materialList.value.length > 0 && materialList.value.every((item) => isMaterialSelected(item.id)),
    );
    const selectedMusicCount = computed(() => selectedMusicIds.value.length);
    const isMusicSelected = (id: string): boolean => selectedMusicIds.value.includes(id);
    const isAllMusicSelected = computed(
        () => musicList.value.length > 0 && musicList.value.every((item) => isMusicSelected(item.id)),
    );

    const sliceStatistics = ref<SliceStatistics | null>(null);
    // 是否仍有未完成的切割任务（summary: pending/slicing/queue 或 finished < total）
    const hasSlicingTask = computed(() => {
        const stat = sliceStatistics.value;
        return !!stat && isSliceTaskRunning(stat);
    });
    // 单个素材是否处于切割中：视频且 slice_status === 1，此时无可用链接
    const isMaterialSlicing = (item: MaterialItem): boolean =>
        item.material_type === 1 && Number(item.slice_status) === VideoSliceStatusEnum.SLICING;
    // 单个素材是否分割失败：视频且 slice_status === 4，无可用内容链接
    const isMaterialSliceFailed = (item: MaterialItem): boolean =>
        item.material_type === 1 && Number(item.slice_status) === VideoSliceStatusEnum.FAILED;
    // 当前列表里是否还有切割中的素材（统计接口可能滞后，用列表兜底触发轮询）
    const hasSlicingMaterial = computed(() => materialList.value.some(isMaterialSlicing));
    // 当前已加载列表中的分割失败条数（切割结束后用于提示条）
    const failedMaterialCount = computed(() => materialList.value.filter(isMaterialSliceFailed).length);
    const getCurrentMaxDuration = (scene = uploadScene.value): number =>
        scene === MaterialUploadScene.DIRECT || publishMode.value === 2 ? 301 : 60;

    const directMaterialScratchList = ref<any[]>([]);
    const { processAndAppend } = useMaterial(toRef(materialList, "value"));
    const { processAndAppend: processDirectAndAppend } = useMaterial(directMaterialScratchList);
    // 指定素材内容（直发场景 / publish_mode 2）的素材直接发布，无需转码
    const isDirectUploadScene = () =>
        pendingUploadScene.value === MaterialUploadScene.DIRECT || publishMode.value === 2;
    // 剪辑原片库上传：切割 ffmpeg=2 后端自动入库；不切割 ffmpeg=1 需手动 batchAdd
    const showCutModePopup = ref(false);
    const pendingUploadCategory = ref<UploadAlbumTypeEnum | null>(null);
    const shouldManualAddAfterUpload = ref(false);

    const {
        showUploadProgress: showComposeUploadProgress,
        uploadMaterialList: composeUploadList,
        uploadAndProcessFiles,
    } = useUpload({
        isTranscode: () => !isDirectUploadScene(),
        isFetchVideoInfo: true,
        imageResolution: [999999, 999999],
        videoDuration: () => [1, 300],
        onSuccess: (res: any[]) => {
            const needManualAdd = isDirectUploadScene() || shouldManualAddAfterUpload.value;
            shouldManualAddAfterUpload.value = false;
            const toPayload = (list: any[]): MaterialPayload[] =>
                list.map((item) => ({
                    file_url: item.url,
                    material_type: item.type === "image" ? PersonaMaterialTypeEnum.IMAGE : PersonaMaterialTypeEnum.VIDEO,
                    material_name: item.name,
                    duration: item.duration ?? 0,
                    thumbnail_url: item.pic,
                }));
            if (!needManualAdd) {
                // 切割：仅视频后端自动入库；同批图片不走切割，需手动 batchAdd
                const videos = res.filter((item) => item.type !== "image");
                const images = res.filter((item) => item.type === "image");
                if (videos.length) {
                    // 合并已有跟踪 id，避免二次上传覆盖第一批切割中的 file_ids
                    const fileIds = [
                        ...new Set([...getCachedVideoSliceFileIds(), ...getUploadedFileIds(videos)]),
                    ];
                    setCachedVideoSliceFileIds(fileIds);
                    if (!images.length) reloadMaterialList();
                    refreshSliceStatistics(fileIds);
                }
                if (images.length) {
                    handleAddMaterial(toPayload(images), pendingUploadScene.value);
                }
                return;
            }
            handleAddMaterial(toPayload(res), pendingUploadScene.value);
        },
    });

    const isMusicFileName = (name = ""): boolean => {
        const ext = name.split("?")[0]?.split(".").pop()?.toLowerCase() || "";
        return (MUSIC_FILE_ACCEPT as readonly string[]).includes(ext);
    };

    const {
        showUploadProgress: showMusicUploadProgress,
        uploadMaterialList: musicUploadList,
        uploadAndProcessFiles: uploadAndProcessMusicFiles,
    } = useUpload({
        isTranscode: false,
        isFetchVideoInfo: false,
        fileAccept: [...MUSIC_FILE_ACCEPT],
        onSuccess: (res: any[]) => {
            const validList = res.filter((item) => isMusicFileName(item.name) || isMusicFileName(item.url));
            if (!validList.length) {
                uni.$u.toast("仅支持 MP3、WAV 格式");
                return;
            }
            if (validList.length < res.length) {
                uni.$u.toast("已忽略非 MP3/WAV 文件");
            }
            const items: MaterialPayload[] = validList.map((item) => ({
                file_url: item.url,
                material_type: PersonaMaterialTypeEnum.MUSIC,
                material_name: item.name?.replace(/\.[^.]+$/, "") || item.name,
                duration: Number(item.duration) || 0,
                thumbnail_url: "",
            }));
            handleAddMusicMaterial(items);
        },
    });

    const showUploadProgress = computed({
        get: () => showComposeUploadProgress.value || showMusicUploadProgress.value,
        set: (value: boolean) => {
            if (!value) {
                showComposeUploadProgress.value = false;
                showMusicUploadProgress.value = false;
            }
        },
    });
    const uploadMaterialList = computed(() =>
        showMusicUploadProgress.value ? musicUploadList.value : composeUploadList.value,
    );

    const isMusicUploadScene = computed(() => uploadScene.value === MaterialUploadScene.MUSIC);
    const uploadCategoryShowCategories = computed((): Array<UploadAlbumTypeEnum | UploadCategoryEnum> =>
        isMusicUploadScene.value
            ? [UploadAlbumTypeEnum.File, UploadCategoryEnum.Library, UploadCategoryEnum.Group]
            : [],
    );
    const uploadCategoryTip = computed(() =>
        isMusicUploadScene.value ? MUSIC_UPLOAD_CATEGORY_TIP : DEFAULT_UPLOAD_CATEGORY_TIP,
    );
    const forceAlbumTypePicker = computed(() => isMusicUploadScene.value);

    const formatMaterialTime = (time: string) => {
        if (!time) return "";
        return uni.$u.timeFormat(time, "yyyy-mm-dd");
    };

    const isCurrentPlaying = (voiceId: string) => isPlaying.value && currVoiceId.value === voiceId;
    const isCurrentMusicPlaying = (musicId: string) => isPlaying.value && currMusicId.value === musicId;

    const queryMaterialList = async () => {
        if (materialLoading.value || materialFinished.value) return;
        try {
            materialLoading.value = true;
            const { lists = [], count = 0 } = await getMaterialLibraryList({
                persona_id: personId.value,
                page_no: materialParams.page_no,
                page_size: materialParams.page_size,
                material_type: materialFilters[activeMaterialFilter.value].value,
                publish_mode: publishMode.value,
            });
            materialList.value = materialParams.page_no === 1 ? lists : materialList.value.concat(lists);
            if (materialList.value.length >= count || lists.length < materialParams.page_size) {
                materialFinished.value = true;
            }
        } catch {
            materialFinished.value = true;
        } finally {
            materialLoading.value = false;
        }
    };

    const queryMusicList = async () => {
        if (musicLoading.value || musicFinished.value) return;
        try {
            musicLoading.value = true;
            const { lists = [], count = 0 } = await getMaterialLibraryList({
                persona_id: personId.value,
                page_no: musicParams.page_no,
                page_size: musicParams.page_size,
                material_type: PersonaMaterialTypeEnum.MUSIC,
                use_status: 1,
            });
            musicList.value = musicParams.page_no === 1 ? lists : musicList.value.concat(lists);
            if (musicList.value.length >= count || lists.length < musicParams.page_size) {
                musicFinished.value = true;
            }
        } catch {
            musicFinished.value = true;
        } finally {
            musicLoading.value = false;
        }
    };

    const resetMusicList = async () => {
        musicParams.page_no = 1;
        musicFinished.value = false;
        musicList.value = [];
        await queryMusicList();
    };

    const loadNextMusicPage = () => {
        if (musicLoading.value || musicFinished.value) return;
        musicParams.page_no += 1;
        queryMusicList();
    };

    const handleAddMusicMaterial = async (items: MaterialPayload[]): Promise<void> => {
        if (!items.length) return;
        uni.showLoading({ title: "添加中...", mask: true });
        try {
            await batchAddMaterial({
                persona_id: personId.value,
                items: items.map((item) => ({
                    ...item,
                    material_type: PersonaMaterialTypeEnum.MUSIC,
                    use_status: 1,
                    thumbnail_url: item.thumbnail_url || "",
                })),
            });
            uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
            await resetMusicList();
        } catch (error: any) {
            uni.showToast({ title: error || "添加失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const queryDirectMaterialList = async (page_no: number, page_size: number): Promise<MaterialItem[]> => {
        const { lists = [] } = await getMaterialLibraryList({
            persona_id: personId.value,
            page_no,
            page_size,
            material_type: materialFilters[activeDirectMaterialFilter.value].value,
            publish_mode: 2,
            is_wechat: 1,
        });
        return lists;
    };

    const reloadMaterialList = async () => {
        materialParams.page_no = 1;
        materialList.value = [];
        materialFinished.value = false;
        handleCancelBatchDelete();
        await queryMaterialList();
    };

    // 切割统计轮询：以统计接口为准，逐次刷新片段进度
    // 不传 file_ids，按 persona 拉全局进度，避免多批上传时只统计到最新一批
    const querySliceStatistics = async () => {
        if (!personId.value) return null;
        try {
            // 完成前是否在切割中（含列表兜底），用于「完成时必刷」且避免初始化重复拉列表
            const wasRunning =
                (!!sliceStatistics.value && isSliceTaskRunning(sliceStatistics.value)) ||
                hasSlicingMaterial.value;
            const res = await getVideoSliceStatistics({ persona_id: personId.value });
            sliceStatistics.value = normalizeSliceStatistics(res);

            // 列表已加载且无切割中 +（有失败 或 统计已结束）：收口进度，避免失败后残留
            if (
                !materialLoading.value &&
                !hasSlicingMaterial.value &&
                (failedMaterialCount.value > 0 || !hasSlicingTask.value)
            ) {
                const shouldReload = wasRunning || failedMaterialCount.value > 0;
                clearSliceProgress();
                if (shouldReload) await reloadMaterialList();
                return res;
            }

            if (!hasSlicingTask.value) {
                stopSlicePolling();
                clearCachedVideoSliceFileIds();
                if (wasRunning) await reloadMaterialList();
            }
            return res;
        } catch {
            return null;
        }
    };

    const { start: startSlicePolling, end: stopSlicePolling } = usePolling(querySliceStatistics, {
        time: SLICE_POLLING_INTERVAL,
        key: "persona-video-slice",
    });

    // 收口切割进度：停轮询、清空统计与本地跟踪 id（失败/删除后避免进度条残留）
    const clearSliceProgress = (): void => {
        stopSlicePolling();
        sliceStatistics.value = null;
        clearCachedVideoSliceFileIds();
    };

    // 删除后同步进度：已无切割中则收口，避免继续用缓存 file_ids 查到残留任务
    const syncSliceProgressWithMaterialList = async (): Promise<void> => {
        if (publishMode.value !== 1) {
            clearSliceProgress();
            return;
        }
        if (hasSlicingMaterial.value) {
            await refreshSliceStatistics();
            return;
        }
        clearSliceProgress();
    };

    const resetMaterialList = async () => {
        await reloadMaterialList();
        // 图片筛选下列表可能看不到切割中视频，仍有缓存 id 时继续轮询，避免误清
        if (hasSlicingMaterial.value || getCachedVideoSliceFileIds().length > 0) {
            await refreshSliceStatistics();
            return;
        }
        clearSliceProgress();
    };

    // 仅自动合成模式（publish_mode 1）涉及视频切割
    const refreshSliceStatistics = async (fileIds = getCachedVideoSliceFileIds()) => {
        if (publishMode.value !== 1) {
            clearSliceProgress();
            return;
        }
        // 无跟踪 id 且列表无切割中：本地没有进行中的切割痕迹，不发起统计（避免拉回历史残留任务）
        if (!fileIds.length && !hasSlicingMaterial.value) {
            clearSliceProgress();
            return;
        }
        await querySliceStatistics();
        // 统计或列表任一仍在切割，就持续轮询
        if (hasSlicingTask.value || hasSlicingMaterial.value) {
            startSlicePolling();
        } else {
            stopSlicePolling();
        }
    };

    const resetDirectMaterialList = () => {
        directPagingRefreshKey.value += 1;
    };

    const loadNextMaterialPage = () => {
        if (materialLoading.value || materialFinished.value) return;
        materialParams.page_no += 1;
        queryMaterialList();
    };

    const handleSwitchMaterialTab = (tab: MaterialTabEnum) => {
        if (tab !== MaterialTabEnum.MUSIC) {
            handleCancelMusicBatch();
        }
        if (tab !== MaterialTabEnum.COMPOSE) {
            handleCancelBatchDelete();
        }
        activeMaterialTab.value = tab;
    };

    const handleSwitchPublishMode = (mode: PublishMode) => {
        if (publishMode.value === mode) return;
        // 剪辑原片库 / 成品直发库 仅作视图切换，不再持久化 publish_mode
        publishMode.value = mode;
        activeMaterialFilter.value = 0;
        resetMaterialList();
    };

    const handleMaterialFilter = (index: number) => {
        activeMaterialFilter.value = index;
        resetMaterialList();
    };

    const handleDirectMaterialFilter = (index: number) => {
        activeDirectMaterialFilter.value = index;
    };

    const handleOpenUploadCategoryPanel = (scene: MaterialUploadScene = MaterialUploadScene.COMPOSE): void => {
        uploadScene.value = scene;
        showUploadCategoryPanel.value = true;
    };

    const handleOpenComposeUpload = (): void => {
        handleOpenUploadCategoryPanel();
    };

    const handleOpenMusicUpload = (): void => {
        handleOpenUploadCategoryPanel(MaterialUploadScene.MUSIC);
    };

    const handleOpenDirectPublish = (): void => {
        showDirectPublishPanel.value = true;
    };

    const handleCloseDirectPublish = (): void => {
        showDirectPublishPanel.value = false;
    };

    const handleSetDirectPublishVisible = (visible: boolean): void => {
        if (visible) {
            handleOpenDirectPublish();
            return;
        }
        handleCloseDirectPublish();
    };

    const handleOpenDirectUpload = (): void => {
        handleOpenUploadCategoryPanel(MaterialUploadScene.DIRECT);
    };

    const isComposeOriginalLibraryUpload = () =>
        pendingUploadScene.value === MaterialUploadScene.COMPOSE && publishMode.value === 1;

    const startAlbumUpload = (category: UploadAlbumTypeEnum, cutMode: "cut" | "none" | null = null) => {
        if (isDirectUploadScene()) {
            shouldManualAddAfterUpload.value = false;
            uploadAndProcessFiles(category, {});
            return;
        }
        // 剪辑原片库：切割走后端自动入库；不切割传 ffmpeg=1 并手动添加
        if (cutMode === "none") {
            shouldManualAddAfterUpload.value = true;
            uploadAndProcessFiles(category, { ffmpeg: 1 });
            return;
        }
        shouldManualAddAfterUpload.value = false;
        uploadAndProcessFiles(category, { persona_id: personId.value, ffmpeg: 2 });
    };

    const handleSelectCategory = (category: UploadAlbumTypeEnum | UploadCategoryEnum): void => {
        // 音乐库：聊天记录选音频 + 素材库选音频；不走切割 / 创作库
        if (uploadScene.value === MaterialUploadScene.MUSIC) {
            if (category === UploadAlbumTypeEnum.File) {
                pendingUploadScene.value = MaterialUploadScene.MUSIC;
                uploadAndProcessMusicFiles("file", {});
                return;
            }
            if (category === UploadCategoryEnum.Library || category === UploadCategoryEnum.Group) {
                pendingUploadScene.value = MaterialUploadScene.MUSIC;
                chooseMaterialContentType.value = "audio";
                materialType.value = category === UploadCategoryEnum.Library ? "all" : "group";
                showMaterialLibrary.value = true;
            }
            return;
        }
        if (
            UploadAlbumTypeEnum.File === category ||
            UploadAlbumTypeEnum.Image === category ||
            UploadAlbumTypeEnum.Video === category
        ) {
            pendingUploadScene.value = uploadScene.value;
            // 剪辑原片库选相册/视频时先选切割模式；纯图片无需切割，ffmpeg=1 后手动入库
            if (
                isComposeOriginalLibraryUpload() &&
                (category === UploadAlbumTypeEnum.File || category === UploadAlbumTypeEnum.Video)
            ) {
                pendingUploadCategory.value = category;
                showCutModePopup.value = true;
                return;
            }
            if (isComposeOriginalLibraryUpload() && category === UploadAlbumTypeEnum.Image) {
                startAlbumUpload(category, "none");
                return;
            }
            startAlbumUpload(category, null);
            return;
        }
        if (category === UploadCategoryEnum.Library || category === UploadCategoryEnum.Group) {
            chooseMaterialContentType.value = "all";
            materialType.value = category === UploadCategoryEnum.Library ? "all" : "group";
            showMaterialLibrary.value = true;
            return;
        }
        if (category === UploadCategoryEnum.Creation) {
            showHistory.value = true;
        }
    };

    const handleConfirmCutMode = (mode: "cut" | "none") => {
        const category = pendingUploadCategory.value;
        pendingUploadCategory.value = null;
        showCutModePopup.value = false;
        if (!category) return;
        startAlbumUpload(category, mode);
    };

    const handleSelectHistory = (selected: any[]): void => {
        const scene = uploadScene.value;
        const appendHandler = scene === MaterialUploadScene.DIRECT ? processDirectAndAppend : processAndAppend;
        appendHandler({
            rawList: selected,
            urlField: "url",
            maxDuration: getCurrentMaxDuration(scene),
            replaceIndex: replaceMaterialIndex.value,
            onSuccess: (formatted) => {
                showHistory.value = false;
                directMaterialScratchList.value = [];
                const items: MaterialPayload[] = formatted.map((item) => ({
                    file_url: item.url,
                    thumbnail_url: item.pic,
                    material_type: item.type === "video" ? 1 : 2,
                    material_name: item.name,
                    duration: Number(item.duration),
                }));
                handleAddMaterial(items, scene);
            },
        });
    };

    const handleSelectMaterial = (selected: any[]): void => {
        const scene = uploadScene.value;
        if (scene === MaterialUploadScene.MUSIC) {
            const validSelected = selected.filter((item) => item?.url && isMusicFileName(item.url));
            if (!validSelected.length) {
                showMaterialLibrary.value = false;
                uni.$u.toast("仅支持 MP3、WAV 格式");
                return;
            }
            if (validSelected.length < selected.length) {
                uni.$u.toast("已忽略非 MP3/WAV 文件");
            }
            const items: MaterialPayload[] = validSelected.map((item) => ({
                file_url: item.url,
                material_type: PersonaMaterialTypeEnum.MUSIC,
                material_name: item.name?.replace(/\.[^.]+$/, "") || item.name,
                duration: Number(item.duration) || 0,
                thumbnail_url: "",
            }));
            showMaterialLibrary.value = false;
            handleAddMusicMaterial(items);
            return;
        }
        const appendHandler = scene === MaterialUploadScene.DIRECT ? processDirectAndAppend : processAndAppend;
        appendHandler({
            rawList: selected,
            urlField: "url",
            maxDuration: getCurrentMaxDuration(scene),
            onSuccess: () => {
                showMaterialLibrary.value = false;
                directMaterialScratchList.value = [];
                const items: MaterialPayload[] = selected.map((item) => ({
                    file_url: item.url,
                    material_type: item.type === "image" ? 2 : 1,
                    material_name: item.name,
                    duration: item.duration ?? 0,
                    thumbnail_url: item.pic,
                }));
                handleAddMaterial(items, scene);
            },
        });
    };

    const handleAddMaterial = async (items: MaterialPayload[], scene = uploadScene.value): Promise<void> => {
        if (!items.length) return;
        uni.showLoading({ title: "添加中...", mask: true });
        try {
            await batchAddMaterial({
                persona_id: personId.value,
                items: items.map((item: any) => ({
                    ...item,
                    publish_mode: scene === MaterialUploadScene.DIRECT ? 2 : publishMode.value,
                })),
            });
            uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
            if (scene === MaterialUploadScene.DIRECT) {
                resetDirectMaterialList();
            } else {
                // 走 batchAdd 的都是图片 / 不切割视频，本身无切割进度；只刷列表
                await reloadMaterialList();
                // 若仍有进行中的视频切割，保持统计轮询
                if (hasSlicingTask.value || hasSlicingMaterial.value) {
                    refreshSliceStatistics();
                }
            }
        } catch (error: any) {
            uni.showToast({ title: error || "添加失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleDeleteMaterial = async (item: MaterialItem): Promise<void> => {
        uni.showModal({
            title: "提示",
            content: "确定删除该素材吗？",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await deleteMaterial({ id: item.id });
                    uni.showToast({ title: "删除成功", icon: "success", duration: 2000 });
                    await reloadMaterialList();
                    await syncSliceProgressWithMaterialList();
                } catch (error: any) {
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleRemoveDirectMaterial = async (item: MaterialItem): Promise<void> => {
        uni.showModal({
            title: "提示",
            content: "确定移除该直发素材吗？",
            confirmColor: "#EF4444",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "移除中...", mask: true });
                try {
                    await deleteMaterial({ id: item.id });
                    uni.showToast({ title: "已移除", icon: "none", duration: 2000 });
                    resetDirectMaterialList();
                } catch (error: any) {
                    uni.showToast({ title: error || "移除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleToggleBatchDelete = (): void => {
        batchDeleteMode.value = !batchDeleteMode.value;
        selectedMaterialIds.value = [];
    };

    const handleCancelBatchDelete = (): void => {
        batchDeleteMode.value = false;
        selectedMaterialIds.value = [];
    };

    const isMaterialSelected = (id: string): boolean => selectedMaterialIds.value.includes(id);

    const handleToggleMaterialSelected = (id: string): void => {
        if (!batchDeleteMode.value || !id) return;
        selectedMaterialIds.value = isMaterialSelected(id)
            ? selectedMaterialIds.value.filter((item) => item !== id)
            : selectedMaterialIds.value.concat(id);
    };

    const handleToggleSelectAll = (): void => {
        if (!batchDeleteMode.value) return;
        selectedMaterialIds.value = isAllMaterialSelected.value ? [] : materialList.value.map((item) => item.id);
    };

    const handleConfirmBatchDelete = (): void => {
        if (!selectedMaterialIds.value.length) {
            uni.showToast({ title: "请选择要删除的素材", icon: "none", duration: 2000 });
            return;
        }
        uni.showModal({
            title: "批量删除",
            content: `确定删除已选的 ${selectedMaterialIds.value.length} 个素材吗？`,
            confirmColor: "#EF4444",
            confirmText: "删除",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    await batchDeleteMaterial({ ids: selectedMaterialIds.value });
                    uni.showToast({ title: "删除成功", icon: "success", duration: 2000 });
                    handleCancelBatchDelete();
                    await reloadMaterialList();
                    await syncSliceProgressWithMaterialList();
                } catch (error: any) {
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleDeleteFailedSlices = (): void => {
        if (!failedMaterialCount.value) {
            uni.showToast({ title: "暂无切割失败素材", icon: "none", duration: 2000 });
            return;
        }
        uni.showModal({
            title: "一键删除",
            content: `确定删除全部 ${failedMaterialCount.value} 条切割失败的视频吗？`,
            confirmColor: "#EF4444",
            confirmText: "删除",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    const res = await deleteFailedSlices({ persona_id: personId.value });
                    const deletedCount = Number(res?.deleted_count ?? failedMaterialCount.value);
                    uni.showToast({
                        title: deletedCount > 0 ? `已删除 ${deletedCount} 条` : "删除成功",
                        icon: "none",
                        duration: 2000,
                    });
                    await reloadMaterialList();
                    await syncSliceProgressWithMaterialList();
                } catch (error: any) {
                    uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleUpdateMaterialStatus = async (item: MaterialItem): Promise<void> => {
        uni.showLoading({ title: "操作中...", mask: true });
        try {
            await updateMaterial({
                id: item.id,
                persona_id: personId.value,
                use_status: item.use_status === 1 ? 2 : 1,
            });
            uni.showToast({ title: "操作成功", icon: "none", duration: 3000 });
            resetMaterialList();
        } catch (error: any) {
            uni.showToast({ title: error || "操作失败", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleMoreMaterial = (item: MaterialItem): void => {
        if (isMaterialSlicing(item)) {
            uni.showToast({ title: "视频切割中，请稍候", icon: "none", duration: 2000 });
            return;
        }
        if (isMaterialSliceFailed(item)) {
            uni.showActionSheet({
                itemList: ["删除"],
                success: ({ tapIndex }) => {
                    if (tapIndex === 0) handleDeleteMaterial(item);
                },
            });
            return;
        }
        uni.showActionSheet({
            itemList: ["查看详情", "删除", "下载"],
            success: ({ tapIndex }) => {
                if (tapIndex === 0) {
                    uni.navigateTo({
                        url: `/ai_modules/person/pages/material_detail/material_detail?id=${item.id}&persona_id=${personId.value}`,
                    });
                } else if (tapIndex === 1) {
                    handleDeleteMaterial(item);
                } else if (tapIndex === 2) {
                    item.material_type === 1
                        ? saveVideoToPhotosAlbum(item.file_url)
                        : saveImageToPhotosAlbum(item.file_url);
                }
            },
        });
    };

    const handlePlayMaterial = (item: MaterialItem): void => {
        playItem.value = { url: item.file_url, pic: item.thumbnail_url };
        showVideoPreview.value = true;
    };

    const handlePreviewMaterial = (item: MaterialItem): void => {
        if (batchDeleteMode.value) {
            handleToggleMaterialSelected(item.id);
            return;
        }
        if (isMaterialSlicing(item)) {
            uni.showToast({ title: "视频切割中，请稍候", icon: "none", duration: 2000 });
            return;
        }
        if (isMaterialSliceFailed(item)) {
            uni.showToast({ title: "视频分割失败，请删除后重新上传", icon: "none", duration: 2000 });
            return;
        }
        if (item.material_type === 1) {
            handlePlayMaterial(item);
            return;
        }
        uni.previewImage({ urls: [item.thumbnail_url || item.file_url] });
    };

    const handlePreviewDirectMaterial = (item: MaterialItem): void => {
        if (item.material_type === 1) {
            handlePlayMaterial(item);
            return;
        }
        uni.previewImage({ urls: [item.thumbnail_url || item.file_url] });
    };

    const handlePlayVoice = (item: VoiceItem): void => {
        if (isCurrentPlaying(item.voice_id)) {
            pause();
            return;
        }
        pause();
        currMusicId.value = null;
        currVoiceId.value = item.voice_id;
        play(item.url);
    };

    const handlePlayMusic = (item: MaterialItem): void => {
        if (isCurrentMusicPlaying(item.id)) {
            pause();
            return;
        }
        if (!item.file_url) {
            uni.showToast({ title: "音频地址无效", icon: "none", duration: 2000 });
            return;
        }
        pause();
        currVoiceId.value = null;
        currMusicId.value = item.id;
        play(item.file_url);
    };

    const handleRemoveMusic = async (item: MaterialItem): Promise<void> => {
        uni.showModal({
            title: "提示",
            content: "确定移出该音乐吗？",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "移除中...", mask: true });
                try {
                    await deleteMaterial({ id: item.id });
                    if (currMusicId.value === item.id) {
                        pause();
                        currMusicId.value = null;
                    }
                    uni.showToast({ title: "已移出音乐库", icon: "none", duration: 2000 });
                    await resetMusicList();
                } catch (error: any) {
                    uni.showToast({ title: error || "移除失败，请重试", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleToggleMusicBatch = (): void => {
        if (!musicList.value.length && !musicBatchMode.value) {
            uni.showToast({ title: "暂无音乐可删除", icon: "none", duration: 2000 });
            return;
        }
        musicBatchMode.value = !musicBatchMode.value;
        selectedMusicIds.value = [];
        if (musicBatchMode.value) {
            pause();
            currMusicId.value = null;
        }
    };

    const handleCancelMusicBatch = (): void => {
        musicBatchMode.value = false;
        selectedMusicIds.value = [];
    };

    const handleToggleMusicSelected = (id: string): void => {
        if (!musicBatchMode.value || !id) return;
        selectedMusicIds.value = isMusicSelected(id)
            ? selectedMusicIds.value.filter((item) => item !== id)
            : selectedMusicIds.value.concat(id);
    };

    const handleToggleSelectAllMusic = (): void => {
        if (!musicBatchMode.value) return;
        selectedMusicIds.value = isAllMusicSelected.value ? [] : musicList.value.map((item) => item.id);
    };

    const handleConfirmMusicBatchDelete = (): void => {
        if (!selectedMusicIds.value.length) {
            uni.showToast({ title: "请选择要移出的音乐", icon: "none", duration: 2000 });
            return;
        }
        uni.showModal({
            title: "批量移出",
            content: `确定移出已选的 ${selectedMusicIds.value.length} 首音乐吗？`,
            confirmColor: "#EF4444",
            confirmText: "移出",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "移除中...", mask: true });
                try {
                    await batchDeleteMaterial({ ids: selectedMusicIds.value });
                    if (currMusicId.value && selectedMusicIds.value.includes(currMusicId.value)) {
                        pause();
                        currMusicId.value = null;
                    }
                    uni.showToast({ title: "已移出音乐库", icon: "none", duration: 2000 });
                    handleCancelMusicBatch();
                    await resetMusicList();
                } catch (error: any) {
                    uni.showToast({ title: error || "移除失败，请重试", icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const getAvatarList = async (): Promise<void> => {
        const { lists } = await getAvatarListApi({ persona_id: personId.value, page_size: 9999 });
        avatars.value = (lists ?? []).map((item: any) => ({
            ...item,
            pic: item.cover_url,
            url: item.video_url,
            name: item.humanAnchor?.name ?? item.name ?? "",
            voice_id: item.voice_id ?? "",
            voice_name: item.voice_name ?? "",
            use_num: item.use_num ?? 0,
        }));
    };

    const getVoiceList = async (): Promise<void> => {
        const { lists } = await getVoiceListApi({ persona_id: personId.value, page_size: 9999 });
        voices.value = (lists ?? []).map((item: any) => ({
            ...item,
            id: item.id,
            voice_id: item.voice_id,
            name: item.voice_name,
            url: item.preview_audio_url,
            use_num: item.use_num ?? 0,
            create_time: item.create_time ?? item.add_time ?? item.update_time ?? "",
        }));
    };

    const handleAddAvatar = async (): Promise<void> => {
        showChooseAnchor.value = true;
        await nextTick();
        chooseAnchorRef.value?.setDisabledLists(avatars.value.map((item) => ({ ...item, id: item.dh_id })));
    };

    const handleChooseAnchor = async (res: AvatarItem[]): Promise<void> => {
        if (res.length === 0) return;
        const newItems = res.filter(
            (item) => !avatars.value.some((avatar) => avatar.dh_id === item.id || avatar.id === item.id),
        );
        if (!newItems.length) return;
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await addAvatar({ persona_id: personId.value, dh_ids: newItems.map((item) => item.id) });
            uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
            await getAvatarList();
        } catch (error: any) {
            uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleRemoveAvatar = (item: AvatarItem): void => {
        uni.showModal({
            title: "提示",
            content: `确定移除形象「${item.name}」吗？`,
            confirmColor: "#FF4D4F",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    if (!item.is_local) await deleteAvatar({ ids: [item.id] });
                    avatars.value = avatars.value.filter((avatar) => avatar.id !== item.id);
                    uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
                } catch (error: any) {
                    uni.showToast({
                        title: error || "删除失败，请重试",
                        icon: "none",
                        duration: 3000,
                    });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const handleOpenVoiceForAvatar = (item: AvatarItem): void => {
        uni.showActionSheet({
            itemList: ["选择原音", "选择音色"],
            success: async ({ tapIndex }) => {
                if (tapIndex === 0) {
                    await handleBindOriginalVoice(item);
                    return;
                }
                currentBindingAvatar.value = item;
                showAvatarVoicePicker.value = true;
                await nextTick();
                if (item.voice_id) {
                    avatarVoicePickerRef.value?.setChooseLists([
                        {
                            voice_id: item.voice_id,
                            id: item.voice_id,
                            name: item.voice_name ?? "",
                            url: "",
                        },
                    ]);
                }
            },
        });
    };

    const handleBindOriginalVoice = async (item: AvatarItem): Promise<void> => {
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await bindAvatarVoice({
                persona_avatar_id: item.id,
                voice_id: 0,
                is_original_voice: 1,
            });
            item.is_original_voice = 1;
            item.voice_id = "";
            item.voice_name = "";
            item.bind_desc = "形象原音";
            uni.showToast({ title: "原音绑定成功", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error ?? "绑定失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleAvatarVoiceSelect = async (res: VoiceItem[]): Promise<void> => {
        if (!currentBindingAvatar.value || res.length === 0) return;
        const selected = res[0];
        const target = currentBindingAvatar.value;
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await bindAvatarVoice({
                persona_avatar_id: target.id,
                voice_id: selected.id,
                is_original_voice: 0,
            });
            target.is_original_voice = 0;
            target.voice_id = selected.id;
            target.voice_name = selected.name;
            uni.showToast({ title: "音色绑定成功", icon: "none", duration: 2000 });
        } catch (error: any) {
            uni.showToast({ title: error ?? "绑定失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
            currentBindingAvatar.value = null;
        }
    };

    const handleAddVoice = async (): Promise<void> => {
        showChooseVoice.value = true;
        await nextTick();
        chooseVoiceRef.value?.setDisabledLists(voices.value.map((item) => ({ ...item, id: item.voice_id })));
    };

    const handleChooseVoice = async (res: VoiceItem[]): Promise<void> => {
        if (res.length === 0) return;
        const newItems = res.filter((item) => !voices.value.some((voice) => voice.voice_id === item.voice_id));
        if (!newItems.length) return;
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await addVoice({
                persona_id: personId.value,
                voice_ids: newItems.map((item) => item.id),
            });
            uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
            await getVoiceList();
        } catch (error: any) {
            uni.showToast({ title: error || "保存失败", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleRemoveVoice = (item: VoiceItem): void => {
        if (isCurrentPlaying(item.voice_id)) {
            pause();
            currVoiceId.value = null;
        }
        uni.showModal({
            title: "提示",
            content: `确定移除音色「${item.name}」吗？`,
            confirmColor: "#FF4D4F",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "删除中...", mask: true });
                try {
                    if (!item.is_local) await deleteVoice({ ids: [item.id] });
                    voices.value = voices.value.filter((voice) => voice.voice_id !== item.voice_id);
                    uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
                } catch (error: any) {
                    uni.showToast({
                        title: error || "删除失败，请重试",
                        icon: "none",
                        duration: 3000,
                    });
                } finally {
                    uni.hideLoading();
                }
            },
        });
    };

    const destroyAudio = () => {
        pause();
        destroy();
    };

    const cleanup = () => {
        destroyAudio();
        stopSlicePolling();
    };

    return {
        activeMaterialFilter,
        activeDirectMaterialFilter,
        activeMaterialTab,
        avatars,
        batchDeleteMode,
        cleanup,
        directPagingRefreshKey,
        destroyAudio,
        formatMaterialTime,
        getAvatarList,
        getVoiceList,
        handleAddAvatar,
        handleAddVoice,
        handleAvatarVoiceSelect,
        handleChooseAnchor,
        handleChooseVoice,
        handleCancelBatchDelete,
        handleConfirmBatchDelete,
        handleConfirmMusicBatchDelete,
        handleCancelMusicBatch,
        handleDeleteFailedSlices,
        handleCloseDirectPublish,
        handleDirectMaterialFilter,
        handleMaterialFilter,
        handleMoreMaterial,
        handleOpenComposeUpload,
        handleOpenDirectPublish,
        handleOpenDirectUpload,
        handleOpenMusicUpload,
        handleOpenVoiceForAvatar,
        handlePlayMaterial,
        handlePlayMusic,
        handlePlayVoice,
        handlePreviewDirectMaterial,
        handlePreviewMaterial,
        handleRemoveMaterial: handleDeleteMaterial,
        handleRemoveDirectMaterial,
        handleRemoveAvatar,
        handleRemoveMusic,
        handleRemoveVoice,
        handleConfirmCutMode,
        handleSelectCategory,
        handleSelectHistory,
        handleSelectMaterial,
        handleSetDirectPublishVisible,
        handleSwitchPublishMode,
        handleSwitchMaterialTab,
        handleToggleBatchDelete,
        handleToggleMaterialSelected,
        handleToggleMusicBatch,
        handleToggleMusicSelected,
        handleToggleSelectAll,
        handleToggleSelectAllMusic,
        hasOverusedMaterial,
        isAllMaterialSelected,
        isAllMusicSelected,
        hasSlicingMaterial,
        hasSlicingTask,
        isCurrentPlaying,
        isCurrentMusicPlaying,
        isMaterialSelected,
        isMusicSelected,
        isMaterialSlicing,
        isMaterialSliceFailed,
        failedMaterialCount,
        loadNextMaterialPage,
        loadNextMusicPage,
        materialFinished,
        materialList,
        materialLoading,
        materialParams,
        materialType,
        chooseMaterialContentType,
        musicBatchMode,
        musicFinished,
        musicList,
        musicLoading,
        selectedMusicCount,
        selectedMusicIds,
        queryDirectMaterialList,
        queryMaterialList,
        queryMusicList,
        replaceMaterialIndex,
        resetMaterialList,
        resetMusicList,
        selectedMaterialCount,
        selectedMaterialIds,
        sliceStatistics,
        showAvatarVoicePicker,
        showChooseAnchor,
        showChooseVoice,
        showCutModePopup,
        showDirectPublishPanel,
        showHistory,
        showMaterialLibrary,
        showUploadCategoryPanel,
        showUploadProgress,
        forceAlbumTypePicker,
        uploadCategoryShowCategories,
        uploadCategoryTip,
        uploadMaterialList,
        voices,
    };
};
