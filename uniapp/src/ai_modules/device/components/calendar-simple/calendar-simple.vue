<template>
    <view>
        <view class="h-[100rpx] flex items-center justify-between gap-x-2">
            <view class="flex items-center gap-x-2">
                <view @click="prevWeek">
                    <u-icon name="arrow-left" size="28" color="#606266"></u-icon>
                </view>
                <view class="text-[30rpx] font-bold text-center" @click="goToToday">{{ getDisplayMonthYear }}</view>
                <view @click="nextWeek">
                    <u-icon name="arrow-right" size="28" color="#606266"></u-icon>
                </view>
            </view>
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
            <view class="py-[26rpx] h-[90rpx]">
                <view class="flex">
                    <view
                        v-for="day in currentWeekDays"
                        class="calendar-item"
                        :key="day.date"
                        @click="selectDate(day.date)">
                        <view
                            class="grid-item"
                            :class="{
                                today: day.isToday,
                                selected: day.date === selectedDate,
                                'text-[#0000004d]':
                                    new Date(day.date.replace(/-/g, '/')).getDay() === 0 ||
                                    new Date(day.date.replace(/-/g, '/')).getDay() === 6,
                            }">
                            {{ day.day }}
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";

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

const getDisplayMonthYear = computed(() => uni.$u.timeFormat(currentDate.value, "yyyy年mm月"));

const weekDays = ["日", "一", "二", "三", "四", "五", "六"];

const currentDate = ref(new Date());
const currentWeekDays = ref<Day[]>([]);

const generateWeek = (dateForWeek: Date): Day[] => {
    const dayArray: Day[] = [];
    const today = new Date();
    const todayDateString = uni.$u.timeFormat(today, "yyyy-mm-dd");

    const startOfWeek = new Date(dateForWeek);
    startOfWeek.setDate(dateForWeek.getDate() - dateForWeek.getDay());

    for (let i = 0; i < 7; i++) {
        const current = new Date(startOfWeek);
        current.setDate(startOfWeek.getDate() + i);
        const dateString = uni.$u.timeFormat(current, "yyyy-mm-dd");
        dayArray.push({
            date: dateString,
            day: current.getDate(),
            isToday: dateString === todayDateString,
        });
    }
    return dayArray;
};

const updateWeek = (baseDate: Date) => {
    currentWeekDays.value = generateWeek(baseDate);
};

onMounted(() => {
    const initialDate = props.modelValue ? new Date(props.modelValue.replace(/-/g, "/")) : new Date();
    currentDate.value = initialDate;
    updateWeek(initialDate);
});

const prevWeek = () => {
    const newDate = new Date(currentDate.value);
    newDate.setDate(newDate.getDate() - 7);
    currentDate.value = newDate;
    updateWeek(newDate);
};

const nextWeek = () => {
    const newDate = new Date(currentDate.value);
    newDate.setDate(newDate.getDate() + 7);
    currentDate.value = newDate;
    updateWeek(newDate);
};

const goToToday = () => {
    const today = new Date();
    currentDate.value = today;
    updateWeek(today);
    selectedDate.value = uni.$u.timeFormat(today, "yyyy-mm-dd");
    emit("change", uni.$u.timeFormat(today, "yyyy-mm-dd"));
};

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
