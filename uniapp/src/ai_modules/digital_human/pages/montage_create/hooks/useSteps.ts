import { montageConfig } from "@/ai_modules/digital_human/config";

const enum CopywriterTab {
    TEXT = 0,
    AUDIO = 1,
}

const COPYWRITER_LIMIT = 600;

interface UseStepsOptions {
    formData: any;
    copywriterTypeIndex: Ref<number>;
    getMaterialTotalDuration: () => number;
    isSingleCopywriterValid: (text: string) => boolean;
}

export function useSteps({
    formData,
    copywriterTypeIndex,
    getMaterialTotalDuration,
    isSingleCopywriterValid,
}: UseStepsOptions) {
    const step = ref(1);

    const steps = ref([
        { step: 1, title: "选择形象" },
        { step: 2, title: "填写身份" },
        { step: 3, title: "选择文案" },
        { step: 4, title: "参考素材" },
        { step: 5, title: "生成设置" },
    ]);

    // ─── 每步通过条件 ────────────────────────────────────────────

    const STEP_VALIDATORS: Record<number, () => boolean> = {
        1: () => formData.anchorLists.length > 0,
        2: () => {
            const hasName = !!formData.person_name.trim();
            const hasIntro = !!formData.person_introduction.trim();
            if (!hasName && !hasIntro) return true;
            return hasName && hasIntro;
        },
        3: () =>
            copywriterTypeIndex.value === CopywriterTab.TEXT
                ? formData.copywriterList.length > 0 &&
                  formData.copywriterList.every((item: any) => isSingleCopywriterValid(item.content))
                : formData.audio.length > 0,
        4: () => getMaterialTotalDuration() <= montageConfig.materialTotalDuration * 60,
        5: () => true,
    };

    const canStepProceed = (stepNumber: number): boolean => STEP_VALIDATORS[stepNumber]?.() ?? false;

    const canNext = computed<boolean>(() => canStepProceed(step.value));

    // ─── 不通过时的提示文案 ──────────────────────────────────────

    const getStepErrorMsg = (stepNumber: number): string => {
        const messages: Record<number, () => string> = {
            1: () => "请至少选择一个形象",
            2: () => "请填写完整的人设名称和介绍",
            3: () => {
                if (copywriterTypeIndex.value === CopywriterTab.TEXT) {
                    const hasInvalid = formData.copywriterList.some(
                        (item: any) => !isSingleCopywriterValid(item.content),
                    );
                    return hasInvalid
                        ? `口播文案内容不能少于3个字，不能超过${COPYWRITER_LIMIT}个字`
                        : "请至少添加一条文案";
                }
                return "请至少添加一条音频";
            },
            4: () => {
                const total = getMaterialTotalDuration();
                return total > montageConfig.materialTotalDuration * 60
                    ? `素材总时长不能超过${montageConfig.materialTotalDuration}分钟`
                    : "";
            },
        };
        return messages[stepNumber]?.() ?? "请完成当前步骤";
    };

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
        // 点击步骤条直接跳转
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
        step,
        steps,
        canNext,
        canStepProceed,
        handleStep,
    };
}
