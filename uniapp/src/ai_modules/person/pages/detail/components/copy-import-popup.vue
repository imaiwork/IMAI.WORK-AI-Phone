<template>
    <popup-bottom
        v-model="show"
        :title="isPublish ? '批量导入发布文案' : '批量导入文案'"
        :height="isPublish ? '44%' : '62%'"
        border-radius="44"
        custom-class="bg-white"
        :z-index="5002"
        :mask-close-able="true">
        <template #content>
            <view class="copy-import">
                <text class="copy-import-sub">
                    {{
                        isPublish
                            ? "按模板整理「标题 + 内容 + 话题」，上传 Excel / CSV 一键批量导入"
                            : "选择要导入的文案类型，点击后上传 Excel / CSV 文件"
                    }}
                </text>

                <template v-if="isPublish">
                    <view class="copy-import-upload" @click="handleSelect(CopyDriverTypeEnum.PUBLISH)">
                        <view class="copy-import-upload-ic">
                            <u-icon name="download" color="#FFFFFF" size="40"></u-icon>
                        </view>
                        <text class="copy-import-upload-t">点击上传文案文件</text>
                        <text class="copy-import-upload-s">支持 .xlsx / .xls / .csv 格式</text>
                    </view>
                </template>

                <template v-else>
                    <view
                        v-for="opt in driveOptions"
                        :key="opt.key"
                        class="copy-import-row"
                        @click="handleSelect(opt.key)">
                        <view class="copy-import-ic" :style="{ background: opt.bg }">
                            <u-icon :name="opt.icon" :color="opt.color" size="36"></u-icon>
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="copy-import-name">{{ opt.name }}</text>
                            <text class="copy-import-desc">{{ opt.desc }}</text>
                        </view>
                        <u-icon name="arrow-right" color="#C9CDD4" size="26"></u-icon>
                    </view>
                </template>

                <view class="copy-import-tpl" @click="handleCopyTemplate">
                    <text>复制模板链接</text>
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
        desc: "仅标题，批量导入新闻体驱动文案",
        icon: "file-text",
        color: "#2F73F6",
        bg: "#EBF3FF",
    },
    {
        key: CopyDriverTypeEnum.VOICEOVER,
        name: "口播文案",
        desc: "标题 + 内容，批量导入数字人口播",
        icon: "mic",
        color: "#4B8EFF",
        bg: "#F0F7FF",
    },
    {
        key: CopyDriverTypeEnum.CLIPS,
        name: "素材混剪口播",
        desc: "标题 + 内容，批量导入混剪口播",
        icon: "list",
        color: "#10B981",
        bg: "#EEF6EE",
    },
];

const handleSelect = (driverType: CopyDriverTypeEnum): void => {
    emit("select", driverType);
};

const { copy } = useCopy();

const handleCopyTemplate = () => {
    copy(`${config.baseUrl}static/file/template/copywrite_temp.zip`);
};
</script>

<style lang="scss" scoped>
.copy-import {
    @apply px-[36rpx] pt-[8rpx] pb-[calc(28rpx+env(safe-area-inset-bottom))];
}

.copy-import-sub {
    @apply block text-[24rpx] text-[#9ca3af] mb-[28rpx];
}

.copy-import-row {
    @apply flex items-center gap-[24rpx] bg-[#F7F9FC] rounded-[24rpx] px-[28rpx] py-[26rpx] mb-[18rpx] active:bg-[#EBF2FF];
}

.copy-import-ic {
    @apply w-[80rpx] h-[80rpx] rounded-[22rpx] flex items-center justify-center shrink-0;
}

.copy-import-name {
    @apply block text-sm font-bold text-[#1F2937];
}

.copy-import-desc {
    @apply block text-[22rpx] text-[#9CA3AF] mt-[6rpx];
}

.copy-import-upload {
    @apply flex flex-col items-center justify-center gap-[14rpx] rounded-[24rpx] py-[48rpx] mb-[8rpx];
    background: #f2f7ff;
    border: 3rpx dashed #9cc3ff;
}

.copy-import-upload-ic {
    @apply w-[96rpx] h-[96rpx] rounded-full flex items-center justify-center;
    background: linear-gradient(135deg, #2680f7, #3e9bff);
    box-shadow: 0 0 0 16rpx rgba(38, 128, 247, 0.12);
}

.copy-import-upload-t {
    @apply text-sm font-semibold text-[#1F2937] mt-[8rpx];
}

.copy-import-upload-s {
    @apply text-[22rpx] text-[#9CA3AF];
}

.copy-import-tpl {
    @apply flex items-center justify-center gap-[8rpx] text-[24rpx] text-[#9CA3AF] mt-[28rpx] active:text-primary;
}
</style>
