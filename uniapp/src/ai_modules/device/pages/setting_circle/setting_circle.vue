<template>
    <view class="h-screen flex flex-col">
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="p-4">
                    <view>
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold">素材设置</view>
                            <view
                                class="text-xs font-bold"
                                :class="isAllConfigComplete ? 'text-primary' : 'text-[#FF2442]'">
                                {{ isAllConfigComplete ? "配置完成" : "配置未完成" }}
                            </view>
                        </view>
                        <view class="mt-[18rpx] bg-white rounded-[20rpx] px-[36rpx]">
                            <view
                                v-for="(material, materialIndex) in materialList"
                                :key="materialIndex"
                                class="flex items-center justify-between border-[0] border-solid border-[#F2F2F2] py-[30rpx]"
                                :class="[
                                    materialIndex === materialList.length - 1 ? 'border-b-[0rpx]' : 'border-b-[1rpx]',
                                ]"
                                @click="toPage(material.page)">
                                <text class="font-bold text-[28rpx]">{{ material.name }} </text>
                                <view class="flex items-center gap-x-1">
                                    <text
                                        class="font-bold text-xs"
                                        :class="[material.list.length > 0 ? 'text-primary' : 'text-[#FF2442]']"
                                        >{{ material.list.length ? `${material.list.length}(个)` : "未配置" }}</text
                                    >
                                    <u-icon name="arrow-right" color="#B2B2B2" size="20"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]">
                        <view class="flex items-center justify-between">
                            <view class="text-[30rpx] font-bold">发布设置</view>
                        </view>
                        <view class="mt-[18rpx] bg-white rounded-[20rpx] px-[36rpx] py-[30rpx]">
                            <view class="border-[0] border-b-[1rpx] border-solid border-[#F2F2F2] pb-[30rpx]">
                                <view class="font-bold text-[30rpx]"> 朋友圈可视范围 </view>
                                <view class="mt-[32rpx]">
                                    <u-radio-group v-model="formData.visible_type" class="w-full">
                                        <view class="flex justify-between w-full">
                                            <u-radio
                                                :name="item.value"
                                                :size="28"
                                                v-for="item in [
                                                    { value: 1, label: '全部人可见' },
                                                    { value: 2, label: '仅标签可见' },
                                                    { value: 3, label: '标签不可见' },
                                                ]">
                                                <text class="text-base">{{ item.label }}</text>
                                            </u-radio>
                                        </view>
                                    </u-radio-group>
                                    <view class="mt-[36rpx]" v-if="formData.type === 2">
                                        <view class="text-primary font-bold">标签内容：</view>
                                        <view
                                            class="mt-[16rpx] bg-[#F3F3F3] px-4 rounded-[16rpx] h-[90rpx] flex items-center">
                                            <u-input
                                                class="w-full"
                                                v-model="formData.tag_content"
                                                placeholder="多个标签之间用 ',' 隔开"
                                                maxlength="100"
                                                clearable />
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[32rpx]">
                                <view class="font-bold text-[30rpx]"> 朋友圈防折叠 </view>
                                <view class="mt-[32rpx]">
                                    <u-radio-group v-model="formData.type" class="w-full">
                                        <u-radio :name="1" :size="28">
                                            <text class="text-base">开启</text>
                                        </u-radio>
                                        <u-radio :name="2" :size="28">
                                            <text class="text-base">关闭</text>
                                        </u-radio>
                                    </u-radio-group>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white flex-shrink-0 pb-5 pt-4 px-6">
            <view
                class="rounded-[16rpx] flex-1 h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import useMaterialStore from "@/ai_modules/device/stores/material";

const materialStore = useMaterialStore();

const formData = reactive<{
    anchorList: any[];
    videoList: any[];
    imageList: any[];
    marketingList: any[];
    type: number;
    tag_content: string;
    visible_type: number;
}>({
    anchorList: [],
    videoList: [],
    imageList: [],
    marketingList: [],
    type: 1,
    visible_type: 1,
    tag_content: "",
});

const materialList = ref<{ name: string; list: any[]; page: string }[]>([
    {
        name: "数字人形象",
        list: [],
        page: "anchor_material",
    },
    {
        name: "视频剪辑素材",
        list: [],
        page: "video_material",
    },
    {
        name: "图文剪辑素材",
        list: [],
        page: "image_material",
    },
    {
        name: "营销主题",
        list: [],
        page: "marketing_material",
    },
]);

//检查是全部配置完成
const isAllConfigComplete = computed(() => {
    return (
        formData.anchorList.length > 0 &&
        formData.videoList.length > 0 &&
        formData.imageList.length > 0 &&
        formData.marketingList.length > 0
    );
});

const toPage = (page: string) => {
    uni.navigateTo({
        url: `/ai_modules/device/pages/${page}/${page}`,
    });
};

const setMaterialList = () => {
    for (const item of materialList.value) {
        switch (item.page) {
            case "anchor_material":
                item.list = materialStore.anchorList;
                break;
            case "video_material":
                item.list = materialStore.videoList;
                break;
            case "image_material":
                item.list = materialStore.imageList;
                break;
            case "marketing_material":
                item.list = materialStore.marketingList;
                break;
        }
    }
};

const handleSaveConfig = () => {};

onShow(() => {
    setMaterialList();
});
</script>

<style scoped></style>
