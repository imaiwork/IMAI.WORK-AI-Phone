<template>
    <view class="flex flex-wrap gap-2">
        <view
            v-for="(item, index) in displayedItems"
            :key="index"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F8F9FD] border border-solid border-[#ececec] rounded-full"
            @click.stop="emit('edit', index)">
            <text class="text-[26rpx] text-[#374151]">{{ item }}</text>
            <view
                class="w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#e3e3e3]"
                @click.stop="emit('remove', index)">
                <u-icon name="close" color="#666666" size="16"></u-icon>
            </view>
        </view>

        <view
            class="inline-flex items-center justify-center px-4 py-1.5 border-2 border-dashed border-[#0065fb]/30 bg-[#F2F7FF] rounded-full"
            @click="emit('add')">
            <u-icon name="plus" color="#0065fb" size="24"></u-icon>
            <text class="text-[26rpx] text-primary ml-1 font-medium">{{ addText }}</text>
        </view>

        <view
            v-if="items.length > defaultShowCount"
            class="inline-flex items-center gap-0.5 px-3 py-1.5 bg-[#F3F4F6] rounded-full"
            @click="toggleExpand">
            <text class="text-[24rpx] text-[#9CA3AF]">
                {{ isExpanded ? "收起" : `+${items.length - defaultShowCount} 个` }}
            </text>
            <u-icon :name="isExpanded ? 'arrow-up' : 'arrow-down'" color="#9CA3AF" size="20"> </u-icon>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    items: string[];
    addText?: string;
    defaultShowCount?: number;
}>();

const emit = defineEmits(["add", "edit", "remove"]);

const defaultShowCount = computed(() => props.defaultShowCount ?? 3);
const isExpanded = ref(false);

const displayedItems = computed(() => (isExpanded.value ? props.items : props.items.slice(0, defaultShowCount.value)));

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

watch(
    () => props.items.length,
    (newLen, oldLen) => {
        if (newLen > oldLen) {
            isExpanded.value = true;
        }
    }
);
</script>
