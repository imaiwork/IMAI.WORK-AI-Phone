<template>
    <view>
        <view v-if="scripts.length" class="cs-jl-list">
            <view
                v-for="(item, index) in scripts"
                :key="index"
                class="cs-jl-item"
                @click.stop="emit('edit', index, props.maxlength)">
                <text class="cs-jl-txt">{{ item }}</text>
                <view class="cs-jl-actions" @click.stop>
                    <view
                        class="cs-jl-act"
                        :class="{ 'is-disabled': index === 0 }"
                        @click.stop="index !== 0 && emit('move', index, 'up')">
                        <u-icon name="arrow-up" :color="index === 0 ? '#D6DBE5' : '#9CA3AF'" size="26"></u-icon>
                    </view>
                    <view
                        class="cs-jl-act"
                        :class="{ 'is-disabled': index === scripts.length - 1 }"
                        @click.stop="index !== scripts.length - 1 && emit('move', index, 'down')">
                        <u-icon
                            name="arrow-down"
                            :color="index === scripts.length - 1 ? '#D6DBE5' : '#9CA3AF'"
                            size="26"></u-icon>
                    </view>
                    <view class="cs-jl-act" @click.stop="emit('remove', index)">
                        <u-icon name="trash" color="#B6BDCB" size="28"></u-icon>
                    </view>
                </view>
            </view>
        </view>
        <view v-else class="cs-jl-empty">暂无话术，请在下方添加</view>

        <view class="cs-jl-add">
            <input
                v-model="inputValue"
                class="cs-jl-add-input"
                :maxlength="maxlength"
                placeholder="输入新话术内容..."
                @confirm="handleAdd" />
            <view class="cs-jl-add-btn" @click.stop="handleAdd">
                <u-icon name="plus" color="#FFFFFF" size="32"></u-icon>
            </view>
        </view>

        <view v-if="maxItems > 0" class="cs-jl-count">
            <text>已添加 {{ scripts.length }}/{{ maxItems }} 条</text>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 智能客服话术列表（社媒/微信/朋友圈/截流共用）
 * - 新增：本地输入框输入完 → emit add
 * - 编辑：点击条目 → emit edit(index, maxlength)，父级用 keyword-edit-popup 弹窗
 * - 删除：点击垃圾桶 → emit remove
 * maxlength 同时控制本地输入框和编辑弹窗的上限，由父级按块业务规则决定。
 */
const props = withDefaults(
    defineProps<{
        scripts: string[];
        maxlength?: number;
        maxItems?: number;
    }>(),
    {
        scripts: () => [],
        maxlength: 100,
        maxItems: 0,
    },
);

const emit = defineEmits<{
    (event: "add", value: string): void;
    (event: "edit", index: number, maxlength: number): void;
    (event: "remove", index: number): void;
    (event: "move", index: number, direction: "up" | "down"): void;
}>();

const inputValue = ref("");

const handleAdd = () => {
    const value = inputValue.value.trim();
    if (!value) return;
    emit("add", value);
    inputValue.value = "";
};
</script>

<style lang="scss" scoped>
.cs-jl-list {
    @apply flex flex-col gap-[16rpx] mb-[16rpx];
}

.cs-jl-item {
    @apply flex items-center gap-[8rpx] bg-[#F6F8FB] border border-solid border-[#EDF0F5] rounded-[20rpx] py-[12rpx] pl-[24rpx] pr-[8rpx] active:bg-[#EEF2F8];
}

.cs-jl-txt {
    @apply flex-1 min-w-0 text-[26rpx] leading-[1.6] text-[#1D2129] break-all py-[8rpx];
}

.cs-jl-actions {
    @apply shrink-0 flex items-center;
}

.cs-jl-act {
    @apply w-[52rpx] h-[52rpx] flex items-center justify-center rounded-[12rpx] active:bg-[#EDF0F5];

    &.is-disabled {
        @apply active:bg-[transparent];
    }
}

.cs-jl-count {
    @apply mt-[12rpx] text-right text-[22rpx] text-[#9CA3AF];
}

.cs-jl-empty {
    @apply text-center text-[24rpx] text-[#9CA3AF] font-semibold bg-[#F4F6FA] rounded-[20rpx] py-[32rpx] mb-[16rpx];
}

.cs-jl-add {
    @apply flex items-center gap-[16rpx];
}

.cs-jl-add-input {
    @apply flex-1 min-w-0 h-[80rpx] bg-white border border-solid border-[#E5E9F0] rounded-[20rpx] px-[24rpx] text-[26rpx] text-[#1D2129] box-border;
}

.cs-jl-add-btn {
    @apply w-[80rpx] h-[80rpx] shrink-0 rounded-[20rpx] bg-primary flex items-center justify-center active:opacity-80;
}
</style>
