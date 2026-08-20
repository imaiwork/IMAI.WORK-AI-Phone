import { MontageTypeEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { NEWS_BODY_COPYWRITER_LIMIT } from "./useSteps";

export function useNewsBodyCopywriter(formData: { copywriterList: any[]; shanjian_type: MontageTypeEnum }) {
    const editCopywriterIndex = ref(-1);
    const showChooseAgent = ref(false);

    const handleSelectCopywriter = (index: number) => {
        editCopywriterIndex.value = index;
        const selectedCopywriter = formData.copywriterList[index];
        handleShowCopywriter(selectedCopywriter);
    };

    const handleShowCopywriter = (data?: string) => {
        uni.navigateTo({
            url: "/ai_modules/digital_human/pages/montage_copywriter/montage_copywriter",
            success: (res) => {
                res.eventChannel.emit("sendData", {
                    title: data,
                    isNewsBody: 1,
                    limit: NEWS_BODY_COPYWRITER_LIMIT,
                });
            },
        });
    };

    const handleDeleteCopywriter = (index: number) => {
        formData.copywriterList.splice(index, 1);
    };

    const handleSelectAgent = (res: any) => {
        const { data } = res;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter",
            params: {
                agentData: JSON.stringify(data),
                montageType: formData.shanjian_type,
            },
        });
    };

    const onCopywriterConfirm = (type: ListenerTypeEnum, data: any[]) => {
        if (type !== ListenerTypeEnum.MONTAGE_COPYWRITER && type !== ListenerTypeEnum.AI_COPYWRITER) return;

        if (data.length === 0) return;

        if (editCopywriterIndex.value !== -1) {
            formData.copywriterList[editCopywriterIndex.value] = data[0];
            editCopywriterIndex.value = -1;
        } else {
            formData.copywriterList.push(...data);
        }
    };

    return {
        editCopywriterIndex,
        showChooseAgent,
        handleSelectCopywriter,
        handleShowCopywriter,
        handleDeleteCopywriter,
        handleSelectAgent,
        onCopywriterConfirm,
    };
}
