<template>
    <popup-bottom
        v-model="show"
        height="78%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :is-disabled-touch="false">
        <template #header>
            <view class="px-[32rpx] pt-[20rpx]">
                <view class="mx-auto h-[8rpx] w-[72rpx] rounded-full bg-[#E5EAF3]" />
                <view class="flex h-[96rpx] items-center justify-between">
                    <view class="flex w-[100rpx] items-center">
                        <view
                            v-if="activeView === MembershipView.REDEEM"
                            class="flex h-[72rpx] items-center gap-[4rpx] text-xs text-[#475569]"
                            @click="activeView = MembershipView.OVERVIEW">
                            <u-icon name="arrow-left" size="24" color="#475569" />
                            <text>返回</text>
                        </view>
                    </view>
                    <text class="text-base font-bold text-[#0F172A]">
                        {{ activeView === MembershipView.OVERVIEW ? "会员订阅" : "兑换码" }}
                    </text>
                    <view class="flex w-[100rpx] justify-end">
                        <view
                            class="flex h-[64rpx] w-[64rpx] items-center justify-center rounded-full bg-[#F1F5F9]"
                            @click="emit('update:modelValue', false)">
                            <u-icon name="close" size="22" color="#64748B" />
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <view v-if="activeView === MembershipView.OVERVIEW" class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[40rpx] pb-[24rpx]">
                            <view
                                class="membership-banner relative overflow-hidden rounded-[32rpx] p-[32rpx]"
                                :class="{ 'is-free': !isMember }">
                                <view class="membership-orb membership-orb-large" />
                                <view class="membership-orb membership-orb-small" />
                                <view class="relative z-10">
                                    <view class="mb-[16rpx] flex items-center gap-[12rpx]">
                                        <view
                                            class="flex items-center gap-[8rpx] rounded-full px-[18rpx] py-[8rpx]"
                                            :class="isMember ? 'bg-[#FCD34D]' : 'bg-[rgba(255,255,255,0.22)]'">
                                            <image
                                                v-if="crownIcon && isMember"
                                                :src="crownIcon"
                                                class="h-[22rpx] w-[22rpx]"
                                                mode="aspectFit" />
                                            <text
                                                class="text-[22rpx] font-bold"
                                                :class="isMember ? 'text-[#7A4800]' : 'text-white'">
                                                {{ isMember ? "VIP 会员" : "普通用户" }}
                                            </text>
                                        </view>
                                        <text class="text-[22rpx] text-[rgba(255,255,255,0.78)]">
                                            {{ expiryText }}
                                        </text>
                                    </view>
                                    <text class="block text-lg font-extrabold text-white line-clamp-1">
                                        {{ planName }}
                                    </text>
                                    <text class="mt-[8rpx] block text-xs leading-[36rpx] text-[rgba(255,255,255,0.76)]">
                                        {{ planDescription }}
                                    </text>
                                </view>
                            </view>

                            <view class="mb-[20rpx] mt-[32rpx] flex items-center gap-[10rpx]">
                                <u-icon name="list" size="28" color="#2563EB" />
                                <text class="text-sm font-bold text-[#0F172A]">套餐用量</text>
                            </view>

                            <view class="flex flex-col gap-[28rpx]">
                                <view v-for="item in usageItems" :key="item.key">
                                    <view class="mb-[12rpx] flex items-center justify-between gap-[20rpx]">
                                        <view class="flex min-w-0 items-center gap-[12rpx]">
                                            <image
                                                v-if="item.icon"
                                                :src="item.icon"
                                                class="h-[28rpx] w-[28rpx] flex-shrink-0"
                                                mode="aspectFit" />
                                            <text class="text-sm font-semibold text-[#0F172A] line-clamp-1">
                                                {{ item.label }}
                                            </text>
                                        </view>
                                        <text class="flex-shrink-0 text-xs text-[#475569]">
                                            <text class="font-bold text-[#0F172A]">{{ item.used }}</text>
                                            / {{ item.limitText ?? item.limit }}
                                        </text>
                                    </view>
                                    <view class="h-[14rpx] overflow-hidden rounded-full bg-[#EEF2F8]">
                                        <view
                                            class="h-full rounded-full"
                                            :class="isUsageOverLimit(item) ? 'bg-[#EF4444]' : 'bg-primary'"
                                            :style="{ width: `${getUsagePercent(item)}%` }" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="px-[40rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))] pt-[20rpx]">
                    <!--
                      会员：输入兑换码
                      普通用户有上级：联系上级（隐藏兑换码）
                      普通用户无上级：联系客服（隐藏兑换码）
                    -->
                    <view
                        v-if="isMember"
                        class="membership-cta flex h-[96rpx] items-center justify-center gap-[12rpx] rounded-[28rpx] text-base font-extrabold text-[#7A4800]"
                        @click="activeView = MembershipView.REDEEM">
                        <u-icon name="coupon" size="30" color="#7A4800" />
                        <text>输入兑换码升级套餐</text>
                    </view>
                    <template v-else>
                        <view
                            class="membership-cta flex h-[96rpx] items-center justify-center gap-[12rpx] rounded-[28rpx] text-base font-extrabold text-[#7A4800]"
                            @click="emit('subscribe')">
                            <image
                                v-if="crownIcon"
                                :src="crownIcon"
                                class="h-[32rpx] w-[32rpx]"
                                mode="aspectFit" />
                            <text>{{ hasSuperior ? "联系上级" : "联系客服" }}</text>
                        </view>
                        <view
                            class="mt-[16rpx] flex h-[88rpx] items-center justify-center rounded-[28rpx] text-sm font-semibold text-[#64748B]"
                            @click="emit('update:modelValue', false)">
                            <text>关闭</text>
                        </view>
                    </template>
                    <text v-if="isMember" class="mt-[14rpx] block text-center text-[22rpx] text-[#64748B]">
                        兑换码可向客服或代理商获取
                    </text>
                </view>
            </view>

            <view v-else class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[40rpx] pb-[32rpx] pt-[8rpx]">
                            <text class="block text-center text-xs leading-[40rpx] text-[#475569]">
                                输入兑换码，即可解锁对应套餐权益
                            </text>
                            <input
                                v-model="redeemCode"
                                class="redeem-input mt-[40rpx] h-[108rpx] rounded-[28rpx] border-[2rpx] border-solid border-[#D9E2F0] bg-[#F7F9FC] px-[32rpx] text-center text-base text-[#0F172A]"
                                type="text"
                                :maxlength="32"
                                confirm-type="done"
                                placeholder="输入兑换码，如 VIP-XXXX-XXXX"
                                placeholder-class="text-[#64748B]"
                                @confirm="handleRedeem" />

                            <view
                                class="mt-[28rpx] flex items-start gap-[16rpx] rounded-[24rpx] border-[2rpx] border-solid border-[#FED7AA] bg-[#FFF7ED] p-[24rpx]">
                                <u-icon name="info-circle" size="26" color="#C2410C" />
                                <text class="flex-1 text-xs leading-[38rpx] text-[#9A3412]">
                                    兑换码一码一用，提交后立即生效，请确认无误后再兑换。
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="px-[40rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))] pt-[20rpx]">
                    <view
                        class="flex h-[96rpx] items-center justify-center rounded-[28rpx] text-base font-bold"
                        :class="isRedeemAvailable ? 'membership-cta text-[#7A4800]' : 'bg-[#E5EAF3] text-[#64748B]'"
                        @click="handleRedeem">
                        <text>{{ submitting ? "兑换中..." : "立即兑换" }}</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
