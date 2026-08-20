<template>
    <popup-bottom v-model="show" title="历史人设" :is-disabled-touch="true" custom-class="bg-[#F7F9FC]">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="pagingRef"
                    v-model="dataLists"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="flex flex-col gap-[16rpx] px-[32rpx] pt-[16rpx] pb-[32rpx]">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)] active:scale-[0.98] transition-transform duration-150"
                            @click="emit('select', item)">
                            <view class="flex">
                                <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                                <view class="flex-1 px-[24rpx] py-[24rpx]">
                                    <view class="flex items-center justify-between">
                                        <view class="flex items-center gap-[10rpx] flex-1 min-w-0">
                                            <view
                                                class="w-[44rpx] h-[44rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="text-[30rpx] font-bold text-[#0D1117] truncate flex-1">{{
                                                item.name
                                            }}</text>
                                        </view>
                                        <view
                                            class="ml-[16rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center flex-shrink-0"
                                            @click.stop="handleDeleteHistory(item.id)">
                                            <u-icon name="trash" color="#9CA3AF" size="16" />
                                        </view>
                                    </view>
                                    <view
                                        v-if="item.introduced"
                                        class="mt-[14rpx] pt-[14rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                        <text class="text-[#4B5563] leading-relaxed line-clamp-2">{{
                                            item.introduced
                                        }}</text>
                                    </view>
                                    <view class="mt-[14rpx] flex items-center justify-end gap-[6rpx]">
                                        <text class="text-[22rpx] text-[#9CA3AF]">点击使用</text>
                                        <u-icon name="arrow-right" size="13" color="#C0C4CC" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <template #empty>
                        <view class="flex flex-col items-center justify-center py-[80rpx] gap-[16rpx]">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                <u-icon name="account" size="44" color="#1C6FEB" />
                            </view>
                            <text class="text-[30rpx] font-bold text-[#0D1117]">暂无历史人设</text>
                            <text class="text-[#9CA3AF]">使用过的人设会保存在这里</text>
                        </view>
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
    await deleteShanjianPerson({ id });
    pagingRef.value?.reload();
};
</script>

<style scoped></style>
