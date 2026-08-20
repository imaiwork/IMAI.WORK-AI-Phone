// ============================================================
// hooks/useCreateTask.ts  —  创建任务 & 冲突检测
// ============================================================
import WechatOA from "@/utils/wechat";
import { createManualAddWechat, checkTaskPublishTime } from "@/api/device";
import type { AddFriendFormData } from "./types";

interface UseCreateTaskOptions {
    formData: AddFriendFormData;
    taskList: Ref<any[]>;
    currentFrequency: Ref<number>;
    timeIntervalIndex: Ref<number>;
    timeInterval: Ref<number | undefined>;
}

export function useCreateTask(options: UseCreateTaskOptions) {
    const { formData, taskList, currentFrequency, timeIntervalIndex, timeInterval } = options;

    const taskErrorMsg = ref("");
    const showCreateTaskSuccessDialog = ref(false);
    const showTaskMsgPop = ref(false);
    const taskMsgPopContent = ref<string[]>([]);

    const executeCreateTask = async () => {
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createManualAddWechat({
                ...formData,
                crawling_task_ids: formData.source === 2 ? taskList.value.map((item) => item.id) : [],
                fileurl: formData.source === 1 ? taskList.value[0].url : "",
                time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
                add_interval_time: timeIntervalIndex.value === 4 ? timeInterval.value : formData.add_interval_time,
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
        if (!formData.name) return uni.$u.toast("请输入任务名称");
        if (formData.device_codes.length === 0) return uni.$u.toast("请选择设备");
        if (currentFrequency.value === 5 && !formData.custom_date.length) return uni.$u.toast("请选择任务日期");
        if (!formData.time_config[0] || !formData.time_config[1]) return uni.$u.toast("请选择时间");
        if (formData.task_exec_type === 1) {
            if (formData.minutes < 1) return uni.$u.toast("执行时间不能小于1分钟");
            if (formData.minutes > 9999) return uni.$u.toast("执行时间不能超过9999分钟");
        }

        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: "检测冲突中...", mask: true });
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    device_codes: formData.device_codes,
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
        showCreateTaskSuccessDialog.value = false;
        uni.$u.route({ url: "/ai_modules/device/pages/index/index", type: "reLaunch" });
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
