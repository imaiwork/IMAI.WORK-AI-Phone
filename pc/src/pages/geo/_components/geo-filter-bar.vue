<template>
    <div class="flex items-center gap-3 flex-wrap">
        <ElDatePicker
            v-if="mode === 'range'"
            class="!w-[240px] !grow-0 shrink-0"
            :model-value="range"
            type="daterange"
            value-format="YYYY-MM-DD"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            @update:model-value="onRange" />
        <ElDatePicker
            v-else
            class="!w-[150px] !grow-0 shrink-0"
            :model-value="date"
            type="date"
            value-format="YYYY-MM-DD"
            placeholder="选择日期"
            @update:model-value="onDate" />
        <ElSelect :model-value="engine" placeholder="全部AI平台" clearable class="!w-[150px] shrink-0" @update:model-value="onEngine" @change="emit('change')">
            <ElOption
                v-for="e in engines"
                :key="e.key"
                :label="e.available ? e.label : `${e.label}(未接入)`"
                :value="e.key"
                :disabled="!e.available" />
        </ElSelect>
        <ElSelect :model-value="topicId" placeholder="全部话题" clearable class="!w-[170px] shrink-0" @update:model-value="onTopic" @change="emit('change')">
            <ElOption v-for="t in topics" :key="t.id" :label="t.name" :value="t.id" />
        </ElSelect>
        <slot />
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        mode?: 'date' | 'range'
        date?: string
        range?: [string, string] | null
        engine?: string
        topicId?: string | number
        engines: any[]
        topics: any[]
    }>(),
    { mode: 'date', date: '', engine: '', topicId: '' }
)

const emit = defineEmits<{
    'update:date': [string]
    'update:range': [[string, string] | null]
    'update:engine': [string]
    'update:topicId': [string | number]
    change: []
}>()

const onDate = (v: string) => {
    emit('update:date', v || '')
    emit('change')
}
const onRange = (v: [string, string] | null) => {
    emit('update:range', v)
    emit('change')
}
const onEngine = (v: string) => emit('update:engine', v || '')
const onTopic = (v: string | number) => emit('update:topicId', v || '')
</script>
