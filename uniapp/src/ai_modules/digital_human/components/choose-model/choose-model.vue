<template>
    <popup-bottom v-model="show" title="请选择版本" custom-class="bg-[#F9FAFB]" @close="close">
        <template #content>
            <view class="px-[32rpx] pt-[30rpx]">
                <view
                    v-for="(item, index) in modelChannel"
                    class="flex items-center mb-[16rpx] gap-x-[24rpx] bg-white rounded-[24rpx] p-[32rpx]"
                    :key="index"
                    @click="chooseModel(item.model_version)">
                    <view class="flex-shrink-0 p-1 leading-[0]">
                        <image class="w-[72rpx] h-[72rpx]" :src="item.logo"></image>
                    </view>
                    <view>
                        <view class="">{{ item.name }}</view>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    modelVersion: {
        type: Array,
        default: () => [],
    },
});
const emit = defineEmits(["update:modelValue", "confirm", "close"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});
const appStore = useAppStore();
const modelChannel = computed(() => {
    const channel = appStore.getAiModelConfig.humanModels;
    if (channel && channel.length > 0) {
        if (props.modelVersion.length) {
            return channel.filter((item: any) => props.modelVersion.includes(parseInt(item.model_version)));
        }
        return channel;
    }
    return [];
});

const chooseModel = (modelVersion: string | number) => {
    show.value = false;
    emit("confirm", modelVersion);
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
