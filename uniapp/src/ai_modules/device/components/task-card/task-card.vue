<template>
    <view
        class="rounded-[20rpx] px-5 py-[26rpx] relative"
        :class="getCardStyle(item.status).bgColor"
        @click="handleClick">
        <view
            class="absolute left-0 top-[50%] h-[52rpx] w-[6rpx] rounded-full"
            :class="getCardStyle(item.status).lineColor"
            style="transform: translateY(-50%)"></view>
        <view class="flex items-center gap-x-2">
            <view class="flex-1">
                <view class="flex items-center gap-x-2">
                    <view class="line-clamp-1 text-[30rpx] font-bold break-all">
                        {{ item.name }}
                    </view>
                    <view class="p-1" @click.stop="handleEditName(item)">
                        <image src="/static/images/icons/edit_pen.svg" class="w-4 h-4" />
                    </view>
                </view>
                <view class="mt-[12rpx] flex items-center gap-x-1">
                    <image src="@/ai_modules/device/static/icons/task.svg" class="w-[32rpx] h-[32rpx]"></image>
                    <text class="text-[#00000066] text-xs">{{ item.task_category }} </text>
                </view>
            </view>
            <view class="flex-shrink-0 text-right">
                <view
                    class="px-[12rpx] py-[6rpx] rounded-[12rpx] text-[22rpx]"
                    :class="getCardStyle(item.status).textColor"
                    >{{ getTaskStatusText(item.status) }}</view
                >
            </view>
        </view>
        <view class="text-[20rpx] text-[#FF2442] break-all mt-2" v-if="[3, 4].includes(item.status)">
            失败原因：({{ item.remark }})
        </view>
    </view>
</template>

<script setup lang="ts">
import { useDevice } from "@/ai_modules/device/hooks/useDevice";

const props = defineProps<{
    item: any;
}>();

const emit = defineEmits<{
    (e: "click"): void;
    (e: "edit-name", item: any): void;
}>();

const { getTaskStatusText } = useDevice();

const getCardStyle = (status: number) => {
    switch (status) {
        case 0:
        case 1:
            return {
                bgColor: "bg-[rgba(0,101,251,0.04)]",
                textColor: "text-primary",
                lineColor: "bg-primary",
            };
        case 2:
            return {
                bgColor: "bg-[rgba(0,192,142,0.1)]",
                textColor: "text-[#00C08E]",
                lineColor: "bg-[#00C08E]",
            };
        case 3:
        case 4:
            return {
                bgColor: "bg-[rgba(255,36,36,0.1)]",
                textColor: "text-[#FF2442]",
                lineColor: "bg-[#FF2442]",
            };
        default:
            return {
                bgColor: "bg-[rgba(0,0,0,0.05)]",
                textColor: "text-[rgba(0,0,0,0.5)]",
                lineColor: "bg-[rgba(0,0,0,0.05)]",
            };
    }
};

const handleEditName = (item: any) => {
    emit("edit-name", item);
};

const handleClick = () => {
    emit("click");
};
</script>

<style scoped></style>
