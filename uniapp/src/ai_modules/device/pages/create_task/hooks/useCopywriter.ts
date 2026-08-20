import { ref } from 'vue'
import type { FormData, CopywriterItem } from './types'

/**
 * useCopywriterStep
 * 职责：Step2 文案管理
 *   - 跳转手动编辑页（携带已有数据）
 *   - 删除文案
 *   - onCopywriterConfirm：EventBus 回调，新增 / 编辑回填
 */
export function useCopywriterStep(formData: FormData) {
    /** 当前正在编辑的文案索引，-1 表示新增 */
    const editCopywriterIndex = ref(-1)

    /** 跳转文案编辑页 */
    const handleEditCopywriter = (index: number) => {
        editCopywriterIndex.value = index
        uni.$u.route({
            url: '/ai_modules/device/pages/task_copywriter/task_copywriter',
            params: { copywriter: JSON.stringify(formData.copywriterList[index]) }
        })
    }

    /** 删除文案 */
    const handleDeleteCopywriter = (index: number) => {
        formData.copywriterList.splice(index, 1)
    }

    /**
     * EventBus 回调：手动输入 / AI 生成文案完成后回填
     * 由页面层 on("confirm") 统一调用
     */
    const onCopywriterConfirm = (data: CopywriterItem | CopywriterItem[]) => {
        if (!data) return
        if (Array.isArray(data)) {
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList.splice(editCopywriterIndex.value, 1, ...data)
            } else {
                formData.copywriterList.push(...data)
            }
        } else {
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data
            } else {
                formData.copywriterList.push(data)
            }
            editCopywriterIndex.value = -1
        }
    }

    return {
        editCopywriterIndex,
        handleEditCopywriter,
        handleDeleteCopywriter,
        onCopywriterConfirm
    }
}
