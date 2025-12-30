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
                                class="absolute z-[8889] w-full h-full bg-[#00000080] rounded-md"
                                v-if="isDelete"
                                @click="clickItem(index)">
                                <view class="absolute right-2 top-2">
                                    <view
                                        class="radio-wrap"
                                        :class="{
                                            'radio-wrap-active': isChoose(index),
                                        }">
                                        <view
                                            class="h-full w-full flex items-center justify-center"
                                            v-if="isChoose(index)">
                                            <u-icon name="checkmark" color="#fff" :size="20"></u-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <anchor-video
                                :item="{
                                    id: item.id,
                                    name: item.name,
                                    pic: item.pic,
                                    status: item.status,
                                    url: item.result_url,
                                    remark: item.remark,
                                    source_type: item.source_type,
                                }"
                                @delete="handleDelete"
                                @play="handlePlay">
                            </anchor-video>
                        </view>
                    </view>
                    <view class="flex flex-col gap-2" v-if="currentTab == 1">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-white rounded-[16rpx] px-[26rpx] h-[170rpx] flex items-center gap-x-1 relative">
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
                                @click="clickItem(index)">
                                <view class="absolute right-2 top-2">
                                    <view
                                        class="radio-wrap"
                                        :class="{
                                            'radio-wrap-active': isChoose(index),
                                        }">
                                        <view
                                            class="h-full w-full flex items-center justify-center"
                                            v-if="isChoose(index)">
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
                    删除 ({{ chooseList.length }})
                </view>
            </view>
        </view>
    </view>
    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :video-url="videoUrl"
        @confirm="showVideoPreview = false" />
</template>

<script setup lang="ts">
import {
    getPublicAnchorList,
    deleteAnchor,
    deleteShanjianAnchor,
    deletePublicAnchor,
    retryAnchor,
    getVoiceList,
    deleteVoice,
} from "@/api/digital_human";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { useAudio } from "@/hooks/useAudio";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import AnchorVideo from "@/ai_modules/digital_human/components/anchor-video/anchor-video.vue";

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
const chooseList = ref<number[]>([]);
const dataCount = ref(0);

// 音频播放hook
const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const model_version = `${DigitalHumanModelVersionEnum.CHANJING},${DigitalHumanModelVersionEnum.STANDARD},${DigitalHumanModelVersionEnum.SHANJIAN}`;
        const { lists, count } =
            currentTab.value == 0
                ? await getPublicAnchorList({
                      page_no,
                      page_size,
                  })
                : await getVoiceList({
                      page_no,
                      page_size,
                      builtin: 1,
                      model_version,
                  });
        dataCount.value = count;
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};
const changeTab = (index: number) => {
    currentTab.value = index;
    chooseList.value = [];
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

const isChoose = (index: number) => {
    return chooseList.value.includes(index);
};

const clickItem = (index: number) => {
    if (isChoose(index)) {
        chooseList.value = chooseList.value.filter((item) => item !== index);
    } else {
        chooseList.value.push(index);
    }
};

const isDelete = ref(false);

const handleManage = () => {
    isDelete.value = !isDelete.value;
    chooseList.value = [];
};

const handleSelectAll = () => {
    if (chooseList.value.length === dataLists.value.length) {
        chooseList.value = [];
    } else {
        chooseList.value = dataLists.value.map((item, index) => index);
    }
};

const handleRetry = async (id: number) => {
    uni.showLoading({
        title: "重试中...",
        mask: true,
    });
    try {
        await retryAnchor({ anchor_id: id });
        uni.hideLoading();
        pagingRef.value?.reload();
        uni.showToast({
            title: "重试成功",
            icon: "none",
            duration: 3000,
        });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "重试失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const handleDelete = async (id?: number, source_type?: string) => {
    const confirmed = await showModal("提示", "确定要删除吗？");
    if (!confirmed) return;

    uni.showLoading({
        title: "删除中...",
        mask: true,
    });

    try {
        if (currentTab.value == 0) {
            if (id) {
                let deleteFunc =
                    source_type === "human_anchor"
                        ? deleteAnchor
                        : source_type === "shanjian_anchor"
                        ? deleteShanjianAnchor
                        : deletePublicAnchor;
                await deleteFunc({ id });
            } else {
                await deleteBySourceType("human_anchor", deleteAnchor);
                await deleteBySourceType("shanjian_anchor", deleteShanjianAnchor);
                await deleteBySourceType("public_anchor", deletePublicAnchor);
            }
        }
        if (currentTab.value == 1) {
            await deleteVoice({ id: id || chooseList.value.map((index) => dataLists.value[index].id) });
        }
        dataLists.value = dataLists.value.filter((item, index) => !chooseList.value.includes(index));
        chooseList.value = [];
        uni.showToast({ title: "删除成功", icon: "success", duration: 3000 });
    } catch (error: any) {
        uni.showToast({ title: error || "删除失败", icon: "error", duration: 3000 });
    } finally {
        uni.hideLoading();
        isDelete.value = false;
        chooseList.value = [];
    }
};

async function showModal(title: string, content: string) {
    return new Promise((resolve) =>
        uni.showModal({
            title,
            content,
            success: resolve,
        })
    ).then((res: any) => res.confirm);
}

async function deleteBySourceType(sourceType: string, deleteFunction: Function) {
    const ids = dataLists.value
        .filter((item, index) => chooseList.value.includes(index) && item.source_type == sourceType)
        .map((item) => item.id);
    if (ids.length === 0) return;

    await deleteFunction({ id: ids });
}

const tryReloadPaging = () => pagingRef.value?.reload();

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
