import { isCommentUserAction, TaskType, STEPS } from './types'
import type { PublishFormData } from './types'

/**
 * 步骤导航 & 每步校验
 */
export function useStep(formData: PublishFormData) {
    const step = ref(1)
    const hasCommentUserAction = () => formData.marker_method.some(isCommentUserAction)

    // ── 每步通过条件 ──────────────────────────────────────────────
    const canStepProceed = (s: number): boolean => {
        switch (s) {
            case 1:
                if (formData.group_buy_type == TaskType.SEARCH && formData.group_type == '')
                    return false
                if (formData.filter.length === 0) return false
                if (!hasCommentUserAction()) return false
                if (!formData.persona_id) return false
                if (!formData.watch_time || Number(formData.watch_time) === 0) return false
                if (!formData.interval_time || Number(formData.interval_time) === 0) return false
                return true
            case 2:
                return formData.custom_date.length > 0
            default:
                return false
        }
    }

    // ── 每步错误提示 ──────────────────────────────────────────────
    const getStepErrorMsg = (s: number): string => {
        const map: Record<number, () => string> = {
            1: () => {
                if (formData.group_buy_type == TaskType.SEARCH && formData.group_type == '')
                    return '请输入团购类型'
                if (formData.filter.length === 0) return '请至少输入一个评论关键词'
                if (!hasCommentUserAction()) return '请至少选择一个执行动作'
                if (!formData.persona_id) return '请选择IP人设'
                if (!formData.watch_time || Number(formData.watch_time) === 0)
                    return '观看视频秒数不能为0'
                if (!formData.interval_time || Number(formData.interval_time) === 0)
                    return '触达间隔不能为0'
                return ''
            },
            2: () => '请设定时间'
        }
        return map[s]?.() ?? '请完成当前步骤'
    }

    const canNext = computed(() => canStepProceed(step.value))

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
        } else {
            for (let i = 1; i < targetStep; i++) {
                if (!canStepProceed(i)) {
                    uni.$u.toast('请按顺序完成步骤')
                    return
                }
            }
            step.value = targetStep
        }
    }

    return { STEPS, step, canNext, handleStep }
}
