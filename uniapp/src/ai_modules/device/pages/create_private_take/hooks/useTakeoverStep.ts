import { getPrivateChatTaskFixedHistory, deletePrivateChatTaskFixedHistory } from '@/api/device'
import type {
    PrivateChatFormData,
    TakeoverType,
    StrategyType,
    CommentRule,
    ScriptTarget
} from './types'
import {
    TakeoverTypeEnum,
    StrategyTypeEnum,
    CommentRuleEnum,
    SCRIPT_EDIT_TITLE_MAP,
    ScriptTargetEnum
} from './types'

/** 话术列表折叠阈值（超过此数量显示展开/折叠按钮） */
const SCRIPT_COLLAPSE_THRESHOLD = 5

/**
 * Step1 接管设置：
 *  - 接管类型切换
 *  - 私信策略切换
 *  - 评论互动规则
 *  - 智能体选择
 *  - 话术快捷添加 & 弹窗编辑
 *  - 私信回复上限
 *  - 话术展开/折叠
 *  - 话术删除（调用接口）
 */
export function useTakeoverStep(formData: PrivateChatFormData) {
    // ── 接管类型 ──────────────────────────────────────────────────
    const takeoverType = ref<TakeoverType>(TakeoverTypeEnum.COMMENT)
    const strategyType = ref<StrategyType>(StrategyTypeEnum.AI)
    const commentRule = ref<CommentRule>(CommentRuleEnum.AI)
    const pmReplyLimit = ref(0)

    const switchTakeoverType = (type: TakeoverType) => {
        takeoverType.value = type
        getFixedHistory()
    }
    const switchStrategy = (type: StrategyType) => {
        strategyType.value = type
    }

    // ── 智能体 ────────────────────────────────────────────────────
    const showChooseAgent = ref(false)
    const selectedCommentAgent = ref<{ id: string; name: string } | null>(null)
    const selectedMessageAgent = ref<{ id: string; name: string } | null>(null)

    const handleChooseAgent = () => {
        showChooseAgent.value = true
        if (takeoverType.value === TakeoverTypeEnum.COMMENT) {
            chooseAgentRef.value?.setChooseLists([selectedCommentAgent.value ?? {}])
        } else {
            chooseAgentRef.value?.setChooseLists([selectedMessageAgent.value ?? {}])
        }
    }
    const handleChooseAgentConfirm = (data: any) => {
        if (takeoverType.value === TakeoverTypeEnum.COMMENT) {
            if (data) selectedCommentAgent.value = { id: data.id, name: data.name }
        } else {
            if (data) selectedMessageAgent.value = { id: data.id, name: data.name }
        }
    }

    // ── 话术快捷添加 ──────────────────────────────────────────────
    const scriptInput = ref('') // 私信话术输入框
    const commentScriptInput = ref('') // 评论话术输入框

    const handleAddScriptByInput = (target: ScriptTarget) => {
        const inputRef = target === 'fixed_scripts' ? scriptInput : commentScriptInput
        const val = inputRef.value.trim()
        if (!val) return
        if (formData[target].includes(val)) {
            uni.$u.toast('该话术已存在')
            return
        }
        formData[target].push(val)
        inputRef.value = ''
    }

    // ── 话术弹窗编辑 ──────────────────────────────────────────────
    const showScriptEdit = ref(false)
    const scriptEditTarget = ref<ScriptTarget>(ScriptTargetEnum.FIXED)
    const scriptEditIndex = ref(-1)
    const keywordsEditRef = ref<any>()
    const chooseAgentRef = ref<any>()

    const scriptEditTitle = computed(() => SCRIPT_EDIT_TITLE_MAP[scriptEditTarget.value])

    /**
     * 打开话术编辑弹窗
     * @param idx    -1 = 新增，≥0 = 编辑
     * @param target 话术字段
     */
    const openScriptEdit = (idx: number, target: ScriptTarget) => {
        scriptEditTarget.value = target
        scriptEditIndex.value = idx
        showScriptEdit.value = true
        // 回显当前值
        const current = idx >= 0 ? formData[target][idx] ?? '' : ''
        keywordsEditRef.value?.setFormData(current)
    }

    /** keywords-edit 确认回调 */
    const handleScriptConfirm = (value: string) => {
        const val = value.trim()
        if (!val) return
        const list = formData[scriptEditTarget.value]
        if (scriptEditIndex.value === -1) {
            if (list.includes(val)) {
                uni.$u.toast('该话术已存在')
                return
            }
            list.push(val)
        } else {
            list[scriptEditIndex.value] = val
        }
        showScriptEdit.value = false
    }

    // ── 话术历史记录 ──────────────────────────────────────────────
    const fixedHistoryMap = ref<Record<ScriptTarget, any>>({
        [ScriptTargetEnum.FIXED]: [] as any[],
        [ScriptTargetEnum.COMMENT]: [] as any[]
    })
    const getFixedHistory = async () => {
        const { lists } = await getPrivateChatTaskFixedHistory({
            type: takeoverType.value,
            page_size: 25000
        })
        if (takeoverType.value === TakeoverTypeEnum.COMMENT) {
            formData.comment_scripts = lists.map((item: any) => item.keyword)
            fixedHistoryMap.value[ScriptTargetEnum.COMMENT] = lists
        } else {
            formData.fixed_scripts = lists.map((item: any) => item.keyword)
            fixedHistoryMap.value[ScriptTargetEnum.FIXED] = lists
        }
    }

    // ── 话术删除（调用接口）──────────────────────────────────────
    /**
     * 删除固定话术
     * @param target 话术字段（fixed_scripts / comment_scripts）
     * @param idx    列表索引
     */
    const handleDeleteScript = async (target: ScriptTarget, idx: number) => {
        const list = formData[target]
        const keyword = list[idx]
        const id = fixedHistoryMap.value[target].find((item: any) => item.keyword === keyword)?.id
        if (!keyword) return
        uni.showLoading({
            title: '删除中...',
            mask: true
        })
        try {
            await deletePrivateChatTaskFixedHistory({
                id,
                keyword
            })
            list.splice(idx, 1)
            uni.hideLoading()
        } catch (error: any) {
            uni.hideLoading()
            uni.showToast({
                title: error,
                icon: 'none',
                duration: 3000
            })
        }
    }

    // ── 话术一键删除 ─────────────────────────────────────────────
    /**
     * 清空当前接管类型下的所有固定话术
     * 只传 type，不传 keyword
     */
    const handleClearAllScripts = async (target: ScriptTarget) => {
        await uni.showModal({
            title: '提示',
            content: '确定要删除所有话术吗？',
            success: async (res) => {
                if (res.confirm) {
                    uni.showLoading({
                        title: '删除中...',
                        mask: true
                    })
                    try {
                        await deletePrivateChatTaskFixedHistory({
                            type: takeoverType.value
                        })
                        formData[target] = []
                        uni.hideLoading()
                    } catch (error: any) {
                        uni.hideLoading()
                        uni.showToast({
                            title: error,
                            icon: 'none',
                            duration: 3000
                        })
                    }
                }
            }
        })
    }

    // ── 话术展开 / 折叠 ──────────────────────────────────────────
    /** 私信话术是否展开 */
    const fixedScriptsExpanded = ref(false)
    /** 评论话术是否展开 */
    const commentScriptsExpanded = ref(false)

    /**
     * 根据是否展开返回需要渲染的话术切片
     * 未展开时只显示前 SCRIPT_COLLAPSE_THRESHOLD 条
     */
    const visibleFixedScripts = computed(() =>
        fixedScriptsExpanded.value
            ? formData.fixed_scripts
            : formData.fixed_scripts.slice(0, SCRIPT_COLLAPSE_THRESHOLD)
    )

    const visibleCommentScripts = computed(() =>
        commentScriptsExpanded.value
            ? formData.comment_scripts
            : formData.comment_scripts.slice(0, SCRIPT_COLLAPSE_THRESHOLD)
    )

    /** 私信话术是否超出阈值（需要显示展开按钮） */
    const fixedScriptsOverflow = computed(
        () => formData.fixed_scripts.length > SCRIPT_COLLAPSE_THRESHOLD
    )
    /** 评论话术是否超出阈值 */
    const commentScriptsOverflow = computed(
        () => formData.comment_scripts.length > SCRIPT_COLLAPSE_THRESHOLD
    )

    const toggleFixedScripts = () => {
        fixedScriptsExpanded.value = !fixedScriptsExpanded.value
    }
    const toggleCommentScripts = () => {
        commentScriptsExpanded.value = !commentScriptsExpanded.value
    }

    onMounted(() => {
        getFixedHistory()
    })

    return {
        // 接管类型
        takeoverType,
        strategyType,
        commentRule,
        pmReplyLimit,
        switchTakeoverType,
        switchStrategy,
        // 智能体
        showChooseAgent,
        selectedCommentAgent,
        selectedMessageAgent,
        handleChooseAgent,
        handleChooseAgentConfirm,
        // 话术快捷添加
        scriptInput,
        commentScriptInput,
        handleAddScriptByInput,
        // 话术弹窗编辑
        showScriptEdit,
        scriptEditTitle,
        keywordsEditRef,
        openScriptEdit,
        handleScriptConfirm,
        chooseAgentRef,
        // 话术删除
        handleDeleteScript,
        handleClearAllScripts,
        // 话术展开/折叠
        fixedScriptsExpanded,
        commentScriptsExpanded,
        visibleFixedScripts,
        visibleCommentScripts,
        fixedScriptsOverflow,
        commentScriptsOverflow,
        toggleFixedScripts,
        toggleCommentScripts,
        SCRIPT_COLLAPSE_THRESHOLD
    }
}
