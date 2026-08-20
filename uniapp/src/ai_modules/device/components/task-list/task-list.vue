<template>
    <view class="flex flex-col gap-y-2">
        <view v-for="(val, key) in list" :key="key">
            <task-card :item="val" @click="emit('handle-detail', val)" @edit-name="emit('update-name', val)" />
        </view>
    </view>
</template>

<script setup lang="ts">
import TaskCard from "../task-card/task-card.vue";

const props = defineProps<{
    list: any[];
}>();
const emit = defineEmits<{
    (e: "handle-detail", data: any): void;
    (e: "update-name", data: any): void;
}>();
</script>

<style scoped lang="scss">
.task-list-item {
    @apply flex gap-x-[30rpx];
}
</style>
