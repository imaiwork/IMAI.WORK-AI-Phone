import { onUnmounted, ref } from 'vue'
import { onHide, onShow } from '@dcloudio/uni-app'

/** 低于这个高度不当成键盘，避开浏览器工具栏抖动 */
const MIN_KEYBOARD_PX = 80
/**
 * 收起键盘时晚一点把高度清成 0。
 * 点「确认文案」会先让输入框失焦、键盘开始收起，高度若马上变 0，按钮会在 click 落到之前被挪走。
 */
const KEYBOARD_CLOSE_DELAY_MS = 320

/**
 * 页面级键盘高度（px）。
 * 确认按钮要抬到键盘上方，不能跟聊天页那份共享高度搅在一起。
 * 离开页面后旧的 onKeyboardHeightChange 会失效，所以每次 onShow 重新挂，并用序号丢掉旧回调。
 */
export function usePageKeyboardInset() {
    const keyboardHeight = ref(0)
    let listening = false
    let paused = false
    let generation = 0
    let removeH5: (() => void) | null = null
    let nativeCallback: ((res: { height: number }) => void) | null = null
    let closeTimer: ReturnType<typeof setTimeout> | null = null

    const clearCloseTimer = () => {
        if (!closeTimer) return
        clearTimeout(closeTimer)
        closeTimer = null
    }

    const applyHeight = (height: number) => {
        if (!listening || paused) return
        const next = Math.round(height)
        const normalized = next >= MIN_KEYBOARD_PX ? next : 0
        if (normalized > 0) {
            clearCloseTimer()
            keyboardHeight.value = normalized
            return
        }
        if (keyboardHeight.value === 0 || closeTimer) return
        closeTimer = setTimeout(() => {
            closeTimer = null
            if (!listening || paused) return
            keyboardHeight.value = 0
        }, KEYBOARD_CLOSE_DELAY_MS)
    }

    const bindNative = () => {
        if (nativeCallback && typeof uni.offKeyboardHeightChange === 'function') {
            uni.offKeyboardHeightChange(nativeCallback)
        }
        const current = ++generation
        nativeCallback = (res: { height: number }) => {
            if (current !== generation) return
            applyHeight(res?.height || 0)
        }
        uni.onKeyboardHeightChange?.(nativeCallback)
    }

    const bind = () => {
        listening = true
        paused = false
        // #ifdef H5
        removeH5?.()
        const viewport = window.visualViewport
        const baseHeight = window.innerHeight
        const sync = () => {
            if (!viewport) {
                applyHeight(0)
                return
            }
            applyHeight(baseHeight - viewport.height - viewport.offsetTop)
        }
        viewport?.addEventListener('resize', sync)
        viewport?.addEventListener('scroll', sync)
        removeH5 = () => {
            viewport?.removeEventListener('resize', sync)
            viewport?.removeEventListener('scroll', sync)
        }
        sync()
        // #endif
        // #ifndef H5
        bindNative()
        // #endif
    }

    const unbind = () => {
        listening = false
        clearCloseTimer()
        keyboardHeight.value = 0
        generation += 1
        // #ifdef H5
        removeH5?.()
        removeH5 = null
        // #endif
        // #ifndef H5
        if (nativeCallback && typeof uni.offKeyboardHeightChange === 'function') {
            uni.offKeyboardHeightChange(nativeCallback)
        }
        nativeCallback = null
        // #endif
    }

    onShow(() => {
        bind()
    })

    onHide(() => {
        paused = true
        clearCloseTimer()
        keyboardHeight.value = 0
    })

    onUnmounted(() => {
        unbind()
    })

    return { keyboardHeight }
}
