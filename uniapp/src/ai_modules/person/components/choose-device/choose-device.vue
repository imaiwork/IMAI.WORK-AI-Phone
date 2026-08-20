<template>
    <popup-bottom v-model="show" title="选择设备" custom-class="bg-[#F4F7FA]" :is-disabled-touch="true" @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="flex items-center justify-between px-[30rpx] mt-3 mb-1" v-if="limit > 1">
                    <text class="text-[#666666] font-medium">请选择设备</text>
                    <view class="flex items-center gap-[10rpx]">
                        <text class="text-xs text-[#00000080]">
                            已选：<text
                                class="font-semibold"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-primary'"
                                >{{ chooseLists.length }}</text
                            >
                        </text>
                        <view
                            class="flex items-center gap-[4rpx] px-[12rpx] h-[36rpx] rounded-full"
                            :class="chooseLists.length >= limit ? 'bg-[#FEF2F2]' : 'bg-[#F0F2F5]'">
                            <u-icon
                                :name="chooseLists.length >= limit ? 'info-circle-fill' : 'info-circle'"
                                :color="chooseLists.length >= limit ? '#EF4444' : '#9CA3AF'"
                                size="18" />
                            <text
                                class="text-[20rpx] font-medium"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-[#9CA3AF]'">
                                最多 {{ limit }} 个
                            </text>
                        </view>
                    </view>
                </view>

                <view class="flex-1 min-h-0 relative">
                    <z-paging
                        ref="pagingRef"
                        v-model="deviceList"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="px-[30rpx] py-4 flex flex-col gap-y-3.5">
                            <view
                                class="rounded-[24rpx] p-4 bg-white border-[2rpx] border-dashed border-[#0065fb]/40 flex items-center gap-x-3 active:scale-[0.98] transition-all duration-300"
                                @click="goBindDevice">
                                <view
                                    class="w-[88rpx] h-[88rpx] rounded-[20rpx] flex-shrink-0 flex items-center justify-center bg-[#EEF4FF]">
                                    <u-icon name="plus" color="#0065fb" size="40"></u-icon>
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text class="font-bold text-[30rpx] text-primary block mb-1">绑定新设备</text>
                                    <text class="text-xs text-[#999999]">扫码或输入设备码快速绑定</text>
                                </view>
                                <u-icon name="arrow-right" color="#0065fb" size="28"></u-icon>
                            </view>

                            <view
                                v-for="(item, index) in deviceList"
                                :key="index"
                                class="rounded-[24rpx] p-4 flex flex-col gap-y-3 relative transition-all duration-300 border-[2rpx] border-solid"
                                :class="
                                    isDisabled(item)
                                        ? 'bg-[#F5F5F5] border-[transparent] shadow-none opacity-60'
                                        : isChoose(item)
                                        ? 'bg-[#F5F9FF] border-primary shadow-[0_8rpx_20rpx_rgba(0,101,251,0.12)] active:scale-[0.98]'
                                        : 'bg-white border-[transparent] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.06)] active:scale-[0.98]'
                                "
                                @click="handleSelect(item)">
                                <view class="flex items-center gap-x-3">
                                    <view
                                        class="w-[88rpx] h-[88rpx] rounded-[20rpx] flex-shrink-0 flex items-center justify-center"
                                        :class="
                                            isDisabled(item)
                                                ? 'bg-[#EFEFEF]'
                                                : item.status === 1
                                                ? 'bg-[#E8F5E9]'
                                                : 'bg-[#F3F4F6]'
                                        ">
                                        <image src="/static/images/icons/device_gray.svg" class="w-[50rpx] h-[50rpx]" />
                                    </view>

                                    <view class="flex-1 min-w-0">
                                        <view class="flex items-center gap-x-2 mb-1">
                                            <text
                                                class="font-extrabold text-[30rpx] truncate"
                                                :class="isDisabled(item) ? 'text-[#BBBBBB]' : 'text-[#1A1A1A]'">
                                                {{ item.device_name || item.device_model }}
                                            </text>
                                            <view
                                                class="flex-shrink-0 px-2 py-[2rpx] rounded-full"
                                                :class="
                                                    isDisabled(item)
                                                        ? 'bg-[#EFEFEF]'
                                                        : item.status === 0
                                                        ? 'bg-[#F3F4F6]'
                                                        : 'bg-[#E8F5E9]'
                                                ">
                                                <text
                                                    class="text-[20rpx]"
                                                    :class="
                                                        isDisabled(item)
                                                            ? 'text-[#CCCCCC]'
                                                            : item.status === 0
                                                            ? 'text-[#9CA3AF]'
                                                            : 'text-[#4CAF50]'
                                                    ">
                                                    {{
                                                        isDisabled(item)
                                                            ? "已使用"
                                                            : item.status === 0
                                                            ? "离线"
                                                            : "在线"
                                                    }}
                                                </text>
                                            </view>
                                        </view>
                                        <text
                                            class="text-xs truncate block"
                                            :class="isDisabled(item) ? 'text-[#CCCCCC]' : 'text-[#999999]'">
                                            {{ item.sdk_version }} ·
                                            {{ item.auto_type === 1 ? "24H" : "手动" }}
                                        </text>
                                    </view>

                                    <view
                                        class="w-[44rpx] h-[44rpx] rounded-full border-[3rpx] flex items-center justify-center transition-colors duration-300 flex-shrink-0"
                                        :class="
                                            isDisabled(item)
                                                ? 'border-[#E5E7EB] bg-[#F0F0F0]'
                                                : isChoose(item)
                                                ? 'border-primary bg-primary'
                                                : 'border-[#E5E7EB] bg-transparent'
                                        ">
                                        <u-icon
                                            v-if="isChoose(item) && !isDisabled(item)"
                                            name="checkmark"
                                            color="#ffffff"
                                            size="24"></u-icon>
                                    </view>
                                </view>

                                <view class="h-[1rpx] bg-[#F0F0F0]"></view>
                                <view v-if="item.accounts?.length" class="flex items-center">
                                    <view class="flex items-center">
                                        <view
                                            v-for="(acc, accIdx) in item.accounts.slice(0, 5)"
                                            :key="accIdx"
                                            class="w-[48rpx] h-[48rpx] rounded-full border-[3rpx] border-white overflow-hidden bg-gray-100"
                                            :style="{
                                                marginLeft: accIdx > 0 ? '-16rpx' : '0',
                                                zIndex: 5 - accIdx,
                                            }">
                                            <image :src="acc.avatar" class="w-full h-full object-cover"></image>
                                        </view>
                                        <view
                                            v-if="item.accounts.length > 5"
                                            class="w-[48rpx] h-[48rpx] rounded-full border-[3rpx] border-white bg-[#E5E7EB] flex items-center justify-center ml-[-16rpx] z-0">
                                            <text class="text-[18rpx] text-[#9CA3AF]"
                                                >+{{ item.accounts.length - 5 }}</text
                                            >
                                        </view>
                                    </view>
                                    <text
                                        class="text-[22rpx] ml-2"
                                        :class="isDisabled(item) ? 'text-[#CCCCCC]' : 'text-[#999999]'"
                                        >已绑定账号</text
                                    >
                                </view>
                            </view>
                        </view>

                        <template #empty>
                            <empty :size="250" />
                        </template>
                    </z-paging>
                </view>

                <view
                    class="bg-[#ffffff]/90 flex-shrink-0 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] px-5 shadow-[0_-8rpx_30rpx_rgba(0,0,0,0.04)] z-50 flex items-center gap-4">
                    <view v-if="limit > 1" class="flex flex-col gap-[6rpx] flex-shrink-0">
                        <view
                            class="flex items-center gap-2 active:opacity-70 transition-opacity py-2"
                            @click="toggleSelect">
                            <view
                                class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-colors"
                                :class="
                                    isCurrentPageAllSelected
                                        ? 'bg-primary'
                                        : 'border-[3rpx] border-[#D1D5DB] bg-[#F9FAFB]'
                                ">
                                <u-icon
                                    v-if="isCurrentPageAllSelected"
                                    name="checkmark"
                                    color="#ffffff"
                                    size="22"></u-icon>
                            </view>
                            <text class="text-[28rpx] text-[#333333] font-medium">全选</text>
                        </view>
                    </view>
                    <view class="flex-1">
                        <u-button
                            type="primary"
                            shape="circle"
                            ripple
                            :custom-style="{
                                fontSize: '28rpx',
                                fontWeight: 'bold',
                                height: '88rpx',
                            }"
                            @click="confirm">
                            确定保存{{ limit > 1 && chooseLists.length > 0 ? `(${chooseLists.length})` : "" }}
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getDeviceList } from "@/api/device";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        deviceId?: number | string;
        limit?: number;
    }>(),
    {
        modelValue: false,
        limit: 1,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", devices: any[]): void;
    (e: "close"): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const deviceList = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);
