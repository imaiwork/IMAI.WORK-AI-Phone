import { STEPS, SearchModeEnum } from './types'
import type { AccountSearchFormData } from './types'

/**
 * 四步向导的步骤流转与放行校验。
 * 走过的步骤可以点回去；往前跳必须把中间每一步都过一遍。
 */
export function useStep(formData: AccountSearchFormData) {
    const step = ref(1)

    /** 当前模式下正在编辑的那份名单：线索词 或 指定账号 */
    const currentClueList = computed(() =>
        formData.search_mode === SearchModeEnum.KEYWORD
            ? formData.keywords
            : formData.target_accounts
    )

    const canStepProceed = (stepNumber: number): boolean => {
        switch (stepNumber) {
            case 1:
                return true
            // 这一步空着，后面全是白设
            case 2:
                return currentClueList.value.length > 0
            case 3:
                return formData.scan_positions.length > 0 && formData.contact_types.length > 0
            default:
                return false
        }
    }

    const canNext = computed(() => canStepProceed(step.value))

    const getStepErrorMsg = (s: number): string => {
        const map: Record<number, () => string> = {
            2: () =>
                formData.search_mode === SearchModeEnum.KEYWORD
                    ? '先加至少一个线索词'
                    : '先加至少一个账号',
            3: () => {
                if (!formData.scan_positions.length) return '至少要留一个查看位置'
                return '至少要认一种联系方式'
            }
        }
        return map[s]?.() ?? '请完成当前步骤'
    }

    const handleStep = (targetStep: number, type?: 'next' | 'prev') => {
        if (type === 'prev') {
            step.value--
            return
        }

        if (type === 'next') {
            if (canNext.value) {
                step.value++
            } else {
                uni.$u.toast(getStepErrorMsg(step.value))
            }
            return
        }

        if (targetStep === step.value) return

        if (targetStep < step.value) {
            step.value = targetStep
            return
        }

        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast('请按顺序完成步骤')
                return
            }
        }
        step.value = targetStep
    }

    return { step, canNext, currentClueList, handleStep, STEPS }
}
