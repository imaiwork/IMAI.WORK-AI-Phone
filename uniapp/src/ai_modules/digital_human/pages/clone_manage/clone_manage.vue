<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }"
            title="我的创作"
            title-bold />

        <view class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-4">
            <view class="flex items-center h-[140rpx] justify-between gap-[16rpx]">
                <view v-show="!isDelete" class="flex-1 flex items-center bg-[#F0F2F5] rounded-[20rpx] p-[6rpx]">
                    <view
                        v-for="(tab, index) in tabs"
                        :key="index"
                        class="flex-1 h-[64rpx] flex items-center justify-center rounded-[16rpx] transition-all duration-300"
                        :class="currentTab === index ? 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.10)]' : ''"
                        @click="changeTab(index)">
                        <text
                            class="text-[28rpx] font-bold transition-all duration-300"
                            :class="currentTab === index ? 'text-primary' : 'text-[#9CA3AF]'">
                            {{ tab.name }}
                        </text>
                    </view>
                </view>

                <view v-show="isDelete" class="flex-1">
                    <text class="text-[28rpx] font-bold text-[#0D1117]">
                        已选 <text class="text-primary">{{ chooseList.length }}</text> 项
                    </text>
                </view>

                <view class="flex items-center gap-[12rpx] flex-shrink-0">
                    <view
                        v-if="isDelete"
                        class="h-[68rpx] px-[24rpx] flex items-center justify-center rounded-[20rpx] border border-solid border-[#E5E9F0] bg-white"
                        @click="handleSelectAll">
                        <text class="font-semibold text-[#4B5563]">
                            {{ chooseList.length === dataLists.length ? "取消全选" : "全选" }}
                        </text>
                    </view>
                    <view
                        v-if="isDelete"
                        class="h-[68rpx] px-[24rpx] flex items-center justify-center rounded-[20rpx]"
                        style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%)"
                        @click="handleDelete()">
                        <text class="font-bold text-white">删除 ({{ chooseList.length }})</text>
                    </view>
                    <view
                        class="h-[68rpx] px-[24rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-300"
                        :class="isDelete ? 'border border-solid border-[#E5E9F0] bg-white' : 'bg-[#EBF2FF]'"
                        @click="handleManage">
                        <text class="font-bold" :class="isDelete ? 'text-[#4B5563]' : 'text-primary'">
                            {{ isDelete ? "取消" : "管理" }}
                        </text>
                    </view>
                </view>
            </view>
        </view>

        <view class="px-4 pt-[16rpx] pb-[8rpx]">
            <view class="flex items-center gap-[8rpx]">
                <view class="w-[6rpx] h-[24rpx] bg-primary rounded-full" />
                <text class="text-xs text-[#9CA3AF]">
                    共 <text class="text-primary font-bold">{{ dataCount }}</text> 条结果
                </text>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4 pb-4">
                    <view class="grid grid-cols-2 gap-[16rpx]" v-if="currentTab == 0">
                        <view
                            class="relative rounded-[24rpx] overflow-hidden aspect-[3/4]"
                            v-for="(item, index) in dataLists"
                            :key="index">
                            <view
                                class="absolute z-[8889] w-full h-full rounded-[24rpx]"
                                :class="isChoose(index) ? 'bg-[#0065fb]/30' : 'bg-[#000000]/40'"
                                v-if="isDelete"
                                @click="clickItem(index)">
                                <view class="absolute right-[12rpx] top-[12rpx]">
                                    <view
                                        class="w-[40rpx] h-[40rpx] rounded-full border-2 border-solid flex items-center justify-center transition-all duration-200"
                                        :class="
                                            isChoose(index)
                                                ? 'bg-primary border-primary'
                                                : 'border-white bg-[#ffffff]/20'
                                        ">
                                        <u-icon v-if="isChoose(index)" name="checkmark" color="#fff" size="20" />
                                    </view>
                                </view>
                            </view>
                            <anchor-video :item="item" @delete="handleDelete" @play="handlePlay" />
                        </view>
                    </view>

                    <view class="flex flex-col gap-[16rpx]" v-if="currentTab == 1">
                        <view
                            v-for="(item, index) in dataLists"
                            :key="index"
                            class="bg-white rounded-[24rpx] px-[28rpx] py-[24rpx] relative overflow-hidden border border-solid border-[#F0F2F5] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                            <view class="flex items-center gap-[20rpx]">
                                <view
                                    class="w-[88rpx] h-[88rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/system_tone.svg"
                                        class="w-[44rpx] h-[44rpx]" />
                                </view>

                                <view class="flex-1 min-w-0">
                                    <text class="text-[30rpx] font-bold text-[#0D1117] line-clamp-1 block mb-[8rpx]">
                                        {{ item.name }}
                                    </text>
                                    <text class="text-xs text-[#9CA3AF]">{{ item.create_time }}</text>
                                </view>

                                <view class="flex-shrink-0">
                                    <view
                                        v-if="item.status == 1"
                                        class="flex items-center justify-center gap-[6rpx] rounded-[20rpx] px-[20rpx] h-[64rpx] border border-solid transition-all duration-300"
                                        :class="
                                            isPlaying && currVoiceId == item.id
                                                ? 'bg-[#EBF2FF] border-[#BFDBFE]'
                                                : 'bg-[#F7F9FC] border-[#E5E9F0]'
                                        "
                                        @click="toggleAudioPlayback(item)">
                                        <u-icon
                                            :name="isPlaying && currVoiceId == item.id ? 'pause-circle' : 'play-circle'"
                                            :size="30"
                                            color="#0065fb" />
                                        <text class="font-bold text-primary">
                                            {{ isPlaying && currVoiceId == item.id ? "暂停" : "试听" }}
                                        </text>
                                    </view>

                                    <view
                                        v-else-if="item.status === 2"
                                        class="flex items-center justify-center gap-[6rpx] rounded-[20rpx] px-[20rpx] h-[64rpx] bg-[#FEF2F2] border border-solid border-[#FECACA]">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/fail.svg"
                                            class="w-[26rpx] h-[26rpx]" />
                                        <text class="font-bold text-[#DC2626]">失败</text>
                                    </view>

                                    <view
                                        v-else-if="[0, 3, 4, 5].includes(item.status)"
                                        class="flex items-center justify-center gap-[6rpx] rounded-[20rpx] px-[20rpx] h-[64rpx] bg-[#FFFBEB] border border-solid border-[#FCD34D]">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/clone.svg"
                                            class="w-[26rpx] h-[26rpx] animate-spin" />
                                        <text class="font-bold text-[#D97706]">克隆中</text>
                                    </view>
                                </view>
                            </view>

                            <view
                                v-if="item.remark && item.status == 2"
                                class="mt-[16rpx] flex items-start gap-[8rpx] bg-[#FEF2F2] rounded-[12rpx] px-[16rpx] py-[12rpx] border border-solid border-[#FECACA]">
                                <view>
                                    <u-icon name="info-circle" color="#DC2626" size="20" />
                                </view>
                                <text class="text-xs text-[#DC2626] flex-1 leading-relaxed"
                                    >原因：{{ item.remark }}</text
                                >
                            </view>

                            <view
                                class="absolute left-0 top-0 w-full h-full rounded-[24rpx] flex items-center justify-center transition-all duration-300 z-[888]"
                                :class="isChoose(index) ? 'bg-[#0065fb]/20' : 'bg-[#000000]/30'"
                                v-if="isDelete"
                                @click="clickItem(index)">
                                <view class="absolute right-[16rpx] top-[16rpx]">
                                    <view
                                        class="w-[40rpx] h-[40rpx] rounded-full border-2 border-solid flex items-center justify-center transition-all duration-200"
                                        :class="
                                            isChoose(index)
                                                ? 'bg-primary border-primary'
                                                : 'border-white bg-[#ffffff]/20'
                                        ">
                                        <u-icon v-if="isChoose(index)" name="checkmark" color="#fff" size="20" />
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

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[50rpx]">
            <view
                class="h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="toClone">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">立即去创建</text>
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

