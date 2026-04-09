<template>
    <popup-bottom
        v-model="show"
        title="请选择人设"
        custom-class="bg-[#F7F9FC]"
        :is-disabled-touch="true"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col bg-[#F7F9FC]">
                <view class="flex items-center justify-between px-[30rpx] mt-3 mb-1" v-if="limit > 1">
                    <text class="text-[26rpx] text-[#666666] font-medium">请选择人设</text>
                    <view class="text-[24rpx] text-[#999999]">
                        已选 <text class="text-primary font-bold mx-0.5">{{ chooseLists.length }}</text> / {{ limit }}
                    </view>
                </view>

                <view class="grow min-h-0 mt-[10rpx]">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[20rpx] px-[30rpx] grid grid-cols-3 gap-x-3 gap-y-4">
                            <view
                                class="relative inline-flex flex-col items-center justify-center w-full h-[260rpx] rounded-[32rpx] border-2 border-dashed border-[#D2DCE6] bg-[#F0F4F8] transition-all duration-300 active:bg-[#E2E8F0]"
                                @click="toCreate()">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-full bg-white shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)] flex items-center justify-center mb-2">
                                    <u-icon name="plus" color="#0065fb" size="32"></u-icon>
                                </view>
                                <text class="text-[24rpx] text-[#666666] font-medium">去创建</text>
                            </view>

                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="relative inline-flex flex-col items-center justify-center w-full h-[260rpx] rounded-[32rpx] border-[3rpx] transition-all duration-300 overflow-hidden bg-white border-transparent shadow-[0_4rpx_16rpx_rgba(0,0,0,0.04)]"
                                :class="[
                                    !item.is_configured ? 'opacity-70' : '',
                                    isDisabled(item) ? 'cursor-not-allowed' : '',
                                ]"
                                @click.stop="handleSelect(item)">
                                <view
                                    v-if="isChoose(item)"
                                    class="absolute inset-0 bg-[#0065fb]/10 border-[3rpx] border-primary rounded-[32rpx] box-border pointer-events-none z-10 transition-all">
                                </view>

                                <view
                                    class="absolute top-2 right-2 w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center z-20 transition-all duration-200"
                                    :class="
                                        isChoose(item)
                                            ? 'bg-primary shadow-[0_2rpx_8rpx_rgba(0,101,251,0.4)] border border-primary'
                                            : 'bg-[#000000]/20 border-[2rpx] border-[#ffffff]/80'
                                    ">
                                    <image
                                        v-if="isChoose(item)"
                                        src="/static/images/icons/success.svg"
                                        class="w-[24rpx] h-[24rpx]">
                                    </image>
                                </view>

                                <view class="absolute top-[16rpx] left-[16rpx] z-20">
                                    <view
                                        v-if="item.is_configured"
                                        class="flex items-center gap-[4rpx] bg-[#ECFDF5] border border-[#A7F3D0] px-[10rpx] py-[4rpx] rounded-full">
                                        <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#10B981]"></view>
                                        <text class="text-[18rpx] text-[#059669] font-medium">已配置</text>
                                    </view>
                                    <view
                                        v-else
                                        class="flex items-center gap-[4rpx] bg-[#F3F4F6] border border-[#E5E7EB] px-[10rpx] py-[4rpx] rounded-full">
                                        <view class="w-[10rpx] h-[10rpx] rounded-full bg-[#D1D5DB]"></view>
                                        <text class="text-[18rpx] text-[#9CA3AF] font-medium">未配置</text>
                                    </view>
                                </view>

                                <view
                                    class="w-[100rpx] h-[100rpx] rounded-full overflow-hidden border-2 border-solid border-white shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)] mb-3">
                                    <image
                                        v-if="item.avatar_url"
                                        :src="item.avatar_url"
                                        class="w-full h-full"
                                        mode="aspectFill">
                                    </image>
                                    <view v-else class="w-full h-full flex items-center justify-center bg-[#E8EDF2]">
                                        <u-icon name="account" color="#B0BEC5" size="48"></u-icon>
                                    </view>
                                </view>

                                <text
                                    class="text-[28rpx] font-bold text-[#1A1A1A] mb-1.5 w-full text-center truncate px-2">
                                    {{ item.persona_name }}
                                </text>

                                <view
                                    class="px-2.5 py-0.5 rounded-full border border-solid border-[#E5E7EB] bg-white text-[20rpx] text-[#666666]">
                                    {{ PersonTypeMap[item.persona_type as keyof typeof PersonTypeMap] }}
                                </view>

                                <view
                                    v-if="!item.is_configured"
                                    class="absolute bottom-0 left-0 right-0 h-[72rpx] flex items-center justify-center bg-[#F9FAFB] border-t border-[#F0F0F0] rounded-b-[32rpx]"
                                    @click.stop="toDetail(item)">
                                    <u-icon name="edit-pen" color="#9CA3AF" size="22"></u-icon>
                                    <text class="text-[20rpx] text-[#9CA3AF] ml-[6rpx]">
                                        {{ skipUnConfig ? "未配置不可选" : "去完善信息" }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <view
                    class="bg-[#ffffff]/90 px-[30rpx] pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] flex items-center justify-between gap-4 z-50">
                    <view
                        class="flex items-center gap-2 active:opacity-70 transition-opacity py-2"
                        @click="toggleSelect"
                        v-if="limit && limit > 1">
                        <view
                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-colors"
                            :class="
                                chooseLists.length > 0 && chooseLists.length === dataLists.length
                                    ? 'bg-primary'
                                    : 'border-[3rpx] border-[#D1D5DB] bg-[#F9FAFB]'
                            ">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length === dataLists.length"
                                src="/static/images/icons/success.svg"
                                class="w-[24rpx] h-[24rpx]">
                            </image>
                        </view>
                        <text class="text-[28rpx] text-[#333333] font-medium">全选</text>
                    </view>

                    <view class="flex-1">
                        <u-button
                            type="primary"
                            shape="circle"
                            ripple
                            :custom-style="{ fontSize: '28rpx', fontWeight: 'bold', height: '88rpx' }"
                            @click="confirm">
                            确定选择 {{ limit > 1 && chooseLists.length > 0 ? `(${chooseLists.length})` : "" }}
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getPersonList } from "@/api/person";
import { PersonTypeMap } from "@/enums/appEnums";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        limit?: number;
        isConfig?: boolean;
        skipUnConfig?: boolean;
    }>(),
    {
        modelValue: false,
        limit: 99,
        isConfig: true,
        skipUnConfig: false,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "select", value: any[]): void;
    (e: "close"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit("update:modelValue", value);
    },
});

