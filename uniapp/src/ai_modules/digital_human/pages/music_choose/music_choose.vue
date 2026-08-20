<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <view class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-4 py-[20rpx]">
            <view class="grid grid-cols-2 gap-[16rpx]">
                <view
                    class="flex items-center justify-center gap-[12rpx] h-[88rpx] rounded-[20rpx] bg-[#EBF2FF] border border-solid border-[#BFDBFE]"
                    @click="uploadAndProcessFiles('file')">
                    <view
                        class="w-[48rpx] h-[48rpx] rounded-[12rpx] flex items-center justify-center"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                        <image src="@/ai_modules/digital_human/static/icons/music.svg" class="w-[32rpx] h-[32rpx]" />
                    </view>
                    <text class="font-bold text-primary">上传音乐</text>
                </view>

                <view
                    class="flex items-center justify-between gap-[12rpx] h-[88rpx] rounded-[20rpx] bg-[#F7F9FC] border border-solid border-[#E5E9F0] px-[20rpx]"
                    @click="showVolumePopup = true">
                    <view class="flex items-center gap-[12rpx]">
                        <view class="w-[48rpx] h-[48rpx] rounded-[12rpx] flex items-center justify-center bg-[#F0F2F5]">
                            <image
                                src="@/ai_modules/digital_human/static/icons/volume.svg"
                                class="w-[32rpx] h-[32rpx]" />
                        </view>
                        <text class="font-bold text-[#0D1117]">音量</text>
                    </view>
                    <view class="flex items-center gap-[4rpx]">
                        <text class="text-xs font-semibold text-primary">{{ formatVolume(currentVolume) }}</text>
                        <u-icon name="arrow-right" size="18" color="#C0C4CC" />
                    </view>
                </view>
            </view>
        </view>

        <view class="grow min-h-0">
            <z-paging v-model="dataList" ref="pagingRef" :hide-empty-view="isShowAi" :fixed="false" @query="queryList">
                <view class="px-4 pt-[16rpx] pb-[20rpx] flex flex-col gap-[12rpx]">
                    <view
                        v-if="isShowAi"
                        class="bg-white rounded-[20rpx] overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.05),0_0_0_1rpx_rgba(0,0,0,0.04)] flex items-center gap-[20rpx] px-[20rpx] h-[112rpx] transition-all duration-200"
                        :class="isAiMusic ? 'shadow-[0_0_0_1.5rpx_#BFDBFE,0_4rpx_16rpx_rgba(0,101,251,0.12)]' : ''"
                        @click="handleChooseAi">
                        <view
                            class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(99,102,241,0.35)]"
                            style="background: linear-gradient(180deg, #8fa8f7 0%, #3f69f2 100%)">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/ai.png"
                                class="w-[44rpx] h-[44rpx]" />
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="text-[28rpx] font-bold text-[#0D1117] line-clamp-1">使用AI音乐库</text>
                            <text class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] block">自动匹配最佳背景音乐</text>
                        </view>
                        <view class="flex-shrink-0">
                            <view
                                v-if="isAiMusic"
                                class="w-[44rpx] h-[44rpx] rounded-full flex items-center justify-center shadow-[0_2rpx_8rpx_rgba(0,101,251,0.4)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="checkmark" color="#fff" size="22" />
                            </view>
                            <view
                                v-else
                                class="w-[44rpx] h-[44rpx] rounded-full border-2 border-solid border-[#E5E9F0] bg-[#F7F9FC]" />
                        </view>
                    </view>

                    <view class="flex items-center gap-[10rpx] px-[4rpx] mt-[4rpx]">
                        <view class="w-[6rpx] h-[24rpx] bg-primary rounded-full" />
                        <text class="text-xs font-extrabold text-[#0D1117]">音乐列表</text>
                        <view class="flex-1 h-[1rpx] bg-[#F0F2F5]" />
                    </view>

                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="bg-white rounded-[20rpx] overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.05),0_0_0_1rpx_rgba(0,0,0,0.04)] flex items-center gap-[20rpx] px-[20rpx] h-[112rpx] transition-all duration-200"
                        :class="isChoose(item) ? 'shadow-[0_0_0_1.5rpx_#BFDBFE,0_4rpx_16rpx_rgba(0,101,251,0.12)]' : ''"
                        @click="handleChoose(item)">
                        <view
                            class="w-[80rpx] h-[80rpx] rounded-[20rpx] flex items-center justify-center flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(120,201,196,0.35)] transition-all duration-200"
                            :style="
                                currentMusicId == item.id && isPlaying
                                    ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                    : 'background: linear-gradient(180deg, #a2e3df 0%, #78c9c4 100%)'
                            "
                            @click.stop="toggleMusic(item)">
                            <u-icon
                                :name="currentMusicId == item.id && isPlaying ? 'pause' : 'play-right'"
                                color="#ffffff"
                                size="36" />
                        </view>

                        <view class="flex-1 min-w-0">
                            <text class="text-[28rpx] font-bold text-[#0D1117] line-clamp-1 break-all">{{
                                item.name
                            }}</text>
                            <view
                                v-if="currentMusicId == item.id && isPlaying"
                                class="flex items-end gap-[4rpx] mt-[8rpx]">
                                <view
                                    class="w-[6rpx] bg-primary rounded-full wave-bar"
                                    style="animation-delay: 0ms; height: 16rpx" />
                                <view
                                    class="w-[6rpx] bg-primary rounded-full wave-bar"
                                    style="animation-delay: 150ms; height: 24rpx" />
                                <view
                                    class="w-[6rpx] bg-primary rounded-full wave-bar"
                                    style="animation-delay: 300ms; height: 12rpx" />
                                <view
                                    class="w-[6rpx] bg-primary rounded-full wave-bar"
                                    style="animation-delay: 100ms; height: 20rpx" />
                                <view
                                    class="w-[6rpx] bg-primary rounded-full wave-bar"
                                    style="animation-delay: 250ms; height: 16rpx" />
                                <text class="text-[20rpx] text-primary font-semibold ml-[6rpx]">播放中</text>
                            </view>
                            <text v-else class="text-[22rpx] text-[#9CA3AF] mt-[4rpx] block">点击播放预览</text>
                        </view>

                        <view class="flex-shrink-0">
                            <view
                                v-if="isChoose(item)"
                                class="w-[44rpx] h-[44rpx] rounded-full flex items-center justify-center shadow-[0_2rpx_8rpx_rgba(0,101,251,0.4)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="checkmark" color="#fff" size="22" />
                            </view>
                            <view
                                v-else
                                class="w-[44rpx] h-[44rpx] rounded-full border-2 border-solid border-[#E5E9F0] bg-[#F7F9FC]" />
                        </view>
                    </view>
                </view>

                <template #empty v-if="!isShowAi">
                    <view class="flex flex-col items-center justify-center py-[120rpx]">
                        <view
                            class="w-[200rpx] h-[200rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[28rpx]">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-[28rpx] flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="music" color="#fff" size="44" />
                            </view>
                        </view>
                        <text class="text-[28rpx] font-extrabold text-[#0D1117] mb-[10rpx]">暂无音乐</text>
                        <text class="text-xs text-[#9CA3AF]">点击上方按钮上传音乐</text>
                    </view>
                </template>
            </z-paging>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] flex items-center gap-[16rpx]">
            <view
                class="w-[96rpx] h-[96rpx] rounded-[24rpx] flex flex-col items-center justify-center flex-shrink-0"
                :class="
                    chooseList.length > 0 || isAiMusic
                        ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                        : 'bg-[#F0F2F5]'
                ">
                <text
                    class="text-[32rpx] font-extrabold"
                    :class="chooseList.length > 0 || isAiMusic ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ isAiMusic ? "AI" : chooseList.length }}
                </text>
                <text
                    class="text-[20rpx] font-semibold"
                    :class="chooseList.length > 0 || isAiMusic ? 'text-primary' : 'text-[#C0C4CC]'">
                    已选
                </text>
            </view>

            <view
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden transition-all duration-200 shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleConfirm">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">确定</text>
            </view>
        </view>
    </view>

    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />

    <popup-bottom v-model="showVolumePopup" title="修改音量" height="35%" :is-disabled-touch="true">
        <template #content>
            <view class="h-full flex flex-col px-4 pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                <view class="grow min-h-0 flex flex-col justify-center">
                    <view class="flex items-center justify-center mb-[32rpx]">
                        <view class="flex items-end gap-[6rpx]">
                            <text class="text-[72rpx] font-extrabold text-primary leading-none">
                                {{ Math.round(changeVolume) }}
                            </text>
                            <text class="text-[28rpx] font-semibold text-[#9CA3AF] mb-[8rpx]">%</text>
                        </view>
                    </view>

                    <view class="flex items-center gap-[16rpx] w-full">
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center flex-shrink-0">
                            <u-icon name="volume-off" color="#9CA3AF" size="22" />
                        </view>
                        <view class="flex-1">
                            <slider
                                :value="changeVolume"
                                :min="0"
                                :max="100"
                                height="8"
                                active-color="#0065fb"
                                @change="handleChangeVolume" />
                        </view>
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="volume" color="#0065fb" size="22" />
                        </view>
                    </view>
                </view>

                <view
                    class="w-full h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleConfirmVolume">
                    <text class="text-[30rpx] font-extrabold text-white">确定</text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getMaterialLibraryList, addMaterialLibrary } from "@/api/material";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import useUpload from "@/hooks/useUpload";
