import { montageConfig } from "@/ai_modules/digital_human/config";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";

const COPYWRITER_LIMIT = 600;

const enum CopywriterTab {
    TEXT = 0,
    AUDIO = 1,
}

interface UseStepsOptions {
    formData: any;
    copywriterTypeIndex: Ref<number>;
    isSingleCopywriterValid: (text: string) => boolean;
    /** 当前已选音色对象（含 model_version） */
    voiceValue: Ref<any>;
    /** 清空音色的回调 */
    onClearVoice: () => void;
}

export const STEPS = [
    { step: 1, title: "上传素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "生成设置" },
] as const;

export function useSteps({
    formData,
    copywriterTypeIndex,
    isSingleCopywriterValid,
    voiceValue,
    onClearVoice,
}: UseStepsOptions) {
    const step = ref(1);
    const steps = ref([...STEPS]);

    // ─── 步骤校验 ────────────────────────────────────────────────

    const getMaterialGroupDuration = computed(() => {
        return formData.materialList.reduce(
            (acc: number, item: any) => acc + item.reduce((acc: number, item: any) => acc + parseInt(item.duration), 0),
            0,
        );
    });

    const VALIDATORS: Record<number, () => boolean> = {
        1: () => {
            return (
                formData.materialList.length > 0 &&
                getMaterialGroupDuration.value <= montageConfig.materialTotalDuration * 60
            );
        },
        2: () =>
            copywriterTypeIndex.value === CopywriterTab.TEXT
                ? formData.copywriterList.length > 0 &&
                  formData.copywriterList.every((item: any) => isSingleCopywriterValid(item.content))
                : formData.audio.length > 0,
        3: () => true,
    };

    // ─── 错误提示 ────────────────────────────────────────────────

    const ERROR_MESSAGES: Record<number, () => string> = {
        1: () => {
            if (getMaterialGroupDuration.value > montageConfig.materialTotalDuration * 60) {
                return `素材图组总时长不能超过${montageConfig.materialTotalDuration}分钟`;
            }
            return "请上传素材图组";
        },
        2: () => {
            if (copywriterTypeIndex.value === CopywriterTab.TEXT) {
                const hasInvalid = formData.copywriterList.some((item: any) => !isSingleCopywriterValid(item.content));
                return hasInvalid ? `口播文案内容不能少于3个字，不能超过${COPYWRITER_LIMIT}个字` : "请至少添加一条文案";
            }
            return "请至少添加一条音频";
        },
        3: () => "",
    };

    const canStepProceed = (stepNumber: number): boolean => VALIDATORS[stepNumber]?.() ?? false;

    const canNext = computed<boolean>(() => canStepProceed(step.value));

    // ─── 步骤2 → 步骤3 时的音色联动处理 ────────────────────────
    /**
     * 当文案模式为「上传音频」时，进入步骤3前检查已选音色：
     * - 若音色的 model_version 不是 SHANJIAN，则清空音色
     * - 若是 SHANJIAN 或未选音色，则保留
     */
    const syncVoiceOnEnterStep3 = (): void => {
        if (copywriterTypeIndex.value !== CopywriterTab.AUDIO) return;
        const currentModelVersion = voiceValue.value?.model_version;
        if (currentModelVersion && currentModelVersion !== DigitalHumanModelVersionEnum.SHANJIAN) {
            onClearVoice();
        }
    };

    // ─── 步骤跳转 ────────────────────────────────────────────────

    const handleStep = (targetStep: number, type?: "next" | "prev"): void => {
        if (type === "prev") {
            step.value--;
            return;
        }

        if (type === "next") {
            if (canNext.value) {
                // 从步骤2前进到步骤3时，执行音色联动
                if (step.value === 2) syncVoiceOnEnterStep3();
                step.value++;
            } else {
                const msg = ERROR_MESSAGES[step.value]?.() ?? "请完成当前步骤";
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
            // 点击步骤条直接跳到步骤3时，同样执行音色联动
            if (step.value === 2 && targetStep === 3) syncVoiceOnEnterStep3();
            step.value = targetStep;
        }
    };

    return { step, steps, canNext, canStepProceed, handleStep };
}
