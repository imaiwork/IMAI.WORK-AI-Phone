<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="自动加好友"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />
        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <view v-show="currentStep === 1" class="flex flex-col h-full">
                <view class="px-4">
                    <view
                        class="flex items-center justify-center gap-[10rpx] h-[100rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleAddTask">
                        <u-icon name="plus-circle-fill" color="#fff" size="32" />
                        <text class="text-white font-extrabold text-[30rpx]">添加任务</text>
                    </view>
                </view>

                <view class="px-4 mt-[24rpx]">
                    <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                        <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                        <text class="text-[28rpx] font-extrabold text-[#0D1117]">任务列表</text>
                        <view class="flex items-center gap-[4rpx] bg-[#EBF2FF] rounded-full px-[14rpx] py-[6rpx]">
                            <text class="text-[22rpx] font-semibold text-primary">{{ taskList.length }}</text>
                        </view>
                    </view>
                    <view class="flex items-start gap-[8rpx]">
                        <view class="flex-shrink-0 mt-[2rpx]">
                            <u-icon name="info-circle" color="#F59E0B" size="22" />
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                            开启此任务请确保当前网络是常用安全网络，加资账号为常态化老号
                        </text>
                    </view>
                </view>

                <view class="grow min-h-0 mt-[16rpx]">
                    <scroll-view class="h-full" scroll-y v-if="taskList.length">
                        <view class="px-4 pb-[120rpx] flex flex-col gap-[12rpx]">
                            <view class="relative" v-for="(item, index) in taskList" :key="index">
                                <view
                                    class="absolute top-[12rpx] right-[12rpx] w-[44rpx] h-[44rpx] rounded-full flex items-center justify-center bg-[#00000040] z-[22]"
                                    @click="handleDeleteTask(index)">
                                    <u-icon name="close" size="18" color="#ffffff" />
                                </view>
                                <clue-card :data="item" :type="item.file_type" />
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="flex flex-col items-center justify-center mt-[80rpx]">
                        <view
                            class="w-[200rpx] h-[200rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[28rpx]">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-[28rpx] flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="list" color="#fff" size="44" />
                            </view>
                        </view>
                        <text class="text-[28rpx] font-extrabold text-[#0D1117] mb-[10rpx]">您还没有添加任务哦</text>
                        <text class="text-xs text-[#9CA3AF]">点击上方按钮添加线索任务</text>
                    </view>
                </view>
            </view>

            <view v-show="currentStep === 2" class="h-full">
                <scroll-view class="h-full" scroll-y>
                    <view class="px-4 pb-[120rpx] flex flex-col gap-[16rpx]">
                        <view
                            class="bg-white rounded-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">加微设置</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <text class="text-[22rpx] text-[#9CA3AF] block mb-[12rpx]">加微微信</text>
                                    <data-select
                                        v-model="formData.wechat_id"
                                        multiple
                                        :localdata="optionsData.wechatLists" />
                                </view>
                                <view class="h-[1rpx] bg-[#F0F2F5]" />
                                <view>
                                    <text class="text-[22rpx] text-[#9CA3AF] block mb-[12rpx]">加微规则</text>
                                    <data-select
                                        v-model="formData.wechat_reg_type"
                                        :clear="false"
                                        :localdata="[
                                            { text: '全部', value: 0 },
                                            { text: '微信号', value: 1 },
                                            { text: '手机号', value: 2 },
                                        ]" />
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">频率设置</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx] flex flex-col gap-[20rpx]">
                                <view>
                                    <text class="text-[22rpx] text-[#9CA3AF] block mb-[14rpx]">每日添加线索数量</text>
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="(item, index) in dayNumList"
                                            :key="index"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                formData.add_number == item
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleDayNum(item)">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    formData.add_number == item ? 'text-primary' : 'text-[#9CA3AF]'
                                                ">
                                                {{ item }}条
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <view class="h-[1rpx] bg-[#F0F2F5]" />

                                <view>
                                    <text class="text-[22rpx] text-[#9CA3AF] block mb-[14rpx]"
                                        >每个账号添加时间间隔</text
                                    >
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="(item, index) in timeIntervalList"
                                            :key="index"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                timeIntervalIndex == index
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleTimeInterval(item, index)">
                                            <text
                                                class="font-bold"
                                                :class="timeIntervalIndex == index ? 'text-primary' : 'text-[#9CA3AF]'">
                                                {{ item }}分钟
                                            </text>
                                        </view>

                                        <view
                                            class="h-[68rpx] px-[20rpx] flex items-center gap-[8rpx] rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                timeIntervalIndex == 4
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="timeIntervalIndex = 4">
                                            <text
                                                class="font-bold"
                                                :class="timeIntervalIndex == 4 ? 'text-primary' : 'text-[#9CA3AF]'">
                                                自定义
                                            </text>
                                            <template v-if="timeIntervalIndex == 4">
                                                <view class="w-[100rpx]">
                                                    <u-input
                                                        v-model="timeInterval"
                                                        type="digit"
                                                        height="20"
                                                        placeholder="请输入"
                                                        placeholder-style="color:#C0C4CC;font-size:24rpx;"
                                                        @focus="timeIntervalIndex = 4" />
                                                </view>
                                                <text class="font-bold text-primary">分钟</text>
                                            </template>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center gap-[10rpx]">
                                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">加好友备注设置</text>
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] rounded-[14rpx] bg-[#EBF2FF] border border-solid border-[#BFDBFE]"
                                    @click="handleEditRemark(-1)">
                                    <u-icon name="plus" color="#0065fb" size="20" />
                                    <text class="text-xs font-semibold text-primary">新增</text>
                                </view>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view v-if="formData.remarks.length > 0" class="flex flex-wrap gap-[12rpx]">
                                    <view
                                        v-for="(item, index) in formData.remarks"
                                        :key="index"
                                        class="relative flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[12rpx] border border-solid border-[#E5E9F0]"
                                        @click="handleEditRemark(index)">
                                        <text class="text-xs text-[#4B5563]">{{ item }}</text>
                                        <view
                                            class="w-[28rpx] h-[28rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center"
                                            @click.stop="handleDeleteRemark(index)">
                                            <u-icon name="close" size="14" color="#9CA3AF" />
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="flex justify-center py-[20rpx]">
                                    <view
                                        class="flex items-center gap-[8rpx] h-[80rpx] px-[40rpx] rounded-[20rpx] border border-dashed border-[#BFDBFE] bg-[#F0F6FF]"
                                        @click="handleEditRemark(-1)">
                                        <u-icon name="plus" color="#0065fb" size="20" />
                                        <text class="text-primary font-semibold">添加备注内容</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <scroll-view v-show="currentStep === 3" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx]">
                    <base-setting
                        v-model="formData"
                        :current-frequency="currentFrequency"
                        @change-frequency="currentFrequency = $event" />
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]"
            :class="currentStep === 1 ? 'justify-end' : 'justify-between'">
            <view
                v-if="currentStep !== 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(currentStep, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="currentStep != STEPS.length">
                <view
                    v-if="currentStep === 1"
                    class="w-[96rpx] h-[96rpx] rounded-[24rpx] flex flex-col items-center justify-center"
                    :class="taskList.length > 0 ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]' : 'bg-[#F0F2F5]'">
                    <text
                        class="text-[32rpx] font-extrabold"
                        :class="taskList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ taskList.length }}
                    </text>
                    <text
                        class="text-[20rpx] font-semibold"
                        :class="taskList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                        已选
                    </text>
                </view>

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

    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />

    <u-popup v-model="showRemarkPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-[32rpx] bg-white rounded-[28rpx]">
            <text class="text-[30rpx] font-extrabold text-[#0D1117] text-center block mb-[28rpx]">输入加好友备注</text>
            <view
                class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0] mb-[28rpx]">
                <u-input
                    v-model="newRemark"
                    placeholder="请输入备注内容"
                    maxlength="100"
                    placeholder-style="color:#C0C4CC;font-size:26rpx;" />
            </view>
            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[20rpx] bg-[#F0F2F5]"
                    @click="closeRemarkPopup">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">取消</text>
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[20rpx] shadow-[0_6rpx_16rpx_rgba(0,101,251,0.25)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleRemarkConfirm">
                    <text class="text-[28rpx] font-bold text-white">确定</text>
                </view>
            </view>
        </view>
    </u-popup>

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
import { getPublishAccountList } from "@/api/device";
import { useAppStore } from "@/stores/app";
import { useDictOptions } from "@/hooks/useDictOptions";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import Steps from "@/ai_modules/device/components/steps/steps.vue";
import ClueCard from "@/ai_modules/device/components/clue-card/clue-card.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";

import { STEPS, createDefaultFormData } from "./hooks/types";
import { useStep } from "./hooks/useStep";
import { useTaskList } from "./hooks/useTaskList";
import { useFrequencySetting } from "./hooks/useFrequencySetting";
import { useCreateTask } from "./hooks/useCreateTask";

const { on } = useEventBusManager();
const appStore = useAppStore();

// ── formData ──────────────────────────────────────────────────────────────────
const formData = reactive(createDefaultFormData(appStore.config.wechat_remarks || []));

// 同步 store 中的 wechat_remarks 变化
watch(
    () => appStore.config.wechat_remarks,
    (newVal) => {
        formData.remarks = newVal || [];
    },
);

const currentFrequency = ref(0);

// ── Hooks ─────────────────────────────────────────────────────────────────────
const { taskList, uploadMaterialList, showUploadProgress, handleAddTask, handleDeleteTask } = useTaskList(formData);

const {
    dayNumList,
    timeIntervalList,
    timeIntervalIndex,
    timeInterval,
    showRemarkPopup,
    newRemark,
    editRemarkIndex,
    handleDayNum,
    handleTimeInterval,
    handleEditRemark,
    handleRemarkConfirm,
    closeRemarkPopup,
    handleDeleteRemark,
} = useFrequencySetting(formData);

const {
    step: currentStep,
    canNext,
    handleStep,
} = useStep({
    formData,
    taskList,
    timeIntervalIndex,
    timeInterval,
});

const {
    taskErrorMsg,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask({ formData, taskList, currentFrequency, timeIntervalIndex, timeInterval });

// ── 账号列表 ──────────────────────────────────────────────────────────────────
const { optionsData } = useDictOptions<{ wechatLists: any[] }>({
    wechatLists: {
        api: getPublishAccountList,
        params: { page_size: 9999, type: 1 },
        transformData: (res: any) => res.lists?.map((item: any) => ({ text: item.nickname, value: item.account })),
    },
});

// ── onLoad EventBus ───────────────────────────────────────────────────────────
onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.WECHAT_CLUE) {
            if (data && data.length > 0) {
                if (formData.source === 1) {
                    taskList.value = [];
                    formData.fileurl = "";
                    formData.source = 2;
                }
                taskList.value = taskList.value.concat(data);
            }
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
        if (type === ListenerTypeEnum.CHOOSE_DEVICE) {
            if (data.length === 0) {
                formData.device_codes = [];
                return;
            }
            formData.device_codes = data;
        }
    });
});
</script>
