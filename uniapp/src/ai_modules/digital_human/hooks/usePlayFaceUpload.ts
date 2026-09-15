import { chooseFile } from '@/components/file-upload/choose-file'
import { uploadPlayVideoFace } from '@/api/play_video'
import { FACE_IMAGE_ACCEPT, FACE_IMAGE_MAX_MB } from '../enums/play_video'

/**
 * 正脸照 / 背景图上传：走站长人脸素材接口（私有登记，返回 face_file_id），
 * 不进素材库，所以不能用通用 useUpload；选文件复用项目 chooseFile。
 */
export function usePlayFaceUpload() {
    const state = reactive({ fileId: 0, previewUrl: '', uploading: false, progress: 0 })

    const pick = async () => {
        if (state.uploading) return
        let file: any = null
        try {
            const { tempFiles } = await chooseFile({
                type: 'image',
                count: 1,
                sizeType: ['compressed'],
                sourceType: ['album', 'camera']
            })
            file = tempFiles[0]
        } catch (e: any) {
            if (!String(e?.errMsg || '').includes('cancel'))
                uni.$u.toast(e?.errMsg || '选择图片失败')
            return
        }
        if (!file?.tempFilePath) return
        const ext =
            String(file.name || file.tempFilePath)
                .split('.')
                .pop()
                ?.toLowerCase() || ''
        if (ext && !FACE_IMAGE_ACCEPT.includes(ext)) {
            uni.$u.toast(`图片格式必须是 ${FACE_IMAGE_ACCEPT.join('、')}`)
            return
        }
        if (file.size > FACE_IMAGE_MAX_MB * 1024 * 1024) {
            uni.$u.toast(`图片超过 ${FACE_IMAGE_MAX_MB}MB，请压缩后上传`)
            return
        }
        state.uploading = true
        state.progress = 0
        try {
            const data: any = await uploadPlayVideoFace(
                file.tempFilePath,
                (p: number) => (state.progress = p)
            )
            state.fileId = Number(data.face_file_id || 0)
            state.previewUrl = data.preview_url || file.tempFilePath
        } catch (e: any) {
            uni.$u.toast(e?.msg || e?.message || '上传失败')
        } finally {
            state.uploading = false
        }
    }

    return { state, pick }
}
