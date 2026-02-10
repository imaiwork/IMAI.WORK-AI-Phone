<template>
    <view class="min-h-screen">
        <u-navbar
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view class="whitespace-nowrap text-[32rpx] font-medium text-[#19C979]">完成</view>
            </template>
        </u-navbar>
        <view class="p-4">
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
                        :maxlength="maxContentLength"
                        placeholder-style="color: #C0C3C4;"
                        :auto-height="false" />
                    <view class="text-right py-4 text-[#C0C3C4]">
                        {{ formData.content.length }}/{{ maxContentLength }}
                    </view>
                </view>
            </view>
            <view class="mt-4 bg-white rounded-[20rpx] p-4">
                <view class="flex items-center justify-between">
                    <text class="text-[30rpx] font-medium">标签</text>
                    <view class="flex items-center gap-x-2" v-if="formData.topic.length < 5" @click="handleAddTag">
                        <image
                            src="@/ai_modules/device/static/images/common/add_circle.png"
                            class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-medium">新增标签</text>
                    </view>
                </view>
                <view class="mt-5 flex flex-wrap gap-2" v-if="formData.topic.length > 0">
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
                <view class="my-5 flex items-center justify-center" v-else>
                    <text class="text-[#C0C3C4] text-[28rpx]">暂无标签</text>
                </view>
            </view>
        </view>
    </view>
    <tag-popup
        ref="tagPopRef"
        v-model="showAddTagPopup"
        :title="editTagIndex == -1 ? '新增标签' : '编辑标签'"
        @confirm="handleTagConfirm"></tag-popup>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import TagPopup from "@/ai_modules/digital_human/components/keywords-edit/keywords-edit.vue";
import { setFormData } from "@/utils/util";

const { emit } = useEventBusManager();

const formData = reactive<any>({
    title: "",
    content: "",
    topic: [],
});

const maxContentLength = 2000;
const showAddTagPopup = ref(false);
const tagPopRef = ref<any>(null);
const editTagIndex = ref(-1);

const handleAddTag = () => {
    editTagIndex.value = -1;
    showAddTagPopup.value = true;
};

const handleTagConfirm = (res: any) => {
    if (editTagIndex.value === -1) {
        formData.topic.push(res);
    } else {
        formData.topic[editTagIndex.value] = res;
    }
    showAddTagPopup.value = false;
};

const handleDeleteTag = (index: number) => {
    formData.topic.splice(index, 1);
};

const handleEditTag = (index: number) => {
    editTagIndex.value = index;
    showAddTagPopup.value = true;
    tagPopRef.value.setFormData(formData.topic[index]);
};

const back = () => {
    if (!formData.title) {
        uni.$u.toast("请输入标题");
        return;
    }
    uni.navigateBack();
    emit("confirm", {
        type: ListenerTypeEnum.PLATFORM_PUBLISH_COPYWRITER,
        data: formData.title ? [formData] : [],
    });
};

onLoad((options: any) => {
    if (options.copywriter) {
        const data = JSON.parse(options.copywriter);
        setFormData(data, formData);
    }
});
</script>

<style scoped lang="scss">
.topic-item {
    @apply border border-solid border-[#E5E5E5] rounded-[100rpx] text-[#000000b3] px-[28rpx] py-[16rpx] font-medium relative;
}
</style>
