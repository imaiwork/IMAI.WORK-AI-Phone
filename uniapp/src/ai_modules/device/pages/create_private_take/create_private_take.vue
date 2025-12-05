<template>
    <view class="p-4 pb-[120rpx]">
        <bast-setting-v2
            v-model="formData"
            :show-device="false"
            :show-accounts="true"
            :current-frequency="currentFrequency"
            :platform-types="[AppTypeEnum.XHS]"
            @change-frequency="currentFrequency = $event" />
        <view class="mt-[50rpx]" v-if="taskErrorMsg">
            <view class="font-bold">任务冲突：</view>
            <view class="text-font-bold text-[#ff2442] text-xs mt-[20rpx]">
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
</template>

<script setup lang="ts">
import { addPrivateChatTask } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import BastSettingV2 from "@/ai_modules/device/components/bast-setting-v2/bast-setting-v2.vue";

const formData = reactive<{
    name: string;
    accounts: string[];
    task_frep: number;
    time_config: string[];
    custom_date: string[];
}>({
    name: `小红书私信接管任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    accounts: [],
    task_frep: 1,
    time_config: ["09:00", "09:30"],
    custom_date: [],
});

// 当前任务频率
const currentFrequency = ref(0);
const taskErrorMsg = ref("");
const showCreateTaskSuccessDialog = ref(false);

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
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

    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await addPrivateChatTask({
            task_name: formData.name,
            accounts: formData.accounts,
            task_frep: formData.task_frep,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            custom_date: formData.custom_date,
        });
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
};
onLoad(() => {
    uni.$on("confirm", (e: any) => {
        const { type, data } = e;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) return;
            formData.accounts = data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }));
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) return;
            formData.custom_date = data;
            currentFrequency.value = 5;
        }
    });
});

onUnload(() => {
    uni.$off("confirm");
});
</script>
