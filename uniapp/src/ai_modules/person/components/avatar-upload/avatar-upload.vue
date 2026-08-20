<template>
    <view
        class="relative bg-white rounded-full border border-solid border-[#D6E8FF]"
        :style="{ width: `${size}rpx`, height: `${size}rpx` }"
        @click="uploadAndProcessFiles('image')">
        <view class="w-full h-full overflow-hidden rounded-full">
            <image :src="avatar" class="w-full h-full" mode="aspectFill" />
        </view>
        <view
            class="absolute bottom-[2rpx] right-[2rpx] rounded-full flex items-center justify-center z-[22]"
            :style="{ width: `${iconSize}rpx`, height: `${iconSize}rpx` }">
            <image
                src="@/ai_modules/person/static/icons/profile-camera.svg"
                class="w-full h-full"
                mode="aspectFit"></image>
        </view>
    </view>
</template>

<script setup lang="ts">
import useUpload from "@/hooks/useUpload";

withDefaults(
    defineProps<{
        avatar: string;
        size?: number;
        iconSize?: number;
    }>(),
    {
        avatar: "",
        size: 180,
        iconSize: 50,
    },
);

const emit = defineEmits<{
    (e: "update:avatar", value: string): void;
}>();

const { uploadAndProcessFiles } = useUpload({
    count: 1,
    fileAccept: ["jpg", "png", "jpeg"],
    fileSize: 20,
    imageResolution: [4096, 4096],
    sourceType: ["album", "camera"],
    onSuccess: (res: any) => {
        if (res.length > 0) {
            emit("update:avatar", res[0].url);
        } else {
            uni.$u.toast("上传失败");
        }
    },
});
</script>

<style scoped></style>
