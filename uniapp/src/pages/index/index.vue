<template>
    <view class="launch-shell" />
</template>

<script setup lang="ts">
const REAL_HOME_PATH = "/packages/pages/home/home";

const buildHomeUrl = (query: Record<string, any> = {}) => {
    const params = Object.entries(query)
        .filter(([, value]) => value !== undefined && value !== null && `${value}` !== "")
        .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
        .join("&");
    return `${REAL_HOME_PATH}${params ? `?${params}` : ""}`;
};

const goHome = (query: Record<string, any> = {}) => {
    const url = buildHomeUrl(query);
    uni.redirectTo({
        url,
        fail: () => {
            uni.reLaunch({ url });
        },
    });
};

onLoad((query) => {
    goHome(query || {});
});
</script>

<style scoped>
.launch-shell {
    min-height: 100vh;
    background: #f3f5fa;
}
</style>
