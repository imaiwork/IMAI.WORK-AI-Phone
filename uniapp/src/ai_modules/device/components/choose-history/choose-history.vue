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
                        :auto="false"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="grid grid-cols-3 gap-2 px-[30rpx]">
                            <view
                                class="rounded-xl relative overflow-hidden aspect-[3/4]"
                                v-for="(item, index) in getLists"
                                :key="index"
                                @click="handleSelect(item)">
                                <image
                                    :src="item.image || item.pic"
                                    class="w-full h-full rounded-xl"
                                    mode="aspectFill"></image>
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
                    <view class="flex items-center gap-x-2" @click="toggleSelect">
                        <view class="w-[32rpx] h-[32rpx]">
                            <image
                                v-if="chooseLists.length > 0 && isAllSelected"
                                src="/static/images/icons/success.svg"
                                class="w-full h-full"></image>
                            <view class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]" v-else> </view>
                        </view>
                        <view>全选</view>
                    </view>
                    <view
                        class="text-white font-medium text-[30rpx] rounded-[20rpx] bg-primary h-[90rpx] w-[460rpx] flex items-center justify-center"
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
import { drawingRecord } from "@/api/drawing";

const props = withDefaults(defineProps<{ modelValue: boolean; type: "image" | "video"; limit: number }>(), {
    limit: 9,
    type: "image",
    modelValue: false,
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

const getLists = computed(() => {
    return props.type == "image" ? dataLists.value.filter((item: any) => item.draw_type != "6") : dataLists.value;
});

const isAllSelected = computed(() => {
    return chooseLists.value.length == dataLists.value.slice(0, props.limit || dataLists.value.length).length;
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        let { lists } =
            props.type == "image"
                ? await drawingRecord({
                      page_no,
                      page_size,
                  })
                : await getVideoCreationRecord({
                      page_no,
                      page_size,
                      success: 1,
                  });

        pagingRef.value?.complete(lists);
    } catch (error) {
        console.error("查询历史记录失败:", error);
    }
};

const isChoose = (data: any) => {
    return chooseLists.value.some((item) => item.id === data.id);
};

const handleSelect = (data: any) => {
    if (props.limit === 1) {
        if (isChoose(data)) {
            chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        } else {
            chooseLists.value = [data];
        }
    } else {
        if (isChoose(data)) {
            chooseLists.value = chooseLists.value.filter((item) => item.id !== data.id);
        } else {
            if (props.limit && chooseLists.value.length >= props.limit) {
                uni.$u.toast(`最多选择${props.limit}个素材`);
                return;
            }
            chooseLists.value.push(data);
        }
    }
};

const toggleSelect = () => {
    if (isAllSelected.value) {
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
