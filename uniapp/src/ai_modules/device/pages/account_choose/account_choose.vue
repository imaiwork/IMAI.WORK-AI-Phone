<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="flex-shrink-0 bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]">
            <view class="flex items-center justify-between px-[28rpx] h-[96rpx]">
                <view class="flex items-center gap-[10rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[32rpx] font-extrabold text-[#0D1117]">选择发布账号</text>
                </view>
                <view
                    class="flex items-center gap-[6rpx] h-[52rpx] px-[20rpx] rounded-full transition-all"
                    :class="chooseAccount.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                    <text
                        class="text-xs font-bold"
                        :class="chooseAccount.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ chooseAccount.length }}
                    </text>
                    <text
                        class="text-[22rpx]"
                        :class="chooseAccount.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                        已选
                    </text>
                </view>
            </view>

            <view class="px-[28rpx] pb-[16rpx]">
                <scroll-view scroll-x :show-scrollbar="false">
                    <view class="flex gap-[12rpx] whitespace-nowrap">
                        <view
                            v-for="item in tabs"
                            :key="item.value"
                            class="flex items-center gap-[8rpx] h-[64rpx] px-[24rpx] rounded-full transition-all flex-shrink-0"
                            :class="
                                item.value === activeTab
                                    ? 'bg-primary shadow-[0_4rpx_12rpx_rgba(0,101,251,0.30)]'
                                    : 'bg-[#F0F2F5]'
                            "
                            @click="handleTab(item)">
                            <image v-if="item.icon" :src="item.icon" class="w-[32rpx] h-[32rpx] flex-shrink-0" />
                            <text
                                class="font-semibold"
                                :class="item.value === activeTab ? 'text-white' : 'text-[#6B7280]'">
                                {{ item.label }}
                            </text>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[24rpx] pt-[16rpx] flex flex-col gap-[12rpx]">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="flex items-center gap-[20rpx] rounded-[24rpx] px-[24rpx] h-[144rpx] transition-all"
                        :class="
                            isChoose(item)
                                ? 'bg-[#EBF2FF] shadow-[0_0_0_2rpx_rgba(0,101,251,0.4)]'
                                : 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.05)]'
                        "
                        @click="handleChooseAccount(item)">
                        <view
                            class="flex-shrink-0 w-[6rpx] h-[72rpx] rounded-full transition-all"
                            :class="isChoose(item) ? 'bg-primary' : 'bg-[transparent]'" />

                        <view class="w-[88rpx] h-[88rpx] relative flex-shrink-0">
                            <image
                                :src="item.avatar"
                                class="w-full h-full rounded-full"
                                :class="isChoose(item) ? 'shadow-[0_0_0_3rpx_rgba(0,101,251,0.3)]' : ''" />
                            <image :src="getIcon(item.type)" class="absolute bottom-0 right-0 w-[32rpx] h-[32rpx]" />
                        </view>

                        <view class="flex-1 min-w-0">
                            <text
                                class="text-[28rpx] font-semibold line-clamp-1 block"
                                :class="isChoose(item) ? 'text-[#0D1117]' : 'text-[#0D1117]'">
                                {{ item.nickname }}
                            </text>
                            <view class="flex items-center gap-[8rpx] mt-[8rpx]">
                                <image
                                    src="/static/images/icons/device.svg"
                                    class="w-[28rpx] h-[28rpx] flex-shrink-0" />
                                <text class="text-[22rpx] text-[#9CA3AF] line-clamp-1">{{ item.device_name }}</text>
                            </view>
                        </view>

                        <view
                            class="flex-shrink-0 w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center transition-all"
                            :class="
                                isChoose(item)
                                    ? 'bg-primary shadow-[0_4rpx_10rpx_rgba(0,101,251,0.35)]'
                                    : 'border-2 border-solid border-[#D1D5DB] bg-white'
                            ">
                            <u-icon v-if="isChoose(item)" name="checkmark" color="#ffffff" size="22" />
                        </view>
                    </view>
                </view>

                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-[24rpx] pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx] shadow-[0_-2rpx_12rpx_rgba(0,0,0,0.06)]">
            <view
                class="flex items-center gap-[10rpx] h-[96rpx] px-[28rpx] rounded-[24rpx] border border-solid transition-all flex-shrink-0"
                :class="selectAll ? 'bg-[#EBF2FF] border-[#0065fb]/30' : 'bg-[#F7F9FC] border-[#E5E9F0]'"
                @click="toggleSelectAll">
                <view
                    class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-all"
                    :class="
                        selectAll
                            ? 'bg-primary shadow-[0_2rpx_8rpx_rgba(0,101,251,0.30)]'
                            : 'border-2 border-solid border-[#D1D5DB] bg-white'
                    ">
                    <u-icon v-if="selectAll" name="checkmark" color="#fff" size="18" />
                </view>
                <text class="font-semibold" :class="selectAll ? 'text-primary' : 'text-[#6B7280]'">
                    {{ selectAll ? "取消全选" : "全选" }}
                </text>
            </view>

            <view
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleConfirmChoose">
                <u-icon name="checkmark" color="#fff" size="22" />
                <text class="text-[30rpx] font-extrabold text-white">确认选择</text>
                <text v-if="chooseAccount.length > 0" class="text-xs text-[#ffffff]/70 font-medium">
                    ({{ chooseAccount.length }})
                </text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPublishAccountList } from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const platformTypes = ref<any[]>([]);
const multiple = ref(1);
const { platform } = useDevice();

const tabs = ref<any[]>([
    { value: 0, label: "全部" },
    { value: AppTypeEnum.SPH, label: "微信", icon: platform.value[AppTypeEnum.SPH].activeIcon },
    { value: AppTypeEnum.XHS, label: "小红书", icon: platform.value[AppTypeEnum.XHS].activeIcon },
    {
        value: AppTypeEnum.DOUYIN,
        label: "抖音",
        icon: platform.value[AppTypeEnum.DOUYIN].activeIcon,
    },
    { value: 5, label: "快手", icon: platform.value[AppTypeEnum.KUAISHOU].activeIcon },
]);

const getTabs = () => {
    if (platformTypes.value.length === 0) return tabs.value;
    tabs.value = tabs.value.filter((item) => platformTypes.value.includes(item.value as AppTypeEnum));
    return tabs.value;
};

const activeTab = ref();
const dataLists = ref<any[]>([]);
const pagingRef = shallowRef();
const chooseAccount = ref<any[]>([]);
const selectAll = ref(false);

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

const getIcon = (type: string) => tabs.value.find((item) => item.value === parseInt(type))?.icon;

const isChoose = (item: any) => chooseAccount.value.some((account) => account.id === item.id);

const handleChooseAccount = (item: any) => {
    if (multiple.value === 0) {
        chooseAccount.value = [item];
        return;
    }
    if (isChoose(item)) {
        chooseAccount.value = chooseAccount.value.filter((account) => account.id !== item.id);
    } else {
        chooseAccount.value.push(item);
    }
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        chooseAccount.value = [];
    } else {
        chooseAccount.value = [...dataLists.value];
    }
    selectAll.value = !selectAll.value;
};

const handleConfirmChoose = () => {
    emit("confirm", { type: ListenerTypeEnum.CHOOSE_ACCOUNT, data: chooseAccount.value });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.accounts) chooseAccount.value = JSON.parse(options.accounts);
    if (options.platformTypes) platformTypes.value = JSON.parse(options.platformTypes);
    if (options.multiple) multiple.value = parseInt(options.multiple);
});

onMounted(() => {
    activeTab.value = getTabs()[0]?.value || 0;
    pagingRef.value?.reload();
});
</script>

<style scoped></style>
