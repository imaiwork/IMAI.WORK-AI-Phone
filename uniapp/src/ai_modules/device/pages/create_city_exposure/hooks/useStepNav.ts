import { STEPS } from "./types";
import type { SameCityFormData } from "./types";

/**
 * 步骤导航 & 每步校验
 */
export function useStepNav(formData: SameCityFormData) {
    const step = ref(1);

    // ── 每步通过条件 ──────────────────────────────────────────────
    const canStepProceed = (s: number): boolean => {
        switch (s) {
            case 1:
                return true;
            case 2:
            default:
                return false;
        }
    };

    // ── 每步错误提示 ──────────────────────────────────────────────
    const getStepErrorMsg = (s: number): string => {
        const map: Record<number, () => string> = {
            2: () => "请设定时间",
        };
        return map[s]?.() ?? "请完成当前步骤";
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
                uni.$u.toast(getStepErrorMsg(step.value));
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

    return { STEPS, step, canNext, handleStep };
}
