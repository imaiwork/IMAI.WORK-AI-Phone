<template>
    <u-popup v-model="showPopup" mode="center" width="90%" border-radius="48">
        <view class="container relative p-[24rpx]">
            <view class="absolute right-4 top-5" @click="showPopup = false">
                <image src="/static/images/icons/close.svg" class="w-[48rpx] h-[48rpx]"></image>
            </view>
            <view class="font-bold text-center mt-[24rpx] text-[30rpx]"> {{ title }} </view>
            <view
                class="border-[4rpx] border-solid border-[#0065fb4d] rounded-[48rpx] h-[846rpx] mt-[40rpx] p-0.5 shadow-lg">
                <video-player
                    v-if="show"
                    ref="videoPlayerRef"
                    :poster="poster || `${config.baseUrl}static/images/dh_example_bg1.png`"
                    :video-url="videoUrl"></video-player>
            </view>
            <view class="mt-[40rpx]">
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{
                        height: '90rpx',
                        boxShadow: ' 0px 3px 12px 0px rgba(0, 0, 0, 0.12)',
                        fontSize: '26rpx',
                    }"
                    @click="confirm"
                    >{{ confirmBtnText }}</u-button
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import config from "@/config";
import VideoPlayer from "../video-player/video-player.vue";
const props = withDefaults(
    defineProps<{
        title?: string;
        show: boolean;
        videoUrl: string;
        confirmBtnText?: string;
        poster?: string;
        zIndex?: number;
    }>(),
    {
        title: "视频预览",
        show: false,
        poster: "",
        confirmBtnText: "确定",
        zIndex: 1000,
    }
);

const emit = defineEmits(["update:show", "confirm"]);

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});

const isShowVideo = ref(true);

watch(
    () => props.show,
    async (val) => {
        if (!val) {
            setTimeout(() => {
                isShowVideo.value = false;
            }, 300);
            return;
        }
        isShowVideo.value = true;
        setTimeout(() => {
            videoPlayerRef.value?.toggleVideo();
        }, 300);
    }
);

const videoPlayerRef = ref<InstanceType<typeof VideoPlayer>>();

const confirm = () => {
    emit("confirm");
    showPopup.value = false;
};

defineExpose({
    toggleVideo: () => {
        videoPlayerRef.value?.toggleVideo();
    },
});
</script>

<style scoped lang="scss">
.container {
    background: linear-gradient(215.46deg, #0065fb -28.04%, #ffffff 35.52%);
}
</style>
