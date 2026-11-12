interface UseMaterialGroupOptions {
    formData: any;
}

/** 镜头组名称最大长度 */
const GROUP_NAME_MAX = 12;

export function useMaterialGroup({ formData }: UseMaterialGroupOptions) {
    const confirmDialogVisible = ref(false);
    const editMaterialIndex = ref(-1);

    const handleEditMaterial = (index?: number) => {
        editMaterialIndex.value = index ?? -1;
        uni.$u.route({
            url: "/ai_modules/digital_human_create/pages/montage_material_group/montage_material_group",
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
                    title: `${formData.storyboardList[i].groupName}的字幕`,
                    contentList: [],
                });
            }
        } else if (newLen < oldLen) {
            formData.subtitleList.splice(newLen);
        }
    };

    // ─── 镜头组重命名 ─────────────────────────────────────────────
    const showGroupNameEdit = ref(false);
    const groupNameEditRef = shallowRef();
    const editingGroupIndex = ref(-1);

    const handleEditGroupName = (index: number) => {
        const group = formData.storyboardList[index];
        if (!group) return;
        editingGroupIndex.value = index;
        showGroupNameEdit.value = true;
        groupNameEditRef.value?.setFormData(group.groupName);
    };

    const handleConfirmGroupName = (name: string) => {
        const index = editingGroupIndex.value;
        editingGroupIndex.value = -1;
        const group = formData.storyboardList[index];
        if (!group) return;
        const newName = (name || "").trim();
        if (!newName || newName === group.groupName) return;
        const oldName = group.groupName;
        group.groupName = newName;
        // 字幕步骤默认按镜头组名称生成标题，改名后同步跟随
        const subtitle = formData.subtitleList[index];
        if (subtitle && subtitle.title === `${oldName}的字幕`) {
            subtitle.title = `${newName}的字幕`;
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
        showGroupNameEdit,
        groupNameEditRef,
        GROUP_NAME_MAX,
        handleEditGroupName,
        handleConfirmGroupName,
    };
}
