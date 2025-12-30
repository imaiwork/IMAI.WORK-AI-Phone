<template>
    <popup-bottom v-model="show" title="请选择版本" custom-class="bg-[#F9FAFB]" @close="close">
        <template #content>
            <view class="px-[32rpx] pt-[30rpx]">
                <view
                    v-for="(item, index) in modelChannel"
                    class="flex items-start mb-[16rpx] gap-x-[24rpx] bg-white rounded-[24rpx] p-[32rpx]"
                    :key="index"
                    @click="chooseModel(item.id)">
                    <view class="flex-shrink-0 p-1 leading-[0]">
                        <image class="w-[72rpx] h-[72rpx]" :src="item.icon"></image>
                    </view>
                    <view>
                        <view class="text-[26rpx]">{{ item.name }}</view>
                        <view class="mt-[16rpx] text-[22rpx] text-[#0000004d]"> {{ item.described }} </view>
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
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.filter(
            (item: any) =>
                item.status == 1 &&
                (DigitalHumanModelVersionEnum.CHANJING == item.id || DigitalHumanModelVersionEnum.STANDARD == item.id)
        );
    }
    return [];
});

const currModel = ref();

const chooseModel = (id: string | number) => {
    currModel.value = id;
    show.value = false;
    emit("confirm", id);
};

const close = () => {
    show.value = false;
    emit("close");
};
</script>

<style scoped></style>
