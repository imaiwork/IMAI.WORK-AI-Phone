<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="px-4 mt-4" v-if="false">
            <view class="bg-white rounded-[16rpx] px-[8rpx] w-fit">
                <view class="w-[360rpx] grid grid-cols-2 relative h-[84rpx]">
                    <view
                        v-for="(item, index) in modelTypes"
                        :key="index"
                        class="rounded-[12rpx] font-bold flex items-center justify-center z-10 transition-colors duration-500"
                        :class="{ 'text-white': modelIndex === index }"
                        @click="modelIndex = index">
                        {{ item.name }}
                    </view>
                    <view class="tab-slider" :style="{ transform: `translateX(${modelIndex * 100}%)` }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-4">
            <scroll-view class="h-full" scroll-y>
                <view class="px-4 flex flex-col gap-y-[50rpx] pb-[100rpx]">
                    <template v-if="modelIndex === 0">
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-bold">
                                    <text class="text-[#FF2442]">*</text>
                                    数字人形象({{ easyModeData.anchorList.length }})
                                </view>
                                <view class="text-xs font-bold text-[#00000080]" @click="toPage('anchor_material')">
                                    全部<u-icon name="arrow-right" color="#00000080" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view v-if="easyModeData.anchorList.length > 0" class="grid grid-cols-3 gap-x-[20rpx]">
                                    <view
                                        v-for="(item, index) in easyModeData.anchorList.slice(0, 3)"
                                        :key="index"
                                        class="h-[250rpx] relative">
                                        <image
                                            :src="item.pic"
                                            class="w-full h-full rounded-[20rpx]"
                                            mode="aspectFill"></image>
                                        <view
                                            class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                            <image
                                                src="/static/images/icons/play.svg"
                                                class="w-[48rpx] h-[48rpx]"
                                                @click="previewVideo(item)"></image>
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加数字人形象</view>
                                    <view class="text-primary font-bold" @click="toPage('anchor_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-bold">
                                    <text class="text-[#FF2442]">*</text>
                                    视频剪辑素材({{ easyModeData.videoList.length }})
                                </view>
                                <view class="text-xs font-bold text-[#00000080]" @click="toPage('video_material')">
                                    全部<u-icon name="arrow-right" color="#00000080" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="easyModeData.videoList.length > 0">
                                    <view
                                        v-for="(item, index) in easyModeData.videoList.slice(0, 3)"
                                        :key="index"
                                        class="h-[250rpx] relative overflow-hidden"
                                        @click="handlePreview(item)">
                                        <image
                                            :src="item.pic"
                                            class="w-full h-full rounded-[20rpx]"
                                            mode="aspectFill"></image>
                                        <view
                                            class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] flex items-center justify-center z-[88]">
                                            <image
                                                v-if="item.type === 'image'"
                                                src="@/ai_modules/digital_human/static/icons/pic.svg"
                                                class="w-[24rpx] h-[24rpx]"></image>
                                            <image
                                                v-else
                                                src="@/ai_modules/digital_human/static/icons/video.svg"
                                                class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <view
                                            v-if="item.type === 'video'"
                                            class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                            <image
                                                src="/static/images/icons/play.svg"
                                                class="w-[48rpx] h-[48rpx]"
                                                @click="handlePreview(item)"></image>
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加视频剪辑素材</view>
                                    <view class="text-primary font-bold" @click="toPage('video_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-bold">
                                    <text class="text-[#FF2442]">*</text>
                                    图文剪辑素材({{ easyModeData.imageList.length }})
                                </view>
                                <view class="text-xs font-bold text-[#00000080]" @click="toPage('image_material')">
                                    全部<u-icon name="arrow-right" color="#00000080" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="easyModeData.imageList.length > 0">
                                    <view
                                        class="h-[200rpx] rounded-[20rpx]"
                                        v-for="(item, index) in easyModeData.imageList.slice(0, 3)"
                                        :key="index">
                                        <image
                                            :src="item.pic"
                                            class="w-full h-full rounded-[20rpx]"
                                            mode="aspectFill"></image>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加图文剪辑素材</view>
                                    <view class="text-primary font-bold" @click="toPage('image_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view v-if="false">
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-bold">
                                    <text class="text-[#FF2442]">*</text>
                                    营销主题({{ easyModeData.marketingList.length }})
                                </view>
                                <view class="text-xs font-bold text-[#00000080]" @click="toPage('marketing_material')">
                                    全部<u-icon name="arrow-right" color="#00000080" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view class="flex flex-wrap gap-2" v-if="easyModeData.marketingList.length > 0">
                                    <view
                                        v-for="(item, index) in easyModeData.marketingList.slice(0, 5)"
                                        :key="index"
                                        class="relative rounded-[20rpx] border border-solid border-[#E5E5E5] px-2 py-[12rpx] flex items-center gap-x-2 font-bold"
                                        >{{ item }}
                                        <view
                                            class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                            @click="handleMarketingDelete(index)">
                                            <u-icon name="close" color="#ffffff" size="16"></u-icon> </view
                                    ></view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加营销主题</view>
                                    <view class="text-primary font-bold" @click="toPage('marketing_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                    <template v-if="modelIndex === 1">
                        <view v-for="(item, index) in platformConfig" :key="index">
                            <view class="flex items-center justify-between">
                                <view class="flex items-center gap-x-2">
                                    <image :src="item.activeIcon" class="w-[40rpx] h-[40rpx]"></image>
                                    <text>{{ item.name }}</text>
                                </view>
                                <view>
                                    <text
                                        class="font-bold text-xs"
                                        :class="[item.materialList.every((material: any) => material.list.length > 0) ? 'text-primary' : 'text-[#FF2442]']"
                                        >{{
                                            item.materialList.every((material: any) => material.list.length > 0)
                                                ? "配置完成"
                                                : "配置未完成"
                                        }}</text
                                    >
                                </view>
                            </view>
                            <view class="mt-[18rpx] bg-white rounded-[20rpx] px-[36rpx]">
                                <view
                                    v-for="(material, materialIndex) in item.materialList"
                                    :key="materialIndex"
                                    class="flex items-center justify-between border-[0] border-solid border-[#F2F2F2] py-[30rpx]"
                                    :class="[
                                        materialIndex === item.materialList.length - 1
                                            ? 'border-b-[0rpx]'
                                            : 'border-b-[1rpx]',
                                    ]"
                                    @click="
                                        toPage(material.page, {
                                            platformIndex: index,
                                            materialIndex: materialIndex,
                                            materialList: material.list,
                                        })
                                    ">
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
                    </template>
                    <view class="flex flex-col gap-y-[50rpx] pb-[400rpx]">
                        <view>
                            <view class="font-bold text-[30rpx]"
                                ><text class="text-[#FF2442]">*</text>您创作的视频营销主题是</view
                            >
                            <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[24rpx]">
                                <textarea
                                    class="w-full"
                                    v-model="formData.videoTheme"
                                    maxlength="1000"
                                    placeholder-style="font-size: 26rpx;"
                                    placeholder="请输入，如：我想创作牙科诊所的营销视频"
                                    :disable-default-padding="true"
                                    :show-confirm-bar="false"
                                    :adjust-position="false"
                                    :auto-height="false" />
                                <view class="text-end text-xs text-[#0000004d]">
                                    {{ formData.videoTheme.length }}/1000
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="font-bold text-[30rpx]"
                                ><text class="text-[#FF2442]">*</text>您创作的图文营销主题是</view
                            >
                            <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[24rpx] flex flex-col">
                                <textarea
                                    class="w-full"
                                    v-model="formData.imageTheme"
                                    maxlength="1000"
                                    placeholder-style="font-size: 26rpx;"
                                    placeholder="请输入，如：我想创作牙科诊所的营销图片和文案"
                                    :disable-default-padding="true"
                                    :adjust-position="false"
                                    :show-confirm-bar="false"
                                    :auto-height="false" />
                                <view class="text-end text-xs text-[#0000004d]">
                                    {{ formData.imageTheme.length }}/1000
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
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
</template>

