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
    updatePerson,
} from "@/api/person";
import { UploadAlbumTypeEnum, UploadCategoryEnum } from "@/enums/appEnums";
import { useMaterial } from "@/ai_modules/person/hooks/useMaterial";
import { MATERIAL_KIND_TO_USE_MODE, MaterialKindEnum, normalizeMaterialUseMode } from "@/ai_modules/person/enums";
import useUpload, { type UploadFailedItem } from "@/hooks/useUpload";
import usePolling from "@/hooks/usePolling";
import { useAudio } from "@/hooks/useAudio";
import cache from "@/utils/cache";
import { saveImageToPhotosAlbum, saveVideoToPhotosAlbum } from "@/utils/file";
import { computed, nextTick, reactive, ref, toRef, watch, type Ref } from "vue";

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
/** 素材表原图标记：原图模式入库 keep_original=1 且 source_type=original；自动适配入库 keep_original=0 */
const KEEP_ORIGINAL_YES = 1;
const KEEP_ORIGINAL_NO = 0;
const SOURCE_TYPE_ORIGINAL = "original";
/** 原图拦截清单最多展示的文件数，超出折叠成「等 N 个」 */
const ORIGINAL_REJECT_LIST_LIMIT = 6;

enum MaterialUploadScene {
    COMPOSE = "compose",
    DIRECT = "direct",
    MUSIC = "music",
}

const MUSIC_FILE_ACCEPT = ["mp3", "wav"] as const;
const DEFAULT_UPLOAD_CATEGORY_TIP = "支持同时选择图片与视频，特定场景仅支持选择视频或图片";
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
    /** 1 = 原图模式入库（keep_original），合成时不压缩不适配 */
    keep_original?: number;
    source_type?: string;
    source_type_text?: string;
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
    const totalCount = pickSliceField(raw, summary, lists, "total_count") || toFiniteNumber(raw?.total) || lists.length;

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
    /** 上游形象/音色还能不能参与合成；false 时仍然列出来，但要标出原因（会员到期冻结等） */
    available?: boolean;
    /** 形象本身（闪剪形象）是否可用：false 表示换音色也救不回来，界面要收掉绑定入口 */
    anchor_available?: boolean;
    unavailable_reason?: string;
}

export interface VoiceItem {
    voice_id: string;
    id: string;
    name: string;
    url: string;
    is_local?: boolean;
    use_num?: number;
    create_time?: string;
    /** 上游音色还能不能参与合成；false 时仍然列出来，但要标出原因（会员到期冻结等） */
    available?: boolean;
    unavailable_reason?: string;
}

export interface ChooseListRef {
    setChooseLists: (list: any[]) => void;
    setDisabledLists: (list: any[]) => void;
}

