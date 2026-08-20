<template>
    <popup-bottom
        v-model="show"
        height="72%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[40rpx] py-[32rpx] border-b-[2rpx] border-[#f3f4f6] flex items-center justify-between">
                <view>
                    <text class="block text-xl font-bold text-[#1f2937]">原视频文案</text>
                    <text class="block mt-[4rpx] text-xs text-[#9ca3af]"> AI 从原视频提取的口播内容 </text>
                </view>
                <view
                    class="w-[64rpx] h-[64rpx] rounded-full bg-[#f3f4f6] text-[#6b7280] flex items-center justify-center text-[44rpx] leading-none"
                    @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[40rpx] py-[32rpx] pb-[56rpx]">
                    <text class="block w-full break-all text-sm text-[#374151] leading-[56rpx] whitespace-pre-line">
                        {{ script }}
                    </text>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    script: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(v) {
        emit("update:modelValue", v);
    },
});
</script>
