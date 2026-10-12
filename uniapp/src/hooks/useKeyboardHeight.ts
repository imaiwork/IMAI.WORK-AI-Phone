import { ref, onMounted, onUnmounted } from 'vue'

/** 全端只有一块键盘，高度是所有调用方共享的值 */
const dynamicHeight = ref(0)

/**
 * 键盘高度监听跟页面走：离开再回来后，上一次注册的回调不再收到事件。
 * 运行时经常没有 offKeyboardHeightChange，不能先摘再挂。
 * 每次重新挂上时换一代序号，旧回调直接丢掉，避免切页面后堆多份监听把高度重新顶起来。
 */
let listenerGeneration = 0

/** 正在使用键盘高度的组件数；归零时复位，避免把「键盘还开着」的脏值带进下一个页面 */
let consumerCount = 0

/** 主动收起键盘后的保护窗口：这期间只接受 0，挡掉收起动画途中回调的中间高度 */
const CLOSE_GUARD_MS = 400
let closing = false
let closeTimer: ReturnType<typeof setTimeout> | null = null

const isWebPlatform = () => uni.getSystemInfoSync().uniPlatform === 'web'

const endClosing = () => {
    closing = false
    if (closeTimer) {
        clearTimeout(closeTimer)
        closeTimer = null
    }
}

const beginClosing = () => {
    closing = true
    if (closeTimer) clearTimeout(closeTimer)
    closeTimer = setTimeout(endClosing, CLOSE_GUARD_MS)
}

const applyHeight = (height: number) => {
    if (consumerCount === 0) return
    // 已经主动收起，收起动画途中回来的非 0 高度一律丢弃，否则 keyboardOpen 会来回抖
    if (closing && height > 0) return
    // 高度 0 不代表旧回调已经排空；保护只在到期或用户重新聚焦时解除。
    dynamicHeight.value = height
}

/** 输入框重新聚焦：键盘马上要弹回来，立刻解除收起保护窗口，别把这次的高度回调丢了 */
export function cancelKeyboardClosing() {
    endClosing()
}

/** 当前页重新挂上键盘高度监听。页面 onShow、以及本页第一个使用者挂载时都要调。 */
export function ensureKeyboardHeightListener() {
    if (isWebPlatform()) return
    const generation = ++listenerGeneration
    uni.onKeyboardHeightChange((res: { height: number }) => {
        if (generation !== listenerGeneration) return
        applyHeight(res.height)
    })
}

/** 复位共享高度并收起键盘：不依赖组件实例，页面 onHide / onUnload / 切工作台模式都能调 */
export function resetKeyboardHeight() {
    beginClosing()
    dynamicHeight.value = 0
    if (!isWebPlatform()) uni.hideKeyboard()
}

export default function useKeyboardHeight() {
    const isH5 = isWebPlatform()

    const handleResize = () => {
        // 获取 H5 页面可视区高度变化时的变化情况
        const newHeight = document.documentElement.clientHeight || window.innerHeight
        dynamicHeight.value = newHeight + 50
    }

    // 小程序端监听是全局的，这里只负责 H5 的 resize
    const onFocus = () => {
        if (isH5) window.addEventListener('resize', handleResize)
    }

    const onBlur = () => {
        dynamicHeight.value = 0
        if (isH5) window.removeEventListener('resize', handleResize)
    }

    const hideKeyboard = () => {
        beginClosing()
        dynamicHeight.value = 0
        if (!isH5) uni.hideKeyboard()
    }

    /** 只复位自己这一份状态：小程序端的全局监听是所有调用方共用的，不能在这里摘掉 */
    const removeKeyboardListener = () => {
        hideKeyboard()
        if (isH5) window.removeEventListener('resize', handleResize)
    }

    onMounted(() => {
        // 第一个使用者上场：不管上一轮留下什么脏值，都从「键盘没开」开始
        if (consumerCount === 0) {
            endClosing()
            dynamicHeight.value = 0
        }
        consumerCount++
        if (isH5) return
        if (consumerCount === 1) ensureKeyboardHeightListener()
    })

    onUnmounted(() => {
        consumerCount = Math.max(0, consumerCount - 1)
        // 最后一个使用者销毁：复位，避免下一个页面/面板一挂载就以为键盘还开着
        if (consumerCount === 0) {
            endClosing()
            dynamicHeight.value = 0
        }
        if (isH5) window.removeEventListener('resize', handleResize)
    })

    return {
        dynamicHeight,
        onFocus,
        onBlur,
        hideKeyboard,
        removeKeyboardListener
    }
}
