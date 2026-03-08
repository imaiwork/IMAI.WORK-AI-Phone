<template>
    <popup-bottom v-model="show" title="获取社媒账号" :mask-close-able="false">
        <template #content>
            <view class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="py-[50rpx] flex flex-col gap-y-6 px-4">
                        <view v-for="(item, index) in sortedPlatform" :key="index">
                            <view class="flex items-center gap-x-2">
                                <image :src="item.activeIcon" class="w-[28rpx] h-[28rpx]"></image>
                                <text class="font-medium">{{ item.name }}账号</text>
                            </view>
                            <view class="bg-[#F6F6F6] p-[44rpx] flex rounded-[20rpx] mt-[18rpx]">
                                <view
                                    class="flex items-center justify-between w-full"
                                    v-if="item.active || item.status == 2">
                                    <view class="flex items-center gap-x-2">
                                        <image :src="item.avatar" class="w-[80rpx] h-[80rpx] rounded-full"></image>
                                        <view>
                                            <view class="text-[30rpx] font-medium break-all line-clamp-1">
                                                {{ item.nickname }}
                                            </view>
                                            <view
                                                class="text-xs text-[#0000004d] font-medium break-all line-clamp-1 mt-[6rpx]">
                                                {{ item.account }}
                                            </view>
                                        </view>
                                    </view>
                                    <view class="flex items-center gap-x-1">
                                        <image
                                            src="@/ai_modules/device/static/icons/success2.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="text-[#00C08E] font-medium">已完成</text>
                                    </view>
                                </view>
                                <view class="flex gap-x-4" v-else>
                                    <image
                                        :src="item.icon"
                                        class="w-[60rpx] h-[60rpx] rounded-full flex-shrink-0"></image>
                                    <text class="text-primary font-medium mt-[10rpx]" v-if="item.status == 1"
                                        >获取中，请等待...</text
                                    >
                                    <view v-else-if="item.status == 3 || item.status == 0">
                                        <text class="text-[#FF2442] font-medium" v-if="item.status == 3"
                                            >获取失败：{{ item.error }}</text
                                        >
                                        <text class="text-[#0000004d] font-medium mt-[10rpx]" v-else>等待获取</text>
                                        <view
                                            class="w-[150rpx] h-[64rpx] bg-primary text-white rounded-[10rpx] flex items-center justify-center mt-[18rpx]"
                                            @click="emit('get-account')">
                                            重新获取</view
                                        >
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: boolean;
    sortedPlatform: any[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", show: boolean): void;
    (e: "get-account"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});
</script>

<style scoped></style>
