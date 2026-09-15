<template>
    <view class="h-screen flex flex-col bg-[#F3F5FA]">
        <u-navbar title="作品数据" title-bold :border-bottom="false" :is-fixed="false"></u-navbar>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                    <view v-if="personaLoading" class="flex flex-col gap-[32rpx]">
                        <view
                            class="bg-white rounded-[32rpx] p-[32rpx] flex items-center gap-[28rpx]"
                        >
                            <skeleton-block w="128rpx" h="128rpx" radius="50%" />
                            <view class="flex-1">
                                <skeleton-block w="50%" h="30rpx" />
                                <skeleton-block class="mt-[16rpx]" w="70%" h="24rpx" />
                            </view>
                        </view>
                        <view class="bg-white rounded-[32rpx] p-[28rpx]">
                            <skeleton-block w="60%" h="40rpx" />
                            <skeleton-block class="mt-[24rpx]" h="120rpx" radius="20rpx" />
                            <skeleton-block class="mt-[24rpx]" h="216rpx" radius="16rpx" />
                        </view>
                    </view>

                    <view
                        v-else-if="personaError"
                        class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] px-[48rpx] py-[80rpx] flex flex-col items-center mt-[80rpx]"
                    >
                        <u-icon name="info-circle" color="#C3CAD6" :size="52"></u-icon>
                        <text
                            class="text-[26rpx] font-semibold text-[#6B7280] mt-[16rpx] text-center"
                            >{{ personaError }}</text
                        >
                        <view
                            class="mt-[28rpx] px-[40rpx] py-[16rpx] rounded-full bg-primary-light-9 active:opacity-70"
                            @click="handleReloadPersonas"
                        >
                            <text class="text-[25rpx] font-bold text-primary">重试</text>
                        </view>
                    </view>

                    <view
                        v-else-if="!personas.length"
                        class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] px-[48rpx] py-[80rpx] flex flex-col items-center mt-[80rpx]"
                    >
                        <view
                            class="w-[128rpx] h-[128rpx] rounded-full bg-primary-light-9 flex items-center justify-center"
                        >
                            <u-icon
                                name="account"
                                color="var(--color-primary, #0065FB)"
                                :size="52"
                            ></u-icon>
                        </view>
                        <text class="text-[32rpx] font-bold text-main mt-[32rpx]"
                            >还没有创建人设</text
                        >
                        <text
                            class="text-[24rpx] text-[#9AA3B2] text-center leading-relaxed mt-[16rpx]"
                        >
                            作品数据按人设分开统计，先创建一个人设并绑定发布账号，这里就会同步账号的作品数据
                        </text>
                        <view
                            class="mt-[40rpx] px-[56rpx] h-[80rpx] rounded-full bg-primary flex items-center justify-center active:opacity-80"
                            @click="handleCreatePersona"
                        >
                            <text class="text-[28rpx] font-bold text-white">去创建人设</text>
                        </view>
                    </view>

                    <template v-else>
                        <persona-card :persona="activePersona" @switch="showPersonaPicker = true" />

                        <view
                            class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] p-[28rpx] mt-[32rpx]"
                        >
                            <view class="flex items-center">
                                <view class="flex gap-[8rpx] flex-1 min-w-0">
                                    <view
                                        v-for="item in PLATFORMS"
                                        :key="item.key"
                                        class="px-[28rpx] py-[16rpx] rounded-[24rpx]"
                                        :class="
                                            item.key === activePlatform
                                                ? 'bg-main'
                                                : 'bg-[transparent]'
                                        "
                                        @click="handlePlatform(item.key)"
                                    >
                                        <text
                                            class="text-[27rpx] font-bold"
                                            :class="
                                                item.key === activePlatform
                                                    ? 'text-white'
                                                    : 'text-[#8A93A3]'
                                            "
                                        >
                                            {{ item.name }}
                                        </text>
                                    </view>
                                </view>
                                <view
                                    class="shrink-0 ml-[8rpx] flex items-center gap-[8rpx] px-[24rpx] py-[16rpx] rounded-[24rpx] bg-[#F5F6FA] active:opacity-70"
                                    @click="handleRefresh"
                                >
                                    <u-loading
                                        v-if="loading"
                                        mode="circle"
                                        size="24"
                                        color="#8A93A3"
                                    ></u-loading>
                                    <u-icon
                                        v-else
                                        name="reload"
                                        color="#8A93A3"
                                        :size="20"
                                    ></u-icon>
                                    <text class="text-[24rpx] font-semibold text-[#6B7280]"
                                        >刷新</text
                                    >
                                </view>
                            </view>

                            <account-tabs
                                v-if="accounts.length > 1"
                                class="mt-[20rpx]"
                                :accounts="accounts"
                                :active-index="activeAccountIndex"
                                @change="handleAccount"
                            />

                            <account-card
                                v-if="activeAccount"
                                class="mt-[20rpx]"
                                :account="analytics?.account || null"
                                :platform="platformConfig"
                                :loading="loading"
                            />

                            <view
                                class="border-[0] border-t-[2rpx] border-solid border-[#F0F2F6] mt-[24rpx] pt-[28rpx]"
                            >
                                <view class="flex items-center mb-[28rpx]">
                                    <text class="text-[30rpx] font-bold text-main">
                                        近 {{ activeWindow }} 日数据
                                    </text>
                                    <view class="ml-auto flex gap-[12rpx]">
                                        <view
                                            v-for="day in WINDOW_OPTIONS"
                                            :key="day"
                                            class="px-[24rpx] py-[12rpx] rounded-full border-[3rpx] border-solid"
                                            :class="
                                                day === activeWindow
                                                    ? 'bg-primary-light-9 border-primary'
                                                    : 'bg-[#F4F6FA] border-[transparent]'
                                            "
                                            @click="activeWindow = day"
                                        >
                                            <text
                                                class="text-[23rpx] font-bold"
                                                :class="
                                                    day === activeWindow
                                                        ? 'text-primary'
                                                        : 'text-[#8A93A3]'
                                                "
                                            >
                                                {{ day }}日
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <kpi-row
                                    :stat="windowStat"
                                    :platform="platformConfig"
                                    :play-available="!!analytics?.playCountAvailable"
                                    :loading="loading"
                                />

                                <skeleton-block
                                    v-if="loading"
                                    class="mt-[32rpx]"
                                    h="216rpx"
                                    radius="16rpx"
                                />
                                <trend-chart
                                    v-else-if="chartSeries.length"
                                    class="mt-[32rpx]"
                                    :series="chartSeries"
                                    :caption="chartCaption"
                                />

                                <view class="flex items-center justify-end mt-[16rpx]">
                                    <text
                                        v-if="refreshCostText"
                                        class="text-[21rpx] text-[#C3CAD6]"
                                    >
                                        刷新消耗 {{ refreshCostText }} ·
                                    </text>
                                    <text class="text-[21rpx] text-[#C3CAD6] ml-[6rpx]">
                                        {{ updatedText }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view class="flex items-baseline mt-[40rpx] mb-[20rpx]">
                            <view class="flex items-center gap-[16rpx]">
                                <view class="w-[8rpx] h-[30rpx] rounded-[4rpx] bg-primary"></view>
                                <text class="text-[32rpx] font-bold text-main">
                                    {{ platformConfig.workLabel }}数据
                                </text>
                            </view>
                            <text v-if="worksNote" class="ml-auto text-[22rpx] text-[#9AA3B2]">
                                {{ worksNote }}
                            </text>
                        </view>

                        <view class="flex flex-col gap-[20rpx]">
                            <template v-if="loading">
                                <view
                                    v-for="item in 3"
                                    :key="item"
                                    class="bg-white rounded-[32rpx] flex gap-[22rpx] p-[24rpx]"
                                >
                                    <skeleton-block w="124rpx" h="164rpx" radius="20rpx" />
                                    <view class="flex-1">
                                        <skeleton-block w="80%" h="26rpx" />
                                        <skeleton-block class="mt-[16rpx]" w="45%" h="22rpx" />
                                        <skeleton-block class="mt-[28rpx]" h="56rpx" />
                                    </view>
                                </view>
                            </template>

                            <view
                                v-else-if="errorText"
                                class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] px-[40rpx] py-[56rpx] flex flex-col items-center"
                            >
                                <u-icon name="info-circle" color="#C3CAD6" :size="52"></u-icon>
                                <text
                                    class="text-[26rpx] font-semibold text-[#6B7280] mt-[16rpx] text-center"
                                >
                                    {{ errorText }}
                                </text>
                                <view
                                    class="mt-[28rpx] px-[40rpx] py-[16rpx] rounded-full bg-primary-light-9 active:opacity-70"
                                    @click="handleRefresh"
                                >
                                    <text class="text-[25rpx] font-bold text-primary">重试</text>
                                </view>
                            </view>

                            <view
                                v-else-if="!activeAccount"
                                class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] px-[40rpx] py-[56rpx] flex flex-col items-center"
                            >
                                <u-icon name="info-circle" color="#C3CAD6" :size="52"></u-icon>
                                <text
                                    class="text-[26rpx] font-semibold text-[#6B7280] mt-[16rpx] text-center"
                                >
                                    「{{ activePersona.name }}」还没绑定{{
                                        platformConfig.short
                                    }}账号
                                </text>
                                <text
                                    class="text-[23rpx] text-[#9AA3B2] mt-[12rpx] text-center leading-relaxed"
                                >
                                    在「AI手机管理」里绑定发布账号后，可手动刷新或开启定时同步，查看该账号的{{
                                        platformConfig.workLabel
                                    }}数据
                                </text>
                            </view>

                            <view
                                v-else-if="!works.length"
                                class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] px-[40rpx] py-[56rpx]"
                            >
                                <text class="block text-[26rpx] text-[#9AA3B2] text-center">
                                    {{
                                        analytics
                                            ? `这个账号还没有发布过${platformConfig.workLabel}`
                                            : '尚未同步作品数据，可点击「刷新」获取'
                                    }}
                                </text>
                            </view>

                            <template v-else>
                                <work-item
                                    v-for="(item, index) in works"
                                    :key="`${item.title}-${index}`"
                                    :item="item"
                                    :platform="platformConfig"
                                />
                            </template>
                        </view>

                        <view class="bg-[#EEF4FF] rounded-[28rpx] p-[28rpx] mt-[32rpx]">
                            <sync-setting
                                :enabled="!!activePersona?.auto_sync_enabled"
                                :saving="savingSync"
                                :cost-text="refreshCostText"
                                @change="handleSyncChange"
                            />
                            <view class="flex gap-[20rpx]">
                                <view class="shrink-0 pt-[4rpx]">
                                    <u-icon
                                        name="info-circle"
                                        color="var(--color-primary, #0065FB)"
                                        :size="28"
                                    ></u-icon>
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text
                                        v-for="(line, index) in caliberLines"
                                        :key="index"
                                        class="block text-[23rpx] text-[#5B6B8C] leading-relaxed"
                                    >
                                        {{ line }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>
                </view>
            </scroll-view>
        </view>

        <persona-picker
            v-model="showPersonaPicker"
            :personas="personas"
            :active-id="activePersonaId"
            @change="handlePersona"
        />

        <confirm-dialog
            v-model="showRefreshConfirm"
            title="刷新将消耗算力"
            :content="refreshConfirmText"
            confirm-text="确定刷新"
            @confirm="runRefresh"
        />
    </view>
</template>

<script setup lang="ts">
import { getWorkDataAnalytics, getWorkDataPersonas, setWorkDataSyncSettings } from '@/api/work_data'
import { useLockFn } from '@/hooks/useLockFn'
import {
    NA_PLAY_TEXT,
    NA_REACH_TEXT,
    PERSONA_CREATE_URL,
    PLATFORMS,
    PlatformEnum,
    REACH_LABEL,
    REFRESH_INTERVAL,
    WINDOW_OPTIONS,
    type AnalyticsData,
    type CostInfo,
    type Persona,
    type SeriesPoint
} from './enums'
import { formatUpdateTime } from './utils'
import AccountCard from './components/account-card.vue'
import AccountTabs from './components/account-tabs.vue'
import KpiRow from './components/kpi-row.vue'
import PersonaCard from './components/persona-card.vue'
import SkeletonBlock from './components/skeleton-block.vue'
import SyncSetting from './components/sync-setting.vue'
import TrendChart from './components/trend-chart.vue'
import WorkItem from './components/work-item.vue'
import PersonaPicker from './components/popups/persona-picker.vue'
import ConfirmDialog from '@/components/confirm-dialog/confirm-dialog.vue'

const DEFAULT_WINDOW = 30
const NETWORK_ERROR_TEXT = '数据服务未连接'

const personas = ref<Persona[]>([])
const personaLoading = ref(true)
const personaError = ref('')
const activePersonaId = ref(0)
const activePlatform = ref<PlatformEnum>(PlatformEnum.DOUYIN)
const activeAccountIndex = ref(0)
const activeWindow = ref(DEFAULT_WINDOW)
const analytics = ref<AnalyticsData | null>(null)
const loading = ref(false)
const errorText = ref('')
const showPersonaPicker = ref(false)
const showRefreshConfirm = ref(false)
const refreshCost = ref<CostInfo | null>(null)

let requestId = 0
let lastRefreshAt = 0

const activePersona = computed(
    () => personas.value.find((item) => item.id === activePersonaId.value) || personas.value[0]
)

const platformConfig = computed(
    () => PLATFORMS.find((item) => item.key === activePlatform.value) || PLATFORMS[0]
)

const accounts = computed(() => activePersona.value?.accounts?.[activePlatform.value] || [])

const activeAccount = computed(() => accounts.value[activeAccountIndex.value] || null)

const windowStat = computed(() => analytics.value?.windows?.[String(activeWindow.value)] || null)

const works = computed(() => analytics.value?.items || [])

// 平台给了播放/浏览量就画它，没有就退回互动量
const usePlaySeries = computed(
    () => !!analytics.value?.playCountAvailable && !!windowStat.value?.playSeries
)

const chartSeries = computed<SeriesPoint[]>(() => {
    const stat = windowStat.value
    if (!stat) return []
    return (usePlaySeries.value ? stat.playSeries : stat.series) || []
})

const chartCaption = computed(() => {
    const platform = platformConfig.value
    return usePlaySeries.value
        ? `按发布日归集的${platform.workLabel}${platform.viewLabel}量（取当前累计值）`
        : '按发布日归集的互动量（点赞+评论+分享+收藏，取当前累计值）'
})

const updatedText = computed(() => {
    if (!activeAccount.value) return '未绑定账号'
    if (errorText.value) return '获取失败'
    if (!analytics.value) return '—'
    const time = formatUpdateTime(analytics.value.fetchedAt)
    if (!time) return '—'
    return `最后更新 ${time}${analytics.value.cached ? '（缓存）' : ''}`
})

// 账号主页计数和列表条数是两个口径（置顶、合集会差 ±1），
// 只有主页数明显更大时才提「账号共 N 条」
const worksNote = computed(() => {
    const data = analytics.value
    if (!data || !works.value.length) return ''
    const listCount = works.value.length
    const total = Number(data.account?.totalWorks) || listCount
    if (data.truncated) {
        return total > listCount
            ? `已取最新 ${listCount} 条 · 账号共 ${total} 条`
            : `已取最新 ${listCount} 条`
    }
    return total > listCount
        ? `共 ${listCount} 条 · 账号主页显示 ${total} 条`
        : `本机发布的${platformConfig.value.workLabel} · 共 ${listCount} 条`
})

const caliberLines = computed(() => {
    const data = analytics.value
    const platform = platformConfig.value
    const lines = [
        `数据来自${platform.short}开放接口，页面展示上次同步结果，点「刷新」可立即重取。`
    ]

    if (data) {
        lines.push(
            data.playCountAvailable
                ? `${platform.viewLabel}量来自${platform.short}统计接口，与创作者后台可能有分钟级延迟。`
                : `${platform.viewLabel}量显示「${NA_PLAY_TEXT}」：${
                      data.playCountNote || '平台接口未提供该数据'
                  }。`
        )
    }

    if (refreshCostScore.value) {
        lines.push(`每点一次「刷新」消耗 ${refreshCostText.value}，命中缓存时不扣费。`)
    }

    lines.push(
        `「${REACH_LABEL}」显示「${NA_REACH_TEXT}」是因为线索/触达体系尚未打通，不代表没有触达。`
    )

    if (data?.truncated) {
        lines.push(
            `${platform.workLabel}列表只取了最新 ${data.pageSize} 条，更早的${platform.workLabel}未包含在统计里。`
        )
    }
    return lines
})

// 后台把「作品数据报表」场景停用时 score 为 0，此时不提示也不弹确认
const refreshCostScore = computed(() => Number(refreshCost.value?.score) || 0)

const refreshCostText = computed(() => {
    if (!refreshCostScore.value) return ''
    return `${refreshCostScore.value} ${refreshCost.value?.unit || '算力/次'}`
})

const refreshConfirmText = computed(
    () =>
        `重新拉取${platformConfig.value.short}最新数据，本次将消耗 ${refreshCostText.value}。命中缓存时不扣费。`
)

const loadAnalytics = async (refresh = false) => {
    const currentRequest = ++requestId
    const account = activeAccount.value
    errorText.value = ''

    if (!activePersona.value || !account) {
        analytics.value = null
        loading.value = false
        return
    }

    loading.value = true

    try {
        const data = await getWorkDataAnalytics({
            persona_id: activePersona.value.id,
            platform: activePlatform.value,
            account: account.account,
            refresh: refresh ? 1 : 0
        })
        if (currentRequest !== requestId) return
        // 未绑定发布账号时后端只回 bound=false，按空状态处理
        const result = data as AnalyticsData
        analytics.value = result?.bound === false || result?.hasData === false ? null : result
    } catch (error: any) {
        if (currentRequest !== requestId) return
        analytics.value = null
        errorText.value = typeof error === 'string' && error ? error : NETWORK_ERROR_TEXT
    } finally {
        if (currentRequest === requestId) loading.value = false
    }
}

const loadPersonas = async () => {
    personaError.value = ''
    try {
        const data: any = await getWorkDataPersonas()
        // 接口返回 { list, cost }；兼容旧版直接返回数组的情况
        const list: Persona[] = Array.isArray(data) ? data : data?.list || []
        personas.value = list.map((persona) => ({
            ...persona,
            auto_sync_enabled: Number(persona.auto_sync_enabled) === 1
        }))
        refreshCost.value = Array.isArray(data) ? null : data?.cost || null
        if (!personas.value.length) return
        activePersonaId.value = personas.value[0].id
        await loadAnalytics()
    } catch (error: any) {
        // 取人设失败和「一个人设都没有」是两回事，不能都引导去创建人设
        personas.value = []
        personaError.value = typeof error === 'string' && error ? error : NETWORK_ERROR_TEXT
    } finally {
        personaLoading.value = false
    }
}

const handlePersona = (id: number) => {
    showRefreshConfirm.value = false
    // 换人设要把账号下标归零：上一个人设可能有 2 个号，新的只有 1 个
    activePersonaId.value = id
    activeAccountIndex.value = 0
    analytics.value = null
    loadAnalytics()
}

const handlePlatform = (key: PlatformEnum) => {
    if (key === activePlatform.value) return
    showRefreshConfirm.value = false
    activePlatform.value = key
    activeAccountIndex.value = 0
    analytics.value = null
    loadAnalytics()
}

const handleAccount = (index: number) => {
    if (index === activeAccountIndex.value) return
    showRefreshConfirm.value = false
    activeAccountIndex.value = index
    analytics.value = null
    loadAnalytics()
}

const handleReloadPersonas = () => {
    personaLoading.value = true
    loadPersonas()
}

const handleCreatePersona = () => {
    uni.navigateTo({ url: PERSONA_CREATE_URL })
}

const { isLock: savingSync, lockFn: handleSyncChange } = useLockFn(async (enabled: boolean) => {
    const persona = activePersona.value
    if (!persona || enabled === persona.auto_sync_enabled) return
    try {
        const result = await setWorkDataSyncSettings({
            persona_id: persona.id,
            auto_sync_enabled: enabled ? 1 : 0
        })
        persona.auto_sync_enabled = Number(result.auto_sync_enabled) === 1
        uni.$u.toast(persona.auto_sync_enabled ? '已开启定时同步' : '已关闭定时同步，不再自动扣费')
    } catch (error) {
        uni.$u.toast(typeof error === 'string' ? error : '保存失败，请重试')
    }
})

const { lockFn: runRefresh } = useLockFn(async () => {
    lastRefreshAt = Date.now()
    uni.$u.toast('正在重新获取…')
    await loadAnalytics(true)
})

const handleRefresh = () => {
    if (loading.value) return
    if (!activeAccount.value) {
        uni.$u.toast(`请先绑定${platformConfig.value.short}账号`)
        return
    }
    // 刷新是按次计费的，节流窗口内后端只回缓存，先挡掉免得白弹确认
    const wait = REFRESH_INTERVAL - (Date.now() - lastRefreshAt)
    if (wait > 0) {
        uni.$u.toast(`刚刷新过，${Math.ceil(wait / 1000)} 秒后再试`)
        return
    }
    if (!refreshCostScore.value) {
        runRefresh()
        return
    }
    showRefreshConfirm.value = true
}

onLoad(() => {
    loadPersonas()
})
</script>
