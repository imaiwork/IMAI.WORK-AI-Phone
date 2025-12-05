<template>
    <view class="h-screen flex flex-col bg-[#F9FAFB]">
        <view class="border-[0] border-b-[1rpx] border-solid border-[#0000000d] px-3">
            <u-tabs
                :list="tabs"
                :current="currentTab"
                height="100"
                bar-width="108"
                font-size="26rpx"
                bg-color="#F9FAFB"
                gutter="40"
                active-color="#000000"
                inactive-color="#00000080"
                :bar-style="{
                    backgroundColor: 'var(--color-primary)',
                    height: '4rpx',
                    bottom: '-5rpx',
                }"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="grow min-h-0 relative">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-[32rpx] pb-[48rpx] mt-4 flex flex-col gap-[24rpx]">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-white rounded-xl h-[108rpx] flex items-center justify-between px-4 gap-x-3"
                        :class="{
                            'shadow-[0_0_0_1px_var(--color-primary)]': isChoose(item.id),
                        }"
                        @click="handleChooseAudio(item)">
                        <view class="flex items-center gap-x-3">
                            <view
                                class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center p-[6rpx]"
                                :class="[isChoose(item.id) ? 'bg-primary' : 'bg-[#0000000d]']">
                                <image
                                    v-if="isChoose(item.id)"
                                    src="@/ai_modules/digital_human/static/icons/music_white.svg"
                                    class="w-full h-full"></image>
                                <image
                                    v-else
                                    src="@/ai_modules/digital_human/static/icons/music_black.svg"
                                    class="w-full h-full"></image>
                            </view>
                            <text class="line-clamp-1">{{ item.name }}</text>
                        </view>
                        <view class="flex-shrink-0 flex items-center gap-x-3">
                            <view
                                :class="isChoose(item.id) ? 'text-primary' : 'text-[#00000080]'"
                                @click="togglePlay(item)"
                                >{{ isPlaying && currAudioId == item.id ? "暂停" : "播放" }}</view
                            >
                            <view class="w-[28rpx] h-[28rpx]">
                                <image
                                    v-if="isChoose(item.id)"
                                    src="@/ai_modules/digital_human/static/icons/radio_s.svg"
                                    class="w-full h-full"></image>
                                <image
                                    v-else
                                    src="@/ai_modules/digital_human/static/icons/radio.svg"
                                    class="w-full h-full"></image>
                            </view>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
            <view
                class="absolute w-full left-0 right-0 bottom-6 flex justify-center z-10"
                v-if="tabs[currentTab].value == '0'">
                <view
                    class="rounded-full h-[100rpx] w-[332rpx] shadow-[0_0_0_1px_rgba(0,101,251,0.1)] text-primary flex items-center justify-center"
                    @click="handleUpload"
                    >本地上传音乐</view
                >
            </view>
        </view>
        <view class="h-[200rpx] px-[60rpx] pt-4 bg-white shadow-[0_0_12rpx_6rpx_rgba(0,0,0,0.05)]">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{
                    width: '100%',
                    height: '100rpx',
                    boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                    fontSize: '26rpx',
                }"
                @click="handleConfirm"
                >确定</u-button
            >
        </view>
    </view>
</template>

<script setup lang="ts">
import { getMaterialMusicList, getMaterialLibraryList, addMaterialMusic } from "@/api/material";
import { uploadFile } from "@/api/app";
import { useAudio } from "@/hooks/useAudio";
import { ChooseResult, chooseFile } from "@/components/file-upload/choose-file";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";

const tabs = [
    {
        name: "系统",
        value: "system",
    },
    {
        name: "我的",
        value: "0",
    },
    {
        name: "科技",
        value: "1",
    },
    {
        name: "悬疑",
        value: "2",
    },
    {
        name: "抒情",
        value: "3",
    },
    {
        name: "欢快",
        value: "4",
    },
    {
        name: "古典",
        value: "5",
    },
    {
        name: "跳跃",
        value: "6",
    },
];

const currentTab = ref(0);

const handleTabChange = (index: number) => {
    currentTab.value = index;
    pagingRef.value?.reload();
};

const dataLists = ref<any[]>([]);
const pagingRef = ref();

const queryList = async (page_no: number, page_size: number) => {
    try {
        const tabKey = tabs[currentTab.value].value;
        let lists = [];
        if (tabKey == "system") {
            ({ lists } = await getMaterialLibraryList({
                m_type: 6,
                page_no,
                page_size,
            }));
            lists = lists.map((item: any) => ({
                ...item,
                url: item.content,
                id: `${item.id}-system`,
            }));
        } else {
            ({ lists } = await getMaterialMusicList({
                page_no,
                page_size,
                style: tabKey,
            }));
        }
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleUpload = async () => {
    const fileResult = await chooseFile({
        count: 9,
        type: "file",
        extension: [".mp3", ".wav"],
    });
    chooseFileCallback(fileResult);
};

// 选择文件回调
const chooseFileCallback = async (filesResult: ChooseResult) => {
    const { tempFiles } = filesResult;
    tempFiles.forEach((item: any) => {
        if (item.size > 20 * 1024 * 1024) {
            uni.$u.toast(`文件包含超过20M，无法上传`);
        } else {
            uploadFileFn(item);
        }
    });
};

// 上传文件
const uploadFileFn = async (file: any) => {
    const showLoading = (title: string) => uni.showLoading({ title, mask: true });
    const showToast = (title: string, duration = 5000) => uni.showToast({ title, icon: "none", duration });

    try {
        // 上传文件
        showLoading("上传中");
        const { uri }: any = await uploadFile("audio", { filePath: file.path });
        uni.hideLoading();

        // 添加音乐
        await addMaterialMusic({
            url: uri,
            name: file.name,
            type: "0",
        });
        uni.$u.toast("上传成功");
        pagingRef.value?.reload();
    } catch (error: any) {
        showToast(error || "解析失败");
    } finally {
        uni.hideLoading();
    }
};

const currAudioId = ref();
const { play, setUrl, pause, pauseAll, isPlaying } = useAudio();

const togglePlay = ({ id, url }: any) => {
    if (isPlaying.value && currAudioId.value !== id) {
        pauseAll();
    }

    if (!isPlaying.value) {
        if (currAudioId.value !== id) {
            setUrl(url);
        }
        play();
        currAudioId.value = id;
    } else {
        pause();
    }
};

const chooseAudio = ref();

const isChoose = (dataId: any) => {
    return chooseAudio.value?.id == dataId;
};

const handleChooseAudio = (data: any) => {
    if (isChoose(data.id)) {
        chooseAudio.value = "";
    } else {
        chooseAudio.value = data;
    }
};

const handleConfirm = () => {
    if (chooseAudio.value.length == 0) {
        uni.$u.toast("请选择背景音乐");
        return;
    }
    uni.$emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_MUSIC,
        data: chooseAudio.value,
    });
    uni.navigateBack();
};

onLoad((options) => {
    uni.setNavigationBarColor({
        frontColor: "#000000",
        backgroundColor: "#F9FAFB",
    });
});
</script>

<style scoped></style>