<script setup lang="ts">
import { createAutoTaskPublishConfig, getAutoTaskPublishConfigDetail } from "@/api/device";
import useMaterialStore from "@/ai_modules/device/stores/material";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import { AppTypeEnum } from "@/enums/appEnums";
import SphIcon from "@/static/images/common/sph_s.png";

const { platformLogo } = useDevice();
const loading = ref(true);

// 模式类型
const modelTypes = [
    { name: "简易模式", value: 1 },
    { name: "精确模式", value: 2 },
];
const modelIndex = ref<number>(0);
const materialStore = useMaterialStore();

const deviceCode = ref<string>("");
const deviceConfig = ref<any>(null);
const easyModeData = reactive<{
    anchorList: any[];
    videoList: any[];
    imageList: any[];
    marketingList: any[];
}>({
    anchorList: [],
    videoList: [],
    imageList: [],
    marketingList: [],
});

const formData = reactive<{
    videoTheme: string;
    imageTheme: string;
}>({
    videoTheme: "",
    imageTheme: "",
});

const commonMaterialList = [
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
];

const platformConfig = ref<any[]>([
    // 小红书
    {
        ...platformLogo[AppTypeEnum.XHS],
        status: 1,
        materialList: JSON.parse(JSON.stringify(commonMaterialList)),
    },
    // 抖音
    {
        ...platformLogo[AppTypeEnum.DOUYIN],
        status: 1,
        materialList: JSON.parse(JSON.stringify(commonMaterialList)),
    },
    //视频号
    {
        ...platformLogo[AppTypeEnum.WECHAT],
        logo: SphIcon,
        status: 1,
        materialList: JSON.parse(JSON.stringify(commonMaterialList)),
    },
    // 快手
    {
        ...platformLogo[AppTypeEnum.KUAISHOU],
        status: 0,
        materialList: JSON.parse(JSON.stringify(commonMaterialList)),
    },
]);

