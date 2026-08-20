<template>
    <popup-bottom
        :model-value="modelValue"
        height="85%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[32rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[72rpx] rounded bg-[#E5EAF3]" />
                    </view>
                </view>
                <view class="mt-[24rpx] mb-[20rpx] flex items-center gap-[20rpx]">
                    <view
                        class="flex h-[88rpx] w-[88rpx] flex-shrink-0 items-center justify-center rounded-[24rpx]"
                        style="background: linear-gradient(135deg, #2563eb, #4f9dff)">
                        <u-icon name="checkmark-circle" size="44" color="#fff" />
                    </view>
                    <view class="min-w-0 flex-1">
                        <text class="block text-[34rpx] font-bold text-[#0F172A]">套餐权益</text>
                        <text class="mt-[6rpx] block text-[24rpx] text-[#94A3B8]">
                            个人版与企业 OEM 能力对比
                        </text>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F1F5F9]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#64748B" />
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view class="flex h-full flex-col">
                <view class="min-h-0 flex-1">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[32rpx] pb-[16rpx]">
                        <view class="mb-[28rpx] flex gap-[16rpx]">
                            <view
                                class="min-w-0 flex-1 rounded-[24rpx] border-[2rpx] border-solid p-[22rpx]"
                                :class="!isOem ? 'border-primary bg-primary-light-9' : 'border-[#E8EEF6] bg-[#F8FAFC]'">
                                <view class="mb-[10rpx] flex items-start justify-between gap-[8rpx]">
                                    <text class="text-[26rpx] font-bold text-[#0F172A]">个人版</text>
                                    <view
                                        v-if="!isOem"
                                        class="rounded-full bg-primary px-[12rpx] py-[4rpx] text-[18rpx] font-bold text-white">
                                        当前
                                    </view>
                                </view>
                                <text class="mb-[8rpx] block text-[36rpx] font-extrabold leading-none text-[#0F172A]">
                                    ¥0
                                </text>
                                <text class="mb-[16rpx] block text-[20rpx] leading-[1.45] text-[#64748B]">
                                    注册即用 · 按算力付费
                                </text>
                                <text class="mb-[8rpx] block text-[20rpx] font-semibold text-[#334155]">
                                    · 全部 AI 按算力使用
                                </text>
                                <text class="block text-[20rpx] font-semibold text-[#334155]">
                                    · 个人账号独立使用
                                </text>
                            </view>

                            <view
                                class="min-w-0 flex-1 rounded-[24rpx] border-[2rpx] border-solid p-[22rpx]"
                                :class="isOem ? 'border-primary bg-primary-light-9' : 'border-[#DCE8FF] bg-[#F5F9FF]'">
                                <view class="mb-[10rpx] flex items-start justify-between gap-[8rpx]">
                                    <text class="text-[26rpx] font-bold text-[#0F172A]">
                                        企业OEM
                                        <text class="text-[20rpx] text-[#F59E0B]">★</text>
                                    </text>
                                    <view
                                        v-if="isOem"
                                        class="rounded-full bg-primary px-[12rpx] py-[4rpx] text-[18rpx] font-bold text-white">
                                        当前
                                    </view>
                                    <view
                                        v-else-if="isPending"
                                        class="rounded-full bg-[#F59E0B] px-[12rpx] py-[4rpx] text-[18rpx] font-bold text-white">
                                        审核中
                                    </view>
                                </view>
                                <text class="mb-[8rpx] block text-[32rpx] font-extrabold leading-none text-[#0F172A]">
                                    长期有效
                                </text>
                                <text class="mb-[16rpx] block text-[20rpx] leading-[1.45] text-[#64748B]">
                                    独立品牌 · 自主经营
                                </text>
                                <text class="mb-[8rpx] block text-[20rpx] font-semibold text-[#334155]">
                                    · 席位 {{ seatLimit || '—' }} 个
                                </text>
                                <text class="block text-[20rpx] font-semibold text-[#334155]">
                                    · 含个人版全部能力
                                </text>
                            </view>
                        </view>

                        <view class="overflow-hidden rounded-[28rpx] border-[2rpx] border-solid border-[#EEF2F7] bg-white">
                            <view
                                class="flex items-center gap-[8rpx] border-0 border-b border-solid border-[#EEF2F7] bg-[#F8FAFC] px-[24rpx] py-[20rpx]">
                                <text class="min-w-0 flex-[1.55] text-[22rpx] font-bold text-[#94A3B8]">权益项</text>
                                <text class="flex-1 text-center text-[22rpx] font-bold text-[#94A3B8]">个人版</text>
                                <text class="flex-1 text-center text-[22rpx] font-bold text-primary">企业OEM</text>
                            </view>

                            <view
                                v-for="(group, gIdx) in groups"
                                :key="group.title"
                                :class="gIdx > 0 ? 'border-0 border-t border-solid border-[#F1F5F9]' : ''">
                                <text class="block px-[24rpx] pt-[22rpx] pb-[8rpx] text-[26rpx] font-extrabold text-[#0F172A]">
                                    {{ group.title }}
                                </text>
                                <view
                                    v-for="row in group.rows"
                                    :key="row.name"
                                    class="flex items-center gap-[8rpx] px-[24rpx] py-[18rpx]">
                                    <text class="min-w-0 flex-[1.55] text-[24rpx] font-medium leading-[1.4] text-[#475569]">
                                        {{ row.name }}
                                    </text>
                                    <view class="flex flex-1 items-center justify-center">
                                        <view
                                            v-if="row.personal === true"
                                            class="flex h-[40rpx] w-[40rpx] items-center justify-center rounded-full bg-primary-light-9">
                                            <u-icon name="checkmark" size="22" color="#0065FB" />
                                        </view>
                                        <text v-else-if="row.personal === false" class="text-[28rpx] font-semibold text-[#CBD5E1]">
                                            —
                                        </text>
                                        <text v-else class="text-center text-[20rpx] font-semibold leading-[1.3] text-[#64748B]">
                                            {{ row.personal }}
                                        </text>
                                    </view>
                                    <view class="flex flex-1 items-center justify-center">
                                        <view
                                            v-if="row.oem === true"
                                            class="flex h-[40rpx] w-[40rpx] items-center justify-center rounded-full bg-primary-light-8">
                                            <u-icon name="checkmark" size="22" color="#0065FB" />
                                        </view>
                                        <text v-else-if="row.oem === false" class="text-[28rpx] font-semibold text-[#CBD5E1]">
                                            —
                                        </text>
                                        <text v-else class="text-center text-[20rpx] font-semibold leading-[1.3] text-primary">
                                            {{ row.oem }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                        </view>
                    </scroll-view>
                </view>

                <view
                    class="border-0 border-t border-solid border-[#F1F5F9] bg-white px-[32rpx] pt-[16rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                    <view
                        class="flex h-[96rpx] items-center justify-center rounded-[28rpx] bg-primary text-[30rpx] font-bold text-white"
                        @click="emit('update:modelValue', false)">
                        我知道了
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean
    oemStatus: number
    seatLimit: number | string
    enabledCount: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const isOem = computed(() => Number(props.oemStatus) === 2)
const isPending = computed(() => Number(props.oemStatus) === 1)

const groups = computed(() => [
    {
        title: '基础 AI 能力',
        rows: [
            { name: '大模型对话 / AI智能体', personal: true, oem: true },
            { name: 'AI作图 / AI PPT', personal: true, oem: true },
            { name: '数字人 / 数字人混剪', personal: true, oem: true },
            { name: '获客工具(高德/视频号/AI手机)', personal: true, oem: true }
        ]
    },
    {
        title: '企业品牌',
        rows: [
            { name: '独立站点域名', personal: false, oem: true },
            { name: '品牌 LOGO / 站点标题', personal: false, oem: true },
            { name: '自有小程序(配置/发版)', personal: false, oem: true },
            { name: '访客归属企业', personal: false, oem: true }
        ]
    },
    {
        title: '组织管理',
        rows: [
            { name: '成员席位', personal: false, oem: `${props.seatLimit || '—'} 个` },
            { name: '邀请码入团 / 移除成员', personal: false, oem: true },
            { name: '成员到期管控', personal: false, oem: true },
            { name: '成员算力消耗明细', personal: false, oem: true }
        ]
    },
    {
        title: '经营能力',
        rows: [
            { name: '算力划拨给成员', personal: false, oem: true },
            { name: '自有卡密套餐 / 制卡', personal: false, oem: true },
            {
                name: '授权功能应用',
                personal: '按算力使用',
                oem: `${props.enabledCount} 项已启用`
            }
        ]
    }
])
</script>
