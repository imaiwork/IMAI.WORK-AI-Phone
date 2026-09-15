<template>
    <popup-bottom
        v-model="show"
        :title="title"
        :height="plain ? 'auto' : '40vh'"
        :clearable="!plain"
        :mask-close-able="maskCloseAble"
        @close="close">
        <template v-if="plain" #header>
            <view class="upload-sheet-handle"></view>
            <view class="upload-sheet-header">
                <text class="upload-sheet-title">{{ title }}</text>
                <view class="upload-sheet-close active:opacity-70" aria-label="关闭" @click="close">
                    <u-icon name="close" color="#5B6B80" size="24" />
                </view>
            </view>
        </template>
        <template #content>
            <view
                class="bg-white w-full rounded-t-[40rpx] pb-[60rpx] pt-[32rpx] px-[32rpx]"
                :class="{ 'upload-sources--plain': plain }">
                <view class="flex flex-row justify-between items-start gap-[8rpx]">
                    <view
                        v-if="isVisible(UploadCategoryEnum.Album)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Album)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(0,101,251,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #ebf2ff 0%, #dbeafe 100%)' }">
                            <u-icon :name="plain ? 'photo' : 'camera-fill'" size="48" color="#0065fb" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151]">相册</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Library)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Library)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(168,85,247,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%)' }">
                            <u-icon name="grid" size="48" :color="plain ? '#0065FB' : '#a855f7'" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151]">素材库</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Group)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Group)">
                        <!-- 高效角标 -->
                        <view
                            v-if="!plain"
                            class="absolute -top-[8rpx] right-[4rpx] z-10 h-[32rpx] px-[10rpx] flex items-center justify-center rounded-full"
                            style="background: linear-gradient(135deg, #ef4444 0%, #f97316 100%)">
                            <text class="text-white text-[18rpx] font-bold">高效</text>
                        </view>
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(249,115,22,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%)' }">
                            <u-icon name="bag" size="48" :color="plain ? '#0065FB' : '#f97316'" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151]">素材组</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Creation)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Creation)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(34,197,94,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)' }">
                            <u-icon name="grid" size="48" :color="plain ? '#0065FB' : '#22c55e'" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151]">创作库</text>
                    </view>
                </view>

                <view class="mt-[40rpx] flex justify-center">
                    <view
                        class="upload-source-note flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                        <u-icon name="info-circle" :color="plain ? '#536A87' : '#C0C4CC'" size="24" />
                        <text class="upload-source-note-text text-[22rpx] text-[#9CA3AF] leading-relaxed">
                            {{ tip }}
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>

    <popup-bottom
        v-model="showAlbumTypePopup"
        title="选择来源"
        :height="plain ? 'auto' : '38vh'"
        :clearable="!plain"
        :mask-close-able="maskCloseAble"
        @close="onAlbumTypeClose">
        <template #header>
            <view v-if="plain" class="upload-sheet-handle"></view>
            <view
                class="flex items-center justify-between px-[32rpx] py-[28rpx] w-full border-[0] border-b border-solid border-[#F0F2F5]"
                :class="{ 'upload-sheet-header': plain }">
                <view
                    class="flex items-center gap-[6rpx] h-[56rpx] px-[16rpx] rounded-[14rpx] bg-[#F0F2F5]"
                    hover-class="opacity-70"
                    @click="onAlbumTypeBack">
                    <u-icon name="arrow-left" size="28" color="#6B7280" />
                    <text class="text-xs font-medium text-[#6B7280]">返回</text>
                </view>
                <text
                    :class="
                        plain ? 'upload-sheet-title flex-1 ml-[24rpx]' : 'text-[30rpx] font-extrabold text-[#0D1117]'
                    "
                    >选择来源</text
                >
                <view
                    v-if="plain"
                    class="upload-sheet-close active:opacity-70"
                    aria-label="关闭"
                    @click="onAlbumTypeClose">
                    <u-icon name="close" color="#5B6B80" size="24" />
                </view>
                <view v-else class="w-[96rpx]" />
            </view>
        </template>
        <template #content>
            <view
                class="bg-white w-full rounded-t-[40rpx] pb-[60rpx] pt-[32rpx] px-[32rpx]"
                :class="{ 'upload-sources--plain': plain }">
                <view class="flex flex-row justify-around items-start gap-[8rpx]">
                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.Image)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.Image)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(0,101,251,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #ebf2ff 0%, #dbeafe 100%)' }">
                            <u-icon name="photo" size="48" color="#0065fb" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151] text-center"
                            >从相册选择图片</text
                        >
                    </view>

                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.Video)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.Video)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(239,68,68,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)' }">
                            <u-icon name="play-right-fill" size="48" :color="plain ? '#0065FB' : '#ef4444'" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151] text-center"
                            >从相册选择视频</text
                        >
                    </view>

                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.File)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.File)">
                        <view
                            class="upload-source-icon w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(34,197,94,0.12)]"
                            :style="plain ? {} : { background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)' }">
                            <u-icon name="chat" size="48" :color="plain ? '#0065FB' : '#22c55e'" />
                        </view>
                        <text class="upload-source-label text-[22rpx] font-semibold text-[#374151] text-center"
                            >从聊天记录选择</text
                        >
                    </view>
                </view>

                <view class="mt-[40rpx] flex justify-center">
                    <view
                        class="upload-source-note flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[14rpx] border border-solid border-[#E5E9F0]">
                        <u-icon name="info-circle" :color="plain ? '#536A87' : '#C0C4CC'" size="24" />
                        <text class="upload-source-note-text text-[22rpx] text-[#9CA3AF]">请选择素材的上传来源</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";

