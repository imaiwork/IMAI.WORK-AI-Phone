<template>
    <view class="h-screen">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-bold text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="px-4 mt-4">
            <view class="bg-white rounded-[16rpx] p-4">
                <view
                    v-for="(item, index) in formData"
                    class="border-[0] border-b-[1rpx] border-solid border-[#F1F2F5] pb-2"
                    :key="index"
                    :class="{
                        'mb-[28rpx] ': index < formData.length - 1,
                    }">
                    <view class="text-[28rpx] font-bold mb-2">{{ index == 0 ? "主标题" : "副标题" }}</view>
                    <u-input v-model="formData[index]" placeholder-style="color: #7C7E80; " maxlength="100"></u-input>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/drawing/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const formData = ref<any[]>(Array.from({ length: 3 }, () => ""));

const back = () => {
    if (formData.value.every((item) => item.trim() === "")) {
        uni.navigateBack();
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.PUZZLE_COPYWRITER,
        data: [formData.value],
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.data) {
        const data = JSON.parse(options.data);
        formData.value = data;
    }
});
</script>

<style scoped></style>
