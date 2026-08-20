import { createSameCityInterceptTask, checkTaskPublishTime } from "@/api/device";
import WechatOA from "@/utils/wechat";
import type { PublishFormData } from "./types";

/**
 * Step2 创建任务：
 *   - 提交逻辑
 *   - 冲突弹窗确认
 *   - 成功回调
 */
export function useCreateTask(formData: PublishFormData) {
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
        name: formData.name,
        marker_method: formData.marker_method,
        persona_id: formData.persona_id,
        interval_time: formData.interval_time,
        watch_time: formData.watch_time,
        radius: formData.radius,
        gender: formData.gender,
        like_num: formData.like_num,
        old: {
            min: formData.age_min,
            max: formData.age_max,
        },
        comment_num: formData.comment_num,
        comment_follow_num: {
            min: formData.comment_follow_min_num,
            max: formData.comment_follow_max_num,
        },
        comment_fans_num: {
            min: formData.comment_fans_min_num,
            max: formData.comment_fans_max_num,
        },
        filter: formData.include_filter,
        nickname_filter: formData.nickname_filter,
        accounts: formData.accounts,
        minutes: formData.minutes,
        task_exec_type: formData.task_exec_type,
        task_frep: formData.task_frep,
        time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
        task_date: formData.custom_date,
        task_ids: formData.task_ids,
    });

    // ── 执行提交 ──────────────────────────────────────────────────
    const executeCreateTask = async () => {
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createSameCityInterceptTask(buildPayload());
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

    const handleTaskMsgPopConfirm = () => {
        showTaskMsgPop.value = false;
        executeCreateTask();
    };

    const handleCreateTaskSuccess = () => {
        showCreateTaskSuccessDialog.value = false;
        uni.navigateBack();
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
