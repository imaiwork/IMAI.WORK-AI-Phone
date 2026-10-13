<template>
    <view
        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
        <view
            class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
            <text class="text-[28rpx] font-bold text-[#0D1117]">视频封面</text>
            <view class="bg-[#F0F2F5] rounded-[8rpx] px-[12rpx] py-[4rpx]">
                <text class="text-[20rpx] text-[#9CA3AF] font-medium">非必填</text>
            </view>
        </view>

        <view class="px-[28rpx] py-[24rpx]">
            <view v-if="!modelValue" class="flex items-center gap-[24rpx]">
                <view
                    class="w-[160rpx] h-[160rpx] rounded-[20rpx] border-[2rpx] border-dashed border-[#C0C4CC] bg-[#F7F9FC] flex flex-col items-center justify-center gap-[8rpx] active:opacity-70"
                    @click="handleChooseImage">
                    <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                        <u-icon name="plus" color="#0065fb" size="26" />
                    </view>
                    <text class="text-[22rpx] text-[#9CA3AF]">上传封面</text>
                </view>

                <view class="flex flex-col gap-[12rpx]">
                    <view class="flex items-center gap-[8rpx]">
                        <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#C0C4CC]" />
                        <text class="text-[22rpx] text-[#9CA3AF]">建议尺寸比例 16:9 或 9:16</text>
                    </view>
                    <view class="flex items-center gap-[8rpx]">
                        <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#C0C4CC]" />
                        <text class="text-[22rpx] text-[#9CA3AF]">支持 jpg、jpeg、png、webp 格式</text>
                    </view>
                    <view class="flex items-center gap-[8rpx]">
                        <view class="w-[6rpx] h-[6rpx] rounded-full bg-[#C0C4CC]" />
                        <text class="text-[22rpx] text-[#9CA3AF]">大小不超过 10MB</text>
                    </view>
                </view>
            </view>

            <view v-else class="flex items-center gap-[24rpx]">
                <view class="relative flex-shrink-0">
                    <image
                        :src="modelValue"
                        class="w-[160rpx] h-[160rpx] rounded-[20rpx] bg-[#F0F2F5]"
                        mode="aspectFill" />
                    <view
                        class="absolute inset-0 rounded-[20rpx] bg-[#000000]/40 flex items-center justify-center gap-[16rpx]">
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#ffffff]/20 flex items-center justify-center"
                            @click="handlePreview">
                            <u-icon name="eye" color="#ffffff" size="22" />
                        </view>
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full bg-[#ffffff]/20 flex items-center justify-center"
                            @click="handleDelete">
                            <u-icon name="trash" color="#ffffff" size="22" />
                        </view>
                    </view>
                </view>

                <view class="flex flex-col gap-[16rpx] flex-1 min-w-0">
                    <view class="flex items-center gap-[8rpx]">
                        <u-icon name="checkmark-circle-fill" color="#07C160" size="28" />
                        <text class="text-[26rpx] font-semibold text-[#0D1117]">封面已上传</text>
                    </view>
                    <text class="text-[22rpx] text-[#9CA3AF] truncate">{{ fileName }}</text>
                    <view
                        class="flex items-center justify-center gap-[8rpx] h-[60rpx] rounded-[14rpx] border border-solid border-[#E5E9F0] bg-white active:opacity-70"
                        @click="handleChooseImage">
                        <u-icon name="reload" color="#4B5563" size="18" />
                        <text class="text-[24rpx] font-semibold text-[#4B5563]">重新上传</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import useUpload from "@/hooks/useUpload";

interface Props {
    modelValue?: string;
    maxSize?: number;
}

interface Emits {
    (e: "update:modelValue", value: string): void;
    (e: "change", value: string): void;
    (e: "delete"): void;
    (e: "error", msg: string): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: "",
    maxSize: 10,
});

const emit = defineEmits<Emits>();

const { uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    imageSize: props.maxSize,
    imageAccept: ["jpg", "jpeg", "png"],
    onSuccess: (res) => {
        const data = res[0] || {};
        fileName.value = data.name || "";
        emit("update:modelValue", data.url);
        uni.hideLoading();
    },
});

// ─── 状态 ────────────────────────────────────────────────────────

const fileName = ref("");

// ─── 选择图片 ────────────────────────────────────────────────────

const handleChooseImage = async () => {
    uni.showLoading({
        title: "上传中...",
        mask: true,
    });
    try {
        await uploadAndProcessFiles("image");
    } finally {
        uni.hideLoading();
    }
};

// ─── 预览图片 ────────────────────────────────────────────────────

const handlePreview = () => {
    if (!props.modelValue) return;
    uni.previewImage({
        urls: [props.modelValue],
        current: props.modelValue,
    });
};

// ─── 删除封面 ────────────────────────────────────────────────────

const handleDelete = () => {
    uni.showModal({
        title: "提示",
        content: "确定要删除封面图片吗？",
        success: (res) => {
            if (res.confirm) {
                fileName.value = "";
                emit("update:modelValue", "");
                emit("delete");
            }
        },
    });
};
</script>
