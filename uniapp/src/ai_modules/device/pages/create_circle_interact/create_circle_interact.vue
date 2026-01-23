<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            title="朋友圈互动"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-2 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="common-step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <scroll-view scroll-y v-if="step === 1" class="h-full">
                <view class="px-4 pb-[100rpx]">
                    <view class="bg-white rounded-[20rpx] px-[40rpx] py-[30rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[26rpx]">
                            <view class="text-[30rpx] font-bold">互动动作</view>
                            <view class="mt-[26rpx]">
                                <u-radio-group v-model="formData.interaction_action" class="w-full">
                                    <view class="flex justify-between w-full">
                                        <u-radio
                                            v-for="item in [
                                                { value: 1, label: '点赞' },
                                                { value: 2, label: '评论' },
                                                { value: 3, label: '评论+点赞' },
                                            ]"
                                            label-size="26"
                                            :size="28"
                                            :key="item.value"
                                            :name="item.value"
                                            >{{ item.label }}</u-radio
                                        >
                                    </view>
                                </u-radio-group>
                            </view>
                        </view>
                        <view class="mt-[32rpx]">
                            <view class="text-[30rpx] font-bold">每个好友当前任务互动数量</view>
                            <view class="mt-[26rpx] flex items-center gap-x-2">
                                <view
                                    class="h-[80rpx] w-[220rpx] rounded-[20rpx] border border-solid border-[#E5E5E5] px-[20rpx]">
                                    <u-input
                                        v-model="formData.interaction_count"
                                        type="digit"
                                        placeholder="请输入"></u-input>
                                </view>
                                条
                            </view>
                        </view>
                        <view class="mt-[32rpx]">
                            <view class="text-[30rpx] font-bold">每次互动时间间隔</view>
                            <view class="mt-[26rpx] flex items-center gap-x-2">
                                <view
                                    class="h-[80rpx] w-[220rpx] rounded-[20rpx] border border-solid border-[#E5E5E5] px-[20rpx]">
                                    <u-input
                                        v-model="formData.interaction_time"
                                        type="digit"
                                        placeholder="请输入"></u-input>
                                </view>
                                分钟
                            </view>
                        </view>
                        <view class="mt-[32rpx] border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[26rpx]">
                            <view class="text-[30rpx] font-bold">互动动作</view>
                            <view class="mt-[26rpx]">
                                <u-radio-group v-model="formData.interaction_time_type" class="w-full">
                                    <view class="flex justify-between w-full">
                                        <u-radio
                                            v-for="item in [
                                                { value: 1, label: '仅当天' },
                                                { value: 2, label: '3天内' },
                                                { value: 3, label: '7天内' },
                                            ]"
                                            :size="28"
                                            label-size="26"
                                            :key="item.value"
                                            :name="item.value"
                                            >{{ item.label }}</u-radio
                                        >
                                    </view>
                                </u-radio-group>
                            </view>
                        </view>
                        <view
                            class="mt-[32rpx] border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[26rpx] flex items-center justify-between"
                            @click="showChooseRobot = true">
                            <view class="text-[30rpx] font-bold">评论智能体</view>
                            <view class="flex items-center gap-x-2">
                                <text :class="!formData.robot_id ? 'text-[#00000099]' : 'text-[#0065FB] font-bold'">{{
                                    formData.robot_id ? formData.robot_name : "请选择"
                                }}</text>
                                <u-icon name="arrow-right" color="#B2B2B2" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="mt-[32rpx]">
                            <view class="text-[30rpx] font-bold">当内容为"图片/视频"类型时</view>
                            <view class="mt-[26rpx]">
                                <u-radio-group v-model="formData.comment_type" class="w-full">
                                    <view class="flex gap-5 w-full">
                                        <u-radio
                                            v-for="item in [
                                                // { value: 0, label: 'AI识别并评论' },
                                                { value: 1, label: '不评论' },
                                                { value: 2, label: '固定评论' },
                                            ]"
                                            :size="28"
                                            label-size="26"
                                            :key="item.value"
                                            :name="item.value"
                                            >{{ item.label }}</u-radio
                                        >
                                    </view>
                                </u-radio-group>
                            </view>
                            <view class="mt-[36rpx]" v-if="formData.comment_type === 2">
                                <view class="font-bold text-primary">固定评论内容：</view>
                                <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] py-1 mt-[16rpx]">
                                    <u-input
                                        v-model="formData.comment_content"
                                        placeholder="请输入固定评论内容"
                                        maxlength="500"
                                        height="150"
                                        type="textarea" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <view v-if="step === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[100rpx]">
                        <base-setting
                            v-model="formData"
                            :show-device="false"
                            :show-accounts="true"
                            :current-frequency="currentFrequency"
                            :platform-types="[AppTypeEnum.WECHAT]"
                            :multiple="0"
                            @change-frequency="currentFrequency = $event" />
                        <view v-if="taskErrorMsg" class="mt-5">
                            <view>任务冲突</view>
                            <view class="text-[#FF2442] mt-[20rpx] text-xs">
                                {{ taskErrorMsg }}
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center px-4 h-[140rpx]" :class="[step == 1 ? 'justify-end' : 'justify-between']">
                <template v-if="step != steps.length">
                    <view
                        v-if="step != 1"
                        class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                        @click="handleStep(step, 'prev')">
                        上一步
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>
    <popup-bottom
        v-model="showChooseRobot"
        title="选择评论智能体"
        custom-class="bg-[#F3F3F3]"
        :is-disabled-touch="true">
        <template #content>
            <view class="h-full">
                <choose-robot :agent-id="formData.robot_id" @confirm="handleChooseRobotConfirm" />
            </view>
        </template>
    </popup-bottom>
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
import { createCircleLikeTask } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ChooseRobot from "@/ai_modules/device/components/choose-robot/choose-robot.vue";

