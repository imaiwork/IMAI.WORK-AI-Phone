<template>
    <view class="flex flex-col gap-[16rpx]">
        <view class="flex gap-[16rpx]">
            <view
                class="flex-1 h-[92rpx] rounded-[20rpx] flex items-center justify-center gap-[10rpx] bg-white border border-solid border-[#E5E9F0]"
                @click="emit('add')"
            >
                <u-icon name="edit-pen" size="26" color="#4B5563" />
                <text class="text-[28rpx] font-bold text-[#4B5563]">
                    {{ isKeywordMode ? '手动输入' : '手动输入账号' }}
                </text>
            </view>
            <!-- AI 只在关键词模式给：它能编行业词，编不出真实存在的抖音号 -->
            <view
                v-if="isKeywordMode"
                class="flex-1 h-[92rpx] rounded-[20rpx] flex items-center justify-center gap-[10rpx] shadow-[0_8rpx_24rpx_rgba(37,115,255,0.25)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="emit('ai-gen')"
            >
                <u-icon name="grid-fill" size="26" color="#ffffff" />
                <text class="text-[28rpx] font-bold text-white">AI 生成</text>
            </view>
        </view>

        <view
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
        >
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[28rpx] font-extrabold text-[#0D1117]">{{ listLabel }}列表</text>
                <text class="text-[24rpx] text-[#9CA3AF]">({{ clueList.length }})</text>
                <view class="flex-1" />
                <text
                    v-if="clueList.length"
                    class="text-[26rpx] font-semibold text-primary"
                    @click="emit('clear')"
                >
                    清空
                </text>
            </view>

            <view class="px-[28rpx] py-[24rpx]">
                <chip-list
                    :list="clueList"
                    add-label="添加"
                    :empty-text="`还没有${listLabel}，手动输入${
                        isKeywordMode ? '或让 AI 生成一批' : ''
                    }`"
                    @add="emit('add')"
                    @edit="emit('edit', $event)"
                    @remove="emit('remove', $event)"
                />

                <template v-if="isKeywordMode">
                    <text class="block mt-[32rpx] mb-[20rpx] text-xs text-[#9CA3AF]">
                        每个词最多取多少个账号
                    </text>
                    <option-grid
                        v-model="formData.per_kw"
                        :options="PER_KEYWORD_LIST"
                        :columns="5"
                    />
                    <text class="block mt-[24rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                        泛词（比如「工厂制造」）一搜就是几百个号，取太多会把时间全花在翻号上。
                    </text>
                </template>
            </view>
        </view>

        <view
            v-if="isKeywordMode"
            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
        >
            <view
                class="flex items-center gap-[10rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full flex-shrink-0" />
                <view class="flex-1 min-w-0">
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">账号初筛</text>
                    <text class="block mt-[8rpx] text-[22rpx] text-[#9CA3AF] leading-relaxed">
                        先粗筛一遍，明显不是商家的号不用点进去
                    </text>
                </view>
                <u-switch v-model="preFilter" size="42" active-color="#0065fb" />
            </view>

            <view v-if="formData.pre_filter" class="px-[28rpx] py-[24rpx]">
                <view class="flex gap-[24rpx]">
                    <view class="flex-1 min-w-0">
                        <text class="block mb-[12rpx] text-xs text-[#9CA3AF]">粉丝数不少于</text>
                        <view
                            class="flex items-center gap-[12rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]"
                        >
                            <u-input
                                v-model="formData.fans_min"
                                type="number"
                                class="flex-1"
                                @blur="handleNumBlur('fans_min')"
                            />
                            <text class="text-[24rpx] text-[#86909C] flex-shrink-0">人</text>
                        </view>
                    </view>
                    <view class="flex-1 min-w-0">
                        <text class="block mb-[12rpx] text-xs text-[#9CA3AF]">作品数不少于</text>
                        <view
                            class="flex items-center gap-[12rpx] bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] h-[80rpx] border border-solid border-[#E5E9F0]"
                        >
                            <u-input
                                v-model="formData.works_min"
                                type="number"
                                class="flex-1"
                                @blur="handleNumBlur('works_min')"
                            />
                            <text class="text-[24rpx] text-[#86909C] flex-shrink-0">个</text>
                        </view>
                    </view>
                </view>

                <!-- 「最近更新在」「只看企业号 / 蓝V」按需求先不露出，
                     active_days / only_biz 仍按默认值随 scan_config 提交 -->
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import ChipList from './chip-list.vue'
import OptionGrid from './option-grid.vue'
import { PER_KEYWORD_LIST, SearchModeEnum, clampNumField } from '../hooks/types'
import type { AccountSearchFormData, NumFieldKey } from '../hooks/types'

const props = defineProps<{
    modelValue: AccountSearchFormData
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: AccountSearchFormData): void
    (e: 'add'): void
    (e: 'ai-gen'): void
    (e: 'clear'): void
    (e: 'remove', index: number): void
    (e: 'edit', index: number): void
}>()

const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const isKeywordMode = computed(() => formData.value.search_mode === SearchModeEnum.KEYWORD)
const listLabel = computed(() => (isKeywordMode.value ? '线索词' : '账号'))
const clueList = computed(() =>
    isKeywordMode.value ? formData.value.keywords : formData.value.target_accounts
)

const preFilter = computed({
    get: () => !!formData.value.pre_filter,
    set: (value: boolean) => (formData.value.pre_filter = value ? 1 : 0)
})

/** 失焦时把钳位后的值写回框里，否则框里显示 -5、实际存的是 0 */
const handleNumBlur = (key: NumFieldKey) => {
    formData.value[key] = clampNumField(key, formData.value[key])
}
</script>

<style scoped></style>
