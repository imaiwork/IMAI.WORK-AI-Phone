import type { Ref } from 'vue'
import { replaceState } from '@/utils/util'
import feedback from '@/utils/feedback'
import type { useUserStore } from '@/stores/user'

export interface DrawModelItem {
    id: string
    name: string
    model_id: number
    model_sub_id: number
    channel: string
    alias: string
    media_type: string
    status: string
    logo?: string
    unit_price?: number
}

export type DrawConvIdMap = { image: number; video: number; ppt: number }

export function resolveDrawErrorMsg(e: any, fallback = '生成失败'): string {
    if (typeof e === 'string' && e.trim()) return e
    const msg = e?.msg || e?.message || e?.data?.msg
    if (typeof msg === 'string' && msg.trim()) return msg
    return fallback
}

export function useWelcomeDrawShared(deps: {
    drawConvId: DrawConvIdMap
    userTokens: Ref<number>
    userStore: ReturnType<typeof useUserStore>
}) {
    const { drawConvId, userTokens, userStore } = deps

    function ensureHasTokens(): boolean {
        if (userTokens.value <= 0) {
            feedback.msgPowerInsufficient()
            return false
        }
        return true
    }

    function ensureEnoughTokens(scene: string, count: number): boolean {
        const score = Number(userStore.getTokenByScene(scene)?.score) || 0
        const need = score * Math.max(1, count)
        if (userTokens.value < need) {
            feedback.msgPowerInsufficient()
            return false
        }
        return true
    }

    function bindDrawConversation(mode: 'image' | 'video' | 'ppt', id: number | string) {
        const num = Number(id) || 0
        if (num <= 0) return
        if (mode === 'image') drawConvId.image = num
        else if (mode === 'video') drawConvId.video = num
        else drawConvId.ppt = num
        replaceState({
            draw_conversation_id: String(num),
            draw_mode: mode
        })
    }

    function clearDrawConversationUrl() {
        replaceState({
            draw_conversation_id: undefined,
            draw_mode: undefined
        })
    }

    return {
        ensureHasTokens,
        ensureEnoughTokens,
        bindDrawConversation,
        clearDrawConversationUrl
    }
}
