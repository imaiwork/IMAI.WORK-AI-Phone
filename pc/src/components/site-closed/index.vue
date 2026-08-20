<template>
    <div class="site-closed">
        <div class="site-closed__panel">
            <div class="site-closed__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-9 h-9">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                </svg>
            </div>
            <h1 class="site-closed__title">站点已关闭</h1>
            <p class="site-closed__desc">
                {{ message || "该企业站点已解散或关闭，暂时无法访问。" }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();
const message = computed(() => {
    const oem = appStore.getOemConfig as Record<string, any>;
    return String(oem?.message || "").trim();
});

useHead({
    title: "站点已关闭",
});
</script>

<style scoped>
.site-closed {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 32px 20px;
    background:
        radial-gradient(ellipse 80% 50% at 50% -10%, rgba(0, 101, 251, 0.08), transparent),
        #f8fafc;
}
.site-closed__panel {
    width: 100%;
    max-width: 420px;
    text-align: center;
    padding: 40px 28px;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #eef2f7;
    box-shadow: 0 20px 48px -20px rgba(15, 23, 42, 0.18);
}
.site-closed__icon {
    width: 72px;
    height: 72px;
    margin: 0 auto;
    border-radius: 20px;
    display: grid;
    place-items: center;
    color: #b45309;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
}
.site-closed__title {
    margin-top: 20px;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.site-closed__desc {
    margin-top: 10px;
    font-size: 14px;
    line-height: 1.65;
    color: #64748b;
}
</style>
