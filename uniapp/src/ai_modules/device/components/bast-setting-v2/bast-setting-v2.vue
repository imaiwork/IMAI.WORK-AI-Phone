<template>
    <view>
        <view>
            <view class="flex items-center gap-x-1">
                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                <text class="font-bold">基础设置</text>
            </view>
            <view class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                <view>
                    <view class="text-[#7C7E80]">任务名称</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.name"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入任务名称"
                                maxlength="30" />
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]" v-if="showDevice">设备选择</view>
                    <view class="text-[#7C7E80]" v-if="showAccounts">账号选择</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <navigator
                                v-if="showDevice"
                                :url="`/ai_modules/device/pages/device_choose/device_choose?device=${JSON.stringify(
                                    formData.device_codes
                                )}`"
                                class="flex items-center justify-between h-[70rpx]"
                                hover-class="none">
                                <text
                                    :class="[
                                        formData.device_codes.length ? 'text-primary font-bold' : 'text-[#00000033]',
                                    ]"
                                    >{{
                                        formData.device_codes.length
                                            ? `${formData.device_codes.length}个设备`
                                            : "选择设备"
                                    }}</text
                                >
                                <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                            </navigator>
                            <navigator
                                v-if="showAccounts"
                                :url="`/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
                                    formData.accounts
                                )}&platformTypes=${JSON.stringify(platformTypes)}`"
                                class="flex items-center justify-between h-[70rpx]"
                                hover-class="none">
                                <text
                                    :class="[formData.accounts.length ? 'text-primary font-bold' : 'text-[#00000033]']"
                                    >{{
                                        formData.accounts.length ? `${formData.accounts.length}个账号` : "选择账号"
                                    }}</text
                                >
                                <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                            </navigator>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="mt-[32rpx]">
            <view class="flex items-center gap-x-1">
                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                <text class="font-bold">时间设置</text>
            </view>
            <view class="bg-white mt-4 rounded-[16rpx] px-4 py-[28rpx] shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
                <view>
                    <view class="text-[#7C7E80]">任务频率</view>
                    <view class="mt-[22rpx]">
                        <view class="flex flex-wrap gap-x-2 gap-y-3">
                            <view
                                v-for="(item, index) in [1, 3, 5, 10, 30]"
                                :key="index"
                                :class="{ active: formData.task_frep == item && currentFrequency != 5 }"
                                class="frequency-item"
                                @click="handleFrequency(item, index)">
                                {{ item }}天
                            </view>
                            <view
                                class="frequency-item"
                                :class="{ active: currentFrequency == 5 }"
                                @click="handleCustomDate">
                                自定义
                            </view>
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]" v-if="formData.custom_date.length && currentFrequency == 5">
                    <view class="flex items-center justify-between">
                        <view class="text-[#7C7E80]">任务时间</view>
                        <view
                            class="flex items-center gap-x-1"
                            v-if="formData.custom_date.length > 8"
                            @click="isExpandDate = !isExpandDate">
                            <text class="text-[#00000080]">{{ isExpandDate ? "收起" : "展开" }}</text>
                            <u-icon
                                :name="isExpandDate ? 'arrow-up' : 'arrow-down'"
                                size="24"
                                color="#00000033"></u-icon>
                        </view>
                    </view>
                    <view class="mt-[22rpx]" :class="{ 'max-h-[120rpx] overflow-hidden': !isExpandDate }">
                        <view class="flex flex-wrap gap-2">
                            <view v-for="(item, index) in formData.custom_date" :key="index" class="date-item">
                                {{ formatDate(item) }}
                            </view>
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">每日执行时间</view>
                    <view class="mt-[12rpx] flex items-center gap-x-4">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                            <picker
                                mode="time"
                                class="w-full"
                                :value="formData.time_config[0]"
                                @change="handleStartTimeChange">
                                <view class="flex items-center justify-between h-[70rpx]">
                                    <text
                                        :class="[
                                            formData.time_config[0] ? 'text-primary font-bold' : 'text-[#00000033]',
                                        ]"
                                        >{{ formData.time_config[0] || "开始时间" }}</text
                                    >
                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                </view>
                            </picker>
                        </view>
                        <view class="text-[#7C7E80]">至</view>
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                            <picker
                                mode="time"
                                class="w-full"
                                :value="formData.time_config[1]"
                                :disabled="!formData.time_config[0]"
                                @click="handleEndTimeClick"
                                @change="handleEndTimeChange">
                                <view class="flex items-center justify-between h-[70rpx]">
                                    <text
                                        :class="[
                                            formData.time_config[1] ? 'text-primary font-bold' : 'text-[#00000033]',
                                        ]"
                                        >{{ formData.time_config[1] || "结束时间" }}</text
                                    >
                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                </view>
                            </picker>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { AppTypeEnum } from "@/enums/appEnums";
const props = withDefaults(
    defineProps<{
        modelValue: any;
        showDevice: boolean;
        showAccounts: boolean;
        platformTypes?: AppTypeEnum[];
        currentFrequency?: number;
        // 时间间隔
        timeInterval?: number;
    }>(),
    {
        showDevice: true,
        showAccounts: false,
        currentFrequency: 0,
        timeInterval: 30,
    }
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

const currentFrequency = computed({
    get() {
        return props.currentFrequency;
    },
    set(value: number) {
        emit("changeFrequency", value);
    },
});

const isExpandDate = ref(false);

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
        params: {
            date: JSON.stringify(formData.value.custom_date),
        },
    });
};

const handleStartTimeChange = (e: any) => {
    const { value } = e.detail;
    // 判断时间不能小于当前时间
    const endTime = new Date(`2000/01/01 ${value}`);
    formData.value.time_config[0] = value;
    endTime.setMinutes(endTime.getMinutes() + 30);
    formData.value.time_config[1] = uni.$u.timeFormat(endTime, "hh:MM");
};

const handleEndTimeChange = (e: any) => {
    const { value } = e.detail;
    // 这里需要判断结束时间是否大于开始时间，并且要大于开始
    if (value <= formData.value.time_config[0]) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTIme = new Date(`2000/01/01 ${formData.value.time_config[0]}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTIme.getTime() < props.timeInterval * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间${props.timeInterval}分钟`);
        return;
    }
    formData.value.time_config[1] = value;
};

const handleEndTimeClick = () => {
    if (!formData.value.time_config[0]) {
        uni.$u.toast("请先选择开始时间");
        return;
    }
};

watch(
    () => props.currentFrequency,
    (newVal) => {
        currentFrequency.value = newVal;
    }
);
</script>

<style scoped lang="scss">
.frequency-item {
    @apply px-[32rpx] py-[16rpx] rounded-[10rpx] bg-[#F6F6F6];
    &.active {
        @apply text-primary shadow-[0_0_0_2rpx_#0065FB] font-bold bg-white;
    }
}
.date-item {
    @apply text-xs font-bold text-[#000000b3] rounded-[10rpx] px-[20rpx] py-[10rpx] bg-[#F6F6F6];
}
</style>
