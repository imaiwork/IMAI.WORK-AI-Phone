<template>
    <view class="w-full h-full bg-white rounded-[40rpx]" @click="emit('detail', item.id)">
        <view
            class="h-[100rpx] px-[32rpx] flex justify-between items-center gap-x-2 border-solid border-[0] border-b-[1rpx] border-[#0000000d]">
            <view class="flex-1 font-bold text-lg flex items-center gap-x-2" @click.stop="emit('edit', item)">
                <text class="line-clamp-2">{{ item.name }}</text>
                <u-icon name="edit-pen" size="24" color="#00000080"></u-icon>
            </view>
        </view>
        <view class="px-[32rpx] py-[36rpx]">
            <view class="flex items-center justify-between">
                <view class="">执行进度</view>
                <view class="text-primary">
                    <text>{{ item.number_of_implemented_keywords || 0 }}</text>
                    <text class="mx-[4rpx]">/</text>
                    <text>{{ item.implementation_keywords_number || 0 }}</text>
                </view>
            </view>
            <view class="relative h-[8rpx] bg-[#0000000d] rounded-full mt-[20rpx]">
                <view
                    class="absolute top-0 left-0 h-full bg-primary rounded-full"
                    :style="{ width: getProgress(item) }"></view>
            </view>
            <view class="flex justify-between items-center gap-x-2 mt-[20rpx]">
                <view class="text-[#0000004d]">当前获客数：{{ item.crawl_number || 0 }}</view>
                <view class="text-[#0000004d]">{{ item.create_time }}</view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { changeTaskStatus, updateTask, retryTask } from "@/api/sph";
const props = defineProps({
    item: {
        type: Object,
        default: () => ({
            id: "",
            name: "",
            status: "",
            number_of_implemented_keywords: 0,
            implementation_keywords_number: 0,
            implementation_total_number: 0,
            create_time: "",
        }),
    },
});

const emit = defineEmits(["changeStatus", "detail", "retry", "edit"]);

const statusMap: any = {
    0: "未执行",
    1: "执行中",
    2: "已暂停",
    3: "已完成",
    4: "已结束",
};

const getTagClass = (status: number) => {
    if (status == 1) {
        return "bg-[#FFF0E5] text-[#FB6500]";
    } else if (status == 2 || status == 0) {
        return "bg-[#F6F6F6] text-[#00000080]";
    } else if (status == 3) {
        return "bg-[#EAF8E5] text-[#2DBC00]";
    } else if (status == 4) {
        return "bg-[#F6F6F6] text-[#00000080]";
    }
};

const getProgress = (item: any) => {
    const { number_of_implemented_keywords, implementation_keywords_number } = item;

    return `${(number_of_implemented_keywords / implementation_keywords_number) * 100}%`;
};

const handleChangeStatus = async (item: any) => {
    const { status } = item;
    if (status == 1 || status == 2) {
        uni.showModal({
            title: `确定${status == 1 ? "暂停" : "继续"}任务吗？`,
            success: async (res) => {
                if (res.confirm) {
                    uni.showLoading({
                        title: "操作中...",
                    });
                    try {
                        await changeTaskStatus({ status: status == 1 ? 2 : 3, id: item.id });
                        uni.hideLoading();
                        uni.showToast({
                            title: "操作成功",
                            icon: "none",
                            duration: 3000,
                        });
                        emit("changeStatus", item);
                    } catch (error: any) {
                        uni.hideLoading();
                        uni.showToast({
                            title: error,
                            icon: "none",
                            duration: 3000,
                        });
                    }
                }
            },
        });
    }
};

const handleException = (item: any) => {
    uni.showModal({
        title: "任务异常情况",
        content: "提醒：重新启动只会启动异常设备\n\n1. 设备启动未执行任务\n2. 设备断开重连\n",
        confirmText: "重新启动",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "操作中...",
                    mask: true,
                });
                try {
                    await retryTask({ id: item.id });
                    uni.hideLoading();
                    uni.showToast({
                        title: "操作成功",
                        icon: "none",
                        duration: 3000,
                    });
                    emit("retry", item);
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error,
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
        },
    });
};
</script>

<style scoped></style>
