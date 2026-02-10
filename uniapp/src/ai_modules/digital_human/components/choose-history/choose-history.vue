<template>
    <popup-bottom v-model="show" title="创作历史" height="80%" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="text-xs text-[#00000080] mt-2 px-[30rpx]"> 已选：{{ chooseLists.length }} </view>
                <view class="grow min-h-0 mt-[20rpx]">
                    <z-paging
                        class="h-full"
                        ref="pagingRef"
                        v-model="dataLists"
                        :fixed="false"
                        :auto="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="grid grid-cols-3 gap-2 px-[30rpx]">
                            <view
                                class="h-[288rpx] rounded-xl relative overflow-hidden"
                                v-for="(item, index) in dataLists"
                                :key="index"
                                @click="handleSelect(item)">
                                <image :src="item.pic" class="w-full h-full rounded-xl" mode="aspectFill"></image>
                                <view class="absolute top-0 left-0 w-full h-full bg-[#00000080]" v-if="isChoose(item)">
                                    <view class="absolute top-2 right-2">
                                        <image
                                            src="/static/images/icons/success.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white"
                                    v-else></view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
                <view class="flex items-center justify-between gap-2 mt-[20rpx] mb-4 px-[30rpx]">
                    <view class="flex items-center gap-x-2" @click="toggleSelect" v-if="limit && limit > 1">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && chooseLists.length == dataLists.length"
                                src="/static/images/icons/success.svg"
                                class="w-full h-full"></image>
                            <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else> </view>
                        </view>
                        <view>全选</view>
                    </view>
                    <view
                        class="flex-1 text-white font-medium text-[30rpx] rounded-[20rpx] bg-primary h-[90rpx] w-[460rpx] flex items-center justify-center"
                        @click="confirm">
                        确定选择
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";

const props = withDefaults(defineProps<{ modelValue: boolean; type?: number; limit?: number }>(), {
    modelValue: false,
    limit: 9,
});

const emit = defineEmits<{ (e: "update:modelValue", value: boolean): void; (e: "select", value: any[]): void }>();

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
        const { lists } = await getVideoCreationRecord({
            page_no,
            page_size,
            type: props.type || "",
        });
        pagingRef.value?.complete(lists.filter((item: any) => item.status == 3));
    } catch (error) {
        console.error("查询历史记录失败:", error);
    }
};

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item.id === data.id);
};

const handleSelect = (data: any) => {
    const isSelected = isChoose(data);

    if (isSelected) {
        chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        return;
    }

    if (props.limit === 1) {
        chooseLists.value = [data];
        return;
    }

    if (chooseLists.value.length >= props.limit) {
        uni.$u.toast(`最多选择${props.limit}个视频`);
        return;
    }

    chooseLists.value.push(data);
};

const toggleSelect = () => {
    if (chooseLists.value.length == dataLists.value.length) {
        chooseLists.value = [];
    } else {
        chooseLists.value = dataLists.value.slice(0, props.limit || dataLists.value.length);
    }
};

const confirm = () => {
    if (chooseLists.value.length == 0) {
        uni.$u.toast("至少选择一个视频");
        return;
    }
    show.value = false;
    emit("select", chooseLists.value);
    chooseLists.value = [];
};
watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal) {
            await nextTick();
            pagingRef.value?.reload();
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped></style>
