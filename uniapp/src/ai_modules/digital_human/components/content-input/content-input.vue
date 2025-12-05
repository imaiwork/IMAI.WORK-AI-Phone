<template>
    <u-popup
        v-model="show"
        width="90%"
        mode="bottom"
        :mask="false"
        :mask-close-able="false"
        :custom-style="{
            background: 'transparent',
        }"
        @close="close">
        <view
            class="bg-white p-4 rounded-lg mx-2 flex flex-col overflow-hidden"
            :style="{ height: `${getContentHeight}px` }">
            <view class="flex">
                <view class="p-2" @click="close()">
                    <u-icon name="arrow-left" size=""></u-icon>
                </view>
            </view>
            <view class="mt-2 grow min-h-0">
                <view class="bg-[#F7FBFF] rounded-lg p-2">
                    <textarea
                        class="h-[300rpx] w-full"
                        v-model="inputValue"
                        placeholder="请输入或粘贴您的文案 ..."
                        placeholder-style="color: #00000033; font-size: 26rpx;"
                        confirm-type=""
                        :disable-default-padding="true"
                        :show-confirm-bar="false"
                        :maxlength="textLimit"></textarea>
                    <view class="flex justify-end mt-2">
                        <u-button
                            type="primary"
                            :custom-style="{
                                height: '46rpx',
                                fontSize: '24rpx',
                            }"
                            @click="close()">
                            完成
                        </u-button>
                    </view>
                </view>
                <view class="flex justify-end mt-2">
                    <view class="text-[#B2B2B2] text-[26rpx]"> {{ inputValue.length }}/{{ textLimit }} </view>
                </view>
            </view>
            <view class="w-full mt-3">
                <view v-if="dynamicHeight > 0" @click="hideKeyboard">
                    <image src="/static/images/common/keyboard.png" class="w-[48rpx] h-[48rpx]"></image>
                </view>
            </view>
            <view :style="{ height: `${dynamicHeight + 10}px` }"></view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import useKeyboardHeight from "@/hooks/useKeyboardHeight";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    textLimit: {
        type: Number,
        default: -1,
    },
});

const emit = defineEmits(["update:model-value", "close"]);

const inputValue = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:model-value", val);
    },
});

const hideKeyboard = () => {
    uni.hideKeyboard();
};
const { dynamicHeight } = useKeyboardHeight();
const show = ref(false);

const open = () => {
    show.value = true;
};

const close = () => {
    emit("close");
    show.value = false;
};

const getContentHeight = computed(() => {
    const { screenHeight, safeArea } = uni.$u.sys();
    const systemInfo = uni.getSystemInfoSync();
    return screenHeight - safeArea.top - (systemInfo.platform == "ios" ? 44 : 48);
});

defineExpose({
    open,
});
</script>

<style scoped></style>
