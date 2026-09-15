import { checkTaskPublishTime, createDySearchTask } from '@/api/device'
import WechatOA from '@/utils/wechat'
import { MissActionEnum } from './types'
import { buildDySearchPayload } from './payload'
import type { AccountSearchFormData } from './types'

/**
 * 第 4 步创建任务：校验 → 提交 → 成功回调。
 * 设备型任务（同 create_add_wechat）不走发布时间冲突检测。
 */
export function useCreateTask(formData: AccountSearchFormData) {
    const currentFrequency = ref(0)
    const taskErrorMsg = ref('')
    const showCreateTaskSuccessDialog = ref(false)
    const showTaskMsgPop = ref(false)
    const taskMsgPopContent = ref<string[]>([])

    // ── 表单校验：只拦会让任务跑不起来的空项 ──────────────────────
    const validateForm = (): string | null => {
        if (!formData.name.trim()) return '请输入任务名称'
        if (!formData.device_codes.length) return '请选择执行设备'
        if (currentFrequency.value === 5 && !formData.custom_date.length) return '请选择任务日期'
        if (formData.task_exec_type === 0 && (!formData.time_config[0] || !formData.time_config[1]))
            return '请选择任务时间'
        if (formData.task_exec_type === 1) {
            if (formData.minutes < 1) return '执行时间不能小于1分钟'
            if (formData.minutes > 9999) return '执行时间不能超过9999分钟'
        }
        // 接口要求：miss_action=dm 时话术至少 1 条
        if (formData.miss_action === MissActionEnum.DM && !formData.miss_scripts.length)
            return '私信话术至少留一条，或把「没识别到」改成直接跳过'
        if (formData.add_type === 1 && !formData.wechat_id) return '还没选加微微信，回第 3 步选一个'
        if (formData.add_type === 1 && formData.add_remark_enable === 1 && !formData.remarks.length)
            return '加好友备注至少留一条，或关掉备注开关'
        return null
    }

    const executeCreateTask = async () => {
        uni.showLoading({ title: '创建中...', mask: true })
        try {
            await createDySearchTask(buildDySearchPayload(formData))
            showCreateTaskSuccessDialog.value = true
            WechatOA.notify()
        } catch (error: any) {
            if (typeof error === 'string' && error.includes('24小时自动执行任务')) {
                uni.showModal({
                    title: '提示',
                    content:
                        '您已开启24小时自动执行任务，无法创建手动任务，如需手动创建任务，请先关闭24小时托管。',
                    success: (res) => {
                        if (res.confirm)
                            uni.$u.route({ url: '/ai_modules/device/pages/index/index' })
                    }
                })
            } else {
                taskErrorMsg.value = error
                uni.showToast({ title: error, icon: 'none', duration: 3000 })
            }
        } finally {
            uni.hideLoading()
        }
    }

    /**
     * 即时执行会立刻占用设备，先问后端这几台设备当前时段有没有排别的任务；
     * 有冲突就把 task_ids 带回去，用户确认后随请求一起提交，后端据此让路。
     */
    const handleCreateTask = async () => {
        const errMsg = validateForm()
        if (errMsg) {
            uni.$u.toast(errMsg)
            return
        }

        if (formData.task_exec_type !== 1) {
            await executeCreateTask()
            return
        }

        uni.showLoading({ title: '检测冲突中...', mask: true })
        try {
            const { messages, task_ids } = await checkTaskPublishTime({
                device_codes: formData.device_codes,
                minutes: formData.minutes
            })
            uni.hideLoading()
            if (messages?.length) {
                taskMsgPopContent.value = messages
                formData.task_ids = task_ids
                showTaskMsgPop.value = true
                return
            }
            await executeCreateTask()
        } catch (error: any) {
            uni.hideLoading()
            taskErrorMsg.value = error
            uni.$u.toast(error)
        }
    }

    const handleTaskMsgPopConfirm = () => {
        showTaskMsgPop.value = false
        executeCreateTask()
    }

    const handleCreateTaskSuccess = () => {
        showCreateTaskSuccessDialog.value = false
        uni.navigateBack()
    }

    return {
        currentFrequency,
        taskErrorMsg,
        showCreateTaskSuccessDialog,
        showTaskMsgPop,
        taskMsgPopContent,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess
    }
}
