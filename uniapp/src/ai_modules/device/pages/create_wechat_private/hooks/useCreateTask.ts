// ════════════════════════════════════════════════════════════════
// hooks/useCreateTask.ts  —  Step2 创建任务 & 冲突检测
// ════════════════════════════════════════════════════════════════
import WechatOA from '@/utils/wechat'
import { createWechatPrivateTask, checkTaskPublishTime } from '@/api/device'
import type { WechatPrivateFormData } from './types'

export function useCreateTask(formData: WechatPrivateFormData, currentFrequency: Ref<number>) {
    const taskErrorMsg = ref('')
    const showCreateTaskSuccessDialog = ref(false)
    const showTaskMsgPop = ref(false)
    const taskMsgPopContent = ref<string[]>([])

    /** 实际调用接口创建任务 */
    const executeCreateTask = async () => {
        uni.showLoading({ title: '创建中...', mask: true })
        try {
            await createWechatPrivateTask({
                task_name: formData.name,
                accounts: formData.accounts,
                task_frep: formData.task_frep,
                time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
                custom_date: formData.custom_date,
                is_manual_agree: formData.interaction_action_switch,
                greet_strategy: formData.interaction_action,
                greet_content: formData.interaction_content,
                paragraph_enable: formData.stage_reply_switch,
                multiple_type: formData.multi_message_type,
                voice_reply_type: formData.voice_reply_type,
                voice_reply: formData.voice_reply_content,
                image_reply_type: formData.image_reply_type,
                image_reply: formData.image_reply_content,
                stop_enable: formData.sensitive_word_switch,
                stop_keywords: formData.sensitive_word,
                is_free_time: formData.time_type,
                task_exec_type: formData.task_exec_type,
                minutes: formData.minutes,
                task_ids: formData.task_ids,
                is_auto_group: formData.is_auto_group,
                group_trigger_mode: formData.group_trigger_mode,
                group_trigger_keywords: formData.group_trigger_keywords,
                sales_wechat: formData.sales_wechat,
                group_name_template: formData.group_name_template,
                is_greeting: formData.is_greeting,
                greeting_text: formData.greeting_text
            })
            uni.hideLoading()
            showCreateTaskSuccessDialog.value = true
            WechatOA.notify()
        } catch (error: any) {
            uni.hideLoading()
            // 24小时托管冲突特殊处理
            if (typeof error === 'string' && error.includes('24小时自动执行任务')) {
                uni.showModal({
                    title: '提示',
                    content:
                        '您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。',
                    success: (res) => {
                        if (res.confirm) {
                            uni.$u.route({ url: '/ai_modules/device/pages/index/index' })
                        }
                    }
                })
            } else {
                taskErrorMsg.value = error
                uni.showToast({ title: error, icon: 'none', duration: 3000 })
            }
        }
    }

    /** 点击"创建任务"按钮 */
    const handleCreateTask = async () => {
        if (!formData.name) return uni.$u.toast('请输入任务名称')
        if (!formData.accounts.length) return uni.$u.toast('请选择发布账号')
        if (currentFrequency.value === 5 && !formData.custom_date.length)
            return uni.$u.toast('请选择任务日期')
        if (!formData.time_config[0] || !formData.time_config[1])
            return uni.$u.toast('请选择任务时间')
        if (formData.task_exec_type === 1) {
            if (formData.minutes < 1) return uni.$u.toast('执行时间不能小于1分钟')
            if (formData.minutes > 9999) return uni.$u.toast('执行时间不能超过9999分钟')
        }

        // 即时执行：先检测冲突
        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: '检测冲突中...', mask: true })
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    accounts: formData.accounts,
                    minutes: formData.minutes
                })
                uni.hideLoading()
                if (messages && messages.length > 0) {
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
        } else {
            await executeCreateTask()
        }
    }

    /** 冲突弹窗确认（强制创建） */
    const handleTaskMsgPopConfirm = async () => {
        showTaskMsgPop.value = false
        await executeCreateTask()
    }

    /** 创建成功后跳转 */
    const handleCreateTaskSuccess = () => {
        showCreateTaskSuccessDialog.value = false
        uni.$u.route({ url: '/ai_modules/device/pages/index/index', type: 'reLaunch' })
    }

    return {
        taskErrorMsg,
        showCreateTaskSuccessDialog,
        showTaskMsgPop,
        taskMsgPopContent,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess
    }
}
