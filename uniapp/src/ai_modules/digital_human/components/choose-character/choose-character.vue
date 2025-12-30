<template>
    <popup-bottom v-model="show" title="历史人设" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="pagingRef"
                    v-model="dataLists"
                    :auto="false"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="flex flex-col gap-4 p-[32rpx]">
                        <view
                            class="bg-white rounded-[24rpx] p-[24rpx] relative"
                            v-for="(item, index) in dataLists"
                            :key="index"
                            @click="emit('select', item)">
                            <view class="font-bold text-[32rpx]"> {{ item.name }} </view>
                            <view class="mt-[12rpx]"> {{ item.introduced }} </view>
                            <view class="absolute right-[-12rpx] top-[-12rpx] z-[22]">
                                <view
                                    class="w-[32rpx] h-[32rpx] bg-[#0000004C] rounded-full flex items-center justify-center"
                                    @click="handleDeleteHistory(item.id)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
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
import { getShanjianPersonList, deleteShanjianPerson } from "@/api/digital_human";

const props = withDefaults(defineProps<{ modelValue: boolean }>(), {
    modelValue: false,
});

const emit = defineEmits(["update:modelValue", "select"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const dataLists = ref<any[]>([]);
const pagingRef = ref<any>(null);

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getShanjianPersonList({
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        console.error("查询历史记录失败:", error);
    }
};

const handleDeleteHistory = async (id: number) => {
    await deleteShanjianPerson({
        id,
    });
    pagingRef.value?.reload();
};

watch(
    () => show.value,
    async () => {
        if (show.value) {
            await nextTick();
            pagingRef.value?.reload();
        }
    }
);
</script>

<style scoped></style>
