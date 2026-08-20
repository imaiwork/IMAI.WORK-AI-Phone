<template>
    <div ref="wrapRef" class="w-full">
        <svg :viewBox="`0 0 ${W} ${H}`" class="block w-full" preserveAspectRatio="xMidYMid meet" :style="{ height: height + 'px' }">
            <line v-for="g in 4" :key="'g' + g" :x1="PAD" :x2="W - RIGHT" :y1="yPos(g * 25)" :y2="yPos(g * 25)" stroke="#eef2f7" stroke-width="1" />
            <line :x1="PAD" :x2="W - RIGHT" :y1="yPos(0)" :y2="yPos(0)" stroke="#e2e8f0" stroke-width="1" />
            <text v-for="g in [0, 50, 100]" :key="'y' + g" :x="PAD - 6" :y="yPos(g) + 4" text-anchor="end" font-size="10" fill="#94a3b8">{{ g }}</text>
            <template v-for="(s, si) in seriesPoints" :key="'series' + si">
                <polyline v-for="(seg, i) in s.segments" :key="'s' + si + '-' + i" :points="seg" fill="none" :stroke="s.color" stroke-width="2" stroke-linejoin="round" />
                <template v-for="(p, i) in s.points" :key="'p' + si + '-' + i">
                    <circle v-if="p.v != null" :cx="p.x" :cy="p.y" r="3" fill="#fff" :stroke="s.color" stroke-width="2">
                        <title>{{ p.label }} · {{ s.name }}:{{ p.v }}{{ s.unit }}{{ p.extra ? '(' + p.extra + ')' : '' }}</title>
                    </circle>
                </template>
            </template>
            <text
                v-for="(p, i) in xLabels"
                :key="'x' + i"
                :x="p.x"
                :y="H - 6"
                :text-anchor="i === 0 ? 'start' : i === xLabels.length - 1 ? 'end' : 'middle'"
                font-size="10"
                fill="#94a3b8">
                {{ p.label }}
            </text>
        </svg>
        <div v-if="allEmpty" class="text-slate-300 text-sm text-center py-3">暂无趋势数据,开启每日自动监测或多跑几轮诊断后可见</div>
        <div v-else-if="dayCount < 2" class="text-slate-400 text-xs text-center py-2">
            当前只有 {{ dayCount }} 天有监测数据,曲线至少需要 2 天。开启每日自动监测或多跑几轮诊断后即可看到走势
        </div>
    </div>
</template>

<script setup lang="ts">
// 轻量 SVG 折线图(支持多序列,值域 0~100):用于可见度/在线趋势曲线(二期),不引入图表库
export interface TrendSeries {
    name: string
    color?: string
    unit?: string
    data: Array<{ label: string; value: number | null; extra?: string }>
}
const props = withDefaults(defineProps<{ series: TrendSeries[]; height?: number }>(), { height: 180 })

const wrapRef = ref<HTMLElement | null>(null)
const W = ref(640)
const H = computed(() => props.height)
const PAD = 34
const RIGHT = 20
const DEFAULT_COLORS = ['#0065fb', '#10b981', '#f59e0b']

const yPos = (v: number) => 14 + (100 - Math.min(100, Math.max(0, v))) * (H.value - 36) / 100

const seriesPoints = computed(() => props.series.map((s, si) => {
    const n = Math.max(1, s.data.length)
    const step = (W.value - PAD - RIGHT) / Math.max(1, n - 1)
    const points = s.data.map((d, i) => ({
        x: PAD + i * step,
        y: d.value == null ? 0 : yPos(d.value),
        v: d.value,
        label: d.label,
        extra: d.extra
    }))
    const segments: string[] = []
    let cur: string[] = []
    for (const p of points) {
        if (p.v == null) { if (cur.length > 1) segments.push(cur.join(' ')); cur = []; continue }
        cur.push(`${p.x},${p.y}`)
    }
    if (cur.length > 1) segments.push(cur.join(' '))
    return { name: s.name, unit: s.unit ?? '', color: s.color || DEFAULT_COLORS[si % DEFAULT_COLORS.length], points, segments }
}))

const xLabels = computed(() => {
    const pts = seriesPoints.value[0]?.points || []
    if (pts.length <= 2) return pts
    const maxTicks = Math.min(7, pts.length)
    const minGap = 56
    const picked: typeof pts = []
    for (let i = 0; i < maxTicks; i++) {
        const idx = Math.round(i * (pts.length - 1) / (maxTicks - 1))
        const p = pts[idx]
        const prev = picked[picked.length - 1]
        if (prev && p.x - prev.x < minGap) {
            if (i === maxTicks - 1) picked[picked.length - 1] = p
            continue
        }
        picked.push(p)
    }
    return picked
})
const allEmpty = computed(() => props.series.every((s) => s.data.every((d) => d.value == null)))
const dayCount = computed(() => {
    const labels = new Set<string>()
    for (const s of props.series) {
        for (const d of s.data) {
            if (d.value != null) labels.add(d.label)
        }
    }
    return labels.size
})

let resizeObs: ResizeObserver | null = null
onMounted(() => {
    const el = wrapRef.value
    if (!el) return
    const apply = () => { W.value = Math.max(320, Math.round(el.clientWidth)) }
    apply()
    resizeObs = new ResizeObserver(apply)
    resizeObs.observe(el)
})
onUnmounted(() => resizeObs?.disconnect())
</script>
