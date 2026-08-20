<template>
    <view class="min-h-screen bg-[#F7F9FC]">
        <u-navbar
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view
                    class="flex items-center justify-center h-[56rpx] px-[28rpx] rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                    <text class="text-[28rpx] font-extrabold text-white tracking-wide whitespace-nowrap">完成</text>
                </view>
            </template>
        </u-navbar>

        <view class="px-[24rpx] pt-[16rpx] pb-[60rpx] flex flex-col gap-[16rpx]">
            <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                <view class="px-[28rpx] pt-[28rpx] pb-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="text-xs font-semibold text-[#9CA3AF]">标题</text>
                        <text class="text-[22rpx] text-[#C0C4CC]">{{ formData.title.length }}/30</text>
                    </view>
                    <u-input
                        v-model="formData.title"
                        placeholder="点击此输入标题"
                        height="72"
                        maxlength="30"
                        placeholder-style="font-size:32rpx;font-weight:600;color:#C0C4CC;"
                        :custom-style="{ fontSize: '32rpx', fontWeight: '600', color: '#0D1117' }" />
                </view>

                <view class="px-[28rpx] pt-[20rpx] pb-[16rpx]">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="text-xs font-semibold text-[#9CA3AF]">正文</text>
                        <text
                            class="text-[22rpx]"
                            :class="
                                formData.content.length >= maxContentLength
                                    ? 'text-[#F56C6C] font-bold'
                                    : 'text-[#C0C4CC]'
                            ">
                            {{ formData.content.length }}/{{ maxContentLength }}
                        </text>
                    </view>
                    <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-input
                            v-model="formData.content"
                            placeholder="请输入或粘贴文本内容"
                            type="textarea"
                            height="360"
                            :maxlength="maxContentLength"
                            placeholder-style="font-size:26rpx;color:#C0C4CC;"
                            :auto-height="false" />
                    </view>
                </view>
            </view>

            <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                <view
                    class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[28rpx] font-extrabold text-[#0D1117]">标签</text>
                        <view class="bg-[#F0F2F5] rounded-full px-[14rpx] h-[36rpx] flex items-center">
                            <text class="text-[22rpx] text-[#9CA3AF]">{{ formData.topic.length }}/5</text>
                        </view>
                    </view>
                    <view
                        v-if="formData.topic.length < 5"
                        class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[20rpx] h-[56rpx]"
                        @click="handleAddTag">
                        <u-icon name="plus" color="#0065fb" size="22" />
                        <text class="text-xs font-semibold text-primary">新增标签</text>
                    </view>
                    <view v-else class="flex items-center gap-[6rpx] bg-[#FEF2F2] rounded-full px-[20rpx] h-[56rpx]">
                        <u-icon name="info-circle" color="#F56C6C" size="20" />
                        <text class="text-[22rpx] text-[#F56C6C]">最多 5 个</text>
                    </view>
                </view>

                <view class="px-[28rpx] py-[24rpx]">
                    <view v-if="formData.topic.length > 0" class="flex flex-wrap gap-[16rpx]">
                        <view
                            v-for="(topic, index) in formData.topic"
                            :key="index"
                            class="relative flex items-center gap-[6rpx] bg-[#EBF2FF] rounded-full px-[24rpx] h-[64rpx] border border-solid border-[#0065fb]/20"
                            @click="handleEditTag(Number(index))">
                            <text class="text-xs font-semibold text-primary">#{{ topic }}</text>
                            <view
                                class="absolute -top-[10rpx] -right-[10rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#374151] flex items-center justify-center shadow-sm"
                                @click.stop="handleDeleteTag(Number(index))">
                                <u-icon name="close" size="14" color="#ffffff" />
                            </view>
                        </view>
                    </view>
                    <view v-else class="flex flex-col items-center justify-center py-[32rpx] gap-[12rpx]">
                        <view class="w-[80rpx] h-[80rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center">
                            <u-icon name="tags" color="#C0C4CC" size="36" />
                        </view>
                        <text class="text-xs text-[#C0C4CC]">暂无标签，点击右上角新增</text>
                    </view>
                </view>
            </view>
        </view>
    </view>

    <tag-popup
        ref="tagPopRef"
        v-model="showAddTagPopup"
        :title="editTagIndex === -1 ? '新增标签' : '编辑标签'"
        @confirm="handleTagConfirm" />
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

<style scoped></style>
