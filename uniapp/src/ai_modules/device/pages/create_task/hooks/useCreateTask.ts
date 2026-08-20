import { ref } from 'vue'
import WechatOA from '@/utils/wechat'
import { createMatrixTask, publishDeviceTask, checkTaskPublishTime } from '@/api/device'
import { TIME_INTERVAL } from './types'
import type { FormData } from './types'
import type KeywordsEdit from '@/ai_modules/device/components/keywords-edit/keywords-edit.vue'

/**
 * useCreateTask
 * 职责：Step3 时间配置 + 创建任务提交
 *   - 发布频率 / 任务频率 / 自定义日期切换
 *   - changeTimeConfig：核心时间表生成
 *   - 时间冲突校验（validateAllTimeConfigs）
 *   - handleCreateTask：提交创建任务
 */
export function useCreateTask(formData: FormData, taskType: Ref<number>) {
    // ── UI 状态 ──────────────────────────────────────────────────────────
    const showNumberPop = ref(false)
    const showKeywordsEdit = ref(false)
    const showCreateTaskSuccessDialog = ref(false)
    const showTaskMsgPop = ref(false)
    const isExpandDate = ref(false)

    // ── 频率选择状态 ──────────────────────────────────────────────────────
    const currentFrequencyIdx = ref(0) // 发布频率索引（5 = 自定义）
    const currentDayFrequencyIdx = ref(0) // 任务频率索引（5 = 自定义日期）
    const customPublishFrep = ref<number | null>(null)

    // ── 校验 & 错误状态 ───────────────────────────────────────────────────
    const timeErrors = ref<Record<number, { start_time?: boolean; end_time?: boolean }>>({})
    const taskErrorMsg = ref('')
    const taskMsgPopContent = ref<Record<string, any>>({})
    const pendingTaskIds = ref<string[]>([])

    // ── Ref：标记地点组件 ─────────────────────────────────────────────────
    const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>()

    // ── 工具：时间字符串转分钟数 ──────────────────────────────────────────
    const toMin = (t: string) => {
        const [h, m] = t.split(':').map(Number)
        return h * 60 + m
    }

    // ── 日期格式化 ────────────────────────────────────────────────────────
    const formatDate = (dateStr: string) => {
        if (!dateStr) return ''
        return uni.$u.timeFormat(new Date(dateStr.replace(/-/g, '/')), 'mm月dd日')
    }

    // ── 判断是否为即时执行模式下今天第一条 ───────────────────────────────
    const isImmediateFirstSlot = (configIndex: number, timeIndex: number): boolean => {
        if (formData.task_exec_type !== 1 || timeIndex !== 0) return false
        const date = formData.time_config[configIndex]?.date
        if (!date) return false
        return date === uni.$u.timeFormat(new Date(), 'yyyy-mm-dd')
    }

    // ── 核心：重新生成时间配置 ────────────────────────────────────────────
    const changeTimeConfig = () => {
        const now = new Date()
        const today = new Date(now)
        today.setHours(0, 0, 0, 0)
        const todayStr = uni.$u.timeFormat(today, 'yyyy-mm-dd')

        const generateTimes = (baseTime: Date) =>
            Array.from({ length: formData.publish_frep }, (_, i) => {
                const startMs = baseTime.getTime() + i * TIME_INTERVAL * 60 * 1000
                const startDate = new Date(startMs)
                let endDate = new Date(startMs + TIME_INTERVAL * 60 * 1000)
                // 跨天截断为 23:59
                if (endDate.getDate() !== startDate.getDate()) {
                    endDate = new Date(startDate)
                    endDate.setHours(23, 59, 59, 999)
                }
                return {
                    start_time: uni.$u.timeFormat(startDate, 'hh:MM'),
                    end_time: uni.$u.timeFormat(endDate, 'hh:MM')
                }
            })

        if (currentDayFrequencyIdx.value === 5 && formData.custom_date.length > 0) {
            formData.time_config = formData.custom_date.map((dateStr) => {
                const isToday = dateStr === todayStr
                const baseTime = isToday ? new Date(now) : new Date(dateStr.replace(/-/g, '/'))
                baseTime.setSeconds(0, 0)
                return {
                    date: uni.$u.timeFormat(new Date(dateStr.replace(/-/g, '/')), 'yyyy-mm-dd'),
                    times: generateTimes(baseTime)
                }
            })
        } else {
            formData.time_config = Array.from({ length: formData.task_frep }, (_, i) => {
                const dateObj = new Date(today.getTime() + i * 24 * 60 * 60 * 1000)
                const baseTime = i === 0 ? new Date(now) : new Date(dateObj)
                baseTime.setSeconds(0, 0)
                return {
                    date: uni.$u.timeFormat(dateObj, 'yyyy-mm-dd'),
                    times: generateTimes(baseTime)
                }
            })
        }

        timeErrors.value = {}
    }

    // ── 全局时间冲突校验 ──────────────────────────────────────────────────
    const validateAllTimeConfigs = () => {
        let hasAnyError = false
        const allErrors: Record<number, { start_time?: boolean; end_time?: boolean }> = {}

        for (let ci = 0; ci < formData.time_config.length; ci++) {
            const nonImmediate = formData.time_config[ci].times
                .map((time, ti) => ({ time, ti, skip: isImmediateFirstSlot(ci, ti) }))
                .filter((x) => !x.skip)

            for (let i = 0; i < nonImmediate.length; i++) {
                const { time: cur, ti } = nonImmediate[i]
                if (!cur.start_time || !cur.end_time) continue

                const s = toMin(cur.start_time)
                const e = toMin(cur.end_time)

                if (s >= e) {
                    allErrors[ti] = { start_time: true, end_time: true }
                    hasAnyError = true
                }

                if (i > 0) {
                    const { time: prev, ti: prevTi } = nonImmediate[i - 1]
                    if (prev.end_time && toMin(prev.end_time) > s) {
                        allErrors[ti] = { ...allErrors[ti], start_time: true }
                        allErrors[prevTi] = { ...allErrors[prevTi], end_time: true }
                        hasAnyError = true
                    }
                }
            }
        }

        return { hasError: hasAnyError, errors: allErrors }
    }

    const validateAndSetErrors = () => {
        const { hasError, errors } = validateAllTimeConfigs()
        timeErrors.value = errors
        return !hasError
    }

    // ── 时间 Picker 事件 ──────────────────────────────────────────────────
    const handleEndTimeClick = (startTime?: string) => {
        if (!startTime) uni.$u.toast('请先选择开始时间')
    }

    const handleStartTimeChange = (e: any, configIndex: number, timeIndex: number) => {
        if (isImmediateFirstSlot(configIndex, timeIndex)) return
        const value = e.detail.value
        const timeItem = formData.time_config[configIndex].times[timeIndex]
        timeItem.start_time = value
        const [h, m] = value.split(':').map(Number)
        const end = new Date()
        end.setHours(h, m + TIME_INTERVAL, 0, 0)
        timeItem.end_time = uni.$u.timeFormat(end, 'hh:MM')
        validateAndSetErrors()
    }

    const handleEndTimeChange = (e: any, configIndex: number, timeIndex: number) => {
        if (isImmediateFirstSlot(configIndex, timeIndex)) return
        const value = e.detail.value
        const timeItem = formData.time_config[configIndex].times[timeIndex]
        if (!timeItem.start_time) return

        const d = new Date().toDateString()
        const start = new Date(`${d} ${timeItem.start_time}`)
        const end = new Date(`${d} ${value}`)

        if (end <= start) {
            uni.$u.toast('结束时间必须晚于开始时间')
            return
        }
        if (end.getTime() - start.getTime() < TIME_INTERVAL * 60 * 1000) {
            uni.$u.toast(`间隔时间必须大于${TIME_INTERVAL}分钟`)
            return
        }

        timeItem.end_time = value
        validateAndSetErrors()
    }

    // ── 发布频率 ──────────────────────────────────────────────────────────
    const handlePublishFrequency = (freq: number) => {
        currentFrequencyIdx.value = 0
        formData.publish_frep = freq
        changeTimeConfig()
    }

    const handleNumberPopConfirm = (value: number) => {
        const maxFreq = Math.floor((24 * 60) / TIME_INTERVAL)
        if (value < 1) {
            uni.$u.toast('请输入有效的发布数量')
            return
        }
        if (value > maxFreq) {
            uni.$u.toast(`每日发布频率最高为${maxFreq}次`)
            return
        }
        currentFrequencyIdx.value = 5
        customPublishFrep.value = value
        formData.publish_frep = value
        showNumberPop.value = false
        changeTimeConfig()
    }

    // ── 任务频率 ──────────────────────────────────────────────────────────
    const handleDayFrequency = (days: number, index: number) => {
        if (currentDayFrequencyIdx.value === index) return
        formData.task_frep = days
        formData.custom_date = []
        currentDayFrequencyIdx.value = index
        changeTimeConfig()
    }

    const handleCustomDate = () => {
        uni.$u.route({
            url: '/ai_modules/device/pages/custom_date/custom_date',
            params: { date: JSON.stringify(formData.custom_date) }
        })
    }

    // ── 标记地点 ──────────────────────────────────────────────────────────
    const handleKeywordsEdit = () => {
        showKeywordsEdit.value = true
        keywordsEditRef.value?.setFormData(formData.location)
    }

    const handleKeywordsEditConfirm = (value: string) => {
        formData.location = value
        showKeywordsEdit.value = false
    }

    // ── 构建提交用时间配置 ────────────────────────────────────────────────
    const getTimeConfig = () => {
        const todayStr = uni.$u.timeFormat(new Date(), 'yyyy-mm-dd')
        return formData.time_config.map((item) => ({
            date: item.date,
            times: item.times.map((time, ti) => {
                const isImmediate =
                    formData.task_exec_type === 1 && item.date === todayStr && ti === 0
                return isImmediate ? 1 : `${time.start_time}-${time.end_time}`
            })
        }))
    }

    // ── 执行创建任务（内部） ──────────────────────────────────────────────
    const executeCreateTask = async (task_ids: string[]) => {
        uni.showLoading({ title: '创建中...', mask: true })
        try {
            const { id } = await createMatrixTask({
                name: formData.name,
                media_type: taskType.value,
                media_url: formData.materialList,
                copywriting: formData.copywriterList
            })
            await publishDeviceTask({
                name: formData.name,
                matrix_media_setting_id: id,
                time_config: getTimeConfig(),
                accounts: formData.accounts,
                publish_frep: formData.publish_frep,
                media_type: taskType.value,
                task_type: 3,
                scene: 2,
                data_type: 0,
                poi: formData.location,
                task_exec_type: formData.task_exec_type,
                task_ids
            })
            uni.hideLoading()
            showCreateTaskSuccessDialog.value = true
            WechatOA.notify()
        } catch (error: any) {
            uni.hideLoading()
            if (typeof error === 'string' && error.includes('24小时自动执行任务')) {
                uni.showModal({
                    title: '提示',
                    content:
                        '您已开启24小时自动执行任务，无法创建手动任务，如需手动创建，请先关闭24小时托管。',
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

    // ── 创建任务（对外暴露） ──────────────────────────────────────────────
    const handleCreateTask = async () => {
        const { hasError, errors } = validateAllTimeConfigs()
        if (hasError) {
            timeErrors.value = errors
            uni.$u.toast('时间配置存在冲突')
            return
        }
        if (!formData.name) {
            uni.$u.toast('请输入任务名称')
            return
        }
        if (!formData.accounts.length) {
            uni.$u.toast('请选择发布账号')
            uni.$u.route({ url: '/ai_modules/device/pages/account_choose/account_choose' })
            return
        }

        // 单素材 + 多同平台账号 → 二次确认
        if (formData.materialList.length === 1) {
            const typeCount = formData.accounts.reduce<Record<string, number>>((acc, a) => {
                acc[a.type] = (acc[a.type] || 0) + 1
                return acc
            }, {})
            const hasDuplicate = Object.values(typeCount).some((c) => c > 1)
            if (hasDuplicate) {
                const confirmed = await new Promise<boolean>((resolve) => {
                    uni.showModal({
                        title: '提示',
                        content:
                            '当前素材只有1条，但您选择了多个相同平台的账号，将只选择一个账号发布内容，是否继续？',
                        confirmText: '继续创建',
                        cancelText: '去修改',
                        success: (res) => resolve(res.confirm)
                    })
                })
                if (!confirmed) return
            }
        }

        uni.showLoading({ title: '检测中...', mask: true })
        try {
            if (formData.task_exec_type === 1) {
                const {
                    messages,
                    task_ids,
                    errors: rawErrors
                } = await checkTaskPublishTime({
                    accounts: formData.accounts,
                    time_config: getTimeConfig(),
                    minutes: 30
                })
                uni.hideLoading()
                if (messages?.length) {
                    pendingTaskIds.value = task_ids
                    taskMsgPopContent.value = { messages, errors: rawErrors }
                    showTaskMsgPop.value = true
                    return
                }
                await executeCreateTask(task_ids)
            } else {
                uni.hideLoading()
                await executeCreateTask([])
            }
        } catch (error: any) {
            uni.hideLoading()
            uni.showToast({ title: error, icon: 'none', duration: 3000 })
        }
    }

    const handleTaskMsgPopConfirm = async () => {
        showTaskMsgPop.value = false
        await executeCreateTask(pendingTaskIds.value)
        pendingTaskIds.value = []
    }

    const handleCreateTaskSuccess = () => {
        uni.$u.route({ url: '/ai_modules/device/pages/index/index', type: 'reLaunch' })
        showCreateTaskSuccessDialog.value = false
    }

    return {
        currentFrequencyIdx,
        currentDayFrequencyIdx,
        customPublishFrep,
        isExpandDate,
        showNumberPop,
        showKeywordsEdit,
        showCreateTaskSuccessDialog,
        showTaskMsgPop,
        taskMsgPopContent,
        taskErrorMsg,
        timeErrors,
        keywordsEditRef,
        formatDate,
        isImmediateFirstSlot,
        changeTimeConfig,
        handlePublishFrequency,
        handleDayFrequency,
        handleCustomDate,
        handleNumberPopConfirm,
        handleKeywordsEdit,
        handleKeywordsEditConfirm,
        handleStartTimeChange,
        handleEndTimeChange,
        handleEndTimeClick,
        handleCreateTask,
        handleTaskMsgPopConfirm,
        handleCreateTaskSuccess
    }
}
