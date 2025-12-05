<template>
    <view class="h-screen flex flex-col bg-[#F9FAFB]">
        <view class="grow min-h-0 relative">
            <view class="px-[32rpx] pb-[48rpx] mt-4 flex flex-col gap-[24rpx]">
                <view
                    v-for="(item, index) in dataLists"
                    :key="index"
                    class="bg-white rounded-xl h-[108rpx] flex items-center justify-between px-4 gap-x-3"
                    :class="{
                        'shadow-[0_0_0_1px_var(--color-primary)]': isChoose(item.id),
                    }"
                    @click="handleChooseAudio(item.id)">
                    <view class="flex items-center gap-x-3">
                        <view
                            class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center p-[6rpx]"
                            :class="[isChoose(item.id) ? 'bg-primary' : 'bg-[#0000000d]']">
                            <image
                                v-if="isChoose(item.id)"
                                src="@/ai_modules/digital_human/static/icons/film_white.svg"
                                class="w-full h-full"></image>
                            <image
                                v-else
                                src="@/ai_modules/digital_human/static/icons/film_black.svg"
                                class="w-full h-full"></image>
                        </view>
                        <text class="line-clamp-1">{{ item.name }}</text>
                    </view>
                    <view class="flex-shrink-0 flex items-center gap-x-3">
                        <text class="w-[28rpx] h-[28rpx]">
                            <image
                                v-if="isChoose(item.id)"
                                src="@/ai_modules/digital_human/static/icons/radio_s.svg"
                                class="w-full h-full"></image>
                            <image
                                v-else
                                src="@/ai_modules/digital_human/static/icons/radio.svg"
                                class="w-full h-full"></image>
                        </text>
                    </view>
                </view>
            </view>
        </view>
        <view class="h-[200rpx] px-[60rpx] pt-4 bg-white shadow-[0_0_12rpx_6rpx_rgba(0,0,0,0.05)]">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{
                    width: '100%',
                    height: '100rpx',
                    boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                    fontSize: '26rpx',
                }"
                @click="handleConfirm"
                >确定</u-button
            >
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
const dataLists = ref<any[]>([]);

const chooseStyles = ref<any[]>([]);

const isChoose = (dataId: any) => {
    return chooseStyles.value.some((item) => item.id == dataId);
};

const handleChooseAudio = (dataId: any) => {
    if (isChoose(dataId)) {
        chooseStyles.value = chooseStyles.value.filter((item) => item.id != dataId);
    } else {
        chooseStyles.value.push({ id: dataId });
    }
};

const handleConfirm = () => {
    if (chooseStyles.value.length == 0) {
        uni.$u.toast("请选择剪辑风格");
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_STYLES,
        data: chooseStyles.value,
    });
    uni.navigateBack();
};

onLoad((options) => {
    uni.setNavigationBarColor({
        frontColor: "#000000",
        backgroundColor: "#F9FAFB",
    });
});
</script>

<style scoped></style>
