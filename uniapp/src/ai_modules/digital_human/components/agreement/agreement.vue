<template>
    <u-mask :show="showAgreement">
        <view class="h-full flex flex-col justify-center items-center">
            <view class="flex flex-col gap-2 rounded-lg h-[70vh] bg-white w-[80%]">
                <view class="text-xl font-bold text-center pt-4"> 克隆协议 </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full p">
                        <view class="px-4 text-xs whitespace-pre-wrap">
                            <rich-text :nodes="getPrivacy"></rich-text>
                        </view>
                    </scroll-view>
                </view>
                <view class="h-[100rpx] flex items-center flex-shrink-0" style="border-top: 1px solid #f0f0f0">
                    <view
                        class="flex-1 text-center h-full flex items-center justify-center font-bold"
                        @click="closeAgreement()">
                        关闭
                    </view>
                    <view class="w-[2rpx] h-full bg-[#f0f0f0]"> </view>
                    <view
                        class="flex-1 text-center h-full flex items-center justify-center text-[#0065FB] font-bold"
                        @click="agreeClone()">
                        同意并使用
                    </view>
                </view>
            </view>
        </view>
    </u-mask>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const props = defineProps<{
    showAgreement: boolean;
}>();

const emit = defineEmits<{
    (event: "agree"): void;
    (event: "close"): void;
    (event: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get() {
        return props.showAgreement;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const appStore = useAppStore();

const getPrivacy = computed(() => {
    return appStore.getDigitalHumanConfig?.privacy;
});

const closeAgreement = () => {
    emit("close");
};

const agreeClone = () => {
    emit("agree");
};
</script>

<style scoped></style>
