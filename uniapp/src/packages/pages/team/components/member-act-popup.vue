<template>
    <popup-bottom
        :model-value="modelValue"
        height="auto"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[32rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[66rpx] rounded bg-[#E5EAF3]" />
                    </view>
                </view>
                <view
                    v-if="member"
                    class="mt-[24rpx] mb-[16rpx] flex items-center gap-[24rpx] border-0 border-b border-solid border-[#F0F4FB] px-[12rpx] pb-[32rpx]">
                    <view
                        class="flex h-[88rpx] w-[88rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-full text-[32rpx] font-bold text-white"
                        :style="{ background: avatarColor }">
                        <image
                            v-if="member.avatar"
                            :src="member.avatar"
                            class="h-full w-full"
                            mode="aspectFill" />
                        <text v-else>{{ avatarText }}</text>
                    </view>
                    <view class="min-w-0 flex-1">
                        <view class="flex items-center gap-[14rpx]">
                            <text class="truncate text-[30rpx] font-bold text-[#0F172A]">
                                {{ member.nickname }}
                            </text>
                            <view
                                class="flex-shrink-0 rounded-full px-[16rpx] py-[4rpx] text-[20rpx] font-bold"
                                :style="{ background: roleMeta.bg, color: roleMeta.color }">
                                {{ roleMeta.label }}
                            </view>
                        </view>
                        <text class="mt-[6rpx] block text-[24rpx] text-[#94A3B8]">
                            剩余算力 {{ tokensText }} · 到期 {{ expireText }}
                        </text>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="px-[32rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))]">
                <view
                    v-for="item in actions"
                    :key="item.key"
                    class="flex items-center gap-[24rpx] rounded-[28rpx] px-[16rpx] py-[28rpx] active:bg-[#F6F9FF]"
                    @click.stop="emit('action', item.key)">
                    <view
                        class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center rounded-[24rpx]"
                        :style="{ background: item.bg }">
                        <u-icon :name="item.icon" size="36" :color="item.color" />
                    </view>
                    <view class="min-w-0 flex-1">
                        <text
                            class="mb-[4rpx] block text-[28rpx] font-semibold"
                            :class="item.danger ? 'text-[#EF4444]' : 'text-[#0F172A]'">
                            {{ item.label }}
                        </text>
                        <text class="block text-[22rpx] text-[#94A3B8] line-clamp-1">{{ item.desc }}</text>
                    </view>
                    <u-icon name="arrow-right" size="28" color="#CBD5E1" />
                </view>
                <view
                    class="mt-[16rpx] rounded-[28rpx] bg-[#F1F5F9] py-[28rpx] text-center text-[28rpx] font-semibold text-[#475569]"
                    @click="emit('update:modelValue', false)">
                    取消
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { ROLE_META, TeamRole } from '../_enums'

export type MemberActKey =
    | 'detail'
    | 'tokens'
    | 'expire'
    | 'setAdmin'
    | 'unsetAdmin'
    | 'remove'

const props = defineProps<{
    modelValue: boolean
    member: any
    isOwner: boolean
    avatarColor: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'action', key: MemberActKey): void
}>()

const roleMeta = computed(() => ROLE_META[Number(props.member?.team_role)] || ROLE_META[TeamRole.Member])
const avatarText = computed(() => String(props.member?.nickname || '?').slice(0, 1))
const tokensText = computed(() => {
    const n = Number(props.member?.tokens) || 0
    return Number.isInteger(n) ? String(n) : String(Math.round(n * 100) / 100)
})
const expireText = computed(
    () => props.member?.team_expire_time_desc || props.member?.team_expire_time || '永久'
)

const actions = computed(() => {
    const m = props.member
    if (!m) return []
    const role = Number(m.team_role)
    const list: Array<{
        key: MemberActKey
        label: string
        desc: string
        icon: string
        color: string
        bg: string
        danger?: boolean
    }> = [
        {
            key: 'detail',
            label: '查看明细',
            desc: '查看该成员的算力消耗记录',
            icon: 'order',
            color: '#0065FB',
            bg: '#E6EFFF'
        },
        {
            key: 'tokens',
            label: '修改算力',
            desc: `当前剩余 ${tokensText.value}，从团队算力池划拨`,
            icon: 'integral',
            color: '#F59E0B',
            bg: '#FFF7ED'
        },
        {
            key: 'expire',
            label: '设置到期',
            desc: `当前到期 ${expireText.value}，到期后暂停使用`,
            icon: 'calendar',
            color: '#0D9488',
            bg: '#ECFDF8'
        }
    ]
    if (props.isOwner && role === TeamRole.Member) {
        list.push({
            key: 'setAdmin',
            label: '设为管理员',
            desc: '可协助管理成员、查看消耗明细',
            icon: 'checkmark-circle',
            color: '#0065FB',
            bg: '#E6EFFF'
        })
    }
    if (props.isOwner && role === TeamRole.Admin) {
        list.push({
            key: 'unsetAdmin',
            label: '取消管理员',
            desc: '降为普通成员，收回管理权限',
            icon: 'close-circle',
            color: '#F59E0B',
            bg: '#FFF7ED'
        })
    }
    list.push({
        key: 'remove',
        label: '移出团队',
        desc: '移出后其数据保留，可重新邀请加入',
        icon: 'minus-circle',
        color: '#EF4444',
        bg: '#FFF1F0',
        danger: true
    })
    return list
})
</script>
