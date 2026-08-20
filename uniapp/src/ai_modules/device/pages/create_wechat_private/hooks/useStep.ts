import type { WechatPrivateFormData } from './types'

export function useStep(formData: WechatPrivateFormData) {
    const step = ref(1)

    /** 校验指定步骤是否满足进入下一步的条件 */
    const canStepProceed = (stepNumber: number): boolean => {
        switch (stepNumber) {
            case 1:
                return (
                    (formData.interaction_action === 2 ? !!formData.interaction_content : true) &&
                    (formData.image_reply_type === 1 ? !!formData.image_reply_content : true) &&
                    (formData.voice_reply_type === 3 ? !!formData.voice_reply_content : true) &&
                    (formData.sensitive_word_switch === 1
                        ? formData.sensitive_word.length > 0
                        : true) &&
                    (formData.is_auto_group === 1 && formData.group_trigger_mode === 2
                        ? formData.group_trigger_keywords.length > 0
                        : true) &&
                    (formData.is_auto_group === 1 ? formData.sales_wechat.length > 0 : true) &&
                    (formData.is_auto_group === 1 && formData.is_greeting === 1
                        ? !!formData.greeting_text
                        : true)
                )
            case 2:
                return true
            default:
                return false
        }
    }

    /** 当前步骤是否可进入下一步（用于按钮状态） */
    const canNext = computed(() => canStepProceed(step.value))

    /** 获取步骤1的错误提示文案 */
    const getStep1ErrorMsg = (): string => {
        if (formData.interaction_action === 2 && !formData.interaction_content)
            return '请输入固定打招呼内容'
        if (formData.image_reply_type === 1 && !formData.image_reply_content)
            return '请输入图片固定回复内容'
        if (formData.sensitive_word_switch === 1 && formData.sensitive_word.length === 0)
            return '请输入敏感词'
        if (formData.voice_reply_type === 3 && !formData.voice_reply_content)
            return '请输入语音固定回复内容'
        if (
            formData.is_auto_group === 1 &&
            formData.group_trigger_mode === 2 &&
            formData.group_trigger_keywords.length === 0
        )
            return '请添加至少一个加群触发词'
        if (formData.is_auto_group === 1 && formData.sales_wechat.length === 0)
            return '请添加至少一个销售微信'
        if (formData.is_auto_group === 1 && formData.is_greeting === 1 && !formData.greeting_text)
            return '请输入建群欢迎语内容'
        return '请完成当前步骤'
    }

    /**
     * 统一步骤跳转入口
     * @param targetStep 目标步骤（点击步骤条时传入）
     * @param type       "next" | "prev"（点击按钮时传入）
     */
    const handleStep = (targetStep: number, type?: 'next' | 'prev') => {
        // 上一步
        if (type === 'prev') {
            step.value--
            return
        }

        // 下一步
        if (type === 'next') {
            if (canNext.value) {
                step.value++
            } else {
                uni.$u.toast(getStep1ErrorMsg())
            }
            return
        }

        // 点击步骤条直接跳转
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

    return { step, canNext, handleStep }
}
