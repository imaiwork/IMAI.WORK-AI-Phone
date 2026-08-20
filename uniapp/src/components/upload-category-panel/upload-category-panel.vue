<template>
    <popup-bottom v-model="show" title="添加素材" height="40vh" @close="close">
        <template #content>
            <view class="bg-white w-full rounded-t-[40rpx] pb-[60rpx] pt-[32rpx] px-[32rpx]">
                <view class="flex flex-row justify-between items-start gap-[8rpx]">
                    <view
                        v-if="isVisible(UploadCategoryEnum.Album)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Album)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(0,101,251,0.12)]"
                            style="background: linear-gradient(135deg, #ebf2ff 0%, #dbeafe 100%)"
                        >
                            <u-icon name="camera-fill" size="48" color="#0065fb" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151]">相册</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Library)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Library)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(168,85,247,0.12)]"
                            style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%)"
                        >
                            <u-icon name="grid" size="48" color="#a855f7" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151]">素材库</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Group)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Group)"
                    >
                        <!-- 高效角标 -->
                        <view
                            class="absolute -top-[8rpx] right-[4rpx] z-10 h-[32rpx] px-[10rpx] flex items-center justify-center rounded-full"
                            style="background: linear-gradient(135deg, #ef4444 0%, #f97316 100%)"
                        >
                            <text class="text-white text-[18rpx] font-bold">高效</text>
                        </view>
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(249,115,22,0.12)]"
                            style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%)"
                        >
                            <u-icon name="bag" size="48" color="#f97316" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151]">素材组</text>
                    </view>

                    <view
                        v-if="isVisible(UploadCategoryEnum.Creation)"
                        class="flex flex-col items-center flex-1 px-[8rpx] relative"
                        hover-class="opacity-70"
                        @click="handleCategory(UploadCategoryEnum.Creation)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(34,197,94,0.12)]"
                            style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)"
                        >
                            <u-icon name="grid" size="48" color="#22c55e" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151]">创作库</text>
                    </view>
                </view>

                <view class="mt-[40rpx] flex justify-center">
                    <view
                        class="flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[14rpx] border border-solid border-[#E5E9F0]"
                    >
                        <u-icon name="info-circle" color="#C0C4CC" size="24" />
                        <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
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
        height="38vh"
        @close="onAlbumTypeClose"
    >
        <template #header>
            <view
                class="flex items-center justify-between px-[32rpx] py-[28rpx] w-full border-[0] border-b border-solid border-[#F0F2F5]"
            >
                <view
                    class="flex items-center gap-[6rpx] h-[56rpx] px-[16rpx] rounded-[14rpx] bg-[#F0F2F5]"
                    hover-class="opacity-70"
                    @click="onAlbumTypeBack"
                >
                    <u-icon name="arrow-left" size="28" color="#6B7280" />
                    <text class="text-xs font-medium text-[#6B7280]">返回</text>
                </view>
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">选择来源</text>
                <view class="w-[96rpx]" />
            </view>
        </template>
        <template #content>
            <view class="bg-white w-full rounded-t-[40rpx] pb-[60rpx] pt-[32rpx] px-[32rpx]">
                <view class="flex flex-row justify-around items-start gap-[8rpx]">
                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.Image)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.Image)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(0,101,251,0.12)]"
                            style="background: linear-gradient(135deg, #ebf2ff 0%, #dbeafe 100%)"
                        >
                            <u-icon name="photo" size="48" color="#0065fb" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151] text-center"
                            >从相册选择图片</text
                        >
                    </view>

                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.Video)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.Video)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(239,68,68,0.12)]"
                            style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%)"
                        >
                            <u-icon name="play-right-fill" size="48" color="#ef4444" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151] text-center"
                            >从相册选择视频</text
                        >
                    </view>

                    <view
                        v-if="isAlbumTypeVisible(UploadAlbumTypeEnum.File)"
                        class="flex flex-col items-center flex-1 px-[8rpx]"
                        hover-class="opacity-70"
                        @click="handleAlbumType(UploadAlbumTypeEnum.File)"
                    >
                        <view
                            class="w-[112rpx] h-[112rpx] rounded-[28rpx] flex items-center justify-center mb-[12rpx] shadow-[0_2rpx_10rpx_rgba(34,197,94,0.12)]"
                            style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)"
                        >
                            <u-icon name="chat" size="48" color="#22c55e" />
                        </view>
                        <text class="text-[22rpx] font-semibold text-[#374151] text-center"
                            >从聊天记录选择</text
                        >
                    </view>
                </view>

                <view class="mt-[40rpx] flex justify-center">
                    <view
                        class="flex items-center gap-[8rpx] bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[14rpx] border border-solid border-[#E5E9F0]"
                    >
                        <u-icon name="info-circle" color="#C0C4CC" size="24" />
                        <text class="text-[22rpx] text-[#9CA3AF]">请选择素材的上传来源</text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { UploadCategoryEnum, UploadAlbumTypeEnum } from '@/enums/appEnums'

