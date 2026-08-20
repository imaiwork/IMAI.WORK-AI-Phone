<template>
    <u-popup v-model="show" mode="center" border-radius="24" width="86%" :closeable="false">
        <view class="relative px-[36rpx] pt-[40rpx] pb-[36rpx]">
            <view class="absolute top-[24rpx] right-[24rpx] z-10" @click="show = false">
                <image src="/static/images/icons/close.svg" class="w-[48rpx] h-[48rpx]" />
            </view>
            <oem-recharge-panel @success="show = false" />
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import OemRechargePanel from "@/components/oem-recharge-panel/oem-recharge-panel.vue";

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
