import WechatOA from '@/utils/wechat'
import { createPrivateChatTask, checkTaskPublishTime } from '@/api/device'
import type { PrivateChatFormData, TakeoverType, StrategyType, CommentRule } from './types'

interface CreateTaskDeps {
    formData: PrivateChatFormData
    takeoverType: Ref<TakeoverType>
    strategyType: Ref<StrategyType>
    commentRule: Ref<CommentRule>
    selectedCommentAgent: Ref<{ id: string; name: string } | null>
    selectedMessageAgent: Ref<{ id: string; name: string } | null>
    pmReplyLimit: Ref<number>
}

/**
 * 创建任务：表单校验 → 冲突检测 → 提交 → 成功/失败处理
 */
export function useCreateTask(deps: CreateTaskDeps) {
    const {
        formData,
        takeoverType,
        strategyType,
        commentRule,
        selectedCommentAgent,
        selectedMessageAgent,
        pmReplyLimit
    } = deps
    const currentFrequency = ref(0)

    const taskErrorMsg = ref('')
    const showCreateTaskSuccessDialog = ref(false)
    const showTaskMsgPop = ref(false)
    const taskMsgPopContent = ref<string[]>([])

    // ── 表单校验 ──────────────────────────────────────────────────
    const validateForm = (): string | null => {
        if (!formData.name) return '请输入任务名称'
        if (!formData.accounts.length) return '请选择发布账号'
        if (currentFrequency.value === 5 && !formData.custom_date.length) return '请选择任务日期'
        if (!formData.time_config[0] || !formData.time_config[1]) return '请选择任务时间'
        return null
    }

    const messageRobotId = computed(() => selectedMessageAgent.value?.id ?? '')
    const commentRobotId = computed(() => selectedCommentAgent.value?.id ?? '')

    // ── 构造提交参数 ──────────────────────────────────────────────
    const buildPayload = () => ({
        task_name: formData.name,
        accounts: formData.accounts,
        task_frep: formData.task_frep,
        time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
        custom_date: formData.custom_date,
        task_exec_type: formData.task_exec_type,
        minutes: formData.minutes,
        task_ids: formData.task_ids,
        task_type: takeoverType.value,
        message_type: strategyType.value,
        comment_type: commentRule.value,
        comment_robot_id: commentRobotId.value,
        comment_speech: formData.comment_scripts,
        message_robot_id: messageRobotId.value,
        message_speech: formData.fixed_scripts,
        message_number: pmReplyLimit.value
    })

    // ── 执行提交 ──────────────────────────────────────────────────
    const executeCreateTask = async () => {
        uni.showLoading({ title: '创建中...', mask: true })
        try {
            await createPrivateChatTask(buildPayload())
            uni.hideLoading()
            showCreateTaskSuccessDialog.value = true
            WechatOA.notify()
        } catch (error: any) {
            uni.hideLoading()
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
        }
    }

    // ── 入口：冲突检测 → 提交 ─────────────────────────────────────
    const handleCreateTask = async () => {
        const errMsg = validateForm()
        if (errMsg) {
            uni.$u.toast(errMsg)
            return
        }

        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: '检测冲突中...', mask: true })
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    accounts: formData.accounts,
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
                uni.showToast({ title: error, icon: 'none', duration: 3000 })
            }
        } else {
            await executeCreateTask()
        }
    }

    // ── 冲突弹窗确认（强制创建）─────────────────────────────────
    const handleTaskMsgPopConfirm = async () => {
        showTaskMsgPop.value = false
        await executeCreateTask()
    }

    // ── 创建成功回调 ──────────────────────────────────────────────
    const handleCreateTaskSuccess = () => {
        showCreateTaskSuccessDialog.value = false
        uni.$u.route({ url: '/ai_modules/device/pages/index/index', type: 'reLaunch' })
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
