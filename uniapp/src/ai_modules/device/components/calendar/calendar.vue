<template>
    <view class="w-full">
        <view class="bg-white px-[32rpx] pt-[22rpx] pb-[20rpx] shadow-[0_8rpx_32rpx_rgba(0,0,0,0.08)]">
            <view class="relative flex items-center justify-center mb-[16rpx]">
                <view class="absolute left-0 flex items-center gap-[8rpx]">
                    <view
                        class="w-[52rpx] h-[52rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center active:bg-[#E5E7EB]"
                        @click="prevMonth">
                        <u-icon name="arrow-left" color="#6B7280" size="18" />
                    </view>
                </view>
                <view class="text-xs font-semibold text-[#9CA3AF]"> {{ currentYear }}年{{ currentMonth }}月 </view>
                <view class="absolute right-0 flex items-center gap-[8rpx]">
                    <view
                        class="text-[22rpx] text-[#2B6EFF] bg-[#EFF6FF] px-[16rpx] py-[6rpx] rounded-full"
                        @click="backToToday(true)"
                        >今天</view
                    >
                    <view
                        class="w-[52rpx] h-[52rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center active:bg-[#E5E7EB]"
                        @click="nextMonth">
                        <u-icon name="arrow-right" color="#6B7280" size="18" />
                    </view>
                </view>
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
                        <view class="day-cell">
                            <text class="week-label">{{ weekDays[dayIndex] }}</text>
                            <text class="day-number">{{ day.day }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <view class="flex items-center justify-center pt-[12rpx] pb-[2rpx] active:opacity-60" @click="toggleExpand">
                <view class="transition-transform duration-300" :class="isExpanded ? 'rotate-180' : 'rotate-0'">
                    <u-icon name="arrow-down" color="#9CA3AF" size="22" />
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

const weekDays = ["周一", "周二", "周三", "周四", "周五", "周六", "周日"];
const days = ref<Day[]>([]);
const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth() + 1);
const selectedDate = ref<string[]>([]);

const isExpanded = ref(false);

const ROW_HEIGHT_PX = 52;

const gridStyle = computed(() => {
    const visibleRows = isExpanded.value ? weeks.value.length + 1 : 1;
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
    const pad = (value: number) => String(value).padStart(2, "0");
    return `${year}-${pad(month)}-${pad(day)}`;
};

const normalizeInputDate = (dateStr: string): string => {
    const normalized = dateStr.replace(/\//g, "-");
    const parts = normalized.split("-").map(Number);
    if (parts.length < 3 || parts.some(isNaN)) return normalized;
    return toDateStr(parts[0], parts[1], parts[2]);
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
    { deep: true, immediate: true },
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

    const firstDay = (new Date(year, month - 1, 1).getDay() + 6) % 7;
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
    @apply text-sm font-bold mt-[4rpx] text-[#374151];
}

.day-cell {
    @apply w-full mx-[4rpx] py-[10rpx] rounded-[24rpx] flex flex-col items-center justify-center;
}

.week-label {
    @apply text-[20rpx] font-medium text-[#9CA3AF];
}

.today .day-cell {
    @apply bg-[#EFF6FF];
}

.today .day-number {
    @apply text-[#2B6EFF];
}

.selected .day-cell {
    @apply bg-[#2B6EFF];
}

.selected .week-label {
    @apply text-[#DBEAFE];
}

.selected .day-number {
    @apply text-white;
}

.not-current-month .day-number {
    @apply text-[#d1d5db];
}

.disabled .day-number {
    @apply text-[#d1d5db];
}

.not-current-month .week-label,
.disabled .week-label {
    @apply text-[#D1D5DB];
}

.selected.today .day-cell {
    @apply bg-[#2B6EFF];
}
</style>
