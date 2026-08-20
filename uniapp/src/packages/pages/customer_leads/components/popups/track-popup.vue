<template>
    <popup-bottom
        v-model="show"
        height="78%"
        custom-class="bg-white"
        :clearable="false"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <popup-header
                :customer="customer"
                title="跟进记录"
                subtitle="客户跟进全流程"
                @close="emit('update:modelValue', false)" />
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[40rpx] py-[28rpx] pb-[64rpx]">
                    <view v-if="loading" class="py-[120rpx] flex flex-col items-center">
                        <u-loading mode="circle" size="40" color="#2845F5"></u-loading>
                        <text class="text-xs text-[#9CA3AF] mt-[18rpx]">加载跟进记录中...</text>
                    </view>
                    <template v-else-if="customer?.track.length">
                        <view v-for="(item, index) in customer.track" :key="index" class="flex gap-[22rpx]">
                            <view class="flex flex-col items-center shrink-0">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                    :class="
                                        item.done
                                            ? 'bg-[#2845F5]'
                                            : item.active
                                            ? 'bg-[#EEF2FF] border border-solid border-[#2845F5]'
                                            : 'bg-[#F3F4F6]'
                                    ">
                                    <u-icon
                                        :name="item.done ? 'checkmark' : 'clock'"
                                        :color="item.done ? '#FFFFFF' : item.active ? '#2845F5' : '#9CA3AF'"
                                        size="26"></u-icon>
                                </view>
                                <view
                                    v-if="index < customer.track.length - 1"
                                    class="w-[2rpx] min-h-[72rpx] flex-1 my-[8rpx]"
                                    :class="item.done ? 'bg-[#2845F5]' : 'bg-[#E5E7EB]'"></view>
                            </view>
                            <view class="flex-1 min-w-0 pb-[34rpx]">
                                <view class="flex items-center gap-[12rpx] mb-[8rpx]">
                                    <text
                                        class="text-sm font-bold"
                                        :class="item.done || item.active ? 'text-[#111827]' : 'text-[#9CA3AF]'">
                                        {{ item.stage }}
                                    </text>
                                    <text v-if="item.time" class="text-[20rpx] text-[#9CA3AF]">
                                        {{ item.time }}
                                    </text>
                                </view>
                                <text
                                    class="text-xs leading-relaxed"
                                    :class="item.done || item.active ? 'text-[#4B5563]' : 'text-[#9CA3AF]'">
                                    {{ item.desc }}
                                </text>
                                <view
                                    v-if="item.chat?.length"
                                    class="mt-[16rpx] bg-[#F8FAFF] rounded-[18rpx] p-[18rpx]">
                                    <text class="text-[20rpx] font-semibold text-[#9CA3AF] mb-[8rpx] block">
                                        聊天记录
                                    </text>
                                    <text
                                        v-for="chat in item.chat"
                                        :key="chat"
                                        class="text-[22rpx] text-[#4B5563] leading-relaxed block">
                                        {{ chat }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>
                    <empty v-else text="暂无跟进记录" />
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import PopupHeader from "../popup-header.vue";
import type { Customer } from "../types";

const props = defineProps<{
    modelValue: boolean;
    customer: Customer | null;
    loading: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
