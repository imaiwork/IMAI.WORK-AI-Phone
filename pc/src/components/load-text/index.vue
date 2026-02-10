<template>
    <div class="py-6 text-center">
        <span v-if="!isLoad" class="text-[11px] text-[#94A3B8] font-medium uppercase tracking-widest">{{ text }}</span>
        <div v-else class="flex items-center justify-center gap-2 text-primary">
            <span class="text-xs font-medium">正在加载更多...</span>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        isLoad: boolean;
        text?: string;
        loadingText?: string;
    }>(),
    {
        isLoad: false,
        text: "暂无更多了~",
        loadingText: "正在加载更多...",
    }
);
</script>
