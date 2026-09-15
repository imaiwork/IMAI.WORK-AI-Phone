<template>
    <popup-bottom
        v-model="show"
        height="78%"
        custom-class="bg-white"
        :clearable="false"
        @input="emit('update:modelValue', $event)"
    >
        <template #header>
            <popup-header
                :customer="customer"
                title="跟进记录"
                subtitle="跟进时间与内容"
                @close="emit('update:modelValue', false)"
            />
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[40rpx] pt-[40rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))]">
                    <view v-if="loading" class="py-[96rpx] flex flex-col items-center text-primary">
                        <u-loading mode="circle" size="36" color="currentColor"></u-loading>
                        <text class="text-[26rpx] text-[#617087] mt-[20rpx]"
                            >正在加载跟进记录…</text
                        >
                    </view>
                    <template v-else-if="customer?.track.length">
                        <view
                            v-for="(item, index) in customer.track"
                            :key="index"
                            class="flex gap-[20rpx]"
                        >
                            <view class="flex flex-col items-center shrink-0">
                                <view
                                    class="relative w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center overflow-hidden"
                                    :class="
                                        item.done
                                            ? 'bg-primary text-white'
                                            : item.active
                                            ? 'track-node-active text-primary border-[2rpx] border-solid border-primary'
                                            : 'bg-[#F1F4F8] text-[#617087]'
                                    "
                                >
                                    <u-icon
                                        class="relative"
                                        :name="item.done ? 'checkmark' : 'clock'"
                                        color="currentColor"
                                        size="22"
                                    ></u-icon>
                                </view>
                                <view
                                    v-if="index < customer.track.length - 1"
                                    class="w-[2rpx] min-h-[64rpx] flex-1 my-[8rpx] bg-[#E1E7EF]"
                                ></view>
                            </view>
                            <view class="flex-1 min-w-0 pb-[40rpx]">
                                <view class="mb-[12rpx]">
                                    <text
                                        class="text-[28rpx] leading-[40rpx] font-semibold block break-all"
                                        :class="
                                            item.done || item.active
                                                ? 'text-[#1E293B]'
                                                : 'text-[#617087]'
                                        "
                                    >
                                        {{ item.stage }}
                                    </text>
                                    <text
                                        v-if="item.time"
                                        class="text-[24rpx] text-[#617087] leading-relaxed mt-[6rpx] block"
                                    >
                                        {{ item.time }}
                                    </text>
                                </view>
                                <text
                                    class="text-[26rpx] text-[#526176] leading-relaxed break-all block"
                                >
                                    {{ item.desc }}
                                </text>
                                <view
                                    v-if="item.chat?.length"
                                    class="mt-[16rpx] bg-[#F5F7FA] rounded-[16rpx] p-[20rpx]"
                                >
                                    <text
                                        class="text-[24rpx] font-medium text-[#617087] mb-[8rpx] block"
                                    >
                                        聊天记录
                                    </text>
                                    <text
                                        v-for="chat in item.chat"
                                        :key="chat"
                                        class="text-[26rpx] text-[#526176] leading-relaxed break-all block"
                                    >
                                        {{ chat }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </template>
                    <leads-empty
                        v-else
                        text="暂无跟进记录"
                        description="有新的跟进记录后，会显示在这里。"
                    />
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import PopupHeader from '../popup-header.vue'
import LeadsEmpty from '../leads-empty.vue'
import type { Customer } from '../types'

const props = defineProps<{
    modelValue: boolean
    customer: Customer | null
    loading: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})
</script>

<style scoped lang="scss">
.track-node-active::before {
    @apply absolute inset-0 bg-primary opacity-[0.08];

    content: '';
}
</style>
