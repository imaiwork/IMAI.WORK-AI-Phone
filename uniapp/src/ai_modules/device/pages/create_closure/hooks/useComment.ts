// ================================================================
// hooks/useCommentStep.ts  —  Step2/3 评论词 / 触达内容 / 固定话术 / 高级筛选
// ================================================================
import {
    COMMENT_FILTER_DEFAULT_SHOW,
    COMMENT_CONTENT_DEFAULT_SHOW,
    FIXED_COMMENT_DEFAULT_SHOW,
    COMMENT_TIME_LIST,
    getCommentTimeIndex,
    normalizeCommentTimeValue
} from './types'
import type { ClosureFormData, CommentFilterItem } from './types'

type KeywordsEditType = 'clue' | 'comment' | 'comment_content' | 'fixed_comment'

export function useCommentStep(
    formData: ClosureFormData,
    isCollect: Ref<boolean>,
    commentIndex: Ref<number>,
    commentContentMap: Record<number, string[]>,
    commentFilterMap: Record<string, CommentFilterItem[]>,
    currentFilterMapKey: ComputedRef<string>
) {
    // ── component refs ────────────────────────────────────────────
    const keywordsEditRef = ref<any>()
    const commentFilterRef = ref<any>()
    const chooseAgeRef = ref<any>()

    // ── show flags ────────────────────────────────────────────────
    const showKeywordsEdit = ref(false)
    const showCommentFilterEdit = ref(false)
    const showAgePopup = ref(false)
    const showChooseRegionPopup = ref(false)
    const showCommentTimePopup = ref(false)

    const keywordsEditType = ref<KeywordsEditType>('clue')
    const keywordsEditIndex = ref<number>(-1)
    const commentTimeType = ref<'content' | 'comment'>('comment')

    // ── 展开 / 收起 ───────────────────────────────────────────────
    const isCommentFilterExpanded = ref(false)
    const isCommentContentExpanded = ref(false)
    const isFixedCommentExpanded = ref(false)

    const displayedCommentFilterItems = computed(() =>
        isCommentFilterExpanded.value
            ? formData.comment_filter_list
            : formData.comment_filter_list.slice(0, COMMENT_FILTER_DEFAULT_SHOW)
    )
    const displayedCommentContentItems = computed(() =>
        isCommentContentExpanded.value
            ? formData.comment_content_list
            : formData.comment_content_list.slice(0, COMMENT_CONTENT_DEFAULT_SHOW)
    )
    const displayedFixedCommentItems = computed(() =>
        isFixedCommentExpanded.value
            ? formData.fixed_comment_list
            : formData.fixed_comment_list.slice(0, FIXED_COMMENT_DEFAULT_SHOW)
    )

    const toggleCommentFilterExpand = () => {
        isCommentFilterExpanded.value = !isCommentFilterExpanded.value
    }
    const toggleCommentContentExpand = () => {
        isCommentContentExpanded.value = !isCommentContentExpanded.value
    }
    const toggleFixedCommentExpand = () => {
        isFixedCommentExpanded.value = !isFixedCommentExpanded.value
    }

    const handleEditClue = (index: number) => {
        keywordsEditIndex.value = index
        keywordsEditType.value = 'clue'
        showKeywordsEdit.value = true
        keywordsEditRef.value?.setFormData(index > -1 ? formData.industry[index] : '')
    }

    // ── 评论词筛选 ────────────────────────────────────────────────
    const openCommentFilterEdit = () => {
        showCommentFilterEdit.value = true
        commentFilterRef.value?.setFormData(formData.comment_filter_list)
    }
    const handleCommentFilterEdit = (index: number) => {
        keywordsEditIndex.value = index
        keywordsEditType.value = 'comment'
        showKeywordsEdit.value = true
        keywordsEditRef.value?.setFormData(
            index > -1 ? formData.comment_filter_list[index].value : ''
        )
    }
    const handleCommentFilterDelete = (index: number) => {
        formData.comment_filter_list.splice(index, 1)
        commentFilterMap[currentFilterMapKey.value] = [...formData.comment_filter_list]
    }
    const handleCommentFilterConfirm = (data: CommentFilterItem[]) => {
        formData.comment_filter_list = data
        commentFilterMap[currentFilterMapKey.value] = [...data]
    }
    const handleCommentFilterClear = () => {
        uni.showModal({
            title: '提示',
            content: '确定清空评论词筛选吗？',
            success: (res) => {
                if (res.confirm) {
                    formData.comment_filter_list = []
                    commentFilterMap[currentFilterMapKey.value] = []
                }
            }
        })
    }

    // ── 触达内容 ──────────────────────────────────────────────────
    const handleEditCommentContent = (index: number) => {
        keywordsEditIndex.value = index
        keywordsEditType.value = 'comment_content'
        showKeywordsEdit.value = true
        keywordsEditRef.value?.setFormData(index > -1 ? formData.comment_content_list[index] : '')
    }
    const handleCommentContentDelete = (index: number) =>
        formData.comment_content_list.splice(index, 1)
    const handleCommentTypeClear = () => {
        uni.showModal({
            title: '提示',
            content: '确定清空触达方式和话术吗？',
            success: (res) => {
                if (res.confirm) {
                    formData.comment_content_list = []
                    commentContentMap[commentIndex.value] = []
                }
            }
        })
    }

    // ── 固定话术 ──────────────────────────────────────────────────
    const handleFixedCommentEdit = (index: number) => {
        keywordsEditIndex.value = index
        keywordsEditType.value = 'fixed_comment'
        showKeywordsEdit.value = true
        keywordsEditRef.value?.setFormData(index > -1 ? formData.fixed_comment_list[index] : '')
    }
    const handleFixedCommentDelete = (index: number) => formData.fixed_comment_list.splice(index, 1)

    // ── keywords-edit 统一回调 ────────────────────────────────────
    const getKeywordsTitle = computed(() => {
        const titles: Record<KeywordsEditType, string> = {
            clue: '线索词',
            comment: '评论词',
            comment_content: '触达内容',
            fixed_comment: '固定话术'
        }
        return titles[keywordsEditType.value]
    })

    const handleKeywordsEditConfirm = (data: string) => {
        const idx = keywordsEditIndex.value
        switch (keywordsEditType.value) {
            case 'clue':
                idx === -1 ? formData.industry.push(data) : (formData.industry[idx] = data)
                break
            case 'comment':
                idx === -1
                    ? formData.comment_filter_list.push({
                          id: Date.now(),
                          value: data,
                          checked: true
                      })
                    : (formData.comment_filter_list[idx].value = data)
                break
            case 'comment_content':
                idx === -1
                    ? formData.comment_content_list.push(data)
                    : (formData.comment_content_list[idx] = data)
                break
            case 'fixed_comment':
                idx === -1
                    ? formData.fixed_comment_list.push(data)
                    : (formData.fixed_comment_list[idx] = data)
                break
        }
        showKeywordsEdit.value = false
    }

    // ── 时间筛选 ──────────────────────────────────────────────────
    const syncTimeField = (type: 'content' | 'comment') => {
        if (type === 'content') {
            formData.content_time = normalizeCommentTimeValue(formData.content_time)
            formData.content_time_index = getCommentTimeIndex(formData.content_time)
            return
        }
        formData.comment_time = normalizeCommentTimeValue(formData.comment_time)
        formData.comment_time_index = getCommentTimeIndex(formData.comment_time)
    }

    const getTimeLabel = (type: 'content' | 'comment'): string => {
        const value = type === 'content' ? formData.content_time : formData.comment_time
        const normalized = normalizeCommentTimeValue(value)
        return COMMENT_TIME_LIST.find((item) => item.value === normalized)?.label || '不限'
    }
    const handleChangeTime = (type: 'content' | 'comment') => {
        commentTimeType.value = type
        syncTimeField(type)
        showCommentTimePopup.value = true
    }
    const handleCommentTimeConfirm = (res: number[]) => {
        const value = normalizeCommentTimeValue(COMMENT_TIME_LIST[res[0]]?.value)
        const index = getCommentTimeIndex(value)
        if (commentTimeType.value === 'content') {
            formData.content_time_index = index
            formData.content_time = value
        } else {
            formData.comment_time_index = index
            formData.comment_time = value
        }
    }

    // ── 地区 / 年龄 / 性别 ────────────────────────────────────────
    const handleChooseRegionConfirm = (data: any) => {
        formData.comment_region = data.isAll || data.regionList.length === 0 ? [] : data.regionList
        showChooseRegionPopup.value = false
    }
    const handleEditCommentAge = () => {
        chooseAgeRef.value?.setFormData(formData.comment_age)
        showAgePopup.value = true
    }
    const handleAgeConfirm = (data: string) => {
        formData.comment_age = data
        showAgePopup.value = false
    }
    const handleEditCommentGender = () => {
        const list = ['不限', '男', '女']
        uni.showActionSheet({
            itemList: list,
            success: (res: any) => {
                formData.comment_gender = list[res.tapIndex]
            }
        })
    }
    const handleEditCommentAccountFeature = () => {
        uni.showActionSheet({
            itemList: ['全部', '跳过认证号'],
            success: (res: any) => {
                formData.comment_account_feature = res.tapIndex === 0 ? '0' : '1'
            }
        })
    }

    return {
        keywordsEditIndex,
        keywordsEditRef,
        commentFilterRef,
        chooseAgeRef,
        showKeywordsEdit,
        showCommentFilterEdit,
        showAgePopup,
        showChooseRegionPopup,
        showCommentTimePopup,
        getKeywordsTitle,
        commentTimeType,
        displayedCommentFilterItems,
        displayedCommentContentItems,
        displayedFixedCommentItems,
        commentFilterDefaultShowCount: COMMENT_FILTER_DEFAULT_SHOW,
        commentContentDefaultShowCount: COMMENT_CONTENT_DEFAULT_SHOW,
        fixedCommentDefaultShowCount: FIXED_COMMENT_DEFAULT_SHOW,
        isCommentFilterExpanded,
        isCommentContentExpanded,
        isFixedCommentExpanded,
        handleEditClue,
        openCommentFilterEdit,
        handleCommentFilterEdit,
        handleCommentFilterDelete,
        handleCommentFilterConfirm,
        handleCommentFilterClear,
        handleEditCommentContent,
        handleCommentContentDelete,
        handleCommentTypeClear,
        handleFixedCommentEdit,
        handleFixedCommentDelete,
        handleKeywordsEditConfirm,
        getTimeLabel,
        handleChangeTime,
        handleCommentTimeConfirm,
        handleChooseRegionConfirm,
        handleEditCommentAge,
        handleAgeConfirm,
        handleEditCommentGender,
        handleEditCommentAccountFeature,
        toggleCommentFilterExpand,
        toggleCommentContentExpand,
        toggleFixedCommentExpand
    }
}
