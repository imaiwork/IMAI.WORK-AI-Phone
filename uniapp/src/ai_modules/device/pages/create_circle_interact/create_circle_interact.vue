<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="朋友圈互动"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <scroll-view scroll-y v-show="currentStep === 1" class="h-full">
                <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动动作</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="item in [
                                        { value: 1, label: '点赞' },
                                        { value: 2, label: '评论' },
                                        { value: 3, label: '评论+点赞' },
                                    ]"
                                    :key="item.value"
                                    class="h-[68rpx] px-[32rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.interaction_action === item.value
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="formData.interaction_action = item.value as 1 | 2 | 3">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.interaction_action === item.value
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{ item.label }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动频率</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx] flex flex-col gap-[20rpx]">
                            <view>
                                <text class="text-[22rpx] text-[#9CA3AF] block mb-[12rpx]"
                                    >每个好友当前任务互动数量</text
                                >
                                <view class="flex items-center gap-[16rpx]">
                                    <view
                                        class="flex items-center bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0] w-[220rpx]">
                                        <u-input
                                            v-model="formData.interaction_count"
                                            type="digit"
                                            placeholder="请输入"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                    </view>
                                    <text class="text-[#4B5563] font-semibold">条</text>
                                </view>
                            </view>

                            <view class="h-[1rpx] bg-[#F0F2F5]" />

                            <view>
                                <text class="text-[22rpx] text-[#9CA3AF] block mb-[12rpx]">每次互动时间间隔</text>
                                <view class="flex items-center gap-[16rpx]">
                                    <view
                                        class="flex items-center bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0] w-[220rpx]">
                                        <u-input
                                            v-model="formData.interaction_time"
                                            type="digit"
                                            placeholder="请输入"
                                            placeholder-style="color:#C0C4CC;font-size:26rpx;" />
                                    </view>
                                    <text class="text-[#4B5563] font-semibold">分钟</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">互动时间范围</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="item in [
                                        { value: 1, label: '仅当天' },
                                        { value: 2, label: '3天内' },
                                        { value: 3, label: '7天内' },
                                    ]"
                                    :key="item.value"
                                    class="h-[68rpx] px-[32rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.interaction_time_type === item.value
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="formData.interaction_time_type = item.value as 1 | 2 | 3">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.interaction_time_type === item.value
                                                ? 'text-primary'
                                                : 'text-[#9CA3AF]'
                                        ">
                                        {{ item.label }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                        @click="showChooseAgent = true">
                        <view class="flex items-center justify-between px-[28rpx] h-[96rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">评论智能体</text>
                            </view>
                            <view class="flex items-center gap-[8rpx]">
                                <text
                                    class=""
                                    :class="formData.robot_id ? 'text-primary font-semibold' : 'text-[#C0C4CC]'">
                                    {{ formData.robot_id ? formData.robot_name : "请选择" }}
                                </text>
                                <u-icon name="arrow-right" color="#C0C4CC" size="20" />
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">当内容为"图片/视频"类型时</text>
                        </view>
                        <view class="px-[28rpx] py-[20rpx]">
                            <view class="flex flex-wrap gap-[12rpx]">
                                <view
                                    v-for="item in [
                                        { value: 1, label: '不评论' },
                                        { value: 2, label: '固定评论' },
                                    ]"
                                    :key="item.value"
                                    class="h-[68rpx] px-[32rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                    :class="
                                        formData.comment_type === item.value
                                            ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                            : 'bg-[#F0F2F5]'
                                    "
                                    @click="formData.comment_type = item.value as 1 | 2">
                                    <text
                                        class="font-bold"
                                        :class="
                                            formData.comment_type === item.value ? 'text-primary' : 'text-[#9CA3AF]'
                                        ">
                                        {{ item.label }}
                                    </text>
                                </view>
                            </view>

                            <view v-if="formData.comment_type === 2" class="mt-[20rpx]">
                                <text class="text-xs text-primary font-semibold block mb-[12rpx]">固定评论内容</text>
                                <view
                                    class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                                    <u-input
                                        v-model="formData.comment_content"
                                        placeholder="请输入固定评论内容"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        maxlength="500"
                                        height="150"
                                        type="textarea" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view v-show="currentStep === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <base-setting
                            v-model="formData"
                            :show-device="false"
                            :show-accounts="true"
                            :current-frequency="currentFrequency"
                            :platform-types="[AppTypeEnum.WECHAT]"
                            :multiple="0"
                            @change-frequency="currentFrequency = $event" />

                        <view
                            v-if="taskErrorMsg"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[80rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-[#EF4444] rounded-full" />
                                <u-icon name="warning-fill" size="24" color="#EF4444" />
                                <text class="text-[28rpx] font-bold text-[#EF4444]">任务冲突</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <text class="text-[#EF4444] leading-relaxed">{{ taskErrorMsg }}</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]"
            :class="currentStep === 1 ? 'justify-end' : 'justify-between'">
            <view
                v-if="currentStep != 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(currentStep, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="currentStep != STEPS.length">
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : ''"
                    :style="
                        canNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleStep(currentStep, 'next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
        </view>
    </view>

    <choose-agent ref="chooseAgentRef" v-model="showChooseAgent" @confirm="handleChooseAgentConfirm" />
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
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";
import ChooseAgent from "@/ai_modules/device/components/choose-agent/choose-agent.vue";

import { STEPS, createDefaultFormData } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useAgentSetting } from "./hooks/useAgentSetting";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();

// ── 共享表单数据 ──────────────────────────────────────────────────
const formData = reactive(createDefaultFormData());
const currentFrequency = ref(0);

// ── Hooks ─────────────────────────────────────────────────────────
const { step: currentStep, canNext, handleStep } = useStep(formData);
const { showChooseAgent, chooseAgentRef, handleChooseAgentConfirm } = useAgentSetting(formData);
const {
    taskErrorMsg,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData, currentFrequency);

// ── onLoad：EventBus 分发 ─────────────────────────────────────────
onLoad(() => {
    on("confirm", (e: any) => {
        const { type, data } = e;
        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            console.log(data);
            if (data.length === 0) {
                formData.accounts = [];
                return;
            }
            formData.accounts = data.map((item: any) => ({
                id: item.id,
                account: item.account,
                type: item.type,
            }));
        }
        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (!data.length) {
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
