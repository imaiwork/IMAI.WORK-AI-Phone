// ════════════════════════════════════════════════════════════════
// hooks/useReplySettings.ts  —  Step1 回复设置（敏感词 / 自动加群）
// ════════════════════════════════════════════════════════════════
import type { WechatPrivateFormData } from './types'
import KeywordsEdit from '@/ai_modules/device/components/keywords-edit/keywords-edit.vue'
import { getGroupTriggerKeywords } from '@/api/device'

const normalizeGroupTriggerKeywords = (list: unknown): string[] => {
    if (!Array.isArray(list)) return []
    return list.map((item) => String(item).trim()).filter(Boolean)
}

export function useReplySettings(formData: WechatPrivateFormData) {
    // ── 敏感词 ────────────────────────────────────────────────────
    const showKeywordsEdit = ref(false)
    const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>()
    const editSensitiveWordIndex = ref(-1)
    const editGroupTriggerKeywordIndex = ref(-1)
    const keywordEditType = ref<'sensitive' | 'groupTrigger'>('sensitive')
    const keywordEditTitle = computed(() =>
        keywordEditType.value === 'groupTrigger' ? '加群触发词' : '敏感词设置'
    )
    const keywordEditMaxlength = computed(() => (keywordEditType.value === 'groupTrigger' ? 20 : 100))

    const handleSensitiveWordEdit = (index: number) => {
        keywordEditType.value = 'sensitive'
        editSensitiveWordIndex.value = index
        showKeywordsEdit.value = true
        if (index >= 0) {
            keywordsEditRef.value?.setFormData(formData.sensitive_word[index])
        }
    }

    const handleSensitiveWordDelete = (index: number) => {
        formData.sensitive_word.splice(index, 1)
    }

    const handleKeywordsConfirm = (data: string) => {
        if (keywordEditType.value === 'groupTrigger') {
            if (formData.group_trigger_keywords.some((item, index) => item === data && index !== editGroupTriggerKeywordIndex.value)) {
                uni.$u.toast('该触发词已存在')
                return
            }
            if (editGroupTriggerKeywordIndex.value >= 0) {
                formData.group_trigger_keywords[editGroupTriggerKeywordIndex.value] = data
            } else {
                formData.group_trigger_keywords.push(data)
            }
            showKeywordsEdit.value = false
            editGroupTriggerKeywordIndex.value = -1
        } else {
            if (editSensitiveWordIndex.value >= 0) {
                formData.sensitive_word[editSensitiveWordIndex.value] = data
            } else {
                formData.sensitive_word.push(data)
            }
            showKeywordsEdit.value = false
            editSensitiveWordIndex.value = -1
        }
    }

    // ── 自动加群 ──────────────────────────────────────────────────
    const groupSalesInput = ref('')
    const GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT = 3
    const showGroupTriggerKeywordsMore = ref(false)
    const visibleGroupTriggerKeywords = computed(() =>
        showGroupTriggerKeywordsMore.value
            ? formData.group_trigger_keywords
            : formData.group_trigger_keywords.slice(0, GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT)
    )
    const hiddenGroupTriggerKeywordCount = computed(() =>
        Math.max(formData.group_trigger_keywords.length - GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT, 0)
    )

    const setGroupTriggerMode = (mode: 1 | 2) => {
        formData.group_trigger_mode = mode
        showGroupTriggerKeywordsMore.value = false
    }

    const queryGroupTriggerKeywords = async () => {
        try {
            const data = await getGroupTriggerKeywords()
            const keywords = normalizeGroupTriggerKeywords(data?.group_trigger_keywords)
            if (keywords.length) {
                formData.group_trigger_keywords = keywords
                showGroupTriggerKeywordsMore.value = false
            }
        } catch {
            // 保留当前关键词，不打断页面
        }
    }

    const handleGroupTriggerKeywordEdit = (index: number) => {
        keywordEditType.value = 'groupTrigger'
        editGroupTriggerKeywordIndex.value = index
        showKeywordsEdit.value = true
        if (index >= 0) {
            keywordsEditRef.value?.setFormData(formData.group_trigger_keywords[index])
        }
    }

    const handleRemoveGroupTriggerKeyword = (index: number) => {
        formData.group_trigger_keywords.splice(index, 1)
    }

    const handleClearAllGroupTriggerKeywords = () => {
        if (!formData.group_trigger_keywords.length) return
        uni.showModal({
            title: '提示',
            content: '确定清空全部自定义触发词吗？',
            success: (res) => {
                if (res.confirm) {
                    formData.group_trigger_keywords = []
                    showGroupTriggerKeywordsMore.value = false
                }
            },
        })
    }

    const handleAddGroupSales = () => {
        const val = groupSalesInput.value.trim()
        if (!val) return
        if (formData.sales_wechat.length >= 5) {
            uni.$u.toast('最多添加5个销售微信')
            return
        }
        if (formData.sales_wechat.includes(val)) {
            uni.$u.toast('该微信号已添加')
            return
        }
        formData.sales_wechat.push(val)
        groupSalesInput.value = ''
    }

    const handleRemoveGroupSales = (index: number) => {
        formData.sales_wechat.splice(index, 1)
    }

    const insertGroupNameTemplate = (variable: string) => {
        if (formData.group_name_template.length + variable.length > 32) {
            uni.$u.toast('群名称模板最多32个字符')
            return
        }
        formData.group_name_template += variable
    }

    const insertWelcomeContent = (variable: string) => {
        formData.greeting_text += variable
    }

    return {
        // 敏感词
        showKeywordsEdit,
        keywordsEditRef,
        keywordEditTitle,
        keywordEditMaxlength,
        handleSensitiveWordEdit,
        handleSensitiveWordDelete,
        handleKeywordsConfirm,
        // 自动加群
        groupSalesInput,
        GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT,
        showGroupTriggerKeywordsMore,
        visibleGroupTriggerKeywords,
        hiddenGroupTriggerKeywordCount,
        setGroupTriggerMode,
        queryGroupTriggerKeywords,
        handleGroupTriggerKeywordEdit,
        handleRemoveGroupTriggerKeyword,
        handleClearAllGroupTriggerKeywords,
        handleAddGroupSales,
        handleRemoveGroupSales,
        insertGroupNameTemplate,
        insertWelcomeContent
    }
}
