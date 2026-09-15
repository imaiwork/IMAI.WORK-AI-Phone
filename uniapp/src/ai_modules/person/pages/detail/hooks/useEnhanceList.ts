import { ENHANCE_THRESHOLD, EnhanceItemEnum } from "@/ai_modules/person/enums";
import type { PersonaSetupSummary } from "./useSetupChecklist";

export interface EnhanceItem {
    key: EnhanceItemEnum;
    title: string;
    /** 当前进度的一句话现状 */
    current: string;
    /** 完成后带来什么。是收益说明，不是打分 */
    benefit: string;
    actionText: string;
    done: boolean;
}

/** 加分项文案。顺序即影响力排序，底部「先补最有用的」直接取第一条未完成项 */
const ITEM_META: Record<EnhanceItemEnum, Pick<EnhanceItem, "title" | "benefit" | "actionText">> = {
    [EnhanceItemEnum.KEYWORD]: {
        title: `追踪词补到 ${ENHANCE_THRESHOLD[EnhanceItemEnum.KEYWORD]} 个`,
        benefit: "每天能挑的选题更多，撞车的概率更低",
        actionText: "去补充",
    },
    [EnhanceItemEnum.AVATAR]: {
        title: "再加 1 个数字人形象",
        benefit: "连发几条画面不会一模一样",
        actionText: "去添加",
    },
    [EnhanceItemEnum.MUSIC]: {
        title: "上传背景音乐",
        benefit: "成片不再是纯人声，完播率通常更高",
        actionText: "去上传",
    },
    [EnhanceItemEnum.ACCOUNT]: {
        title: "再绑 1 个平台账号",
        benefit: "一条内容同时发两个平台，不多花算力",
        actionText: "去绑定",
    },
    [EnhanceItemEnum.COPYWRITING]: {
        title: `文案库积累到 ${ENHANCE_THRESHOLD[EnhanceItemEnum.COPYWRITING]} 条`,
        benefit: "AI 有更多你自己的风格样本可参考",
        actionText: "去补充",
    },
    [EnhanceItemEnum.VOICE]: {
        title: "再加 1 个音色",
        benefit: "不同类型的内容可以换个声音说",
        actionText: "去添加",
    },
    [EnhanceItemEnum.MATERIAL]: {
        title: `素材补到 ${ENHANCE_THRESHOLD[EnhanceItemEnum.MATERIAL]} 段`,
        benefit: "素材池更深，成片重复率更低",
        actionText: "去上传",
    },
};

const ITEM_ORDER: EnhanceItemEnum[] = [
    EnhanceItemEnum.KEYWORD,
    EnhanceItemEnum.AVATAR,
    EnhanceItemEnum.MUSIC,
    EnhanceItemEnum.ACCOUNT,
    EnhanceItemEnum.COPYWRITING,
    EnhanceItemEnum.VOICE,
    EnhanceItemEnum.MATERIAL,
];

const toCount = (value: unknown): number => Number(value) || 0;

/** 成品直发的人设不用剪辑原片库，加分项要盯的是成品库 */
const DIRECT_MATERIAL_META: Pick<EnhanceItem, "title" | "benefit" | "actionText"> = {
    title: `成品补到 ${ENHANCE_THRESHOLD[EnhanceItemEnum.MATERIAL]} 条`,
    benefit: "成品每条只能用一次，存量越多越不容易断供",
    actionText: "去上传",
};

export const useEnhanceList = (
    summary: Ref<PersonaSetupSummary | null | undefined>,
    requiredDone: Ref<number>,
    requiredTotal: Ref<number>,
) => {
    const items = computed<EnhanceItem[]>(() => {
        const data = summary.value;
        const isDirectSend = toCount(data?.publish_mode) === 2;
        const countMap: Record<EnhanceItemEnum, number> = {
            [EnhanceItemEnum.KEYWORD]: toCount(data?.keyword_count),
            [EnhanceItemEnum.AVATAR]: toCount(data?.avatar_count),
            [EnhanceItemEnum.MUSIC]: toCount(data?.music_count),
            [EnhanceItemEnum.ACCOUNT]: toCount(data?.account_count),
            [EnhanceItemEnum.COPYWRITING]: toCount(data?.copywriting_count),
            [EnhanceItemEnum.VOICE]: toCount(data?.voice_count),
            // 跟配置清单第 2 步同一个口径：看哪个库由人设的发布方式决定，
            // 否则成品直发的人设会被要求「素材补到 20 段」，而它压根不用剪辑原片库
            [EnhanceItemEnum.MATERIAL]: toCount(isDirectSend ? data?.direct_material_count : data?.material_count),
        };
        const unitMap: Record<EnhanceItemEnum, string> = {
            [EnhanceItemEnum.KEYWORD]: "个",
            [EnhanceItemEnum.AVATAR]: "个",
            [EnhanceItemEnum.MUSIC]: "首",
            [EnhanceItemEnum.ACCOUNT]: "个",
            [EnhanceItemEnum.COPYWRITING]: "条",
            [EnhanceItemEnum.VOICE]: "个",
            [EnhanceItemEnum.MATERIAL]: isDirectSend ? "条" : "段",
        };

        return ITEM_ORDER.map((key) => {
            const count = countMap[key];
            return {
                key,
                ...(key === EnhanceItemEnum.MATERIAL && isDirectSend
                    ? DIRECT_MATERIAL_META
                    : ITEM_META[key]),
                current: count ? `现在 ${count} ${unitMap[key]}` : "还没有",
                done: count >= ENHANCE_THRESHOLD[key],
            };
        });
    });

    const totalCount = computed(() => items.value.length);
    const doneCount = computed(() => items.value.filter((item) => item.done).length);
    const remainCount = computed(() => totalCount.value - doneCount.value);

    // 完善度把必须项和加分项一起算：必须项齐了只是及格，不是满分
    const percent = computed(() => {
        const total = requiredTotal.value + totalCount.value;
        if (!total) return 0;
        return Math.round(((requiredDone.value + doneCount.value) / total) * 100);
    });

    // 不让用户在 7 项里自己挑，底部按钮直接给影响最大的那一项
    const topItem = computed<EnhanceItem | undefined>(() => items.value.find((item) => !item.done));

    return { doneCount, items, percent, remainCount, topItem, totalCount };
};
