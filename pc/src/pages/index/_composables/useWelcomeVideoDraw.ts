import { computed, reactive, ref, watch, type Ref } from 'vue'
import { drawGenerateVideo } from '@/api/drawing'
import {
    buildVideoTurnsFromDetail,
    fetchDrawConversationDetail,
    isDrawTaskFailed,
    isDrawTaskPending,
    isDrawTaskSuccess
} from './useDrawConversation'
import { VIDEO_RATIO_OPTIONS } from '../_enums/welcome-toolbar'
import feedback from '@/utils/feedback'
import type { DrawConvIdMap, DrawModelItem } from './useWelcomeDrawShared'
import { resolveDrawErrorMsg } from './useWelcomeDrawShared'
import type { useAppStore } from '@/stores/app'
import type { ImgAttachment } from './useWelcomeImageDraw'

export interface VideoItem {
    id: number
    url: string
    loading: boolean
    progress: number
    status: number
    error: boolean
    msg?: string
}

export interface VideoTask {
    id: number
    prompt: string
    ratio: string
    resolution: string
    type: number
    typeName: string
    model: number
    modelName: string
    count: number
    imageUrl?: string
    videos: VideoItem[]
}

export type VideoChatMsg =
    | { id: number; role: 'user'; text: string; attachments: ImgAttachment[] }
    | { id: number; role: 'assistant'; task: VideoTask }

const DRAW_VIDEO_DEFAULT = {
    model: 'doubao-seedance-1-0-pro-250528',
    model_name: '豆包(SEEDANCE)',
    billing_scene_txt: 'doubao_txt_to_video',
    billing_scene_img: 'doubao_img_to_video'
} as const

const VIDEO_DRAW_MODEL_NAME = 'seedance1.0-pro'

function toVideoResolutionTier(raw?: string): '480p' | '720p' | '1080p' {
    const s = String(raw || '720p').toLowerCase()
    if (s.includes('1080') || s === '1080p') return '1080p'
    if (s.includes('480') || s === '480p') return '480p'
    return '720p'
}

