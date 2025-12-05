import {
    materialGroupLists,
    materialGroupAdd,
    materialGroupUpdate,
    materialGroupDelete,
    materialLists,
    materialAdd,
    materialUpdate,
    materialDelete,
    materialInfo,
} from "@/api/person_wechat";
import { downloadFile } from "@/utils/util";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
const nuxtApp = useNuxtApp();

// 当前类目
const currentCate = ref<MaterialTypeEnum>(MaterialTypeEnum.IMAGE);
// 当前分组
const currentGroupId = ref<any>();
// 分组列表
const cateLists = ref<any[]>([]);
export function useCate() {
    // 获取分组列表
    const getCateLists = async () => {
        const data = await materialGroupLists({
            page_type: 0,
            file_type: currentCate.value,
        });
        cateLists.value = data.lists;
        if (cateLists.value.length > 0 && !currentGroupId.value) {
            currentGroupId.value = cateLists.value[0].id;
        }
    };

    // 添加分组
    const handleAddGroup = async (value: string) => {
        try {
            await materialGroupAdd({ group_name: value });
            feedback.msgSuccess("添加成功");
            getCateLists();
        } catch (error) {
            feedback.msgError(error);
        }
    };

    // 编辑分组
    const handleEditGroup = async (value: string, id: number) => {
        try {
            await materialGroupUpdate({
                id,
                group_name: value,
            });
            feedback.msgSuccess("编辑成功");
            // getCateLists();
        } catch (error) {
            feedback.msgError(error);
        }
    };

    // 删除分组
    const handleDeleteGroup = async (id: number) => {
        return new Promise(async (resolve, reject) => {
            await nuxtApp.$confirm({
                message: "确定要删除？",
                onConfirm: async () => {
                    try {
                        await materialGroupDelete({ id });
                        feedback.msgSuccess("删除成功");
                        if (id == currentGroupId.value) {
                            currentGroupId.value = cateLists.value[0].id;
                        }
                        getCateLists();
                        resolve(true);
                    } catch (error) {
                        feedback.msgError(error);
                        reject(false);
                    }
                },
            });
        });
    };

    //选中分类
    const handleGroupSelect = (item: any) => {
        currentGroupId.value = item.id;
    };

    return {
        currentCate,
        currentGroupId,
        cateLists,
        getCateLists,
        handleAddGroup,
        handleEditGroup,
        handleDeleteGroup,
        handleGroupSelect,
    };
}

const queryParams = reactive({
    file_name: "",
    file_type: currentCate.value,
    group_id: currentGroupId.value,
});
const { pager, getLists, resetPage } = usePaging({
    fetchFun: materialLists,
    params: queryParams,
    firstLoading: true,
});
export function useFile() {
    const selectItem = ref<any[]>([]);

    // 添加素材
    const handleAddMaterial = async (params: any) => {
        try {
            await materialAdd({
                file_type: currentCate.value,
                ...params,
            });
            getLists();
            feedback.msgSuccess("添加成功");
        } catch (error) {
            feedback.msgError(error);
        }
    };

    // 删除素材
    const handleDeleteMaterial = async (ids: string[] | number[]) => {
        return new Promise(async (resolve, reject) => {
            nuxtApp.$confirm({
                message: "确定要删除？",
                onConfirm: async () => {
                    try {
                        await materialDelete({ ids });
                        feedback.msgSuccess("删除成功");
                        getLists();
                        resolve(true);
                    } catch (error) {
                        feedback.msgError(error);
                        reject(false);
                    }
                },
            });
        });
    };

    // 编辑素材
    const handleEditMaterial = async (params: any) => {
        try {
            await materialUpdate(params);
            getLists();
            feedback.msgSuccess("编辑成功");
        } catch (error) {
            feedback.msgError(error);
        }
    };

    // 下载素材
    const handleDownload = (url: string) => {
        downloadFile(url);
    };

    // 获取素材详情
    const handleGetMaterialInfo = async (id: number) => {
        await materialInfo({ id });
    };

    return {
        currentCate,
        queryParams,
        pager,
        selectItem,
        getLists,
        resetPage,
        handleAddMaterial,
        handleDeleteMaterial,
        handleEditMaterial,
        handleGetMaterialInfo,
        handleDownload,
    };
}
