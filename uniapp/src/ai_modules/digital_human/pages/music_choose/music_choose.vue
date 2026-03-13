<template>
    <view class="h-screen flex flex-col bg-white">
        <view class="grid grid-cols-2 gap-x-[20rpx] px-[26rpx] pt-4">
            <view
                class="flex items-center justify-center gap-x-[14rpx] border border-solid border-[#000000]/10 rounded-[10rpx] p-[20rpx]"
                @click="uploadAndProcessFiles('file')">
                <image src="@/ai_modules/digital_human/static/icons/upload.svg" class="w-[28rpx] h-[28rpx]"></image>
                <text class="font-medium">上传音乐</text>
            </view>
            <view
                class="flex items-center justify-between gap-x-[14rpx] border border-solid border-[#000000]/10 rounded-[10rpx] p-[20rpx]"
                @click="showVolumePopup = true">
                <view class="flex items-center gap-x-[14rpx]">
                    <image src="@/ai_modules/digital_human/static/icons/volume.svg" class="w-[28rpx] h-[28rpx]"></image>
                    <text class="font-medium">音量</text>
                </view>
                <view>
                    <text class="text-primary font-medium">{{ formatVolume(currentVolume) }}</text>
                    <u-icon name="arrow-right" size="20" color="#B2B2B2"></u-icon>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-6">
            <z-paging v-model="dataList" ref="pagingRef" :hide-empty-view="isShowAi" :fixed="false" @query="queryList">
                <view class="flex flex-col gap-y-[48rpx] px-4">
                    <view v-if="isShowAi" class="flex items-center gap-x-[20rpx]" @click="handleChoose(-1)">
                        <view
                            class="w-[90rpx] h-[90rpx] rounded-[20rpx] flex items-center justify-center bg-1"
                            style="">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/ai.png"
                                class="w-[44rpx] h-[44rpx]"></image>
                        </view>
                        <view class="flex-1 line-clamp-1 break-all text-[30rpx] font-medium"> 使用AI音乐库 </view>
                        <view class="shrink-0 w-[60rpx]">
                            <image
                                src="/static/images/icons/success.svg"
                                class="w-[40rpx] h-[40rpx]"
                                v-if="isAiMusic"></image>
                            <view
                                class="w-[40rpx] h-[40rpx] border border-solid border-[#000000]/10 rounded-[100rpx]"
                                v-else></view>
                        </view>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="flex items-center gap-x-[20rpx]"
                        @click="handleChoose(item)">
                        <view
                            class="w-[90rpx] h-[90rpx] rounded-[20rpx] flex items-center justify-center bg-2"
                            @click.stop="toggleMusic(item)">
                            <image
                                v-if="currentMusicId == item.id && isPlaying"
                                src="@/ai_modules/digital_human/static/images/common/audio_pause.png"
                                class="w-[44rpx] h-[44rpx]"></image>
                            <image
                                v-else
                                src="@/ai_modules/digital_human/static/images/common/audio_play.png"
                                class="w-[44rpx] h-[44rpx]"></image>
                        </view>
                        <view class="flex-1 line-clamp-1 break-all">
                            <text class="text-[30rpx] font-medium">{{ item.name }}</text>
                        </view>
                        <view class="shrink-0 w-[60rpx]">
                            <image
                                src="/static/images/icons/success.svg"
                                class="w-[40rpx] h-[40rpx]"
                                v-if="isChoose(item)"></image>
                            <view
                                v-else
                                class="w-[40rpx] h-[40rpx] border border-solid border-[#000000]/10 rounded-[100rpx]"></view>
                        </view>
                    </view>
                </view>
                <template #empty v-if="!isShowAi">
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="flex items-center justify-between px-[26rpx] py-4">
            <view class="font-medium text-[#000000]/50">已选：{{ chooseList.length }}</view>
            <view
                class="w-[440rpx] h-[90rpx] bg-black rounded-[20rpx] flex items-center justify-center font-medium text-white text-[30rpx]"
                @click="handleConfirm">
                确定
            </view>
        </view>
    </view>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <popup-bottom v-model="showVolumePopup" title="修改音量" height="35%" :is-disabled-touch="true">
        <template #content>
            <view class="h-full flex flex-col p-4">
                <view class="grow min-h-0">
                    <view class="flex items-center gap-x-4 w-full mt-[70rpx]">
                        <text>0</text>
                        <view class="flex-1">
                            <slider
                                :value="changeVolume"
                                :min="0"
                                :max="100"
                                height="12"
                                active-color="#0065fb"
                                @change="handleChangeVolume">
                            </slider>
                        </view>
                        <text>100</text>
                    </view>
                </view>
                <view
                    class="w-full h-[90rpx] bg-black rounded-[20rpx] flex items-center justify-center font-medium text-white text-[30rpx]"
                    @click="handleConfirmVolume">
                    确定
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
const isAiMusic = ref(true);
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

const handleChoose = (data: any) => {
    if (data == -1) {
        isAiMusic.value = true;
        chooseList.value = [];
        return;
    }
    isAiMusic.value = false;
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
    if (chooseList.value.length == 0 && !isAiMusic.value) {
        uni.showToast({
            title: "请选择音乐",
            icon: "none",
        });
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_MUSIC,
        data: {
            music: isAiMusic.value ? [] : chooseList.value,
            volume: currentVolume.value.toFixed(1),
        },
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    const { volume, music, is_ai } = options;
    if (music && music.length > 0) {
        chooseList.value = JSON.parse(music);
        isAiMusic.value = chooseList.value.length == 0;
    }
    if (volume) {
        currentVolume.value = parseFloat(volume);
        changeVolume.value = currentVolume.value * 100;
    }
    if (is_ai == "0") {
        isShowAi.value = false;
    }
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
</style>
