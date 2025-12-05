<template>
    <u-popup
        v-model="showPopup"
        mode="bottom"
        :height="height"
        :border-radius="borderRadius"
        :closeable="false"
        :z-index="zIndex"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd">
        <view class="h-full flex flex-col" :class="customClass" :style="customStyle">
            <view
                v-if="showCloseBtn"
                class="w-4 h-4 flex items-center justify-center rounded-full absolute top-3 right-4 border border-solid border-[#8B9199]"
                @click="closePopup">
                <u-icon name="close" color="#8B9199" :size="16"></u-icon>
            </view>
            <view>
                <view class="px-[60rpx]" v-if="!$slots.header">
                    <view class="flex justify-center">
                        <view class="p-1" @click="closePopup">
                            <view class="w-[66rpx] h-[8rpx] rounded bg-black mt-4"></view>
                        </view>
                    </view>
                    <view class="mt-[40rpx] pb-[30rpx]">
                        <view class="font-bold text-center">{{ title }}</view>
                    </view>
                    <u-line />
                </view>
                <slot name="header" v-else></slot>
            </view>
            <view class="grow min-h-0">
                <slot name="content" />
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    height: {
        type: String,
        default: "90%",
    },
    borderRadius: {
        type: [String, Number],
        default: "48",
    },
    customClass: {
        type: String,
        default: "",
    },
    customStyle: {
        type: Object,
        default: () => ({}),
    },
    title: {
        type: String,
        default: "",
    },
    isDisabledTouch: {
        type: Boolean,
        default: false,
    },
    showCloseBtn: {
        type: Boolean,
        default: false,
    },
    zIndex: {
        type: Number,
        default: 1000,
    },
});
const emit = defineEmits(["update:show", "close"]);

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});

// 记录触摸起始位置
let startX = 0;
let startY = 0;

const handleTouchStart = (event: TouchEvent) => {
    if (props.isDisabledTouch) return;
    startX = event.touches[0].clientX;
    startY = event.touches[0].clientY;
};

const handleTouchEnd = (event: TouchEvent) => {
    if (props.isDisabledTouch) return;
    const endX = event.changedTouches[0].clientX;
    const endY = event.changedTouches[0].clientY;
    const deltaX = endX - startX;
    const deltaY = endY - startY;

    // 如果向下滑动距离超过50px，并且主要是在Y轴上滑动，则关闭弹框
    if (deltaY > 50 && Math.abs(deltaY) > Math.abs(deltaX)) {
        closePopup();
    }
};

const closePopup = () => {
    emit("close");
    showPopup.value = false;
};
</script>

<style scoped></style>
