<template>
    <view class="flex flex-col gap-y-2">
        <view v-for="item in taskGroupByDate" :key="item.status" class="task-list-item">
            <view class="flex-shrink-0 text-[#00000066] font-bold"> {{ item.title }} </view>
            <view class="flex flex-col gap-y-2 flex-1">
                <view v-for="(val, key) in item.list" :key="key">
                    <view class="text-xs mb-2 mt-[4rpx] font-bold">{{ val.time }}</view>
                    <task-card :item="val" @click="emit('handle-detail', val)" @edit-name="emit('update-name', val)" />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { setFormData } from "@/utils/util";
import TaskCard from "../task-card/task-card.vue";

const props = defineProps<{
    list: any[];
}>();
const emit = defineEmits<{
    (e: "handle-detail", data: any): void;
    (e: "update-name", data: any): void;
}>();

const editData = reactive({
    id: "",
    sub_task_id: "",
    source: "",
    name: "",
});
const shoeEditNamePop = ref(false);

const taskGroupByDate = computed(() => {
    const periodMap = { morning: "上午", afternoon: "下午" };

    return props.list
        .sort((a, b) => a.start_time.localeCompare(b.start_time))
        .reduce((acc, task) => {
            const isDateFull = task.start_time.length != 5;
            const hour = new Date(!isDateFull ? `2000-01-01 ${task.start_time}` : task.start_time).getHours();
            const periodKey = hour < 12 ? "morning" : "afternoon";
            let period = acc.find((g: any) => g.title === periodMap[periodKey]);

            if (!period) {
                period = { title: periodMap[periodKey], list: [] };
                acc.push(period);
            }
            if (isDateFull) {
                task.time = `${task.start_time.split(" ")[1]}-${task.end_time.split(" ")[1]}`;
            } else {
                task.time = `${task.start_time}-${task.end_time}`;
            }

            period.list.push(task);

            return acc;
        }, []);
});
</script>

<style scoped lang="scss">
.task-list-item {
    @apply flex gap-x-[30rpx];
}
</style>
