<template>
    <view class="min-h-screen bg-[#F8FAFC] flex flex-col pb-[150rpx]">
        <view class="sticky top-0 z-10 bg-[#FFFFFF] px-2 shadow-sm">
            <u-tabs
                :list="tabs"
                :current="current"
                active-color="#3B82F6"
                inactive-color="#64748B"
                bg-color="transparent"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="p-4">
            <view class="grid grid-cols-2 gap-4">
                <view
                    v-for="(template, index) in currentTemplateList"
                    :key="template.templateID"
                    class="relative flex flex-col bg-[#FFFFFF] rounded-xl shadow-sm border-2 transition-all duration-200 overflow-hidden"
                    :class="[isSelected(template.templateID) ? 'border-primary' : 'border-transparent']"
                    @click="toggleSelect(template)">
                    <view class="relative aspect-[3/4] bg-[#F1F5F9]">
                        <image
                            :src="getImageUrl(template.pic)"
                            class="w-full h-full object-cover"
                            mode="aspectFill"
                            lazy-load />

                        <view
                            class="absolute top-2 right-2 w-6 h-6 rounded-full flex items-center justify-center border-2 transition-colors"
                            :class="[
                                isSelected(template.templateID)
                                    ? 'bg-primary border-primary'
                                    : 'bg-[#00000022] border-[#FFFFFF]',
                            ]">
                            <text v-if="isSelected(template.templateID)" class="text-[#FFFFFF] text-[20rpx]">✓</text>
                        </view>
                    </view>

                    <view class="p-3 flex items-center justify-between">
                        <text class="text-[28rpx] font-semibold text-[#1E293B] line-clamp-1">{{ template.name }}</text>
                        <view class="px-3 py-1 bg-primary rounded-full" @click.stop="previewVideo(template)">
                            <text class="text-[22rpx] text-[#FFFFFF] font-medium">预览</text>
                        </view>
                    </view>
                </view>
            </view>

            <view v-if="currentTemplateList.length === 0" class="flex flex-col items-center justify-center py-32">
                <view class="w-20 h-20 bg-[#F1F5F9] rounded-full flex items-center justify-center mb-4">
                    <text class="text-[40rpx]">📂</text>
                </view>
                <text class="text-[#94A3B8] text-sm">暂无可用模板</text>
            </view>
        </view>
        <view
            class="fixed bottom-0 left-0 right-0 bg-[#FFFFFF] border-t border-[#F1F5F9] px-6 py-4 pb-safe flex items-center justify-between shadow-[0_-4px_20px_rgba(0,0,0,0,0.05)]">
            <view class="flex flex-col">
                <view class="flex items-baseline">
                    <text class="text-[32rpx] font-medium text-primary">{{ selectedIds.length }}</text>
                    <text class="ml-1 text-[24rpx] text-[#64748B]">个已选模板</text>
                </view>
                <text class="text-[20rpx] text-[#94A3B8]">可多选批量处理</text>
            </view>

            <view
                class="px-8 py-3 rounded-full transition-all active:opacity-80"
                :class="[selectedIds.length > 0 ? 'bg-primary' : 'bg-[#E2E8F0]']"
                @tap="confirmSelection">
                <text class="text-[#FFFFFF] font-medium text-[28rpx]">确认选择</text>
            </view>
        </view>
    </view>
    <video-preview v-model="showVideoPreview" :video-url="videoUrl" />
</template>

<script setup lang="ts">
import config from "@/config";
import { ListenerTypeEnum, MontageStylesChooseType, MontageStylesType } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import {
    digitalPersonTemplates,
    realPersonTemplates,
    newsTemplates,
    materialTemplates,
} from "@/ai_modules/digital_human/config";

const { emit } = useEventBusManager();

const { baseUrl } = config;

const tabs = [
    { name: "全部", value: MontageStylesChooseType.ALL },
    { name: "高级感", value: MontageStylesChooseType.HIGH },
    { name: "综艺感", value: MontageStylesChooseType.VARIETY },
    { name: "热门", value: MontageStylesChooseType.HOT },
    { name: "简约", value: MontageStylesChooseType.SIMPLE },
    { name: "本地引流", value: MontageStylesChooseType.LOCAL },
];

// 响应式状态
const current = ref(0);
const currentTemp = ref<MontageStylesType>(MontageStylesType.DIGITAL_PERSON);
const selectedIds = ref<string[]>([]);

const showVideoPreview = ref(false);
const videoUrl = ref("");
const currentTemplateList: Ref<any[]> = computed(() => {
    const templates = {
        [MontageStylesType.DIGITAL_PERSON]: digitalPersonTemplates,
        [MontageStylesType.REAL_PERSON]: realPersonTemplates,
        [MontageStylesType.NEWS]: newsTemplates,
        [MontageStylesType.MATERIAL]: materialTemplates,
    };
    const currentTemplates = templates[currentTemp.value as keyof typeof templates];
    const currentCategory = tabs[current.value].value;
    // 如果是全部的需要把所有的都显示出来
    if (currentCategory === MontageStylesChooseType.ALL) {
        return Object.values(currentTemplates).flat();
    }
    return currentTemplates[currentCategory as keyof typeof currentTemplates] || [];
});

const handleTabChange = (index: number) => {
    current.value = index;
};

const toggleSelect = (template: any) => {
    const id = template.templateID;
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const isSelected = (id: string) => {
    return selectedIds.value.includes(id);
};

const getImageUrl = (pic: string) => {
    if (currentTemp.value === MontageStylesType.REAL_PERSON) {
        return `${baseUrl}static/videos/montage_template/${pic}`;
    }
    return `${baseUrl}static/images/montage_template/${pic}`;
};

const getVideoUrl = (link: string) => {
    return `${baseUrl}static/videos/montage_template/${link}`;
};

const previewImage = (pic: string) => {
    uni.previewImage({
        urls: [getImageUrl(pic)],
    });
};

const previewVideo = (template: any) => {
    const link = template.link;
    const pic = template.pic;
    if (currentTemp.value === MontageStylesType.REAL_PERSON) {
        videoUrl.value = getVideoUrl(link);
        showVideoPreview.value = true;
    } else {
        previewImage(pic);
    }
};

const confirmSelection = () => {
    if (selectedIds.value.length === 0) {
        uni.$u.toast("请至少选择一个模板");
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.CHOOSE_VIDEO_STYLES,
        data: selectedIds.value,
    });
    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.type) {
        currentTemp.value = parseInt(options.type);
    }
    if (options.data) {
        selectedIds.value = JSON.parse(options.data);
    }

    const titles = {
        [MontageStylesType.DIGITAL_PERSON]: "数字人口播模板",
        [MontageStylesType.REAL_PERSON]: "真人口播模板",
        [MontageStylesType.NEWS]: "新闻体模板",
        [MontageStylesType.MATERIAL]: "素材混剪模板",
    };

    uni.setNavigationBarTitle({
        title: titles[currentTemp.value as keyof typeof titles] || "",
    });
});
</script>