const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });

// 当前正在编辑的平台和素材索引
const currentEditing = ref<{
    platformIndex: number | null;
    materialIndex: number | null;
}>({
    platformIndex: null,
    materialIndex: null,
});

const handlePreview = (item: any) => {
    if (item.type === "image") {
        uni.previewImage({
            urls: [item.pic],
        });
    } else {
        previewVideo(item);
    }
};

const previewVideo = (item: any) => {
    playData.value = { url: item.url, pic: item.pic };
    showVideoPreview.value = true;
};

const handleMarketingDelete = (index: number) => {
    easyModeData.marketingList.splice(index, 1);
};

const toPage = (page: string, options?: { platformIndex: number; materialIndex: number; materialList: any[] }) => {
    materialStore.clearMaterial(); // 跳转前清空store

    if (modelIndex.value === 0) {
        // 简易模式：将所有列表加载到store
        materialStore.anchorList = easyModeData.anchorList;
        materialStore.videoList = easyModeData.videoList;
        materialStore.imageList = easyModeData.imageList;
        materialStore.marketingList = easyModeData.marketingList;
    } else if (options) {
        // 精确模式：记录正在编辑的平台和素材，并加载对应列表到store
        const { platformIndex, materialIndex, materialList } = options;
        currentEditing.value = { platformIndex, materialIndex };

        // 根据要跳转的页面，加载正确的列表到store
        switch (page) {
            case "anchor_material":
                materialStore.anchorList = materialList;
                break;
            case "video_material":
                materialStore.videoList = materialList;
                break;
            case "image_material":
                materialStore.imageList = materialList;
                break;
            case "marketing_material":
                materialStore.marketingList = materialList;
                break;
        }
    }

    const urls = {
        anchor_material: "/ai_modules/device/pages/anchor_material/anchor_material",
        video_material: "/ai_modules/device/pages/video_material/video_material",
        image_material: "/ai_modules/device/pages/image_material/image_material",
        marketing_material: "/ai_modules/device/pages/marketing_material/marketing_material",
    };
    uni.$u.route({
        url: urls[page as keyof typeof urls],
    });
};

