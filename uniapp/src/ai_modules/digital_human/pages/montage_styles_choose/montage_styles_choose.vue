<template>
    <view class="min-h-screen bg-[#F8FAFC] flex flex-col pb-[150rpx]">
        <view class="p-4">
            <view class="grid grid-cols-2 gap-4">
                <view
                    v-for="(template, index) in currentTemplateList"
                    :key="template.templateID"
                    class="relative flex flex-col bg-[#FFFFFF] rounded-xl shadow-sm border-2 transition-all duration-200 overflow-hidden"
                    :class="[isSelected(template.templateID) ? 'border-primary' : 'border-[transparent]']"
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

            <view v-if="loading" class="flex flex-col items-center justify-center py-32">
                <text class="text-[#94A3B8] text-sm">模板加载中...</text>
            </view>

            <view v-else-if="currentTemplateList.length === 0" class="flex flex-col items-center justify-center py-32">
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
                    <text class="ml-1 text-xs text-[#64748B]">个已选模板</text>
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
import { ListenerTypeEnum, MontageStylesType } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { getShanjianClipTemplateList } from "@/api/digital_human";

const { emit } = useEventBusManager();

// 响应式状态
const currentTemp = ref<MontageStylesType>(MontageStylesType.DIGITAL_PERSON);
const selectedIds = ref<string[]>([]);
const templateList = ref<any[]>([]);
const loading = ref(false);

const showVideoPreview = ref(false);
const videoUrl = ref("");
const currentTemplateList: Ref<any[]> = computed(() => {
    return templateList.value;
});

const sceneMap: Record<MontageStylesType, string> = {
    [MontageStylesType.DIGITAL_PERSON]: "virtualman",
    [MontageStylesType.REAL_PERSON]: "realMan",
    [MontageStylesType.NEWS]: "newsMixCutting",
    [MontageStylesType.MATERIAL]: "oralMixCutting",
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

const getImageUrl = (pic: string) => pic;

const getVideoUrl = (link: string) => link;

const previewImage = (pic: string) => {
    uni.previewImage({
        urls: [getImageUrl(pic)],
    });
};

const previewVideo = (template: any) => {
    const link = template.link;
    const pic = template.pic;
    if (link) {
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

const normalizeTemplate = (item: any) => ({
    name: item.name,
    pic: item.cover_url,
    link: item.demo_url,
    templateID: item.id,
});

const fetchTemplateList = async () => {
    loading.value = true;
    try {
        const res: any = await getShanjianClipTemplateList({
            scene: sceneMap[currentTemp.value],
            page_no: 1,
            page_size: 999,
        });
        const lists = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
        templateList.value = lists.map(normalizeTemplate);
    } catch (error) {
        templateList.value = [];
        uni.$u.toast("风格模板加载失败");
    } finally {
        loading.value = false;
    }
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

    fetchTemplateList();
});
</script>
