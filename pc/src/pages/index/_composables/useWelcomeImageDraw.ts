import { computed, reactive, ref, watch, type Ref } from 'vue'
import { drawGenerateImage } from '@/api/drawing'
import {
    buildImageTurnsFromDetail,
    fetchDrawConversationDetail,
    isDrawTaskFailed,
    isDrawTaskPending,
    isDrawTaskSuccess
} from './useDrawConversation'
import { RATIO_PRESETS, snapDimToMultipleOf16 } from '../_enums/welcome-toolbar'
import feedback from '@/utils/feedback'
import type { DrawConvIdMap, DrawModelItem } from './useWelcomeDrawShared'
import { resolveDrawErrorMsg } from './useWelcomeDrawShared'
import type { useAppStore } from '@/stores/app'

export interface ImgAttachment {
    id: number
    url: string
    name: string
}

export interface ImageItem {
    id: number
    done: boolean
    bg: string
    title: string
    dataUrl: string
    error?: boolean
    errorMsg?: string
}

export interface ImageTask {
    id: number
    prompt: string
    ratio: string
    resolution: string
    width: number
    height: number
    count: number
    optimized: boolean
    images: ImageItem[]
}

export type ImageChatMsg =
    | { id: number; role: 'user'; text: string; attachments: ImgAttachment[] }
    | { id: number; role: 'assistant'; task: ImageTask }

const SEEDREAM40_MODEL_NAME = 'seedream4.0'

function isSeedream40Model(m: DrawModelItem | null | undefined): boolean {
    return (m?.name || '').trim().toLowerCase() === SEEDREAM40_MODEL_NAME
}

function resolveImageBillingScene(alias: string, hasRef: boolean): string {
    const a = (alias || '').toLowerCase()
    if (a.includes('seedream')) {
        return hasRef ? 'volc_img_to_img_v2' : 'volc_txt_to_img_v2'
    }
    return 'volc_txt_to_img'
}

