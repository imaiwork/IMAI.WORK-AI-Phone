<template>
    <view class="h-screen flex flex-col relative">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="变化记录"
            title-bold>
        </u-navbar>
        <view class="grow min-h-0 flex flex-col relative z-20 my-2">
            <u-tabs :list="tabs" :is-scroll="false" :current="current" bg-color="" @change="change"></u-tabs>
            <view class="grow min-h-0 rounded-[24rpx]">
                <z-paging
                    ref="pagingRef"
                    v-model="balanceLists"
                    :fixed="false"
                    :default-page-size="20"
                    :safe-area-inset-bottom="true"
                    auto-show-back-to-top
                    @query="queryList">
                    <view class="px-[32rpx] flex flex-col gap-2">
                        <view v-for="(item, index) in balanceLists" :key="index" class="bg-white p-4 rounded-[16rpx]">
                            <view class="flex items-center justify-between">
                                <view>
                                    <view>{{ item.remark }}</view>
                                    <view class="text-[#8C8C8C] text-[20rpx] mt-1">{{ item.create_time }}</view>
                                </view>
                                <view class="text-right">
                                    <view class="text-[32rpx] font-bold">
                                        {{ item.change_amount_desc }}
                                    </view>

                                    <view class="text-[#8C8C8C] text-[20rpx] mt-1">
                                        剩余算力：{{ item.left_tokens }}
                                    </view>
                                </view>
                            </view>
                            <view class="my-2">
                                <u-line></u-line>
                            </view>

                            <view class="text-[#8C8C8C] mt-1 flex gap-2 text-[20rpx]">
                                <view v-for="(value, key) in item.extra"> {{ key }}：{{ value }} </view>
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

const tabs = [
    {
        name: "消耗记录",
        action: 2,
    },
    {
        name: "订阅记录",
        action: 1,
    },
];

const current = ref(0);

const change = (e: any) => {
    current.value = e;
    queryParams.action = tabs[e].action;
    pagingRef.value?.reload();
};

const balanceLists = ref<any[]>([]);

const pagingRef = shallowRef();
const queryParams = reactive({
    type: "tokens",
    action: 2,
});
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await accountLog({
            page_no,
            page_size,
            ...queryParams,
        });

        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};
</script>

<style scoped></style>
