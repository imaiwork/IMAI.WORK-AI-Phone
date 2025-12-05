<template>
    <div class="grid grid-cols-5 gap-2">
        <div class="flex flex-col items-center w-full" v-for="(item, index) in dateList" :key="index">
            <div>第{{ item.order_day }}天</div>
            <div class="flex flex-col gap-2 mt-1">
                <template v-if="item.timeList.length < 3">
                    <div v-for="(value, index) in item.timeList" :key="index">
                        <div class="text-xs text-primary underline cursor-pointer" @click="emit('edit', value.id)">
                            {{ value.push_time }}
                        </div>
                    </div>
                </template>
                <template v-else>
                    <ElPopover width="200px">
                        <template #reference>
                            <div class="underline text-primary text-xs cursor-pointer">
                                {{ item.timeList.length }}条内容
                            </div>
                        </template>
                        <div class="flex flex-col gap-2">
                            <div v-for="(value, index) in item.timeList" :key="index">
                                <div class="text-xs text-primary underline mb-2">
                                    {{ value.push_time }}
                                </div>
                                <FileItem :file="value" @edit="emit('edit', value.id)" />
                            </div>
                        </div>
                    </ElPopover>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import FileItem from "./file-item.vue";

const props = defineProps<{
    dateList: Record<string, any>[];
}>();

const emit = defineEmits<{
    (e: "edit", data: any): void;
}>();
</script>

<style scoped></style>
