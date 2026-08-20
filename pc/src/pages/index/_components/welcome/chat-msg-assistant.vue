<template>
    <div class="msg assistant flex w-full max-w-full items-start gap-2.5">
        <div
            v-if="showAvatar"
            class="avatar assistant-avatar flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white shadow-[0_2px_6px_rgba(0,0,0,0.08)]"
            :style="avatarBg ? { background: avatarBg } : undefined">
            <Icon v-if="avatarIcon" :name="avatarIcon" :size="22" />
            <span v-else>{{ avatarLabel }}</span>
        </div>
        <div
            class="bubble min-w-0 w-full flex-1 rounded-[14px] border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        showAvatar?: boolean
        avatarBg?: string
        avatarIcon?: string
        avatarLabel?: string
    }>(),
    {
        showAvatar: true,
        avatarBg: '',
        avatarIcon: '',
        avatarLabel: 'AI'
    }
)
</script>

<style lang="scss" scoped>
.assistant-avatar {
    :deep(svg) {
        @apply h-[22px] w-[22px];
    }
}
</style>
