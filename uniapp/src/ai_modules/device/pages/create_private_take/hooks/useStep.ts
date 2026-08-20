import type { PrivateChatFormData, TakeoverType, StrategyType, CommentRule } from './types'
import { TakeoverTypeEnum, StrategyTypeEnum, CommentRuleEnum, STEPS } from './types'

interface StepNavDeps {
    formData: PrivateChatFormData
    takeoverType: Ref<TakeoverType>
    strategyType: Ref<StrategyType>
    commentRule: Ref<CommentRule>
    selectedCommentAgent: Ref<{ id: string; name: string } | null>
    selectedMessageAgent: Ref<{ id: string; name: string } | null>
}

export function useStep(deps: StepNavDeps) {
    const {
        formData,
        takeoverType,
        strategyType,
        commentRule,
        selectedCommentAgent,
        selectedMessageAgent
    } = deps

    const currentStep = ref(1)
    const isAutoGroupReady = (): boolean => {
        if (takeoverType.value !== TakeoverTypeEnum.PM || formData.is_auto_group !== 1) return true
        return (
            formData.sales_wechat.length > 0 &&
            !!formData.group_name_template.trim() &&
            (formData.is_greeting === 1 ? !!formData.greeting_text.trim() : true)
        )
    }

    // ── 每步校验 ──────────────────────────────────────────────────
    const canStepProceed = (s: number): boolean => {
        switch (s) {
            case 1: {
                if (takeoverType.value === TakeoverTypeEnum.COMMENT) {
                    if (commentRule.value === 1) return !!selectedCommentAgent.value
                    if (commentRule.value === 2) return formData.comment_scripts.length > 0
                    if (commentRule.value === 3) return true
                }
                if (takeoverType.value === TakeoverTypeEnum.PM) {
                    if (strategyType.value === StrategyTypeEnum.AI)
                        return !!selectedMessageAgent.value && isAutoGroupReady()
                    if (strategyType.value === StrategyTypeEnum.FIXED)
                        return formData.fixed_scripts.length > 0 && isAutoGroupReady()
                }
                return true
            }
            case 2:
                return formData.accounts.length > 0
            default:
                return false
        }
    }

    // ── 每步错误提示 ──────────────────────────────────────────────
    const getStepErrorMsg = (s: number): string => {
        switch (s) {
            case 1: {
                if (takeoverType.value === TakeoverTypeEnum.PM) {
                    if (strategyType.value === StrategyTypeEnum.AI && !selectedMessageAgent.value)
                        return '请选择关联智能体'
                    if (
                        strategyType.value === StrategyTypeEnum.FIXED &&
                        !formData.fixed_scripts.length
                    )
                        return '请至少添加一条私信话术'
                    if (formData.is_auto_group === 1 && formData.sales_wechat.length === 0)
                        return '请添加至少一个销售微信'
                    if (formData.is_auto_group === 1 && !formData.group_name_template.trim())
                        return '请输入群名称模板'
                    if (formData.is_auto_group === 1 && formData.is_greeting === 1 && !formData.greeting_text.trim())
                        return '请输入建群欢迎语内容'
                }
                if (takeoverType.value === TakeoverTypeEnum.COMMENT) {
                    if (commentRule.value === CommentRuleEnum.AI && !selectedCommentAgent.value)
                        return '请选择关联智能体'
                    if (
                        commentRule.value === CommentRuleEnum.FIXED &&
                        !formData.comment_scripts.length
                    )
                        return '请至少添加一条评论话术'
                }
                return '请完成接管设置'
            }
            case 2:
                return '请至少选择一个账号'
            default:
                return '请完成当前步骤'
        }
    }

    const canNext = computed(() => canStepProceed(currentStep.value))

    // ── 步骤跳转 ──────────────────────────────────────────────────
    const handleStep = (targetStep: number, type?: 'next' | 'prev') => {
        if (type === 'prev') {
            currentStep.value--
            return
        }
        if (type === 'next') {
            if (canNext.value) {
                currentStep.value++
            } else {
                uni.$u.toast(getStepErrorMsg(currentStep.value))
            }
            return
        }

        // 点击步骤条直接跳转
        if (targetStep === currentStep.value) return
        if (targetStep < currentStep.value) {
            currentStep.value = targetStep
            return
        }
        // 向后跳：逐步校验
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast('请按顺序完成步骤')
                return
            }
        }
        currentStep.value = targetStep
    }

    return {
        STEPS,
        currentStep,
        canNext,
        handleStep
    }
}
