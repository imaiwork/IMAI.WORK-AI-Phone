interface UseMaterialGroupOptions {
    formData: any;
}

export function useMaterialGroup({ formData }: UseMaterialGroupOptions) {
    const confirmDialogVisible = ref(false);
    const editMaterialIndex = ref(-1);

    // 获取当前素材组总时长
    const getMaterialGroupDuration = computed(() => {
        return formData.materialList.reduce(
            (acc: number, item: any) => acc + item.reduce((acc: number, item: any) => acc + parseInt(item.duration), 0),
            0,
        );
    });

    const getGroupDuration = (group: any[]): number => {
        return group.reduce((sum, item) => sum + Number(item.duration || 0), 0);
    };

    // ─── 跳转图组编辑页 ──────────────────────────────────────────

    const handleEditMaterial = (index?: number): void => {
        editMaterialIndex.value = index ?? -1;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_material_group/montage_material_group",
            params: {
                materialList:
                    editMaterialIndex.value !== -1
                        ? JSON.stringify(formData.materialList[editMaterialIndex.value])
                        : "",
            },
        });
    };

    // ─── 删除图组（二次确认） ────────────────────────────────────

    const handleDeleteMaterial = (index: number): void => {
        editMaterialIndex.value = index;
        confirmDialogVisible.value = true;
    };

    const handleDeleteMaterialConfirm = (): void => {
        formData.materialList.splice(editMaterialIndex.value, 1);
        confirmDialogVisible.value = false;
        editMaterialIndex.value = -1;
    };

    /**
     * EventBus 回调：图组编辑页返回后写入数据
     * 供 onLoad 的 on("confirm") 调用
     */
    const onMaterialGroupConfirm = (data: any[]): void => {
        if (editMaterialIndex.value !== -1) {
            // 编辑模式：data 为空则删除该图组
            if (data.length === 0) {
                formData.materialList.splice(editMaterialIndex.value, 1);
            } else {
                formData.materialList[editMaterialIndex.value] = data;
            }
            editMaterialIndex.value = -1;
        } else {
            // 新增模式：data 为空不处理
            if (data.length === 0) return;
            formData.materialList.push(data);
        }
    };

    return {
        confirmDialogVisible,
        editMaterialIndex,
        getMaterialGroupDuration,
        getGroupDuration,
        handleEditMaterial,
        handleDeleteMaterial,
        handleDeleteMaterialConfirm,
        onMaterialGroupConfirm,
    };
}