type Category = UploadCategoryEnum | UploadAlbumTypeEnum;

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        showCategories?: Category[];
        tip?: string;
        /** 为 true 时即使只有一个相册来源，也展示二级选择面板（不自动跳过） */
        forceAlbumTypePicker?: boolean;
        /** 由页面决定是否允许点遮罩退出，不影响其他使用场景的默认行为。 */
        maskCloseAble?: boolean;
        /** 页面可选择统一的轻量视觉，其他入口保持原样。 */
        plain?: boolean;
        title?: string;
    }>(),
    {
        modelValue: false,
        showCategories: () => [],
        tip: "支持同时选择图片与视频，特定场景仅支持选择视频或图片",
        forceAlbumTypePicker: false,
        maskCloseAble: false,
        plain: false,
        title: "添加素材",
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "select", category: Category): void;
    (e: "select:albumType", albumType: Category): void;
    (e: "close"): void;
    (e: "popup-visibility-change", value: boolean): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const showAlbumTypePopup = ref(false);
// 对父页面暴露完整弹层周期，包含一级/二级面板的 nextTick 接力。
const albumTransitioning = ref(false);

watch(
    () => props.modelValue || showAlbumTypePopup.value || albumTransitioning.value,
    (visible) => emit("popup-visibility-change", visible),
    { immediate: true },
);

onUnmounted(() => emit("popup-visibility-change", false));

const ALL_ALBUM_TYPES = [UploadAlbumTypeEnum.Image, UploadAlbumTypeEnum.Video, UploadAlbumTypeEnum.File] as const;

const ALL_CATEGORIES = [
    UploadCategoryEnum.Album,
    UploadCategoryEnum.Library,
    UploadCategoryEnum.Group,
    UploadCategoryEnum.Creation,
] as const;

const isAlbumTypeValue = (v: Category): v is UploadAlbumTypeEnum => (ALL_ALBUM_TYPES as readonly unknown[]).includes(v);

const isCategoryValue = (v: Category): v is UploadCategoryEnum => (ALL_CATEGORIES as readonly unknown[]).includes(v);

const isEmpty = computed(() => !props.showCategories || props.showCategories.length === 0);

const filteredAlbumTypes = computed((): UploadAlbumTypeEnum[] => {
    if (isEmpty.value) return [...ALL_ALBUM_TYPES];
    const result = props.showCategories.filter(isAlbumTypeValue);
    return result.length > 0 ? result : [...ALL_ALBUM_TYPES];
});

const singleAlbumType = computed((): UploadAlbumTypeEnum | null =>
    filteredAlbumTypes.value.length === 1 ? filteredAlbumTypes.value[0] : null,
);

const isVisible = (category: Category): boolean => {
    if (isEmpty.value) return true;

    if (category === UploadCategoryEnum.Album) {
        return (
            props.showCategories.some((v) => isCategoryValue(v) && v === UploadCategoryEnum.Album) ||
            props.showCategories.some(isAlbumTypeValue)
        );
    }

    return props.showCategories.some((v) => isCategoryValue(v) && v === category);
};

const isAlbumTypeVisible = (albumType: UploadAlbumTypeEnum): boolean => filteredAlbumTypes.value.includes(albumType);

const handleCategory = (category: Category) => {
    if (category === UploadCategoryEnum.Album) {
        if (singleAlbumType.value !== null && !props.forceAlbumTypePicker) {
            emit("select", singleAlbumType.value);
            close();
            return;
        }
        albumTransitioning.value = true;
        show.value = false;
        nextTick(() => {
            showAlbumTypePopup.value = true;
            albumTransitioning.value = false;
        });
        return;
    }
    emit("select", category);
    close();
};

const handleAlbumType = (albumType: Category) => {
    emit("select", albumType);
    showAlbumTypePopup.value = false;
    albumTransitioning.value = false;
    emit("close");
};

const onAlbumTypeBack = () => {
    albumTransitioning.value = true;
    showAlbumTypePopup.value = false;
    nextTick(async () => {
        show.value = true;
        await nextTick();
        albumTransitioning.value = false;
    });
};

const onAlbumTypeClose = () => {
    showAlbumTypePopup.value = false;
    albumTransitioning.value = false;
    emit("close");
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style lang="scss" scoped>
.upload-sheet-handle {
    @apply w-[64rpx] h-[8rpx] rounded-full bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.upload-sheet-header {
    @apply flex items-center justify-between gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx] border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.upload-sheet-title {
    @apply text-[34rpx] font-semibold text-[#202937];
}

.upload-sheet-close {
    @apply relative w-[56rpx] h-[56rpx] shrink-0 flex items-center justify-center rounded-full bg-[#F1F4F8];

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.upload-sources--plain {
    @apply px-[36rpx] pt-[40rpx];
    padding-bottom: calc(32rpx + env(safe-area-inset-bottom));

    .upload-source-label {
        @apply text-[24rpx] font-medium text-[#4A5E76];
    }

    .upload-source-icon {
        @apply w-[96rpx] h-[96rpx] rounded-[28rpx] mb-[20rpx] bg-[#EDF3FF];
        box-shadow: none;
    }

    .upload-source-note {
        @apply items-start gap-[16rpx] rounded-[24rpx] bg-[#EDF3FC] border-[#E0E9F7] p-[24rpx];
    }

    .upload-source-note-text {
        @apply text-[24rpx] leading-[40rpx] text-[#536A87];
    }
}
</style>
