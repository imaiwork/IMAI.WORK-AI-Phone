<template>
    <view>
        <view class="h-[100rpx] flex items-center justify-between gap-x-2">
            <view class="text-[30rpx] font-bold">{{ getDay }}</view>
            <navigator
                url="/ai_modules/device/pages/task_calendar_full/task_calendar_full"
                hover-class="none"
                class="flex items-center gap-x-1">
                <text class="text-primary font-bold">完整日历</text>
                <u-icon name="arrow-right" color="#0065FB"></u-icon>
            </navigator>
        </view>
        <view>
            <view
                class="flex items-center border-[0] border-t-[1rpx] border-b-[1rpx] border-solid border-[#00000008] h-[58rpx]">
                <view
                    v-for="(item, index) in weekDays"
                    class="text-[18rpx] font-bold text-center flex-1"
                    :key="index"
                    :class="{
                        'text-[#0000004d]': index == 0 || index == weekDays.length - 1,
                    }">
                    {{ item }}
                </view>
            </view>
            <view class="flex items-center py-[26rpx]">
                <view v-for="(day, index) in getDays" class="calendar-item" :key="index" @click="selectDate(day.date)">
                    <view
                        class="grid-item"
                        :class="{
                            today: day.isToday,
                            selected: day.date === selectedDate,
                            'text-[#0000004d]': index == 0 || index == weekDays.length - 1,
                        }">
                        {{ day.day }}
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
interface Day {
    date: string;
    day: number;
    isToday: boolean;
}

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
    (e: "change", value: string): void;
}>();

const selectedDate = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const getDay = computed(() =>
    uni.$u.timeFormat(selectedDate.value ? new Date(selectedDate.value) : new Date(), "yyyy年mm月dd日")
);

const getDays = computed(() => {
    const centerDate = selectedDate.value
        ? new Date(selectedDate.value.replace(/-/g, "/"))
        : new Date();
    const today = new Date();
    const todayDateString = uni.$u.timeFormat(today, "yyyy-mm-dd");

    const days: Day[] = [];

    // 获取前3天
    for (let i = 3; i > 0; i--) {
        const date = new Date(centerDate);
        date.setDate(centerDate.getDate() - i);
        const dateString = uni.$u.timeFormat(date, "yyyy-mm-dd");
        days.push({
            date: dateString,
            day: uni.$u.timeFormat(date, "dd"),
            isToday: dateString === todayDateString,
        });
    }

    // 添加中间日期
    const centerDateString = uni.$u.timeFormat(centerDate, "yyyy-mm-dd");
    days.push({
        date: centerDateString,
        day: uni.$u.timeFormat(centerDate, "dd"),
        isToday: centerDateString === todayDateString,
    });

    // 获取后3天
    for (let i = 1; i <= 3; i++) {
        const date = new Date(centerDate);
        date.setDate(centerDate.getDate() + i);
        const dateString = uni.$u.timeFormat(date, "yyyy-mm-dd");
        days.push({
            date: dateString,
            day: uni.$u.timeFormat(date, "dd"),
            isToday: dateString === todayDateString,
        });
    }

    return days;
});

const weekDays = computed(() => {
    const daysOfWeek = ["日", "一", "二", "三", "四", "五", "六"];
    return getDays.value.map(day => {
        const date = new Date(day.date.replace(/-/g, "/"));
        return daysOfWeek[date.getDay()];
    });
});

const selectDate = (date: string) => {
    selectedDate.value = date;
    emit("change", date);
};
</script>

<style scoped lang="scss">
.calendar-item {
    @apply flex-1 flex items-center justify-center;
}

.grid-item {
    @apply w-[86rpx] h-[86rpx] flex items-center justify-center rounded-full font-bold relative;
}
.selected {
    @apply bg-primary text-white;
}

.today:before {
    transform: translateX(-50%);
    @apply content-[''] absolute bottom-[10rpx] left-1/2  w-[8rpx] h-[8rpx] rounded-full bg-primary;
}

.selected.today:before {
    @apply bg-white;
}
</style>
