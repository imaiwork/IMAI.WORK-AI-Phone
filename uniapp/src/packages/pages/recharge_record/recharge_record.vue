<template>
    <view class="h-screen flex flex-col relative">
        <view class="index-bg"></view>
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :background="{
                    background: 'transparent',
                }"
                title="订阅记录"
                title-bold>
            </u-navbar>
        </view>
        <view class="grow min-h-0 flex flex-col relative z-20 my-4 px-[32rpx]">
            <view class="bg-white grow min-h-0 rounded-[24rpx] py-[24rpx]">
                <z-paging
                    ref="pagingRef"
                    v-model="balanceLists"
                    :fixed="false"
                    :default-page-size="20"
                    :safe-area-inset-bottom="true"
                    auto-show-back-to-top
                    @query="queryList">
                    <view class="px-[32rpx]">
                        <view
                            v-for="(item, index) in balanceLists"
                            :key="index"
                            class="flex items-center justify-between py-4"
                            style="border-bottom: 1rpx solid #d6d6d6">
                            <view>
                                <view>{{ item.remark }}</view>
                                <view class="text-[#8C8C8C] text-xs mt-1">订单编号：{{ item.sn }}</view>
                            </view>
                            <view class="text-right">
                                <view class="text-[32rpx] font-bold">
                                    {{ item.change_amount_desc }}
                                </view>
                                <view class="text-[#8C8C8C] text-xs mt-1">{{ item.create_time }}</view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { accountLog } from "@/api/user";

const balanceLists = ref<any[]>([]);

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await accountLog({
            page_no,
            page_size,
            type: "tokens",
            is_order: 1,
            action: 1,
        });

        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};
</script>

<style scoped></style>
