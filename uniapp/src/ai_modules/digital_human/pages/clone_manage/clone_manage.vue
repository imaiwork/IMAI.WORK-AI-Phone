<template>
    <view class="h-screen flex flex-col page-bg">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }"
            title="我的克隆"
            title-bold>
        </u-navbar>
        <view class="px-10">
            <u-tabs :list="tabs" :is-scroll="false" :current="currentTab" bg-color="" @change="changeTab"></u-tabs>
        </view>
        <view class="px-4 mt-4">
            <view class="text-xs text-[#00000080]">结果：{{ dataCount }}</view>
        </view>
        <view class="grow min-h-0 mt-4">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4">
                    <view class="grid grid-cols-2 gap-2" v-if="currentTab == 0">
                        <view class="h-[486rpx] relative" v-for="(item, index) in dataLists" :key="index">
                            <view
                                class="absolute z-[8888] w-full h-full bg-[rgba(0,0,0,0.5)] rounded-md"
                                v-if="isDelete"
                                @click="clickItem(item.id)">
                                <view class="absolute right-2 top-2">
                                    <view
                                        class="radio-wrap"
                                        :class="{
                                            'radio-wrap-active': active.includes(item.id),
                                        }">
                                        <view
                                            class="h-full w-full flex items-center justify-center"
                                            v-if="active.includes(item.id)">
                                            <u-icon name="checkmark" color="#fff" :size="20"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <video-item
                                :item="{
                                    id: item.id,
                                    name: item.name,
                                    pic: item.pic,
                                    status: item.status,
                                    video_url: item.url,
                                    model_version: item.model_version,
                                    remark: item.remark,
                                }"
                                :show-play="false"
                                :show-more="!isDelete"
                                @delete="handleDelete"
                                @play="handlePlay">
                            </video-item>
                        </view>
                    </view>
                    <view class="flex flex-col gap-2" v-if="currentTab == 1">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-white rounded-[16rpx] px-[26rpx] h-[170rpx] flex items-center gap-x-2 relative">
                            <view class="flex items-center gap-x-3 flex-1">
                                <image
                                    src="@/ai_modules/digital_human/static/images/common/audio_icon.png"
                                    class="w-[68rpx] h-[68rpx] flex-shrink-0"></image>
                                <view>
                                    <view class="line-clamp-1 break-all"> {{ item.name }} </view>
                                    <view class="text-[22rpx] text-[#0000004d] mt-1">
                                        {{ item.create_time }}
                                    </view>
                                </view>
                            </view>
                            <view
                                v-if="item.status == 1"
                                class="flex items-center justify-center gap-x-1 bg-[#EBF3FE] rounded-[10rpx] flex-shrink-0 w-[116rpx] h-[60rpx]"
                                @click="toggleAudioPlayback(item)">
                                <image
                                    v-if="isPlaying && currVoiceId == item.id"
                                    src="@/ai_modules/digital_human/static/icons/stop.svg"
                                    class="w-[24rpx] h-[24rpx]"></image>
                                <image
                                    v-else
                                    src="@/ai_modules/digital_human/static/icons/play2.svg"
                                    class="w-[24rpx] h-[24rpx]"></image>
                                <text class="text-xs text-primary">{{
                                    isPlaying && currVoiceId == item.id ? "暂停" : "试听"
                                }}</text>
                            </view>
                            <template v-else-if="item.status === 2">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/fail.svg"
                                    class="w-[32rpx] h-[32rpx]"></image>
                                <text class="text-xs text-[#FF5757]">失败</text>
                            </template>
                            <template v-else-if="[0, 3, 4, 5].includes(item.status)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/clone.svg"
                                    class="w-[24rpx] h-[24rpx]"></image>
                                <text class="text-xs text-[#FF8D1A]">克隆中</text>
                            </template>
                            <view
                                class="absolute z-[8888] left-0 top-0 w-full h-full bg-[#00000080] rounded-md"
                                v-if="isDelete"
                                @click="clickItem(item.id)">
                                <view class="absolute right-2 top-2">
                                    <view
                                        class="radio-wrap"
                                        :class="{
                                            'radio-wrap-active': active.includes(item.id),
                                        }">
                                        <view
                                            class="h-full w-full flex items-center justify-center"
                                            v-if="active.includes(item.id)">
                                            <u-icon name="checkmark" color="#fff" :size="20"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="px-4 pb-4 pt-1 flex items-center justify-between" v-if="dataLists.length > 0">
            <view class="flex items-center gap-x-2">
                <view
                    class="w-[144rpx] h-[68rpx] flex items-center justify-center text-white bg-primary rounded-md"
                    @click="handleManage">
                    {{ isDelete ? "取消" : "管理" }}
                </view>
                <view
                    v-if="isDelete"
                    class="w-[144rpx] h-[68rpx] flex items-center justify-center text-primary border border-solid border-primary rounded-md"
                    @click="handleSelectAll">
                    全选
                </view>
            </view>
            <view v-if="isDelete">
                <view
                    class="w-[174rpx] h-[68rpx] flex items-center justify-center text-white bg-[#FF2442] rounded-md"
                    @click="handleDelete()">
                    删除 ({{ active.length }})
                </view>
            </view>
        </view>
    </view>
    <video-preview
        v-model:show="showVideoPreview"
        title="视频预览"
        :video-url="videoUrl"
        @confirm="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { getAnchorList, deleteAnchor, getVoiceList, deleteVoice } from "@/api/digital_human";
