<template>
    <popup-bottom
        v-model="show"
        custom-class="bg-white"
        :is-disabled-touch="true"
        :clearable="false"
        height="80%">
        <template #header>
            <view class="px-[40rpx] pt-3 pb-[24rpx] border-b border-solid border-[#F3F4F6]">
                <view class="w-[66rpx] h-[8rpx] rounded-full bg-[#E5E7EB] mx-auto mb-3"></view>
                <view class="flex items-center justify-between">
                    <view>
                        <view class="text-[32rpx] font-bold text-[#1F2937]">挂载</view>
                        <view class="text-xs text-[#9CA3AF] mt-[4rpx]">为本次对话挂载设备</view>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#666666" :size="20"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex-1 min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="deviceList"
                        :fixed="false"
                        :safe-area-inset-bottom="false"
                        @query="queryList">
                        <view class="px-[24rpx] py-2 flex flex-col gap-y-[12rpx]">
                            <view
                                v-for="(item, index) in deviceList"
                                :key="index"
                                class="check-row"
                                :class="{ 'check-row--selected': isChoose(item) }"
                                @click="toggleDevice(item)">
                                <view
                                    class="w-[76rpx] h-[76rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0"
                                    :class="item.status === 1 ? 'bg-[#EFF6FF]' : 'bg-[#F3F4F6]'">
                                    <image
                                        :src="
                                            item.status === 1
                                                ? '/static/images/icons/device_primary.svg'
                                                : '/static/images/icons/device_gray.svg'
                                        "
                                        class="w-[36rpx] h-[36rpx]" />
                                </view>
                                <view class="flex-1 min-w-0">
                                    <view class="flex items-center gap-x-[10rpx]">
                                        <text class="text-[28rpx] font-semibold text-[#1F2937] truncate">
                                            {{ item.device_name || item.device_model }}
                                        </text>
                                        <text
                                            class="flex-shrink-0 text-[20rpx] px-[12rpx] py-[2rpx] rounded-[6rpx]"
                                            :class="
                                                item.status === 1
                                                    ? 'bg-[#DCFCE7] text-[#16A34A]'
                                                    : 'bg-[#F3F4F6] text-[#9CA3AF]'
                                            ">
                                            {{ [1,2].includes(item.status) ? "在线" : "离线" }}
                                        </text>
                                    </view>
                                    <text class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] block truncate">
                                        {{ item.sdk_version }} · {{ item.auto_type === 1 ? "24H" : "手动" }}
                                    </text>
                                </view>
                                <view
                                    class="w-[40rpx] h-[40rpx] rounded-full border-[3rpx] border-solid flex items-center justify-center flex-shrink-0 transition-colors"
                                    :class="isChoose(item) ? 'border-primary bg-primary' : 'border-[#D1D5DB] bg-[transparent]'">
                                    <u-icon v-if="isChoose(item)" name="checkmark" color="#ffffff" :size="22"></u-icon>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <view class="flex flex-col items-center justify-center pt-[120rpx] px-[60rpx]">
                                <view
                                    class="w-[148rpx] h-[148rpx] rounded-full bg-[#EFF6FF] flex items-center justify-center mb-[28rpx]">
                                    <image src="/static/images/icons/device_primary.svg" class="w-[68rpx] h-[68rpx]" />
                                </view>
                                <text class="text-[30rpx] font-semibold text-[#1F2937]">还没有绑定设备</text>
                                <text class="text-[24rpx] text-[#9CA3AF] mt-[12rpx] text-center leading-relaxed">
                                    绑定设备后即可挂载到本次对话
                                </text>
                                <view
                                    class="mt-[36rpx] h-[80rpx] px-[48rpx] rounded-full bg-primary flex items-center justify-center active:opacity-90"
                                    @click="goBindDevice">
                                    <u-icon name="plus" color="#ffffff" :size="26"></u-icon>
                                    <text class="text-white text-[28rpx] font-semibold ml-[8rpx]">去绑定设备</text>
                                </view>
                            </view>
                        </template>
                    </z-paging>
                </view>
                <view
                    class="flex-shrink-0 px-[32rpx] pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] border-t border-solid border-[#F3F4F6]">
                    <view
                        class="w-full h-[88rpx] bg-primary rounded-full flex items-center justify-center active:opacity-90"
                        @click="confirm">
                        <text class="text-white font-bold text-[28rpx]">
                            确定{{ tempSelected.length ? `(${tempSelected.length})` : "" }}
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script lang="ts" setup>
import { getDeviceList } from "@/api/device";

const props = defineProps<{
    modelValue: boolean;
    mountedIds?: (number | string)[];
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "confirm", devices: any[]): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const deviceList = ref<any[]>([]);
const pagingRef = ref<any>(null);
const tempSelected = ref<(number | string)[]>([]);

watch(
    () => props.modelValue,
    (v) => {
        if (v) tempSelected.value = [...(props.mountedIds || [])];
    },
);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceList({ page_no, page_size });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete(false);
    }
};

const isChoose = (item: any) => tempSelected.value.includes(item.id);

const toggleDevice = (item: any) => {
    const index = tempSelected.value.indexOf(item.id);
    if (index > -1) {
        tempSelected.value.splice(index, 1);
    } else {
        tempSelected.value.push(item.id);
    }
};

const goBindDevice = () => {
    emit("update:modelValue", false);
    uni.navigateTo({ url: "/ai_modules/device/pages/rpa_code/rpa_code" });
};

const confirm = () => {
    const devices = tempSelected.value
        .map((id) => deviceList.value.find((d) => d.id === id))
        .filter(Boolean);
    emit("confirm", devices);
    emit("update:modelValue", false);
};
</script>

<style lang="scss" scoped>
.check-row {
    @apply flex items-center gap-x-3 px-[24rpx] py-[20rpx] rounded-[20rpx] border border-solid border-[transparent] bg-[#F9FAFB];
}
.check-row--selected {
    @apply bg-[#EFF6FF] border-primary;
}
</style>
