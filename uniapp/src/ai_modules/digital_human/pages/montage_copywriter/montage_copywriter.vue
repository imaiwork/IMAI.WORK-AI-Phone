<template>
    <view class="h-screen bg-white">
        <u-navbar :is-fixed="false" :border-bottom="false" is-custom-back-icon :custom-back="back">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-bold text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="p-4">
            <template v-if="!isNewsBody">
                <view class="border-[0] border-b border-solid border-[#EDEDED]">
                    <u-input
                        v-model="formData.title"
                        placeholder="点击此输入标题"
                        height="120"
                        maxlength="50"
                        placeholder-style="font-size: 32rpx; font-weight: 600; color: ##838383;" />
                </view>
                <view class="mt-4">
                    <u-input
                        v-model="formData.content"
                        placeholder="粘贴你的口播文案或者输入内容"
                        type="textarea"
                        height="600"
                        maxlength="500"
                        placeholder-style="color: #C0C3C4;"
                        :auto-height="false" />
                    <view class="text-right mt-4 text-[#C0C3C4]"> {{ formData.content?.length }}/500 </view>
                </view>
            </template>
            <template v-else>
                <view
                    v-for="(item, index) in newsBodyData"
                    class="border-[0] border-b-[1rpx] border-solid border-[#F1F2F5] pb-2"
                    :key="index"
                    :class="{
                        'mb-[28rpx] ': index < newsBodyData.length - 1,
                    }">
                    <view class="text-[28rpx] font-bold mb-2">标题{{ index + 1 }}</view>
                    <u-input
                        v-model="newsBodyData[index]"
                        placeholder-style="color: #7C7E80; "
                        maxlength="100"></u-input>
                </view>
            </template>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const formData = reactive({
    title: "",
    content: "",
});

const newsBodyData = ref<string[]>(Array.from({ length: 5 }, () => ""));

const montageType = ref<MontageTypeEnum>();

const isNewsBody = computed(() => {
    return montageType.value == MontageTypeEnum.NEWS_BODY;
});

const back = () => {
    if (isNewsBody.value) {
        if (newsBodyData.value.every((item) => item.trim() === "")) {
            uni.$u.toast("所有标题不能为空");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.MONTAGE_COPYWRITER,
            data: [newsBodyData.value],
        });
    } else {
        if (!formData.title && !formData.content) {
            uni.navigateBack();
            return;
        }
        if (!formData.title) {
            uni.$u.toast("请输入标题");
            return;
        } else if (!formData.content) {
            uni.$u.toast("请输入口播内容");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.MONTAGE_COPYWRITER,
            data: [formData],
        });
    }

    uni.navigateBack();
};

onLoad((options: any) => {
    if (options.montageType) {
        montageType.value = parseInt(options.montageType);
        if (options.data) {
            const data = JSON.parse(options.data);
            newsBodyData.value = data;
        }
    } else {
        if (options.data) {
            const data = JSON.parse(options.data);
            formData.title = data.title;
            formData.content = data.content;
        }
    }
});
</script>

<style scoped></style>
