<template>
    <div class="flex items-center gap-1 border-b border-br" role="tablist">
        <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            role="tab"
            class="h-10 px-3 text-sm text-slate-500 border-b-2 border-transparent -mb-px transition-colors hover:text-slate-800"
            :class="{ '!text-primary font-semibold !border-primary': modelValue === t.key }"
            :aria-selected="modelValue === t.key"
            @click="$emit('update:modelValue', t.key)">
            {{ t.label }}
        </button>
        <div class="flex-1"></div>
        <slot />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    tabs: Array<{ key: string; label: string }>
    modelValue: string
}>()
defineEmits<{ 'update:modelValue': [string] }>()
</script>
