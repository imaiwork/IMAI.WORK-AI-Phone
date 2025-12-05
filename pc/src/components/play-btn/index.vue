<template>
    <div
        class="w-full h-full rounded-full flex items-center justify-center bg-[#ffffff33] cursor-pointer"
        style="backdrop-filter: blur(5px)">
        <Icon name="local-icon-play2" :size="iconSize"></Icon>
    </div>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        iconSize?: number;
    }>(),
    {
        iconSize: 40,
    }
);
</script>

<style scoped></style>
