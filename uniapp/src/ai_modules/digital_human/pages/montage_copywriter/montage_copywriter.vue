<template>
    <view class="h-screen bg-white">
        <u-navbar :is-fixed="false" :border-bottom="false" is-custom-back-icon :custom-back="back">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-medium text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="p-4">
            <template v-if="!isNewsBody">
                <view class="border-[0] border-b border-solid border-[#EDEDED]">
                    <u-input
                        v-model="formData.title"
                        placeholder="点击此输入标题"
                        height="120"
                        :maxlength="100"
                        placeholder-style="font-size: 32rpx; font-weight: 600; color: ##838383;" />
                </view>
                <view class="mt-4" v-if="!isNewsBody">
                    <u-input
                        v-model="formData.content"
                        placeholder="粘贴你的口播文案或者输入内容"
                        type="textarea"
                        height="600"
                        maxlength="500"
                        placeholder-style="color: #C0C3C4;"
                        :auto-height="false" />
                    <view class="text-right mt-4 text-[#C0C3C4]"> {{ formData.content?.length || 0 }}/500 </view>
                </view>
            </template>
            <template v-else>
                <u-input
                    v-model="formData.title"
                    placeholder="点击此输入标题"
                    type="textarea"
                    :maxlength="1000"
                    height="400"
                    placeholder-style="color: #C0C3C4;" />
                <view class="text-right mt-4 text-[#C0C3C4]"> {{ formData.title?.length || 0 }}/1000 </view>
            </template>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const formData = reactive({
    title: "",
    content: "",
});

const isNewsBody = ref(false);

const back = () => {
    if (isNewsBody.value) {
        if (formData.title.trim() === "") {
            uni.$u.toast("请输入标题");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.MONTAGE_COPYWRITER,
            data: [formData.title],
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
        } else if (formData.content.trim().length < 3) {
            uni.$u.toast("口播内容不能少于10个字");
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
    if (options.isNewsBody) {
        isNewsBody.value = true;
        if (options.data) {
            formData.title = options.data;
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
