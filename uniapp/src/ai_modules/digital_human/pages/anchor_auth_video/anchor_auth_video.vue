<template>
    <view class="h-screen bg-[#F7F9FC] flex flex-col">
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4 pt-[16rpx] pb-[120rpx]">
                    <view class="flex items-center justify-between mb-[16rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">选择授权视频</text>
                        </view>
                        <view
                            class="flex items-center gap-[6rpx] px-[16rpx] py-[8rpx] rounded-full transition-all duration-200"
                            :class="chooseVideo.id ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                            <text
                                class="text-[22rpx] font-semibold"
                                :class="chooseVideo.id ? 'text-primary' : 'text-[#9CA3AF]'">
                                {{ chooseVideo.id ? "已选 1" : "未选择" }}
                            </text>
                        </view>
                    </view>

                    <view class="grid grid-cols-3 gap-[12rpx]">
                        <view
                            v-for="item in dataList"
                            :key="item.id"
                            class="relative rounded-[20rpx] overflow-hidden w-full h-[276rpx]"
                            @click="handleChoose(item)">
                            <image :src="item.authorized_pic" class="w-full h-full" mode="aspectFill" />

                            <view
                                class="absolute inset-0 transition-all duration-200"
                                :class="isChoose(item) ? 'bg-[#0065fb]/20' : 'bg-[#000000]/10'" />

                            <view
                                class="absolute top-1/2 left-1/2"
                                style="transform: translate(-50%, -50%)"
                                @click.stop="previewVideo(item.authorized_url)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/video_play.svg"
                                    class="w-[64rpx] h-[64rpx]" />
                            </view>

                            <view
                                v-if="isChoose(item)"
                                class="absolute top-[10rpx] right-[10rpx] w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center shadow-[0_2rpx_8rpx_rgba(0,101,251,0.4)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="checkmark" color="#fff" size="20" />
                            </view>

                            <view
                                v-else
                                class="absolute top-[10rpx] right-[10rpx] w-[40rpx] h-[40rpx] rounded-full border-2 border-solid border-[#ffffff]/70 bg-[#000000]/20" />
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="flex flex-col items-center justify-center py-[120rpx]">
                        <view
                            class="w-[200rpx] h-[200rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[28rpx]">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-[28rpx] flex items-center justify-center shadow-[0_6rpx_20rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                <u-icon name="video-camera" color="#fff" size="44" />
                            </view>
                        </view>
                        <text class="text-[28rpx] font-extrabold text-[#0D1117] mb-[10rpx]">暂无授权视频</text>
                        <text class="text-xs text-[#9CA3AF]">请先上传授权视频</text>
                    </view>
                </template>
            </z-paging>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden transition-all duration-200"
                :class="chooseVideo.id ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                :style="
                    chooseVideo.id
                        ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                        : 'background: #C0C4CC'
                "
                @click="handleConfirm">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">确认选择</text>
            </view>
        </view>
    </view>

    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :video-url="previewVideoUrl"
        @confirm="showVideoPreview = false" />
</template>
<script setup lang="ts">
import { shanjianAnchorAuthorizedList } from "@/api/digital_human";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import VideoPreview from "@/components/video-preview/video-preview.vue";

const { emit } = useEventBusManager();

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
        const { lists } = await shanjianAnchorAuthorizedList({
            page_no: page_no,
            page_size: page_size,
        });
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
    emit("confirm", {
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
