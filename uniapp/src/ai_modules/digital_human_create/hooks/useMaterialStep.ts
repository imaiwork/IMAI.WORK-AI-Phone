import useUpload from "@/hooks/useUpload";
import { useMaterial } from "@/ai_modules/digital_human_create/hooks/useMaterial";
import { montageConfig, calcMaterialDurationBudget } from "@/enums/montageEnums";
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";
import { batchGetVideoInfoByUrl } from "@/api/app";

interface UseMaterialStepOptions {
    formData: any;
}

type Category = UploadAlbumTypeEnum | UploadCategoryEnum;

export function useMaterialStep({ formData }: UseMaterialStepOptions) {
    const showUploadCategoryPanel = ref(false);
    const showMaterialLibrary = ref(false);
    const showChooseHistory = ref(false);
    const showVideoPreview = ref(false);
    const showUploadTip = ref(false);
    const isFirstOpen = ref(true);
    const uploadMaterialType = ref<any>();
    const uploadMaterialMode = ref<any>("all");
    const replaceMaterialIndex = ref(-1);
    const videoPreview = reactive({ poster: "", url: "" });

    const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

    // ─── 素材总时长 ──────────────────────────────────────────────

    // 口径含截断误差补偿：消费方（getDurationLimit、useSteps 校验、图组确认）都是闸门，
    // 展示用的已用/剩余时长走 material-duration-bar，仍按原值显示
    const getMaterialTotalDuration = (): number =>
        formData.materialList.reduce(
            (acc: number, item: any) => acc + calcMaterialDurationBudget(item.type !== "video", item.duration),
            0,
        );

    const getDurationLimit = computed<number>(
        () => montageConfig.materialTotalDuration * 60 - getMaterialTotalDuration(),
    );

    // ─── 上传素材 ────────────────────────────────────────────────

    const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
        isTranscode: true,
        videoDuration: montageConfig.videoDuration,
        videoSize: montageConfig.videoSize,
        isFetchVideoInfo: true,
        onSuccess: (materials: any[]) => {
            // 先把素材写入列表，让 UI 立即响应（useUpload 不 await onSuccess，
            // showUploadProgress 会在本回调返回后关闭，不能阻塞）
            if (replaceMaterialIndex.value !== -1) {
                formData.materialList[replaceMaterialIndex.value] = materials[0];
            } else {
                formData.materialList = formData.materialList.concat(materials);
            }
            replaceMaterialIndex.value = -1;

            // 后端转码未完成时 upload 接口可能返回 duration=0，
            // 异步补全缺失的视频时长（与 useMaterial.processAndAppend 同口径）
            const needFetch = materials.filter(
                (item) => item.type === "video" && (!item.duration || Number(item.duration) <= 0),
            );
            if (needFetch.length > 0) {
                // 进度弹窗已关闭，短暂 loading 提示用户仍在处理
                uni.showLoading({ title: "时长获取中...", mask: true });
                batchGetVideoInfoByUrl({
                    video_urls: needFetch.map((item) => item.url),
                })
                    .then(({ results }: any) => {
                        const durationMap = new Map(results.map((r: any) => [r.url, r.data.duration]));
                        materials.forEach((item) => {
                            if (item.type === "video" && (!item.duration || Number(item.duration) <= 0)) {
                                const newDuration = durationMap.get(item.url);
                                if (newDuration != null) {
                                    item.duration = newDuration;
                                }
                            }
                        });
                    })
                    .catch(() => {
                        // 补全失败不阻塞主流程
                    })
                    .finally(() => {
                        uni.hideLoading();
                    });
            }
        },
    });

    // ─── 素材来源选择（相册 / 素材库 / 分组 / 创作记录） ────────

    const albumHandler = (type: UploadAlbumTypeEnum) => {
        uploadMaterialType.value = type;
        if (isFirstOpen.value) {
            isFirstOpen.value = false;
            showUploadTip.value = true;
            return;
        }
        uploadAndProcessFiles(type);
    };

    const CATEGORY_HANDLER_MAP: Partial<Record<Category, () => void>> = {
        // 批量注册多个 key 指向同一个函数
        ...Object.fromEntries(
            [UploadAlbumTypeEnum.Image, UploadAlbumTypeEnum.Video, UploadAlbumTypeEnum.File].map((type) => [
                type,
                () => albumHandler(type),
            ]),
        ),
        [UploadCategoryEnum.Library]: () => {
            uploadMaterialType.value = "all";
            uploadMaterialMode.value = "all";
            showMaterialLibrary.value = true;
        },
        [UploadCategoryEnum.Group]: () => {
            uploadMaterialType.value = "all";
            uploadMaterialMode.value = "group";
            showMaterialLibrary.value = true;
        },
        [UploadCategoryEnum.Creation]: () => {
            uploadMaterialType.value = "creation";
            showChooseHistory.value = true;
        },
    };

    const handleSelectCategory = (category: Category): void => CATEGORY_HANDLER_MAP[category]?.();

    const chooseUploadType = (): void => {
        showUploadCategoryPanel.value = true;
    };

    // ─── 从素材库选择 ────────────────────────────────────────────

    const handleSelectMaterial = async (res: any[]): Promise<void> => {
        await processAndAppend({
            rawList: res,
            urlField: "url",
            maxDuration: 59,
            replaceIndex: replaceMaterialIndex.value,
            onSuccess: (materials: any[]) => {},
        });
        replaceMaterialIndex.value = -1;
        showMaterialLibrary.value = false;
    };

    // ─── 从创作记录选择 ──────────────────────────────────────────

    const handleSelectHistory = async (res: any[]): Promise<void> => {
        await processAndAppend({
            rawList: res,
            urlField: "url",
            maxDuration: 59,
            replaceIndex: replaceMaterialIndex.value,
        });
    };

    // ─── 预览 ────────────────────────────────────────────────────

    const previewMaterial = (item: any): void => {
        if (item.type === "image") {
            uni.previewImage({ urls: [item.pic] });
        } else {
            videoPreview.poster = item.pic;
            videoPreview.url = item.url;
            showVideoPreview.value = true;
        }
    };

    // ─── 替换 / 删除 ─────────────────────────────────────────────

    const handleReplaceMaterial = (index: number): void => {
        replaceMaterialIndex.value = index;
        chooseUploadType();
    };

    const handleDeleteMaterial = (index: number): void => {
        formData.materialList.splice(index, 1);
    };

    return {
        montageConfig,
        // 状态
        isFirstOpen,
        showUploadCategoryPanel,
        showMaterialLibrary,
        showChooseHistory,
        showVideoPreview,
        showUploadTip,
        uploadMaterialType,
        uploadMaterialMode,
        replaceMaterialIndex,
        videoPreview,
        // 上传
        showUploadProgress,
        uploadMaterialList,
        uploadAndProcessFiles,
        // 计算
        getDurationLimit,
        getMaterialTotalDuration,
        // 方法
        handleSelectCategory,
        chooseUploadType,
        handleSelectMaterial,
        handleSelectHistory,
        previewMaterial,
        handleReplaceMaterial,
        handleDeleteMaterial,
    };
}