import VideoItem from "@/ai_modules/digital_human/components/video-item/video-item.vue";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum } from "../../enums";
import { useAudio } from "@/hooks/useAudio";

const tabs = [
    {
        name: "形象克隆",
    },
    {
        name: "声音克隆",
    },
];
const currentTab = ref(0);

const dataLists = ref<any[]>([]);
const active = ref<number[]>([]);
const dataCount = ref(0);

// 音频播放hook
const { setUrl, isPlaying, play, pause, pauseAll, destroy } = useAudio();

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists, count } =
            currentTab.value == 0
                ? await getAnchorList({
                      page_no,
                      page_size,
                      type: 0,
                      model_version: DigitalHumanModelVersionEnum.CHANJING,
                  })
                : await getVoiceList({
                      page_no,
                      page_size,
                      builtin: 1,
                      model_version: DigitalHumanModelVersionEnum.CHANJING,
                  });
        dataCount.value = count;
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};
const changeTab = (index: number) => {
    currentTab.value = index;
    active.value = [];
    pagingRef.value?.reload();
    if (currentTab.value == 1) {
        pauseAll();
        destroy();
    }
};

const videoUrl = ref<string>("");
const showVideoPreview = ref(false);
const handlePlay = (video_url: string) => {
    videoUrl.value = video_url;
    showVideoPreview.value = true;
};

// 音频播放控制
const currVoiceId = ref(null);
const toggleAudioPlayback = async (item: any) => {
    if (isPlaying.value && currVoiceId.value !== item.id) {
        pauseAll();
    }
    if (isPlaying.value) {
        pause();
    } else {
        play(item.voice_urls);
        currVoiceId.value = item.id;
    }
};

const clickItem = (id: number) => {
    if (active.value.includes(id)) {
        active.value = active.value.filter((item) => item !== id);
    } else {
        active.value.push(id);
    }
};

const isDelete = ref(false);

const handleManage = () => {
    isDelete.value = !isDelete.value;
    active.value = [];
};

const handleSelectAll = () => {
    if (active.value.length === dataLists.value.length) {
        active.value = [];
    } else {
        active.value = dataLists.value.map((item) => item.id);
    }
};

const handleDelete = (id?: number) => {
    uni.showModal({
        title: "提示",
        content: "确定要删除吗？",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    currentTab.value == 0
                        ? await deleteAnchor({ id: id || active.value })
                        : await deleteVoice({ id: id || active.value });
                    pagingRef.value?.reload();
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 3000,
                    });
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "删除失败",
                        icon: "none",
                        duration: 3000,
                    });
                }
            }
            isDelete.value = false;
            active.value = [];
        },
    });
};

const handleCreate = (item: any) => {
    const { pic, anchor_id, model_version, name, url, width, height } = item;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/video_create/video_create",
        params: {
            type: ListenerTypeEnum.CREATE_ANCHOR,
            data: JSON.stringify({
                pic,
                anchor_id,
                model_version,
                name,
                url,
                width,
                height,
            }),
        },
    });
};

onUnload(() => {
    destroy();
});
</script>

<style scoped lang="scss">
.radio-wrap {
    @apply w-[32rpx] h-[32rpx] rounded-full border border-solid border-[#c8c9cc];
}
.radio-wrap-active {
    @apply bg-primary border-primary;
}
</style>
