import useUpload from "@/hooks/useUpload";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";

interface UseRealPersonAnchorOptions {
    formData: any;
}

export function useRealPersonAnchor({ formData }: UseRealPersonAnchorOptions) {
    const showHistory = ref(false);
    const showUploadTip = ref(false);
    const isFirstOpen = ref(true);
    const uploadMaterialType = ref("video");
    const replaceMaterialIndex = ref(-1);
    const showVideoPreview = ref(false);
    const videoPreview = reactive({ poster: "", url: "" });

    const { processAndAppend } = useMaterial(toRef(formData, "anchorLists"));

    const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
        isTranscode: true,
        videoDuration: [1, 59],
        isFetchVideoInfo: true,
        onSuccess: (materials: any[]) => {
            if (replaceMaterialIndex.value !== -1) {
                formData.anchorLists[replaceMaterialIndex.value] = materials[0];
            } else {
                formData.anchorLists = formData.anchorLists.concat(materials);
            }
            replaceMaterialIndex.value = -1;
        },
    });

    // ─── 播放预览 ────────────────────────────────────────────────

    const handleVideoPlay = (item: any): void => {
        videoPreview.poster = item.pic;
        videoPreview.url = item.url;
        showVideoPreview.value = true;
    };

    // ─── 删除口播视频 ────────────────────────────────────────────

    const handleDeleteAnchor = (index: number): void => {
        formData.anchorLists.splice(index, 1);
    };

    // ─── 替换口播视频 ────────────────────────────────────────────

    const handleReplaceAnchor = (index: number): void => {
        replaceMaterialIndex.value = index;
        uploadMaterialType.value = "video";
        chooseAnchorUploadType();
    };

    /**
     * 口播视频上传来源选择
     * 仅支持：创作历史 / 手机相册
     */
    const chooseAnchorUploadType = (): void => {
        uni.showActionSheet({
            itemList: ['从"创作历史"中选择', '从"手机相册"中选择'],
            success: ({ tapIndex }) => {
                if (tapIndex === 0) {
                    showHistory.value = true;
                    return;
                }
                // 手机相册
                uploadMaterialType.value = "video";
                if (isFirstOpen.value) {
                    isFirstOpen.value = false;
                    showUploadTip.value = true;
                    return;
                }
                uploadAndProcessFiles("video");
            },
        });
    };

    /**
     * 从创作历史选择口播视频
     */
    const handleSelectHistory = async (lists: any[]): Promise<void> => {
        showHistory.value = false;
        await processAndAppend({
            rawList: lists,
            urlField: "url",
            maxDuration: 59,
            replaceIndex: replaceMaterialIndex.value,
        });
    };

    return {
        showHistory,
        showUploadTip,
        isFirstOpen,
        showVideoPreview,
        videoPreview,
        showUploadProgress,
        uploadMaterialList,
        uploadMaterialType,
        replaceMaterialIndex,
        uploadAndProcessFiles,
        handleVideoPlay,
        handleDeleteAnchor,
        handleReplaceAnchor,
        chooseAnchorUploadType,
        handleSelectHistory,
    };
}
