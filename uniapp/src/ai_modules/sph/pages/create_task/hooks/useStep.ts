import type { SphFormData } from "./types";

export function useStep(formData: SphFormData) {
    const step = ref(1);

    const canStepProceed = (s: number): boolean => {
        const strategy: Record<number, () => boolean> = {
            1: () => true,
            2: () => formData.keywords.length > 0,
            3: () =>
                formData.add_type == 0 ||
                (formData.wechat_id.length > 0 && (formData.add_remark_enable === 0 || formData.remarks.length > 0)),
            4: () => formData.device_codes.length > 0 && !!formData.time_config[0],
        };
        return strategy[s]?.() ?? false;
    };

    const canNext = computed(() => canStepProceed(step.value));

    const STEP_MESSAGES: Record<number, string> = {
        2: "请至少添加一条线索",
        3: "请完善加微设置",
        4: "请设定时间",
    };

    const handleStep = (targetStep: number, type?: "next" | "prev") => {
        if (type === "prev") {
            step.value--;
            return;
        }
        if (type === "next") {
            canNext.value ? step.value++ : uni.$u.toast("请完成当前步骤");
            return;
        }
        if (targetStep === step.value) return;
        if (targetStep < step.value) {
            step.value = targetStep;
            return;
        }
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast(STEP_MESSAGES[i] || "请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    };

    return { step, canNext, canStepProceed, handleStep };
}
