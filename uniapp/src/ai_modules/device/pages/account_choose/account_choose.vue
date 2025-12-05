<template>
    <view class="bg-white h-screen flex flex-col">
        <view class="flex-shrink-0 h-[120rpx] flex items-center px-4">
            <scroll-view scroll-x>
                <view class="flex gap-x-4">
                    <view
                        v-for="item in tabs"
                        :key="item.value"
                        class="px-[24rpx] py-[12rpx] rounded-[50rpx] text-[#959FAF] font-bold whitespace-nowrap"
                        :class="{
                            'bg-primary text-white': item.value === activeTab,
                        }"
                        @click="handleTab(item)">
                        {{ item.label }}
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-4 p-4">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-[#F8F8FA] rounded-[16rpx] h-[144rpx] flex items-center justify-between gap-x-4 px-4"
                        :class="{ 'shadow-[0_0_0_1rpx_var(--color-primary)]': isChoose(item) }"
                        @click="handleChooseAccount(item)">
                        <view class="flex-1 flex items-center gap-x-4">
                            <view class="w-[88rpx] h-[88rpx] relative flex-shrink-0">
                                <image :src="item.avatar" class="w-full h-full rounded-full" />
                                <image
                                    :src="getIcon(item.type)"
                                    class="absolute bottom-0 right-0 w-[32rpx] h-[32rpx]"></image>
                            </view>
                            <view>
                                <view class="flex items-center gap-x-2">
                                    <view class="font-bold line-clamp-1">{{ item.nickname }}</view>
                                </view>
                                <view class="flex items-center gap-x-2 mt-[12rpx]">
                                    <image src="/static/images/icons/device.svg" class="w-[32rpx] h-[32rpx]"></image>
                                    <view class="text-[#00000080] text-xs">{{ item.device_name }}</view>
                                </view>
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
import { getPublishAccountList } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";

const platformTypes = ref<any[]>([]);

const { platformLogo } = useDevice();

const tabs = ref<any[]>([
    { value: 0, label: "全部" },
    { value: AppTypeEnum.SPH, label: "微信", icon: platformLogo[AppTypeEnum.SPH].activeIcon },
    { value: AppTypeEnum.XHS, label: "小红书", icon: platformLogo[AppTypeEnum.XHS].activeIcon },
    { value: AppTypeEnum.DOUYIN, label: "抖音", icon: platformLogo[AppTypeEnum.DOUYIN].activeIcon },
    { value: 5, label: "快手", icon: platformLogo[AppTypeEnum.KUAISHOU].activeIcon },
]);

const getTabs = () => {
    if (platformTypes.value.length === 0) {
        return tabs.value;
    }
    tabs.value = tabs.value.filter((item) => platformTypes.value.includes(item.value as AppTypeEnum));
    return tabs.value;
};

const activeTab = ref();

// 这里需要根据平台类型来判断显示的tab

const dataLists = ref<any[]>([]);
const pagingRef = shallowRef();

const chooseAccount = ref<any[]>([]);

const handleTab = (item: any) => {
    activeTab.value = item.value;
    pagingRef.value?.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublishAccountList({
            page_no,
            page_size,
            type: activeTab.value === 0 ? "" : activeTab.value,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const getIcon = (type: string) => {
    return tabs.value.find((item) => item.value === parseInt(type))?.icon;
};

// 判断是否选中
const isChoose = (item: any) => {
    return chooseAccount.value.some((account) => account.id === item.id);
};

const handleChooseAccount = (item: any) => {
    if (isChoose(item)) {
        chooseAccount.value = chooseAccount.value.filter((account) => account.id !== item.id);
    } else {
        chooseAccount.value.push(item);
    }
};

const handleConfirmChoose = () => {
    if (chooseAccount.value.length === 0) return;
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_ACCOUNT,
        data: chooseAccount.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.account) {
        chooseAccount.value = JSON.parse(options.account);
    }
    if (options.platformTypes) {
        platformTypes.value = JSON.parse(options.platformTypes);
    }
});

onMounted(() => {
    activeTab.value = getTabs()[0]?.value || 0;
    pagingRef.value?.reload();
});
</script>

<style scoped></style>
