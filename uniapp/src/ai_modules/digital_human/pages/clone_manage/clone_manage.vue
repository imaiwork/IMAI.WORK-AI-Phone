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
        <view class="flex justify-between h-[100rpx] items-center px-4">
            <view class="flex-1" v-show="!isDelete">
                <u-tabs :list="tabs" :is-scroll="false" :current="currentTab" bg-color="" @change="changeTab"></u-tabs>
            </view>
            <view class="flex items-center justify-between" :class="{ 'flex-1': isDelete }">
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

        <view class="px-4 mt-4">
            <view class="text-xs text-[#00000080]">结果：{{ dataCount }}</view>
        </view>
        <view class="grow min-h-0 mt-4">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
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
                            <anchor-video :item="item" @delete="handleDelete" @play="handlePlay"> </anchor-video>
                        </view>
                    </view>
                    <view class="flex flex-col gap-2" v-if="currentTab == 1">
                        <view v-for="(item, index) in dataLists" :key="index" class="audio-item">
                            <view class="flex items-center min-h-[120rpx] gap-x-2">
                                <view
                                    class="absolute top-0 right-0 w-[100rpx] h-[100rpx] rounded-full opacity-30"
                                    style="
                                        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                                        transform: translate(40rpx, -40rpx);
                                    ">
                                </view>
                                <view class="flex items-center gap-x-4 flex-1 relative z-10">
                                    <view
                                        class="icon-container w-[80rpx] h-[80rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0 relative overflow-hidden shadow-[0_4rpx_15rpx_rgba(59,130,246,0.3)]"
                                        style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)">
                                        <image
                                            src="@/ai_modules/digital_human/static/images/common/system_tone.svg"
                                            class="w-[40rpx] h-[40rpx] relative z-10"></image>
                                    </view>
                                    <view class="flex-1 min-w-0">
                                        <view
                                            class="line-clamp-1 break-all text-[32rpx] font-medium mb-[8rpx] text-[#1f2937]">
                                            {{ item.name }}
                                        </view>
                                        <view class="text-[24rpx] flex items-center gap-x-2 text-[#6b7280]">
                                            <text>{{ item.create_time }}</text>
                                        </view>
                                    </view>
                                </view>

                                <view class="flex items-center gap-x-2 flex-shrink-0 relative z-10">
                                    <view
                                        v-if="item.status == 1"
                                        class="play-btn flex items-center justify-center gap-x-1 rounded-[16rpx] px-[20rpx] py-[12rpx] transition-all duration-300 bg-[#eef6ff] border border-solid border-[#dbeafe] min-w-[120rpx] h-[64rpx]"
                                        :class="isPlaying && currVoiceId == item.id ? 'playing' : 'paused'"
                                        style="background: linear-gradient(135deg, #eef6ff 0%, #dbeafe 100%)"
                                        @click="toggleAudioPlayback(item)">
                                        <u-icon
                                            :name="isPlaying && currVoiceId == item.id ? 'pause-circle' : 'play-circle'"
                                            :size="30"
                                            color="#0065fb"></u-icon>
                                        <text class="text-[26rpx] font-medium text-primary">
                                            {{ isPlaying && currVoiceId == item.id ? "暂停" : "试听" }}
                                        </text>
                                    </view>

                                    <view
                                        v-else-if="item.status === 2"
                                        class="status-badge flex items-center gap-x-1 rounded-[16rpx] px-[20rpx] py-[12rpx] bg-[#fef2f2] border border-solid border-[#fca5a5] min-w-[100rpx] h-[64rpx]"
                                        style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/fail.svg"
                                            class="w-[26rpx] h-[26rpx]"></image>
                                        <text class="text-[26rpx] font-medium text-[#dc2626]"> 失败 </text>
                                    </view>

                                    <view
                                        v-else-if="[0, 3, 4, 5].includes(item.status)"
                                        class="status-badge flex items-center gap-x-1 rounded-[16rpx] px-[20rpx] py-[12rpx] relative bg-[#fffbeb] border border-solid border-[#fcd34d] min-w-[120rpx] h-[64rpx]"
                                        style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/clone.svg"
                                            class="w-[26rpx] h-[26rpx] animate-spin"></image>
                                        <text class="text-[26rpx] font-medium text-[#d97706]"> 克隆中 </text>
                                    </view>
                                </view>

                                <view
                                    class="z-[888] absolute left-0 top-0 w-full h-full rounded-[20rpx] flex items-center justify-center transition-all duration-300 bg-[#00000080]"
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
                            <view
                                class="absolute top-0 right-0 from-[#eef6ff] to-[#dbeafe] text-[#3b82f6] px-[16rpx] py-[8rpx] rounded-bl-[20rpx] text-[20rpx] font-medium z-[10]">
                                {{ gerModelVersionName(item.model_version) }}
                            </view>
                            <view
                                class="text-xs text-[#00000080] mt-2 p-2 bg-[#fef2f2] rounded-[12rpx] border border-solid border-[#fca5a5] text-[#dc2626]"
                                v-if="item.remark && item.status == 2">
                                原因：{{ item.remark }}
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="px-4 py-2 pb-4">
            <view
                class="h-[100rpx] flex items-center justify-center bg-black text-white font-bold text-[30rpx] rounded-[20rpx]"
                @click="toClone">
                立即去克隆
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
import { DigitalHumanModelVersionEnum, DigitalHumanModelVersionEnumMap } from "@/enums/appEnums";
import { useAudio } from "@/hooks/useAudio";
import { ModeTypeEnum } from "@/ai_modules/digital_human/enums";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import AnchorVideo from "@/ai_modules/digital_human/components/anchor-video/anchor-video.vue";

const tabs = [
    {
        name: "形象列表",
    },
    {
        name: "声音列表",
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
        dataCount.value = 0;
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

const gerModelVersionName = (model_version: number) => {
    return DigitalHumanModelVersionEnumMap[model_version as keyof typeof DigitalHumanModelVersionEnumMap];
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
    if (dataLists.value.length === 0) return;
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
        if (id) {
            dataLists.value = dataLists.value.filter((item) => item.id !== id);
        } else {
            dataLists.value = dataLists.value.filter((item, index) => !chooseList.value.includes(index));
        }
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

const toClone = () => {
    if (currentTab.value == 0) {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/anchor_create/anchor_create?type=anchor&model_version=1",
        });
    } else {
        uni.$u.route({ url: "/ai_modules/digital_human/pages/tone_clone/tone_clone?type=voice&model_version=1" });
    }
};

onLoad(async (options: any) => {
    if (options.type == ModeTypeEnum.ANCHOR) {
        currentTab.value = 0;
    } else if (options.type == ModeTypeEnum.TONE) {
        currentTab.value = 1;
    }
    await nextTick();
    pagingRef.value?.reload();
});

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
.audio-item {
    @apply bg-white rounded-[20rpx] px-[32rpx] py-[24rpx] relative overflow-hidden  border border-solid border-[#e2e8f0] shadow-[0_4rpx_20rpx_rgba(0,0,0,0.08)];
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 状态徽章动画 */
.status-badge {
    animation: pulse-subtle 2s ease-in-out infinite;
}

@keyframes pulse-subtle {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

/* 加载动画 */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
.progress-bar {
    animation: progress 2s ease-in-out infinite;
    width: 0;
}

@keyframes progress {
    0% {
        width: 0;
    }
    50% {
        width: 60%;
    }
    100% {
        width: 0;
    }
}
</style>
