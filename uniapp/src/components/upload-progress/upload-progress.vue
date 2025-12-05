<template>
    <u-popup v-model="show" mode="center" border-radius="24" width="90%" :mask-close-able="false">
        <view class="bg-white rounded-[24rpx] p-[48rpx]">
            <view class="font-bold text-center">上传进度</view>
            <view class="mt-[48rpx]">
                <view class="">
                    <view class="flex items-center justify-between">
                        <view>第{{ currentIndex }}个素材上传中...</view>
                        <view class="text-[#515357] font-bold text-[32rpx]"> {{ currentProgress }}% </view>
                    </view>
                    <view class="mt-[16rpx]">
                        <u-line-progress
                            :striped="true"
                            :percent="currentProgress"
                            :height="16"
                            :striped-active="true"
                            active-color="#0065FB"
                            inactive-color="#CDE0FC"></u-line-progress>
                    </view>
                </view>
                <view class="mt-[24rpx]">
                    <view class="flex items-center justify-between">
                        <view>总体进度</view>
                        <view class="text-[#515357] font-bold text-[32rpx]">
                            {{ currentIndex }}/{{ uploadList.length }}
                        </view>
                    </view>
                    <view class="mt-[16rpx]">
                        <u-line-progress
                            :striped="true"
                            :percent="parseInt(((currentIndex / uploadList.length) * 100).toFixed(2))"
                            :height="16"
                            :striped-active="true"
                            active-color="#0065FB"
                            inactive-color="#CDE0FC"></u-line-progress>
                    </view>
                </view>
            </view>
            <view class="text-center mt-[48rpx] text-[#9999997F] text-[26rpx]"> 请勿熄屏或切换应用 </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        uploadList: any[];
    }>(),
    {
        modelValue: false,
        // 上传列表
        uploadList: () => [],
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

// 获取当前上传进度
const currentProgress = computed(() => {
    // 如果没有上传列表，返回0
    if (!props.uploadList.length) {
        return 0;
    }

    // 如果所有文件都已上传完成，则显示100%
    if (props.uploadList.every((item) => item.progress === 100)) {
        return 100;
    }

    // 获取当前正在上传的文件的进度（第一个进度未达到100%的文件）
    const currentFile = props.uploadList.find((item) => item.progress < 100);
    // 返回当前文件的进度，如果找不到（理论上不会发生，因为上面已经处理了全部完成的情况），则返回0
    return currentFile ? currentFile.progress : 0;
});

// 获取当前索引
const currentIndex = computed(() => {
    if (props.uploadList.every((item) => item.progress === 100)) {
        return props.uploadList.length;
    }
    return (props.uploadList.findIndex((item) => item.progress !== 100) || 0) + 1;
});
</script>

<style scoped></style>
