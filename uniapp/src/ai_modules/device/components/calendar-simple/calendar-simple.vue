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
            <swiper
                :current="swiperCurrent"
                :duration="swiperDuration"
                @animationfinish="onAnimationFinish"
                class="py-[26rpx] h-[90rpx]">
                <swiper-item>
                    <view class="flex">
                        <view
                            v-for="day in previousWeekDays"
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
                </swiper-item>
                <swiper-item>
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
                </swiper-item>
                <swiper-item>
                    <view class="flex">
                        <view
                            v-for="day in nextWeekDays"
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
                </swiper-item>
            </swiper>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";

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
    uni.$u.timeFormat(
        selectedDate.value ? new Date(selectedDate.value.replace(/-/g, "/")) : new Date(),
        "yyyy年mm月dd日"
    )
);

const weekDays = ["日", "一", "二", "三", "四", "五", "六"];

const currentDate = ref(new Date()); // Represents the Sunday of the current week
const previousWeekDays = ref<Day[]>([]);
const currentWeekDays = ref<Day[]>([]);
const nextWeekDays = ref<Day[]>([]);

const swiperCurrent = ref(1);
const isAnimating = ref(false);
const swiperDuration = computed(() => (isAnimating.value ? 0 : 300));

const generateWeek = (dateForWeek: Date): Day[] => {
    const dayArray: Day[] = [];
    const today = new Date();
    const todayDateString = uni.$u.timeFormat(today, "yyyy-mm-dd");

    const startOfWeek = new Date(dateForWeek);
    startOfWeek.setDate(dateForWeek.getDate() - dateForWeek.getDay()); // Set to Sunday

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

const updateWeeks = (baseDate: Date) => {
    currentWeekDays.value = generateWeek(baseDate);

    const prevDate = new Date(baseDate);
    prevDate.setDate(prevDate.getDate() - 7);
    previousWeekDays.value = generateWeek(prevDate);

    const nextDate = new Date(baseDate);
    nextDate.setDate(nextDate.getDate() + 7);
    nextWeekDays.value = generateWeek(nextDate);
};

onMounted(() => {
    const initialDate = props.modelValue ? new Date(props.modelValue.replace(/-/g, "/")) : new Date();
    const startOfWeek = new Date(initialDate);
    startOfWeek.setDate(initialDate.getDate() - initialDate.getDay());
    currentDate.value = startOfWeek;
    updateWeeks(startOfWeek);
});

const onAnimationFinish = async ({ detail }: any) => {
    if (detail.source !== "touch") return;
    const newIndex = detail.current;
    if (newIndex === swiperCurrent.value) return;

    isAnimating.value = true;

    if (newIndex === 2) {
        // Swiped to Next
        const newCurrentDate = new Date(currentDate.value);
        newCurrentDate.setDate(newCurrentDate.getDate() + 7);
        currentDate.value = newCurrentDate;

        previousWeekDays.value = currentWeekDays.value;
        currentWeekDays.value = nextWeekDays.value;
        swiperCurrent.value = 1;

        await nextTick();
        const newNextDate = new Date(currentDate.value);
        newNextDate.setDate(newNextDate.getDate() + 7);
        nextWeekDays.value = generateWeek(newNextDate);
    } else if (newIndex === 0) {
        // Swiped to Previous
        const newCurrentDate = new Date(currentDate.value);
        newCurrentDate.setDate(newCurrentDate.getDate() - 7);
        currentDate.value = newCurrentDate;

        nextWeekDays.value = currentWeekDays.value;
        currentWeekDays.value = previousWeekDays.value;
        swiperCurrent.value = 1;

        await nextTick();
        const newPrevDate = new Date(currentDate.value);
        newPrevDate.setDate(newPrevDate.getDate() - 7);
        previousWeekDays.value = generateWeek(newPrevDate);
    }

    isAnimating.value = false;
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
