import type { AddFriendFormData } from "./types";

interface UseStepOptions {
    formData: AddFriendFormData;
    taskList: Ref<any[]>;
    timeIntervalIndex: Ref<number>;
    timeInterval: Ref<number | undefined>;
}

export function useStep(options: UseStepOptions) {
    const { formData, taskList, timeIntervalIndex, timeInterval } = options;

    const step = ref(1);

    const canStepProceed = (stepNumber: number): boolean => {
        switch (stepNumber) {
            case 1:
                return taskList.value.length > 0;
            case 2:
                if (formData.wechat_id.length === 0) return false;
                if (timeIntervalIndex.value === 4 && !timeInterval.value) return false;
                formData.add_remark_enable = formData.remarks.length === 0 ? 0 : 1;
                return true;
            case 3:
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
                const messages: Record<number, string> = {
                    1: "请至少添加一个线索",
                    2: formData.wechat_id.length === 0 ? "请选择加微微信" : "请填写完整设置",
                    3: "请设定时间",
                };
                uni.$u.toast(messages[step.value] || "请完成当前步骤");
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

    return { step, canNext, canStepProceed, handleStep };
}
