<template>
    <view class="h-screen flex flex-col">
        <view class="font-bold text-[30rpx] mx-4 mt-4">剪辑素材({{ dataList.length }})</view>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-[26rpx] p-4 pb-[100rpx]">
                    <view
                        class="aspect-[3/4] bg-white rounded-[20rpx] flex flex-col items-center justify-center"
                        @click="chooseUploadType">
                        <image src="@/ai_modules/device/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-[28rpx] text-[#000000b3] mt-1">添加</text>
                    </view>
                    <view
                        v-for="(item, index) in dataList"
                        :key="index"
                        class="aspect-[3/4] relative rounded-[12rpx]"
                        @click="handlePreview(item)">
                        <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click.stop="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                        <view
                            v-if="item.type === 'video'"
                            class="absolute top-0 left-0 h-full w-full z-[89] flex items-center justify-center">
                            <image src="/static/images/icons/play.svg" class="w-[48rpx] h-[48rpx]"></image>
                        </view>
                        <view
                            class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] rounded-b-[12rpx] flex items-center justify-center z-[88]">
                            <image
                                v-if="item.type === 'image'"
                                src="@/ai_modules/digital_human/static/icons/pic.svg"
                                class="w-[24rpx] h-[24rpx]"></image>
                            <image
                                v-else
                                src="@/ai_modules/digital_human/static/icons/video.svg"
                                class="w-[24rpx] h-[24rpx]"></image>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white px-6 pt-4 pb-5">
            <u-button
                type="primary"
                :custom-style="{ height: '100rpx', borderRadius: '16rpx', fontWeight: 'bold' }"
                @click="handleConfirm"
                >确定保存</u-button
            >
        </view>
    </view>
    <choose-history v-model="showHistory" :type="showHistoryType" :limit="9999" @select="handleSelectHistory" />
    <choose-material v-model="showChooseMaterial" type="video" @select="handleChooseMaterial" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import useMaterialStore from "@/ai_modules/device/stores/material";
import useMontageMaterial from "@/hooks/useMontageMaterial";
import ChooseHistory from "@/ai_modules/device/components/choose-history/choose-history.vue";

const materialStore = useMaterialStore();
const { videoList } = storeToRefs(materialStore);
const dataList = ref<any[]>(JSON.parse(JSON.stringify(videoList.value)));
const replaceMaterialIndex = ref(-1);
const showChooseMaterial = ref(false);
const showHistory = ref(false);
const showHistoryType = ref<"video" | "image">("video");
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useMontageMaterial({
    isTranscode: true,
    count: 9,
    onSuccess: (res: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            dataList.value[replaceMaterialIndex.value] = res[0];
        } else {
            dataList.value.push(...res);
        }
        replaceMaterialIndex.value = -1;
    },
});

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ["选择图片素材", "选择视频素材", '从"创作中心视频素材"中选择', '从"创作中心图片素材"中选择'],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("image");
            else if (res.tapIndex === 1) uploadAndProcessFiles("video");
            else if (res.tapIndex === 2 || res.tapIndex === 3) {
                showHistoryType.value = res.tapIndex === 2 ? "video" : "image";
                showHistory.value = true;
            }
        },
    });
};

const handleChooseMaterial = (list: any[]) => {
    if (replaceMaterialIndex.value !== -1) {
        dataList.value[replaceMaterialIndex.value] = list[0];
    } else {
        dataList.value.push(...list.map((item) => ({ pic: item.pic, url: item.content, type: "video" })));
    }
    replaceMaterialIndex.value = -1;
};

const handleSelectHistory = (list: any[]) => {
    if (replaceMaterialIndex.value !== -1) {
        if (showHistoryType.value === "video") {
            dataList.value[replaceMaterialIndex.value] = list.map((item) => ({
                pic: item.pic,
                url: item.clip_result_url || item.video_result_url,
                type: "video",
            }));
        } else {
            dataList.value[replaceMaterialIndex.value] = list.map((item) => ({
                pic: item.pic,
                url: item.content,
                type: "image",
            }));
        }
    } else {
        if (showHistoryType.value === "video") {
            dataList.value.push(
                ...list.map((item) => ({
                    pic: item.pic,
                    url: item.clip_result_url || item.video_result_url,
                    type: "video",
                }))
            );
        } else {
            dataList.value.push(...list.map((item) => ({ pic: item.image, url: item.image, type: "image" })));
        }
    }
    replaceMaterialIndex.value = -1;
};

const handleReplace = (index: number) => {
    replaceMaterialIndex.value = index;

    chooseUploadType();
};

const handleDelete = (index: number) => {
    dataList.value.splice(index, 1);
};

const handlePreview = (item: any) => {
    if (item.type === "image") {
        uni.previewImage({
            urls: [item.pic],
        });
    } else {
        playData.value = { url: item.url, pic: item.pic };
        showVideoPreview.value = true;
    }
};

const handleConfirm = () => {
    if (dataList.value.length <= 0) {
        uni.$u.toast("请至少选择一个素材");
        return;
    }
    materialStore.setList("videoList", dataList.value);
    uni.navigateBack();
};
</script>

<style scoped></style>
