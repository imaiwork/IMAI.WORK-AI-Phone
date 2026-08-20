import WechatOA from "@/utils/wechat";
import { createCircleLikeTask, checkTaskPublishTime } from "@/api/device";
import type { CircleFormData } from "./types";

export function useCreateTask(formData: CircleFormData, currentFrequency: Ref<number>) {
    const taskErrorMsg = ref<string>("");
    const showCreateTaskSuccessDialog = ref(false);
    const showTaskMsgPop = ref(false);
    const taskMsgPopContent = ref<string[]>([]);

    const executeCreateTask = async () => {
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createCircleLikeTask({
                task_name: formData.name,
                accounts: formData.accounts,
                task_frep: formData.task_frep,
                time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
                custom_date: formData.custom_date,
                action: formData.interaction_action,
                number: formData.interaction_count,
                interval: formData.interaction_time,
                range: formData.interaction_time_type,
                comment_type: formData.comment_type,
                comment: formData.comment_content,
                robot_id: formData.robot_id,
                task_exec_type: formData.task_exec_type,
                minutes: formData.minutes,
                task_ids: formData.task_ids,
            });
            uni.hideLoading();
            showCreateTaskSuccessDialog.value = true;
            WechatOA.notify();
        } catch (error: any) {
            uni.hideLoading();
            if (error.indexOf("24小时自动执行任务") > -1) {
                uni.showModal({
                    title: "提示",
                    content: "您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。",
                    success: (res) => {
                        if (res.confirm) {
                            uni.$u.route({ url: "/ai_modules/device/pages/index/index" });
                        }
                    },
                });
            } else {
                taskErrorMsg.value = error;
                uni.showToast({ title: error, icon: "none", duration: 3000 });
            }
        }
    };

    const handleCreateTask = async () => {
        if (formData.interaction_time <= 0) return uni.$u.toast("每次互动时间间隔不能小于1分钟");
        if (!formData.name) return uni.$u.toast("请输入任务名称");
        if (!formData.accounts.length) return uni.$u.toast("请选择发布账号");
        if (currentFrequency.value === 5 && !formData.custom_date.length) return uni.$u.toast("请选择任务日期");
        if (!formData.time_config[0] || !formData.time_config[1]) return uni.$u.toast("请选择任务时间");
        if (formData.task_exec_type == 1) {
            if (formData.minutes < 1) return uni.$u.toast("执行时间不能小于1分钟");
            if (formData.minutes > 9999) return uni.$u.toast("执行时间不能超过9999分钟");
        }

        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: "检测冲突中...", mask: true });
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    accounts: formData.accounts,
                    minutes: formData.minutes,
                });
                uni.hideLoading();
                if (messages && messages.length > 0) {
                    taskMsgPopContent.value = messages;
                    formData.task_ids = task_ids;
                    showTaskMsgPop.value = true;
                    return;
                }
                await executeCreateTask();
            } catch (error: any) {
                uni.hideLoading();
                taskErrorMsg.value = error;
                uni.$u.toast(error);
            }
        } else {
            await executeCreateTask();
        }
    };

    const handleTaskMsgPopConfirm = async () => {
        await executeCreateTask();
    };

    const handleCreateTaskSuccess = () => {
        uni.$u.route({ url: "/ai_modules/device/pages/index/index", type: "reLaunch" });
        showCreateTaskSuccessDialog.value = false;
    };

    return {
        taskErrorMsg,
        showCreateTaskSuccessDialog,
        showTaskMsgPop,
        taskMsgPopContent,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess,
    };
}
