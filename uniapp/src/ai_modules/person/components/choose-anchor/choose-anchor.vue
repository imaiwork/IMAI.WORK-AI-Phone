<template>
    <popup-bottom
        v-model="show"
        title="请选择形象"
        custom-class="bg-[#F7F9FC]"
        :is-disabled-touch="true"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col bg-[#F7F9FC]">
                <view class="flex items-center justify-between px-[30rpx] mt-3 mb-1" v-if="limit > 1">
                    <text class="text-[#666666] font-medium">请选择形象</text>
                    <view class="text-xs text-[#999999]">
                        已选
                        <text class="text-primary font-bold mx-0.5">{{ chooseLists.length }}</text>
                        / {{ limit }}
                    </view>
                </view>

                <view class="grow min-h-0 mt-[10rpx]">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[20rpx] px-[30rpx] grid grid-cols-3 gap-x-3 gap-y-5">
                            <view class="flex flex-col gap-2 group" @click="toClone">
                                <view
                                    class="relative w-full aspect-[3/4] rounded-[24rpx] bg-[#F0F4F8] border-2 border-dashed border-[#D2DCE6] flex flex-col items-center justify-center active:bg-[#E2E8F0] transition-colors">
                                    <view
                                        class="w-[64rpx] h-[64rpx] rounded-full bg-white shadow-[0_4rpx_12rpx_rgba(0,0,0,0.05)] flex items-center justify-center mb-2">
                                        <u-icon name="plus" color="#0065fb" size="32"></u-icon>
                                    </view>
                                    <text class="text-xs text-[#666666] font-medium">去克隆</text>
                                </view>
                            </view>

                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="flex flex-col gap-2"
                                @click.stop="handleSelect(item)">
                                <view
                                    class="relative w-full aspect-[3/4] rounded-[24rpx] overflow-hidden bg-[#F4F5F7] shadow-[0_4rpx_16rpx_rgba(0,0,0,0.03)] border border-[#000000]/5">
                                    <image :src="item.pic" class="w-full aspect-[3/4]" mode="aspectFill"></image>

                                    <view
                                        v-if="[0, 1].includes(item.status)"
                                        class="absolute inset-0 bg-[#ffffff]/40 flex items-center justify-center z-10">
                                        <view
                                            class="px-3 py-1.5 rounded-full bg-[#000000]/60 text-white text-[22rpx] font-medium tracking-wide">
                                            克隆中
                                        </view>
                                    </view>

                                    <!-- 禁用遮罩 -->
                                    <view
                                        v-else-if="isDisabled(item)"
                                        class="absolute inset-0 bg-[#000000]/40 flex items-center justify-center z-10">
                                        <view
                                            class="px-3 py-1.5 rounded-full bg-[#000000]/60 text-white text-[22rpx] font-medium tracking-wide">
                                            已使用
                                        </view>
                                    </view>

                                    <template v-else>
                                        <view
                                            v-if="isChoose(item)"
                                            class="absolute inset-0 bg-[#0065fb]/10 border-[3rpx] border-primary rounded-[24rpx] box-border pointer-events-none z-10 transition-all">
                                        </view>

                                        <view
                                            class="absolute top-2 right-2 w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center z-20 transition-all duration-200"
                                            :class="
                                                isChoose(item)
                                                    ? 'bg-primary shadow-[0_2rpx_8rpx_rgba(0,101,251,0.4)] border border-primary'
                                                    : 'bg-[#000000]/20 border-[2rpx] border-[#ffffff/80'
                                            ">
                                            <image
                                                v-if="isChoose(item)"
                                                src="/static/images/icons/success.svg"
                                                class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                    </template>
                                </view>

                                <text
                                    class="font-bold text-center truncate px-1"
                                    :class="isDisabled(item) ? 'text-[#BBBBBB]' : 'text-[#333333]'">
                                    {{ item.name || "形象名称" }}
                                </text>
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
                                chooseLists.length > 0 && chooseLists.length == selectableLists.length
                                    ? 'bg-primary'
                                    : 'border-[3rpx] border-[#D1D5DB] bg-[#F9FAFB]'
                            ">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length == selectableLists.length"
                                src="/static/images/icons/success.svg"
                                class="w-[24rpx] h-[24rpx]"></image>
                        </view>
                        <text class="text-[28rpx] text-[#333333] font-medium">全选</text>
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
                            确定选择
                            {{ limit > 1 && chooseLists.length > 0 ? `(${chooseLists.length})` : "" }}
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getPublicAnchorListV2 } from "@/api/digital_human";

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        limit?: number;
    }>(),
    {
        modelValue: false,
        limit: 99,
    },
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
const disabledLists = ref<any[]>([]);

const selectableLists = computed(() => {
    return dataLists.value.filter((item) => item.status != 0 && !isDisabled(item));
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const res = await getPublicAnchorListV2({ page_no, page_size, status: [0, 1, 2] });
        pagingRef.value?.complete(
            res.lists.map((item: any) => ({
                id: item.id,
                pic: item.pic,
                name: item.name,
                url: item.result_url,
                status: item.status,
            })) || [],
        );
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const toClone = () => {
    show.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
    });
};

const isChoose = (item: any) => {
    return chooseLists.value.some((c) => c.id === item.id);
};

const isDisabled = (item: any) => {
    return disabledLists.value.some((d) => d.id === item.id);
};

const handleSelect = (item: any) => {
    if ([0, 1].includes(item.status)) {
        uni.showToast({ title: "该形象正在克隆中，无法选择", icon: "none" });
        return;
    }

    if (isDisabled(item)) {
        uni.showToast({ title: "该形象已被使用，无法选择", icon: "none" });
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
    if (chooseLists.value.length === selectableLists.value.length && selectableLists.value.length > 0) {
        chooseLists.value = [];
    } else {
        const allSelectable = [...selectableLists.value];
        if (allSelectable.length > props.limit) {
            uni.showToast({ title: `最多只能选择${props.limit}个`, icon: "none" });
            chooseLists.value = allSelectable.slice(0, props.limit);
        } else {
            chooseLists.value = allSelectable;
        }
    }
};

const confirm = () => {
    if (chooseLists.value.length === 0 && disabledLists.value.length === 0) {
        uni.showToast({ title: "请至少选择一个形象", icon: "none" });
        return;
    }
    close();
    emit("select", chooseLists.value);
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
