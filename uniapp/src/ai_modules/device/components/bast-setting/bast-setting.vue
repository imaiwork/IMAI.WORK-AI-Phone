<template>
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
                            maxlength="30"
                            :custom-style="{
                                fontSize: '26rpx',
                            }" />
                    </view>
                </view>
            </view>
            <view class="mt-[28rpx]">
                <view class="text-[#7C7E80]">发布账号选择</view>
                <view class="mt-[12rpx]">
                    <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                        <navigator
                            :url="`/ai_modules/device/pages/account_choose/account_choose?account=${JSON.stringify(
                                formData.accounts
                            )}`"
                            class="flex items-center justify-between h-[70rpx]"
                            hover-class="none">
                            <text :class="[formData.accounts.length ? 'text-primary font-bold' : 'text-[#00000033]']">{{
                                formData.accounts.length ? `${formData.accounts.length}个账号` : "选择账号"
                            }}</text>
                            <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                        </navigator>
                    </view>
                </view>
            </view>
            <view class="mt-[28rpx]">
                <view class="text-[#7C7E80]">发布频率（每日）</view>
                <view class="mt-[28rpx] grid grid-cols-5 gap-2">
                    <view
                        v-for="item in [1, 2, 3, 5, 10]"
                        :key="item"
                        class="prompt-length-item"
                        :class="{ active: formData.publish_frep === item }"
                        @click="handleFrequency(item)">
                        {{ item }}条
                    </view>
                </view>
            </view>
        </view>
    </view>
    <view class="mt-[32rpx]">
        <view class="flex items-center gap-x-1">
            <text class="text-[#FF3C26] text-[32rpx]">*</text>
            <text class="font-bold">发布时间</text>
        </view>
        <view class="mt-4 rounded-[16rpx] px-4 py-[28rpx] bg-white shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.03)]">
            <view class="mb-[28rpx]">
                <u-notice-bar
                    mode="vertical"
                    padding="20rpx 0"
                    border-radius="8"
                    font-size="24rpx"
                    :autoplay="false"
                    :volume-icon="false"
                    :list="[`发布的间隔时间必须大于${timeInterval}分钟`]"></u-notice-bar>
            </view>
            <view class="flex flex-col gap-y-[28rpx]">
                <view v-for="(item, index) in formData.time_config" :key="index">
                    <view class="text-[#7C7E80]">每天第{{ index + 1 }}个任务发布时间</view>
                    <view class="mt-[12rpx] flex items-center gap-x-4">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1 flex-1">
                            <picker
                                mode="time"
                                class="w-full"
                                :value="item.start_time"
                                @change="handleStartTimeChange($event, index)">
                                <view class="flex items-center justify-between h-[70rpx]">
                                    <text
                                        :class="[
                                            timeErrors[index]?.start_time
                                                ? 'text-[#FF3C26] font-bold'
                                                : item.start_time
                                                ? 'text-primary font-bold'
                                                : 'text-[#00000033]',
                                        ]"
                                        >{{ item.start_time || "开始时间" }}</text
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
                                :value="item.end_time"
                                :disabled="!item.end_time"
                                @click="handleEndTimeClick(index)"
                                @change="handleEndTimeChange($event, index)">
                                <view class="flex items-center justify-between h-[70rpx]">
                                    <text
                                        :class="[
                                            timeErrors[index]?.end_time
                                                ? 'text-[#FF3C26] font-bold'
                                                : item.end_time
                                                ? 'text-primary font-bold'
                                                : 'text-[#00000033]',
                                        ]"
                                        >{{ item.end_time || "结束时间" }}</text
                                    >
                                    <u-icon name="arrow-right" size="24" color="#00000033"></u-icon>
                                </view>
                            </picker>
                        </view>
                    </view>
                </view>
                <view v-if="Object.keys(timeErrors).length > 0" class="mt-2 text-[#FF3C26]"> 时间配置存在冲突 </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: any;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: any): void;
}>();

const formData = computed({
    get() {
        return props.modelValue;
    },
    set(value: any) {
        emit("update:modelValue", value);
    },
});

// 时间间隔
const timeInterval = 30;
// 时间错误
const timeErrors = ref<any>({});

const handleFrequency = (item: number) => {
    if (item == formData.value.publish_frep) return;
    formData.value.publish_frep = item;
    // 这里每次更改频率，都要重新生成时间
    formData.value.time_config = Array.from({ length: item }, (_, index) => {
        const baseTime = new Date();
        // 要使用当前早上九点的时分秒
        baseTime.setHours(9, 0, 0);
        const startTime = new Date(baseTime.getTime() + index * timeInterval * 60 * 1000);
        const endTime = new Date(startTime.getTime() + timeInterval * 60 * 1000);
        return {
            start_time: uni.$u.timeFormat(startTime, "hh:MM"),
            end_time: uni.$u.timeFormat(endTime, "hh:MM"),
        };
    });
    timeErrors.value = {};
};

const handleEndTimeClick = (index: number) => {
    const data = formData.value.time_config[index];
    if (!data.start_time) {
        uni.$u.toast("请先选择开始时间");
        return;
    }
};

const handleStartTimeChange = (e: any, index: number) => {
    const { value } = e.detail;
    const data = formData.value.time_config[index];
    // 判断时间不能小于当前时间
    const startTIme = new Date(`2000/01/01 ${new Date().toLocaleTimeString()}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() < startTIme.getTime()) {
        uni.$u.toast("开始时间不能小于当前时间");
        return;
    }
    data.start_time = value;
    endTime.setMinutes(endTime.getMinutes() + 30);
    data.end_time = uni.$u.timeFormat(endTime, "hh:MM");
    const { errors } = validateSchedule(formData.value.time_config);

    timeErrors.value = errors;
};