const { on } = useEventBusManager();

const step = ref(1);
const steps = ref([
    { step: 1, title: "调设置" },
    { step: 2, title: "设定时间" },
]);

const formData = reactive<{
    name: string;
    interaction_action: 1 | 2 | 3;
    interaction_count: number;
    interaction_time: number;
    interaction_time_type: 1 | 2 | 3;
    comment_type: 1 | 2 | 3;
    comment_content: string;
    accounts: any[];
    task_frep: number;
    time_config: string[];
    robot_id: number | string;
    custom_date: string[];
    robot_name: string;
}>({
    name: `朋友圈互动任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    interaction_action: 1,
    interaction_count: 1,
    interaction_time: 10,
    interaction_time_type: 1,
    comment_type: 1,
    comment_content: "",
    accounts: [],
    task_frep: 1,
    time_config: ["09:00", "09:30"],
    custom_date: [],
    robot_id: "",
    robot_name: "",
});
const showChooseRobot = ref(false);
const currentFrequency = ref(0);
const taskErrorMsg = ref<string>("");
const showCreateTaskSuccessDialog = ref(false);

const canNext = computed(() => canStepProceed(step.value));

const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.robot_id && (formData.comment_type == 2 ? formData.comment_content : true);
        case 2:
            return true;

        default:
            return false;
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    if (type === "prev") {
        step.value--;
        return;
    }
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: () => string } = {
                1: () => {
                    if (!formData.robot_id) {
                        return "请设置评论智能体";
                    }
                    if (formData.comment_type == 2 && !formData.comment_content) {
                        return "请输入固定评论内容";
                    }
                    return "";
                },
            };
            uni.$u.toast(messages[step.value]?.() || "请完成当前步骤");
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

const handleChooseRobotConfirm = (agent: any) => {
    formData.robot_id = agent.id;
    formData.robot_name = agent.name;
    showChooseRobot.value = false;
};

const handleCreateTask = async () => {
    if (formData.interaction_time <= 0) {
        uni.$u.toast("每次互动时间间隔不能小于1分钟");
        return;
    }
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

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
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

<style scoped></style>
