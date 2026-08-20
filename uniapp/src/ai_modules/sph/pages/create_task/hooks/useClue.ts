// hooks/useClueStep.ts — Step2 线索词管理
import type { SphFormData } from "./types";

export function useClueStep(formData: SphFormData) {
    const showClueEdit = ref(false);
    const clueEditRef = shallowRef();
    const editClueIndex = ref(-1);

    const handleEditClue = async (index: number) => {
        showClueEdit.value = true;
        editClueIndex.value = index;
        await nextTick();
        if (index >= 0) clueEditRef.value?.setFormData(formData.keywords[index]);
    };

    const handleClueConfirm = (val: string) => {
        if (editClueIndex.value === -1) {
            formData.keywords.push(val);
        } else {
            formData.keywords[editClueIndex.value] = val;
        }
        showClueEdit.value = false;
        editClueIndex.value = -1;
    };

    const handleDeleteClue = (index: number) => formData.keywords.splice(index, 1);

    return {
        showClueEdit,
        clueEditRef,
        editClueIndex,
        handleEditClue,
        handleClueConfirm,
        handleDeleteClue,
    };
}
