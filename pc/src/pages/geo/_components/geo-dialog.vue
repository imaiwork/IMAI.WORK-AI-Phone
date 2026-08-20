<template>
    <ElDialog
        :model-value="modelValue"
        :width="width"
        :show-close="false"
        align-center
        append-to-body
        class="geo-dialog"
        :close-on-click-modal="false"
        :destroy-on-close="destroyOnClose"
        @update:model-value="onVisible"
        @closed="emit('closed')">
        <button class="geo-dialog__close" type="button" aria-label="关闭" @click="onCancel">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>

        <div v-if="layout === 'hero'" class="text-center pt-3">
            <div class="geo-dialog__icon" :class="`is-${tone}`" aria-hidden="true">
                <slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath" />
                    </svg>
                </slot>
            </div>
            <h3 class="text-[19px] font-[800] text-slate-900 mt-4">{{ title }}</h3>
            <p v-if="desc" class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">{{ desc }}</p>
        </div>

        <div v-else class="flex items-start gap-3 pr-10 mb-5">
            <div class="geo-dialog__icon geo-dialog__icon--sm" :class="`is-${tone}`" aria-hidden="true">
                <slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath" />
                    </svg>
                </slot>
            </div>
            <div class="min-w-0">
                <h3 class="text-[17px] font-[800] text-slate-900 leading-tight">{{ title }}</h3>
                <p v-if="desc" class="text-[12px] text-slate-400 mt-1 leading-relaxed">{{ desc }}</p>
            </div>
        </div>

        <div class="geo-dialog__body" :class="layout === 'hero' ? 'mt-5' : ''">
            <slot />
        </div>

        <div v-if="$slots.footer || confirmText" class="mt-6">
            <slot name="footer">
                <div class="flex gap-3">
                    <ElButton v-if="cancelText" class="geo-dialog__btn !flex-1" @click="onCancel">{{ cancelText }}</ElButton>
                    <ElButton
                        v-if="confirmText"
                        :type="confirmType"
                        class="geo-dialog__btn !flex-1 !font-semibold"
                        :loading="confirmLoading"
                        :disabled="confirmDisabled"
                        @click="emit('confirm')">
                        {{ confirmText }}
                    </ElButton>
                </div>
            </slot>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
export type GeoDialogTone = 'info' | 'primary' | 'warning' | 'danger'
export type GeoDialogLayout = 'hero' | 'panel'

const ICON: Record<GeoDialogTone, string> = {
    info: 'M13 3v7h5l-7 11v-7H6l7-11z',
    primary: 'M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z',
    warning: 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
    danger: 'M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2'
}

const props = withDefaults(defineProps<{
    modelValue: boolean
    title: string
    desc?: string
    width?: string
    layout?: GeoDialogLayout
    tone?: GeoDialogTone
    confirmText?: string
    cancelText?: string
    confirmLoading?: boolean
    confirmDisabled?: boolean
    destroyOnClose?: boolean
}>(), {
    desc: '',
    width: '440px',
    layout: 'hero',
    tone: 'primary',
    confirmText: '',
    cancelText: '取消',
    confirmLoading: false,
    confirmDisabled: false,
    destroyOnClose: false
})

const emit = defineEmits<{
    'update:modelValue': [boolean]
    confirm: []
    cancel: []
    closed: []
}>()

const iconPath = computed(() => ICON[props.tone])
const confirmType = computed(() => (props.tone === 'danger' ? 'danger' : props.tone === 'warning' ? 'warning' : 'primary'))

const onCancel = () => {
    emit('update:modelValue', false)
    emit('cancel')
}
const onVisible = (v: boolean) => {
    if (!v) onCancel()
    else emit('update:modelValue', true)
}
</script>

<style lang="scss">
@import '../_styles/dialog.scss';
</style>
