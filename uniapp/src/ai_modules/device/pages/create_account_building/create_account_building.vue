<template>
    <view class="p-4 pb-[120rpx]">
        <base-setting
            v-model="formData"
            :show-device="false"
            :show-accounts="true"
            :current-frequency="currentFrequency"
            :platform-types="[AppTypeEnum.XHS, AppTypeEnum.DOUYIN, AppTypeEnum.KUAISHOU]"
            @change-frequency="currentFrequency = $event" />
        <view class="mt-[50rpx]" v-if="taskErrorMsg">
            <view class="font-medium">任务冲突：</view>
            <view class="text-font-medium text-[#ff2442] text-xs mt-[20rpx]">
                {{ taskErrorMsg }}
            </view>
        </view>
        <view class="fixed bottom-0 left-0 w-full px-4 pt-2 pb-5">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '20rpx', fontWeight: 'bold' }"
                @click="handleSubmit"
                >创建任务</u-button
            >
        </view>
    </view>
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { addGrowthAccountTask, checkTaskPublishTime } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";

const { on } = useEventBusManager();

const formData = reactive<{
    name: string;
    accounts: string[];
    task_frep: number;
    time_config: string[];
    custom_date: string[];
    task_exec_type: number;
    minutes: number;
    task_ids: string[];
}>({
    name: `自动养号任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    accounts: [],
    task_frep: 1,
    time_config: [
        uni.$u.timeFormat(new Date(), "hh:MM"),
        uni.$u.timeFormat(new Date(new Date().getTime() + 30 * 60 * 1000), "hh:MM"),
    ],
    custom_date: [],
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
});

// 当前任务频率
const currentFrequency = ref(0);
const taskErrorMsg = ref("");
const showCreateTaskSuccessDialog = ref(false);
const showTaskMsgPop = ref(false);
const taskMsgPopContent = ref<string[]>([]);

const executeCreateTask = async () => {
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await addGrowthAccountTask({
            task_name: formData.name,
            accounts: formData.accounts,
            task_frep: formData.task_frep,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            custom_date: formData.custom_date,
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
                        uni.$u.route({
                            url: "/ai_modules/device/pages/index/index",
                        });
                    }
                },
            });
        } else {
            taskErrorMsg.value = error;
            uni.showToast({
                title: error || "创建失败",
                icon: "none",
                duration: 3000,
            });
        }
    }
};

const handleSubmit = async () => {
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    if (!formData.accounts.length) {
        uni.$u.toast("请选择发布账号");
        return;
    }
    if (currentFrequency.value === 5 && !formData.custom_date.length) {
        uni.$u.toast("请选择任务日期");
        return;
    }
    if (!formData.time_config[0] || !formData.time_config[1]) {
        uni.$u.toast("请选择任务时间");
        return;
    }

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
    uni.$u.route({
        url: "/ai_modules/device/pages/index/index",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
};

onLoad(() => {
    on("confirm", (e: any) => {
        const { type, data } = e;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) return;
            formData.accounts = data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }));
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) {
                currentFrequency.value = 0;
                formData.custom_date = [];
                return;
            }
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
    });
});
</script>
