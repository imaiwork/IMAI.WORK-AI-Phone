<template>
    <GeoDialog
        :model-value="geoConfirmVisible"
        layout="hero"
        :title="s.title"
        :desc="s.message"
        :tone="s.tone"
        :confirm-text="s.confirmText"
        :cancel-text="s.cancelText"
        @confirm="resolveGeoConfirm"
        @cancel="rejectGeoConfirm">
        <div v-if="s.facts.length" class="geo-dialog__facts" :class="`is-${s.tone}`">
            <div v-for="f in s.facts" :key="f.label" class="flex items-center justify-between gap-3 text-[13px]">
                <span class="text-slate-500">{{ f.label }}</span>
                <span :class="f.emphasize ? 'font-bold text-primary text-[15px]' : 'font-semibold text-slate-700'">{{ f.value }}</span>
            </div>
            <div v-if="s.note" class="text-[12px] text-slate-400 pt-1 border-t border-black/5">{{ s.note }}</div>
        </div>
        <div v-else-if="s.impacts.length" class="geo-dialog__facts" :class="`is-${s.tone}`">
            <div v-for="(item, i) in s.impacts" :key="i" class="flex items-start gap-2.5">
                <span class="w-4 h-4 rounded-full grid place-items-center shrink-0 mt-0.5" :class="impactDot">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-2.5 h-2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v9m0 3.5h.01" />
                    </svg>
                </span>
                <span class="text-[13px] text-slate-600 leading-relaxed">{{ item }}</span>
            </div>
        </div>
    </GeoDialog>
</template>

<script setup lang="ts">
import GeoDialog from './geo-dialog.vue'
import { geoConfirmState, geoConfirmVisible, rejectGeoConfirm, resolveGeoConfirm } from '../_composables/geo-confirm'

const s = geoConfirmState
const impactDot = computed(() => {
    if (s.value.tone === 'danger') return 'bg-red-100 text-red-500'
    if (s.value.tone === 'warning') return 'bg-amber-100 text-amber-500'
    return 'bg-[#E8EEFF] text-primary'
})
</script>