interface MembershipUsageItem {
    key: string;
    label: string;
    used: number;
    limit: number;
    limitText?: string;
    icon?: string;
}

interface Props {
    modelValue: boolean;
    planName: string;
    planDescription: string;
    expiryText: string;
    isMember?: boolean;
    /** 普通用户是否有上级（userInfo.has_parent_agent） */
    hasSuperior?: boolean;
    crownIcon?: string;
    usageItems: MembershipUsageItem[];
    submitting?: boolean;
}

enum MembershipView {
    OVERVIEW = "overview",
    REDEEM = "redeem",
}

const props = withDefaults(defineProps<Props>(), {
    isMember: false,
    hasSuperior: false,
    crownIcon: "",
    submitting: false,
});

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "redeem", code: string): void;
    (event: "subscribe"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const activeView = ref(MembershipView.OVERVIEW);
const redeemCode = ref("");
const normalizedRedeemCode = computed(() => redeemCode.value.trim().toUpperCase());
const isRedeemAvailable = computed(() => normalizedRedeemCode.value.length >= 6 && !props.submitting);

const getUsagePercent = (item: MembershipUsageItem) => {
    if (item.limit <= 0) return 0;
    return Math.min(Math.max((item.used / item.limit) * 100, 0), 100);
};

const isUsageOverLimit = (item: MembershipUsageItem) => item.limit > 0 && item.used > item.limit;

const handleRedeem = () => {
    if (!isRedeemAvailable.value) return;
    emit("redeem", normalizedRedeemCode.value);
};

const reset = () => {
    activeView.value = MembershipView.OVERVIEW;
    redeemCode.value = "";
};

watch(
    () => props.modelValue,
    (visible) => {
        if (!visible) reset();
    },
);
</script>

<style lang="scss" scoped>
.membership-banner {
    background: linear-gradient(135deg, #3b4a8a 0%, #5b6bc8 100%);
    &.is-free {
        background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);
    }
}

.membership-orb {
    @apply pointer-events-none absolute rounded-full bg-[rgba(255,255,255,0.08)];
}

.membership-orb-large {
    @apply right-[-48rpx] top-[-56rpx] h-[240rpx] w-[240rpx];
}

.membership-orb-small {
    @apply bottom-[-80rpx] right-[60rpx] h-[200rpx] w-[200rpx] bg-[rgba(255,255,255,0.05)];
}

.membership-cta {
    background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 100%);
    box-shadow: 0 12rpx 36rpx rgba(245, 158, 11, 0.28);
}

.redeem-input {
    letter-spacing: 2rpx;
}

@media (prefers-reduced-motion: reduce) {
    .membership-cta {
        transition: none;
    }
}
</style>
