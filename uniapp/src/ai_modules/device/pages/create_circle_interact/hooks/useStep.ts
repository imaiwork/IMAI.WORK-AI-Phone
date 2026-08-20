import type { CircleFormData } from "./types";

export function useStep(formData: CircleFormData) {
    const step = ref(1);

    const canStepProceed = (stepNumber: number): boolean => {
        switch (stepNumber) {
            case 1:
                return !!(formData.robot_id && (formData.comment_type == 2 ? formData.comment_content : true));
            case 2:
                return true;
            default:
                return false;
        }
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
                    1: () => {
                        if (!formData.robot_id) return "请设置评论智能体";
                        if (formData.comment_type == 2 && !formData.comment_content) return "请输入固定评论内容";
                        return "";
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

    return { step, canNext, handleStep };
}
