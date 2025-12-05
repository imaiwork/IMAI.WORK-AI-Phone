<template>
    <view class="task-calendar-container">
        <view class="task-calendar">
            <view class="calendar-header">
                <view class="header-left">
                    <view class="month-year">{{ currentYear }}年 {{ currentMonth }}月</view>
                    <view class="back-to-today" @click="backToToday(true)">今天</view>
                </view>
                <view class="arrow-group">
                    <view class="arrow" @click="prevMonth">
                        <view class="arrow-icon arrow-left"></view>
                    </view>
                    <view class="arrow" @click="nextMonth">
                        <view class="arrow-icon arrow-right"></view>
                    </view>
                </view>
            </view>
            <view class="calendar-weekdays">
                <view class="weekday" v-for="day in weekDays" :key="day">{{ day }}</view>
            </view>
            <view>
                <view class="calendar-week" v-for="(week, weekIndex) in weeks" :key="weekIndex">
                    <view
                        class="grid-item"
                        :class="{
                            'not-current-month': !day.isCurrentMonth,
                            selected: selectedDate.includes(day.date),
                            today: day.isToday,
                            disabled: day.isDisabled,
                        }"
                        v-for="(day, dayIndex) in week"
                        :key="dayIndex"
                        @click="selectDate(day)">
                        <view class="day-number">{{ day.day }}</view>
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
    isCurrentMonth: boolean;
    isToday: boolean;
    isDisabled: boolean;
}

interface Props {
    modelValue?: string | string[];
    multiSelect?: boolean;
    disabledDates?: string[];
    disabledDateMethod?: (date: Date) => boolean;
    isToday?: boolean;
}
const props = withDefaults(defineProps<Props>(), {
    multiSelect: false,
    disabledDates: () => [],
    disabledDateMethod: () => false,
    isToday: true,
});

const disabledDatesSet = computed(() => new Set(props.disabledDates));

const emit = defineEmits<{
    (e: "selectDate", date: string | string[]): void;
    (e: "update:modelValue", value: string | string[]): void;
}>();

const weekDays = ["日", "一", "二", "三", "四", "五", "六"];
const days = ref<Day[]>([]);
const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth() + 1);
const selectedDate = ref<string[]>([]);

watch(
    () => props.modelValue,
    (newValue) => {
        let newSelected: string[] = [];
        if (Array.isArray(newValue)) {
            newSelected = [...newValue];
        } else if (typeof newValue === "string" && newValue) {
            newSelected = [newValue];
        }

        if (JSON.stringify(selectedDate.value) !== JSON.stringify(newSelected)) {
            selectedDate.value = newSelected;
        }
    },
    { deep: true, immediate: true }
);

const weeks = computed(() => {
    const weeksR: Day[][] = [];
    const daysV = days.value;
    for (let i = 0; i < daysV.length; i += 7) {
        weeksR.push(daysV.slice(i, i + 7));
    }
    return weeksR;
});

