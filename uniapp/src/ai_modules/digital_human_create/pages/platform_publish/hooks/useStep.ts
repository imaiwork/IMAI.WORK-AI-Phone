import type { PublishFormData } from "./types";

/**
 * 步骤导航
 * - canStepProceed：逐步校验
 * - handleStep：上一步 / 下一步 / 直接跳转
 */
export function useStep(formData: PublishFormData) {
    const step = ref(1);

    const canStepProceed = (stepNumber: number): boolean => {
        const strategy: Record<number, () => boolean> = {
            1: () => formData.materialList.length > 0,
            2: () => true,
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
                const messages: Record<number, string> = {
                    1: "请至少选择一个素材",
                };
                uni.$u.toast(messages[step.value] || "请完成当前步骤");
            }
            return;
        }

        // 点击步骤条直接跳转
        if (targetStep === step.value) return;
        if (targetStep < step.value) {
            step.value = targetStep;
            return;
        }
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    };

    return { step, canNext, handleStep };
}
