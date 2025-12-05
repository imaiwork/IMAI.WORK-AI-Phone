<template>
    <popup-bottom v-model:show="showPopup" title="请选择版本" custom-class="bg-[#F9FAFB]">
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
import { DigitalHumanModelVersionEnum } from "@/ai_modules/digital_human/enums";
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});
const emit = defineEmits(["update:show", "confirm"]);

const showPopup = computed({
    get() {
        return props.show;
    },
    set(val) {
        emit("update:show", val);
    },
});
const appStore = useAppStore();
const modelChannel = computed(() => {
    const { channel } = appStore.getDigitalHumanConfig;
    if (channel && channel.length > 0) {
        return channel.filter((item: any) => item.status == 1 && DigitalHumanModelVersionEnum.CHANJING == item.id);
    }
    return [];
});

const currModel = ref();

const chooseModel = (id: string | number) => {
    currModel.value = id;
    emit("confirm", id);
    showPopup.value = false;
};
</script>

<style scoped></style>
