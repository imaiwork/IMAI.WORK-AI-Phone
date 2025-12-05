<template>
    <view class="flex flex-col items-center justify-center">
        <image src="/static/images/common/empty.png" :style="{ width: size + 'rpx', height: size + 'rpx' }"></image>
        <text class="text-xs text-[#969EA9]">{{ text }}</text>
    </view>
</template>

<script setup lang="ts">
const props = defineProps({
    text: {
        type: String,
        default: "暂无数据",
    },
    size: {
        type: Number,
        default: 400,
    },
});
</script>

<style scoped></style>
