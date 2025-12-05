<template>
    <view class="flex flex-col px-[32rpx]">
        <view class="rounded-xl bg-[#00000005] p-[18rpx] mt-[46rpx]">
            <view class="">
                <u-input
                    v-model="formData[currentGreetingContentSettingType]"
                    class="w-full"
                    type="textarea"
                    placeholder="请输入AI提示词"
                    placeholder-style="font-size:26rpx;color:rgba(0,0,0,0.2)"
                    :height="800"
                    :auto-height="false"
                    :maxlength="1000" />
            </view>
            <view class="text-[26rpx] text-right mt-[14rpx]">
                {{ formData[currentGreetingContentSettingType].length }}/1000
            </view>
        </view>
        <view class="text-end text-primary mt-2" @click="handleDefaultPrompt"> 一键填写默认数据 </view>
        <view class="flex gap-x-[24rpx] mt-[36rpx] w-full">
            <view class="flex-1">
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{
                        flex: 1,
                        height: '100rpx',
                        boxShadow: '0 6px 12px 0 rgba(0, 101, 251, 0.20)',
                        fontSize: '26rpx',
                    }"
                    @click="handleConfirm"
                    >确定</u-button
                >
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getScenePrompt as getScenePromptApi } from "@/api/app";

enum GreetingContentSettingTypeEnum {
    ADD_FRIEND = "add_friends_prompt",
    PRIVATE_CHAT = "private_message_prompt",
}

const formData = reactive<any>({
    private_message_prompt: "",
    add_friends_prompt: "",
});

const currentGreetingContentSettingType = ref<GreetingContentSettingTypeEnum>(
    GreetingContentSettingTypeEnum.PRIVATE_CHAT
);

const handleDefaultPrompt = () => {
    if (currentGreetingContentSettingType.value == GreetingContentSettingTypeEnum.PRIVATE_CHAT) {
        formData.private_message_prompt = scenePrompt.value.find((item: any) => item.id == 21)?.prompt_text;
    } else {
        formData.add_friends_prompt = scenePrompt.value.find((item: any) => item.id == 22)?.prompt_text;
    }
};

const handleConfirm = () => {
    uni.$emit("save", {
        type: currentGreetingContentSettingType.value,
        prompt: formData[currentGreetingContentSettingType.value],
    });
    uni.navigateBack();
};

const scenePrompt = ref<any[]>([]);
const getScenePrompt = async () => {
    const res = await getScenePromptApi();
    scenePrompt.value = res;
};

onLoad(({ type, prompt }: any) => {
    currentGreetingContentSettingType.value = type;
    if (prompt) {
        formData[type] = decodeURIComponent(JSON.parse(prompt));
    }
    getScenePrompt();
});
</script>

<style scoped></style>