const handleEndTimeChange = (e: any, index: number) => {
    const { value } = e.detail;
    const data = formData.value.time_config[index];
    // 这里需要判断结束时间是否大于开始时间，并且要大于开始
    if (value <= data.start_time) {
        uni.$u.toast("结束时间不能小于开始时间");
        return;
    }
    const startTIme = new Date(`2000/01/01 ${data.start_time}`);
    const endTime = new Date(`2000/01/01 ${value}`);
    if (endTime.getTime() - startTIme.getTime() < 30 * 60 * 1000) {
        uni.$u.toast(`结束时间不能小于开始时间${timeInterval}分钟`);
        return;
    }
    data.end_time = value;
    const { errors } = validateSchedule(formData.value.time_config);
    timeErrors.value = errors;
};

function validateSchedule(list: any[]) {
    const toMin = (t: any) => {
        if (!t) return NaN;
        const [h, m] = (t || "").split(":").map(Number);
        return h * 60 + m;
    };

    const schedule = list.map((item) => ({
        start_time: item.start_time,
        end_time: item.end_time,
        s: toMin(item.start_time),
        e: toMin(item.end_time),
    }));

    const errors = schedule.reduce((acc, cur, i, arr) => {
        const addError = (index: number, field: "start_time" | "end_time") => {
            if (!acc[index]) acc[index] = { start_time: false, end_time: false };
            acc[index][field] = true;
        };

        if (cur.start_time == null || cur.start_time === "") {
            addError(i, "start_time");
        }
        if (cur.end_time == null || cur.end_time === "") {
            addError(i, "end_time");
        }

        if (isNaN(cur.s) || isNaN(cur.e)) {
            return acc;
        }

        if (cur.s >= cur.e) {
            addError(i, "start_time");
            addError(i, "end_time");
        }

        if (i > 0) {
            const prev = arr[i - 1];
            if (!isNaN(prev.e) && cur.s < prev.e) {
                addError(i - 1, "end_time");
                addError(i, "start_time");
            }
        }

        return acc;
    }, {} as { [key: number]: { start_time: boolean; end_time: boolean } });

    const isValid = Object.keys(errors).length === 0;
    return { valid: isValid, errors };
}

defineExpose({
    validateForm: () => {
        return Object.keys(timeErrors.value).some(
            (key) => timeErrors.value[key].start_time || timeErrors.value[key].end_time
        );
    },
});
</script>

<style scoped lang="scss">
.prompt-length-item {
    @apply bg-[#F6F6F6] rounded-[16rpx]  py-2 text-center text-xs text-[#00000080] border border-solid border-[#F6F6F6];
    &.active {
        @apply bg-white border-primary text-primary font-bold;
    }
}
</style>
