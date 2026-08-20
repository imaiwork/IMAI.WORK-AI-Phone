// ================================================================
// hooks/useCreateTask.ts  —  创建任务 & 冲突检测
// ================================================================
import WechatOA from '@/utils/wechat'
import { createClosureTask, checkTaskPublishTime } from '@/api/device'
import { normalizeCommentTimeValue, type ClosureFormData } from './types'

export function useCreateTask(
    formData: ClosureFormData,
    isCollect: Ref<boolean>,
    isPinLunWork: Ref<boolean>,
    getTouchTypeList: Ref<any[]>,
    isComment: Ref<boolean>
) {
    const taskErrorMsg = ref<string>('')
    const showTaskMsgPop = ref(false)
    const taskMsgPopContent = ref<string[]>([])
    const showCreateTaskSuccessDialog = ref(false)

    const executeCreateTask = async () => {
        uni.showLoading({ title: '创建中...', mask: true })
        try {
            const params = {
                name: formData.name,
                accounts: formData.accounts,
                city: formData.region,
                industry_type: formData.customer_type,
                task_frep: formData.task_frep,
                task_type: isCollect.value ? 3 : isComment.value ? 1 : 2,
                time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
                task_date: formData.custom_date,
                industry: formData.industry.join(';'),
                industry_num: formData.industryNum,
                filter: formData.comment_filter_list.map((i: any) => i.value),
                content:
                    isCollect.value && formData.comment_type === 1 && isPinLunWork.value
                        ? formData.fixed_comment_list
                        : formData.comment_content_list,
                send_num: formData.commentNum,
                is_like: formData.comment_like,
                is_follow: formData.comment_follow,
                gender: formData.comment_gender,
                old: formData.comment_age,
                is_content_author: formData.skip_author,
                is_execed_clues: formData.filter_executed_customer,
                content_publish_day: normalizeCommentTimeValue(formData.content_time),
                comment_publish_day: normalizeCommentTimeValue(formData.comment_time),
                ip_address: formData.comment_region,
                marker_method: getTouchTypeList.value.filter((i) => i.checked).map((i) => i.name),
                task_exec_type: formData.task_exec_type,
                minutes: formData.minutes,
                task_ids: formData.task_ids
            }

            await createClosureTask(params)
            uni.hideLoading()
            showCreateTaskSuccessDialog.value = true
            WechatOA.notify()
        } catch (error: any) {
            uni.hideLoading()
            if (typeof error === 'string' && error.indexOf('24小时自动执行任务') > -1) {
                uni.showModal({
                    title: '提示',
                    content:
                        '您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。',
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

    const handleCreateTask = async () => {
        if (!formData.name) return uni.$u.toast('请输入任务名称')
        if (!formData.accounts.length) return uni.$u.toast('请选择账号')
        if (formData.task_exec_type === 0) {
            if (!formData.time_config[0] || !formData.time_config[1])
                return uni.$u.toast('请设置每日执行时间')
        }
        if (formData.task_exec_type === 1) {
            if (formData.minutes < 1) return uni.$u.toast('执行时间不能小于1分钟')
            if (formData.minutes > 9999) return uni.$u.toast('执行时间不能超过9999分钟')
        }

        if (formData.task_exec_type === 1) {
            uni.showLoading({ title: '检测冲突中...', mask: true })
            try {
                const { messages, task_ids } = await checkTaskPublishTime({
                    accounts: formData.accounts,
                    minutes: formData.minutes
                })
                uni.hideLoading()
                if (messages?.length > 0) {
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

    const handleTaskMsgPopConfirm = async () => {
        await executeCreateTask()
    }

    const handleCreateTaskSuccess = () => {
        uni.$u.route({ url: '/ai_modules/device/pages/index/index', type: 'reLaunch' })
        showCreateTaskSuccessDialog.value = false
    }

    return {
        taskErrorMsg,
        showTaskMsgPop,
        taskMsgPopContent,
        showCreateTaskSuccessDialog,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess
    }
}
