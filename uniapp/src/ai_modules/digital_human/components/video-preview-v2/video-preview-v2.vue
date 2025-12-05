<template>
    <view class="fixed bottom-0 left-0 w-full h-full bg-white z-[88888] flex flex-col" v-if="showPopup">
        <view class="grow min-h-0">
            <video-player
                v-if="showPopup"
                show-close
                :border-radius="0"
                :poster="poster"
                :video-url="videoUrl"
                @close="emit('update:show', false)"
                @click="emit('update:show', false)"></video-player>
        </view>
        <view class="h-[278rpx] bg-black">
            <view class="text-center mt-[28rpx] text-white">
                免责声明：内容由<text class="text-xs text-[#FF6A00]">AI生成</text>，请仔细甄别。
            </view>
            <view class="flex items-center justify-center gap-x-2 px-4 mt-4">
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center bg-white rounded-[16rpx]"
                    @click="saveVideoToPhotosAlbum(videoUrl)"
                    >下载视频</view
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { saveVideoToPhotosAlbum } from "@/utils/file";
import VideoPlayer from "../video-player/video-player.vue";
const props = withDefaults(
    defineProps<{
        show: boolean;
        videoUrl: string;
        confirmBtnText?: string;
        poster?: string;
    }>(),
    {
        show: false,
        poster: "",
    }
);

const emit = defineEmits(["update:show"]);

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});
</script>

<style scoped lang="scss">
.container {
    background: linear-gradient(215.46deg, #0065fb -28.04%, #ffffff 35.52%);
}
</style>
