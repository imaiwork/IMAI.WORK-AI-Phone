// hooks/useTimeConfig.ts — Step4 时间配置 & 创建任务
import { createTask } from "@/api/sph";
import { checkTaskPublishTime } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { TIME_INTERVAL } from "./types";
import type { SphFormData } from "./types";

export function useTimeConfig(formData: SphFormData) {
    const currentFrequency = ref(0);
    const currentWechatFrequency = ref(0);
    const isExpandDate = ref(false);
    const isWechatExpandDate = ref(false);
    const customDateType = ref<1 | 2>(1);
    const taskErrorMsg = ref("");
    const showTaskMsgPop = ref(false);
    const taskMsgPopContent = ref<string[]>([]);
    const showCreateTaskSuccessDialog = ref(false);

    const formatDate = (date: string) => uni.$u.timeFormat(new Date(date), "mm月dd日");

    // ── 频率 ──────────────────────────────────────────────────
    const handleFrequency = (item: number, index: number) => {
        currentFrequency.value = index;
        formData.task_frep = item;
    };
    const handleWechatFrequency = (item: number, index: number) => {
        currentWechatFrequency.value = index;
        formData.wechat_task_frep = item;
    };

    // ── 自定义日期 ─────────────────────────────────────────────
    const handleCustomDate = (type: 1 | 2) => {
        customDateType.value = type;
        const date =
            type === 1
                ? formData.custom_date.length
                    ? JSON.stringify(formData.custom_date)
                    : null
                : formData.wechat_custom_date.length
                ? JSON.stringify(formData.wechat_custom_date)
                : null;
        uni.$u.route({ url: "/ai_modules/device/pages/custom_date/custom_date", params: { date } });
    };

    /** 由页面层 EventBus 回调后调用 */
    const applyCustomDate = (data: string[]) => {
        if (data.length === 0) {
            if (customDateType.value === 1) {
                currentFrequency.value = 0;
                formData.custom_date = [];
            } else {
                currentWechatFrequency.value = 0;
                formData.wechat_custom_date = [];
            }
            return;
        }
        if (customDateType.value === 1) {
            currentFrequency.value = 5;
            formData.custom_date = data;
        } else {
            currentWechatFrequency.value = 5;
            formData.wechat_custom_date = data;
        }
    };

    // ── 执行分钟 ───────────────────────────────────────────────
    const handleExecuteMinuteChange = (delta: number) => {
        const next = Number(formData.minutes) + delta;
        if (next >= 1) formData.minutes = next;
    };

    // ── 时间段（获客任务） ──────────────────────────────────────
    const handleStartTimeChange = (e: any) => {
        const { value } = e.detail;
        const end = new Date(`2000/01/01 ${value}`);
        formData.time_config[0] = value;
        end.setMinutes(end.getMinutes() + TIME_INTERVAL);
        formData.time_config[1] = uni.$u.timeFormat(end, "hh:MM");
    };
    const handleEndTimeChange = (e: any) => {
        const { value } = e.detail;
        if (value <= formData.time_config[0]) {
            uni.$u.toast("结束时间不能小于开始时间");
            return;
        }
        const diff =
            new Date(`2000/01/01 ${value}`).getTime() - new Date(`2000/01/01 ${formData.time_config[0]}`).getTime();
        if (diff < TIME_INTERVAL * 60 * 1000) {
            uni.$u.toast(`结束时间不能小于开始时间${TIME_INTERVAL}分钟`);
            return;
        }
        formData.time_config[1] = value;
    };
    const handleEndTimeClick = () => {
        if (!formData.time_config[0]) uni.$u.toast("请先选择开始时间");
    };

    // ── 时间段（加微任务） ──────────────────────────────────────
    const handleWechatStartTimeChange = (e: any) => {
        const { value } = e.detail;
        const end = new Date(`2000/01/01 ${value}`);
        formData.wechat_time_config[0] = value;
        end.setMinutes(end.getMinutes() + TIME_INTERVAL);
        formData.wechat_time_config[1] = uni.$u.timeFormat(end, "hh:MM");
    };
    const handleWechatEndTimeChange = (e: any) => {
        const { value } = e.detail;
        if (value <= formData.wechat_time_config[0]) {
            uni.$u.toast("结束时间不能小于开始时间");
            return;
        }
        const diff =
            new Date(`2000/01/01 ${value}`).getTime() -
            new Date(`2000/01/01 ${formData.wechat_time_config[0]}`).getTime();
        if (diff < TIME_INTERVAL * 60 * 1000) {
            uni.$u.toast(`结束时间不能小于开始时间${TIME_INTERVAL}分钟`);
            return;
        }
        formData.wechat_time_config[1] = value;
    };
    const handleWechatEndTimeClick = () => {
        if (!formData.wechat_time_config[0]) uni.$u.toast("请先选择每日加微执行开始时间");
    };

    // ── 创建任务 ───────────────────────────────────────────────
    const executeCreateTask = async () => {
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createTask({
                ...formData,
                time_config:
                    formData.task_exec_type === 1 ? "" : `${formData.time_config[0]}-${formData.time_config[1]}`,
                wechat_time_config:
                    formData.wechat_time_type === 1
                        ? `${formData.wechat_time_config[0]}-${formData.wechat_time_config[1]}`
                        : "",
                type: [AppTypeEnum.SPH],
            });
            uni.hideLoading();
            showCreateTaskSuccessDialog.value = true;
        } catch (error: any) {
            uni.hideLoading();
            taskErrorMsg.value = error;
            uni.$u.toast(error);
        }
    };

    const handleCreateTask = async () => {
        if (!formData.name) return uni.$u.toast("请输入任务名称");
        if (!formData.device_codes.length) return uni.$u.toast("请选择设备");
        if (formData.task_exec_type === 0 && (!formData.time_config[0] || !formData.time_config[1]))
            return uni.$u.toast("请设置每日执行时间");
        if (formData.task_exec_type === 1) {
            if (formData.minutes < 1) return uni.$u.toast("执行时间不能小于1分钟");
            if (formData.minutes > 9999) return uni.$u.toast("执行时间不能超过9999分钟");
            uni.showLoading({ title: "检测冲突中...", mask: true });
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    device_codes: formData.device_codes,
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
                uni.$u.toast(error);
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
        uni.$u.route({ url: "/ai_modules/sph/pages/index/index", type: "reLaunch" });
    };

    return {
        currentFrequency,
        currentWechatFrequency,
        isExpandDate,
        isWechatExpandDate,
        taskErrorMsg,
        showTaskMsgPop,
        taskMsgPopContent,
        showCreateTaskSuccessDialog,
        formatDate,
        applyCustomDate,
        handleFrequency,
        handleWechatFrequency,
        handleCustomDate,
        handleExecuteMinuteChange,
        handleStartTimeChange,
        handleEndTimeChange,
        handleEndTimeClick,
        handleWechatStartTimeChange,
        handleWechatEndTimeChange,
        handleWechatEndTimeClick,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess,
    };
}
