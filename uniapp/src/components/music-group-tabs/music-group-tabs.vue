<template>
    <!-- 分组 tab：0 = 全部。分组由后台维护，没有可用曲目的分组不会下发 -->
    <scroll-view v-if="tabs.length > 1" scroll-x class="w-full" :show-scrollbar="false">
        <view class="flex items-center gap-[12rpx] pr-[8rpx]">
            <view
                v-for="tab in tabs"
                :key="tab.id"
                class="mg-tab"
                :class="Number(modelValue) === tab.id ? 'mg-tab--on' : ''"
                @click="emit('update:modelValue', tab.id)">
                <text>{{ tab.name }}</text>
            </view>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
/** 选曲页的分组筛选条：全部 + 各分组 */
const props = withDefaults(
    defineProps<{
        groups?: Array<{ id: number | string; name: string }>;
        /** 0 = 全部 */
        modelValue?: number;
    }>(),
    {
        groups: () => [],
        modelValue: 0,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: number): void;
}>();

const tabs = computed(() => [
    { id: 0, name: "全部" },
    ...props.groups
        .map((item) => ({ id: Number(item.id) || 0, name: String(item.name || "") }))
        .filter((item) => item.id > 0 && item.name !== ""),
]);

// 后台删了分组后重新进入选曲页，选中项可能已不存在，回落到「全部」避免空白
watch(
    () => props.groups,
    () => {
        const current = Number(props.modelValue) || 0;
        if (current === 0) return;
        if (!props.groups.some((item) => Number(item.id) === current)) {
            emit("update:modelValue", 0);
        }
    },
    { deep: true },
);
</script>

<style scoped lang="scss">
.mg-tab {
    @apply shrink-0 px-[22rpx] py-[10rpx] rounded-full bg-[#F3F4F6] text-[24rpx] text-[#526B88];
}
.mg-tab--on {
    @apply bg-[#E8F1FF] text-[#075ECF] font-bold;
}
</style>
