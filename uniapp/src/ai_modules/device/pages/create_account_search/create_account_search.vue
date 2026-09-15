<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title-bold
            title="抖音账号搜寻获客"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }"
        />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]"
        >
            <steps :steps="STEPS" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <!-- ── 第 1 步：选择类型 ── -->
            <scroll-view v-show="currentStep === 1" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                    >
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                        >
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]"
                                >选择搜寻方式</text
                            >
                        </view>
                        <view class="px-[28rpx] py-[24rpx]">
                            <view class="flex gap-[20rpx]">
                                <view
                                    v-for="item in SEARCH_MODE_LIST"
                                    :key="item.value"
                                    class="flex-1 min-w-0 rounded-[24rpx] border border-solid px-[16rpx] py-[36rpx] text-center transition-all duration-200"
                                    :class="
                                        formData.search_mode === item.value
                                            ? 'border-primary bg-[#F8FBFF] shadow-[0_0_0_4rpx_rgba(37,99,235,0.09)]'
                                            : 'border-[#E8ECF2] bg-white'
                                    "
                                    @click="handlePickMode(item.value)"
                                >
                                    <view
                                        class="w-[92rpx] h-[92rpx] mx-auto mb-[20rpx] rounded-[26rpx] flex items-center justify-center"
                                        :class="
                                            formData.search_mode === item.value
                                                ? 'bg-[#EBF2FF]'
                                                : 'bg-[#F3F5FA]'
                                        "
                                    >
                                        <u-icon
                                            :name="item.icon"
                                            size="40"
                                            :color="
                                                formData.search_mode === item.value
                                                    ? '#0065fb'
                                                    : '#94A3B8'
                                            "
                                        />
                                    </view>
                                    <text
                                        class="text-[30rpx] font-bold"
                                        :class="
                                            formData.search_mode === item.value
                                                ? 'text-primary'
                                                : 'text-[#6B7280]'
                                        "
                                    >
                                        {{ item.name }}
                                    </text>
                                    <text
                                        class="block mt-[12rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed"
                                    >
                                        {{ item.desc }}
                                    </text>
                                </view>
                            </view>

                            <view
                                class="mt-[24rpx] rounded-[16rpx] bg-[#F2F7FF] border border-solid border-[#D6E6FF] px-[22rpx] py-[20rpx]"
                            >
                                <text class="text-[22rpx] text-[#2A5FAE] leading-relaxed">
                                    不管哪种方式，进到账号里都是同一套动作：读简介、读抖音号，从这两处找联系方式。
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <!-- ── 第 2 步：设置线索 ── -->
            <scroll-view v-show="currentStep === 2" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx]">
                    <clue-step
                        v-model="formData"
                        @add="handleOpenEdit(EditTarget.CLUE)"
                        @edit="handleOpenEdit(EditTarget.CLUE, $event)"
                        @ai-gen="showClueGenPopup = true"
                        @clear="handleClearClue"
                        @remove="currentClueList.splice($event, 1)"
                    />
                </view>
            </scroll-view>

            <!-- ── 第 3 步：填设置 ── -->
            <scroll-view v-show="currentStep === 3" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx]">
                    <config-step
                        v-model="formData"
                        :wechat-options="optionsData.wechatLists"
                        @add-miss-dm="handleOpenEdit(EditTarget.MISS_DM)"
                        @add-remark="handleOpenEdit(EditTarget.REMARK)"
                        @edit-miss-dm="handleOpenEdit(EditTarget.MISS_DM, $event)"
                        @edit-remark="handleOpenEdit(EditTarget.REMARK, $event)"
                    />
                </view>
            </scroll-view>

            <!-- ── 第 4 步：设定时间 ── -->
            <scroll-view v-show="currentStep === 4" scroll-y class="h-full">
                <view class="px-4 pb-[120rpx]">
                    <time-step
                        v-model="formData"
                        :current-frequency="currentFrequency"
                        :wechat-options="optionsData.wechatLists"
                        :task-error-msg="taskErrorMsg"
                        @change-frequency="currentFrequency = $event"
                    />
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] flex items-center gap-[16rpx]"
            :class="currentStep === 1 ? 'justify-end' : 'justify-between'"
        >
            <view
                v-if="currentStep !== 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(currentStep, 'prev')"
            >
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="currentStep !== STEPS.length">
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : ''"
                    :style="
                        canNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleStep(currentStep, 'next')"
                >
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask"
                >
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide"
                        >创建任务</text
                    >
                </view>
            </template>
        </view>
    </view>

    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        :title="editTitle"
        @confirm="handleEditConfirm"
    />
    <clue-gen-pop v-model="showClueGenPopup" @confirm="handleClueGenConfirm" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm"
    />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess"
    />
</template>

