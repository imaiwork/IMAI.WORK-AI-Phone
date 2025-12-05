<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            title="混剪自动发布任务"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>

        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="dataLists"
                :fixed="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-y-4 p-4">
                    <view
                        v-for="(item, index) in dataLists"
                        :key="index"
                        class="bg-white rounded-[16rpx] px-4 py-[28rpx]">
                        <view class="flex justify-between w-full gap-x-2">
                            <view class="flex items-center gap-x-4 flex-1">
                                <view class="w-[88rpx] h-[88rpx] relative flex-shrink-0">
                                    <image :src="item.avatar" class="w-full h-full rounded-full" />
                                    <image
                                        :src="getIcon(item.type)"
                                        class="absolute bottom-0 right-0 w-[32rpx] h-[32rpx]"></image>
                                </view>
                                <view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="font-bold line-clamp-1">{{ item.nickname }}</view>
                                    </view>
                                    <view class="flex items-center gap-x-1 mt-[12rpx]">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/phone.svg"
                                            class="w-[32rpx] h-[32rpx]"></image>
                                        <view class="text-[#00000080] text-xs">{{ item.device_model }}</view>
                                    </view>
                                </view>
                            </view>
                            <view>
                                <view
                                    class="px-[16rpx] py-[8rpx] rounded-[8rpx] text-[20rpx] font-bold"
                                    :class="[
                                        item.status === 1
                                            ? 'text-[#00B862] bg-[#E0F1EB]'
                                            : item.status === 2
                                            ? 'text-[#F63E2F] bg-[#FDEBEA]'
                                            : 'text-[#F5922F] bg-[#FEF4EB]',
                                    ]"
                                    >{{ item.status === 1 ? "成功" : item.status === 2 ? "失败" : "等待发布" }}</view
                                >
                            </view>
                        </view>
                        <view class="bg-[#F6F9FF] rounded-[16rpx] p-[28rpx] flex gap-x-4 mt-[28rpx]">
                            <view class="w-[178rpx] h-[250rpx] rounded-[16rpx] overflow-hidden flex-shrink-0 relative">
                                <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                <view class="absolute left-0 top-0 w-full h-full flex items-center justify-center">
                                    <view
                                        class="rounded-full bg-[#ffffff33] w-[48rpx] h-[48rpx]"
                                        style="backdrop-filter: blur(5px)"
                                        @click="handlePlay(item)">
                                        <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                    </view>
                                </view>
                            </view>
                            <view class="flex flex-col justify-between">
                                <view>
                                    <view class="line-clamp-2 break-all">{{ item.material_title }}</view>
                                    <view class="line-clamp-3 text-[#00000080] text-[20rpx] mt-[16rpx] break-all">{{
                                        item.material_subtitle
                                    }}</view>
                                </view>
                                <view class="text-[#00000099] text-xs mt-[16rpx]">
                                    发布时间：{{ item.publish_time }}
                                </view>
                            </view>
                        </view>
                        <view
                            v-if="item.status === 2 && item.remark"
                            class="text-[#F63E2F] text-xs mt-[16rpx] break-all">
                            失败原因：{{ item.remark }}
                        </view>
                    </view>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
    <video-preview-v2
        v-model:show="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview-v2>
</template>

<script setup lang="ts">
import { getPublishRecordVideoList } from "@/api/digital_human";
import { AppTypeEnum } from "@/enums/appEnums";
import XHSIcon from "@/ai_modules/digital_human/static/images/common/xhs.png";
import SPHIcon from "@/ai_modules/digital_human/static/images/common/sph.png";
import VideoPreviewV2 from "@/ai_modules/digital_human/components/video-preview-v2/video-preview-v2.vue";
const dataLists = ref<any[]>([]);

const pagingRef = shallowRef();
const recordId = ref("");

const showVideoPreview = ref(false);
const playItem = reactive<any>({
    url: "",
    pic: "",
});

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getPublishRecordVideoList({
            id: recordId.value,
            page_no,
            page_size,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const getIcon = (type: string) => {
    switch (parseInt(type)) {
        case AppTypeEnum.XHS:
            return XHSIcon;
        case AppTypeEnum.SPH:
            return SPHIcon;
    }
};

const handlePlay = (item: any) => {
    playItem.url = item.material_url;
    playItem.pic = item.pic;
    showVideoPreview.value = true;
};

onLoad(({ id }: any) => {
    recordId.value = id;
});
</script>
<style scoped lang="scss"></style>
