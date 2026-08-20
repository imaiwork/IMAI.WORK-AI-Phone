<template>
    <web-view v-if="url" :src="url"></web-view>
</template>

<script setup lang="ts">
// 使用原生导航栏（默认 navigationStyle），保证小程序端 web-view 有返回按钮
const url = ref('')

onLoad((options: any) => {
    url.value = decodeURIComponent(options?.url || '')
    const title = options?.title ? decodeURIComponent(options.title) : ''
    if (title) {
        uni.setNavigationBarTitle({ title })
    }
})
</script>

<style lang="scss" scoped></style>