const tabs = [{ name: "形象列表" }, { name: "音频列表" }];
const currentTab = ref(0);

const dataLists = ref<any[]>([]);
const chooseList = ref<number[]>([]);
const dataCount = ref(0);

const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const model_version = `${DigitalHumanModelVersionEnum.CHANJING},${DigitalHumanModelVersionEnum.MINIMAX_HD},${DigitalHumanModelVersionEnum.MINIMAX_TURBO},${DigitalHumanModelVersionEnum.SHANJIAN}`;
        const { lists, count } =
            currentTab.value == 0
                ? await getPublicAnchorList({ page_no, page_size })
                : await getVoiceList({ page_no, page_size, builtin: 1, model_version });
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

const videoUrl = ref<string>("");
const showVideoPreview = ref(false);
const handlePlay = (video_url: string) => {
    videoUrl.value = video_url;
    showVideoPreview.value = true;
};

const currVoiceId = ref(null);
const toggleAudioPlayback = async (item: any) => {
    if (!item.voice_urls) return uni.$u.toast("当前音频不支持试听");
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

const isChoose = (index: number) => chooseList.value.includes(index);

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
    uni.showLoading({ title: "重试中...", mask: true });
    try {
        await retryAnchor({ anchor_id: id });
        uni.hideLoading();
        pagingRef.value?.reload();
        uni.showToast({ title: "重试成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "重试失败", icon: "none", duration: 3000 });
    }
};

const handleDelete = async (id?: number, source_type?: string) => {
    if (isDelete.value && chooseList.value.length === 0) {
        uni.showToast({ title: "请选择要删除的项", icon: "none", duration: 3000 });
        return;
    }
    const confirmed = await showModal("提示", "确定要删除吗？");
    if (!confirmed) return;

    uni.showLoading({ title: "删除中...", mask: true });
    try {
        if (currentTab.value == 0) {
            if (id) {
                const deleteFunc =
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
            await deleteVoice({
                id: id || chooseList.value.map((index) => dataLists.value[index].id),
            });
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
    return new Promise((resolve) => uni.showModal({ title, content, success: resolve })).then(
        (res: any) => res.confirm,
    );
}

async function deleteBySourceType(sourceType: string, deleteFunction: Function) {
    const ids = dataLists.value
        .filter((item, index) => chooseList.value.includes(index) && item.source_type == sourceType)
        .map((item) => item.id);
    if (ids.length === 0) return;
    await deleteFunction({ id: ids });
}

const toClone = () => {
    if (currentTab.value == 0) {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/anchor_create/anchor_create?type=anchor",
        });
    } else {
        uni.$u.route({ url: "/ai_modules/digital_human/pages/tone_clone/tone_clone?type=voice" });
    }
};

onLoad((options: any) => {
    if (options.type == ModeTypeEnum.ANCHOR) {
        currentTab.value = 0;
    } else if (options.type == ModeTypeEnum.TONE) {
        currentTab.value = 1;
    }
});

onShow(async () => {
    await nextTick();
    pagingRef.value?.reload();
});

onUnload(() => {
    destroy();
});
</script>

<style scoped lang="scss">
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
</style>
