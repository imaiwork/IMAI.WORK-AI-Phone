<template>
    <view>
        <view v-if="list.length" class="flex flex-wrap gap-[16rpx]">
            <view
                v-for="(item, index) in visibleList"
                :key="index"
                class="flex items-center gap-[12rpx] max-w-full bg-white rounded-[18rpx] px-[22rpx] py-[16rpx] border border-solid border-[#E5E9F0]"
            >
                <text
                    class="text-[26rpx] text-[#0D1117] line-clamp-1"
                    @click.stop="emit('edit', index)"
                >
                    {{ item }}
                </text>
                <view class="flex-shrink-0" @click.stop="emit('remove', index)">
                    <u-icon name="close" color="#C0C4CC" size="20" />
                </view>
            </view>
            <view
                class="flex items-center gap-[6rpx] rounded-[18rpx] px-[26rpx] py-[16rpx] bg-[#F5F9FF] border border-dashed border-[#9DC0FF]"
                @click="emit('add')"
            >
                <u-icon name="plus" color="#0065fb" size="20" />
                <text class="text-[26rpx] font-semibold text-primary">{{ addLabel }}</text>
            </view>
        </view>

        <view
            v-else
            class="rounded-[20rpx] border border-dashed border-[#D4DBE8] py-[44rpx] flex items-center justify-center"
            @click="emit('add')"
        >
            <text class="text-[26rpx] text-[#AEB6C4]">{{ emptyText }}</text>
        </view>

        <view
            v-if="list.length > COLLAPSED_COUNT"
            class="flex items-center justify-center gap-[6rpx] mt-[24rpx]"
            @click="expanded = !expanded"
        >
            <text class="text-[26rpx] text-[#9CA3AF]">
                {{ expanded ? '收起' : `查看全部 ${list.length} 条` }}
            </text>
            <u-icon :name="expanded ? 'arrow-up' : 'arrow-down'" size="18" color="#9CA3AF" />
        </view>
    </view>
</template>

<script setup lang="ts">
/** 收起时最多露几条，多的折进「查看全部」 */
const COLLAPSED_COUNT = 2

const props = withDefaults(
    defineProps<{
        list: string[]
        addLabel?: string
        emptyText?: string
    }>(),
    {
        addLabel: '添加',
        emptyText: '还没有内容，点这里添加'
    }
)

const emit = defineEmits<{
    (e: 'remove', index: number): void
    (e: 'edit', index: number): void
    (e: 'add'): void
}>()

const expanded = ref(false)

const visibleList = computed(() =>
    expanded.value ? props.list : props.list.slice(0, COLLAPSED_COUNT)
)
</script>

<style scoped></style>
