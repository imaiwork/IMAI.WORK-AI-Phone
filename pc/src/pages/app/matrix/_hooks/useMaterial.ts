import {
    getCopywritingMaterialDetail as apiGetCopywritingMaterialDetail,
    addCopywritingMaterial as apiAddCopywritingMaterial,
    updateCopywritingMaterial as apiUpdateCopywritingMaterial,
    deleteCopywritingMaterial as apiDeleteCopywritingMaterial,
} from "@/api/redbook";
import { useDebounceFn } from "@vueuse/core";
export default function useMaterial() {
    const getCopywritingMaterialDetail = async (id: string) => {
        const result = await apiGetCopywritingMaterialDetail({ id });
        return result;
    };

    const addCopywritingMaterial = async (params: any) => {
        const result = await apiAddCopywritingMaterial(params);
        return result;
    };

    const updateCopywritingMaterial = useDebounceFn(async (params: any) => {
        // await apiUpdateCopywritingMaterial(params);
    }, 1000);

    const deleteCopywritingMaterial = async (id: string) => {
        const result = await apiDeleteCopywritingMaterial({ id });
    };

    return {
        getCopywritingMaterialDetail,
        addCopywritingMaterial,
        updateCopywritingMaterial,
        deleteCopywritingMaterial,
    };
}
