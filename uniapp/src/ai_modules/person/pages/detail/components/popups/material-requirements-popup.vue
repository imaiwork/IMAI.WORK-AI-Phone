<template>
    <popup-bottom
        v-model="show"
        mode="bottom"
        height="auto"
        border-radius="48"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        :z-index="5002"
        safe-area-inset-bottom
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="mr-handle"></view>
            <view class="mr-header">
                <view class="flex-1 min-w-0 flex flex-col gap-[8rpx]">
                    <text class="text-[34rpx] font-semibold text-[#202937]">素材要求</text>
                    <text class="text-[24rpx] leading-[36rpx] text-[#647184]">
                        {{
                            publishMode === 1
                                ? `剪辑原片库 · ${materialKind === MaterialKindEnum.ORIGINAL ? "原图模式" : "自动适配"}`
                                : "成品直发库"
                        }}
                    </text>
                </view>
                <view class="mr-close" @click="show = false">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <view class="w-full flex flex-col">
                <scroll-view scroll-y class="mr-scroll">
                    <view class="p-[36rpx] flex flex-col gap-[24rpx]">
                        <view v-if="publishMode === 1 && materialMin > 0" class="mr-note">
                            <u-icon name="info-circle" color="#3970AB" size="30" class="shrink-0 mt-[4rpx]"></u-icon>
                            <text class="flex-1 min-w-0"
                                >建议准备至少 {{ materialMin }} 段可用素材，竖屏、每段 5–10 秒更便于剪辑。</text
                            >
                        </view>
                        <text class="text-[28rpx] leading-[44rpx] font-semibold text-[#202937]">{{
                            specText.title
                        }}</text>
                        <view class="flex flex-col">
                            <view v-for="line in specText.lines" :key="line" class="mr-rule">
                                <text class="text-[#3970AB] shrink-0">·</text>
                                <text class="flex-1 min-w-0">{{ line }}</text>
                            </view>
                        </view>
                        <text class="text-[24rpx] leading-[40rpx] text-[#647184]">{{ specText.note }}</text>
                    </view>
                </scroll-view>
                <view class="mr-footer">
                    <view class="mr-primary active:opacity-85" @click="show = false">
                        <text class="text-[28rpx] font-semibold text-white">我知道了</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { MaterialKindEnum } from "@/ai_modules/person/enums";
import { materialKindSpec, type PublishMode } from "../../hooks/useMaterialsTab";

const props = defineProps<{
    modelValue: boolean;
    materialKind: MaterialKindEnum;
    publishMode: PublishMode;
    materialMin: number;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

// 原片完整复用上传规格；成品遵循既有直发、不转码、不切割的处理流程。
const specText = computed(() =>
    props.publishMode === 1
        ? materialKindSpec[props.materialKind] ?? materialKindSpec[MaterialKindEnum.AUTO]
        : {
              title: "上传已制作好的成品",
              lines: ["素材类型：成品视频或图片组", "素材处理：不进行转码或切割", "使用规则：每条素材只能使用一次"],
              note: "成品直接使用，跳过 AI 合成，不消耗合成算力。",
          },
);
</script>

<style lang="scss" scoped>
.mr-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.mr-header {
    @apply flex items-center gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.mr-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full shrink-0 flex items-center justify-center bg-[#F1F4F8] active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.mr-scroll {
    max-height: 56vh;
}

.mr-note {
    @apply flex items-start gap-[20rpx] rounded-[24rpx] px-[26rpx] py-[24rpx]
        bg-[#F1F6FC] text-[24rpx] leading-[40rpx] text-[#456181];
}

.mr-rule {
    @apply flex items-start gap-[16rpx] py-[24rpx] text-[26rpx] leading-[42rpx] text-[#36516F]
        border-[0] border-b-[2rpx] border-solid border-[#E7ECF2];

    &:last-child {
        @apply border-b-[0];
    }
}

.mr-footer {
    @apply px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))]
        border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
}

.mr-primary {
    @apply min-h-[94rpx] rounded-[24rpx] bg-primary flex items-center justify-center;
}
</style>