interface UseMaterialsTabOptions {
    personId: Ref<string>;
    publishMode: Ref<PublishMode>;
    /** 人设接口 material_use_mode，回显素材模式（人设表为准） */
    materialUseMode: Ref<number>;
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

/** 素材模式切换项（仅剪辑原片库） */
export const materialKindOptions = [
    { value: MaterialKindEnum.AUTO, label: "自动适配" },
    { value: MaterialKindEnum.ORIGINAL, label: "原图" },
] as const;

export const materialKindHint: Record<MaterialKindEnum, string> = {
    [MaterialKindEnum.AUTO]: "上传后自动转码压缩，不合规格也能用，画质会有轻微损失",
    [MaterialKindEnum.ORIGINAL]: "不压缩不适配，规格不符会被拦下",
};

/** 原图硬规格与站长 OriginalMaterialSpec 一致，改规格两边要同步 */
export const materialKindSpec: Record<MaterialKindEnum, { title: string; lines: string[]; note: string }> = {
    [MaterialKindEnum.AUTO]: {
        title: "自动适配：上传后由平台统一处理，不合规格也能用",
        lines: [
            "视频：自动转 H.264 · 帧率 30 · 短边 1080，超过 10 秒可自动切片",
            "图片：自动压缩适配",
            "音乐：MP3 / WAV 上传后统一转码",
        ],
        note: "处理需要一点时间，画质会有轻微损失；对画质有要求请切「原图」。合成只取自动适配下传的素材，原图素材要切到原图模式才会用上。",
    },
    [MaterialKindEnum.ORIGINAL]: {
        title: "原图模式：素材不做任何适配，必须满足",
        lines: [
            "视频：MP4/MOV 容器 · H.264 编码 · 帧率 25~30 · 长边 ≤2000 · 单条 ≤59 秒",
            "图片：JPG/PNG · 长边 ≤2000",
            "音乐：MP3/M4A/AAC/WAV，不重新编码",
        ],
        note: "长边一律不超过 2000（合成端硬限制，超过会被拒）。不符合的上传时会被拦下，素材入库后不再转码。",
    },
};

export const useMaterialsTab = ({
    personId,
    publishMode,
    materialUseMode,
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
    let materialRequestId = 0;
    const materialParams = reactive({
        page_no: 1,
        page_size: 10,
    });
    const activeDirectMaterialFilter = ref(0);

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
    const showAvatarVoiceMenu = ref(false);
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

    // 素材模式：回显人设接口 material_use_mode，切换走 aiPersona/update（人设表 + 合成配置双写）；默认自动适配，保持原有转码 / 切片链路
    const materialKind = ref<MaterialKindEnum>(MaterialKindEnum.AUTO);
    watch(
        materialUseMode,
        (mode) => {
            materialKind.value = normalizeMaterialUseMode(mode);
        },
        { immediate: true },
    );
    const isOriginalKind = (): boolean => materialKind.value === MaterialKindEnum.ORIGINAL;
    // 剪辑原片库两种模式各看各的：原图只看 keep_original=1 且 source_type=original，
    // 自动适配显式传 keep_original=0——不传时服务端不过滤，原图素材会混进来。
    // 音乐库不用这个过滤（见 queryMusicList）
    const getMaterialKindQuery = (): { keep_original: number; source_type?: string } =>
        isOriginalKind()
            ? { keep_original: KEEP_ORIGINAL_YES, source_type: SOURCE_TYPE_ORIGINAL }
            : { keep_original: KEEP_ORIGINAL_NO };
    // 原图只对剪辑原片库的相册上传生效：material_kind=original + ffmpeg=0，服务端校验后直接入库
    const isOriginalKindUpload = (): boolean => isComposeOriginalLibraryUpload() && isOriginalKind();

    const handleSwitchMaterialKind = async (kind: MaterialKindEnum): Promise<void> => {
        if (materialKind.value === kind) return;
        uni.showLoading({ title: "切换中...", mask: true });
        try {
            await updatePerson({ id: personId.value, material_use_mode: MATERIAL_KIND_TO_USE_MODE[kind] });
            materialKind.value = kind;
            // 素材列表按 keep_original 过滤，模式变了必须重拉，
            // 否则列表还是上一个模式的结果，来回切几次就会显示成空的。
            // 音乐库不分模式，不需要跟着刷
            await resetMaterialList();
            uni.hideLoading();
            uni.$u.toast(
                kind === MaterialKindEnum.ORIGINAL
                    ? "已切到原图：上传按规格校验，不再压缩"
                    : "已切到自动适配：上传后平台统一转码",
            );
        } catch (error: any) {
            uni.hideLoading();
            uni.$u.toast(error || "切换失败，请重试");
        }
    };

    // 上传失败清单：文件名 + 服务端给的原因一起列出来（可能一次多个），方便对照规格重新导出。
    // 原图模式额外给一个"切自动适配"的出口；其余场景只报原因，不做引导。
    const showUploadRejects = (failed: UploadFailedItem[]): void => {
        if (!failed.length) return;
        const isOriginal = isOriginalKindUpload();

        const lines = failed
            .slice(0, ORIGINAL_REJECT_LIST_LIMIT)
            .map((item) => `${item.name}：${item.reason || (isOriginal ? "未达原图规格" : "上传失败")}`);
        if (failed.length > ORIGINAL_REJECT_LIST_LIMIT) lines.push(`…等 ${failed.length} 个文件`);
        uni.showModal({
            title: isOriginal ? `${failed.length} 个文件未达原图规格` : `${failed.length} 个文件上传失败`,
            content: lines.join("\n"),
            showCancel: isOriginal,
            cancelText: "知道了",
            confirmText: isOriginal ? "自动适配" : "知道了",
            success: ({ confirm }) => {
                if (isOriginal && confirm) handleSwitchMaterialKind(MaterialKindEnum.AUTO);
            },
            fail: (err) => {
                console.log("err", err);
            },
        });
    };

    const {
        showUploadProgress: showComposeUploadProgress,
        uploadMaterialList: composeUploadList,
        uploadAndProcessFiles,
    } = useUpload({
        isTranscode: () => !isDirectUploadScene() && !isOriginalKindUpload(),
        isFetchVideoInfo: true,
        imageResolution: [999999, 999999],
        videoDuration: () => [1, 300],
        onPartialFail: showUploadRejects,
        onSuccess: (res: any[]) => {
            // 原图模式：上传接口已按规格校验并直接入库（返回 material_id），这里只刷列表，不再 batchAdd
            if (isOriginalKindUpload()) {
                shouldManualAddAfterUpload.value = false;
                uni.$u.toast(`已入库 ${res.length} 个原图素材`);
                reloadMaterialList();
                return;
            }
            const needManualAdd = isDirectUploadScene() || shouldManualAddAfterUpload.value;
            shouldManualAddAfterUpload.value = false;
            const toPayload = (list: any[]): MaterialPayload[] =>
                list.map((item) => ({
                    file_url: item.url,
                    material_type:
                        item.type === "image" ? PersonaMaterialTypeEnum.IMAGE : PersonaMaterialTypeEnum.VIDEO,
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
                    const fileIds = [...new Set([...getCachedVideoSliceFileIds(), ...getUploadedFileIds(videos)])];
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
    const uploadCategoryShowCategories = computed(
        (): Array<UploadAlbumTypeEnum | UploadCategoryEnum> =>
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

    const queryMaterialList = async (reload = false): Promise<boolean> => {
        if ((!reload && materialLoading.value) || materialFinished.value) return false;
        // 切回配置原片库或快速切筛选时，新查询不能被旧请求挡住，也不能接收旧结果。
        const requestId = ++materialRequestId;
        const pageNo = materialParams.page_no;
        try {
            materialLoading.value = true;
            const { lists = [], count = 0 } = await getMaterialLibraryList({
                persona_id: personId.value,
                page_no: pageNo,
                page_size: materialParams.page_size,
                material_type: materialFilters[activeMaterialFilter.value].value,
                publish_mode: publishMode.value,
                // 素材模式只管剪辑原片库；成品直发库不按原图过滤
                ...(publishMode.value === 1 ? getMaterialKindQuery() : {}),
            });
            if (requestId !== materialRequestId) return false;
            materialList.value = pageNo === 1 ? lists : materialList.value.concat(lists);
            if (materialList.value.length >= count || lists.length < materialParams.page_size) {
                materialFinished.value = true;
            }
        } catch {
            if (requestId !== materialRequestId) return false;
            materialFinished.value = true;
        } finally {
            if (requestId === materialRequestId) materialLoading.value = false;
        }
        return true;
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
                // 音乐库不跟随素材模式分库：原图/自动适配讲的是视频图片要不要转码，
                // 一段背景音频没有「原图」可言。传过的音乐两种模式都能看到、都能用
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
                // 原图模式：服务端按 MP3/M4A/AAC/WAV 校验并标 keep_original=1，合成时不重新编码
                ...(isOriginalKind() ? { material_kind: MaterialKindEnum.ORIGINAL } : {}),
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
        return queryMaterialList(true);
    };

    // 切割统计轮询：以统计接口为准，逐次刷新片段进度
    // 不传 file_ids，按 persona 拉全局进度，避免多批上传时只统计到最新一批
    const querySliceStatistics = async () => {
        if (!personId.value) return null;
        try {
            // 完成前是否在切割中（含列表兜底），用于「完成时必刷」且避免初始化重复拉列表
            const wasRunning =
                (!!sliceStatistics.value && isSliceTaskRunning(sliceStatistics.value)) || hasSlicingMaterial.value;
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
        if (!(await reloadMaterialList())) return;
        // 图片筛选下列表可能看不到切割中视频，仍有缓存 id 时继续轮询，避免误清
        if (hasSlicingMaterial.value || getCachedVideoSliceFileIds().length > 0) {
            await refreshSliceStatistics();
            return;
        }
        clearSliceProgress();
    };

    // 素材模式变了（人设回显到位 / 用户切换）查询条件跟着变，只重拉已经拉过的列表，没打开过的等首次加载
    watch(materialKind, () => {
        if (materialList.value.length || materialFinished.value) resetMaterialList();
        if (musicList.value.length || musicFinished.value) resetMusicList();
    });

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

    // 成品直发库渲染的就是 materialList（publish_mode=2），跟剪辑原片库同一份列表，
    // 所以刷新只能走 reloadMaterialList；早期 z-paging 版那个 refreshKey 没有组件消费，加了也不会刷新
    const resetDirectMaterialList = async () => {
        await reloadMaterialList();
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

    // 素材内容的「上传」按钮两个库共用，落哪个库要看当前在看哪个：
    // 一直按 COMPOSE 场景走，成品会被当成剪辑原片入库（原图模式下还会带上原图硬规格，
    // 59 秒以上的成品直接被服务端拒掉）
    const handleOpenComposeUpload = (): void => {
        handleOpenUploadCategoryPanel(
            publishMode.value === 2 ? MaterialUploadScene.DIRECT : MaterialUploadScene.COMPOSE,
        );
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
        // 原图模式：不转码不切割，material_kind=original + ffmpeg=0（缺一不可，服务端会拒），校验通过直接入库
        if (isOriginalKindUpload()) {
            shouldManualAddAfterUpload.value = false;
            uploadAndProcessFiles(category, {
                persona_id: personId.value,
                ffmpeg: 0,
                material_kind: MaterialKindEnum.ORIGINAL,
            });
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
            // 剪辑原片库选相册/视频时先选切割模式；纯图片无需切割，ffmpeg=1 后手动入库；原图模式不切割，跳过选择
            if (
                isComposeOriginalLibraryUpload() &&
                !isOriginalKind() &&
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
        // 原图是剪辑原片库的素材模式，成品直发库不分模式。按目标库判定，
        // 不能只看 scene：在成品直发库点通用上传时 scene 仍是 COMPOSE
        const targetPublishMode = scene === MaterialUploadScene.DIRECT ? 2 : publishMode.value;
        try {
            await batchAddMaterial({
                persona_id: personId.value,
                // 原图模式手动同步（素材库 / 素材组 / 创作库选入）：服务端逐条探测规格，不合格整批拒绝并给原因
                ...(isOriginalKind() && targetPublishMode === 1
                    ? { material_kind: MaterialKindEnum.ORIGINAL }
                    : {}),
                items: items.map((item: any) => ({
                    ...item,
                    publish_mode: targetPublishMode,
                })),
            });
            uni.showToast({ title: "添加成功", icon: "none", duration: 3000 });
            if (scene === MaterialUploadScene.DIRECT) {
                await resetDirectMaterialList();
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
            confirmColor: "#F56C6C",
            success: async ({ confirm }) => {
                if (!confirm) return;
                uni.showLoading({ title: "移除中...", mask: true });
                try {
                    await deleteMaterial({ id: item.id });
                    uni.showToast({ title: "已移除", icon: "none", duration: 2000 });
                    await resetDirectMaterialList();
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
            confirmColor: "#F56C6C",
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
            confirmColor: "#F56C6C",
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
            confirmColor: "#F56C6C",
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
            // 服务端会说清是哪一个形象、为什么加不进来（冻结 / 未克隆完），
            // 这种解释性文案塞 toast 会被截断，用弹窗保证看得全
            uni.hideLoading();
            uni.showModal({
                title: "形象没能加入",
                content: String(error || "保存失败，请重试"),
                showCancel: false,
                confirmText: "知道了",
            });
        } finally {
            uni.hideLoading();
        }
    };

    const handleRemoveAvatar = (item: AvatarItem): void => {
        uni.showModal({
            title: "提示",
            content: `确定移除形象「${item.name}」吗？`,
            confirmColor: "#F56C6C",
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
        showAvatarVoiceMenu.value = true;
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
            complete: () => {
                showAvatarVoiceMenu.value = false;
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
            uni.showToast({ title: "原音绑定成功", icon: "none", duration: 2000 });
            // 换绑之后这个形象可能从「不可用」变回可用（原来只是音色被冻结），
            // 就地改几个字段看不出这个变化，必须重拉列表让 available 重新算
            await getAvatarList();
        } catch (error: any) {
            uni.showToast({ title: error ?? "绑定失败，请重试", icon: "none", duration: 3000 });
        } finally {
            uni.hideLoading();
        }
    };

    const handleAvatarVoiceSelect = async (res: VoiceItem | VoiceItem[] | undefined): Promise<void> => {
        if (!currentBindingAvatar.value) return;
        const selected = Array.isArray(res) ? res[0] : res;
        const target = currentBindingAvatar.value;
        if (!selected) {
            currentBindingAvatar.value = null;
            return;
        }
        uni.showLoading({ title: "保存中...", mask: true });
        try {
            await bindAvatarVoice({
                persona_avatar_id: target.id,
                voice_id: selected.id,
                is_original_voice: 0,
            });
            uni.showToast({ title: "音色绑定成功", icon: "none", duration: 2000 });
            // 同上：绑上可用音色后这个形象就能用了，列表要重拉才看得到
            await getAvatarList();
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
            confirmColor: "#F56C6C",
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
        materialRequestId += 1;
        showAvatarVoiceMenu.value = false;
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
        handleSwitchMaterialKind,
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
        materialKind,
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
        showAvatarVoiceMenu,
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
