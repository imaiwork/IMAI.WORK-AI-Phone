<template>
    <u-popup
        v-model="show"
        mode="center"
        border-radius="32"
        width="84%"
        :mask-close-able="false"
        @close="handleClose">
        <view class="bg-white rounded-[32rpx] overflow-hidden">
            <view v-if="error" class="px-[48rpx] pt-[48rpx] pb-[48rpx] flex flex-col items-center">
                <view
                    class="w-[96rpx] h-[96rpx] rounded-[28rpx] bg-[#FEF2F2] border border-solid border-[#FECACA] flex items-center justify-center mb-[24rpx]">
                    <u-icon name="error-circle-fill" color="#EF4444" size="52" />
                </view>
                <text class="text-[30rpx] font-extrabold text-[#212121]">{{ ERROR_TITLE }}</text>
                <view class="w-full mt-[24rpx] mb-[36rpx] px-[24rpx] py-[20rpx] rounded-[20rpx] bg-[#F8FAFC]">
                    <text class="text-[24rpx] text-[#676767] leading-[40rpx] break-all">{{ errorDetail }}</text>
                </view>
                <view class="flex items-center gap-[16rpx] w-full">
                    <view
                        class="flex-1 h-[88rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                        @click="handleClose">
                        <text class="text-[28rpx] font-semibold text-[#676767]">{{ CLOSE_TEXT }}</text>
                    </view>
                    <view
                        class="flex-1 h-[88rpx] rounded-full bg-primary flex items-center justify-center shadow-sm active:opacity-90"
                        @click="emit('retry')">
                        <text class="text-[28rpx] font-semibold text-white">{{ RETRY_TEXT }}</text>
                    </view>
                </view>
            </view>

            <template v-else>
                <view class="px-[48rpx] pt-[48rpx] pb-[32rpx] flex flex-col items-center gap-[12rpx]">
                    <view
                        class="w-[96rpx] h-[96rpx] rounded-full flex items-center justify-center mb-[8rpx]"
                        :class="isExecuteComplete ? 'bg-[#ECFDF5]' : 'bg-[#EEF4FF]'">
                        <u-icon v-if="isExecuteComplete" name="checkmark-circle" color="#10B981" size="52" />
                        <u-icon v-else name="reload" color="#0065fb" size="48" />
                    </view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">
                        {{ isExecuteComplete ? "更新完成" : "正在更新中..." }}
                    </text>
                    <text class="text-[22rpx] text-[#676767]">
                        {{ isExecuteComplete ? "账号信息已成功获取" : "请保持手机屏幕常亮" }}
                    </text>
                </view>

                <view class="mx-[48rpx] h-[1rpx] bg-[#F4F6FB]"></view>

                <view class="px-[48rpx] py-[36rpx] flex flex-col">
                    <view v-for="(item, index) in steps" :key="index" class="flex gap-[20rpx]">
                        <view class="flex flex-col items-center flex-shrink-0">
                            <view
                                class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center"
                                :class="{
                                    'bg-[#F4F6FB] border border-solid border-[#f9f9f9]':
                                        item.status === STEP_STATUS.PENDING,
                                    'bg-[#EEF4FF] border border-solid border-primary':
                                        item.status === STEP_STATUS.RUNNING,
                                    'bg-primary': item.status === STEP_STATUS.DONE,
                                    'bg-[#FEF2F2] border border-solid border-danger':
                                        item.status === STEP_STATUS.FAILED,
                                }">
                                <view
                                    v-if="item.status === STEP_STATUS.RUNNING"
                                    class="w-[14rpx] h-[14rpx] rounded-full bg-primary animate-pulse"></view>
                                <u-icon
                                    v-else-if="item.status === STEP_STATUS.DONE"
                                    name="checkmark"
                                    color="#ffffff"
                                    size="20" />
                                <u-icon
                                    v-else-if="item.status === STEP_STATUS.FAILED"
                                    name="close"
                                    color="#EF4444"
                                    size="20" />
                            </view>
                            <view
                                v-if="index !== steps.length - 1"
                                class="w-[2rpx] flex-1 min-h-[28rpx] my-[4rpx] rounded-full"
                                :class="item.status === STEP_STATUS.DONE ? 'bg-primary' : 'bg-[#F4F6FB]'"></view>
                        </view>

                        <view
                            class="flex flex-col justify-center pb-[28rpx]"
                            :class="{ 'pb-0': index === steps.length - 1 }">
                            <text
                                class="text-[26rpx] font-semibold"
                                :class="{
                                    'text-[#676767]': item.status === STEP_STATUS.PENDING,
                                    'text-primary': item.status === STEP_STATUS.RUNNING,
                                    'text-[#212121]': item.status === STEP_STATUS.DONE,
                                    'text-[#EF4444]': item.status === STEP_STATUS.FAILED,
                                }"
                                >{{ item.title }}</text
                            >
                            <text
                                v-if="item.status === STEP_STATUS.RUNNING"
                                class="text-[20rpx] text-primary mt-[4rpx]"
                                >获取中...</text
                            >
                            <text
                                v-else-if="item.status === STEP_STATUS.FAILED"
                                class="text-[20rpx] text-[#EF4444] mt-[4rpx]"
                                >获取失败，请重试</text
                            >
                        </view>
                    </view>
                </view>

                <view class="px-[48rpx] pb-[48rpx] flex flex-col gap-[16rpx]">
                    <view
                        v-if="isExecuteComplete"
                        class="w-full h-[88rpx] rounded-full bg-primary flex items-center justify-center shadow-sm active:opacity-90"
                        @click="handleClose">
                        <text class="text-[28rpx] font-bold text-white">确认</text>
                    </view>
                    <view
                        class="w-full h-[88rpx] rounded-full bg-[#F4F6FB] flex items-center justify-center active:opacity-70"
                        @click="handleClose">
                        <text class="text-[28rpx] font-semibold text-[#676767]">{{
                            isExecuteComplete ? "关闭" : "取消"
                        }}</text>
                    </view>
                </view>
            </template>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { formatAccountFetchError } from "@/ai_modules/device/hooks/apply-account-fetch-error";

const ERROR_TITLE = "账号信息获取失败";
const ERROR_FALLBACK = "无法通过 RPA 建立连接";
const CLOSE_TEXT = "暂不同步";
const RETRY_TEXT = "重新尝试";

const STEP_STATUS = {
    PENDING: 0,
    RUNNING: 1,
    DONE: 2,
    FAILED: 3,
} as const;

interface UpdateProgressStep {
    title: string;
    status: number;
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        steps: UpdateProgressStep[];
        error?: boolean;
        errorMsg?: string;
    }>(),
    {
        error: false,
        errorMsg: "",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "close"): void;
    (e: "retry"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const isExecuteComplete = computed(() =>
    props.steps.length > 0 && props.steps.every((item) => item.status === STEP_STATUS.DONE),
);

const errorDetail = computed(() => formatAccountFetchError(props.errorMsg || ERROR_FALLBACK));

const handleClose = () => {
    emit("update:modelValue", false);
    emit("close");
};
</script>
