<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="同城曝光"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="step" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <view v-show="step === 1" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-[24rpx] pb-[120rpx] flex flex-col gap-[16rpx]">
                        <view
                            class="flex items-start gap-[12rpx] rounded-[20rpx] px-[24rpx] py-[20rpx] bg-[#EBF2FF] border border-solid border-[#BFDBFE]">
                            <u-icon name="info-circle" color="#0065fb" size="28" class="flex-shrink-0 mt-[2rpx]" />
                            <text class="text-xs text-primary leading-relaxed">
                                自动高频访问同城用户主页，在对方访客记录中留下足迹，吸引对方回访。
                            </text>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">同城范围</text>
                                </view>
                                <text class="text-[28rpx] font-bold text-primary">{{ formData.radius }} 公里内</text>
                            </view>
                            <view class="px-[28rpx] py-[32rpx]">
                                <slider
                                    :value="formData.radius"
                                    :min="DISTANCE_MIN"
                                    :max="DISTANCE_MAX"
                                    :step="1"
                                    activeColor="#0065fb"
                                    backgroundColor="#E5E9F0"
                                    block-color="#0065fb"
                                    block-size="22"
                                    @change="formData.radius = $event.detail.value" />
                                <view class="flex justify-between mt-[8rpx]">
                                    <text class="text-[22rpx] text-[#9CA3AF]">1公里</text>
                                    <text class="text-[22rpx] text-[#9CA3AF]">50公里</text>
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">访问数量</text>
                            </view>
                            <view class="px-[28rpx] py-[28rpx] flex items-center justify-between">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#F0F2F5] flex items-center justify-center"
                                    @click="formData.visit_num = Math.max(1, formData.visit_num - 10)">
                                    <u-icon name="minus" color="#4B5563" size="28" />
                                </view>
                                <view class="flex items-center gap-[8rpx]">
                                    <u-input
                                        v-model="visitCountStr"
                                        type="digit"
                                        :custom-style="{
                                            textAlign: 'center',
                                            fontWeight: '800',
                                            color: '#0065fb',
                                            fontSize: '48rpx',
                                            width: '160rpx',
                                        }"
                                        placeholder="访问数量"
                                        placeholder-style="color:#C0C4CC;font-size: 24rpx;"
                                        @blur="onVisitCountBlur" />
                                    <text class="text-[#9CA3AF]">个人</text>
                                </view>
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-[20rpx] flex items-center justify-center shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                    @click="formData.visit_num += 10">
                                    <u-icon name="plus" color="#fff" size="28" />
                                </view>
                            </view>
                        </view>

                        <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">访问频率</text>
                            </view>
                            <view class="px-[28rpx] py-[28rpx]">
                                <view class="flex items-center justify-between mb-[16rpx]">
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-[20rpx] bg-[#F0F2F5] flex items-center justify-center"
                                        @click="formData.interval_time = Math.max(1, formData.interval_time - 1)">
                                        <u-icon name="minus" color="#4B5563" size="28" />
                                    </view>
                                    <view class="flex items-center gap-[8rpx]">
                                        <text class="text-[28rpx] text-[#9CA3AF]">间隔</text>
                                        <u-input
                                            v-model="visitIntervalStr"
                                            type="digit"
                                            :custom-style="{
                                                textAlign: 'center',
                                                fontWeight: '800',
                                                color: '#0065fb',
                                                fontSize: '48rpx',
                                                width: '120rpx',
                                            }"
                                            placeholder="访问间隔"
                                            placeholder-style="color:#C0C4CC;font-size: 24rpx;"
                                            @blur="onVisitIntervalBlur" />
                                        <text class="text-[28rpx] text-[#9CA3AF]">秒触达一人</text>
                                    </view>
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-[20rpx] flex items-center justify-center shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                        @click="formData.interval_time++">
                                        <u-icon name="plus" color="#fff" size="28" />
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-center gap-[8rpx] bg-[#FFF8E6] rounded-[12rpx] py-[12rpx]">
                                    <u-icon name="warning" color="#F59E0B" size="22" />
                                    <text class="text-[22rpx] text-[#92400E]"
                                        >建议间隔 5-15 秒，避免操作过快被限制</text
                                    >
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <scroll-view v-show="step === 2" scroll-y class="h-full">
                <view class="px-[24rpx] pb-[120rpx]">
                    <base-setting
                        v-model="formData"
                        :show-device="false"
                        :show-accounts="true"
                        :multiple="0"
                        :current-frequency="currentFrequency"
                        :platform-types="[AppTypeEnum.DOUYIN]"
                        @change-frequency="currentFrequency = $event" />
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]"
            :class="step === 1 ? 'justify-end' : 'justify-between'">
            <view
                v-if="step !== 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center justify-center border border-solid border-[#E5E9F0] bg-[#F7F9FC]"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>

            <template v-if="step !== STEPS.length">
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                    :style="
                        canNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleStep(step, 'next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>

            <template v-else>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
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
import { AppTypeEnum } from "@/enums/appEnums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import { STEPS, DISTANCE_MIN, DISTANCE_MAX, createDefaultFormData } from "./hooks/types";
import { useStepNav } from "./hooks/useStepNav";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();

const formData = reactive(createDefaultFormData());

const { step, canNext, handleStep } = useStepNav(formData);

const visitCountStr = computed({
    get: () => String(formData.visit_num),
    set: (val) => {
        const num = parseInt(val);
        if (!isNaN(num) && num >= 1) formData.visit_num = num;
    },
});

const visitIntervalStr = computed({
    get: () => String(formData.interval_time),
    set: (val) => {
        const num = parseInt(val);
        if (!isNaN(num) && num >= 1) formData.interval_time = num;
    },
});

function onVisitCountBlur() {
    if (!formData.visit_num || formData.visit_num < 1) formData.visit_num = 1;
}

function onVisitIntervalBlur() {
    if (!formData.interval_time || formData.interval_time < 1) formData.interval_time = 1;
}

const {
    currentFrequency,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData);

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (!data?.length) {
                currentFrequency.value = 0;
                formData.custom_date = [];
            } else {
                formData.custom_date = data;
                currentFrequency.value = 5;
            }
            return;
        }

        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            formData.accounts = data?.length
                ? data.map((item: any) => ({ id: item.id, account: item.account, type: item.type }))
                : [];
        }
    });
});
</script>

<style scoped></style>
