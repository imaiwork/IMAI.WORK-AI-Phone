<template>
    <view class="min-h-screen">
        <u-navbar
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-bold text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="px-4">
            <view class="rounded-[20rpx] bg-white px-4">
                <view class="border-[0] border-b border-solid border-[#EDEDED]">
                    <u-input
                        v-model="formData.title"
                        placeholder="点击此输入标题"
                        height="120"
                        maxlength="30"
                        placeholder-style="font-size: 32rpx; font-weight: 600; color: ##838383;" />
                </view>
                <view class="mt-4">
                    <u-input
                        v-model="formData.content"
                        placeholder="请输入或粘贴文本内容"
                        type="textarea"
                        height="400"
                        maxlength="500"
                        placeholder-style="color: #C0C3C4;"
                        :auto-height="false" />
                    <view class="text-right py-4 text-[#C0C3C4]"> {{ formData.content.length }}/2000 </view>
                </view>
            </view>
            <view class="mt-[66rpx]">
                <view class="flex items-center justify-between">
                    <text class="text-[30rpx] font-bold">标签</text>
                    <view class="flex items-center gap-x-2" @click="handleAddTag">
                        <image
                            src="@/ai_modules/device/static/images/common/add_circle.png"
                            class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold">新增标签</text>
                    </view>
                </view>
                <view class="mt-5 flex flex-wrap gap-2">
                    <view
                        v-for="(topic, index) in formData.topic"
                        :key="index"
                        class="topic-item"
                        @click="handleEditTag(index)">
                        #{{ topic }}
                        <view
                            class="absolute top-[-10rpx] right-[-10rpx] w-[32rpx] h-[32rpx] rounded-full bg-[#0000004d] flex items-center justify-center"
                            @click.stop="handleDeleteTag(index)">
                            <u-icon name="close" size="16" color="#ffffff"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
    <u-popup v-model="showAddTagPopup" mode="center" width="90%" :border-radius="20">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">{{ editTagIndex === -1 ? "新增" : "编辑" }}标签</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newTopic"
                    placeholder="请输入标签"
                    maxlength="20"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="handleAddTagCancel">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-primary font-bold text-white"
                    @click="handleTagConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/device/enums";

const formData = reactive<any>({
    title: "",
    content: "",
    topic: [],
});

const showAddTagPopup = ref(false);
const newTopic = ref("");

const editTagIndex = ref(-1);

const handleAddTag = () => {
    editTagIndex.value = -1;
    showAddTagPopup.value = true;
};

const handleTagConfirm = () => {
    if (!newTopic.value) {
        uni.$u.toast("请输入标签");
        return;
    }
    if (editTagIndex.value === -1) {
        formData.topic.push(newTopic.value);
    } else {
        formData.topic[editTagIndex.value] = newTopic.value;
    }
    showAddTagPopup.value = false;
    newTopic.value = "";
};

const handleAddTagCancel = () => {
    showAddTagPopup.value = false;
    newTopic.value = "";
};

const handleDeleteTag = (index: number) => {
    formData.topic.splice(index, 1);
};

const handleEditTag = (index: number) => {
    editTagIndex.value = index;
    showAddTagPopup.value = true;
    newTopic.value = formData.topic[index];
};

const back = () => {
    if (!formData.title) {
        uni.$u.toast("请输入标题");
        return;
    }
    uni.navigateBack();
    uni.$emit("confirm", {
        type: ListenerTypeEnum.TASK_COPYWRITER,
        data: formData.title ? [formData] : [],
    });
};

onLoad((options: any) => {
    if (options.copywriter) {
        const data = JSON.parse(options.copywriter);
        formData.title = data.title;
        formData.content = data.content;
        formData.topic = data.topic;
    }
});
</script>

<style scoped lang="scss">
.topic-item {
    @apply bg-white rounded-[10rpx] text-[#000000b3] px-[28rpx] py-[16rpx] font-bold relative;
}
</style>
