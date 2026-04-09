<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            title="个微接管"
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
            <scroll-view scroll-y v-show="step === 1" class="h-full">
                <view class="px-4 pb-[200rpx] space-y-4">
                    <view class="bg-white rounded-[20rpx] px-[40rpx] py-[30rpx]" v-if="false">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[38rpx]">
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">互动动作</view>
                                <u-switch
                                    v-model="formData.interaction_action_switch"
                                    :active-value="1"
                                    :inactive-value="0"
                                    :size="36" />
                            </view>
                        </view>
                        <view class="mt-[38rpx]">
                            <view class="text-[30rpx] font-medium mb-3">新好友打招呼设置</view>
                            <u-radio-group v-model="formData.interaction_action" class="w-full">
                                <view class="flex flex-wrap justify-between w-full">
                                    <u-radio
                                        v-for="item in [
                                            { value: 0, label: '不打招呼' },
                                            { value: 1, label: '对方先打招呼后，不再回复' },
                                            { value: 2, label: '任何情况都固定打招呼' },
                                        ]"
                                        label-size="26"
                                        :size="28"
                                        :key="item.value"
                                        :name="item.value"
                                        >{{ item.label }}</u-radio
                                    >
                                </view>
                            </u-radio-group>
                            <view v-if="formData.interaction_action == 2">
                                <view class="mt-[26rpx]">
                                    <view class="font-medium text-primary">打招呼内容：</view>
                                    <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] py-1 mt-[16rpx]">
                                        <u-input
                                            v-model="formData.interaction_content"
                                            placeholder="请输入固定打招呼内容"
                                            maxlength="200"
                                            type="textarea" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="bg-white rounded-[20rpx] px-[40rpx] py-[30rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[38rpx]">
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">分段回复</view>
                                <u-switch
                                    v-model="formData.stage_reply_switch"
                                    :active-value="1"
                                    :inactive-value="0"
                                    :size="36" />
                            </view>
                        </view>
                        <view class="mt-[38rpx] border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[38rpx]">
                            <view class="text-[30rpx] font-medium mb-3">多条消息回复设置</view>
                            <u-radio-group v-model="formData.multi_message_type" class="w-full">
                                <view class="flex flex-wrap justify-between w-full">
                                    <u-radio
                                        v-for="item in [
                                            { value: 0, label: '逐条回复' },
                                            { value: 1, label: '合并回复' },
                                            { value: 2, label: '只回复最后一条' },
                                        ]"
                                        label-size="26"
                                        :size="28"
                                        :key="item.value"
                                        :name="item.value"
                                        >{{ item.label }}</u-radio
                                    >
                                </view>
                            </u-radio-group>
                            <view v-if="formData.interaction_action == 2">
                                <view class="mt-[26rpx]">
                                    <view class="font-medium text-primary">打招呼内容：</view>
                                    <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] py-1 mt-[16rpx]">
                                        <u-input
                                            v-model="formData.interaction_content"
                                            placeholder="请输入固定打招呼内容"
                                            maxlength="200"
                                            type="textarea" />
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[38rpx] border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[38rpx]">
                            <view class="text-[30rpx] font-medium">图片回复设置</view>
                            <u-radio-group v-model="formData.image_reply_type" class="w-full mt-3">
                                <view class="flex flex-wrap justify-between w-full">
                                    <u-radio
                                        v-for="item in [
                                            { value: 1, label: '固定回复' },
                                            { value: 2, label: 'AI识别回复' },
                                            { value: 3, label: '不回复' },
                                        ]"
                                        label-size="26"
                                        :size="28"
                                        :key="item.value"
                                        :name="item.value"
                                        >{{ item.label }}</u-radio
                                    >
                                </view>
                            </u-radio-group>
                            <view v-if="formData.image_reply_type == 1">
                                <view class="mt-[26rpx]">
                                    <view class="font-medium text-primary">固定回复内容：</view>
                                    <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] py-1 mt-[16rpx]">
                                        <u-input
                                            v-model="formData.image_reply_content"
                                            placeholder="请输入固定回复内容"
                                            maxlength="200"
                                            type="textarea" />
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[38rpx] border-[0] border-b-[1rpx] border-solid border-[#E5E5E5] pb-[38rpx]">
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">敏感词停止回复</view>
                                <u-switch
                                    v-model="formData.sensitive_word_switch"
                                    :active-value="1"
                                    :inactive-value="0"
                                    :size="36" />
                            </view>
                            <view class="flex flex-wrap gap-2 mt-[36rpx]" v-if="formData.sensitive_word_switch == 1">
                                <view
                                    v-for="(item, index) in formData.sensitive_word"
                                    class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2"
                                    @click="handleSensitiveWordEdit(index)">
                                    {{ item }}
                                    <view
                                        class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleSensitiveWordDelete(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="border border-solid border-[#0065FB] rounded-[20rpx] px-[28rpx] h-[60rpx] flex items-center justify-center"
                                    @click="handleSensitiveWordEdit(-1)">
                                    <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                    <text class="text-primary font-medium ml-1">添加</text>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[38rpx]" v-if="false">
                            <view class="text-[30rpx] font-medium mb-3">语音消息回复设置</view>
                            <u-radio-group v-model="formData.voice_reply_type" class="w-full">
                                <view class="flex flex-wrap justify-between w-full">
                                    <u-radio
                                        v-for="item in [
                                            { value: 1, label: '不回复' },
                                            { value: 2, label: '转文字后回复' },
                                            { value: 3, label: '固定回复' },
                                        ]"
                                        label-size="26"
                                        :size="28"
                                        :key="item.value"
                                        :name="item.value"
                                        >{{ item.label }}</u-radio
                                    >
                                </view>
                            </u-radio-group>
                            <view v-if="formData.voice_reply_type == 3">
                                <view class="mt-[26rpx]">
                                    <view class="font-medium text-primary">固定回复内容：</view>
                                    <view class="bg-[#F3F3F3] rounded-[16rpx] px-[26rpx] py-1 mt-[16rpx]">
                                        <u-input
                                            v-model="formData.voice_reply_content"
                                            placeholder="请输入固定回复内容"
                                            maxlength="200"
                                            type="textarea" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <view v-show="step === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[100rpx]">
                        <base-setting
                            v-model="formData"
                            :show-device="false"
                            :show-accounts="true"
                            :current-frequency="currentFrequency"
                            :platform-types="[AppTypeEnum.WECHAT]"
                            :multiple="1"
                            is-wechat-private
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
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="敏感词设置"
        @confirm="handleKeywordsConfirm" />
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
import { createWechatPrivateTask, checkTaskPublishTime } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";

const { on } = useEventBusManager();

const steps = ref([
    { step: 1, title: "回复设置" },
    { step: 2, title: "设定时间" },
]);
const step = ref(1);

const formData = reactive<{
    name: string;
    interaction_action_switch: 0 | 1;
    interaction_action: 0 | 1 | 2;
    interaction_content: string;
    stage_reply_switch: 0 | 1;
    multi_message_type: 0 | 1 | 2;
    image_reply_type: 1 | 2 | 3;
    image_reply_content: string;
    sensitive_word_switch: 0 | 1;
    sensitive_word: string[];
    voice_reply_type: 1 | 2 | 3;
    voice_reply_content: string;
    accounts: any[];
    task_frep: number;
    time_type: 0 | 1;
    time_config: string[];
    custom_date: string[];
    task_exec_type: number;
    minutes: number;
    task_ids: string[];
}>({
    name: `个微接管任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`,
    interaction_action_switch: 1,
    interaction_action: 0,
    interaction_content: "",
    stage_reply_switch: 1,
    multi_message_type: 0,
    image_reply_type: 1,
    image_reply_content: "",
    sensitive_word_switch: 1,
    sensitive_word: [],
    voice_reply_type: 1,
    voice_reply_content: "",
    accounts: [],
    task_frep: 1,
    time_type: 0,
    time_config: ["09:00", "09:30"],
    custom_date: [],
    task_exec_type: 1,
    minutes: 30,
    task_ids: [],
});

const editSensitiveWordIndex = ref(-1);
const showKeywordsEdit = ref(false);
const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();
const currentFrequency = ref(0);
const taskErrorMsg = ref<string>("");
const showCreateTaskSuccessDialog = ref(false);
const showTaskMsgPop = ref(false);
const taskMsgPopContent = ref<string[]>([]);

const canNext = computed(() => canStepProceed(step.value));

const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            const flag =
                (formData.interaction_action == 2 ? formData.interaction_content : true) &&
                (formData.image_reply_type == 1 ? formData.image_reply_content : true) &&
                (formData.voice_reply_type == 3 ? formData.voice_reply_content : true) &&
                (formData.sensitive_word_switch == 1 ? formData.sensitive_word.length > 0 : true);
            return flag;
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
                    if (formData.interaction_action == 2 && !formData.interaction_content) {
                        return "请输入固定打招呼内容";
                    }
                    if (formData.image_reply_type == 1 && !formData.image_reply_content) {
                        return "请输入图片固定回复内容";
                    }
                    if (formData.sensitive_word_switch == 1 && formData.sensitive_word.length == 0) {
                        return "请输入敏感词";
                    }
                    if (formData.voice_reply_type == 3 && !formData.voice_reply_content) {
                        return "请输入语音固定回复内容";
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

const handleSensitiveWordDelete = (index: number) => {
    formData.sensitive_word.splice(index, 1);
};

const handleSensitiveWordEdit = (index: number) => {
    editSensitiveWordIndex.value = index;
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.sensitive_word[index]);
};

const handleKeywordsConfirm = (data: string) => {
    if (editSensitiveWordIndex.value >= 0) {
        formData.sensitive_word[editSensitiveWordIndex.value] = data;
    } else {
        formData.sensitive_word.push(data);
    }
    showKeywordsEdit.value = false;
    editSensitiveWordIndex.value = -1;
};
const executeCreateTask = async () => {
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createWechatPrivateTask({
            task_name: formData.name,
            accounts: formData.accounts,
            task_frep: formData.task_frep,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            custom_date: formData.custom_date,
            is_manual_agree: formData.interaction_action_switch,
            greet_strategy: formData.interaction_action,
            greet_content: formData.interaction_content,
            paragraph_enable: formData.stage_reply_switch,
            multiple_type: formData.multi_message_type,
            voice_reply_type: formData.voice_reply_type,
            voice_reply: formData.voice_reply_content,
            image_reply_type: formData.image_reply_type,
            image_reply: formData.image_reply_content,
            stop_enable: formData.sensitive_word_switch,
            stop_keywords: formData.sensitive_word,
            is_free_time: formData.time_type,
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
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    }
};

const handleCreateTask = async () => {
    // --- 基础校验逻辑 ---
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

<style scoped></style>