import { useAudio } from "@/hooks/useAudio";

const { emit } = useEventBusManager();
const pagingRef = ref<any>(null);

const isShowAi = ref(true);
const isAiMusic = ref(false);
const currentMusicId = ref<any>(null);
const currentVolume = ref(0);
const changeVolume = ref(0);
const showVolumePopup = ref(false);

const dataList = ref<any[]>([]);
const chooseList = ref<any[]>([]);

const queryList = async (page_no: number, page_size: number) => {
    try {
        let { lists } = await getMaterialLibraryList({
            page_no,
            page_size,
            m_type: 6,
        });
        // 通过content判断链接是不是m4a,如果是则过滤掉m4a文件
        lists = lists.filter((item: any) => !item.content.includes(".m4a"));
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const { isPlaying, pauseAll, setUrl, play, pause, stop } = useAudio();

const toggleMusic = (item: any) => {
    const { id, content } = item;
    if (isPlaying.value && currentMusicId.value !== id) {
        pauseAll();
    }
    if (!isPlaying.value) {
        if (currentMusicId.value !== id) {
            setUrl(content);
        }
        play();
        currentMusicId.value = id;
    } else {
        pause();
    }
};

const { uploadAndProcessFiles, uploadMaterialList, showUploadProgress } = useUpload({
    fileAccept: ["mp3", "wav"],
    fileSize: 20,
    onSuccess: async (res: any[]) => {
        uni.showLoading({
            title: "添加中...",
        });
        try {
            // 把数据做成异步函数，然后通过promise.allSettled来执行
            const promises = res.map(async (item) => {
                return await addMaterialLibrary({
                    name: item.name.split(".")[0],
                    content: item.url,
                    size: item.size,
                    pic: "",
                    duration: item.duration,
                    sort: 0,
                    type: 3,
                    m_type: 6,
                });
            });
            await Promise.allSettled(promises);
            pagingRef.value.reload();
            uni.hideLoading();
        } catch (error) {
            uni.hideLoading();
            uni.showToast({
                title: "添加失败",
                icon: "none",
                duration: 3000,
            });
        }
    },
});

const isChoose = (data: any) => {
    return chooseList.value.some((item: any) => item.id == data.id);
};

// 点击 AI 音乐库：与普通音乐互斥，支持取消
const handleChooseAi = () => {
    if (isAiMusic.value) {
        // 已选中则取消
        isAiMusic.value = false;
    } else {
        // 未选中则选中，同时清空普通音乐列表
        isAiMusic.value = true;
        chooseList.value = [];
    }
};

// 点击普通音乐：与 AI 音乐互斥，支持多选/取消
const handleChoose = (data: any) => {
    if (isAiMusic.value) {
        // 已选中 AI 音乐，先取消 AI 音乐再选中普通音乐
        isAiMusic.value = false;
    }
    if (isChoose(data)) {
        chooseList.value = chooseList.value.filter((item: any) => item.id != data.id);
    } else {
        chooseList.value.push(data);
    }
};

const formatVolume = (volume: number) => {
    return Math.round(volume * 100) + "%";
};

const handleChangeVolume = (e: any) => {
    const { value } = e.detail;
    changeVolume.value = value;
};

const handleConfirmVolume = () => {
    currentVolume.value = changeVolume.value / 100;
    showVolumePopup.value = false;
};

const handleConfirm = () => {
    // 允许不选择音乐直接确定，此时 music 为空数组，isAiMusic 为 false
    emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_MUSIC,
        data: {
            music: isAiMusic.value ? -1 : chooseList.value,
            volume: currentVolume.value.toFixed(1),
        },
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    const { volume, music, is_ai, ai_music } = options;
    if (music && music.length > 0) {
        chooseList.value = JSON.parse(music);
    }
    if (volume) {
        currentVolume.value = parseFloat(volume);
        changeVolume.value = currentVolume.value * 100;
    }
    if (is_ai == "0") {
        isShowAi.value = false;
    }
    isAiMusic.value = ai_music == "true";
});

onUnload(() => {
    pauseAll();
    stop();
});
</script>

<style scoped lang="scss">
.bg-1 {
    background: linear-gradient(180deg, rgba(143, 168, 247, 1) 0%, rgba(63, 105, 242, 1) 100%);
}
.bg-2 {
    background: linear-gradient(180deg, rgba(162, 227, 223, 1) 0%, rgba(120, 201, 196, 1) 100%);
}
.wave-bar {
    animation: wave 0.8s ease-in-out infinite alternate;
}
@keyframes wave {
    0% {
        transform: scaleY(0.4);
    }
    100% {
        transform: scaleY(1.2);
    }
}
</style>