type Category = UploadCategoryEnum | UploadAlbumTypeEnum

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        showCategories?: Category[]
        tip?: string
        /** 为 true 时即使只有一个相册来源，也展示二级选择面板（不自动跳过） */
        forceAlbumTypePicker?: boolean
    }>(),
    {
        modelValue: false,
        showCategories: () => [],
        tip: '支持同时选择图片与视频，特定场景仅支持选择视频或图片',
        forceAlbumTypePicker: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'select', category: Category): void
    (e: 'select:albumType', albumType: Category): void
    (e: 'close'): void
}>()

const show = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})

const showAlbumTypePopup = ref(false)

const ALL_ALBUM_TYPES = [
    UploadAlbumTypeEnum.Image,
    UploadAlbumTypeEnum.Video,
    UploadAlbumTypeEnum.File
] as const

const ALL_CATEGORIES = [
    UploadCategoryEnum.Album,
    UploadCategoryEnum.Library,
    UploadCategoryEnum.Group,
    UploadCategoryEnum.Creation
] as const

const isAlbumTypeValue = (v: Category): v is UploadAlbumTypeEnum =>
    (ALL_ALBUM_TYPES as readonly unknown[]).includes(v)

const isCategoryValue = (v: Category): v is UploadCategoryEnum =>
    (ALL_CATEGORIES as readonly unknown[]).includes(v)

const isEmpty = computed(() => !props.showCategories || props.showCategories.length === 0)

const filteredAlbumTypes = computed((): UploadAlbumTypeEnum[] => {
    if (isEmpty.value) return [...ALL_ALBUM_TYPES]
    const result = props.showCategories.filter(isAlbumTypeValue)
    return result.length > 0 ? result : [...ALL_ALBUM_TYPES]
})

const singleAlbumType = computed((): UploadAlbumTypeEnum | null =>
    filteredAlbumTypes.value.length === 1 ? filteredAlbumTypes.value[0] : null
)

const isVisible = (category: Category): boolean => {
    if (isEmpty.value) return true

    if (category === UploadCategoryEnum.Album) {
        return (
            props.showCategories.some(
                (v) => isCategoryValue(v) && v === UploadCategoryEnum.Album
            ) || props.showCategories.some(isAlbumTypeValue)
        )
    }

    return props.showCategories.some((v) => isCategoryValue(v) && v === category)
}

const isAlbumTypeVisible = (albumType: UploadAlbumTypeEnum): boolean =>
    filteredAlbumTypes.value.includes(albumType)

const handleCategory = (category: Category) => {
    if (category === UploadCategoryEnum.Album) {
        if (singleAlbumType.value !== null && !props.forceAlbumTypePicker) {
            emit('select', singleAlbumType.value)
            close()
            return
        }
        show.value = false
        nextTick(() => {
            showAlbumTypePopup.value = true
        })
        return
    }
    emit('select', category)
    close()
}

const handleAlbumType = (albumType: Category) => {
    emit('select', albumType)
    showAlbumTypePopup.value = false
    emit('close')
}

const onAlbumTypeBack = () => {
    showAlbumTypePopup.value = false
    nextTick(() => {
        show.value = true
    })
}

const onAlbumTypeClose = () => {
    showAlbumTypePopup.value = false
    emit('close')
}

const close = () => {
    show.value = false
    emit('close')
}
</script>