const generateCalendar = (year: number, month: number) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayDate = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;

    const date = new Date(year, month - 1, 1);
    const firstDay = date.getDay();
    const daysInMonth = new Date(year, month, 0).getDate();

    const calendarDays: Day[] = [];

    const prevMonthEndDate = new Date(year, month - 1, 0);
    const prevMonthLastDay = prevMonthEndDate.getDate();
    const prevMonthYear = prevMonthEndDate.getFullYear();
    const prevMonth = prevMonthEndDate.getMonth() + 1;
    for (let i = firstDay; i > 0; i--) {
        const day = prevMonthLastDay - i + 1;
        const dateStr = `${prevMonthYear}-${prevMonth}-${day}`;
        const dateObj = new Date(prevMonthYear, prevMonth - 1, day);
        const isDisabledByMethod = props.disabledDateMethod(dateObj);
        calendarDays.push({
            date: dateStr,
            day: day,
            isCurrentMonth: false,
            isToday: false,
            isDisabled: disabledDatesSet.value.has(dateStr) || isDisabledByMethod,
        });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = `${year}-${month}-${i}`;
        const isToday = dateStr === todayDate;
        const dateObj = new Date(year, month - 1, i);
        const isDisabledByMethod = props.disabledDateMethod(dateObj);
        calendarDays.push({
            date: dateStr,
            day: i,
            isCurrentMonth: true,
            isToday: isToday,
            isDisabled: disabledDatesSet.value.has(dateStr) || isDisabledByMethod,
        });
    }

    const gridCells = 35;
    if (calendarDays.length < gridCells) {
        const nextMonthStartDate = new Date(year, month, 1);
        const nextMonthYear = nextMonthStartDate.getFullYear();
        const nextMonth = nextMonthStartDate.getMonth() + 1;
        let nextDay = 1;
        while (calendarDays.length < gridCells) {
            const dateStr = `${nextMonthYear}-${nextMonth}-${nextDay}`;
            const dateObj = new Date(nextMonthYear, nextMonth - 1, nextDay);
            const isDisabledByMethod = props.disabledDateMethod(dateObj);
            calendarDays.push({
                date: dateStr,
                day: nextDay,
                isCurrentMonth: false,
                isToday: false,
                isDisabled: disabledDatesSet.value.has(dateStr) || isDisabledByMethod,
            });
            nextDay++;
        }
    }

    days.value = calendarDays;
};

const selectDate = (day: Day) => {
    if (day.isDisabled) return;
    const dateStr = day.date;

    if (props.multiSelect) {
        const index = selectedDate.value.indexOf(dateStr);
        if (index > -1) {
            selectedDate.value.splice(index, 1);
        } else {
            selectedDate.value.push(dateStr);
        }
        emit("update:modelValue", selectedDate.value);
    } else {
        selectedDate.value = [dateStr];
        emit("update:modelValue", dateStr);
    }

    if (!day.isCurrentMonth) {
        const [year, month] = dateStr.split("-").map(Number);
        currentYear.value = year;
        currentMonth.value = month;
        generateCalendar(currentYear.value, currentMonth.value);
    }
    emit("selectDate", props.multiSelect ? selectedDate.value : dateStr);
};

const prevMonth = () => {
    currentMonth.value--;
    if (currentMonth.value < 1) {
        currentMonth.value = 12;
        currentYear.value--;
    }
    generateCalendar(currentYear.value, currentMonth.value);
};

const nextMonth = () => {
    currentMonth.value++;
    if (currentMonth.value > 12) {
        currentMonth.value = 1;
        currentYear.value++;
    }
    generateCalendar(currentYear.value, currentMonth.value);
};

const backToToday = (doSelect: boolean) => {
    const today = new Date();
    currentYear.value = today.getFullYear();
    currentMonth.value = today.getMonth() + 1;

    if (doSelect) {
        const todayStr = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;
        if (props.multiSelect) {
            if (!selectedDate.value.includes(todayStr)) {
                selectedDate.value.push(todayStr);
            }
            emit("update:modelValue", selectedDate.value);
        } else {
            selectedDate.value = [todayStr];
            emit("update:modelValue", todayStr);
        }
        emit("selectDate", props.multiSelect ? selectedDate.value : todayStr);
    }
    generateCalendar(currentYear.value, currentMonth.value);
};

const locateToDate = (date: string) => {
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) {
        return;
    }
    currentYear.value = dateObj.getFullYear();
    currentMonth.value = dateObj.getMonth() + 1;
    const dateStr = `${dateObj.getFullYear()}-${dateObj.getMonth() + 1}-${dateObj.getDate()}`;

    if (props.multiSelect) {
        const index = selectedDate.value.indexOf(dateStr);
        if (index === -1) {
            selectedDate.value.push(dateStr);
        }
        emit("update:modelValue", selectedDate.value);
    } else {
        selectedDate.value = [dateStr];
        emit("update:modelValue", dateStr);
    }

    generateCalendar(currentYear.value, currentMonth.value);
    emit("selectDate", props.multiSelect ? selectedDate.value : dateStr);
};

