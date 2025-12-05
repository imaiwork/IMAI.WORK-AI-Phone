<template>
    <div class="bg-white rounded-lg p-2 w-full h-full flex flex-col">
        <div class="flex items-center gap-2">
            <Icon name="local-icon-mini_program_fill" color="var(--color-primary)" :size="24"></Icon>
            <span class="line-clamp-1">{{ title }}</span>
        </div>
        <div class="grow min-h-0 overflow-hidden mt-2">
            <img
                :src="pic"
                class="w-full h-full object-cover rounded hover:scale-105 transition-all duration-300 max-h-[100px]" />
        </div>
        <div class="flex items-center gap-2 my-2">
            <Icon name="el-icon-Link" color="var(--color-primary)" :size="16"></Icon>
            <span class="line-clamp-1 text-xs text-[#545454]">
                {{ link }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    pic: string;
    link: string;
}>();
</script>

<style scoped></style>
