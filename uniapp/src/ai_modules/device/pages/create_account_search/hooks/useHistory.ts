import { getDySearchTaskHistory } from '@/api/device'
import type { AccountSearchFormData } from './types'

/**
 * 进页面时回填上次填过的线索词 / 私信话术 / 加好友备注。
 * 接口一人一行、每列最多 100 条，返回 { keywords, remarks, miss_scripts } 三个字符串数组。
 * 指定账号模式的名单后端不记历史，所以只回填 keywords，不碰 target_accounts。
 */
export function useHistory(formData: AccountSearchFormData) {
    // 历史为空时保留内置默认话术，否则新用户进来会是空列表
    const applyHistory = (list: unknown, target: string[]) => {
        if (!Array.isArray(list) || !list.length) return
        target.splice(0, target.length, ...list)
    }

    const queryHistory = async () => {
        try {
            const res: any = await getDySearchTaskHistory()
            if (!res) return
            applyHistory(res.keywords, formData.keywords)
            applyHistory(res.remarks, formData.remarks)
            applyHistory(res.miss_scripts, formData.miss_scripts)
        } catch (error) {
            // 历史拉不到不该挡住建任务，静默降级到默认话术
            console.error('[dySearchTask] 历史记录获取失败', error)
        }
    }

    onMounted(queryHistory)

    return { queryHistory }
}
