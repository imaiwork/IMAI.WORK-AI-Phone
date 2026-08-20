<template>
    <ElEmpty :description="description" :image-size="80">
        <slot name="action" />
    </ElEmpty>
</template>

<script setup lang="ts">
defineProps<{ description: string }>()
</script>
