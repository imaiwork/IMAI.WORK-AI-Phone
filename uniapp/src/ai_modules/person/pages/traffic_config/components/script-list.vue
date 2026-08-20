<template>
    <view class="flex flex-col gap-2">
        <view
            v-for="(item, index) in displayedItems"
            :key="index"
            class="flex items-start justify-between p-3.5 bg-[#F8F9FD] border border-solid border-[#ececec] rounded-[20rpx]">
            <textarea
                v-model="displayedItems[index]"
                class="text-[28rpx] text-[#374151] leading-relaxed flex-1 pr-4"
                v-if="type === 'textarea'" />
            <input
                v-model="displayedItems[index]"
                class="text-[28rpx] text-[#374151] leading-relaxed flex-1 pr-4"
                v-if="type === 'text'" />
            <view
                class="w-[40rpx] h-[40rpx] flex-shrink-0 flex items-center justify-center rounded-full bg-[#e3e3e3] mt-0.5"
                @click.stop="emit('remove', index)">
                <u-icon name="close" color="#666666" size="20"></u-icon>
            </view>
        </view>

        <view
            v-if="items.length > defaultShowCount"
            class="flex items-center justify-center py-2 gap-1"
            @click="toggleExpand">
            <text class="text-[#9CA3AF]">
                {{ isExpanded ? "收起" : `查看全部 ${items.length} 条` }}
            </text>
            <u-icon :name="isExpanded ? 'arrow-up' : 'arrow-down'" color="#9CA3AF" size="22"> </u-icon>
        </view>

        <view
            class="flex items-center justify-center w-full py-3 border-2 border-dashed border-[#0065fb]/30 bg-[#F2F7FF] rounded-[20rpx] transition-colors"
            @click="emit('add')">
            <u-icon name="plus" color="#0065fb" size="28"></u-icon>
            <text class="text-[28rpx] text-primary ml-1 font-medium">{{ addText }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        items: string[];
        addText?: string;
        defaultShowCount?: number;
        type?: "text" | "textarea";
    }>(),
    {
        type: "text",
    },
);

const emit = defineEmits(["add", "edit", "remove"]);

const defaultShowCount = computed(() => props.defaultShowCount ?? 2);
const isExpanded = ref(false);

const displayedItems = computed(() => (isExpanded.value ? props.items : props.items.slice(0, defaultShowCount.value)));

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

// 当新增数据时，自动展开
watch(
    () => props.items.length,
    (newLen, oldLen) => {
        if (newLen > oldLen) {
            isExpanded.value = true;
        }
    },
);
</script>
