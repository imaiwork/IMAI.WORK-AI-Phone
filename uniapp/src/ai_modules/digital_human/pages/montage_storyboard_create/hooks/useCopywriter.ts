import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";

export function useStoryboardCopywriter(formData: any, copywriterTypeIndex: Ref<number>) {
    const showChooseAgent = ref(false);
    const editCopywriterIndex = ref(-1);
    const editSubtitleContentIndex = ref(-1);
    const addSubtitleContentIndex = ref(-1);

    const handleShowCopywriter = (data?: string) => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter",
            params: {
                content: data ?? "",
            },
        });
    };

    const handleSelectCopywriter = (index: number, contentIndex?: number) => {
        editCopywriterIndex.value = index;
        if (copywriterTypeIndex.value === 0) {
            handleShowCopywriter(formData.copywriterList[index]);
        } else {
            editSubtitleContentIndex.value = contentIndex ?? -1;
            handleShowCopywriter(formData.subtitleList[index].contentList[contentIndex ?? -1]);
        }
    };

    const handleDeleteCopywriter = (index: number, contentIndex?: number) => {
        if (copywriterTypeIndex.value === 0) {
            formData.copywriterList.splice(index, 1);
        } else {
            formData.subtitleList[index].contentList.splice(contentIndex ?? -1, 1);
        }
    };

    const handleAddSubtitleContent = (index: number) => {
        addSubtitleContentIndex.value = index;
        editCopywriterIndex.value = -1;
        editSubtitleContentIndex.value = -1;
        handleShowCopywriter();
    };

    const handleSelectAgent = (res: any) => {
        const { data } = res;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter",
            params: {
                agentData: JSON.stringify(data),
                montageType: MontageTypeEnum.STORYBOARD_MIX,
            },
        });
    };

    // 事件总线：仅处理分镜混剪的文案事件
    const onCopywriterConfirm = (type: ListenerTypeEnum, data: any) => {
        // AI 文案回调（仅按顺序文案模式）
        if (type === ListenerTypeEnum.AI_COPYWRITER) {
            formData.copywriterList.push(...data.map((item: any) => item));
            return;
        }

        // 手动文案回调
        if (type === ListenerTypeEnum.SZR_COPYWRITER) {
            if (copywriterTypeIndex.value === 0) {
                // 按顺序文案：编辑 or 新增
                if (editCopywriterIndex.value !== -1) {
                    formData.copywriterList[editCopywriterIndex.value] = data;
                    editCopywriterIndex.value = -1;
                } else {
                    formData.copywriterList.push(data);
                }
            } else {
                // 镜头匹配文案：为指定组新增 or 编辑已有条目
                if (addSubtitleContentIndex.value !== -1) {
                    formData.subtitleList[addSubtitleContentIndex.value].contentList.push(data);
                    addSubtitleContentIndex.value = -1;
                } else if (editSubtitleContentIndex.value !== -1) {
                    formData.subtitleList[editCopywriterIndex.value].contentList[editSubtitleContentIndex.value] = data;
                    editSubtitleContentIndex.value = -1;
                    editCopywriterIndex.value = -1;
                }
            }
        }
    };

    return {
        showChooseAgent,
        editCopywriterIndex,
        editSubtitleContentIndex,
        addSubtitleContentIndex,
        handleShowCopywriter,
        handleSelectCopywriter,
        handleDeleteCopywriter,
        handleAddSubtitleContent,
        handleSelectAgent,
        onCopywriterConfirm,
    };
}
