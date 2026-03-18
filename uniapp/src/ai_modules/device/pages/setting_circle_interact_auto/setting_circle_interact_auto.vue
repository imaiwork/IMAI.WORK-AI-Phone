<template>
    <view class="min-h-screen pb-[250rpx] bg-[#F5F6FA]" v-if="!loading">
        <view class="px-[32rpx] pt-[24rpx]">
            <view class="text-[28rpx] font-semibold text-[#1A1A1A] mb-[16rpx]">互动设置</view>
            <view class="bg-white rounded-[24rpx] px-[36rpx] py-[32rpx]">
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-x-[20rpx]">
                        <view class="bg-[#FEF2F2] rounded-full flex items-center justify-center w-[72rpx] h-[72rpx]">
                            <image src="@/ai_modules/device/static/icons/like.svg" class="w-[34rpx] h-[34rpx]" />
                        </view>
                        <view>
                            <view class="font-medium text-[28rpx] text-[#1A1A1A]">自动点赞</view>
                            <view class="text-[22rpx] text-[#00000040] mt-[4rpx]">浏览朋友圈自动点赞</view>
                        </view>
                    </view>
                    <u-switch v-model="formData.is_like" :size="40" :active-value="1" :inactive-value="0" />
                </view>

                <view class="my-[28rpx] h-[1rpx] bg-[#F5F5F5]" />

                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-x-[20rpx]">
                        <view class="bg-[#EFFDF4] rounded-full flex items-center justify-center w-[72rpx] h-[72rpx]">
                            <image src="@/ai_modules/device/static/icons/comment.svg" class="w-[34rpx] h-[34rpx]" />
                        </view>
                        <view>
                            <view class="font-medium text-[28rpx] text-[#1A1A1A]">自动评论</view>
                            <view class="text-[22rpx] text-[#00000040] mt-[4rpx]">客户动态下自动评论</view>
                        </view>
                    </view>
                    <u-switch v-model="formData.is_comment" :size="40" :active-value="1" :inactive-value="0" />
                </view>
            </view>

            <view class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[48rpx] mb-[16rpx]">评论设置</view>
            <view class="bg-white rounded-[24rpx] p-[32rpx]">
                <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-full">
                    <view class="grid grid-cols-2 relative h-[72rpx]">
                        <view
                            v-for="(item, index) in [
                                { label: 'AI 拟人评论', value: 1 },
                                { label: '固定话术随机', value: 2 },
                            ]"
                            :key="index"
                            class="flex flex-col items-center justify-center rounded-[16rpx] relative z-10 transition-colors duration-500 text-[24rpx] font-medium"
                            :class="formData.comment_method == item.value ? 'text-primary' : 'text-[#00000060]'"
                            @click="
                                formData.comment_method = item.value;
                                commentIndex = index;
                            ">
                            {{ item.label }}
                        </view>
                        <view class="tab-slider" :style="{ transform: `translateX(${commentIndex * 100}%)` }" />
                    </view>
                </view>

                <template v-if="commentIndex === 0">
                    <view class="mt-[28rpx] bg-[#F8F9FF] rounded-[16rpx] px-[24rpx] py-[24rpx]">
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-x-[12rpx]">
                                <image src="/static/images/icons/success.svg" class="w-[32rpx] h-[32rpx]" />
                                <text class="text-[28rpx] font-medium text-[#1A1A1A]">评论机器人提示词</text>
                            </view>
                            <view class="flex items-center gap-x-[6rpx]" @click="openCommentPrompt">
                                <text class="text-[26rpx] text-[#4A6CF7] font-medium">修改</text>
                                <u-icon name="arrow-right" color="#4A6CF7" size="20" />
                            </view>
                        </view>
                        <view class="text-[22rpx] text-[#00000050] mt-[16rpx] leading-[1.7]">
                            自动读取客户朋友圈图文内容，结合当前 IP
                            <text class="text-primary font-medium">【{{ detail.industryType }}】</text>
                            人设，由 AI 实时生成千人千面的专属评论。
                        </view>
                    </view>
                </template>

                <view class="flex flex-wrap gap-2 mt-[28rpx]" v-if="commentIndex === 1">
                    <view
                        v-for="(item, index) in formData.comment_speech"
                        :key="index"
                        class="border border-solid border-[#E8E8E8] rounded-[20rpx] px-[20rpx] py-[12rpx] flex items-center gap-x-[10rpx] break-all text-[26rpx] text-[#333]"
                        @click="handleEditCommentContent(index)">
                        {{ item }}
                        <view
                            class="flex-shrink-0 rounded-full flex items-center justify-center w-[32rpx] h-[32rpx] bg-[#0000003A]"
                            @click.stop="handleCommentContentDelete(index)">
                            <u-icon name="close" color="#ffffff" size="16" />
                        </view>
                    </view>
                    <view
                        class="border border-solid border-primary rounded-[20rpx] px-[28rpx] h-[64rpx] flex items-center justify-center gap-x-[6rpx]"
                        @click="handleEditCommentContent(-1)">
                        <u-icon name="plus" color="#0065FB" size="20" />
                        <text class="text-primary font-medium text-[26rpx]">添加</text>
                    </view>
                </view>
            </view>

            <view class="text-[28rpx] font-semibold text-[#1A1A1A] mt-[48rpx] mb-[16rpx]">防封控与频率限制</view>
            <view class="bg-white rounded-[24rpx] p-[32rpx]">
                <view class="flex items-start gap-x-[16rpx] bg-[#E7F9FF] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                    <view class="w-[6rpx] self-stretch rounded-full bg-[#0089BF] flex-shrink-0 mt-[4rpx]" />
                    <text class="text-[#0089BF] text-[22rpx] leading-[1.7] flex-1">
                        已开启"拟人随机停顿"。每次互动后，系统将随机停留 30秒~2分钟，模拟真人浏览行为，降低风控风险。
                    </text>
                </view>

                <view class="flex items-center justify-between mt-[40rpx] mb-[20rpx]">
                    <view class="font-medium text-[28rpx] text-[#1A1A1A]"
                        >每天互动人数
                        <text class="text-[22rpx] text-[#00000040] font-normal">（仅互动当天）</text>
                    </view>
                    <view class="bg-[#F0F3FF] rounded-[12rpx] px-[20rpx] py-[6rpx]">
                        <text class="text-primary font-semibold text-[30rpx]">{{ formData.number }}</text>
                        <text class="text-primary text-[22rpx] ml-[4rpx]">人</text>
                    </view>
                </view>

                <u-slider v-model="formData.number" height="12" inactive-color="#F2F2F2" min="1" max="30" />
                <view class="flex items-center justify-between text-[22rpx] text-[#00000045] font-medium mt-[14rpx]">
                    <text>保守（防封）</text>
                    <text>激进（易封）</text>
                </view>
            </view>
        </view>

        <view class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#F0F0F0] pt-[20rpx] pb-[48rpx] px-[32rpx]">
            <view
                class="rounded-[20rpx] h-[96rpx] bg-black text-white font-medium text-[30rpx] flex items-center justify-center"
                @click="handleSaveConfig">
                确定保存
            </view>
        </view>
    </view>

    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="固定话术"
        @confirm="handleKeywordsEditConfirm" />
</template>

<script setup lang="ts">
import {
    marketingAnalysisDetail,
    getAutoCircleInteractionTaskConfigDetail,
    updateAutoCircleInteractionTaskConfig,
} from "@/api/device";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import { setFormData } from "@/utils/util";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { on } = useEventBusManager();

const deviceCode = ref("");
const detail = ref<any>({
    id: "",
    industryType: "",
});

const formData = reactive<{
    id: number;
    is_like: number;
    is_comment: number;
    comment_method: number;
    comment_speech: string[];
    number: number;
    comment_robot_prompt: string;
}>({
    id: 0,
    is_like: 0,
    is_comment: 0,
    comment_method: 0,
    comment_speech: [],
    number: 15,
    comment_robot_prompt: "",
});

const openCommentPrompt = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/setting_prompt/setting_prompt",
        params: {
            type: "circle_interaction",
            prompt: formData.comment_robot_prompt,
        },
    });
};

