<template>
    <u-popup
        v-model="show"
        mode="center"
        border-radius="20"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[20rpx] pt-[94rpx] px-[62rpx] pb-[60rpx]">
            <view class="text-[40rpx] font-bold text-center">{{ title }}</view>
            <view class="text-[30rpx] mt-[40rpx]"> {{ desc }} </view>
            <view
                v-if="seekText"
                class="mt-[50rpx] bg-black text-white text-[30rpx] font-bold rounded-[20rpx] h-[90rpx] flex items-center justify-center"
                @click="emit('seek')">
                {{ seekText }}
            </view>
            <view
                v-if="toText"
                class="mt-[30rpx] bg-[#F3F3F3] text-[30rpx] font-bold rounded-[20rpx] h-[90rpx] flex items-center justify-center"
                @click="emit('to')">
                {{ toText }}
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{ modelValue: boolean; title: string; desc: string; toText?: string; seekText?: string }>(),
    {
        modelValue: false,
        toText: "立即去发布",
        seekText: "查看创作记录",
    }
);

const emit = defineEmits<{ (e: "update:modelValue", value: boolean): void; (e: "to"): void; (e: "seek"): void }>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});
</script>

<style scoped></style>
