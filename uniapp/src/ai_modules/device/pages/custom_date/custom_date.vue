<template>
    <view>
        <Calendar
            ref="calendarRef"
            multi-select
            :is-today="false"
            :selected-dates="date"
            :disabled-date-method="disabledDateMethod"
            @selectDate="handleSelectDate" />
        <view class="px-4 mt-[50rpx]">
            <view class="font-bold text-[30rpx]">已选的任务时间（{{ date.length }}）</view>
            <view class="text-[#0000004d] text-xs mt-[4rpx] font-bold"> 多个时间范围在30天以内 </view>
            <view class="flex flex-wrap gap-2 mt-[26rpx]">
                <view
                    v-for="(item, index) in date"
                    :key="index"
                    class="date-item"
                    :class="{ 'error-date': dateErrorIndexes.includes(index) }"
                    @click="handleDeleteDate(item)">
                    {{ formatDate(item) }}
                </view>
            </view>
        </view>
        <view class="fixed bottom-0 left-0 w-full h-[180rpx] bg-white px-6 pt-4">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '12rpx', fontWeight: 'bold' }"
                @click="handleSave"
                >确定保存</u-button
            >
        </view>
    </view>
</template>

<script setup lang="ts">
import Calendar from "@/ai_modules/device/components/calendar/calendar.vue";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";

const date = ref<string[]>([]);
const calendarRef = ref<InstanceType<typeof Calendar>>();
const dateErrorIndexes = ref<number[]>([]);

const formatDate = (date: string) => {
    return uni.$u.timeFormat(new Date(date), "mm月dd日");
};

const disabledDateMethod = (date: Date) => {
    //根据date时间判断是否是今天之前的，如果是就传true，否则传false，
    const today = new Date();
    return date.getTime() < today.getTime() - 1 * 24 * 60 * 60 * 1000;
};

const handleSelectDate = (value: any) => {
    date.value = value;
};

const handleDeleteDate = (date: string) => {
    calendarRef.value?.locateToDate(date);
};

const handleSave = () => {
    // 要判断不能选择今天之前的时间，还有不能选择超过30天的时间
    let maxDifference = 30;
    let invalidIndexes = [-1, -1];

    for (let i = 0; i < date.value.length; i++) {
        for (let j = i + 1; j < date.value.length; j++) {
            const date1 = new Date(date.value[i]);
            const date2 = new Date(date.value[j]);
            const diffDays = Math.abs((date1.getTime() - date2.getTime()) / (24 * 60 * 60 * 1000));

            if (diffDays > 30 && diffDays > maxDifference) {
                maxDifference = diffDays;
                invalidIndexes = [i, j];
            }
        }
    }

    const isError = invalidIndexes[0] !== -1;

    dateErrorIndexes.value = invalidIndexes;

    if (isError) {
        uni.$u.toast("不能选择时间间隔超过30天的时间");

        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_DATE,
        data: date.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.date) {
        date.value = JSON.parse(options.date);
    }
});
</script>

<style scoped lang="scss">
.date-item {
    @apply bg-white text-xs px-2 py-1 rounded-[10rpx] font-bold;
    &.error-date {
        @apply bg-[#FFE5E5];
    }
}
</style>