export function useWelcomeImageDraw(deps: {
    drawConvId: DrawConvIdMap
    appStore: ReturnType<typeof useAppStore>
    pollDrawTask: (taskNo: string, callbacks?: { onProgress?: (p: number) => void }) => Promise<any>
    uploadFilesToUrls: (files: File[]) => Promise<string[]>
    refreshHistoryIfOpen: () => void
    bindDrawConversation: (mode: 'image' | 'video' | 'ppt', id: number | string) => void
    ensureEnoughTokens: (scene: string, count: number) => boolean
    ensureHasTokens: () => boolean
    displayedMode: Ref<string>
    openPopup: Ref<string>
}) {
    const {
        drawConvId,
        appStore,
        pollDrawTask,
        uploadFilesToUrls,
        refreshHistoryIfOpen,
        bindDrawConversation,
        ensureEnoughTokens,
        ensureHasTokens,
        displayedMode,
        openPopup
    } = deps

    const imageChat = ref<ImageChatMsg[]>([])
    let _imgChatId = 1

    const imageDrawModels = computed<DrawModelItem[]>(() =>
        (appStore.getDrawModel as DrawModelItem[]).filter(
            (m) => m.media_type === 'image' && String(m.status) === '1'
        )
    )

    const selectedImageModelId = ref('')
    const currentImageDrawModel = computed(
        () =>
            imageDrawModels.value.find((m) => m.id === selectedImageModelId.value) ??
            imageDrawModels.value[0] ??
            null
    )

    watch(
        imageDrawModels,
        (list) => {
            if (!list.length) {
                selectedImageModelId.value = ''
                return
            }
            if (!list.some((m) => m.id === selectedImageModelId.value)) {
                selectedImageModelId.value = list[0].id
            }
        },
        { immediate: true }
    )

    const imageMaxCount = computed(() => (isSeedream40Model(currentImageDrawModel.value) ? 1 : 9))

    function selectImageDrawModel(m: DrawModelItem) {
        selectedImageModelId.value = m.id
        if (isSeedream40Model(m)) {
            imageState.count = 1
        }
        openPopup.value = ''
    }

    const imageState = reactive({
        ratio: '9:16',
        resolution: '高清 2K',
        width: 1440,
        height: 2560,
        count: 1
    })
    const imageHasOutput = ref(false)

    watch(
        currentImageDrawModel,
        (m) => {
            if (isSeedream40Model(m) && imageState.count > 1) {
                imageState.count = 1
            }
        },
        { immediate: true }
    )

    function nextImageChatId() {
        return ++_imgChatId
    }

    function createImageTaskShell(payload: {
        prompt: string
        ratio: string
        resolution: string
        width: number
        height: number
        count: number
        optimized: boolean
    }): ImageTask {
        const width = snapDimToMultipleOf16(payload.width || 1024)
        const height = snapDimToMultipleOf16(payload.height || 1024)
        // 自定义输入未失焦时也强制对齐，并回写到规格面板
        if (imageState.width !== width) imageState.width = width
        if (imageState.height !== height) imageState.height = height

        const task: ImageTask = {
            id: ++_imgChatId,
            prompt: payload.prompt,
            ratio: payload.ratio,
            resolution: payload.resolution,
            width,
            height,
            count: Math.max(1, Math.min(imageMaxCount.value, payload.count)),
            optimized: payload.optimized,
            images: []
        }
        for (let i = 0; i < task.count; i++) {
            task.images.push({
                id: ++_imgChatId,
                done: false,
                bg: '',
                title: '',
                dataUrl: '',
                error: false,
                errorMsg: ''
            })
        }
        return task
    }

    function fillImagesFromAssets(task: ImageTask, t: any) {
        const assets = (t?.assets ?? []).filter((a: any) => a.asset_type === 'image')
        if (!assets.length) {
            const msg = (t?.error_msg || '未返回图片').toString()
            markImageTaskFailed(task, msg)
            return
        }
        task.images.splice(0, task.images.length)
        assets.forEach((a: any, i: number) => {
            const url = a.file_full_url || a.file_url
            task.images.push({
                id: ++_imgChatId,
                done: true,
                bg: `url("${url}") center/cover no-repeat`,
                title: `${task.prompt.slice(0, 12)}${task.prompt.length > 12 ? '…' : ''} #${i + 1}`,
                dataUrl: url,
                error: false,
                errorMsg: ''
            })
        })
    }

    function markImageTaskFailed(task: ImageTask, msg: string, opts?: { silent?: boolean }) {
        const text = (msg || '生成失败').toString()
        task.images.forEach((img) => {
            img.done = true
            img.error = true
            img.errorMsg = text
            img.title = text
            img.dataUrl = ''
            img.bg = ''
        })
        if (text && !opts?.silent) feedback.msgError(text)
    }

    async function runDrawImage(task: ImageTask, prompt: string, refFiles: File[]) {
        const selected = currentImageDrawModel.value
        const modelAlias = (selected?.alias || selected?.name || '').trim()
        if (!modelAlias) {
            markImageTaskFailed(task, '请选择生图模型')
            return
        }

        const hasRef = refFiles.length > 0
        const billingScene = resolveImageBillingScene(modelAlias, hasRef)
        if (!ensureEnoughTokens(billingScene, task.count)) {
            const text = '算力不足，请充值！'
            task.images.forEach((img) => {
                img.done = true
                img.error = true
                img.errorMsg = text
                img.title = text
                img.dataUrl = ''
                img.bg = ''
            })
            return
        }

        try {
            const attachments = hasRef ? await uploadFilesToUrls(refFiles) : []
            const res: any = await drawGenerateImage({
                conversation_id: drawConvId.image || undefined,
                prompt,
                attachments,
                model: modelAlias,
                model_name: selected?.name || modelAlias,
                billing_scene: billingScene,
                params: {
                    prompt,
                    ratio: task.ratio,
                    resolution: task.resolution,
                    width: task.width,
                    height: task.height,
                    n: task.count,
                    ...(attachments.length ? { image: attachments } : {})
                }
            })
            if (res?.conversation_id) bindDrawConversation('image', res.conversation_id)
            refreshHistoryIfOpen()
            let t = res?.task
            if (Number(t?.status) !== 3) {
                t = await pollDrawTask(t?.task_no)
            }
            fillImagesFromAssets(task, t)
        } catch (e: any) {
            markImageTaskFailed(task, resolveDrawErrorMsg(e))
        }
    }

    async function callGptImage(task: ImageTask, prompt: string, refFiles: File[]) {
        await runDrawImage(task, prompt, refFiles)
    }

    function setRatio(k: string) {
        imageState.ratio = k
        const p = RATIO_PRESETS[k]
        if (p) {
            imageState.width = p[0]
            imageState.height = p[1]
        }
    }

    function changeCount(delta: number) {
        imageState.count = Math.max(1, Math.min(imageMaxCount.value, imageState.count + delta))
    }

    function restoreImageConversation(
        detail: NonNullable<Awaited<ReturnType<typeof fetchDrawConversationDetail>>>
    ) {
        drawConvId.image = Number(detail.id) || 0
        displayedMode.value = 'image'
        imageHasOutput.value = true
        imageChat.value = []

        const pending: Array<{ task: ImageTask; taskNo: string }> = []
        const turns = buildImageTurnsFromDetail(detail)

        for (const turn of turns) {
            if (turn.user.text || turn.user.attachments.length) {
                imageChat.value.push({
                    id: ++_imgChatId,
                    role: 'user',
                    text: turn.user.text,
                    attachments: turn.user.attachments.map((u, i) => ({ id: i, url: u, name: '' }))
                })
            }
            if (!turn.assistant) continue

            const shell = createImageTaskShell({
                prompt: turn.assistant.prompt,
                ratio: turn.assistant.ratio,
                resolution: turn.assistant.resolution,
                width: turn.assistant.width,
                height: turn.assistant.height,
                count: turn.assistant.count,
                optimized: false
            })

            if (isDrawTaskSuccess(turn.assistant.taskStatus) && turn.assistant.assets.length) {
                fillImagesFromAssets(shell, {
                    assets: turn.assistant.assets,
                    error_msg: turn.assistant.errorMsg
                })
            } else if (isDrawTaskFailed(turn.assistant.taskStatus)) {
                markImageTaskFailed(shell, turn.assistant.errorMsg || '生成失败', { silent: true })
            } else if (isDrawTaskPending(turn.assistant.taskStatus) && turn.assistant.taskNo) {
                pending.push({ task: shell, taskNo: turn.assistant.taskNo })
            } else if (turn.assistant.assets.length) {
                fillImagesFromAssets(shell, { assets: turn.assistant.assets })
            } else {
                markImageTaskFailed(shell, turn.assistant.errorMsg || '未返回图片', { silent: true })
            }

            imageChat.value.push({ id: ++_imgChatId, role: 'assistant', task: shell })
        }

        for (const item of pending) {
            const reactiveTask =
                imageChat.value.find((m) => m.role === 'assistant' && m.task.id === item.task.id)?.task ??
                item.task
            pollDrawTask(item.taskNo)
                .then((t) => fillImagesFromAssets(reactiveTask, t))
                .catch((e) => markImageTaskFailed(reactiveTask, resolveDrawErrorMsg(e)))
        }
    }

    function onImageRegenerate(oldTask: ImageTask) {
        if (!ensureHasTokens()) return
        const newShell = createImageTaskShell({
            prompt: oldTask.prompt,
            ratio: oldTask.ratio,
            resolution: oldTask.resolution,
            width: oldTask.width,
            height: oldTask.height,
            count: oldTask.count,
            optimized: oldTask.optimized
        })
        const newId = ++_imgChatId
        imageChat.value.push({
            id: newId,
            role: 'assistant',
            task: newShell
        })
        const newMsg = imageChat.value.find((m) => m.id === newId)
        if (newMsg && newMsg.role === 'assistant') {
            callGptImage(newMsg.task, oldTask.prompt, [])
        }
    }

    return {
        imageChat,
        imageState,
        imageHasOutput,
        imageDrawModels,
        selectedImageModelId,
        currentImageDrawModel,
        imageMaxCount,
        selectImageDrawModel,
        nextImageChatId,
        createImageTaskShell,
        callGptImage,
        setRatio,
        changeCount,
        onImageRegenerate,
        restoreImageConversation
    }
}
