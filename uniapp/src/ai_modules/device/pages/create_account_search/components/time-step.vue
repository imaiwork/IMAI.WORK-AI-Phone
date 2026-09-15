<template>
    <view class="flex flex-col gap-[16rpx]">
        <base-setting
            v-model="formData"
            :current-frequency="currentFrequency"
            @change-frequency="emit('change-frequency', $event)"
        />

        <view
            v-if="taskErrorMsg"
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
        >
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view class="w-[6rpx] h-[32rpx] bg-[#EF4444] rounded-full" />
                <u-icon name="warning-fill" size="24" color="#EF4444" />
                <text class="text-[28rpx] font-bold text-[#EF4444]">任务冲突</text>
            </view>
            <view class="px-[28rpx] py-[20rpx]">
                <text class="text-[#EF4444] leading-relaxed">{{ taskErrorMsg }}</text>
            </view>
        </view>

        <!-- 风控上限：这类任务不设上限很容易把号跑废，第 3 步的私信提示也指着它 -->
        <view
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
        >
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full flex-shrink-0" />
                <view class="flex-1 min-w-0">
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">单日上限</text>
                    <text class="block mt-[8rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                        一天最多新增多少条客资
                    </text>
                </view>
            </view>
            <view class="px-[28rpx] py-[24rpx]">
                <option-grid v-model="formData.day_cap" :options="DAY_CAP_LIST" :columns="4" />
                <text class="block mt-[24rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                    建议：单个抖音号一天翻号不超过 80 个、主动私信不超过 20 条。
                </text>
            </view>
        </view>

        <!-- 创建之前把前三步的设置摆一遍，省得回头一步步翻 -->
        <view
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
        >
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">这条任务会做什么</text>
            </view>
            <view class="px-[28rpx] py-[8rpx]">
                <view
                    v-for="row in summaryRows"
                    :key="row.label"
                    class="flex gap-[16rpx] py-[18rpx] border-[0] border-b border-solid border-[#F4F6F9] last:border-[0]"
                >
                    <text class="w-[120rpx] flex-shrink-0 text-[24rpx] text-[#9CA3AF]">
                        {{ row.label }}
                    </text>
                    <text class="flex-1 min-w-0 text-[24rpx] text-[#0D1117] leading-relaxed">
                        {{ row.text }}
                    </text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import BaseSetting from '@/ai_modules/device/components/base-setting/base-setting.vue'
import OptionGrid from './option-grid.vue'
import {
    CONTACT_TYPE_LIST,
    DAY_CAP_LIST,
    PER_KEYWORD_LIST,
    VISIBLE_SCAN_POSITION_LIST,
    ContactTypeEnum,
    MissActionEnum,
    SearchModeEnum
} from '../hooks/types'
import type { AccountSearchFormData } from '../hooks/types'

const props = defineProps<{
    modelValue: AccountSearchFormData
    currentFrequency: number
    wechatOptions: { text: string; value: string }[]
    taskErrorMsg?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: AccountSearchFormData): void
    (e: 'change-frequency', value: number): void
}>()

const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const findLabel = (list: { value: number; label: string }[], value: number) =>
    list.find((item) => item.value === value)?.label ?? String(value)

/** wechat_id 存的是 account(wxid) 逗号分隔，摘要里换回昵称 */
const wechatNames = computed(() => {
    if (!formData.value.wechat_id) return ''
    return formData.value.wechat_id
        .split(',')
        .filter(Boolean)
        .map((id) => props.wechatOptions.find((item) => item.value === id)?.text ?? id)
        .join('、')
})

const summaryRows = computed(() => {
    const data = formData.value
    const isKeywordMode = data.search_mode === SearchModeEnum.KEYWORD
    const clueList = isKeywordMode ? data.keywords : data.target_accounts

    const scanNames = VISIBLE_SCAN_POSITION_LIST.filter((item) =>
        data.scan_positions.includes(item.value)
    ).map((item) => item.name)
    const contactNames = CONTACT_TYPE_LIST.filter((item) =>
        data.contact_types.includes(item.value)
    ).map((item) => item.name)

    const phoneNote =
        data.phone_as_wechat && data.contact_types.includes(ContactTypeEnum.PHONE)
            ? '，手机号当微信号加'
            : ''

    return [
        {
            label: '设备',
            text: data.device_codes.length
                ? `${data.device_codes.length} 个设备`
                : '还没选，任务不会执行'
        },
        {
            label: '搜寻',
            text: isKeywordMode
                ? `用 ${clueList.length} 个线索词，每词最多 ${findLabel(
                      PER_KEYWORD_LIST,
                      data.per_kw
                  )}账号`
                : `指定的 ${clueList.length} 个账号`
        },
        {
            label: '查看',
            text: scanNames.length
                ? scanNames.join(' / ')
                : '没选位置，会一个都识别不到'
        },
        {
            label: '识别',
            text: contactNames.length
                ? `${contactNames.join(' / ')}${phoneNote}`
                : '没选类型，会一个都识别不到'
        },
        {
            label: '没识别到',
            text:
                data.miss_action === MissActionEnum.DM
                    ? `私信主动问（${data.miss_scripts.length} 条话术随机）`
                    : '直接跳过'
        },
        {
            label: '识别到',
            text:
                data.add_type !== 1
                    ? '只存客资，不自动加好友'
                    : wechatNames.value
                    ? `存客资 + 用「${wechatNames.value}」加好友`
                    : '存客资；还没选加微微信，加好友这步不会执行'
        },
        {
            label: '节奏',
            text:
                data.task_exec_type === 1
                    ? `创建后立即执行，单次跑 ${data.minutes} 分钟，单日最多 ${findLabel(
                          DAY_CAP_LIST,
                          data.day_cap
                      )}`
                    : `每 ${data.task_frep} 天一次，${data.time_config[0]}–${
                          data.time_config[1]
                      }，单日最多 ${findLabel(DAY_CAP_LIST, data.day_cap)}`
        }
    ]
})
</script>

<style scoped></style>
