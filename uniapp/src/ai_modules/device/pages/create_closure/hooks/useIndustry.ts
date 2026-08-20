// ================================================================
// hooks/useIndustryStep.ts  —  Step1 行业线索词管理 & 历史记录
// ================================================================
import {
    getClosureIndustryHistory,
    deleteClosureIndustryHistory,
    getTaskClosureIndustryHistory
} from '@/api/device'
import type { ClosureFormData, CommentFilterItem } from './types'

export function useIndustryStep(
    formData: ClosureFormData,
    isCollect: Ref<boolean>,
    isComment: Ref<boolean>,
    commentIndex: Ref<number>,
    commentFilterRef: Ref<any>,
    commentContentMap: Record<number, string[]>,
    commentFilterMap: Record<string, CommentFilterItem[]>,
    currentFilterMapKey: ComputedRef<string>
) {
    const industryInput = ref<string>('')
    const scrollToIndustryId = ref<string>('')
    const showClueGenPopup = ref(false)
    const showHistoryIndustryPopup = ref(false)
    const historyIndustry = ref<any[]>([])
    const industryHistoryPagingRef = shallowRef()

    // ── 添加线索词 ────────────────────────────────────────────────
    const handleAddIndustry = () => {
        const val = industryInput.value.trim()
        if (!val) {
            uni.$u.toast('请输入获客行业')
            return
        }
        if (formData.industry.includes(val)) {
            uni.$u.toast('已存在')
            return
        }
        formData.industry.push(val)
        industryInput.value = ''
        nextTick(() => {
            scrollToIndustryId.value = 'industry_' + (formData.industry.length - 1)
        })
    }

    const handleDeleteClue = (index: number) => formData.industry.splice(index, 1)

    const handleClueGenConfirm = (clueList: string[]) => {
        formData.industry.push(...clueList)
        showClueGenPopup.value = false
    }

    // ── 历史记录 ──────────────────────────────────────────────────
    const getIndustryHistory = async (page_no: number, page_size: number) => {
        try {
            const { lists } = await getClosureIndustryHistory({
                task_type: isCollect.value ? 3 : isComment.value ? 1 : 2,
                page_no,
                page_size
            })
            if (!industryHistoryPagingRef.value) {
                historyIndustry.value = lists
            }
            industryHistoryPagingRef.value?.complete(lists)
        } catch {
            industryHistoryPagingRef.value?.complete([])
        }
    }

    const reloadIndustryHistory = () => industryHistoryPagingRef.value?.reload()

    const handleSelectHistoryIndustry = (keyword: string) => {
        if (formData.industry.includes(keyword)) {
            uni.$u.toast('已存在')
            return
        }
        formData.industry.push(keyword)
        showHistoryIndustryPopup.value = false
    }

    const handleDeleteHistoryIndustry = async (index: number) => {
        uni.showLoading({ title: '删除中...', mask: true })
        try {
            await deleteClosureIndustryHistory({ id: historyIndustry.value[index].id })
            historyIndustry.value.splice(index, 1)
            uni.hideLoading()
            uni.showToast({ title: '删除成功', icon: 'none', duration: 3000 })
        } catch (error: any) {
            uni.showToast({ title: error || '删除失败', icon: 'none', duration: 3000 })
        }
    }

    // ── 拉取公共历史（评论词 + 触达内容 + 固定话术） ─────────────
    const getClosureCommonHistory = async () => {
        try {
            const { comment_speech, msg_speech, mark_speech, filter, mark_filter } =
                await getTaskClosureIndustryHistory()

            commentContentMap[0] = [...(msg_speech ?? [])]
            commentContentMap[1] = [...comment_speech]
            commentFilterMap['closure'] = filter ?? []
            commentFilterMap['collect'] = mark_filter ?? []

            formData.comment_content_list = [...commentContentMap[commentIndex.value]]
            formData.comment_filter_list = [...commentFilterMap[currentFilterMapKey.value]].map(
                (item: any, idx: number) => ({ id: idx, value: item, checked: true })
            )
            formData.fixed_comment_list = mark_speech

            await nextTick()
            commentFilterRef.value?.setFormData(formData.comment_filter_list)
        } catch (error: any) {
            historyIndustry.value = []
        }
    }

    return {
        industryInput,
        scrollToIndustryId,
        showClueGenPopup,
        showHistoryIndustryPopup,
        historyIndustry,
        industryHistoryPagingRef,
        handleAddIndustry,
        handleDeleteClue,
        handleClueGenConfirm,
        getIndustryHistory,
        reloadIndustryHistory,
        handleSelectHistoryIndustry,
        handleDeleteHistoryIndustry,
        getClosureCommonHistory
    }
}
