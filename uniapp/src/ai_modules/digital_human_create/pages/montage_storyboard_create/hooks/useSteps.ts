export const STORYBOARD_COPYWRITER_LIMIT = 1000;
export const STORYBOARD_SUBTITLE_LIMIT = 600;

export function useStoryboardSteps(formData: any, copywriterTypeIndex: Ref<number>) {
    const steps = ref([
        { step: 1, title: "分镜素材" },
        { step: 2, title: "字幕文案" },
        { step: 3, title: "生成设置" },
    ]);

    const step = ref(1);

    const isSingleCopywriterValid = (text: string): boolean => {
        return text.trim().length >= 3 && text.length <= STORYBOARD_COPYWRITER_LIMIT;
    };

    const isSingleSubtitleValid = (text: string): boolean => {
        return text.trim().length >= 3 && text.length <= STORYBOARD_SUBTITLE_LIMIT;
    };

    const isCopywriterValid = (): boolean => {
        if (copywriterTypeIndex.value === 0) {
            return (
                formData.copywriterList.every((item: string) => isSingleCopywriterValid(item)) &&
                formData.copywriterList.length <= 50
            );
        } else {
            return formData.subtitleList.every(
                (item: any) =>
                    item.contentList.length > 0 &&
                    item.contentList.every((content: string) => isSingleSubtitleValid(content)) &&
                    item.contentList.length <= 50,
            );
        }
    };

    const canStepProceed = (stepNumber: number): boolean => {
        const strategy: Record<number, () => boolean> = {
            1: () =>
                formData.storyboardList.length > 0 &&
                formData.storyboardList.every(
                    (item: any) => item.materialList.length > 0 && item.materialList.length <= 200,
                ),
            2: () => {
                if (copywriterTypeIndex.value === 0) {
                    return formData.copywriterList.length > 0 && isCopywriterValid();
                } else {
                    return formData.subtitleList.length > 0 && isCopywriterValid();
                }
            },
            3: () => true,
        };
        return strategy[stepNumber]?.() ?? false;
    };

    const canNext = computed(() => canStepProceed(step.value));

    const handleStep = (targetStep: number, type?: "next" | "prev") => {
        if (copywriterTypeIndex.value === 1 && formData.voiceValue.id) {
            formData.voiceValue = {};
        }
        if (type === "prev") {
            step.value--;
            return;
        }

        if (type === "next") {
            if (canNext.value) {
                step.value++;
            } else {
                const messages: Record<number, () => string> = {
                    1: () => "请至少添加一个镜头组素材",
                    2: () => {
                        if (copywriterTypeIndex.value === 0) {
                            if (!isCopywriterValid()) {
                                return `口播文案内容不能少于3个字，不能超过${STORYBOARD_COPYWRITER_LIMIT}个字`;
                            }
                            return "请至少添加一条文案";
                        } else {
                            if (!isCopywriterValid()) {
                                return "每个镜头组至少需要添加一条字幕，且内容不能少于3个字";
                            }
                            return "请至少添加一条字幕";
                        }
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
        isCopywriterValid,
        isSingleCopywriterValid,
        isSingleSubtitleValid,
    };
}
