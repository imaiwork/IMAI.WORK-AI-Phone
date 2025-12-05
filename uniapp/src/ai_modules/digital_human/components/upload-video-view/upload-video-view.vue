<template>
    <view>
        <view class="bg-[#F7F7F7] flex justify-center items-center rounded-lg relative">
            <view class="relative w-full h-[466rpx] rounded overflow-hidden">
                <template v-if="url">
                    <view class="absolute top-0 left-0 w-full h-full">
                        <image class="w-full h-full" :src="pic" mode="aspectFill" style="filter: blur(10px)"></image>
                    </view>
                    <view class="flex justify-center items-center w-full h-full relative z-10">
                        <image class="w-[50%] h-full" :src="pic" mode="aspectFill"></image>
                    </view>
                    <view
                        class="absolute top-[50%] left-[50%] flex flex-col items-center justify-center z-30"
                        style="transform: translate(-50%, -50%)"
                        @click="emit('preview-video')">
                        <image
                            src="@/ai_modules/digital_human/static/icons/play.svg"
                            class="w-[96rpx] h-[96rpx]"></image>
                    </view>
                    <view class="absolute left-2 top-2" v-if="modelVersionMap[modelVersion]">
                        <view class="version-tag">
                            {{ modelVersionMap[modelVersion] }}
                        </view>
                    </view>
                    <view class="absolute top-2 right-2 z-30">
                        <view @click="openVideo()">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/refresh.png"
                                class="w-[48rpx] h-[48rpx]"></image>
                        </view>
                    </view>
                </template>
                <view
                    v-else
                    class="w-full h-full flex flex-col items-center justify-center border border-dashed border-[#EBEBEB] rounded-lg p-4 bg-primary-light-8"
                    @click="openVideo()">
                    <image
                        src="@/ai_modules/digital_human/static/images/common/upload.png"
                        class="w-[126rpx] h-[126rpx]"></image>
                    <view class="text-primary text-lg font-bold"> 点击上传视频 </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { useUpload, uploadLimit } from "../../hooks/useUpload";

const props = withDefaults(
    defineProps<{
        url: string;
        pic: string;
        modelVersion: string;
        isCustomRefresh?: boolean;
    }>(),
    {
        isCustomRefresh: false,
    }
);

const emit = defineEmits<{
    (event: "success", value: Record<string, any>): void;
    (event: "custom-refresh"): void;
    (event: "preview-video"): void;
}>();

const appStore = useAppStore();
const { getDigitalHumanConfig } = toRefs(appStore);

const modelVersionMap = computed(() => {
    return getDigitalHumanConfig.value.reduce((acc: Record<string, any>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

// 上传参数
const uploadParams = computed(() => {
    return uploadLimit[props.modelVersion];
});

const openVideo = () => {
    if (props.isCustomRefresh) {
        emit("custom-refresh");
        return;
    }
    const { upload } = useUpload({
        size: uploadParams.value?.size,
        resolution: [uploadParams.value?.minResolution, uploadParams.value?.maxResolution],
        duration: [uploadParams.value?.videoMinDuration, uploadParams.value?.videoMaxDuration],
        extension: ["mp4"],
        onSuccess(res) {
            const { url, pic } = res;
            emit("success", { url, pic });
        },
    });
    upload();
};
</script>

<style scoped></style>
