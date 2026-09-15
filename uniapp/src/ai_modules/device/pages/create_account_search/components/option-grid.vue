<template>
    <view class="grid gap-[12rpx]" :class="gridClass">
        <view
            v-for="item in options"
            :key="item.value"
            class="h-[76rpx] rounded-[18rpx] flex items-center justify-center border border-solid transition-all duration-200"
            :class="
                item.value === modelValue
                    ? 'bg-[#EBF2FF] border-[#9DC0FF]'
                    : 'bg-[#F4F6FB] border-[transparent]'
            "
            @click="emit('update:modelValue', item.value)"
        >
            <text
                class="text-[26rpx]"
                :class="item.value === modelValue ? 'text-primary font-semibold' : 'text-[#86909C]'"
            >
                {{ item.label }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        options: { value: any; label: string }[]
        modelValue: any
        columns?: number
    }>(),
    {
        columns: 5
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
}>()

/**
 * 项数固定的行用等分网格：靠 min-width 硬塞会在 375px（更别说 360px 安卓）
 * 上掉出「4 + 1 孤儿」这种排版。grid-cols-N 是静态类名，小程序才扫得到。
 */
const gridClass = computed(() => {
    const map: Record<number, string> = {
        2: 'grid-cols-2',
        3: 'grid-cols-3',
        4: 'grid-cols-4',
        5: 'grid-cols-5'
    }
    return map[props.columns] ?? 'grid-cols-5'
})
</script>

<style scoped></style>
