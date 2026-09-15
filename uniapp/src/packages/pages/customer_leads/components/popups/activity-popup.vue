<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-white"
        :clearable="false"
        @input="emit('update:modelValue', $event)"
    >
        <template #header>
            <popup-header
                :customer="customer"
                :title="activityType === 'chat' ? '私信聊天记录' : '朋友圈互动记录'"
                :subtitle="activityType === 'chat' ? '平台与私域沟通明细' : '点赞、评论'"
                @close="emit('update:modelValue', false)"
            />
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[32rpx] pt-[32rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))]">
                    <view v-if="loading" class="py-[96rpx] flex flex-col items-center text-primary">
                        <u-loading mode="circle" size="36" color="currentColor"></u-loading>
                        <text class="text-[26rpx] text-[#617087] mt-[20rpx]">正在加载记录…</text>
                    </view>

                    <!-- 私信聊天 -->
                    <template v-else-if="activityType === 'chat'">
                        <view
                            v-if="customer?.chatLog.length || customer?.chatScreenshots.length"
                            class="flex flex-col gap-[28rpx]"
                        >
                            <view
                                v-for="(item, index) in customer.chatLog"
                                :key="index"
                                class="flex gap-[16rpx]"
                                :class="item.from === 'agent' ? 'flex-row-reverse' : ''"
                            >
                                <view
                                    class="relative w-[60rpx] h-[60rpx] rounded-full flex items-center justify-center shrink-0 overflow-hidden"
                                    :class="
                                        item.from === 'agent'
                                            ? 'bg-primary text-white'
                                            : 'chat-avatar-customer text-primary'
                                    "
                                >
                                    <image
                                        v-if="item.from !== 'agent' && customer.avatar"
                                        :src="customer.avatar"
                                        class="relative w-full h-full"
                                        mode="aspectFill"
                                    ></image>
                                    <text v-else class="relative text-[22rpx] font-semibold">
                                        {{
                                            item.from === 'agent' ? 'AI' : customer.name.slice(0, 1)
                                        }}
                                    </text>
                                </view>
                                <view class="min-w-0 max-w-[76%]">
                                    <view
                                        class="relative overflow-hidden px-[24rpx] py-[20rpx] rounded-[24rpx]"
                                        :class="
                                            item.from === 'agent'
                                                ? 'chat-bubble-agent rounded-tr-[6rpx]'
                                                : 'bg-[#F1F4F8] rounded-tl-[6rpx]'
                                        "
                                    >
                                        <text
                                            class="relative text-[26rpx] text-[#1E293B] leading-[1.8] break-all whitespace-pre-wrap"
                                        >
                                            {{ item.text }}
                                        </text>
                                    </view>
                                    <text
                                        class="text-[24rpx] text-[#617087] mt-[8rpx] leading-relaxed block text-center"
                                    >
                                        {{ item.time }}
                                    </text>
                                </view>
                            </view>

                            <view
                                v-if="customer.chatScreenshots.length"
                                class="grid grid-cols-3 gap-[14rpx] pt-[12rpx]"
                            >
                                <image
                                    v-for="item in customer.chatScreenshots"
                                    :key="item"
                                    :src="item"
                                    class="w-full h-[180rpx] rounded-[18rpx] bg-[#F4F6FA]"
                                    mode="aspectFill"
                                ></image>
                            </view>
                        </view>
                        <leads-empty
                            v-else
                            text="暂无聊天记录"
                            description="有聊天内容后，可以在这里查看。"
                        />
                    </template>

                    <!-- 朋友圈互动 -->
                    <template v-else>
                        <view v-if="customer?.momentsLog.length" class="flex flex-col">
                            <view
                                v-for="(item, index) in customer.momentsLog"
                                :key="index"
                                class="moment-record"
                            >
                                <text
                                    class="text-[24rpx] text-[#617087] mb-[12rpx] leading-relaxed block"
                                >
                                    {{ item.time }}
                                </text>
                                <text
                                    class="text-[26rpx] text-[#1E293B] leading-relaxed break-all whitespace-pre-wrap block"
                                >
                                    {{ item.content }}
                                </text>
                                <image
                                    v-if="item.image"
                                    :src="item.image"
                                    class="w-full h-[320rpx] rounded-[20rpx] bg-[#F4F6FA] mt-[18rpx]"
                                    mode="aspectFit"
                                ></image>
                                <view class="flex flex-wrap gap-[10rpx] mt-[18rpx]">
                                    <view
                                        v-if="item.liked"
                                        class="flex items-center gap-[8rpx] text-primary"
                                    >
                                        <u-icon
                                            name="thumb-up"
                                            color="currentColor"
                                            size="24"
                                        ></u-icon>
                                        <text class="text-[24rpx] font-medium">已点赞</text>
                                    </view>
                                    <text
                                        v-if="item.comment"
                                        class="w-full text-[26rpx] text-[#526176] leading-relaxed break-all"
                                    >
                                        评论：{{ item.comment }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <leads-empty
                            v-else
                            text="暂无朋友圈互动"
                            description="有点赞或评论记录后，会显示在这里。"
                        />
                    </template>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import PopupHeader from '../popup-header.vue'
import LeadsEmpty from '../leads-empty.vue'
import type { ActivityType, Customer } from '../types'

const props = defineProps<{
    modelValue: boolean
    customer: Customer | null
    activityType: ActivityType
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
.chat-bubble-agent::before,
.chat-avatar-customer::before {
    @apply absolute inset-0 bg-primary opacity-[0.07];

    content: '';
}

.moment-record {
    @apply pt-[8rpx] pb-[28rpx] mb-[28rpx] border-[0] border-b-[2rpx] border-solid border-[#E7ECF2];
}

.moment-record:last-child {
    @apply mb-0 border-b-[0];
}
</style>
