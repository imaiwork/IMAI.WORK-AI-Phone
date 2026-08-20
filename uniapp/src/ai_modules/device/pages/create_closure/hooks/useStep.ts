import type { ClosureFormData } from './types'

export function useStep(
    formData: ClosureFormData,
    isCollect: Ref<boolean>,
    isPinLunWork: Ref<boolean>,
    getTouchTypeList: Ref<any[]>
) {
    const step = ref(1)

    const canStepProceed = (stepNumber: number): boolean => {
        switch (stepNumber) {
            case 1:
                return formData.customer_type === 0 ? formData.industry.length > 0 : true
            case 2:
                if (isCollect.value) {
                    const hasSelected = getTouchTypeList.value.some((item: any) => item.checked)
                    if (!hasSelected) return false
                    const hasComment = getTouchTypeList.value
                        .filter((i) => i.checked)
                        .map((i) => i.name)
                        .includes(4)
                    if (
                        hasComment &&
                        formData.comment_type === 1 &&
                        formData.fixed_comment_list.length === 0
                    ) {
                        return false
                    }
                    return true
                } else {
                    return (
                        formData.comment_filter_list.length > 0 &&
                        formData.comment_content_list.length > 0
                    )
                }
            case 3:
                return true
            default:
                return false
        }
    }

    const canNext = computed(() => canStepProceed(step.value))

    const getStepErrorMsg = (s: number): string => {
        const map: Record<number, () => string> = {
            1: () => '请至少添加一个线索',
            2: () => {
                if (formData.customer_type == 0) {
                    if (formData.comment_filter_list.length == 0) return '评论词筛选至少添加一个'
                    if (!isCollect.value && formData.comment_content_list.length == 0)
                        return '触达方式和话术至少添加一个'
                    if (isPinLunWork.value && formData.fixed_comment_list.length == 0)
                        return '固定话术至少添加一个'
                }
                return '请配置相关数据'
            },
            3: () => '请设定时间'
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

    return { step, canNext, handleStep }
}
