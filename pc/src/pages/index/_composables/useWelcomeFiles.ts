import { computed, ref, type Ref } from 'vue'
import { UPLOAD_STATUS, type FileParams } from '@/composables/usePasteImage'
import { uploadImage } from '@/api/app'
import feedback from '@/utils/feedback'
import { urlToFile } from '@/utils/util'

export interface WelcomeRefFile {
    id: number
    name: string
    url: string
    raw: File
    loading?: boolean
    status?: UPLOAD_STATUS | string
    progress?: number
    file_id?: number | string
}

export const isBlobUrl = (url?: string) => !!url && url.startsWith('blob:')
export const isDataUrl = (url?: string) => !!url && url.startsWith('data:')
export const revokeIfBlobUrl = (url?: string) => {
    if (isBlobUrl(url)) URL.revokeObjectURL(url!)
}

/** 可交给后端解析的远程/相对地址 */
export const isRemoteFileUrl = (url?: string) => {
    const u = (url || '').trim()
    if (!u || isBlobUrl(u) || isDataUrl(u)) return false
    return /^(https?:)?\/\//.test(u) || u.startsWith('/')
}

export const isWelcomeFileReady = (f: Pick<WelcomeRefFile, 'loading' | 'status' | 'file_id' | 'url'>) => {
    if (f.loading || f.status === UPLOAD_STATUS.UPLOADING || f.status === 'uploading') return false
    if (f.file_id != null && f.file_id !== '') return true
    return isRemoteFileUrl(f.url)
}

