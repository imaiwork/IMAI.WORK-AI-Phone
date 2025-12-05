<template>
    <view class="bg-white h-screen flex flex-col">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-4 p-4">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-[#F8F9FD] rounded-[16rpx] h-[144rpx] flex justify-between items-center gap-x-4 px-4"
                        :class="{ 'shadow-[0_0_0_1rpx_var(--color-primary)]': isChoose(item) }"
                        @click="handleChoose(item)">
                        <view>
                            <view class="flex items-center gap-x-2">
                                <view class="font-bold line-clamp-1">{{ item.device_name || "-" }}</view>
                                <view
                                    class="px-[16rpx] py-[8rpx] rounded-[8rpx] text-[20rpx] font-bold"
                                    :class="[
                                        item.status === 0
                                            ? 'text-[#F63E2F] bg-[#F7E5E5]'
                                            : 'text-[#00B862] bg-[#E0F1EB]',
                                    ]"
                                    >{{ item.status === 1 ? "在线" : "离线" }}</view
                                >
                            </view>
                            <view class="flex items-center gap-x-1 mt-[12rpx]">
                                <image src="/static/images/icons/device.svg" class="w-[32rpx] h-[32rpx]"></image>
                                <view class="text-[#00000080] text-xs">{{ item.device_code }}</view>
                            </view>
                        </view>
                        <view
                            class="w-5 h-5 rounded-full flex-shrink-0"
                            :class="[
                                isChoose(item)
                                    ? 'bg-primary flex items-center justify-center'
                                    : 'border border-solid border-[#00000033]',
                            ]">
                            <u-icon name="checkmark" color="#ffffff" size="20" v-if="isChoose(item)"></u-icon>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="flex-shrink-0 pb-5 px-4 pt-2">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '20rpx', fontWeight: 'bold' }"
                @click="handleConfirmChoose">
                确认选择
            </u-button>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getDeviceList } from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";

const dataLists = ref<any[]>([]);
const pagingRef = shallowRef();

const chooseDevice = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceList({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

// 判断是否选中
const isChoose = (item: any) => {
    return chooseDevice.value.some((deviceCode) => deviceCode == item.device_code);
};

const handleChoose = (item: any) => {
    if (isChoose(item)) {
        chooseDevice.value = chooseDevice.value.filter((deviceCode) => deviceCode !== item.device_code);
    } else {
        chooseDevice.value.push(item.device_code);
    }
};

const handleConfirmChoose = () => {
    if (chooseDevice.value.length === 0) {
        uni.$u.toast("至少选择一台手机设备");
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_DEVICE,
        data: chooseDevice.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.device) {
        chooseDevice.value = JSON.parse(options.device);
    }
});
</script>

<style scoped></style>
