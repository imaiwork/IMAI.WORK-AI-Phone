import { montageConfig } from "@/ai_modules/digital_human/config";
import type { Ref } from "vue";

interface StepItem {
    step: number;
    title: string;
}

interface UseStepsOptions {
    formData: any;
    getMaterialTotalDuration: () => number;
    /** 可选：覆盖默认步骤配置（默认 5 步） */
    stepsConfig?: StepItem[];
    /** 可选：覆盖各步骤通过条件 */
    stepValidators?: Record<number, () => boolean>;
    /** 可选：覆盖各步骤错误提示 */
    stepErrorMessages?: Record<number, () => string>;
}

const DEFAULT_STEPS: StepItem[] = [
    { step: 1, title: "选择形象" },
    { step: 2, title: "填写身份" },
    { step: 3, title: "参考素材" },
    { step: 4, title: "生成设置" },
];

export function useSteps({
    formData,
    getMaterialTotalDuration,
    stepsConfig,
    stepValidators,
    stepErrorMessages,
}: UseStepsOptions) {
    const step = ref(1);
    const steps = ref<StepItem[]>(stepsConfig ?? DEFAULT_STEPS);

    // ─── 默认步骤校验策略 ────────────────────────────────────────

    const DEFAULT_VALIDATORS: Record<number, () => boolean> = {
        1: () => formData.anchorLists.length > 0,
        2: () => {
            const hasName = !!formData.person_name.trim();
            const hasIntro = !!formData.person_introduction.trim();
            if (!hasName && !hasIntro) return true;
            return hasName && hasIntro;
        },
        3: () => getMaterialTotalDuration() <= montageConfig.materialTotalDuration * 60,
        4: () => true,
    };

    // ─── 默认错误提示 ────────────────────────────────────────────

    const DEFAULT_ERROR_MESSAGES: Record<number, () => string> = {
        1: () => "请至少选择一个口播视频",
        2: () => "请填写完整的人设名称和介绍",
        3: () => {
            const total = getMaterialTotalDuration();
            return total > montageConfig.materialTotalDuration * 60
                ? `素材总时长不能超过${montageConfig.materialTotalDuration}分钟`
                : "";
        },
    };

    // 合并：外部传入的覆盖默认值
    const VALIDATORS = { ...DEFAULT_VALIDATORS, ...(stepValidators ?? {}) };
    const ERROR_MESSAGES = { ...DEFAULT_ERROR_MESSAGES, ...(stepErrorMessages ?? {}) };

    const canStepProceed = (stepNumber: number): boolean => VALIDATORS[stepNumber]?.() ?? false;

    const canNext = computed<boolean>(() => canStepProceed(step.value));

    const getStepErrorMsg = (stepNumber: number): string => ERROR_MESSAGES[stepNumber]?.() ?? "请完成当前步骤";

    // ─── 步骤跳转 ────────────────────────────────────────────────

    const handleStep = (targetStep: number, type?: "next" | "prev"): void => {
        if (type === "prev") {
            step.value--;
            return;
        }
        if (type === "next") {
            if (canNext.value) {
                step.value++;
            } else {
                const msg = getStepErrorMsg(step.value);
                if (msg) uni.$u.toast(msg);
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

    return { step, steps, canNext, canStepProceed, handleStep };
}
