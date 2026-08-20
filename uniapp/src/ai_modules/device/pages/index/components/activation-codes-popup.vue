<template>
    <popup-bottom v-model="showPopup" height="82%" custom-class="bg-white" :clearable="false" :mask-close-able="true">
        <template #header>
            <view class="px-[32rpx] pt-[24rpx] pb-[20rpx] border-b border-[#F0F0F0]">
                <view class="w-[72rpx] h-[8rpx] bg-[#E5EAF3] rounded-full mx-auto mb-[28rpx]"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <text class="block text-[32rpx] font-bold text-[#1A1A1A]">我的CDK</text>
                        <text class="block text-[22rpx] text-[#888888] mt-[6rpx]">
                            {{
                                loading
                                    ? "加载中..."
                                    : `共 ${codes.length} 张 · 未使用 ${unusedCodeCount} 张`
                            }}
                        </text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] bg-[#F4F6FA] rounded-full flex items-center justify-center active:opacity-80"
                        @click="showPopup = false">
                        <u-icon name="close" color="#888888" size="24" />
                    </view>
                </view>
            </view>
        </template>

        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[24rpx] py-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]">
                    <view v-if="loading" class="pt-[160rpx] flex flex-col items-center">
                        <u-loading mode="circle" size="40" color="#2B6EFF" />
                        <text class="text-[24rpx] text-[#888888] mt-[24rpx]">加载中...</text>
                    </view>

                    <view v-else-if="codes.length > 0" class="flex flex-col gap-y-[20rpx]">
                        <view
                            v-for="code in codes"
                            :key="code.code"
                            class="bg-white rounded-[24rpx] overflow-hidden border border-[#E8ECF0] shadow-[0_4rpx_20rpx_rgba(15,23,42,0.04)]">
                            <view
                                class="flex items-center justify-between px-[24rpx] py-[18rpx] bg-[#F7F9FF] border-b border-[#F0F0F0]">
                                <view
                                    class="px-[18rpx] py-[4rpx] rounded-full"
                                    :class="isPermanentCode(code) ? 'bg-[#FFF7E6]' : 'bg-[#EBF2FF]'">
                                    <text
                                        class="text-[22rpx] font-bold"
                                        :class="isPermanentCode(code) ? 'text-[#D46B08]' : 'text-primary'">
                                        {{ code.planName }} · {{ code.daysLabel }}
                                    </text>
                                </view>
                                <view
                                    class="px-[16rpx] py-[4rpx] rounded-full"
                                    :class="code.status === 'unused' ? 'bg-[#F0FBE8]' : 'bg-[#F0F0F0]'">
                                    <text
                                        class="text-[20rpx] font-bold"
                                        :class="code.status === 'unused' ? 'text-[#52C41A]' : 'text-[#BBBBBB]'">
                                        {{ code.status === "unused" ? "● 未使用" : "✓ 已使用" }}
                                    </text>
                                </view>
                            </view>
                            <view class="px-[24rpx] py-[22rpx]">
                                <view class="flex items-center gap-x-[16rpx]">
                                    <text
                                        class="flex-1 min-w-0 text-[34rpx] font-mono font-bold tracking-wide line-clamp-1"
                                        :class="
                                            code.status === 'used' ? 'text-[#BBBBBB] line-through' : 'text-[#1A1A1A]'
                                        ">
                                        {{ code.code }}
                                    </text>
                                    <view
                                        class="w-[56rpx] h-[56rpx] rounded-[14rpx] bg-[#F4F6FA] border border-[#E8ECF0] flex items-center justify-center shrink-0 active:bg-[#E8ECF0]"
                                        @click="copy(code.code)">
                                        <image
                                            src="/static/images/icons/copy.svg"
                                            mode="aspectFit"
                                            class="w-[26rpx] h-[26rpx]" />
                                    </view>
                                </view>
                                <text class="block text-[22rpx] text-[#BBBBBB] mt-[12rpx]">
                                    购买于 {{ code.buyTime || "-" }}
                                </text>
                                <view
                                    v-if="code.status === 'used'"
                                    class="mt-[16rpx] rounded-[16rpx] bg-[#F4F6FA] px-[20rpx] py-[14rpx]">
                                    <view class="flex items-center gap-x-[10rpx]">
                                        <image
                                            src="@/ai_modules/device/static/icons/device.svg"
                                            mode="aspectFit"
                                            class="w-[24rpx] h-[24rpx] shrink-0" />
                                        <text class="text-[20rpx] text-[#888888]">
                                            使用设备：<text class="font-semibold text-[#1A1A1A]">{{
                                                code.usedDeviceName || "-"
                                            }}</text>
                                        </text>
                                    </view>
                                    <view class="flex items-center gap-x-[10rpx] mt-[8rpx]">
                                        <image
                                            src="@/ai_modules/device/static/icons/calendar_gray.svg"
                                            mode="aspectFit"
                                            class="w-[24rpx] h-[24rpx] shrink-0" />
                                        <text class="text-[20rpx] text-[#888888]">
                                            使用于：<text class="font-semibold text-[#1A1A1A]">{{
                                                code.usedTime || "-"
                                            }}</text>
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-else class="pt-[120rpx] flex flex-col items-center">
                        <image
                            src="@/ai_modules/device/static/icons/ticket.svg"
                            mode="aspectFit"
                            class="w-[110rpx] h-[110rpx] opacity-60" />
                        <text class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[24rpx]">暂无CDK</text>
                        <text class="text-[22rpx] text-[#888888] mt-[8rpx]">购买后会在这里展示CDK</text>
                        <view
                            class="mt-[28rpx] h-[76rpx] px-[36rpx] rounded-full bg-primary flex items-center justify-center"
                            @click="handlePurchase">
                            <text class="text-white text-[24rpx] font-semibold">去购买</text>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useCopy } from "@/hooks/useCopy";

interface ActivationCodeItem {
    code: string;
    planName: string;
    daysLabel: string;
    status: "unused" | "used";
    buyTime: string;
    usedDeviceName?: string;
    usedTime?: string;
}

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        codes?: ActivationCodeItem[];
        loading?: boolean;
    }>(),
    {
        modelValue: false,
        codes: () => [],
        loading: false,
    },
);

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "purchase"): void;
}>();

const { copy } = useCopy();

const showPopup = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const unusedCodeCount = computed(() => props.codes.filter((item) => item.status === "unused").length);

// 永久卡使用暖色徽标，其余套餐使用蓝色徽标
const isPermanentCode = (item: ActivationCodeItem) => /永久|终身/.test(`${item.planName}${item.daysLabel}`);

const handlePurchase = () => {
    showPopup.value = false;
    emit("purchase");
};
</script>
