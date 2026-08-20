import { computed, reactive, ref, watch, type Ref } from 'vue'
import { drawPptChapters, drawPptSubmitSlides } from '@/api/drawing'
import {
    buildPptTurnsFromDetail,
    fetchDrawConversationDetail,
    isDrawTaskPending
} from './useDrawConversation'
import { resolveDrawErrorMsg, type DrawConvIdMap, type DrawModelItem } from './useWelcomeDrawShared'
import feedback from '@/utils/feedback'
import { exportSlidesToPptx } from '@/utils/ppt-export'
import type { CozeField } from '@/utils/coze'
import type { useAppStore } from '@/stores/app'

export interface PptSlide {
    id: number
    page: number
    title: string
    content: string
    imageUrl?: string
    imageLoading?: boolean
    imageError?: string
}

type PptAssistantState = 'thinking' | 'followup' | 'generating' | 'done'

export interface PptChatMsg {
    id: number
    role: 'user' | 'assistant'
    text?: string
    state?: PptAssistantState
    topic?: string
    pageCount?: number
    slides?: PptSlide[]
    summary?: Record<string, string>
    conversationId?: number
    turnKey?: string
    fuFields?: CozeField[]
    fuDescription?: string
    fuPptType?: string
    pptScene?: string
}

const PPT_DRAW_MODEL_NAME = 'image-2'
const PPT_DRAW_MODEL_ALIAS = 'gpt-image-2'

