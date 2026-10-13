<template>
    <popup-bottom
        v-model="show"
        :height="isPublish ? '44%' : '62%'"
        border-radius="48"
        custom-class="bg-white"
        :z-index="5002"
        :clearable="false"
        :mask-close-able="true"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="copy-import-handle"></view>
            <view class="copy-import-header">
                <view class="flex-1 min-w-0">
                    <text class="copy-import-title">{{ isPublish ? "批量导入发布文案" : "批量导入文案" }}</text>
                    <text class="copy-import-sub">支持 XLSX / XLS / CSV</text>
                </view>
                <view class="copy-import-close" @click="emit('update:modelValue', false)">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <view class="copy-import">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="copy-import-body">
                            <view v-if="!isPublish" class="copy-import-field">
                                <text class="copy-import-label">导入类型</text>
                                <view class="copy-import-select" @click="showDriveOptions = !showDriveOptions">
                                    <text>{{ selectedDriveOption.name }}</text>
                                    <u-icon
                                        :name="showDriveOptions ? 'arrow-up' : 'arrow-down'"
                                        color="#647184"
                                        size="24"></u-icon>
                                </view>
                                <view v-if="showDriveOptions" class="copy-import-options">
                                    <view
                                        v-for="(option, index) in driveOptions"
                                        :key="option.key"
                                        class="copy-import-option"
                                        :class="{ 'copy-import-option--on': selectedDriveIndex === index }"
                                        @click="handleDriveChange(index)">
                                        <text class="flex-1 min-w-0">{{ option.name }}</text>
                                        <u-icon
                                            v-if="selectedDriveIndex === index"
                                            name="checkmark"
                                            color="#0065FB"
                                            size="26"></u-icon>
                                    </view>
                                </view>
                                <text class="copy-import-hint">{{ selectedDriveOption.desc }}</text>
                            </view>
                            <view class="copy-import-field">
                                <text class="copy-import-label">选择表格文件</text>
                                <view class="copy-import-file" @click="handleSelect(selectedDriverType)">
                                    <u-icon name="file-text" color="#5B6B80" size="36"></u-icon>
                                    <text class="flex-1 min-w-0">选择文案文件</text>
                                    <u-icon name="arrow-right" color="#647184" size="24"></u-icon>
                                </view>
                                <text class="copy-import-hint">
                                    {{
                                        isPublish
                                            ? "按模板整理标题、内容与话题，选择文件后开始上传并导入。"
                                            : "一行视频文案对应一行发布文案，选择文件后开始上传并导入。"
                                    }}
                                </text>
                            </view>
                            <view class="copy-import-tpl" @click="handleCopyTemplate">
                                <u-icon name="attach" color="#0761D9" size="28"></u-icon>
                                <text>复制模板链接</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="copy-import-footer">
                    <view class="copy-import-cancel" @click="emit('update:modelValue', false)">取消</view>
                    <view class="copy-import-confirm" @click="handleSelect(selectedDriverType)">选择文件并导入</view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import config from "@/config";
import { useCopy } from "@/hooks/useCopy";
import { CopyDriverTypeEnum, CopyLibraryTypeEnum } from "../hooks/useCopyLibrary";

const props = defineProps<{
    modelValue: boolean;
    libraryType: number;
}>();

const emit = defineEmits<{
    (event: "update:modelValue", value: boolean): void;
    (event: "select", driverType: CopyDriverTypeEnum): void;
    (event: "downloadTemplate"): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const isPublish = computed(() => props.libraryType === CopyLibraryTypeEnum.PUBLISH);

const driveOptions = [
    {
        key: CopyDriverTypeEnum.NEWS,
        name: "新闻体",
        desc: "一行视频文案对应一行发布文案。A 列是多行标题，后面三列是发布标题、正文和话题，可以不填。",
    },
    {
        key: CopyDriverTypeEnum.VOICEOVER,
        name: "口播文案",
        desc: "一行视频文案对应一行发布文案。标题和口播必填，发布标题、正文、话题可以不填。",
    },
    {
        key: CopyDriverTypeEnum.CLIPS,
        name: "素材混剪口播",
        desc: "和口播文案同一份表。标题和口播必填，发布标题、正文、话题可以不填。",
    },
];

const selectedDriveIndex = ref(0);
const showDriveOptions = ref(false);
const selectedDriveOption = computed(() => driveOptions[selectedDriveIndex.value] ?? driveOptions[0]);
const selectedDriverType = computed(() =>
    isPublish.value ? CopyDriverTypeEnum.PUBLISH : selectedDriveOption.value.key,
);

const handleDriveChange = (index: number): void => {
    selectedDriveIndex.value = index;
    showDriveOptions.value = false;
};

watch(
    () => props.modelValue,
    (visible) => {
        showDriveOptions.value = false;
        if (visible) selectedDriveIndex.value = 0;
    },
);

const handleSelect = (driverType: CopyDriverTypeEnum): void => {
    emit("select", driverType);
};

const { copy } = useCopy();

const handleCopyTemplate = () => {
    copy(`${config.baseUrl}static/file/template/copywrite_temp.zip`);
};
</script>

<style lang="scss" scoped>
.copy-import-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.copy-import-header {
    @apply flex items-center gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.copy-import-title {
    @apply block text-[34rpx] font-semibold text-[#202937];
}

.copy-import-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full bg-[#F1F4F8] flex items-center justify-center shrink-0 active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.copy-import {
    @apply h-full flex flex-col;
}

.copy-import-sub {
    @apply block mt-[8rpx] text-[24rpx] leading-[36rpx] text-[#647184];
}

.copy-import-body {
    @apply p-[36rpx];
}

.copy-import-field {
    @apply mb-[36rpx];
}

.copy-import-label {
    @apply block mb-[16rpx] text-[26rpx] font-semibold text-[#475971];
}

.copy-import-select,
.copy-import-file {
    @apply min-h-[92rpx] flex items-center justify-between gap-[16rpx] px-[24rpx] py-[20rpx]
        rounded-[22rpx] border-[2rpx] border-solid border-[#DBE3EE] bg-[#F9FBFD]
        text-[30rpx] leading-[44rpx] text-[#26364E] active:opacity-70;
}

.copy-import-hint {
    @apply block mt-[12rpx] text-[24rpx] leading-[38rpx] text-[#647184];
}

.copy-import-options {
    @apply mt-[12rpx] p-[8rpx] rounded-[22rpx] border-[2rpx] border-solid border-[#DBE3EE] bg-white;
}

.copy-import-option {
    @apply min-h-[88rpx] px-[20rpx] flex items-center gap-[16rpx] rounded-[16rpx]
        text-[28rpx] text-[#26364E] active:opacity-70;
}

.copy-import-option--on {
    @apply bg-[#EDF4FF] text-[#0761D9];
}

.copy-import-tpl {
    @apply min-h-[76rpx] flex items-center gap-[10rpx] text-[26rpx] text-[#0761D9] active:opacity-70;
}

.copy-import-footer {
    @apply flex gap-[20rpx] px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.copy-import-cancel,
.copy-import-confirm {
    @apply min-h-[94rpx] flex-1 flex items-center justify-center rounded-[24rpx]
        text-[28rpx] font-semibold active:opacity-80;
}

.copy-import-cancel {
    @apply bg-white text-[#36516F] border-[2rpx] border-solid border-[#D8E2ED];
}

.copy-import-confirm {
    @apply bg-primary text-white;
}
</style>
