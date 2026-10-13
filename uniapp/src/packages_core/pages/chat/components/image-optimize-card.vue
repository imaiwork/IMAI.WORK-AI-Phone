<template>
    <view class="bg-white rounded-[24rpx] p-[24rpx]">
        <view class="flex items-center gap-x-[10rpx] mb-[14rpx]">
            <u-icon name="star-fill" :size="28" color="#F59E0B"></u-icon>
            <text class="text-[24rpx] text-[#6B7280]">已优化的提示词(可编辑)</text>
        </view>
        <textarea
            class="opt-ta w-full min-h-[220rpx] box-border bg-white border border-solid border-[#E5E7EB] rounded-[24rpx] px-[26rpx] py-[22rpx] text-[26rpx] text-[#374151] leading-relaxed"
            :value="text"
            :disabled="busy"
            :maxlength="-1"
            :auto-height="true"
            :show-confirm-bar="false"
            placeholder="优化后的提示词"
            @input="onInput" />
        <view class="flex flex-wrap gap-[16rpx] mt-[16rpx]">
            <view
                class="opt-btn"
                hover-class="opacity-70"
                :hover-stay-time="80"
                @click="onRegen">
                <u-icon name="reload" :size="24" color="#4B5563"></u-icon>
                <text>{{ regenerating ? "优化中…" : "重新生成" }}</text>
            </view>
            <view
                class="opt-btn opt-btn--primary"
                hover-class="opacity-80"
                :hover-stay-time="80"
                @click="onConfirm">
                <u-icon name="photo" :size="24" color="#FFFFFF"></u-icon>
                <text>直接用此内容生成</text>
            </view>
            <view
                class="opt-btn opt-btn--muted"
                hover-class="opacity-70"
                :hover-stay-time="80"
                @click="emit('cancel')">
                <text>取消</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        text?: string;
        regenerating?: boolean;
        busy?: boolean;
    }>(),
    {
        text: "",
        regenerating: false,
        busy: false,
    },
);

const emit = defineEmits<{
    (e: "update:text", v: string): void;
    (e: "regen"): void;
    (e: "confirm", text: string): void;
    (e: "cancel"): void;
}>();

const onInput = (e: any) => {
    emit("update:text", String(e?.detail?.value ?? ""));
};

const onRegen = () => {
    if (props.busy || props.regenerating) return;
    emit("regen");
};

const onConfirm = () => {
    if (props.busy || props.regenerating) return;
    const val = String(props.text || "").trim();
    if (!val) {
        uni.$u.toast("请输入提示词");
        return;
    }
    emit("confirm", val);
};
</script>

<style lang="scss" scoped>
.opt-btn {
    @apply inline-flex items-center gap-x-[10rpx] text-[26rpx] text-[#4B5563] bg-white border border-solid border-[#E5E7EB] rounded-[20rpx] px-[24rpx] py-[14rpx];
}
.opt-btn--primary {
    @apply text-white bg-primary border-primary;
}
.opt-btn--muted {
    @apply text-[#9CA3AF] bg-[#F3F4F6] border-[transparent];
}
</style>