const clearSelectedDates = () => {
    selectedDate.value = [];
    if (props.multiSelect) {
        emit("update:modelValue", []);
    } else {
        emit("update:modelValue", "");
    }
    emit("selectDate", props.multiSelect ? selectedDate.value : "");
};

const clearSingleSelectedDate = (dateToClear: string) => {
    if (!props.multiSelect) return;

    const index = selectedDate.value.indexOf(dateToClear);
    if (index > -1) {
        selectedDate.value.splice(index, 1);
        emit("update:modelValue", selectedDate.value);
        emit("selectDate", selectedDate.value);
    }
};

defineExpose({
    locateToDate,
    clearSelectedDates,
    clearSingleSelectedDate,
});

onMounted(() => {
    const today = new Date();

    if (selectedDate.value.length > 0) {
        const dateObj = new Date(selectedDate.value[0]);
        currentYear.value = dateObj.getFullYear();
        currentMonth.value = dateObj.getMonth() + 1;
    } else {
        currentYear.value = today.getFullYear();
        currentMonth.value = today.getMonth() + 1;
    }

    if (selectedDate.value.length === 0 && props.isToday) {
        const todayStr = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`;
        selectedDate.value = [todayStr];
        if (props.multiSelect) {
            emit("update:modelValue", [todayStr]);
        } else {
            emit("update:modelValue", todayStr);
        }
    }

    generateCalendar(currentYear.value, currentMonth.value);
});
</script>

<style scoped lang="scss">
.task-calendar {
    @apply bg-white p-[30rpx] shadow-[0_8rpx_24rpx_rgba(0,0,0,0.05)];
}

.calendar-header {
    @apply flex justify-between items-center mb-[20rpx];
}

.header-left {
    @apply flex items-center;
}

.back-to-today {
    @apply ml-[20rpx] px-[20rpx] py-[8rpx] bg-[#f0f2f5] text-[#606266] rounded-[30rpx] text-xs  cursor-pointer;
}

.month-year {
    @apply text-[40rpx] font-semibold text-[#2c3e50];
}

.arrow-group {
    @apply flex items-center;
}

.arrow {
    @apply w-[60rpx] h-[60rpx] flex justify-center items-center cursor-pointer rounded-full;
}

.arrow-icon {
    @apply w-[16rpx] h-[16rpx] border-solid border-[#2c3e50] border-r-[4rpx] border-b-[4rpx] border-t-0 border-l-0;
}

.arrow-left {
    transform: rotate(135deg);
}

.arrow-right {
    transform: rotate(-45deg);
}

.calendar-weekdays {
    @apply grid grid-cols-7 py-[20rpx] border-[0] border-b border-t border-solid border-[#00000008];
}

.weekday {
    @apply text-center text-[18rpx] font-medium text-[#909399];
}

.calendar-week {
    @apply grid grid-cols-7 gap-[10rpx] py-[10rpx];
    border-bottom: 1rpx solid #00000008;
}

.calendar-week:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.grid-item {
    @apply relative flex justify-center items-center h-[80rpx] w-[80rpx] rounded-full;
}

.day-number {
    @apply text-[30rpx] font-medium text-[#606266];
}

.not-current-month .day-number {
    @apply text-[#c0c4cc];
}

.today .day-number {
    @apply text-primary font-semibold;
}

.today:before {
    transform: translateX(-50%);
    @apply content-[''] absolute bottom-[10rpx] left-1/2 w-[8rpx] h-[8rpx] rounded-full bg-primary;
}

.selected {
    @apply bg-primary text-white;
}

.selected .day-number {
    @apply text-white;
}

.selected.today:before {
    @apply bg-white;
}

.grid-item.disabled {
    @apply cursor-not-allowed bg-[transparent];
}

.grid-item.disabled .day-number {
    @apply text-[#c0c4cc] line-through;
}
</style>