export function useWelcomePptDraw(deps: {
    drawConvId: DrawConvIdMap
    appStore: ReturnType<typeof useAppStore>
    userTokens: Ref<number>
    pollDrawTask: (taskNo: string) => Promise<any>
    refreshHistoryIfOpen: () => void
    bindDrawConversation: (mode: 'image' | 'video' | 'ppt', id: number | string) => void
    ensureHasTokens: () => boolean
    displayedMode: Ref<string>
    openPopup: Ref<string>
    lastUserText: Ref<string>
}) {
    const {
        drawConvId,
        appStore,
        userTokens,
        pollDrawTask,
        refreshHistoryIfOpen,
        bindDrawConversation,
        ensureHasTokens,
        displayedMode,
        openPopup,
        lastUserText
    } = deps

    const pptDrawModels = computed<DrawModelItem[]>(() =>
        (appStore.getDrawModel as DrawModelItem[]).filter((m) => {
            if (String(m.status) !== '1') return false
            const name = (m.name || '').trim().toLowerCase()
            const alias = (m.alias || '').trim().toLowerCase()
            return name === PPT_DRAW_MODEL_NAME || alias === PPT_DRAW_MODEL_ALIAS
        })
    )

    const selectedPptModelId = ref('')
    const currentPptDrawModel = computed(
        () =>
            pptDrawModels.value.find((m) => m.id === selectedPptModelId.value) ??
            pptDrawModels.value[0] ??
            null
    )

    watch(
        pptDrawModels,
        (list) => {
            if (!list.length) {
                selectedPptModelId.value = ''
                return
            }
            if (!list.some((m) => m.id === selectedPptModelId.value)) {
                selectedPptModelId.value = list[0].id
            }
        },
        { immediate: true }
    )

    function selectPptDrawModel(m: DrawModelItem) {
        selectedPptModelId.value = m.id
        openPopup.value = ''
    }

    const customPagesInput = ref<number | string>('')
    const isCustomPagesValid = computed(() => {
        const n = Number(customPagesInput.value)
        return Number.isInteger(n) && n >= 1 && n <= 99
    })

    const pptState = reactive({ pages: '15-25页', scene: '通用' })
    const pptOpen = ref(false)

    function applyCustomPages() {
        if (!isCustomPagesValid.value) return
        const n = Number(customPagesInput.value)
        pptState.pages = `${n}页`
        openPopup.value = ''
        customPagesInput.value = ''
    }

    const pptChat = ref<PptChatMsg[]>([])
    let _pptId = 1
    const activePptMsgId = ref<number | null>(null)
    const activePptMsg = computed(
        () =>
            pptChat.value.find((m) => m.id === activePptMsgId.value && m.role === 'assistant') as
                | (PptChatMsg & { role: 'assistant' })
                | undefined
    )

    function nextPptId() {
        return ++_pptId
    }

    function resolvePptDrawModel(): { alias: string; name: string; unitPrice: number } | null {
        const selected = currentPptDrawModel.value
        const alias = (selected?.alias || selected?.name || '').trim()
        if (!alias) return null
        const name = (selected?.name || '').trim().toLowerCase()
        const aliasLower = alias.toLowerCase()
        if (name !== PPT_DRAW_MODEL_NAME && aliasLower !== PPT_DRAW_MODEL_ALIAS) {
            return null
        }
        return {
            alias:
                aliasLower === PPT_DRAW_MODEL_ALIAS || name === PPT_DRAW_MODEL_NAME
                    ? PPT_DRAW_MODEL_ALIAS
                    : alias,
            name: selected?.name || PPT_DRAW_MODEL_NAME,
            unitPrice: Number(selected?.unit_price) || 0
        }
    }

    function ensurePptTokens(pageCount: number): boolean {
        if (!ensureHasTokens()) return false
        const model = resolvePptDrawModel()
        const unit = model?.unitPrice || 0
        if (unit > 0 && userTokens.value < unit * Math.max(1, pageCount)) {
            feedback.msgPowerInsufficient()
            return false
        }
        return true
    }

    function extractSlideImageUrl(task: any): string {
        const assets = Array.isArray(task?.assets) ? task.assets : []
        const img = assets.find((a: any) => String(a?.asset_type || '') === 'image') || assets[0]
        return String(img?.file_full_url || img?.file_url || '').trim()
    }

    async function fillSlideFromDrawTask(slide: PptSlide, taskNo: string) {
        slide.imageLoading = true
        slide.imageError = undefined
        try {
            const t: any = await pollDrawTask(taskNo)
            const url = extractSlideImageUrl(t)
            if (!url) {
                throw new Error(t?.error_msg || '未返回图片')
            }
            slide.imageUrl = url
        } catch (e: any) {
            slide.imageError = resolveDrawErrorMsg(e, '生成失败')
        } finally {
            slide.imageLoading = false
        }
    }

    async function generatePptSlidesOneByOne(
        msg: PptChatMsg,
        style: string,
        audience: string,
        modelAlias: string,
        totalPages: number
    ) {
        if (!msg.slides?.length || !msg.topic) {
            msg.state = 'done'
            return
        }

        try {
            for (let i = 0; i < msg.slides.length; i++) {
                const slide = msg.slides[i]
                if (!slide) continue

                if (!ensurePptTokens(1)) {
                    slide.imageError = '算力不足，请充值！'
                    for (let j = i + 1; j < msg.slides.length; j++) {
                        const rest = msg.slides[j]
                        if (rest && !rest.imageUrl) {
                            rest.imageError = '算力不足，已跳过'
                        }
                    }
                    break
                }

                slide.imageLoading = true
                slide.imageError = undefined
                slide.imageUrl = ''

                try {
                    const res: any = await drawPptSubmitSlides({
                        model: modelAlias,
                        topic: msg.topic,
                        slides: [
                            {
                                page: slide.page,
                                title: slide.title,
                                content: slide.content
                            }
                        ],
                        total_pages: totalPages,
                        is_cover: i === 0,
                        ppt_type: msg.fuPptType,
                        audience,
                        style,
                        conversation_id: msg.conversationId || drawConvId.ppt || undefined,
                        turn_key: msg.turnKey,
                        write_user: i === 0
                    })
                    if (res?.conversation_id) {
                        msg.conversationId = Number(res.conversation_id) || msg.conversationId
                        bindDrawConversation('ppt', res.conversation_id)
                        refreshHistoryIfOpen()
                    }

                    const hit = Array.isArray(res?.tasks) ? res.tasks[0] : null
                    if (!hit?.task_no) {
                        slide.imageLoading = false
                        slide.imageError = hit?.error || '提交失败'
                        continue
                    }
                    if (Number(hit.status) === 4 || Number(hit.status) === 5) {
                        slide.imageLoading = false
                        slide.imageError = hit.error || '生成失败'
                        continue
                    }
                    await fillSlideFromDrawTask(slide, hit.task_no)
                } catch (e: any) {
                    slide.imageLoading = false
                    slide.imageError = resolveDrawErrorMsg(e)
                }
            }
        } finally {
            msg.state = 'done'
        }
    }

    async function streamPptSlides(msg: PptChatMsg) {
        if (!msg.topic || !msg.pageCount) return
        const model = resolvePptDrawModel()
        if (!model) {
            feedback.msgError('PPT 生成仅支持 image-2 模型，请先在后台启用')
            msg.state = 'done'
            return
        }
        if (!ensurePptTokens(1)) {
            msg.state = 'done'
            return
        }

        msg.turnKey = `ppt-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`
        if (!msg.conversationId && drawConvId.ppt) {
            msg.conversationId = drawConvId.ppt
        }

        msg.slides = []
        msg.state = 'generating'
        activePptMsgId.value = msg.id
        pptOpen.value = true

        const style = msg.summary?.['PPT风格偏好'] ?? msg.summary?.['风格偏好'] ?? ''
        const audience = msg.summary?.['汇报对象'] ?? msg.summary?.['目标观众'] ?? ''

        let chapters: Array<{ page: number; title: string; content: string }>
        try {
            const res: any = await drawPptChapters({
                topic: msg.topic,
                page_count: msg.pageCount,
                ppt_scene: msg.pptScene,
                summary: msg.summary
            })
            chapters = Array.isArray(res?.pages) ? res.pages : []
            if (!chapters.length) {
                throw new Error('章节列表为空')
            }
            if (msg.pageCount && chapters.length > msg.pageCount) {
                chapters = chapters.slice(0, msg.pageCount)
            }
        } catch (e: any) {
            feedback.msgError(`章节生成失败:${resolveDrawErrorMsg(e)}`)
            msg.state = 'done'
            return
        }

        const total = chapters.length
        chapters.forEach((chapter, i) => {
            msg.slides!.push({
                id: ++_pptId,
                page: Number(chapter.page) || i + 1,
                title: String(chapter.title || ''),
                content: String(chapter.content || ''),
                imageUrl: '',
                imageLoading: false
            })
        })

        await generatePptSlidesOneByOne(msg, style, audience, model.alias, total)
    }

    async function regenerateSlideImage(msgId: number, slideId: number) {
        const msg = pptChat.value.find((m) => m.id === msgId)
        if (!msg || msg.role !== 'assistant' || !msg.slides) return
        const idx = msg.slides.findIndex((s) => s.id === slideId)
        if (idx < 0) return
        const slide = msg.slides[idx]
        const style = msg.summary?.['PPT风格偏好'] ?? msg.summary?.['风格偏好'] ?? ''
        const audience = msg.summary?.['汇报对象'] ?? msg.summary?.['目标观众'] ?? ''
        const model = resolvePptDrawModel()
        if (!model) {
            feedback.msgError('PPT 生成仅支持 image-2 模型，请先在后台启用')
            return
        }
        if (!ensurePptTokens(1)) return

        slide.imageUrl = ''
        slide.imageError = undefined
        slide.imageLoading = true
        try {
            const res: any = await drawPptSubmitSlides({
                model: model.alias,
                topic: msg.topic || '',
                slides: [
                    {
                        page: slide.page,
                        title: slide.title,
                        content: slide.content
                    }
                ],
                total_pages: msg.slides.length,
                is_cover: idx === 0,
                ppt_type: msg.fuPptType,
                audience,
                style,
                conversation_id: msg.conversationId || drawConvId.ppt || undefined,
                turn_key: msg.turnKey,
                write_user: false
            })
            if (res?.conversation_id) {
                msg.conversationId = Number(res.conversation_id) || msg.conversationId
                bindDrawConversation('ppt', res.conversation_id)
                refreshHistoryIfOpen()
            }
            const taskNo = res?.tasks?.[0]?.task_no
            if (!taskNo) {
                throw new Error(res?.tasks?.[0]?.error || '提交失败')
            }
            await fillSlideFromDrawTask(slide, taskNo)
        } catch (e: any) {
            slide.imageLoading = false
            slide.imageError = resolveDrawErrorMsg(e)
        }
    }

    function restorePptConversation(
        detail: NonNullable<Awaited<ReturnType<typeof fetchDrawConversationDetail>>>
    ) {
        drawConvId.ppt = Number(detail.id) || 0
        displayedMode.value = 'ppt'
        pptChat.value = []
        pptOpen.value = false
        activePptMsgId.value = null

        const turns = buildPptTurnsFromDetail(detail)
        const pendingAll: Array<{ slide: PptSlide; taskNo: string; assistantId: number }> = []

        for (const turn of turns) {
            pptChat.value.push({
                id: ++_pptId,
                role: 'user',
                text: turn.topic
            })

            const assistantId = ++_pptId
            const slides: PptSlide[] = turn.slides.map((s) => ({
                id: ++_pptId,
                page: s.page,
                title: s.title,
                content: s.content,
                imageUrl: s.imageUrl || undefined,
                imageLoading: isDrawTaskPending(s.taskStatus),
                imageError: s.errorMsg || undefined
            }))

            pptChat.value.push({
                id: assistantId,
                role: 'assistant',
                state: slides.some((s) => s.imageLoading) ? 'generating' : 'done',
                topic: turn.topic,
                pageCount: turn.pageCount,
                conversationId: Number(detail.id) || undefined,
                turnKey: turn.turnKey || undefined,
                slides
            })

            turn.slides.forEach((s, i) => {
                const slide = slides[i]
                if (slide && s.taskNo && isDrawTaskPending(s.taskStatus)) {
                    pendingAll.push({ slide, taskNo: s.taskNo, assistantId })
                }
            })
        }

        const lastAssistant = [...pptChat.value].reverse().find((m) => m.role === 'assistant')
        if (lastAssistant) {
            activePptMsgId.value = lastAssistant.id
            pptOpen.value = !!(lastAssistant.slides && lastAssistant.slides.length)
            if (lastAssistant.topic) lastUserText.value = lastAssistant.topic
        }

        Promise.all(
            pendingAll.map(async ({ slide, taskNo, assistantId }) => {
                await fillSlideFromDrawTask(slide, taskNo)
                const assistant = pptChat.value.find((m) => m.id === assistantId)
                if (assistant?.role === 'assistant' && assistant.state === 'generating') {
                    const stillLoading = assistant.slides?.some((s) => s.imageLoading)
                    if (!stillLoading) assistant.state = 'done'
                }
            })
        )
    }

    function onFollowupConfirm(
        msgId: number,
        payload: {
            answers: Record<string, any>
            summary: Record<string, string>
            pageCount?: number
        }
    ) {
        const msg = pptChat.value.find((m) => m.id === msgId)
        if (!msg || msg.role !== 'assistant') return
        msg.summary = payload.summary
        if (payload.pageCount && payload.pageCount > 0) {
            msg.pageCount = payload.pageCount
        }
        const firstKey = Object.keys(payload.summary)[0]
        const firstVal = firstKey ? payload.summary[firstKey] : ''
        feedback.msgSuccess(
            `已采纳「${(firstVal || msg.topic || '').slice(0, 18)}…」，开始生成 ${msg.pageCount} 页 PPT`
        )
        streamPptSlides(msg)
    }

    function onFollowupCancel(msgId: number) {
        const idx = pptChat.value.findIndex((m) => m.id === msgId)
        if (idx < 0) return
        pptChat.value.splice(Math.max(0, idx - 1), 2)
    }

    function onViewPpt(msgId: number) {
        activePptMsgId.value = msgId
        pptOpen.value = true
        const msg = pptChat.value.find((m) => m.id === msgId)
        if (msg?.role === 'assistant' && msg.conversationId) {
            bindDrawConversation('ppt', msg.conversationId)
        }
    }

    function onRegeneratePpt(msgId: number) {
        const msg = pptChat.value.find((m) => m.id === msgId)
        if (!msg || msg.role !== 'assistant') return
        streamPptSlides(msg)
    }

    async function onExportPptMsg(msg: PptChatMsg) {
        if (msg.role !== 'assistant') return
        const list = Array.isArray(msg.slides) ? msg.slides : []
        const topic = msg.topic || 'PPT'
        try {
            const n = await exportSlidesToPptx(list, topic)
            feedback.msgSuccess(`已导出 ${n} 页 PPT`)
        } catch (e: any) {
            feedback.msgError(e?.message || '导出失败，请稍后重试')
        }
    }

    function onClosePpt() {
        pptOpen.value = false
    }

    async function onPptExport(slides: any) {
        const list = Array.isArray(slides) ? slides : []
        const topic = activePptMsg.value?.topic || 'PPT'
        try {
            const n = await exportSlidesToPptx(list, topic)
            feedback.msgSuccess(`已导出 ${n} 页 PPT`)
        } catch (e: any) {
            feedback.msgError(e?.message || '导出失败，请稍后重试')
        }
    }

    return {
        pptChat,
        pptState,
        pptOpen,
        activePptMsgId,
        activePptMsg,
        pptDrawModels,
        selectedPptModelId,
        currentPptDrawModel,
        selectPptDrawModel,
        customPagesInput,
        isCustomPagesValid,
        applyCustomPages,
        nextPptId,
        resolvePptDrawModel,
        streamPptSlides,
        regenerateSlideImage,
        restorePptConversation,
        onFollowupConfirm,
        onFollowupCancel,
        onViewPpt,
        onRegeneratePpt,
        onExportPptMsg,
        onClosePpt,
        onPptExport
    }
}