const setMaterialList = () => {
    // 从素材页返回后，根据当前模式更新对应的数据
    if (modelIndex.value === 0) {
        // 简易模式
        easyModeData.anchorList = materialStore.anchorList;
        easyModeData.videoList = materialStore.videoList;
        easyModeData.imageList = materialStore.imageList;
        easyModeData.marketingList = materialStore.marketingList;
    } else {
        // 精确模式
        const { platformIndex, materialIndex } = currentEditing.value;
        if (platformIndex !== null && materialIndex !== null) {
            const platform = platformConfig.value[platformIndex];
            const material = platform.materialList[materialIndex];
            // 根据素材类型更新对应的列表
            switch (material.page) {
                case "anchor_material":
                    material.list = materialStore.anchorList;
                    break;
                case "video_material":
                    material.list = materialStore.videoList;
                    break;
                case "image_material":
                    material.list = materialStore.imageList;
                    break;
                case "marketing_material":
                    material.list = materialStore.marketingList;
                    break;
            }
            // 重置编辑标记
            currentEditing.value.platformIndex = null;
            currentEditing.value.materialIndex = null;
        }
    }
};

const handleSaveConfig = async () => {
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await createAutoTaskPublishConfig({
            device_config_id: deviceConfig.value.device_config_id,
            device_code: deviceCode.value,
            text_theme: formData.imageTheme,
            video_theme: formData.videoTheme,
            human_image: materialStore.anchorList.map((item: any) => ({
                ...item.anchor_ids,
                ...item.extra_info,
                id: item.id,
                pic: item.pic,
                voice_id: item.extra_info.shanjian_voice_id,
                anchor_url: item.result_url,
            })),
            clip_material: materialStore.videoList.map((item: any) => ({
                type: item.type,
                fileUrl: item.url,
                cover: item.pic,
            })),
            image_material: materialStore.imageList.map((item: any) => item.url),
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getDetail = async () => {
    uni.showLoading({
        title: "加载中...",
        mask: true,
    });
    try {
        const data = await getAutoTaskPublishConfigDetail({ device_code: deviceCode.value });
        deviceConfig.value = data;
        easyModeData.anchorList = data.human_image.map((item: any) => ({
            id: item.id,
            pic: item.pic,
            result_url: item.anchor_url,
            anchor_ids: {
                shanjian_anchor_id: item.shanjian_anchor_id,
                chanjing_anchor_id: item.chanjing_anchor_id,
                weiju_anchor_id: item.weiju_anchor_id,
            },
            extra_info: {
                width: item.width,
                height: item.height,
                shanjian_voice_id: item.shanjian_voice_id,
            },
        }));
        easyModeData.videoList = data.clip_material.map((item: any) => ({
            url: item.fileUrl,
            pic: item.cover,
            type: item.type,
        }));
        easyModeData.imageList = data.image_material.map((item: any) => ({
            pic: item,
            url: item,
        }));
        materialStore.anchorList = easyModeData.anchorList;
        materialStore.videoList = easyModeData.videoList;
        materialStore.imageList = easyModeData.imageList;
        formData.videoTheme = data.video_theme;
        formData.imageTheme = data.text_theme;
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

watch(modelIndex, (newVal) => {
    materialStore.clearMaterial();
    if (newVal === 0) {
        materialStore.anchorList = easyModeData.anchorList;
        materialStore.videoList = easyModeData.videoList;
        materialStore.imageList = easyModeData.imageList;
        materialStore.marketingList = easyModeData.marketingList;
    }
});

onShow(() => {
    setMaterialList();
});

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
});

onUnload(() => {
    materialStore.clearMaterial();
});
</script>

<style scoped lang="scss">
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-primary absolute top-[4rpx] left-0 transition-all duration-500;
}
.marketing-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] px-4 py-2;
}
</style>
