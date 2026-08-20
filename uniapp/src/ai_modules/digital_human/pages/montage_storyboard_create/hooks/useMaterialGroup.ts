interface UseMaterialGroupOptions {
    formData: any;
}

export function useMaterialGroup({ formData }: UseMaterialGroupOptions) {
    const confirmDialogVisible = ref(false);
    const editMaterialIndex = ref(-1);

    const handleEditMaterial = (index?: number) => {
        editMaterialIndex.value = index ?? -1;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_material_group/montage_material_group",
            params: {
                type: "storyboard",
                materialList:
                    editMaterialIndex.value !== -1
                        ? JSON.stringify(formData.storyboardList[editMaterialIndex.value].materialList)
                        : "",
            },
        });
    };

    const syncSubtitleList = () => {
        const newLen = formData.storyboardList.length;
        const oldLen = formData.subtitleList.length;
        if (newLen > oldLen) {
            for (let i = oldLen; i < newLen; i++) {
                formData.subtitleList.push({
                    title: `镜头组${i + 1}的字幕`,
                    contentList: [],
                });
            }
        } else if (newLen < oldLen) {
            formData.subtitleList.splice(newLen);
        }
    };

    const handleDeleteStoryboard = (index: number) => {
        formData.storyboardList.splice(index, 1);
        syncSubtitleList();
    };
    return {
        confirmDialogVisible,
        editMaterialIndex,
        syncSubtitleList,
        handleEditMaterial,
        handleDeleteStoryboard,
    };
}
