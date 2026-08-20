<template>
    <!-- 选时间时先收起中心弹窗，避免 u-popup center 的 z-index:99999 压住 u-picker -->
    <u-popup v-model="expirePopupShow" mode="center" width="90%" :border-radius="32">
        <view class="rounded-[32rpx] bg-white p-[40rpx]">
            <!-- 图标 + 标题(与 PC 端 dialog-expire 一致) -->
            <view
                class="mx-auto mb-[24rpx] flex h-[96rpx] w-[96rpx] items-center justify-center rounded-[28rpx]"
                style="background: linear-gradient(135deg, #3b82f6, #60a5fa); box-shadow: 0 10rpx 24rpx -6rpx rgba(59, 130, 246, 0.5)">
                <u-icon name="calendar" size="44" color="#ffffff" />
            </view>
            <view class="mb-[12rpx] text-center text-[34rpx] font-extrabold text-[#0F172A]">设置到期时间</view>
            <view class="mb-[32rpx] text-center text-[24rpx] leading-[38rpx] text-[#94A3B8]">
                <text class="block">设置「{{ member?.nickname || '' }}」的团队权益有效期</text>
                <text class="block">到期后将无法使用企业空间</text>
            </view>

            <!-- 当前到期 -->
            <view
                class="mb-[20rpx] flex items-center justify-between rounded-[24rpx] bg-[#F8FAFC] px-[28rpx] py-[24rpx]">
                <text class="text-[26rpx] text-[#94A3B8]">当前到期</text>
                <text class="text-[26rpx] font-bold" :class="currentExpired ? 'text-[#EF4444]' : 'text-[#334155]'">
                    {{ currentExpireText }}
                </text>
            </view>

            <!-- 快捷时长:从今天起算 -->
            <view class="mb-[20rpx] flex gap-[12rpx]">
                <view
                    v-for="opt in QUICK_OPTIONS"
                    :key="opt.label"
                    class="flex h-[64rpx] flex-1 items-center justify-center rounded-[20rpx] text-[24rpx]"
                    :class="
                        isChipActive(opt)
                            ? 'bg-primary-light-9 font-semibold text-primary border border-solid border-primary-light-5'
                            : 'bg-[#F8FAFC] text-[#64748B] border border-solid border-[transparent]'
                    "
                    @click="applyQuick(opt)">
                    {{ opt.label }}
                </view>
            </view>

            <view
                class="mb-[40rpx] flex items-center justify-between rounded-[24rpx] bg-[#F4F6FB] px-[28rpx] py-[28rpx]"
                @click="openPicker">
                <text class="text-[28rpx]" :class="dateValue ? 'text-[#0F172A]' : 'text-[#9CA3AF]'">
                    {{ dateValue || '选择日期时间，留空为永久有效' }}
                </text>
                <u-icon name="calendar" size="32" color="#94A3B8" />
            </view>

            <view class="flex items-center gap-[16rpx]">
                <view
                    class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-[#F4F6FB] active:opacity-70"
                    @click="closeExpire">
                    <text class="text-[28rpx] font-semibold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex h-[88rpx] flex-1 items-center justify-center rounded-full bg-primary active:opacity-90"
                    @click="emit('confirm')">
                    <text class="text-[28rpx] font-bold text-white">{{ submitting ? '保存中...' : '保存' }}</text>
                </view>
            </view>
        </view>
    </u-popup>

    <!-- 与 PC datetime 对齐：年月日时分秒 -->
    <u-picker
        v-model="showPicker"
        mode="time"
        :params="pickerParams"
        :default-time="pickerDefaultTime"
        :start-year="pickerStartYear"
        :end-year="2099"
        @confirm="onPickConfirm"
        @cancel="onPickCancel" />
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    member: any
    date: string
    submitting?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:date', value: string): void
    (e: 'confirm'): void
}>()

const showPicker = ref(false)

// 选时间时临时隐藏中心弹窗，但不通知父层关闭业务态
const expirePopupShow = computed({
    get: () => props.modelValue && !showPicker.value,
    set: (v: boolean) => {
        if (showPicker.value) return
        emit('update:modelValue', v)
    }
})

const dateValue = computed(() => props.date)

const pickerParams = {
    year: true,
    month: true,
    day: true,
    hour: true,
    minute: true,
    second: true,
    timestamp: true
}

const pad = (n: number) => String(n).padStart(2, '0')
const fmtDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const fmtDateTime = (d: Date) =>
    `${fmtDate(d)} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
const datePart = (s: string) => String(s || '').slice(0, 10)

const pickerStartYear = computed(() => {
    const y = Number(datePart(props.date).slice(0, 4))
    const nowY = new Date().getFullYear()
    return Number.isFinite(y) && y > 0 ? Math.min(y, nowY) : nowY
})

const pickerDefaultTime = computed(() => {
    if (props.date) return props.date
    return fmtDateTime(new Date())
})

const openPicker = () => {
    showPicker.value = true
}

const closeExpire = () => {
    showPicker.value = false
    emit('update:modelValue', false)
}

const onPickCancel = () => {
    showPicker.value = false
}

// 当前到期(读列表行的 expire_time_ts,0=永久)
const currentExpired = computed(() => {
    const ts = Number(props.member?.expire_time_ts) || 0
    return ts > 0 && ts * 1000 < Date.now()
})
const currentExpireText = computed(() => {
    const ts = Number(props.member?.expire_time_ts) || 0
    if (!ts) return '永久'
    return fmtDateTime(new Date(ts * 1000)) + (currentExpired.value ? '（已到期）' : '')
})

// 快捷时长(与 PC 端一致,永久=清空日期)
interface QuickOption {
    label: string
    months: number
}
const QUICK_OPTIONS: QuickOption[] = [
    { label: '1个月', months: 1 },
    { label: '3个月', months: 3 },
    { label: '半年', months: 6 },
    { label: '1年', months: 12 },
    { label: '永久', months: 0 }
]

const quickDate = (months: number): string => {
    if (months <= 0) return ''
    const d = new Date()
    d.setMonth(d.getMonth() + months)
    return fmtDateTime(d)
}

const applyQuick = (opt: QuickOption) => {
    emit('update:date', quickDate(opt.months))
}

// 同一天视为命中该快捷项(时分误差忽略，与 PC 一致)
const isChipActive = (opt: QuickOption) => {
    if (opt.months === 0) return !props.date
    if (!props.date) return false
    return datePart(props.date) === datePart(quickDate(opt.months))
}

const onPickConfirm = (e: any) => {
    const y = e?.year
    const m = e?.month
    const d = e?.day
    const h = e?.hour ?? '00'
    const mi = e?.minute ?? '00'
    const s = e?.second ?? '00'
    if (!y || !m || !d) {
        emit('update:date', '')
    } else {
        emit('update:date', `${y}-${m}-${d} ${h}:${mi}:${s}`)
    }
    showPicker.value = false
}

watch(
    () => props.modelValue,
    (v) => {
        if (!v) showPicker.value = false
    }
)
</script>
