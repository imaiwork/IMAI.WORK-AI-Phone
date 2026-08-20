import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";

export const NEWS_BODY_COPYWRITER_LIMIT = 1000;

export interface NewsBodyFormData {
    anchorLists: any[];
    copywriterList: any[];
    materialList: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    shanjian_type: MontageTypeEnum;
    music: any[];
    extra: {
        ai_music: boolean;
        volume: number;
        clip: number;
        music: number;
        video_count: number;
    };
    audio: any[];
    clip: any[];
}

export function useNewsBodySteps(formData: NewsBodyFormData) {
    const steps = ref([
        { step: 1, title: "上传视频" },
        { step: 2, title: "填写身份" },
        { step: 3, title: "填写文案" },
        { step: 4, title: "生成设置" },
    ]);

    const step = ref(1);

    const isSingleCopywriterValid = (text: string): boolean => {
        return text.trim().length >= 3 && text.length <= NEWS_BODY_COPYWRITER_LIMIT;
    };

    const isContentValid = (): boolean => {
        return formData.copywriterList.every((item: string) => isSingleCopywriterValid(item));
    };

    const canStepProceed = (stepNumber: number): boolean => {
        const strategy: Record<number, () => boolean> = {
            1: () => formData.materialList.length > 0,
            2: () => {
                if (!formData.person_introduction && !formData.person_name) {
                    return true;
                }
                return !!formData.person_introduction.trim() && !!formData.person_name.trim();
            },
            3: () => formData.copywriterList.length > 0 && isContentValid(),
            4: () => true,
        };
        return strategy[stepNumber]?.() ?? false;
    };

    const canNext = computed(() => canStepProceed(step.value));

    const handleStep = (targetStep: number, type?: "next" | "prev") => {
        if (type === "prev") {
            step.value--;
            return;
        }

        if (type === "next") {
            if (canNext.value) {
                step.value++;
            } else {
                const messages: Record<number, () => string> = {
                    1: () => "请上传参考素材",
                    2: () => "填写完整的人设信息",
                    3: () => {
                        if (!isContentValid()) return "文案不能少于3个字";
                        return "请至少添加一条文案";
                    },
                };
                uni.$u.toast(messages[step.value]?.() || "请完成当前步骤");
            }
            return;
        }

        if (targetStep === step.value) return;

        if (targetStep < step.value) {
            step.value = targetStep;
        } else {
            for (let i = 1; i < targetStep; i++) {
                if (!canStepProceed(i)) {
                    uni.$u.toast("请按顺序完成步骤");
                    return;
                }
            }
            step.value = targetStep;
        }
    };

    return {
        steps,
        step,
        canNext,
        handleStep,
        isContentValid,
        isSingleCopywriterValid,
    };
}
