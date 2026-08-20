import { STEPS } from "./types";
import type { PublishFormData } from "./types";

/**
 * 步骤导航 & 每步校验
 */
export function useStep(formData: PublishFormData) {
    const step = ref(1);

    // ── 每步通过条件 ──────────────────────────────────────────────
    const canStepProceed = (s: number): boolean => {
        switch (s) {
            case 1:
                if (formData.marker_method.length === 0) return false;
                if (!formData.persona_id) return false;
                if (!formData.watch_time || Number(formData.watch_time) === 0) return false;
                if (!formData.interval_time || Number(formData.interval_time) === 0) return false;
                if (formData.include_filter.length === 0) return false;
                return true;
            case 2:
                return formData.custom_date.length > 0;
            default:
                return false;
        }
    };

    // ── 每步错误提示 ──────────────────────────────────────────────
    const getStepErrorMsg = (s: number): string => {
        const map: Record<number, () => string> = {
            1: () => {
                if (formData.marker_method.length === 0) return "请至少选择一个执行动作";
                if (!formData.persona_id) return "请选择IP人设";
                if (!formData.watch_time || Number(formData.watch_time) === 0) return "观看视频秒数不能为0";
                if (!formData.interval_time || Number(formData.interval_time) === 0) return "触达间隔不能为0";
                if (Number(formData.age_min) > Number(formData.age_max)) return "年龄最小值不能大于最大值";
                if (Number(formData.age_max) > 999) return "年龄最大值不能大于999";
                if (formData.include_filter.length === 0) return "请至少添加一个评论包含关键词";
                return "";
            },
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

        // 点击步骤条直接跳转（只允许跳到已完成的步骤）
        if (targetStep < step.value) {
            step.value = targetStep;
        }
    };

    return { step, canNext, handleStep };
}
