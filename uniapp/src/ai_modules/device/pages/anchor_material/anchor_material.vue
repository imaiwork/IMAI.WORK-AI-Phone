<template>
    <view class="h-screen flex flex-col">
        <view class="font-bold text-[30rpx] mx-4 mt-4">数字人形象({{ dataList.length }})</view>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="grid grid-cols-3 gap-[26rpx] p-4 pb-[100rpx]">
                    <view
                        class="h-[250rpx] bg-white rounded-[20rpx] flex flex-col items-center justify-center"
                        @click="showChooseAnchor = true">
                        <image src="@/ai_modules/device/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-[28rpx] text-[#000000b3] mt-1">添加</text>
                    </view>
                    <view v-for="(item, index) in dataList" :key="index" class="h-[250rpx] relative">
                        <image :src="item.pic" class="w-full h-full rounded-[20rpx]" mode="aspectFill"></image>
                        <view
                            class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                            @click="handleDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16"></u-icon>
                        </view>
                        <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                            <image
                                src="/static/images/icons/play.svg"
                                class="w-[48rpx] h-[48rpx]"
                                @click="previewVideo(item)"></image>
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

    <choose-anchor ref="chooseAnchorRef" v-model="showChooseAnchor" @confirm="handleChooseAnchor" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import ChooseAnchor from "@/ai_modules/device/components/choose-anchor/choose-anchor.vue";
import useMaterialStore from "@/ai_modules/device/stores/material";

const materialStore = useMaterialStore();
const { anchorList } = storeToRefs(materialStore);

const chooseAnchorRef = shallowRef<InstanceType<typeof ChooseAnchor>>();

const dataList = ref<any[]>(JSON.parse(JSON.stringify(anchorList.value)));
const showChooseAnchor = ref(false);
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

const handleChooseAnchor = (list: any[]) => {
    dataList.value.push(...list);
};

const handleDelete = (index: number) => {
    dataList.value.splice(index, 1);
};

const previewVideo = (item: any) => {
    playData.value = { url: item.url, pic: item.pic };
    showVideoPreview.value = true;
};

const handleConfirm = () => {
    if (dataList.value.length === 0) {
        uni.$u.toast("请至少选择一个数字人形象");
        return;
    }
    materialStore.setList("anchorList", dataList.value);
    uni.navigateBack();
};

onShow(() => {
    chooseAnchorRef.value?.queryList();
});
</script>

<style scoped></style>
