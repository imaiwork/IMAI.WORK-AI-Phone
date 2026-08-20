import { getShanjianAnchorList } from "@/api/digital_human";
import { ShanjianCloneTypeEnum } from "@/ai_modules/digital_human/enums";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";

/** 形象状态 */
const enum AnchorStatus {
    GENERATING = 0,
    READY = 6,
}

interface UseAnchorOptions {
    formData: any;
}

export function useAnchor({ formData }: UseAnchorOptions) {
    const anchorLists = ref<any[]>([]);
    const anchorPagingRef = ref();

    // ─── 获取形象列表（z-paging 回调） ──────────────────────────

    const getAnchorList = async (page_no: number, page_size: number): Promise<void> => {
        try {
            const { lists } = await getShanjianAnchorList({
                page_no,
                page_size,
                status: [AnchorStatus.GENERATING, AnchorStatus.READY],
                clone_type: ShanjianCloneTypeEnum.FAST,
            });
            anchorPagingRef.value?.complete(lists);
        } catch {
            anchorPagingRef.value?.complete([]);
        }
    };

    // ─── 选择 / 取消选择形象 ─────────────────────────────────────

    const handleAnchorSelect = (val: any): void => {
        if (val.status !== AnchorStatus.READY) {
            uni.$u.toast("该形象正在生成中，请稍后再选择");
            return;
        }
        if (formData.anchorLists.includes(val)) {
            formData.anchorLists = formData.anchorLists.filter((item: any) => item !== val);
        } else {
            formData.anchorLists.push(val);
        }
        // 每次选择变化后同步 voice
        formData.voice = formData.anchorLists.map((item: any) => ({
            voice_id: item.voice_id,
            voice_url: item.voice_url,
            name: item.name,
            model_version: DigitalHumanModelVersionEnum.SHANJIAN,
        }));
    };

    // ─── 跳转新增形象页 ──────────────────────────────────────────

    const handleCreateAnchor = (): void => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
            params: { source: DigitalHumanModelVersionEnum.SHANJIAN },
        });
    };

    return {
        anchorLists,
        anchorPagingRef,
        getAnchorList,
        handleAnchorSelect,
        handleCreateAnchor,
    };
}
