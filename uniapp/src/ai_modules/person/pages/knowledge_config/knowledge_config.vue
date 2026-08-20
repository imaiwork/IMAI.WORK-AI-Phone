<template>
    <view class="min-h-screen bg-[#F5F7FA] pb-[250rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#FFFFFF' }" title="知识库配置" title-bold />

        <template v-if="loading">
            <view class="px-[24rpx] pt-[24rpx]">
                <view class="bg-white rounded-[32rpx] h-[120rpx] mb-[24rpx] animate-pulse"></view>
                <view v-for="i in 4" :key="i" class="bg-white rounded-[32rpx] p-[32rpx] mb-[24rpx] animate-pulse">
                    <view class="flex items-center gap-[16rpx] mb-[24rpx]">
                        <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6]"></view>
                        <view class="h-[32rpx] w-[200rpx] bg-[#F3F4F6] rounded-full"></view>
                        <view class="ml-auto h-[32rpx] w-[80rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="h-[200rpx] bg-[#F3F4F6] rounded-[20rpx]"></view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="flex items-center gap-[12rpx] px-[32rpx] pt-[12rpx] pb-[24rpx] bg-white">
                <view class="w-[44rpx] h-[44rpx] bg-[#F2F5FF] rounded-full flex items-center justify-center">
                    <u-icon name="account-fill" color="#3B71E8" size="22"></u-icon>
                </view>
                <text class="text-[24rpx] text-[#666666]">当前配置IP：</text>
                <text class="text-[26rpx] font-bold text-[#1A1A1A]">{{ personaName }}</text>
            </view>
            <view class="px-[30rpx] pt-2">
                <view
                    class="flex items-start gap-[24rpx] bg-[#EBF3FF] border border-solid border-[#C5D9FF] rounded-[32rpx] px-[28rpx] py-[28rpx] mb-[24rpx]">
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <u-icon name="info-circle-fill" color="#ffffff" size="26"></u-icon>
                    </view>
                    <text class="text-[25rpx] text-[#2D5FBF] leading-[1.7] font-medium flex-1">
                        系统将在自动生成<text class="text-primary font-bold">24H视频</text
                        >时，根据知识库中的内容来生成视频文案，如果为空则不参考。
                    </text>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('biz')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#E6F0FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="bag-fill" color="#0065FB" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">我的主营业务/产品</text>
                        <view class="px-[16rpx] py-[6rpx] bg-[#F5F5F5] rounded-[32rpx]">
                            <text class="text-[22rpx] font-bold text-[#888888]">可选</text>
                        </view>
                        <u-icon
                            :name="openPanels.biz ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.biz" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <text class="text-[24rpx] text-[#B4B4B4] leading-relaxed block mb-[24rpx]"
                            >告诉AI您具体卖什么、提供什么服务。</text
                        >
                        <view
                            class="rounded-[20rpx] px-[28rpx] py-[24rpx] border border-solid relative"
                            :class="focus1 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                            <view
                                v-if="!formData.business"
                                class="absolute top-[24rpx] left-[28rpx] right-[28rpx] pointer-events-none">
                                <text class="text-[28rpx] text-[#C0C4CC] block mb-[8rpx]">请描述您具体卖什么？</text>
                                <text class="text-[23rpx] text-[#C0C4CC] block mb-[4rpx]">例如：</text>
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 正宗重庆老火锅，主打鲜切毛肚</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 短视频运营与剪辑线上课程</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
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
                            <view class="flex justify-end mt-[8rpx]">
                                <text class="text-[22rpx] text-[#C0C4CC]"
                                    >{{ formData.business.length }}/{{ maxLength }}</text
                                >
                            </view>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('cus')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center flex-shrink-0">
                            <u-icon name="account-fill" color="#00C08E" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">目标客户与痛点</text>
                        <view class="px-[16rpx] py-[6rpx] bg-[#F5F5F5] rounded-[32rpx]">
                            <text class="text-[22rpx] font-bold text-[#888888]">可选</text>
                        </view>
                        <u-icon
                            :name="openPanels.cus ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.cus" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <text class="text-[24rpx] text-[#B4B4B4] leading-relaxed block mb-[24rpx]"
                            >告诉AI您的客户是谁，以及他们遇到了什么麻烦/需求。</text
                        >
                        <view
                            class="rounded-[20rpx] px-[28rpx] py-[24rpx] border border-solid relative"
                            :class="focus2 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                            <view
                                v-if="!formData.customer"
                                class="absolute top-[24rpx] left-[28rpx] right-[28rpx] pointer-events-none">
                                <text class="text-[28rpx] text-[#C0C4CC] block mb-[8rpx]"
                                    >您的客户是谁？他们遇到了什么麻烦？</text
                                >
                                <text class="text-[23rpx] text-[#C0C4CC] block mb-[4rpx]">例如：</text>
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 周边3公里不知道吃什么的大学生</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 想做副业但毫无经验的宝妈</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
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
                            <view class="flex justify-end mt-[8rpx]">
                                <text class="text-[22rpx] text-[#C0C4CC]"
                                    >{{ formData.customer.length }}/{{ maxLength }}</text
                                >
                            </view>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('adv')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(135deg, #fde8e8 0%, #ddeeff 100%)">
                            <u-icon name="star-fill" color="#8B5CF6" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">差异化优势与行动引导</text>
                        <view class="px-[16rpx] py-[6rpx] bg-[#F5F5F5] rounded-[32rpx]">
                            <text class="text-[22rpx] font-bold text-[#888888]">可选</text>
                        </view>
                        <u-icon
                            :name="openPanels.adv ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.adv" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <text class="text-[24rpx] text-[#B4B4B4] leading-relaxed block mb-[24rpx]"
                            >告诉AI为什么选您，以及视频最后用什么福利引导客户留资或到店。</text
                        >
                        <view
                            class="rounded-[20rpx] px-[28rpx] py-[24rpx] border border-solid relative"
                            :class="focus3 ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'">
                            <view
                                v-if="!formData.advantage"
                                class="absolute top-[24rpx] left-[28rpx] right-[28rpx] pointer-events-none">
                                <text class="text-[28rpx] text-[#C0C4CC] block mb-[8rpx]"
                                    >为什么选您？想要客户做什么？</text
                                >
                                <text class="text-[23rpx] text-[#C0C4CC] block mb-[4rpx]">例如：</text>
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 30年祖传秘方，凭视频到店送特色菜</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
                                    >· 10年实战经验，私信回复"学习"领资料</text
                                >
                                <text class="text-[23rpx] text-[#D0D3DC] block leading-[1.8]"
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
                            <view class="flex justify-end mt-[8rpx]">
                                <text class="text-[22rpx] text-[#C0C4CC]"
                                    >{{ formData.advantage.length }}/{{ maxLength }}</text
                                >
                            </view>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('pub')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F0FF] flex items-center justify-center flex-shrink-0">
                            <u-icon name="shopping-cart-fill" color="#8B5CF6" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">发布附加配置</text>
                        <view class="px-[16rpx] py-[6rpx] bg-[#F5F5F5] rounded-[32rpx]">
                            <text class="text-[22rpx] font-bold text-[#888888]">可选</text>
                        </view>
                        <u-icon
                            :name="openPanels.pub ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.pub" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <view class="flex items-center justify-between mb-[28rpx]">
                            <view class="flex items-center gap-[20rpx]">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-[16rpx] bg-[#EEF5FF] flex items-center justify-center flex-shrink-0">
                                    <u-icon name="map-fill" color="#0065FB" size="32"></u-icon>
                                </view>
                                <text class="text-[28rpx] font-bold text-[#1A1A1A]">商家定位</text>
                            </view>
                            <u-switch
                                v-model="formData.locationSwitch"
                                :active-value="1"
                                :inactive-value="0"
                                :size="40" />
                        </view>

                        <view v-if="formData.locationSwitch === 1" class="mb-[24rpx]">
                            <view
                                class="flex items-center gap-[16rpx] h-[80rpx] rounded-[20rpx] px-[28rpx] border border-solid"
                                :class="
                                    focusLocation ? 'bg-[#FAFCFF] border-primary' : 'bg-[#00000005] border-[#E8E8E8]'
                                ">
                                <u-icon name="search" color="#C0C4CC" size="28" class="flex-shrink-0"></u-icon>
                                <input
                                    v-model="formData.locationKeyword"
                                    type="text"
                                    placeholder="请输入要搜索挂载的门店位置"
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    class="flex-1 text-[28rpx] text-[#333]"
                                    :maxlength="100"
                                    @focus="focusLocation = true"
                                    @blur="focusLocation = false" />
                            </view>
                        </view>

                        <view class="flex items-start gap-[10rpx] bg-[#F8F9FD] rounded-[16rpx] px-[24rpx] py-[20rpx]">
                            <u-icon
                                name="info-circle"
                                color="#9CA3AF"
                                size="22"
                                class="flex-shrink-0 mt-[2rpx]"></u-icon>
                            <text class="text-[22rpx] text-[#666666] leading-relaxed flex-1">
                                <text class="font-bold text-[#1A1A1A]">提示：</text>
                                开启后，系统在自动发布时将通过顶部搜索栏查找您填写的关键词，并挂载对应的定位；若关闭或未填写，则不携带。
                            </text>
                        </view>
                    </view>
                </view>
            </view>
            <view class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-2 z-50">
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
                    @click="handleSaveConfig">
                    保存配置
                </u-button>
                <view class="text-center mt-2.5">
                    <text class="text-[22rpx] text-[#b4b4b4]">配置自动同步至关联设备</text>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail, updateKnowledgeConfig } from "@/api/person";

const personId = ref<string>("");
const personaName = ref<string>("");
const loading = ref<boolean>(true);

const formData = reactive({
    business: "",
    customer: "",
    advantage: "",
    cartSwitch: 0 as 0 | 1,
    cartKeywords: "",
    locationSwitch: 0 as 0 | 1,
    locationKeyword: "",
});

const maxLength = 2000;

const focus1 = ref(false);
const focus2 = ref(false);
const focus3 = ref(false);
const focusLocation = ref(false);

const ALL_PANEL_KEYS = ["biz", "cus", "adv", "pub"];

const openPanels = ref<Record<string, boolean>>(Object.fromEntries(ALL_PANEL_KEYS.map((k) => [k, true])));

const togglePanel = (key: string): void => {
    openPanels.value = { ...openPanels.value, [key]: !openPanels.value[key] };
};

const handleSaveConfig = async () => {
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await updateKnowledgeConfig({
            persona_id: personId.value,
            main_business: formData.business,
            target_pain_points: formData.customer,
            conversion_hook: formData.advantage,
            is_shopping_cart: formData.cartSwitch,
            goods_name: formData.cartKeywords,
            is_store_position: formData.locationSwitch,
            store_position: formData.locationKeyword,
        });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const init = async () => {
    try {
        const {
            persona_name,
            main_business,
            target_pain_points,
            conversion_hook,
            is_shopping_cart,
            goods_name,
            is_store_position,
            store_position,
        } = await getPersonDetail({ id: personId.value });
        personaName.value = persona_name;
        formData.business = main_business || "";
        formData.customer = target_pain_points || "";
        formData.advantage = conversion_hook || "";
        formData.cartSwitch = is_shopping_cart == 1 ? 1 : 0;
        formData.cartKeywords = goods_name || "";
        formData.locationSwitch = is_store_position == 1 ? 1 : 0;
        formData.locationKeyword = store_position || "";
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    personId.value = options.id;
    init();
});
</script>
