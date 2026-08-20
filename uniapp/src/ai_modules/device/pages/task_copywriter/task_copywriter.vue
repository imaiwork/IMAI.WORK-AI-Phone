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

        <view class="px-[24rpx] pt-[16rpx] flex flex-col gap-[16rpx]">
            <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                <view class="px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                    <view class="flex items-center justify-between mb-[8rpx]">
                        <view class="flex items-center gap-[16rpx]">
                            <text class="text-xs font-semibold text-[#9CA3AF]">标题</text>
                            <view
                                class="flex items-center gap-[10rpx] bg-[#F7F9FC] rounded-full px-[16rpx] h-[44rpx] border border-solid"
                                :class="formData.is_title_show === 1 ? 'border-[#0065fb]/30' : 'border-[#E5E9F0]'"
                                @click="toggleTitleShow">
                                <view
                                    class="w-[52rpx] h-[28rpx] rounded-full relative transition-all duration-300"
                                    :style="{
                                        background:
                                            formData.is_title_show === 1
                                                ? 'linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                                : '#D1D5DB',
                                    }">
                                    <view
                                        class="absolute top-[4rpx] w-[20rpx] h-[20rpx] bg-white rounded-full shadow-sm transition-all duration-300"
                                        :style="{
                                            left: formData.is_title_show === 1 ? '28rpx' : '4rpx',
                                        }" />
                                </view>
                                <text
                                    class="text-[22rpx] font-semibold"
                                    :class="formData.is_title_show === 1 ? 'text-primary' : 'text-[#9CA3AF]'">
                                    {{ formData.is_title_show === 1 ? "显示" : "隐藏" }}
                                </text>
                            </view>
                        </view>
                        <text class="text-[22rpx] text-[#C0C4CC]">{{ formData.title?.length || 0 }}/30</text>
                    </view>

                    <view class="mb-[12rpx]">
                        <text
                            class="text-[22rpx]"
                            :class="formData.is_title_show === 1 ? 'text-primary' : 'text-[#9CA3AF]'">
                            {{
                                formData.is_title_show === 1
                                    ? "已开启，小红书等平台将展示此标题"
                                    : "已关闭，平台将不展示此标题"
                            }}
                        </text>
                    </view>

                    <view
                        class="transition-all duration-300"
                        :style="{ opacity: formData.is_title_show === 1 ? 1 : 0.4 }">
                        <u-input
                            v-model="formData.title"
                            placeholder="点击此输入标题"
                            height="72"
                            maxlength="30"
                            :disabled="formData.is_title_show === 0"
                            placeholder-style="font-size:32rpx;font-weight:600;color:#C0C4CC;"
                            :custom-style="{
                                fontSize: '32rpx',
                                fontWeight: '600',
                                color: '#0D1117',
                            }" />
                    </view>
                </view>

                <view class="px-[28rpx] py-[22rpx]">
                    <view class="flex items-center justify-between mb-[12rpx]">
                        <text class="text-xs font-semibold text-[#9CA3AF]">正文</text>
                        <text
                            class="text-[22rpx]"
                            :class="
                                formData.content?.length >= maxContentLength
                                    ? 'text-[#F56C6C] font-bold'
                                    : 'text-[#C0C4CC]'
                            ">
                            {{ formData.content?.length || 0 }}/{{ maxContentLength }}
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
                            @click="handleEditTag(index)">
                            <text class="text-xs font-semibold text-primary">{{ topic }}</text>
                            <view
                                class="absolute -top-[10rpx] -right-[10rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#374151]/50 flex items-center justify-center shadow-sm"
                                @click.stop="handleDeleteTag(index)">
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

    <u-popup v-model="showAddTagPopup" mode="center" width="90%" :border-radius="20">
        <view class="bg-white rounded-[28rpx] overflow-hidden">
            <view
                class="flex items-center justify-center h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5] relative">
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">
                    {{ editTagIndex === -1 ? "新增" : "编辑" }}标签
                </text>
                <view
                    class="absolute right-[24rpx] w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                    @click="handleAddTagCancel">
                    <u-icon name="close" size="18" color="#6B7280" />
                </view>
            </view>

            <view class="px-[32rpx] pt-[32rpx] pb-[40rpx]">
                <view class="bg-[#F7F9FC] rounded-[20rpx] px-[24rpx] py-[16rpx] border border-solid border-[#E5E9F0]">
                    <u-input
                        v-model="newTopic"
                        placeholder="请输入标签内容"
                        maxlength="20"
                        placeholder-style="color:#C0C4CC;font-size:28rpx;" />
                </view>
                <text class="text-[20rpx] text-[#C0C4CC] mt-[12rpx] block text-right">{{ newTopic.length }}/20</text>

                <view class="flex items-center gap-[16rpx] mt-[32rpx]">
                    <view
                        class="flex-1 h-[88rpx] flex items-center justify-center rounded-[20rpx] bg-[#F3F4F6]"
                        @click="handleAddTagCancel">
                        <text class="text-[28rpx] font-semibold text-[#6B7280]">取消</text>
                    </view>
                    <view
                        class="flex-1 h-[88rpx] flex items-center justify-center rounded-[20rpx] shadow-[0_6rpx_16rpx_rgba(0,101,251,0.28)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="handleTagConfirm">
                        <text class="text-[28rpx] font-extrabold text-white">确定</text>
                    </view>
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit } = useEventBusManager();

const formData = reactive<any>({
    title: "",
    content: "",
    topic: [],
    is_title_show: 1,
});

const maxContentLength = 2000;
const showAddTagPopup = ref(false);
const newTopic = ref("");
const editTagIndex = ref(-1);

const toggleTitleShow = () => {
    formData.is_title_show = formData.is_title_show === 1 ? 0 : 1;
};

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
    if (formData.is_title_show === 1 && !formData.title) {
        uni.$u.toast("请输入标题");
        return;
    }
    if (formData.is_title_show === 0) {
        formData.title = "";
    }
    uni.navigateBack();
    emit("confirm", {
        type: ListenerTypeEnum.TASK_COPYWRITER,
        data: formData.title || formData.content || formData.topic.length ? formData : null,
    });
};

onLoad((options: any) => {
    if (options.copywriter) {
        const data = JSON.parse(options.copywriter);
        formData.title = data.title;
        formData.content = data.content;
        formData.topic = data.topic;
        formData.is_title_show = Number(data.is_title_show) ?? 1;
    }
});
</script>

<style scoped></style>
