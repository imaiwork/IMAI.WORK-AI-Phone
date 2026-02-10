<template>
    <view class="h-full flex flex-col">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="robotList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="p-4 flex flex-col gap-y-4">
                    <view
                        v-for="(item, index) in robotList"
                        :key="index"
                        class="rounded-[20rpx] bg-white p-[32rpx] flex gap-x-4 relative"
                        :class="selectedAgent == item.id ? 'shadow-[0_0_0_2rpx_#0065FB] bg-[#deecffb3]' : 'bg-white'"
                        @click="selectedAgent = item.id">
                        <image :src="item.image" class="flex-shrink-0 w-[90rpx] h-[90rpx] rounded-full"></image>
                        <view class="flex-1">
                            <view class="font-medium text-[30rpx] line-clamp-1">{{ item.name }}</view>
                            <view class="text-xs text-[#0000004d] line-clamp-1 mt-[10rpx]">
                                {{ item.intro }}
                            </view>
                            <view class="mt-[27rpx] text-xs text-[#0000004d]"> 创建人：{{ item.source_text }} </view>
                        </view>
                        <view class="absolute top-2 right-2" v-if="selectedAgent == item.id">
                            <image src="/static/images/icons/success.svg" class="w-[32rpx] h-[32rpx]"></image>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-medium flex items-center justify-center"
                @click="confirm">
                确定保存
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getAgentList } from "@/api/agent";

const props = defineProps<{
    agentId?: number | string;
}>();

const emit = defineEmits<{
    (e: "confirm", agent: any): void;
}>();

const selectedAgent = ref<any>(props.agentId);
const robotList = ref<any[]>([]);
const pagingRef = ref<any>(null);
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAgentList({
            page_no,
            page_size,
        });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const confirm = () => {
    if (!selectedAgent.value) {
        uni.$u.toast("请选择评论智能体");
        return;
    }
    const data = robotList.value.find((item) => item.id === selectedAgent.value);

    emit("confirm", data);
};
</script>

<style scoped></style>
