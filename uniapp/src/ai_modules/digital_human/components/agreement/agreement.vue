<template>
    <u-mask :show="show">
        <view class="h-full flex flex-col justify-center items-center">
            <view class="flex flex-col gap-2 rounded-lg h-[70vh] bg-white w-[80%]">
                <view class="text-xl font-medium text-center pt-4"> {{ title }} </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 text-xs whitespace-pre-wrap">
                            <rich-text :nodes="getPrivacy"></rich-text>
                        </view>
                    </scroll-view>
                </view>
                <view class="h-[100rpx] flex items-center flex-shrink-0 bg-white" style="border-top: 1px solid #f0f0f0">
                    <view
                        class="flex-1 text-center h-full flex items-center justify-center font-medium"
                        @click="closeAgreement()">
                        关闭
                    </view>
                    <view class="w-[2rpx] h-full bg-[#f0f0f0]"> </view>
                    <view
                        class="flex-1 text-center h-full flex items-center justify-center text-[#0065FB] font-medium"
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

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        title?: string;
        content?: string;
    }>(),
    {
        title: "克隆协议",
        content: "",
    },
);

const emit = defineEmits<{
    (event: "agree"): void;
    (event: "close"): void;
    (event: "update:modelValue", value: boolean): void;
}>();

const show = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

const appStore = useAppStore();

const getPrivacy = computed(() => {
    return props.content || appStore.getDigitalHumanConfig?.privacy;
});

const closeAgreement = () => {
    show.value = false;
    emit("close");
};

const agreeClone = () => {
    emit("agree");
};
</script>

<style scoped></style>