<script setup lang="ts">
import { AppTypeEnum } from '@/enums/appEnums'
import { getPublishAccountList } from '@/api/device'
import { ListenerTypeEnum } from '@/ai_modules/device/enums'
import { useDictOptions } from '@/hooks/useDictOptions'
import { useEventBusManager } from '@/hooks/useEventBusManager'
import Steps from '@/ai_modules/device/components/steps/steps.vue'
import KeywordsEdit from '@/ai_modules/device/components/keywords-edit/keywords-edit.vue'
import ClueGenPop from '@/ai_modules/device/components/clue-gen-pop/clue-gen-pop.vue'
import TaskConflictDialog from '@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue'
import ClueStep from './components/clue-step.vue'
import ConfigStep from './components/config-step.vue'
import TimeStep from './components/time-step.vue'
import { SEARCH_MODE_LIST, SearchModeEnum, createDefaultFormData } from './hooks/types'
import { useStep } from './hooks/useStep'
import { useCreateTask } from './hooks/useCreateTask'
import { useHistory } from './hooks/useHistory'

/** keywords-edit 一个弹窗服务三处新增，靠它区分这次是在加什么 */
enum EditTarget {
    CLUE = 'clue',
    MISS_DM = 'miss_dm',
    REMARK = 'remark'
}

const EDIT_TITLE: Record<EditTarget, string> = {
    [EditTarget.CLUE]: '线索词',
    [EditTarget.MISS_DM]: '私信话术',
    [EditTarget.REMARK]: '好友验证语'
}

const { on } = useEventBusManager()

const formData = reactive(createDefaultFormData())

const { step: currentStep, canNext, currentClueList, handleStep, STEPS } = useStep(formData)
const {
    currentFrequency,
    taskErrorMsg,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess
} = useCreateTask(formData)

// 进页面回填上次填过的线索词 / 私信话术 / 加好友备注
useHistory(formData)

// 加微客服微信下拉：account 就是 wxid，data-select 多选后按逗号拼进 wechat_id
const { optionsData } = useDictOptions<{ wechatLists: any[] }>({
    wechatLists: {
        api: getPublishAccountList,
        params: { page_size: 9999, type: AppTypeEnum.WECHAT },
        transformData: (res: any) =>
            res.lists?.map((item: any) => ({ text: item.nickname, value: item.account }))
    }
})

const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>()
const showKeywordsEdit = ref(false)
/** -1 = 新增，其余是正在编辑的下标 */
const editIndex = ref(-1)
const showClueGenPopup = ref(false)
const editTarget = ref<EditTarget>(EditTarget.CLUE)

const editTitle = computed(() =>
    editTarget.value === EditTarget.CLUE && formData.search_mode === SearchModeEnum.ACCOUNT
        ? '抖音号'
        : EDIT_TITLE[editTarget.value]
)

const editTargetList = computed<Record<EditTarget, string[]>>(() => ({
    [EditTarget.CLUE]: currentClueList.value,
    [EditTarget.MISS_DM]: formData.miss_scripts,
    [EditTarget.REMARK]: formData.remarks
}))

const handlePickMode = (mode: SearchModeEnum) => {
    formData.search_mode = mode
}

const handleOpenEdit = (target: EditTarget, index = -1) => {
    editTarget.value = target
    editIndex.value = index
    keywordsEditRef.value?.setFormData(index === -1 ? '' : editTargetList.value[target][index])
    showKeywordsEdit.value = true
}

const handleEditConfirm = (value: string) => {
    const text = value.trim()
    if (!text) return
    const list = editTargetList.value[editTarget.value]
    // 编辑时内容原样不动不算重复，只有撞上别的条目才拦
    const duplicated = list.findIndex((item) => item === text)
    if (duplicated >= 0 && duplicated !== editIndex.value) {
        uni.$u.toast('已经有这一条了')
        return
    }
    if (editIndex.value === -1) {
        list.push(text)
    } else {
        list[editIndex.value] = text
    }
    editIndex.value = -1
}

const handleClearClue = () => {
    const list = currentClueList.value
    if (!list.length) return
    uni.showModal({
        title: '提示',
        content: `清空全部 ${list.length} 条？`,
        success: (res) => {
            if (res.confirm) list.splice(0, list.length)
        }
    })
}

const handleClueGenConfirm = (list: any[]) => {
    const target = currentClueList.value
    const fresh = list
        .map((item: any) => (typeof item === 'string' ? item : item?.name ?? item?.content ?? ''))
        .map((item: string) => item.trim())
        .filter((item: string) => item && !target.includes(item))
    if (!fresh.length) {
        uni.$u.toast('这批词都已经在列表里了')
        return
    }
    target.push(...fresh)
    uni.$u.toast(`AI 补了 ${fresh.length} 个词，不合适可以删掉`)
}

on('confirm', (res: any) => {
    const { type, data } = res
    if (type === ListenerTypeEnum.CHOOSE_DEVICE) {
        formData.device_codes = data ?? []
    }
    if (type === ListenerTypeEnum.CHOOSE_DATE) {
        if (!data?.length) {
            currentFrequency.value = 0
            formData.custom_date = []
            return
        }
        formData.custom_date = data
        currentFrequency.value = 5
    }
})
</script>

<style scoped></style>
