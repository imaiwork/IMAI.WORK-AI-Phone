<template>
    <view class="wd-skeleton" :style="{ width: w, height: h, borderRadius: radius }"></view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        w?: string
        h?: string
        radius?: string
    }>(),
    {
        w: '100%',
        h: '24rpx',
        radius: '8rpx'
    }
)
</script>

<style lang="scss" scoped>
.wd-skeleton {
    background: linear-gradient(90deg, #f2f4f8 25%, #e9edf4 37%, #f2f4f8 63%);
    background-size: 400% 100%;
    animation: wd-skeleton-loading 1.3s ease infinite;
}

@keyframes wd-skeleton-loading {
    0% {
        background-position: 100% 50%;
    }

    100% {
        background-position: 0 50%;
    }
}
</style>
