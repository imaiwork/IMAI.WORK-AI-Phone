import type { CopywriterItem, PublishFormData } from "./types";

/**
 * Step2 文案管理
 * - 新增 / 编辑 / 删除文案
 * - EventBus 回调写入 formData
 */
export function useCopywriter(formData: PublishFormData) {
    const editCopywriterIndex = ref(-1);

    const showChooseAgent = ref(false);
    const handleSelectAgent = (res: any) => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter",
            params: { agentData: JSON.stringify(res.data) },
        });
    };

    /** 手动输入文案（新增 or 编辑） */
    const handleShowCopywriter = (data?: CopywriterItem) => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/platform_publish_copywriter/platform_publish_copywriter",
            params: { data: data ? JSON.stringify(data) : "" },
        });
    };

    /** 点击已有文案进入编辑 */
    const handleEditCopywriter = (index: number) => {
        editCopywriterIndex.value = index;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/platform_publish_copywriter/platform_publish_copywriter",
            params: { copywriter: JSON.stringify(formData.copywriterList[index]) },
        });
    };

    /** 删除文案 */
    const handleDeleteCopywriter = (index: number) => {
        formData.copywriterList.splice(index, 1);
    };

    /**
     * EventBus 回调：接收文案页面返回的数据
     */
    const onCopywriterConfirm = (data: CopywriterItem[]) => {
        if (!data.length) return;
        if (editCopywriterIndex.value !== -1) {
            formData.copywriterList[editCopywriterIndex.value] = data[0];
            editCopywriterIndex.value = -1;
        } else {
            formData.copywriterList.push(...data);
        }
    };

    return {
        showChooseAgent,
        editCopywriterIndex,
        handleSelectAgent,
        handleShowCopywriter,
        handleEditCopywriter,
        handleDeleteCopywriter,
        onCopywriterConfirm,
    };
}
