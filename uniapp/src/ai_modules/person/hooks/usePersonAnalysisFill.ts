import { createPersonAnalysis } from '@/api/person'
import {
    ACCOUNT_STYLE_OPTION_NAMES,
    ACTION_GOAL_OPTION_NAMES,
    INDUSTRY_DIRECTION_OPTION_NAMES,
    type BasicFormData,
    type PersonFormData
} from './usePersonFormCore'

/** AI 分析接口返回的统一字段（不再按人设类型区分 key） */
export interface PersonAnalysisResult {
    ip_name?: string
    ip_intro?: string
    what_you_do?: string | string[]
    main_share?: string
    target_viewers?: string
    tone?: string | string[]
    location_city?: string
    desired_action?: string | string[]
    what_you_sell?: string
    target_buyers?: string
    advantage?: string
}

/**
 * 把 AI 返回的自由文本模糊匹配到选择器可选项。
 * 匹配不到返回空数组——由调用方保留原值，不能用「未匹配」覆盖用户已填内容。
 */
const matchOptions = (
    value: string | string[] | undefined,
    availableOptions: readonly string[]
): string[] => {
    if (!value) return []
    const items = (Array.isArray(value) ? value : String(value).split(/[,，;；、\s]+/))
        .map((item) => String(item).trim())
        .filter((item) => item.length > 0)

    const matched: string[] = []
    items.forEach((item) => {
        const hit = availableOptions.find(
            (option) => option === item || option.includes(item) || item.includes(option)
        )
        if (hit && !matched.includes(hit)) matched.push(hit)
    })
    return matched
}

/**
 * 人设 AI 分析的「请求 + 回填」。
 * 语音转写与手动输入文字共用这一套：两条链路只要都在这里回填，
 * 字段映射就不会各写一份、后续改字段时漏掉其中一条。
 */
export function usePersonAnalysisFill(basicForm: BasicFormData, formData: PersonFormData) {
    const applyResult = (result: PersonAnalysisResult): void => {
        // 步骤1 基础信息（绑定 basicForm，直接回显）
        if (result.ip_name) basicForm.ai_name = result.ip_name
        if (result.ip_intro) basicForm.intro = result.ip_intro
        if (result.main_share) basicForm.content_direction = result.main_share
        if (result.target_viewers) basicForm.target_audience = result.target_viewers
        if (result.location_city) basicForm.city_position = result.location_city

        // 步骤1 选择器（匹配到可选项才回填，匹配不到保留原值）
        const matchedIndustry = matchOptions(result.what_you_do, INDUSTRY_DIRECTION_OPTION_NAMES)
        if (matchedIndustry.length) basicForm.industry_direction = matchedIndustry
        const matchedStyle = matchOptions(result.tone, ACCOUNT_STYLE_OPTION_NAMES)
        if (matchedStyle.length) basicForm.account_style = matchedStyle[0]
        const matchedGoal = matchOptions(result.desired_action, ACTION_GOAL_OPTION_NAMES)
        if (matchedGoal.length) basicForm.action_goal = matchedGoal[0]

        // 步骤2 业务描述（绑定 formData，直接回显）
        if (result.what_you_sell) formData.main_business = result.what_you_sell
        if (result.target_buyers) formData.target_pain_points = result.target_buyers
        if (result.advantage) formData.conversion_hook = result.advantage
    }

    const analyzeByContents = async (contents: string): Promise<PersonAnalysisResult> => {
        const result = await createPersonAnalysis({
            contents,
            model: formData.persona_type
        })
        applyResult(result || {})
        return result || {}
    }

    return { analyzeByContents }
}