const commentIndex = ref(0);
const keywordsEditRef = shallowRef<InstanceType<typeof KeywordsEdit>>();
const showKeywordsEdit = ref(false);
const keywordsEditIndex = ref(-1);

const handleEditCommentContent = (index: number) => {
    showKeywordsEdit.value = true;
    keywordsEditIndex.value = index;
    keywordsEditRef.value?.setFormData(index > -1 ? formData.comment_speech[index] : "");
};

const handleCommentContentDelete = (index: number) => {
    formData.comment_speech.splice(index, 1);
};

const handleKeywordsEditConfirm = (value: string) => {
    if (keywordsEditIndex.value === -1) {
        formData.comment_speech.push(value);
    } else {
        formData.comment_speech[keywordsEditIndex.value] = value;
    }
    keywordsEditIndex.value = -1;
    showKeywordsEdit.value = false;
};

const handleSaveConfig = async () => {
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await updateAutoCircleInteractionTaskConfig({
            device_code: deviceCode.value,
            ...formData,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "none",
            duration: 3000,
        });
        uni.navigateBack();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    } finally {
        uni.hideLoading();
    }
};

const loading = ref(true);
const getDetail = async () => {
    try {
        const configRes = await getAutoCircleInteractionTaskConfigDetail({
            device_code: deviceCode.value,
        });
        const analysisRes = await marketingAnalysisDetail({
            device_code: deviceCode.value,
        });

        detail.value.industryType = analysisRes.report?.result?.Operations?.industryType;
        setFormData(configRes, formData);
        commentIndex.value = formData.comment_method == 1 ? 0 : 1;
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    getDetail();
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CIRCLE_INTERACT_PROMPT) {
            formData.comment_robot_prompt = data;
        }
    });
});
</script>

<style scoped lang="scss">
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
    box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.08);
}
</style>
