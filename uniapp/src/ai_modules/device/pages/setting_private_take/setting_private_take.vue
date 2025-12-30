<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="px-4 text-[30rpx] font-bold mt-4"> 选择智能体 </view>
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
                            <view class="font-bold text-[30rpx] line-clamp-1">{{ item.name }}</view>
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
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getAgentList } from "@/api/agent";
import { createAutoTaskPrivateTakeConfig, getAutoTaskPrivateTakeConfigDetail } from "@/api/device";
const loading = ref(true);
const deviceCode = ref("");
const robotList = ref<any[]>([]);
const pagingRef = ref<any>(null);

const selectedAgent = ref<number>();

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

const handleSaveConfig = async () => {
    if (!selectedAgent.value) {
        uni.$u.toast("请选择智能体");
        return;
    }
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await createAutoTaskPrivateTakeConfig({
            device_code: deviceCode.value,
            robot_id: selectedAgent.value,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const data = await getAutoTaskPrivateTakeConfigDetail({ device_code: deviceCode.value });
        selectedAgent.value = data.robot_id;
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
});
</script>

<style scoped></style>
