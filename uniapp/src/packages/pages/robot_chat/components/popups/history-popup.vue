<template>
    <popup-bottom v-model="show" title="历史会话" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full py-[30rpx]">
                <z-paging
                    ref="pagingRef"
                    v-model="recordLists"
                    :auto="false"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryRecordList">
                    <view class="flex flex-col gap-4 px-[32rpx]">
                        <view
                            v-for="item in recordLists"
                            :key="item.task_id"
                            class="bg-white rounded-[24rpx] p-[24rpx]"
                            :class="isCurrRecord(item) ? 'border-[2rpx] border-solid border-primary' : ''"
                            @click="emit('select', item)">
                            <view class="flex items-center justify-between">
                                <view class="text-[#AEAFB0] text-xs bg-[#F9FAFB] rounded-[12rpx] py-[4rpx] px-[8rpx]">
                                    {{ formatRecordTime(item.update_time || item.create_time) }}
                                </view>
                                <text v-if="isCurrRecord(item)" class="text-primary text-xs">当前会话</text>
                            </view>
                            <view class="line-clamp-3 mt-4 text-sm break-all">
                                {{ getRecordTitle(item) }}
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getCreativeRecord } from "@/api/chat";
import { formatRecordTime } from "@/utils/util";

const props = defineProps<{
    modelValue: boolean;
    /** 当前助理 ID，历史会话按助理维度隔离 */
    assistantId: number | string;
    /** 当前正在查看的会话，用于在列表里标记 */
    currTaskId?: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "select", record: any): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const pagingRef = shallowRef();
const recordLists = ref<any[]>([]);

const isCurrRecord = (item: any) => !!props.currTaskId && item.task_id === props.currTaskId;

const getRecordTitle = (item: any) => item.message || item.file_info?.name || "-";

const queryRecordList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getCreativeRecord({
            page_no,
            page_size,
            assistant_id: props.assistantId,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete(false);
    }
};

// 每次打开都重新拉取：新会话产生后列表要能立刻反映出来
watch(
    () => props.modelValue,
    async (visible) => {
        if (!visible) return;
        await nextTick();
        pagingRef.value?.reload();
    },
);
</script>
