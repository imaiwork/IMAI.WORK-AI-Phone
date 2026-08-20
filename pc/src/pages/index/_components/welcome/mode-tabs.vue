<template>
    <div
        class="mode-tabs flex flex-wrap items-center gap-2"
        :class="variant === 'inside' ? 'inside' : 'outside justify-center'">
        <div
            v-for="tab in tabs"
            :key="tab.key"
            class="mode-tab"
            :class="[
                tab.key === active ? 'active' : '',
                variant === 'inside' ? 'is-inside' : '',
            ]"
            @click="$emit('change', tab.key)">
            <span class="m-ic">
                <Icon :name="tab.icon" :size="variant === 'inside' ? 14 : 16" />
            </span>
            <span>{{ tab.label }}</span>
            <span v-if="tab.badge" class="mt-badge" :class="tab.badge.type">{{ tab.badge.text }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
export interface ModeTabItem {
    key: string
    label: string
    icon: string
    badge?: { type: string; text: string }
}

withDefaults(
    defineProps<{
        tabs: ModeTabItem[]
        active: string
        variant?: 'outside' | 'inside'
    }>(),
    {
        variant: 'outside'
    }
)

defineEmits<{
    (e: 'change', key: string): void
}>()
</script>

<style lang="scss" scoped>
.mode-tabs.inside {
    @apply border-b border-[#f3f5f9] pb-1;
}

.mode-tab {
    @apply flex cursor-pointer items-center gap-1.5 rounded-[20px] border border-[#ebedf0] bg-white px-4 py-2 text-[13px] text-[#6b7280] transition-all duration-150 ease-[ease];

    &:hover {
        @apply border-[#93c5fd] text-[#2563eb];
    }

    &.active {
        @apply border-[#93c5fd] bg-gradient-to-br from-[#ecf2ff] to-[#dbeafe] font-semibold text-[#2563eb];
    }

    &.is-inside {
        @apply rounded-2xl px-3 py-[5px] text-xs;
    }

    .m-ic {
        @apply inline-flex h-4 w-4 items-center justify-center;
    }

    &.is-inside .m-ic {
        @apply h-3.5 w-3.5;
    }

    .mt-badge {
        @apply ml-0.5 -translate-y-px rounded-lg px-1.5 py-px text-[9px] font-bold text-white;

        &.new {
            @apply bg-gradient-to-br from-[#4f8ef7] to-[#2563eb];
        }

        &.hot {
            @apply bg-gradient-to-br from-[#fb923c] to-[#ef4444] px-1;
        }
    }
}
</style>
