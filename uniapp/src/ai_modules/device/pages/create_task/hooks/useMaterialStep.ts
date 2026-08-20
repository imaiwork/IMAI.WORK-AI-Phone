import { ref, reactive } from 'vue'
import type { Ref } from 'vue'
import useUpload from '@/hooks/useUpload'
import { UploadCategoryEnum, UploadAlbumTypeEnum } from '@/enums/appEnums'
import { VIDEO_CONFIG, TaskType } from './types'
import type { FormData } from './types'

type Category = UploadAlbumTypeEnum | UploadCategoryEnum

/**
 * useMaterialStep
 * 职责：Step1 素材管理
 *   - 视频：上传 / 素材库 / 历史记录 / 替换 / 删除 / 预览
 *   - 图组：跳转编辑页 / 删除（二次确认）
 *   - 暴露 applyImgGroupResult / removeImgGroupIfEditing 供 EventBus 调用
 */
export function useMaterialStep(formData: FormData, taskType: Ref<TaskType>) {
    // ── UI 状态 ──────────────────────────────────────────────────────────
    const showUploadCategoryPanel = ref(false)
    const showVideoMaterial = ref(false)
    const showHistory = ref(false)
    const showVideoPreview = ref(false)
    const showVideoUploadTip = ref(false)
    const isVideoInitialOpen = ref(true)
    const confirmDialogVisible = ref(false)

    // ── 内部索引 ─────────────────────────────────────────────────────────
    const replaceVideoIndex = ref(-1)
    const deleteImgIndex = ref(-1)
    const editImgIndex = ref(-1)

    // ── 视频预览数据 ──────────────────────────────────────────────────────
    const playItem = reactive({ url: '', pic: '' })

    // ── 上传 Hook ─────────────────────────────────────────────────────────
    const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
        count: VIDEO_CONFIG.limit,
        imageSize: VIDEO_CONFIG.size,
        fileAccept: VIDEO_CONFIG.format as unknown as string[],
        videoAccept: VIDEO_CONFIG.format as unknown as string[],
        fileSize: VIDEO_CONFIG.size,
        onSuccess: (res: any[]) => {
            const data = res.map((item: any) => ({ url: [item.pic, item.url] }))
            if (replaceVideoIndex.value !== -1) {
                formData.materialList[replaceVideoIndex.value] = data[0]
            } else {
                formData.materialList.push(...data)
            }
            replaceVideoIndex.value = -1
        }
    })

    // ── 分类面板选择（对象映射替代 if-else 链） ───────────────────────────
    const CATEGORY_ACTION: Partial<Record<Category, () => void>> = {
        [UploadAlbumTypeEnum.Video]: () => uploadAndProcessFiles('video'),
        [UploadCategoryEnum.Library]: () => (showVideoMaterial.value = true),
        [UploadCategoryEnum.Group]: () => (showVideoMaterial.value = true),
        [UploadCategoryEnum.Creation]: () => (showHistory.value = true)
    }

    const handleSelectCategory = (category: Category) => CATEGORY_ACTION[category]?.()

    // ── 从素材库选择视频 ──────────────────────────────────────────────────
    const handleSelectVideoMaterial = (res: any[]) => {
        const data = res.map((item: any) => ({ url: [item.pic, item.url] }))
        if (replaceVideoIndex.value !== -1) {
            if (data.length) formData.materialList[replaceVideoIndex.value] = data[0]
        } else {
            formData.materialList.push(...data)
        }
        replaceVideoIndex.value = -1
    }

    // ── 从历史记录选择视频 ────────────────────────────────────────────────
    const handleSelectHistory = (res: any[]) => {
        if (replaceVideoIndex.value !== -1) {
            formData.materialList[replaceVideoIndex.value] = { url: [res[0].pic, res[0].url] }
        } else {
            formData.materialList.push(...res.map((item: any) => ({ url: [item.pic, item.url] })))
        }
        replaceVideoIndex.value = -1
    }

    // ── 图组：跳转编辑页 ──────────────────────────────────────────────────
    const handleEditMaterial = (index?: number) => {
        editImgIndex.value = index ?? -1
        uni.$u.route({
            url: '/ai_modules/device/pages/task_img_group/task_img_group',
            params: {
                imgs:
                    editImgIndex.value !== -1
                        ? JSON.stringify(formData.materialList[editImgIndex.value].url)
                        : ''
            }
        })
    }

    /** EventBus 回调：图组编辑完成后写回数据 */
    const applyImgGroupResult = (data: string[]) => {
        if (editImgIndex.value !== -1) {
            formData.materialList[editImgIndex.value].url = data
            editImgIndex.value = -1
        } else {
            formData.materialList.push({ url: data })
        }
    }

    /** EventBus 回调：图组编辑返回空（表示用户删除了该图组） */
    const removeImgGroupIfEditing = () => {
        if (editImgIndex.value !== -1) {
            formData.materialList.splice(editImgIndex.value, 1)
            editImgIndex.value = -1
        }
    }

    // ── 图组：删除（二次确认） ────────────────────────────────────────────
    const handleDeleteMaterial = (index: number) => {
        deleteImgIndex.value = index
        confirmDialogVisible.value = true
    }
    const handleDeleteMaterialConfirm = () => {
        formData.materialList.splice(deleteImgIndex.value, 1)
        confirmDialogVisible.value = false
        deleteImgIndex.value = -1
    }

    // ── 视频：预览 / 删除 / 替换 ──────────────────────────────────────────
    const handlePlayVideo = (item: string[]) => {
        playItem.pic = item[0]
        playItem.url = item[1]
        showVideoPreview.value = true
    }
    const handleDeleteVideo = (index: number) => formData.materialList.splice(index, 1)
    const handleReplaceVideo = (index: number) => {
        replaceVideoIndex.value = index
        showUploadCategoryPanel.value = true
    }

    return {
        showUploadCategoryPanel,
        showVideoMaterial,
        showHistory,
        showUploadProgress,
        showVideoPreview,
        showVideoUploadTip,
        isVideoInitialOpen,
        confirmDialogVisible,
        replaceVideoIndex,
        uploadMaterialList,
        playItem,
        handleSelectCategory,
        handleSelectVideoMaterial,
        handleSelectHistory,
        handleEditMaterial,
        handleDeleteMaterial,
        handleDeleteMaterialConfirm,
        handlePlayVideo,
        handleDeleteVideo,
        handleReplaceVideo,
        uploadAndProcessFiles,
        applyImgGroupResult,
        removeImgGroupIfEditing
    }
}
