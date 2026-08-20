import WechatOA from "@/utils/wechat";
import { createSameCityTask, checkTaskPublishTime } from "@/api/device";
import type { SameCityFormData } from "./types";

export function useCreateTask(formData: SameCityFormData) {
    const currentFrequency = ref(0);
    const taskErrorMsg = ref("");

    const showCreateTaskSuccessDialog = ref(false);
    const showTaskMsgPop = ref(false);
    const taskMsgPopContent = ref<string[]>([]);
    // ── 表单校验 ──────────────────────────────────────────────────
    const validateForm = (): string | null => {
        if (!formData.name) return "请输入任务名称";
        if (!formData.accounts.length) return "请选择发布账号";
        if (currentFrequency.value === 5 && !formData.custom_date.length) return "请选择任务日期";
        if (!formData.time_config[0] || !formData.time_config[1]) return "请选择任务时间";
        return null;
    };
    const buildPayload = () => ({
        ...formData,
        task_date: formData.custom_date,
        time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
        custom_date: undefined,
    });

    // ── 执行提交 ──────────────────────────────────────────────────
    const executeCreateTask = async () => {
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createSameCityTask(buildPayload());
            uni.hideLoading();
            showCreateTaskSuccessDialog.value = true;
            WechatOA.notify();
        } catch (error: any) {
            uni.hideLoading();
            if (typeof error === "string" && error.includes("24小时自动执行任务")) {
                uni.showModal({
                    title: "提示",
                    content: "您已开启24小时自动执行任务，无法创建手动任务，如需手动创建任务，请先关闭24小时托管。",
                    success: (res) => {
                        if (res.confirm) uni.$u.route({ url: "/ai_modules/device/pages/index/index" });
                    },
                });
            } else {
                taskErrorMsg.value = error;
                uni.showToast({ title: error, icon: "none", duration: 3000 });
            }
        }
    };

    // ── 入口：冲突检测 → 提交 ─────────────────────────────────────
    const handleCreateTask = async () => {
        const errMsg = validateForm();
        if (errMsg) {
            uni.$u.toast(errMsg);
            return;
        }

        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: "检测冲突中...", mask: true });
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    accounts: formData.accounts,
                    minutes: formData.minutes,
                });
                uni.hideLoading();
                if (messages?.length) {
                    taskMsgPopContent.value = messages;
                    formData.task_ids = task_ids;
                    showTaskMsgPop.value = true;
                    return;
                }
                await executeCreateTask();
            } catch (error: any) {
                uni.hideLoading();
                taskErrorMsg.value = error;
                uni.showToast({ title: error, icon: "none", duration: 3000 });
            }
        } else {
            await executeCreateTask();
        }
    };

    const handleTaskMsgPopConfirm = async () => {
        showTaskMsgPop.value = false;
        await executeCreateTask();
    };

    const handleCreateTaskSuccess = () => {
        showCreateTaskSuccessDialog.value = false;
        uni.$u.route({ url: "/ai_modules/device/pages/index/index", type: "reLaunch" });
    };

    return {
        currentFrequency,
        showCreateTaskSuccessDialog,
        showTaskMsgPop,
        taskMsgPopContent,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess,
    };
}
