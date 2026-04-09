<template>
    <view class="w-full">
        <view class="bg-white px-[30rpx] py-[30rpx] shadow-[0_8rpx_24rpx_rgba(0,0,0,0.05)]">
            <view class="flex items-center justify-between mb-[24rpx]">
                <view class="flex items-center gap-[16rpx]">
                    <view class="text-[32rpx] font-semibold text-[#111827]"
                        >{{ currentYear }}年 {{ currentMonth }}月</view
                    >
                    <view
                        class="text-[24rpx] text-[#0065fb] bg-[#ebf2ff] px-[16rpx] py-[4rpx] rounded-full"
                        @click="backToToday(true)"
                        >今天</view
                    >
                </view>
                <view class="flex items-center gap-[8rpx]">
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center active:bg-[#e5e7eb]"
                        @click="prevMonth">
                        <u-icon name="arrow-left" color="#6b7280" size="20" />
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#f3f4f6] flex items-center justify-center active:bg-[#e5e7eb]"
                        @click="nextMonth">
                        <u-icon name="arrow-right" color="#6b7280" size="20" />
                    </view>
                </view>
            </view>

            <view class="flex mb-[8rpx]">
                <view
                    class="flex-1 text-center text-[24rpx] text-[#9ca3af] py-[8rpx]"
                    v-for="day in weekDays"
                    :key="day"
                    >{{ day }}</view
                >
            </view>

            <view class="overflow-hidden transition-all duration-300 ease-in-out" :style="gridStyle">
                <view
                    class="flex"
                    v-for="(week, weekIndex) in weeks"
                    :key="weekIndex"
                    v-show="isExpanded || weekIndex === activeWeekIndex">
                    <view
                        class="flex-1 flex items-center justify-center py-[10rpx]"
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

            <view class="flex items-center justify-center pt-[16rpx] pb-[4rpx] active:opacity-60" @click="toggleExpand">
                <view class="transition-transform duration-300" :class="isExpanded ? 'rotate-180' : 'rotate-0'">
                    <u-icon name="arrow-down" color="#9CA3AF" size="24" />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { isIOS } from "@/utils/client";

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

const isExpanded = ref(false);

const ROW_HEIGHT_PX = 42;

const gridStyle = computed(() => {
    const visibleRows = isExpanded.value ? weeks.value.length : 1;
    return { maxHeight: `${visibleRows * ROW_HEIGHT_PX}px` };
});

const activeWeekIndex = computed(() => {
    const target =
        selectedDate.value[0] || toDateStr(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
    const idx = weeks.value.findIndex((week) => week.some((d) => d.date === target));
    return idx >= 0 ? idx : 0;
});

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const toDateStr = (year: number, month: number, day: number): string => {
    return `${year}-${month}-${day}`;
};

const normalizeInputDate = (dateStr: string): string => {
    return dateStr.replace(/\//g, "-");
};

const toOutputDate = (dateStr: string): string => {
    return isIOS() ? dateStr.replace(/-/g, "/") : dateStr;
};

watch(
    () => props.modelValue,
    (newValue) => {
        let newSelected: string[] = [];
        if (Array.isArray(newValue)) {
            newSelected = newValue.map(normalizeInputDate);
        } else if (typeof newValue === "string" && newValue) {
            newSelected = [normalizeInputDate(newValue)];
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
    const todayDate = toDateStr(today.getFullYear(), today.getMonth() + 1, today.getDate());

    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    const calendarDays: Day[] = [];

    const prevMonthEndDate = new Date(year, month - 1, 0);
    const prevMonthLastDay = prevMonthEndDate.getDate();
    const prevMonthYear = prevMonthEndDate.getFullYear();
    const prevMonth = prevMonthEndDate.getMonth() + 1;
    for (let i = firstDay; i > 0; i--) {
        const day = prevMonthLastDay - i + 1;
        const dateStr = toDateStr(prevMonthYear, prevMonth, day);
        const dateObj = new Date(prevMonthYear, prevMonth - 1, day);
        calendarDays.push({
            date: dateStr,
            day,
            isCurrentMonth: false,
            isToday: false,
            isDisabled: disabledDatesSet.value.has(dateStr) || props.disabledDateMethod(dateObj),
        });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = toDateStr(year, month, i);
        const dateObj = new Date(year, month - 1, i);
        calendarDays.push({
            date: dateStr,
            day: i,
            isCurrentMonth: true,
            isToday: dateStr === todayDate,
            isDisabled: disabledDatesSet.value.has(dateStr) || props.disabledDateMethod(dateObj),
        });
    }

    const gridCells = 35;
    if (calendarDays.length < gridCells) {
        const nextMonthStartDate = new Date(year, month, 1);
        const nextMonthYear = nextMonthStartDate.getFullYear();
        const nextMonth = nextMonthStartDate.getMonth() + 1;
        let nextDay = 1;
        while (calendarDays.length < gridCells) {
            const dateStr = toDateStr(nextMonthYear, nextMonth, nextDay);
            const dateObj = new Date(nextMonthYear, nextMonth - 1, nextDay);
            calendarDays.push({
                date: dateStr,
                day: nextDay,
                isCurrentMonth: false,
                isToday: false,
                isDisabled: disabledDatesSet.value.has(dateStr) || props.disabledDateMethod(dateObj),
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
        emit("update:modelValue", selectedDate.value.map(toOutputDate));
    } else {
        selectedDate.value = [dateStr];
        emit("update:modelValue", toOutputDate(dateStr));
    }

    if (!day.isCurrentMonth) {
        const [year, month] = dateStr.split("-").map(Number);
        currentYear.value = year;
        currentMonth.value = month;
        generateCalendar(currentYear.value, currentMonth.value);
    }

    emit("selectDate", props.multiSelect ? selectedDate.value.map(toOutputDate) : toOutputDate(dateStr));
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
        const todayStr = toDateStr(today.getFullYear(), today.getMonth() + 1, today.getDate());
        if (props.multiSelect) {
            if (!selectedDate.value.includes(todayStr)) {
                selectedDate.value.push(todayStr);
            }
            emit("update:modelValue", selectedDate.value.map(toOutputDate));
        } else {
            selectedDate.value = [todayStr];
            emit("update:modelValue", toOutputDate(todayStr));
        }
        emit("selectDate", props.multiSelect ? selectedDate.value.map(toOutputDate) : toOutputDate(todayStr));
    }
    generateCalendar(currentYear.value, currentMonth.value);
};

const locateToDate = (date: string) => {
    const normalized = normalizeInputDate(date);
    const parts = normalized.split("-").map(Number);
    if (parts.length < 3 || parts.some(isNaN)) return;
    const [y, m, d] = parts;
    const dateObj = new Date(y, m - 1, d);
    if (isNaN(dateObj.getTime())) return;
    currentYear.value = y;
    currentMonth.value = m;
    const dateStr = toDateStr(y, m, d);

    if (props.multiSelect) {
        if (!selectedDate.value.includes(dateStr)) selectedDate.value.push(dateStr);
        emit("update:modelValue", selectedDate.value.map(toOutputDate));
    } else {
        selectedDate.value = [dateStr];
        emit("update:modelValue", toOutputDate(dateStr));
    }

    generateCalendar(currentYear.value, currentMonth.value);
    emit("selectDate", props.multiSelect ? selectedDate.value.map(toOutputDate) : toOutputDate(dateStr));
};

const clearSelectedDates = () => {
    selectedDate.value = [];
    emit("update:modelValue", props.multiSelect ? [] : "");
    emit("selectDate", props.multiSelect ? [] : "");
};

const clearSingleSelectedDate = (dateToClear: string) => {
    if (!props.multiSelect) return;
    const normalized = normalizeInputDate(dateToClear);
    const index = selectedDate.value.indexOf(normalized);
    if (index > -1) {
        selectedDate.value.splice(index, 1);
        emit("update:modelValue", selectedDate.value.map(toOutputDate));
        emit("selectDate", selectedDate.value.map(toOutputDate));
    }
};

defineExpose({ locateToDate, clearSelectedDates, clearSingleSelectedDate });

onMounted(() => {
    const today = new Date();
    if (selectedDate.value.length > 0) {
        const parts = selectedDate.value[0].split("-").map(Number);
        currentYear.value = parts[0];
        currentMonth.value = parts[1];
    } else {
        currentYear.value = today.getFullYear();
        currentMonth.value = today.getMonth() + 1;
    }

    if (selectedDate.value.length === 0 && props.isToday) {
        const todayStr = toDateStr(today.getFullYear(), today.getMonth() + 1, today.getDate());
        selectedDate.value = [todayStr];
        emit("update:modelValue", props.multiSelect ? [toOutputDate(todayStr)] : toOutputDate(todayStr));
    }

    generateCalendar(currentYear.value, currentMonth.value);
});
</script>

<style scoped lang="scss">
.arrow-icon {
    @apply w-[16rpx] h-[16rpx] border-t-[3rpx] border-solid border-[#6b7280] border-r-[3rpx];

    &.arrow-left {
        transform: rotate(-135deg) translate(3rpx, -3rpx);
    }

    &.arrow-right {
        transform: rotate(45deg) translate(-3rpx, 3rpx);
    }
}

.day-number {
    @apply w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center text-[28rpx] text-[#171717];
}

.today .day-number {
    @apply text-primary font-semibold border-[2rpx] border-solid border-primary;
}

.selected .day-number {
    @apply bg-primary text-white font-semibold border-none;
}

.not-current-month .day-number {
    @apply text-[#d1d5db];
}

.disabled .day-number {
    @apply text-[#d1d5db];
}

.selected.today .day-number {
    @apply bg-primary text-white border-[none];
}
</style>
