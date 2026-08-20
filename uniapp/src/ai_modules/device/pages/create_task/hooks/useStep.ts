import type { FormData } from './types'

export function useStep(formData: FormData) {
    const currentStep = ref(1)

    const STEP_MESSAGES: Record<number, string> = {
        1: '请至少选择一个素材',
        2: '请至少添加一条文案'
    }

    /** 校验指定步骤是否满足条件，不满足时弹 toast */
    const checkStep = (step: number): boolean => {
        const valid =
            step === 1
                ? formData.materialList.length > 0
                : step === 2
                ? formData.copywriterList.length > 0
                : true
        if (!valid) uni.$u.toast(STEP_MESSAGES[step] || '请完成当前步骤')
        return valid
    }

    /** 当前步骤是否可以进入下一步（不弹 toast，仅用于按钮样式） */
    const canProceedNext = computed(() => {
        if (currentStep.value === 1) return formData.materialList.length > 0
        if (currentStep.value === 2) return formData.copywriterList.length > 0
        return true
    })

    /** 底部按钮：上一步 / 下一步 */
    const navigateStep = (direction: 'next' | 'prev') => {
        if (direction === 'prev') {
            currentStep.value--
            return
        }
        if (canProceedNext.value) {
            currentStep.value++
        } else {
            uni.$u.toast(STEP_MESSAGES[currentStep.value] || '请完成当前步骤')
        }
    }

    /** 点击步骤条跳转（需校验中间每一步） */
    const handleStepJump = (targetStep: number) => {
        if (targetStep === currentStep.value) return
        if (targetStep < currentStep.value) {
            currentStep.value = targetStep
            return
        }
        for (let i = 1; i < targetStep; i++) {
            if (!checkStep(i)) return
        }
        currentStep.value = targetStep
    }

    return { currentStep, canProceedNext, navigateStep, handleStepJump }
}
