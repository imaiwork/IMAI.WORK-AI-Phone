<template>
    <view class="h-screen bg-white flex flex-col">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="grid grid-cols-3 gap-3 p-4">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="h-[276rpx] rounded-xl relative"
                        @click="handleChoose(item)">
                        <image :src="item.authorized_pic" class="w-full h-full rounded-xl" mode="aspectFill"></image>
                        <view class="absolute top-[50%] left-[50%]" style="transform: translate(-50%, -50%)">
                            <view @click.stop="previewVideo(item.authorized_url)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/video_play.svg"
                                    class="w-[60rpx] h-[60rpx]"></image>
                            </view>
                        </view>
                        <view class="absolute top-2 right-2" v-if="isChoose(item)">
                            <image
                                src="@/ai_modules/digital_human/static/icons/success.svg"
                                class="w-[28rpx] h-[28rpx]"></image>
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
        <view class="flex-shrink-0 px-4 pb-6">
            <view
                class="h-[100rpx] text-white flex items-center justify-center rounded-[8rpx]"
                :class="[chooseVideo.id ? 'bg-black' : 'bg-[#787878CC]']"
                @click="handleConfirm">
                确认选择
            </view>
        </view>
    </view>
    <video-preview
        v-model:show="showVideoPreview"
        title="视频预览"
        :video-url="previewVideoUrl"
        @confirm="showVideoPreview = false" />
</template>

<script setup lang="ts">
import { shanjianAnchorAuthorizedList } from "@/api/digital_human";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";

const dataList = ref<any[]>([]);
const pagingRef = ref<any>(null);
const chooseVideo = ref<any>({});

const previewVideoUrl = ref<string>("");
const showVideoPreview = ref(false);

const isChoose = (data: any) => {
    return chooseVideo.value.id === data.id;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await shanjianAnchorAuthorizedList({ page_no: page_no, page_size: page_size });
        pagingRef.value.complete(lists);
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

const previewVideo = (url: string) => {
    if (!url) return;
    showVideoPreview.value = true;
    previewVideoUrl.value = url;
};

const handleChoose = (item: any) => {
    if (chooseVideo.value.id === item.id) {
        chooseVideo.value = {};
    } else {
        chooseVideo.value = item;
    }
};

const handleConfirm = () => {
    if (!chooseVideo.value) return;
    uni.navigateBack();
    uni.$emit("confirm", {
        type: ListenerTypeEnum.ANCHOR_AUTH,
        data: {
            url: chooseVideo.value.authorized_url,
            pic: chooseVideo.value.authorized_pic,
            name: chooseVideo.value.name,
        },
    });
};
</script>

<style scoped></style>
