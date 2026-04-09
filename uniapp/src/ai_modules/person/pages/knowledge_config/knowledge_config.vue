<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[220rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="知识库配置" title-bold />

        <view class="px-[30rpx] pt-2">
            <view
                class="flex items-start gap-3 bg-[#EBF3FF] border border-solid border-[#C5D9FF] rounded-[24rpx] px-4 py-4 mb-6">
                <view
                    class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                    <u-icon name="info-circle-fill" color="#ffffff" size="26"></u-icon>
                </view>
                <text class="text-[25rpx] text-[#2D5FBF] leading-[1.7] font-medium flex-1">
                    系统将在自动生成
                    <text class="text-primary font-bold">24H视频</text>
                    时，根据知识库中的内容，来生成视频中对应的文案，如果为空则不参考
                </text>
            </view>

            <view class="mb-6">
                <view class="flex items-center gap-2 mb-3 px-1">
                    <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#E6F0FF] flex items-center justify-center">
                        <u-icon name="bag-fill" color="#0065FB" size="28"></u-icon>
                    </view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">我的主营业务/产品</text>
                    <view class="ml-auto px-3 py-1 bg-[#FFF1F0] rounded-full">
                        <text class="text-[22rpx] font-bold text-primary">选填</text>
                    </view>
                </view>

                <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                    <text class="text-[24rpx] text-[#b4b4b4] leading-relaxed block mb-4">
                        告诉AI您具体卖什么、提供什么服务。
                    </text>
                    <view
                        class="rounded-[20rpx] px-4 py-3 border border-solid relative"
                        :class="focus1 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                        <view
                            v-if="!formData.business"
                            class="absolute top-[24rpx] left-[30rpx] right-[30rpx] pointer-events-none">
                            <text class="text-[26rpx] text-[#c0c4cc] block mb-2">请描述您具体卖什么？</text>
                            <text class="text-[23rpx] text-[#d0d3dc] block mb-1">例如：</text>
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 正宗重庆老火锅，主打鲜切毛肚</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 短视频运营与剪辑线上课程</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 企业级AI智能客服SaaS系统</text
                            >
                        </view>
                        <textarea
                            v-model="formData.business"
                            class="w-full text-[#333] leading-[1.8] min-h-[160rpx] max-h-[320rpx] text-[28rpx]"
                            :maxlength="maxLength"
                            :auto-height="false"
                            :show-confirm-bar="false"
                            @focus="focus1 = true"
                            @blur="focus1 = false" />
                        <view class="flex justify-end mt-1">
                            <text class="text-[22rpx] text-[#c0c4cc]"
                                >{{ formData.business.length }}/{{ maxLength }}</text
                            >
                        </view>
                    </view>
                </view>
            </view>

            <view class="mb-6">
                <view class="flex items-center gap-2 mb-3 px-1">
                    <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center">
                        <u-icon name="account-fill" color="#00C08E" size="28"></u-icon>
                    </view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">目标客户与痛点</text>
                    <view class="ml-auto px-3 py-1 bg-[#FFF1F0] rounded-full">
                        <text class="text-[22rpx] font-bold text-primary">选填</text>
                    </view>
                </view>

                <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                    <text class="text-[24rpx] text-[#b4b4b4] leading-relaxed block mb-4">
                        告诉AI您的客户是谁，以及他们遇到了什么麻烦/需求。
                    </text>
                    <view
                        class="rounded-[20rpx] px-4 py-3 border border-solid relative"
                        :class="focus2 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                        <view
                            v-if="!formData.customer"
                            class="absolute top-[24rpx] left-[30rpx] right-[30rpx] pointer-events-none">
                            <text class="text-[26rpx] text-[#c0c4cc] block mb-2"
                                >您的客户是谁？他们遇到了什么麻烦？</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block mb-1">例如：</text>
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 周边3公里不知道吃什么的大学生</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 想做副业但毫无经验的宝妈</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 获客成本高、管理效率低的中小企业老板</text
                            >
                        </view>
                        <textarea
                            v-model="formData.customer"
                            class="w-full text-[#333] leading-[1.8] min-h-[160rpx] max-h-[320rpx] text-[28rpx]"
                            :maxlength="maxLength"
                            :auto-height="false"
                            :show-confirm-bar="false"
                            @focus="focus2 = true"
                            @blur="focus2 = false" />
                        <view class="flex justify-end mt-1">
                            <text class="text-[22rpx] text-[#c0c4cc]"
                                >{{ formData.customer.length }}/{{ maxLength }}</text
                            >
                        </view>
                    </view>
                </view>
            </view>

            <view class="mb-6">
                <view class="flex items-center gap-2 mb-3 px-1">
                    <view
                        class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center"
                        style="background: linear-gradient(135deg, #ff9a9e 0%, #a1c4fd 100%)">
                        <u-icon name="star-fill" color="#ffffff" size="28"></u-icon>
                    </view>
                    <text class="text-[30rpx] font-extrabold text-[#212121]">差异化优势与行动引导</text>
                    <view class="ml-auto px-3 py-1 bg-[#F0F7FF] rounded-full">
                        <text class="text-[22rpx] font-bold text-primary">选填</text>
                    </view>
                </view>

                <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                    <text class="text-[24rpx] text-[#b4b4b4] leading-relaxed block mb-4">
                        告诉AI为什么选您，以及视频最后用什么福利引导客户留资或到店。
                    </text>
                    <view
                        class="rounded-[20rpx] px-4 py-3 border border-solid relative"
                        :class="focus3 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                        <view
                            v-if="!formData.advantage"
                            class="absolute top-[24rpx] left-[30rpx] right-[30rpx] pointer-events-none">
                            <text class="text-[26rpx] text-[#c0c4cc] block mb-2">为什么选您？想要客户做什么？</text>
                            <text class="text-[23rpx] text-[#d0d3dc] block mb-1">例如：</text>
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 30年祖传秘方，凭视频到店送特色菜</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 10年实战经验，私信回复"学习"领资料</text
                            >
                            <text class="text-[23rpx] text-[#d0d3dc] block leading-[1.8]"
                                >· 服务过500强企业，点击主页免费定制方案</text
                            >
                        </view>
                        <textarea
                            v-model="formData.advantage"
                            class="w-full text-[#333] leading-[1.8] min-h-[160rpx] max-h-[320rpx] text-[28rpx]"
                            :maxlength="maxLength"
                            :auto-height="false"
                            :show-confirm-bar="false"
                            @focus="focus3 = true"
                            @blur="focus3 = false" />
                        <view class="flex justify-end mt-1">
                            <text class="text-[22rpx] text-[#c0c4cc]"
                                >{{ formData.advantage.length }}/{{ maxLength }}</text
                            >
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view
            class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] z-50">
            <u-button
                type="primary"
                shape="circle"
                :ripple="true"
                :custom-style="{
                    height: '96rpx',
                    fontSize: '30rpx',
                    fontWeight: '900',
                    border: 'none',
                    boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                }"
                @click="handleSave">
                保存配置
            </u-button>
            <view class="text-center mt-2.5">
                <text class="text-[22rpx] text-[#b4b4b4]">配置自动同步至关联设备</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, updateKnowledgeConfig } from "@/api/person";

const personId = ref<string>("");
const formData = reactive({
    business: "",
    customer: "",
    advantage: "",
});

const maxLength = 2000;

const focus1 = ref(false);
const focus2 = ref(false);
const focus3 = ref(false);

const handleSave = async () => {
    uni.showLoading({
        title: "保存中...",
        mask: true,
    });
    try {
        await updateKnowledgeConfig({
            persona_id: personId.value,
            main_business: formData.business,
            target_pain_points: formData.customer,
            conversion_hook: formData.advantage,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => {
            uni.navigateBack();
        }, 1500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const init = async () => {
    const { main_business, target_pain_points, conversion_hook } = await getPersonDetail({ id: personId.value });
    formData.business = main_business || "";
    formData.customer = target_pain_points || "";
    formData.advantage = conversion_hook || "";
};

onLoad((options: any) => {
    personId.value = options.id;
    init();
});
</script>
