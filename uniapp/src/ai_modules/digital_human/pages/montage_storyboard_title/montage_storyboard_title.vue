<template>
    <view class="h-screen flex flex-col">
        <view class="flex justify-end p-4" v-if="lists.length > 0">
            <view class="flex items-center gap-x-1" @click="handleAdd">
                <u-icon name="plus" size="16" color="#0065fb"></u-icon>
                <text class="text-primary font-medium">继续添加</text>
            </view>
        </view>
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y v-if="lists.length > 0">
                <view class="px-4 space-y-4 pb-[450rpx]">
                    <view
                        v-for="(item, index) in lists"
                        :key="index"
                        class="p-4 bg-white rounded-[16rpx] relative pr-8">
                        <u-input v-model="lists[index]" placeholder="请输入标题" type="textarea" maxlength="50" />
                        <view
                            class="absolute right-3 top-4 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                            @click.stop="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <view class="h-full flex flex-col items-center justify-center px-6" v-else>
                <view class="w-full max-w-[500rpx] relative overflow-hidden" @click="handleAdd">
                    <view
                        class="relative rounded-[20rpx] p-3 flex items-center justify-center space-x-4 shadow-[0_16rpx_32rpx_0_rgba(0,0,0,0.15)] bg-primary">
                        <view
                            class="w-12 h-12 rounded-full bg-[#ffffff] bg-opacity-20 flex items-center justify-center transform transition-transform duration-300">
                            <u-icon name="plus" size="24" color="#ffffff"></u-icon>
                        </view>

                        <view class="flex-1">
                            <text class="text-white text-lg font-bold block">添加新内容</text>
                            <text class="text-[#cccccc] text-sm">点击开始创建</text>
                        </view>

                        <view class="transform transition-transform duration-300">
                            <u-icon name="arrow-right" size="20" color="#ffffff"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="p-4 bg-white flex justify-center">
            <view
                class="rounded-[16rpx] w-full h-[100rpx] bg-[#000000] text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                @click="handleConfirm">
                确定
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const lists = ref<any[]>([]);

const handleAdd = () => {
    lists.value.push("");
};

const handleDelete = (index: number) => {
    lists.value.splice(index, 1);
};

const handleConfirm = () => {
    emit("confirm", {
        type: ListenerTypeEnum.MONTAGE_TOP_TITLE,
        data: lists.value.filter((item) => item.trim().length > 0),
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.data) {
        const data = JSON.parse(options.data);
        lists.value = data;
    }
});
</script>
