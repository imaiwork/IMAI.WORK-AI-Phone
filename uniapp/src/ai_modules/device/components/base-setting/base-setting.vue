<template>
    <view class="flex flex-col gap-[16rpx]">
        <view
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">基础设置</text>
            </view>

            <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <text class="text-xs text-[#9CA3AF] block mb-[12rpx]">任务名称</text>
                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                    <u-input
                        v-model="formData.name"
                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                        placeholder="请输入任务名称"
                        maxlength="30" />
                </view>
            </view>

            <navigator
                v-if="showDevice"
                :url="`/ai_modules/device/pages/device_choose/device_choose?device=${JSON.stringify(
                    formData?.device_codes,
                )}`"
                class="flex items-center justify-between px-[28rpx] h-[100rpx]"
                hover-class="none">
                <text class="text-xs text-[#9CA3AF]">设备选择</text>
                <view class="flex items-center gap-[6rpx]">
                    <text
                        class="font-semibold"
                        :class="formData?.device_codes?.length ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ formData?.device_codes?.length ? `${formData.device_codes.length} 个设备` : "选择设备" }}
                    </text>
                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                </view>
            </navigator>

            <navigator
                v-if="showAccounts"
                :url="`/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
                    formData.accounts,
                )}&platformTypes=${JSON.stringify(platformTypes)}&multiple=${multiple}`"
                class="flex items-center justify-between px-[28rpx] h-[100rpx]"
                hover-class="none">
                <text class="text-xs text-[#9CA3AF]">账号选择</text>
                <view class="flex items-center gap-[6rpx]">
                    <text class="font-semibold" :class="formData?.accounts?.length ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ formData?.accounts?.length ? `${formData.accounts.length} 个账号` : "选择账号" }}
                    </text>
                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                </view>
            </navigator>
            <slot name="base-setting" />
        </view>

        <view
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">时间设置</text>
            </view>

            <view class="px-[28rpx] py-[20rpx] flex flex-col gap-[24rpx]">
                <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx] gap-[6rpx]">
                    <view
                        v-for="(item, index) in taskExecTypeOptions"
                        :key="index"
                        class="flex-1 flex items-center justify-center gap-[8rpx] h-[72rpx] rounded-[16rpx] font-semibold transition-all duration-200"
                        :class="
                            formData.task_exec_type === item.value
                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                : 'text-[#9CA3AF]'
                        "
                        @click="formData.task_exec_type = item.value">
                        <u-icon
                            :name="item.icon"
                            size="26"
                            :color="formData.task_exec_type === item.value ? '#0065fb' : '#9CA3AF'" />
                        <text>{{ item.text }}</text>
                    </view>
                </view>

                <view
                    v-if="formData.task_exec_type === 1"
                    class="flex items-center justify-between py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view>
                        <text class="text-[28rpx] font-semibold text-[#0D1117] block mb-[8rpx]">任务执行时间</text>
                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                            当内容执行完成后，任务会根据<br />设定时间提前结束
                        </text>
                    </view>
                    <view class="flex items-center gap-[12rpx] flex-shrink-0 ml-[16rpx]">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                            @click="handleExecuteMinuteChange(-1)">
                            <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                        </view>
                        <view
                            class="w-[100rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                            <u-input
                                v-model="formData.minutes"
                                type="digit"
                                placeholder=""
                                :custom-style="{
                                    color: '#0065fb',
                                    fontWeight: '800',
                                    fontSize: '28rpx',
                                    textAlign: 'center',
                                }"
                                input-align="center" />
                        </view>
                        <text class="text-xs text-[#9CA3AF]">分钟</text>
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-[16rpx] border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                            @click="handleExecuteMinuteChange(1)">
                            <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                        </view>
                    </view>
                </view>

                <view v-if="formData.task_exec_type == 0">
                    <text class="text-xs text-[#9CA3AF] block mb-[16rpx]">任务频率</text>
                    <view class="flex flex-wrap gap-[12rpx]">
                        <view
                            v-for="(item, index) in [1, 3, 5, 10, 30]"
                            :key="index"
                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                            :class="
                                formData.task_frep == item && currentFrequency != 5
                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                    : 'bg-[#F0F2F5]'
                            "
                            @click="handleFrequency(item, index)">
                            <text
                                class="font-bold"
                                :class="
                                    formData.task_frep == item && currentFrequency != 5
                                        ? 'text-primary'
                                        : 'text-[#9CA3AF]'
                                ">
                                {{ item }}天
                            </text>
                        </view>
                        <view
                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                            :class="
                                currentFrequency == 5
                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                    : 'bg-[#F0F2F5]'
                            "
                            @click="handleCustomDate">
                            <text class="font-bold" :class="currentFrequency == 5 ? 'text-primary' : 'text-[#9CA3AF]'">
                                自定义
                            </text>
                        </view>
                    </view>
                </view>

                <view v-if="formData.custom_date.length && currentFrequency == 5">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="text-xs text-[#9CA3AF]">任务时间</text>
                        <view
                            v-if="formData.custom_date.length > 8"
                            class="flex items-center gap-[4rpx]"
                            @click="isExpandDate = !isExpandDate">
                            <text class="text-xs text-[#9CA3AF]">{{ isExpandDate ? "收起" : "展开" }}</text>
                            <u-icon :name="isExpandDate ? 'arrow-up' : 'arrow-down'" size="22" color="#9CA3AF" />
                        </view>
                    </view>
                    <view :class="{ 'max-h-[120rpx] overflow-hidden': !isExpandDate }">
                        <view class="flex flex-wrap gap-[10rpx]">
                            <view
                                v-for="(item, index) in formData.custom_date"
                                :key="index"
                                class="px-[16rpx] py-[8rpx] rounded-[12rpx] bg-[#EBF2FF]">
                                <text class="text-[22rpx] text-primary font-semibold">{{ formatDate(item) }}</text>
                            </view>
                        </view>
                    </view>
                </view>

                <view>
                    <view class="flex items-center justify-between mb-[16rpx]">
                        <view>
                            <text class="text-xs text-[#9CA3AF] block">每日执行时间</text>
                            <text v-if="isWechatPrivate" class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] block">
                                当天无任务时段均为"空闲"
                            </text>
                        </view>
                        <view
                            v-if="isWechatPrivate && formData.task_exec_type === 0"
                            class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                            <view
                                v-for="(item, index) in [
                                    { label: '自选时间', value: 0 },
                                    { label: '空闲时间', value: 1 },
                                ]"
                                :key="index"
                                class="flex-1 h-[60rpx] rounded-[14rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                :class="
                                    formData.time_type == item.value
                                        ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                        : 'text-[#9CA3AF]'
                                "
                                @click="
                                    formData.time_type = item.value;
                                    timeTypeIndex = index;
                                ">
                                {{ item.label }}
                            </view>
                        </view>
                    </view>

                    <template v-if="showTimeConfig">
                        <view
                            v-if="formData.task_exec_type == 1"
                            class="flex items-center justify-between h-[80rpx] bg-[#F0FDF4] rounded-[16rpx] px-[20rpx] border border-solid border-[#BBF7D0]">
                            <text class="font-semibold text-[#16A34A]">今日发布时间</text>
                            <view
                                class="px-[20rpx] py-[8rpx] rounded-full bg-[#DCFCE7] border border-solid border-[#BBF7D0]">
                                <text class="text-xs font-bold text-[#16A34A]">立即执行</text>
                            </view>
                        </view>

                        <view v-else class="flex items-center gap-[16rpx]">
                            <view
                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                <picker
                                    mode="time"
                                    class="w-full"
                                    :value="formData.time_config[0]"
                                    @change="handleStartTimeChange">
                                    <view class="flex items-center justify-between">
                                        <text
                                            class="font-semibold"
                                            :class="formData.time_config[0] ? 'text-primary' : 'text-[#C0C4CC]'">
                                            {{ formData.time_config[0] || "开始时间" }}
                                        </text>
                                        <u-icon name="arrow-down" size="20" color="#C0C4CC" />
                                    </view>
                                </picker>
                            </view>
                            <text class="text-xs text-[#9CA3AF] flex-shrink-0">至</text>
                            <view
                                class="flex-1 bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                                <picker
                                    mode="time"
                                    class="w-full"
                                    :value="formData.time_config[1]"
                                    :disabled="!formData.time_config[0]"
                                    @click="handleEndTimeClick"
                                    @change="handleEndTimeChange">
                                    <view class="flex items-center justify-between">
                                        <text
                                            class="font-semibold"
                                            :class="formData.time_config[1] ? 'text-primary' : 'text-[#C0C4CC]'">
                                            {{ formData.time_config[1] || "结束时间" }}
                                        </text>
                                        <u-icon name="arrow-down" size="20" color="#C0C4CC" />
                                    </view>
                                </picker>
                            </view>
                        </view>
                    </template>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        modelValue: {
            name: string;
            device_codes?: string[];
            accounts?: string[];
            task_frep: number;
            custom_date: string[];
            time_config: string[];
            task_exec_type?: number;
            minutes?: number;
        } & any;
        showDevice?: boolean;
        showAccounts?: boolean;
        platformTypes?: AppTypeEnum[];
        currentFrequency?: number;
        timeInterval?: number;
        multiple?: 0 | 1;
        isWechatPrivate?: boolean;
    }>(),
    {
        showDevice: true,
        showAccounts: false,
        currentFrequency: 0,
        timeInterval: 30,
        multiple: 1,
        isWechatPrivate: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: any): void;
    (e: "changeFrequency", value: number): void;
}>();

const formData = computed({
    get() {
        return props.modelValue;
    },
    set(value: any) {
        emit("update:modelValue", value);
    },
});

const showTimeConfig = computed(() => {
    return props.isWechatPrivate ? formData.value.time_type == 0 : true;
});

const currentFrequency = computed({
    get() {
        return props.currentFrequency;
    },
    set(value: number) {
        emit("changeFrequency", value);
    },
});

const isExpandDate = ref(false);
const timeTypeIndex = ref(0);

const taskExecTypeOptions = [
    { icon: "arrow-upward", text: "即时执行", value: 1 },
    { icon: "clock", text: "定时执行", value: 0 },
];

const handleExecuteMinuteChange = (type: number) => {
    const next = Number(formData.value.minutes) + type;
    if (next < 1) return;
    formData.value.minutes = next;
};

const handleFrequency = (item: number, index: number) => {
    currentFrequency.value = index;
    formData.value.task_frep = item;
    formData.value.custom_date = [];
    isExpandDate.value = false;
};

const formatDate = (date: string) => {
    return uni.$u.timeFormat(new Date(date), "mm月dd日");
};

const handleCustomDate = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/custom_date/custom_date",
        params: { date: JSON.stringify(formData.value.custom_date) },
    });
};

const handleStartTimeChange = (e: any) => {
    const { value } = e.detail;
    const endTime = new Date(`2000/01/01 ${value}`);
    formData.value.time_config[0] = value;
    endTime.setMinutes(endTime.getMinutes() + 30);
    formData.value.time_config[1] = uni.$u.timeFormat(endTime, "hh:MM");
};

const handleEndTimeChange = (e: any) => {
    const { value } = e.detail;
    if (value <= formData.value.time_config[0]) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTime = new Date(`2000/01/01 ${formData.value.time_config[0]}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTime.getTime() < props.timeInterval * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间${props.timeInterval}分钟`);
        return;
    }
    formData.value.time_config[1] = value;
};

const handleEndTimeClick = () => {
    if (!formData.value.time_config[0]) {
        uni.$u.toast("请先选择开始时间");
    }
};

watch(
    () => props.currentFrequency,
    (newVal) => {
        currentFrequency.value = newVal;
    },
);
</script>
