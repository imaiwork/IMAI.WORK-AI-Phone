<template>
    <div class="h-full rounded-xl p-4 bg-white flex gap-2 items-center justify-between">
        <div>
            <div class="font-bold break-all">{{ name }}</div>
        </div>
        <Icon name="local-icon-file_fill" color="#80B8F8" :size="iconSize" />
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        name: string;
        url: string;
        iconSize?: number;
    }>(),
    {
        iconSize: 32,
    }
);
</script>

<style scoped></style>
