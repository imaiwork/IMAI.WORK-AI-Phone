<template>
    <view class="sec-t">
        <slot />
    </view>
</template>

<script setup lang="ts"></script>

<style lang="scss" scoped>
.sec-t {
    @apply text-[30rpx] font-extrabold text-[#1D2129] flex items-center gap-[12rpx];
    &::before {
        content: '';
        width: 8rpx;
        height: 30rpx;
        border-radius: 4rpx;
        background: #2680f7;
    }
}
</style>
