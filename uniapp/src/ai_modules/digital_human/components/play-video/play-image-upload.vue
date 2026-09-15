<template>
    <view class="flex items-center gap-[24rpx]">
        <view
            class="rounded-[20rpx] flex flex-col items-center justify-center overflow-hidden flex-shrink-0 relative"
            :class="[
                sizeClass,
                previewUrl ? '' : 'border-[3rpx] border-dashed border-[#C9D2E0] bg-[#FAFBFD]'
            ]"
            @click="emit('pick')"
        >
            <image v-if="previewUrl" :src="previewUrl" class="w-full h-full" mode="aspectFill" />
            <template v-else>
                <text class="text-[40rpx] text-[#C9D2E0]">+</text>
                <text class="text-[19rpx] text-[#9CA3AF]">{{ placeholder }}</text>
            </template>
            <!-- 蒙层必须盖在已有预览之上：换图时不盖住就一点变化都没有，用户会以为点了没反应 -->
            <view
                v-if="uploading"
                class="absolute inset-0 flex items-center justify-center upload-mask"
            >
                <text class="text-[20rpx] font-semibold text-white">上传中 {{ progress }}%</text>
            </view>
        </view>
        <view class="flex-1 min-w-0">
            <text class="text-[21rpx] text-[#4B5563] leading-relaxed block">{{ hint }}</text>
            <view
                v-if="previewUrl || uploading"
                class="mt-[12rpx] px-[20rpx] py-[8rpx] rounded-full bg-[#F4F6FA] inline-flex active:opacity-70"
                @click="emit('pick')"
            >
                <text class="text-[20rpx] text-[#4B5563]">{{
                    uploading ? '上传中…' : '重新上传'
                }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        previewUrl: string
        uploading: boolean
        progress: number
        hint: string
        placeholder?: string
        sizeClass?: string
    }>(),
    { placeholder: '选择照片', sizeClass: 'w-[160rpx] h-[160rpx]' }
)
const emit = defineEmits<{ (e: 'pick'): void }>()
</script>

<style lang="scss" scoped>
// 主题把 black 定义成了 CSS 变量，bg-black/50 这类透明度修饰符编译不出规则，蒙层只能写死 rgba
.upload-mask {
    background: rgba(13, 17, 23, 0.55);
}
</style>
