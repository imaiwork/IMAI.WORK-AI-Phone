<template>
    <view class="h-screen bg-white">
        <u-navbar :is-fixed="false" :border-bottom="false" is-custom-back-icon :custom-back="back">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-bold text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="p-4">
            <view class="mt-4">
                <u-input
                    v-model="formData.content"
                    placeholder="粘贴你的口播文案或者输入内容"
                    type="textarea"
                    height="400"
                    placeholder-style="color: #C0C3C4;"
                    focus="true"
                    :maxlength="textLimit"
                    :auto-height="false" />
                <view class="text-right mt-4 text-[#C0C3C4]"> {{ formData.content?.length }}/{{ textLimit }} </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const formData = reactive({
    content: "",
});

const textLimit = ref(0);
const back = () => {
    if (!formData.content) {
        uni.navigateBack();
        return;
    }
    emit("confirm", {
        type: ListenerTypeEnum.SZR_COPYWRITER,
        data: formData.content,
    });

    uni.navigateBack();
};

onLoad((options: any) => {
    textLimit.value = parseInt(options.limit);
    if (options.content) {
        formData.content = options.content;
    }
});
</script>

<style scoped></style>