const disabledLists = ref<any[]>([]);

// 初始化单选时的默认选中
if (props.limit === 1 && props.deviceId) {
    chooseLists.value = [{ id: props.deviceId }];
}

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getDeviceList({ page_no, page_size });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const isChoose = (item: any) => chooseLists.value.some((c) => c.id === item.id);

const isDisabled = (item: any) => disabledLists.value.some((d) => d.id === item.id);

// 全选仅针对可选项（排除已禁用）
const selectableLists = computed(() => deviceList.value.filter((item) => !isDisabled(item)));

const isCurrentPageAllSelected = computed(() => {
    if (selectableLists.value.length === 0) return false;
    const unselected = selectableLists.value.filter((item) => !isChoose(item));
    if (unselected.length === 0) return true;
    const remaining = props.limit - chooseLists.value.length;
    if (remaining <= 0) return true;
    return false;
});

const handleSelect = (item: any) => {
    if (isDisabled(item)) {
        uni.$u.toast("该设备已被使用，无法选择");
        return;
    }
    const index = chooseLists.value.findIndex((c) => c.id === item.id);
    if (index > -1) {
        chooseLists.value.splice(index, 1);
    } else {
        if (props.limit === 1) {
            chooseLists.value = [item];
        } else {
            if (chooseLists.value.length >= props.limit) {
                uni.$u.toast(`最多只能选择 ${props.limit} 个设备`);
            } else {
                chooseLists.value.push(item);
            }
        }
    }
};