export function useWelcomeFiles(options?: { getMaxRefs?: () => number }) {
    const refImages = ref<WelcomeRefFile[]>([])
    const isDragover = ref(false)

    const resolveMaxRefs = () => {
        const max = options?.getMaxRefs?.()
        return typeof max === 'number' && max > 0 ? max : Number.POSITIVE_INFINITY
    }

    const isRefUploading = computed(() =>
        refImages.value.some(
            (f) =>
                f.loading === true ||
                f.status === UPLOAD_STATUS.UPLOADING ||
                f.status === 'uploading' ||
                // 已有文件但还没拿到远程地址/id，视为未就绪
                (!!f.raw && !isWelcomeFileReady(f))
        )
    )

    const welcomeFileList = computed({
        get() {
            return refImages.value.map((file) => {
                const uploading =
                    file.loading === true ||
                    file.status === UPLOAD_STATUS.UPLOADING ||
                    file.status === 'uploading' ||
                    !isWelcomeFileReady(file)
                return {
                    uid: file.id,
                    name: file.name,
                    url: file.url,
                    file: file.raw,
                    size: file.raw?.size || 0,
                    file_id: file.file_id,
                    status: uploading ? UPLOAD_STATUS.UPLOADING : UPLOAD_STATUS.SUCCESS,
                    loading: uploading,
                    progress: uploading ? Number(file.progress || 0) : 100
                }
            })
        },
        set(list: FileParams[]) {
            syncWelcomeFiles(list)
        }
    })

    function syncWelcomeFiles(list: FileParams[] = []) {
        const nextList = Array.isArray(list) ? list : []
        const maxRefs = resolveMaxRefs()
        const cappedList = nextList.length > maxRefs ? nextList.slice(0, maxRefs) : nextList
        if (cappedList.length < nextList.length) {
            feedback.msgError(`最多添加 ${maxRefs} 张参考图`)
        }
        const ids = new Set(cappedList.map((file) => Number(file.uid)))
        refImages.value = refImages.value.filter((file) => {
            const keep = ids.has(file.id)
            if (!keep && file.url) URL.revokeObjectURL(file.url)
            return keep
        })

        cappedList.forEach((file) => {
            const id = Number(file.uid ?? Date.now() + Math.floor(Math.random() * 1000))
            const exist = refImages.value.find((item) => item.id === id)
            // 上传完成回写时可能只有 url/file_id；没有 raw 时仍要更新已有项
            if (!file.file && !exist) return

            const fileId = file.file_id ?? (file as any).id ?? exist?.file_id
            const url = (file.url || exist?.url || '').trim()
            const uploading =
                file.loading === true ||
                file.status === UPLOAD_STATUS.UPLOADING ||
                file.status === 'uploading'
            const ready = !uploading && (fileId != null && fileId !== '' || isRemoteFileUrl(url))

            const next = {
                name: file.name || file.file?.name || exist?.name || '',
                url,
                raw: (file.file || exist?.raw) as File,
                loading: uploading || !ready,
                status: uploading || !ready ? UPLOAD_STATUS.UPLOADING : UPLOAD_STATUS.SUCCESS,
                progress: Number(file.progress ?? (uploading || !ready ? file.progress || 0 : 100)),
                file_id: fileId
            }
            if (exist) {
                Object.assign(exist, next)
                return
            }
            if (!next.raw) return
            refImages.value.push({ id, ...next } as WelcomeRefFile)
        })
    }

    /** 发送聊天时用的附件快照（只含已上传完成的） */
    function getChatFilePayload() {
        return refImages.value.filter(isWelcomeFileReady).map((f) => ({
            uid: f.id,
            id: f.file_id ?? f.id,
            file_id: f.file_id,
            url: f.url,
            name: f.name,
            type: f.raw?.type || '',
            size: f.raw?.size || 0
        }))
    }

    function addFiles(files: File[]) {
        const maxRefs = resolveMaxRefs()
        const remain = Math.max(0, maxRefs - refImages.value.length)
        if (remain <= 0) {
            feedback.msgError(`最多添加 ${maxRefs} 张参考图`)
            return
        }
        const accept = files.slice(0, remain)
        if (accept.length < files.length) {
            feedback.msgError(`最多添加 ${maxRefs} 张参考图`)
        }
        accept.forEach((f) => {
            refImages.value.push({
                id: Date.now() + Math.floor(Math.random() * 1000),
                name: f.name,
                url: f.type.startsWith('image/') ? URL.createObjectURL(f) : '',
                raw: f,
                loading: true,
                status: UPLOAD_STATUS.UPLOADING,
                progress: 0
            })
        })
    }

    function removeRef(idx: number) {
        const item = refImages.value[idx]
        if (item?.url) URL.revokeObjectURL(item.url)
        refImages.value.splice(idx, 1)
    }

    function onDrop(e: DragEvent) {
        isDragover.value = false
        if (e.dataTransfer?.files) addFiles(Array.from(e.dataTransfer.files))
    }

    function clearRefs() {
        refImages.value.forEach((f) => revokeIfBlobUrl(f.url))
        refImages.value = []
    }

    async function uploadFilesToUrls(files: File[]): Promise<string[]> {
        const urls: string[] = []
        for (const f of files) {
            try {
                const resp: any = await uploadImage({ file: f, name: 'file' })
                const url = resp?.uri || resp?.url || resp?.src || resp?.data?.uri || resp?.data?.url
                if (url) urls.push(url)
            } catch {
                /* 单张失败忽略 */
            }
        }
        return urls
    }

    async function chooseCaseImage(payload: { title: string; pic: string }, inputText: Ref<string>) {
        if (payload.title) inputText.value = payload.title
        if (!payload.pic) return
        try {
            const filename = payload.pic.split('/').pop()?.split('?')[0] || `case_${Date.now()}.png`
            const file = await urlToFile(payload.pic, filename)
            refImages.value.forEach((f) => revokeIfBlobUrl(f.url))
            refImages.value = [
                {
                    id: Date.now() + Math.floor(Math.random() * 1000),
                    name: file.name || filename,
                    url: payload.pic,
                    raw: file,
                    loading: false,
                    status: UPLOAD_STATUS.SUCCESS,
                    progress: 100
                }
            ]
        } catch {
            feedback.msgError('案例参考图加载失败，请稍后重试')
        }
    }

    return {
        refImages,
        isDragover,
        isRefUploading,
        welcomeFileList,
        syncWelcomeFiles,
        getChatFilePayload,
        addFiles,
        removeRef,
        onDrop,
        clearRefs,
        uploadFilesToUrls,
        chooseCaseImage
    }
}
