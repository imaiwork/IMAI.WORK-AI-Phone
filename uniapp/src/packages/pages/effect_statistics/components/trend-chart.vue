<template>
    <view>
        <text class="block text-[22rpx] text-[#9AA3B2] mb-[12rpx]">{{ caption }}</text>

        <view :id="wrapId" class="w-full">
            <image
                v-if="imagePath"
                :src="imagePath"
                mode="widthFix"
                :style="{ width: width + 'px', height: height + 'px' }"
            />
            <canvas
                v-else
                :canvas-id="canvasId"
                :id="canvasId"
                :style="{ width: width + 'px', height: height + 'px' }"
            ></canvas>
        </view>

        <view class="flex justify-between mt-[6rpx]">
            <text v-for="item in axisLabels" :key="item" class="text-[20rpx] text-[#9AA3B2]">
                {{ item }}
            </text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { formatAxisDate } from '../utils'
import type { SeriesPoint } from '../enums'

// 原型按 320x108 的画布设计，按容器实际宽度等比放大，保证各端观感一致
const DESIGN_WIDTH = 320
const DESIGN_HEIGHT = 108
const DESIGN_PAD = 6
const DESIGN_BOTTOM = 10
const DESIGN_TOP = 26
// 值为 0 时贴着坐标轴画等于看不见，抬高一点让 0 那段也是一条可见的线
const DESIGN_ZERO_FLOOR = 4
// canvas 取不到 CSS 变量，这里用主题主色的默认值
const CHART_COLOR = '#0065FB'
const CHART_RGB = '0,101,251'
const MAX_AXIS_LABELS = 7

const props = defineProps<{
    series: SeriesPoint[]
    caption: string
}>()

const instance = getCurrentInstance()
const { proxy } = instance as { proxy: any }
const canvasId = `wdTrendCanvas${Math.random().toString(36).slice(2, 10)}`
const wrapId = `${canvasId}Wrap`

const width = ref(DESIGN_WIDTH)
const height = ref(DESIGN_HEIGHT)
const imagePath = ref('')

const axisLabels = computed(() => {
    const list = props.series
    if (!list.length) return []
    if (list.length <= MAX_AXIS_LABELS) return list.map((item) => formatAxisDate(item.date))
    return [0, Math.floor(list.length / 2), list.length - 1].map((index) =>
        formatAxisDate(list[index].date)
    )
})

const measure = () =>
    new Promise<number>((resolve) => {
        uni.createSelectorQuery()
            .in(proxy)
            .select(`#${wrapId}`)
            .boundingClientRect((rect: any) => {
                resolve(rect && rect.width ? rect.width : 0)
            })
            .exec()
    })

const drawChart = () => {
    const list = props.series
    if (!list.length) return

    const w = width.value
    const h = height.value
    const scale = w / DESIGN_WIDTH
    const pad = DESIGN_PAD * scale
    const baseY = h - DESIGN_BOTTOM * scale
    const zeroY = baseY - DESIGN_ZERO_FLOOR * scale
    const plotHeight = h - (DESIGN_BOTTOM + DESIGN_TOP) * scale - DESIGN_ZERO_FLOOR * scale
    const max = Math.max(...list.map((item) => Number(item.value) || 0), 1)
    const stepDenominator = Math.max(list.length - 1, 1)
    const pointX = (index: number) => pad + (index / stepDenominator) * (w - pad * 2)
    const pointY = (value: number) => zeroY - ((Number(value) || 0) / max) * plotHeight

    const ctx = uni.createCanvasContext(canvasId, instance)
    ctx.clearRect(0, 0, w, h)

    ctx.beginPath()
    ctx.moveTo(pad, baseY)
    ctx.lineTo(w - pad, baseY)
    ctx.setStrokeStyle('#EEF1F6')
    ctx.setLineWidth(1)
    ctx.stroke()

    const gradient = ctx.createLinearGradient(0, 0, 0, h)
    gradient.addColorStop(0, `rgba(${CHART_RGB},0.22)`)
    gradient.addColorStop(1, `rgba(${CHART_RGB},0)`)
    ctx.beginPath()
    ctx.moveTo(pad, baseY)
    list.forEach((item, index) => ctx.lineTo(pointX(index), pointY(item.value)))
    ctx.lineTo(w - pad, baseY)
    ctx.closePath()
    ctx.setFillStyle(gradient)
    ctx.fill()

    ctx.beginPath()
    list.forEach((item, index) => {
        const x = pointX(index)
        const y = pointY(item.value)
        if (index === 0) {
            ctx.moveTo(x, y)
        } else {
            ctx.lineTo(x, y)
        }
    })
    ctx.setStrokeStyle(CHART_COLOR)
    ctx.setLineWidth(2 * scale)
    ctx.setLineJoin('round')
    ctx.setLineCap('round')
    ctx.stroke()

    const peak = list.reduce(
        (acc, item, index) => (Number(item.value) > Number(list[acc].value) ? index : acc),
        0
    )
    ctx.beginPath()
    ctx.arc(pointX(peak), pointY(list[peak].value), 3.5 * scale, 0, Math.PI * 2)
    ctx.setFillStyle(CHART_COLOR)
    ctx.fill()

    ctx.draw(false, () => {
        const dpr = uni.getSystemInfoSync().pixelRatio || 2
        uni.canvasToTempFilePath(
            {
                x: 0,
                y: 0,
                width: w,
                height: h,
                destWidth: w * dpr,
                destHeight: h * dpr,
                canvasId,
                success: (res) => {
                    // 转成图片后不再是原生组件，人设弹窗才能正常盖住图表
                    if (res.tempFilePath) imagePath.value = res.tempFilePath
                }
            },
            proxy
        )
    })
}

const render = async () => {
    if (!props.series.length) {
        imagePath.value = ''
        return
    }
    await nextTick()
    // 小程序首次渲染时节点可能还没进布局，量到 0 就再试一次，否则图表画不出来
    let measured = await measure()
    if (!measured) measured = await measure()
    width.value = measured || DESIGN_WIDTH
    height.value = Math.round((width.value * DESIGN_HEIGHT) / DESIGN_WIDTH)
    imagePath.value = ''
    await nextTick()
    drawChart()
}

onMounted(render)

watch(() => props.series, render, { deep: true })
</script>
