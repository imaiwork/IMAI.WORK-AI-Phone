import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const MILLISECONDS_PER_SECOND = 1000
const SECONDS_PER_MINUTE = 60
const SECONDS_PER_HOUR = SECONDS_PER_MINUTE * SECONDS_PER_MINUTE
const pad = (value: number): string => String(value).padStart(2, '0')

export const useFirstRunCountdown = (nextRunAt: () => number, enabled: () => boolean) => {
    const remainSeconds = ref(0)
    let mounted = false
    let timer: ReturnType<typeof setInterval> | null = null

    const stop = (): void => {
        if (timer !== null) clearInterval(timer)
        timer = null
    }

    const syncRemain = (): void => {
        // 每次重新计算时间差，页面恢复或定时器延迟时不会累积误差。
        remainSeconds.value = enabled()
            ? Math.max(0, nextRunAt() - Math.floor(Date.now() / MILLISECONDS_PER_SECOND))
            : 0
        if (remainSeconds.value === 0) stop()
    }

    const restart = (): void => {
        stop()
        syncRemain()
        if (mounted && remainSeconds.value > 0) {
            timer = setInterval(syncRemain, MILLISECONDS_PER_SECOND)
        }
    }

    const remainText = computed(() => {
        const total = remainSeconds.value
        return `${pad(Math.floor(total / SECONDS_PER_HOUR))}:${pad(
            Math.floor((total % SECONDS_PER_HOUR) / SECONDS_PER_MINUTE)
        )}:${pad(total % SECONDS_PER_MINUTE)}`
    })

    watch([nextRunAt, enabled], restart, { immediate: true })
    onMounted(() => {
        mounted = true
        restart()
    })
    onUnmounted(() => {
        mounted = false
        stop()
    })

    return { remainSeconds, remainText }
}
