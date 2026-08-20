export type GeoConfirmTone = 'info' | 'warning' | 'danger'

export interface GeoConfirmFact {
    label: string
    value: string
    emphasize?: boolean
}

export interface GeoConfirmOptions {
    title: string
    message?: string
    confirmText?: string
    cancelText?: string
    tone?: GeoConfirmTone
    facts?: GeoConfirmFact[]
    note?: string
    impacts?: string[]
}

export interface GeoConfirmState {
    title: string
    message: string
    confirmText: string
    cancelText: string
    tone: GeoConfirmTone
    facts: GeoConfirmFact[]
    note: string
    impacts: string[]
}

const DEFAULTS: GeoConfirmState = {
    title: '请确认',
    message: '',
    confirmText: '确定',
    cancelText: '取消',
    tone: 'info',
    facts: [],
    note: '',
    impacts: []
}

export const geoConfirmVisible = ref(false)
export const geoConfirmState = ref<GeoConfirmState>({ ...DEFAULTS })

let settled = false
let resolveFn: ((v: true) => void) | null = null
let rejectFn: ((e: string) => void) | null = null

const settle = (ok: boolean) => {
    if (settled) return
    settled = true
    geoConfirmVisible.value = false
    if (ok) resolveFn?.(true)
    else rejectFn?.('cancel')
    resolveFn = null
    rejectFn = null
}

export function geoConfirm(options: GeoConfirmOptions): Promise<true> {
    if (geoConfirmVisible.value) settle(false)
    settled = false
    geoConfirmState.value = {
        ...DEFAULTS,
        ...options,
        message: options.message || '',
        confirmText: options.confirmText || DEFAULTS.confirmText,
        cancelText: options.cancelText || DEFAULTS.cancelText,
        tone: options.tone || DEFAULTS.tone,
        facts: options.facts || [],
        note: options.note || '',
        impacts: options.impacts || []
    }
    geoConfirmVisible.value = true
    return new Promise((resolve, reject) => {
        resolveFn = resolve
        rejectFn = reject
    })
}

export function resolveGeoConfirm() {
    settle(true)
}

export function rejectGeoConfirm() {
    settle(false)
}
