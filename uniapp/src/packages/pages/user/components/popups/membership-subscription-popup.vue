<template>
    <popup-bottom
        v-model="show"
        height="80%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :is-disabled-touch="true"
    >
        <template #header>
            <view
                class="border-[0] border-b-[2rpx] border-solid border-[#F3F4F6] px-[40rpx] pb-[24rpx] pt-[24rpx]"
            >
                <view class="mx-auto mb-[24rpx] h-[8rpx] w-[66rpx] rounded-full bg-[#E5E7EB]" />
                <view class="flex items-center justify-between gap-[20rpx]">
                    <view class="flex min-w-0 flex-1 items-center gap-[16rpx]">
                        <view
                            v-if="activeView === MembershipView.REDEEM"
                            class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F3F4F6] active:opacity-80"
                            @click="activeView = MembershipView.OVERVIEW"
                        >
                            <u-icon name="arrow-left" color="#666666" :size="24" />
                        </view>
                        <view class="min-w-0 flex-1">
                            <view class="text-[32rpx] font-bold text-[#1F2937]">{{
                                headerTitle
                            }}</view>
                            <view class="mt-[4rpx] text-xs leading-[36rpx] text-[#667085]">{{
                                headerSubtitle
                            }}</view>
                        </view>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F3F4F6] active:opacity-80"
                        @click="emit('update:modelValue', false)"
                    >
                        <u-icon name="close" color="#666666" :size="20" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0 bg-[#F9FAFB]">
                    <scroll-view scroll-y class="h-full w-full">
                        <view
                            v-if="activeView === MembershipView.OVERVIEW"
                            class="px-[32rpx] py-[24rpx]"
                        >
                            <view
                                class="membership-summary rounded-[28rpx] border-[2rpx] border-solid p-[28rpx]"
                                :class="{ 'is-free': !isMember }"
                            >
                                <view class="flex items-start gap-[24rpx]">
                                    <view
                                        class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center rounded-[24rpx] bg-primary"
                                    >
                                        <image
                                            v-if="isMember && crownIcon"
                                            :src="crownIcon"
                                            class="h-[38rpx] w-[38rpx]"
                                            mode="aspectFit"
                                        />
                                        <u-icon
                                            v-else
                                            name="account-fill"
                                            size="38"
                                            color="#FFFFFF"
                                        />
                                    </view>
                                    <view class="min-w-0 flex-1">
                                        <view class="flex min-w-0 items-center gap-[12rpx]">
                                            <text
                                                class="block min-w-0 text-[30rpx] font-bold text-[#172B4D] line-clamp-1"
                                            >
                                                {{ planName }}
                                            </text>
                                            <text
                                                class="flex-shrink-0 rounded-full px-[14rpx] py-[5rpx] text-[20rpx] font-semibold"
                                                :class="
                                                    isMember
                                                        ? 'bg-primary text-white'
                                                        : 'bg-[#E8EEF8] text-[#526581]'
                                                "
                                            >
                                                {{ isMember ? '已开通' : '当前套餐' }}
                                            </text>
                                        </view>
                                        <text
                                            class="mt-[8rpx] block text-xs leading-[36rpx] text-[#526581] line-clamp-2"
                                        >
                                            {{ planDescription }}
                                        </text>
                                    </view>
                                </view>
                                <view
                                    class="mt-[24rpx] flex items-center justify-between border-[0] border-t-[2rpx] border-solid border-[#D8E5F7] pt-[20rpx]"
                                >
                                    <text class="text-[22rpx] text-[#667085]">有效期</text>
                                    <text class="text-[22rpx] font-semibold text-[#344054]">{{
                                        expiryText
                                    }}</text>
                                </view>
                            </view>

                            <view
                                class="mb-[16rpx] mt-[32rpx] flex items-end justify-between px-[4rpx]"
                            >
                                <view>
                                    <text class="block text-sm font-bold text-[#111827]"
                                        >资源用量</text
                                    >
                                    <text class="mt-[4rpx] block text-[22rpx] text-[#667085]"
                                        >额度以当前套餐配置为准</text
                                    >
                                </view>
                                <text class="text-[22rpx] text-[#98A2B3]">已用 / 总量</text>
                            </view>

                            <view
                                class="overflow-hidden rounded-[28rpx] bg-white px-[28rpx] shadow-[0_8rpx_28rpx_rgba(16,39,73,0.06)]"
                            >
                                <view
                                    v-for="(item, index) in usageItems"
                                    :key="item.key"
                                    class="py-[24rpx]"
                                    :class="
                                        index < usageItems.length - 1
                                            ? 'border-[0] border-b-[2rpx] border-solid border-[#EDF1F6]'
                                            : ''
                                    "
                                >
                                    <view class="flex items-center justify-between gap-[20rpx]">
                                        <view class="min-w-0 flex-1">
                                            <text
                                                class="text-sm font-semibold text-[#1D2939] line-clamp-1"
                                            >
                                                {{ item.label }}
                                            </text>
                                            <text
                                                class="mt-[6rpx] block text-[20rpx]"
                                                :class="getUsageStateClass(item)"
                                            >
                                                {{ getUsageStateText(item) }}
                                            </text>
                                        </view>
                                        <view
                                            class="flex flex-shrink-0 items-baseline text-[#98A2B3]"
                                        >
                                            <text class="text-base font-bold text-[#1D2939]">{{
                                                item.used
                                            }}</text>
                                            <text class="ml-[6rpx] text-[22rpx]"
                                                >/ {{ item.limitText ?? item.limit }}</text
                                            >
                                        </view>
                                    </view>
                                    <view
                                        v-if="item.limit > 0"
                                        class="mt-[16rpx] h-[8rpx] overflow-hidden rounded-full bg-[#E9EEF5]"
                                    >
                                        <view
                                            class="h-full rounded-full"
                                            :class="
                                                isUsageOverLimit(item) ? 'bg-error' : 'bg-primary'
                                            "
                                            :style="{ width: `${getUsagePercent(item)}%` }"
                                        />
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view v-else class="px-[32rpx] py-[24rpx]">
                            <view
                                class="rounded-[28rpx] bg-white p-[32rpx] shadow-[0_8rpx_28rpx_rgba(16,39,73,0.06)]"
                            >
                                <text class="block text-sm font-semibold text-[#1D2939]"
                                    >输入兑换码</text
                                >
                                <text
                                    class="mt-[8rpx] block text-xs leading-[40rpx] text-[#667085]"
                                >
                                    输入兑换码，即可解锁对应套餐权益
                                </text>
                                <input
                                    v-model="redeemCode"
                                    class="redeem-input mt-[28rpx] h-[100rpx] rounded-[22rpx] border-[2rpx] border-solid border-[#D0D9E7] bg-[#F8FAFD] px-[28rpx] text-center text-base font-semibold text-[#1D2939]"
                                    type="text"
                                    :maxlength="32"
                                    confirm-type="done"
                                    placeholder="输入兑换码，如 VIP-XXXX-XXXX"
                                    placeholder-class="text-[#64748B]"
                                    @confirm="handleRedeem"
                                />

                                <view
                                    class="mt-[24rpx] flex items-start gap-[16rpx] rounded-[20rpx] bg-[#FFF7ED] p-[22rpx]"
                                >
                                    <u-icon name="info-circle" size="26" color="#C2410C" />
                                    <text class="flex-1 text-xs leading-[38rpx] text-[#9A3412]">
                                        兑换码一码一用，提交后立即生效，请确认无误后再兑换。
                                    </text>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view
                    class="flex-shrink-0 border-[0] border-t-[2rpx] border-solid border-[#F3F4F6] bg-white px-[32rpx] pt-[24rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]"
                >
                    <template v-if="activeView === MembershipView.OVERVIEW">
                        <view
                            v-if="isMember"
                            class="membership-action bg-primary text-white active:opacity-90"
                            @click="activeView = MembershipView.REDEEM"
                        >
                            <u-icon name="coupon" size="30" color="#FFFFFF" />
                            <text>使用兑换码升级</text>
                        </view>
                        <view
                            v-else
                            class="membership-action bg-primary text-white active:opacity-90"
                            @click="emit('subscribe')"
                        >
                            <u-icon name="server-man" size="30" color="#FFFFFF" />
                            <text>{{ hasSuperior ? '联系上级开通' : '联系客服开通' }}</text>
                        </view>
                        <text
                            v-if="isMember"
                            class="mt-[14rpx] block text-center text-[22rpx] text-[#667085]"
                        >
                            兑换码可向客服或代理商获取
                        </text>
                    </template>
                    <view
                        v-else
                        class="membership-action"
                        :class="
                            isRedeemAvailable
                                ? 'bg-primary text-white active:opacity-90'
                                : 'bg-[#E4E9F0] text-[#667085]'
                        "
                        @click="handleRedeem"
                    >
                        <text>{{ submitting ? '兑换中...' : '立即兑换' }}</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
interface MembershipUsageItem {
    key: string
    label: string
    used: number
    limit: number
    limitText?: string
    icon?: string
}

interface Props {
    modelValue: boolean
    planName: string
    planDescription: string
    expiryText: string
    isMember?: boolean
    /** 普通用户是否有上级（userInfo.has_parent_agent） */
    hasSuperior?: boolean
    crownIcon?: string
    usageItems: MembershipUsageItem[]
    submitting?: boolean
}

enum MembershipView {
    OVERVIEW = 'overview',
    REDEEM = 'redeem'
}

const props = withDefaults(defineProps<Props>(), {
    isMember: false,
    hasSuperior: false,
    crownIcon: '',
    submitting: false
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'redeem', code: string): void
    (event: 'subscribe'): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const activeView = ref(MembershipView.OVERVIEW)
const headerTitle = computed(() =>
    activeView.value === MembershipView.OVERVIEW ? '会员与用量' : '兑换码'
)
const headerSubtitle = computed(() =>
    activeView.value === MembershipView.OVERVIEW
        ? '查看当前套餐及资源使用情况'
        : '输入兑换码，解锁对应套餐权益'
)
const redeemCode = ref('')
const normalizedRedeemCode = computed(() => redeemCode.value.trim().toUpperCase())
const isRedeemAvailable = computed(
    () => normalizedRedeemCode.value.length >= 6 && !props.submitting
)

const getUsagePercent = (item: MembershipUsageItem) => {
    if (item.limit <= 0) return 0
    return Math.min(Math.max((item.used / item.limit) * 100, 0), 100)
}

const isUsageOverLimit = (item: MembershipUsageItem) => item.limit > 0 && item.used > item.limit

const getUsageStateText = (item: MembershipUsageItem) => {
    if (item.limit === -1) return '当前套餐未开放'
    if (item.limit === 0) return '不限额度'
    if (isUsageOverLimit(item)) return `已超出 ${item.used - item.limit}`
    return `剩余 ${Math.max(item.limit - item.used, 0)}`
}

const getUsageStateClass = (item: MembershipUsageItem) => {
    if (item.limit === -1) return 'text-[#98A2B3]'
    if (isUsageOverLimit(item)) return 'text-error'
    return 'text-[#667085]'
}

const handleRedeem = () => {
    if (!isRedeemAvailable.value) return
    emit('redeem', normalizedRedeemCode.value)
}

const reset = () => {
    activeView.value = MembershipView.OVERVIEW
    redeemCode.value = ''
}

watch(
    () => props.modelValue,
    (visible) => {
        if (!visible) reset()
    }
)
</script>

<style lang="scss" scoped>
.membership-summary {
    @apply border-[#D8E5F7] bg-[#EFF6FF];

    &.is-free {
        @apply border-[#DCE4EF] bg-[#F3F6FA];
    }
}

.membership-action {
    @apply flex h-[88rpx] w-full items-center justify-center gap-[12rpx] rounded-full text-[28rpx] font-bold;
}

.redeem-input {
    letter-spacing: 2rpx;
}
</style>
