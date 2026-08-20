import type { MaterialItem, PublishFormData } from "./types";
import useUpload from "@/hooks/useUpload";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";

type Category = UploadAlbumTypeEnum | UploadCategoryEnum;

export function useMaterialStep(formData: PublishFormData) {
    const showUploadCategoryPanel = ref(false);
    const uploadMaterialType = ref<"image" | "video">("image");
    const uploadMaterialMode = ref<any>("all");
    const showHistory = ref(false);
    const showMaterialLibrary = ref(false);
    const replaceMaterialIndex = ref(-1);
    const showVideoPreview = ref(false);
    const playItem = reactive({ pic: "", url: "" });

    const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

    const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
        onSuccess: (materials: any[]) => {
            const idx = replaceMaterialIndex.value;
            if (idx !== -1) {
                formData.materialList[idx] = materials[0];
            } else {
                formData.materialList = [...formData.materialList, ...materials];
            }
            replaceMaterialIndex.value = -1;
        },
    });

    const CATEGORY_HANDLER_MAP: Partial<Record<Category, () => void>> = {
        // 批量注册多个 key 指向同一个函数
        ...Object.fromEntries(
            [UploadAlbumTypeEnum.Image, UploadAlbumTypeEnum.Video, UploadAlbumTypeEnum.File].map((type) => [
                type,
                () => uploadAndProcessFiles(type),
            ]),
        ),
        [UploadCategoryEnum.Library]: () => {
            uploadMaterialMode.value = "all";
            showMaterialLibrary.value = true;
        },
        [UploadCategoryEnum.Group]: () => {
            uploadMaterialMode.value = "group";
            showMaterialLibrary.value = true;
        },
        [UploadCategoryEnum.Creation]: () => {
            showHistory.value = true;
        },
    };

    /** 上传分类面板回调 */
    const handleSelectCategory = (category: Category) => CATEGORY_HANDLER_MAP[category]?.();

    /** 预览素材 */
    const previewMaterial = (item: MaterialItem) => {
        if (item.type === "image") {
            uni.previewImage({ urls: [item.pic] });
        } else {
            playItem.pic = item.pic;
            playItem.url = item.url;
            showVideoPreview.value = true;
        }
    };

    /** 替换素材 */
    const handleReplaceMaterial = (index: number) => {
        replaceMaterialIndex.value = index;
        showUploadCategoryPanel.value = true;
    };

    /** 删除素材 */
    const handleDeleteMaterial = (index: number) => {
        formData.materialList.splice(index, 1);
    };

    /** 从创作历史选择 */
    const handleSelectHistory = async (lists: any[]) => {
        await processAndAppend({
            isParseVideoElement: false,
            rawList: lists,
            urlField: "url",
            replaceIndex: replaceMaterialIndex.value,
            onSuccess: () => (showHistory.value = false),
        });
    };

    /** 从素材库选择 */
    const handleSelectMaterial = (res: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = res[0];
        } else {
            formData.materialList = formData.materialList.concat(res);
        }
        replaceMaterialIndex.value = -1;
        showMaterialLibrary.value = false;
    };

    return {
        // 状态
        showUploadCategoryPanel,
        uploadMaterialType,
        uploadMaterialMode,
        showHistory,
        showMaterialLibrary,
        replaceMaterialIndex,
        showVideoPreview,
        playItem,
        showUploadProgress,
        uploadMaterialList,
        // 方法
        handleSelectCategory,
        previewMaterial,
        handleReplaceMaterial,
        handleDeleteMaterial,
        handleSelectHistory,
        handleSelectMaterial,
    };
}