export function useWelcomeVideoDraw(deps: {
    drawConvId: DrawConvIdMap
    appStore: ReturnType<typeof useAppStore>
    pollDrawTask: (taskNo: string, callbacks?: { onProgress?: (p: number) => void }) => Promise<any>
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
        refreshHistoryIfOpen,
        bindDrawConversation,
        ensureEnoughTokens,
        ensureHasTokens,
        displayedMode,
        openPopup
    } = deps

    const videoChat = ref<VideoChatMsg[]>([])
    let _videoChatId = 1

    const videoDrawModels = computed<DrawModelItem[]>(() =>
        (appStore.getDrawModel as DrawModelItem[]).filter(
            (m) => (m.name || '').trim().toLowerCase() === VIDEO_DRAW_MODEL_NAME && String(m.status) === '1'
        )
    )

    const selectedVideoModelId = ref('')
    const currentVideoDrawModel = computed(
        () =>
            videoDrawModels.value.find((m) => m.id === selectedVideoModelId.value) ??
            videoDrawModels.value[0] ??
            null
    )

    watch(
        videoDrawModels,
        (list) => {
            if (!list.length) {
                selectedVideoModelId.value = ''
                return
            }
            if (!list.some((m) => m.id === selectedVideoModelId.value)) {
                selectedVideoModelId.value = list[0].id
            }
        },
        { immediate: true }
    )

    function selectVideoDrawModel(m: DrawModelItem) {
        selectedVideoModelId.value = m.id
        openPopup.value = ''
    }

    const videoState = reactive({
        type: 0,
        ratio: '16:9',
        resolution: '720p',
        count: 1
    })

    function setVideoRatio(k: string) {
        videoState.ratio = k
        const found = VIDEO_RATIO_OPTIONS.find((r) => r.key === k)
        if (found) videoState.resolution = found.resolution
    }

    function changeVideoCount(_delta: number) {
        videoState.count = 1
    }

    function nextVideoChatId() {
        return ++_videoChatId
    }

    function createVideoTaskShell(payload: {
        prompt: string
        ratio: string
        resolution: string
        type: number
        typeName: string
        model: number
        modelName: string
        count: number
        imageUrl?: string
    }): VideoTask {
        const task: VideoTask = {
            id: ++_videoChatId,
            prompt: payload.prompt,
            ratio: payload.ratio,
            resolution: payload.resolution,
            type: payload.type,
            typeName: payload.typeName,
            model: payload.model,
            modelName: payload.modelName,
            count: 1,
            imageUrl: payload.imageUrl,
            videos: []
        }
        for (let i = 0; i < task.count; i++) {
            task.videos.push({
                id: ++_videoChatId,
                url: '',
                loading: true,
                progress: 0,
                status: 0,
                error: false
            })
        }
        return task
    }

    function fillVideosFromAssets(task: VideoTask, t: any) {
        const vids = (t?.assets ?? []).filter((a: any) => a.asset_type === 'video')
        if (!vids.length) {
            markVideoTaskFailed(task, (t?.error_msg || '未返回视频').toString())
            return
        }
        task.videos.splice(0, task.videos.length)
        vids.forEach((a: any) => {
            task.videos.push({
                id: ++_videoChatId,
                url: a.file_full_url || a.file_url,
                loading: false,
                progress: 100,
                status: 1,
                error: false
            })
        })
    }

    function markVideoTaskFailed(task: VideoTask, msg: string, opts?: { silent?: boolean }) {
        const text = (msg || '生成失败').toString()
        task.videos.forEach((v) => {
            v.loading = false
            v.error = true
            v.msg = text
            v.progress = 0
            v.status = 2
        })
        if (text && !opts?.silent) feedback.msgError(text)
    }

    async function runDrawVideo(task: VideoTask) {
        const selected = currentVideoDrawModel.value
        const modelAlias = (selected?.alias || selected?.name || DRAW_VIDEO_DEFAULT.model).trim()
        if (!modelAlias) {
            markVideoTaskFailed(task, '请选择生视频模型')
            return
        }

        const hasRef = !!task.imageUrl
        const billingScene = hasRef
            ? DRAW_VIDEO_DEFAULT.billing_scene_img
            : DRAW_VIDEO_DEFAULT.billing_scene_txt
        if (!ensureEnoughTokens(billingScene, task.count)) {
            markVideoTaskFailed(task, '算力不足，请充值！', { silent: true })
            return
        }

        try {
            const res: any = await drawGenerateVideo({
                conversation_id: drawConvId.video || undefined,
                prompt: task.prompt,
                attachments: hasRef ? [task.imageUrl!] : [],
                model: modelAlias,
                model_name: selected?.name || task.modelName || DRAW_VIDEO_DEFAULT.model_name,
                billing_scene: billingScene,
                params: {
                    prompt: task.prompt,
                    aspect_ratio: task.ratio,
                    resolution: task.resolution || '720p',
                    seconds: '5',
                    ...(hasRef ? { image: task.imageUrl } : {}),
                    metadata: {
                        resolution: toVideoResolutionTier(task.resolution),
                        ratio: hasRef ? 'adaptive' : task.ratio
                    }
                }
            })
            if (res?.conversation_id) bindDrawConversation('video', res.conversation_id)
            refreshHistoryIfOpen()
            let t = res?.task
            if (Number(t?.status) !== 3) {
                t = await pollDrawTask(t?.task_no, {
                    onProgress: (p) => {
                        task.videos.forEach((v) => {
                            if (v.loading) v.progress = Math.min(95, p || v.progress + 5)
                        })
                    }
                })
            }
            fillVideosFromAssets(task, t)
        } catch (e: any) {
            markVideoTaskFailed(task, resolveDrawErrorMsg(e))
        }
    }

    function startVideoGen(task: VideoTask) {
        runDrawVideo(task)
    }

    function restoreVideoConversation(
        detail: NonNullable<Awaited<ReturnType<typeof fetchDrawConversationDetail>>>
    ) {
        drawConvId.video = Number(detail.id) || 0
        displayedMode.value = 'video'
        videoChat.value = []

        const pending: Array<{ task: VideoTask; taskNo: string }> = []
        const turns = buildVideoTurnsFromDetail(detail)

        for (const turn of turns) {
            if (turn.user.text || turn.user.attachments.length) {
                videoChat.value.push({
                    id: ++_videoChatId,
                    role: 'user',
                    text: turn.user.text,
                    attachments: turn.user.attachments.map((u, i) => ({ id: i, url: u, name: '' }))
                })
            }
            if (!turn.assistant) continue

            const shell = createVideoTaskShell({
                prompt: turn.assistant.prompt,
                ratio: turn.assistant.ratio,
                resolution: turn.assistant.resolution,
                type: turn.assistant.type,
                typeName: turn.assistant.typeName,
                model: 0,
                modelName: turn.assistant.modelName,
                count: turn.assistant.count,
                imageUrl: turn.assistant.imageUrl || ''
            })

            if (isDrawTaskSuccess(turn.assistant.taskStatus) && turn.assistant.assets.length) {
                fillVideosFromAssets(shell, { assets: turn.assistant.assets })
            } else if (isDrawTaskFailed(turn.assistant.taskStatus)) {
                markVideoTaskFailed(shell, turn.assistant.errorMsg || '生成失败', { silent: true })
            } else if (isDrawTaskPending(turn.assistant.taskStatus) && turn.assistant.taskNo) {
                pending.push({ task: shell, taskNo: turn.assistant.taskNo })
            } else if (turn.assistant.assets.length) {
                fillVideosFromAssets(shell, { assets: turn.assistant.assets })
            } else {
                markVideoTaskFailed(shell, turn.assistant.errorMsg || '未返回视频', { silent: true })
            }

            videoChat.value.push({ id: ++_videoChatId, role: 'assistant', task: shell })
        }

        for (const item of pending) {
            const reactiveTask =
                videoChat.value.find((m) => m.role === 'assistant' && m.task.id === item.task.id)?.task ??
                item.task
            pollDrawTask(item.taskNo, {
                onProgress: (p) => {
                    reactiveTask.videos.forEach((v) => {
                        if (v.loading) v.progress = Math.min(95, p || v.progress + 5)
                    })
                }
            })
                .then((t) => fillVideosFromAssets(reactiveTask, t))
                .catch((e) => markVideoTaskFailed(reactiveTask, resolveDrawErrorMsg(e)))
        }
    }

    function onVideoRegenerate(oldTask: VideoTask) {
        if (!ensureHasTokens()) return
        const newShell = createVideoTaskShell({
            prompt: oldTask.prompt,
            ratio: oldTask.ratio,
            resolution: oldTask.resolution,
            type: oldTask.type,
            typeName: oldTask.typeName,
            model: oldTask.model,
            modelName: oldTask.modelName,
            count: 1,
            imageUrl: oldTask.imageUrl
        })
        const newId = ++_videoChatId
        videoChat.value.push({ id: newId, role: 'assistant', task: newShell })
        const newMsg = videoChat.value.find((m) => m.id === newId)
        if (newMsg && newMsg.role === 'assistant') {
            startVideoGen(newMsg.task)
        }
    }

    return {
        videoChat,
        videoState,
        videoDrawModels,
        selectedVideoModelId,
        currentVideoDrawModel,
        selectVideoDrawModel,
        nextVideoChatId,
        createVideoTaskShell,
        startVideoGen,
        setVideoRatio,
        changeVideoCount,
        onVideoRegenerate,
        restoreVideoConversation,
        DRAW_VIDEO_DEFAULT
    }
}