const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseLists = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPersonList({ page_no, page_size, is_configured: props.isConfig ? 1 : "" });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const toDetail = (item?: any) => {
    uni.$u.route({
        url: "/ai_modules/person/pages/detail/detail",
        params: { id: item.id },
    });
};

const toCreate = (item?: any) => {
    const params = item ? { id: item.id, mode: "edit", persona_type: item.persona_type } : {};
    show.value = false;
    uni.$u.route({
        url: "/ai_modules/person/pages/create/create",
        params: {
            ...params,
            source: "back",
        },
    });
};

const isChoose = (item: any) => {
    return chooseLists.value.some((c) => c.id === item.id);
};

// 是否禁用该项
const isDisabled = (item: any) => {
    return props.skipUnConfig && !item.is_configured;
};

const handleSelect = (item: any) => {
    // 禁用未配置项
    if (isDisabled(item)) {
        uni.showToast({ title: "该人设未配置，无法选择", icon: "none" });
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
                uni.showToast({ title: `最多只能选择${props.limit}个`, icon: "none" });
            } else {
                chooseLists.value.push(item);
            }
        }
    }
};

const toggleSelect = () => {
    // 过滤可选列表
    const selectable = props.skipUnConfig ? dataLists.value.filter((item) => item.is_configured) : dataLists.value;

    if (chooseLists.value.length === selectable.length && selectable.length > 0) {
        chooseLists.value = [];
    } else {
        if (selectable.length > props.limit) {
            uni.showToast({ title: `最多只能选择${props.limit}个`, icon: "none" });
            chooseLists.value = selectable.slice(0, props.limit);
        } else {
            chooseLists.value = [...selectable];
        }
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0) {
        uni.showToast({ title: "请至少选择一个人设", icon: "none" });
        return;
    }
    close();
    emit("select", props.limit === 1 ? chooseLists.value[0] : chooseLists.value);
};

const close = () => {
    emit("close");
    show.value = false;
};

defineExpose({
    setChooseLists: (lists: any[]) => {
        chooseLists.value = JSON.parse(JSON.stringify(lists));
    },
});
</script>