const toggleSelect = () => {
    if (isCurrentPageAllSelected.value) {
        const selectableIds = new Set(selectableLists.value.map((i) => i.id));
        chooseLists.value = chooseLists.value.filter((i) => !selectableIds.has(i.id));
    } else {
        for (const item of selectableLists.value) {
            if (chooseLists.value.length >= props.limit) break;
            if (!isChoose(item)) chooseLists.value.push(item);
        }
    }
};

const goBindDevice = () => {
    show.value = false;
    uni.navigateTo({ url: "/ai_modules/device/pages/rpa_code/rpa_code" });
};

const confirm = () => {
    if (chooseLists.value.length === 0 && disabledLists.value.length === 0) {
        uni.$u.toast("请选择设备");
        return;
    }
    const result = chooseLists.value.map((c) => deviceList.value.find((d) => d.id === c.id)).filter(Boolean);
    show.value = false;
    emit("confirm", props.limit > 1 ? result : result[0]);
    chooseLists.value = [];
};

const close = () => {
    emit("close");
    show.value = false;
};

defineExpose({
    setChooseLists: (lists: any[]) => {
        chooseLists.value = JSON.parse(JSON.stringify(lists));
    },
    setDisabledLists: (lists: any[]) => {
        disabledLists.value = JSON.parse(JSON.stringify(lists));
    },
});
</script>
