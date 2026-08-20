<template>
    <ElConfigProvider :locale="zhCn">
        <div class="error-page">
            <header class="error-page__header">
                <a href="/" class="error-page__brand" @click.prevent="handleGoHome">
                    <img v-if="logo" :src="logo" alt="logo" class="error-page__logo" />
                    <span class="error-page__name">{{ siteName }}</span>
                </a>
            </header>

            <main class="error-page__main">
                <div class="error-page__panel">
                    <p class="error-page__code" aria-hidden="true">{{ statusCode }}</p>
                    <h1 class="error-page__title">{{ title }}</h1>
                    <p class="error-page__desc">{{ description }}</p>
                    <p v-if="pathHint" class="error-page__path" :title="pathHint">{{ pathHint }}</p>

                    <div class="error-page__actions">
                        <ElButton type="primary" @click="handleGoHome">返回首页</ElButton>
                        <ElButton v-if="canGoBack" @click="handleGoBack">返回上一页</ElButton>
                        <ElButton v-else-if="!isNotFound" @click="handleRetry">重新加载</ElButton>
                    </div>
                </div>
            </main>
        </div>
    </ElConfigProvider>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'
import { ElButton, ElConfigProvider } from 'element-plus'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import { useAppStore } from '@/stores/app'

const props = defineProps<{
    error: NuxtError
}>()

const appStore = useAppStore()
const route = useRoute()

const statusCode = computed(() => props.error?.statusCode || 500)
const isNotFound = computed(() => statusCode.value === 404)

const logo = computed(() => {
    const oem = appStore.getOemConfig as Record<string, any>
    const website = appStore.getWebsiteConfig as Record<string, any>
    return oem?.is_oem == 1 ? oem?.site_logo : website?.pc_logo
})

const siteName = computed(() => {
    const oem = appStore.getOemConfig as Record<string, any>
    const website = appStore.getWebsiteConfig as Record<string, any>
    if (oem?.is_oem == 1 && oem?.name) return oem.name
    return website?.pc_title || 'AI数字员工'
})

const title = computed(() => {
    if (isNotFound.value) return '页面不存在'
    if (statusCode.value === 403) return '暂无访问权限'
    return '页面加载失败'
})

const description = computed(() => {
    if (isNotFound.value) return '你访问的地址不存在，或已被移动。请检查链接后重试。'
    if (statusCode.value === 403) return '当前账号没有访问该页面的权限，可返回首页继续使用。'
    return props.error?.statusMessage || '服务暂时出了点问题，请稍后重试。'
})

const pathHint = computed(() => {
    const fromRoute = route.fullPath
    if (fromRoute && fromRoute !== '/') return fromRoute

    const message = props.error?.message || ''
    const matched = message.match(/:\s*(\/\S+)/)
    return matched?.[1] || ''
})

const canGoBack = computed(() => {
    if (!import.meta.client) return false
    return window.history.length > 1
})

useHead({
    title: computed(() => `${title.value} · ${siteName.value}`)
})

const handleGoHome = () => {
    clearError({ redirect: '/' })
}

const handleGoBack = async () => {
    await clearError()
    if (import.meta.client && window.history.length > 1) {
        window.history.back()
        return
    }
    await navigateTo('/')
}

const handleRetry = () => {
    clearError({ redirect: route.fullPath || '/' })
}
</script>

<style lang="scss" scoped>
.error-page {
    min-height: 100vh;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    background:
        radial-gradient(1200px 480px at 50% -10%, rgba(0, 101, 251, 0.08), transparent 60%),
        var(--el-bg-color-page);
    color: var(--el-text-color-primary);
}

.error-page__header {
    display: flex;
    align-items: center;
    height: 64px;
    padding: 0 28px;
}

.error-page__brand {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: inherit;
}

.error-page__logo {
    width: 28px;
    height: 28px;
    border-radius: 9999px;
    object-fit: cover;
    background: #fff;
}

.error-page__name {
    font-size: 14px;
    font-weight: 500;
    color: var(--el-text-color-secondary);
}

.error-page__main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 24px 80px;
}

.error-page__panel {
    width: min(100%, 440px);
    text-align: center;
    animation: error-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.error-page__code {
    margin: 0 0 12px;
    font-size: 64px;
    line-height: 1;
    font-weight: 600;
    letter-spacing: -0.04em;
    color: var(--color-primary);
}

.error-page__title {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
    line-height: 1.3;
    text-wrap: balance;
}

.error-page__desc {
    margin: 10px 0 0;
    font-size: 14px;
    line-height: 1.6;
    color: var(--el-text-color-secondary);
    text-wrap: pretty;
}

.error-page__path {
    margin: 14px auto 0;
    max-width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.04);
    color: #64748b;
    font-size: 12px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.error-page__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 12px;
    margin-top: 28px;
}

@keyframes error-enter {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .error-page__panel {
        animation: none;
    }
}
</style>
