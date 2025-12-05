<template>
    <view class="h-screen bg-white flex flex-col">
        <view class="fixed top-0 left-0 right-0 z-50">
            <u-navbar
                :is-fixed="false"
                :border-bottom="false"
                :background="{
                    background: 'transparent',
                }">
            </u-navbar>
        </view>
        <view class="">
            <image src="@/ai_modules/meeting_minutes/static/images/home/cover.png" class="w-full h-[642rpx]"></image>
        </view>
        <view class="-mt-[120rpx] relative z-10 mx-[32rpx]">
            <view class="grid grid-cols-2 gap-4">
                <navigator
                    v-for="(item, index) in mainLists"
                    hover-class="none"
                    class="h-[312rpx] relative"
                    :url="item.link"
                    :key="index">
                    <view class="absolute top-0 left-0 right-0 bottom-0 h-[312rpx]">
                        <image :src="item.bg" class="w-full h-full"></image>
                    </view>
                    <image :src="item.logo" class="w-[164rpx] h-[208rpx] absolute -top-[80rpx] left-[12%] z-10"></image>
                    <view class="h-full w-full p-3 relative z-10">
                        <view class="text-[32rpx] font-bold mt-[100rpx]">
                            {{ item.title }}
                        </view>
                        <view class="text-[#474747] text-xs mt-[24rpx]">
                            <view>
                                {{ item.desc1 }}
                            </view>
                            <view>
                                {{ item.desc2 }}
                            </view>
                        </view>
                    </view>
                </navigator>
            </view>
        </view>
        <view class="grow min-h-0 mt-6 flex flex-col bg-[#EDF2FB7F] rounded-tl-[24rpx] rounded-tr-[24rpx]">
            <view class="flex justify-between items-center px-[32rpx] my-[32rpx]">
                <view
                    class="flex items-center text-xl font-bold before:content-[''] before:w-[6rpx] before:h-[24rpx] before:bg-primary before:block before:mr-[16rpx]">
                    我的会议
                </view>
                <navigator
                    hover-class="none"
                    url="/ai_modules/meeting_minutes/pages/record/record"
                    class="flex items-center gap-1">
                    <text class="text-[#A7A7A7]">更多</text>
                    <u-icon name="arrow-right" color="#A7A7A7"></u-icon>
                </navigator>
            </view>
            <view class="grow min-h-0">
                <scroll-view class="h-full" scroll-y>
                    <template v-if="pager.lists.length > 0">
                        <view class="px-[32rpx] flex flex-col gap-4 pb-4">
                            <view class="" v-for="(item, index) in pager.lists" :key="index">
                                <record-card
                                    :item="item"
                                    @delete-success="getLists"
                                    @again-success="getLists"></record-card>
                            </view>
                        </view>
                    </template>
                    <template v-else>
                        <Empty class="mt-8" />
                    </template>
                </scroll-view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import MeetingBg from "@/ai_modules/meeting_minutes/static/images/home/btn_meeting_cloud.png";
import MeetingLogo from "@/ai_modules/meeting_minutes/static/images/home/hysq.svg";
import AudioBg from "@/ai_modules/meeting_minutes/static/images/home/btn_audio.png";
import AudioLogo from "@/ai_modules/meeting_minutes/static/images/home/wkbb.svg";
import RecordCard from "@/ai_modules/meeting_minutes/components/record-card/record-card.vue";
import Empty from "../../components/empty/empty.vue";
import useHandleApi from "../../hooks/useHandleApi";
import { CreateType, TurnStatus } from "../../enums";

const mainLists = [
    {
        bg: MeetingBg,
        logo: MeetingLogo,
        title: "开启实时记录",
        desc1: "实时语音转文字",
        desc2: "同步口译，智能总结要点",
        link: `/ai_modules/meeting_minutes/pages/audio_upload/audio_upload?type=${CreateType.SINGLE}`,
    },
    {
        bg: AudioBg,
        logo: AudioLogo,
        title: "上传音视频文件",
        desc1: "即刻转写文字",
        desc2: "AI区分发言人，掌握内容",
        link: `/ai_modules/meeting_minutes/pages/audio_upload/audio_upload?type=${CreateType.BATCH}`,
    },
];

const { pager, getLists } = useHandleApi();

const loopTimer = ref<any>();
const loopLists = async () => {
    loopTimer.value = setTimeout(() => {
        if (pager.lists.some((item: any) => item.status == TurnStatus.ING || item.status == TurnStatus.WAITING)) {
            getLists();
            loopLists();
        } else {
            clearTimeout(loopTimer.value);
        }
    }, 2000);
};

const init = async () => {
    uni.showLoading({
        title: "加载中",
        mask: true,
    });
    try {
        await getLists();
        loopLists();
    } finally {
        uni.hideLoading();
    }
};

onShow(() => {
    init();
});

onUnload(() => {
    clearTimeout(loopTimer.value);
});
</script>

<style scoped></style>
