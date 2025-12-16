<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title-bold
            title="一句话生成视频"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-4 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="common-step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[20rpx]">
            <view class="h-full flex flex-col" v-if="step == 1">
                <view class="flex-shrink-0 px-4 mt-2">
                    <view class="bg-white rounded-[20rpx] px-[8rpx]">
                        <view class="grid grid-cols-2 gap-x-1 h-[100rpx] relative">
                            <view
                                v-for="(item, index) in themeTypes"
                                :key="index"
                                class="theme-type-item"
                                :class="{ active: item.value == themeType }"
                                @click="handleThemeType(item.value)">
                                {{ item.label }}
                            </view>
                            <view
                                class="tab-slider"
                                :style="{ transform: `translateX(${themeTypeIndex * 100}%)` }"></view>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view
                        :scroll-y="dynamicHeight == 0"
                        :scroll-top="dynamicHeight == 0 ? 0 : dynamicHeight / 2"
                        class="h-full">
                        <view class="px-4 pb-[100rpx]">
                            <view v-show="themeType == ThemeType.MARKETING">
                                <view class="text-[30rpx] font-bold">营销类型</view>
                                <view class="mt-[20rpx] flex flex-wrap gap-2">
                                    <view
                                        v-for="item in marketingTypes"
                                        :key="item.value"
                                        class="marketing-type-item"
                                        :class="{ active: item.value == marketingType }"
                                        @click="handleMarketingType(item.value)">
                                        {{ item.label }}
                                    </view>
                                </view>
                            </view>
                            <view v-show="themeType == ThemeType.CREATION || themeType == ThemeType.LONG_VIDEO">
                                <view class="text-[30rpx] font-bold">视频类型</view>
                                <view class="mt-[20rpx] flex flex-wrap gap-2">
                                    <view
                                        v-for="item in videoTypes"
                                        :key="item.value"
                                        class="video-type-item"
                                        :class="{ active: item.value == videoType }"
                                        @click="handleVideoType(item.value)">
                                        {{ item.label }}
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[50rpx]">
                                <view class="text-[30rpx] font-bold">出镜人性别</view>
                                <view class="mt-[20rpx] flex flex-wrap gap-2">
                                    <view
                                        v-for="item in genders"
                                        :key="item.value"
                                        class="gender-type-item"
                                        :class="{ active: item.value == gender }"
                                        @click="handleGender(item.value)">
                                        {{ item.label }}
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[50rpx]" v-show="themeType == ThemeType.MARKETING">
                                <view class="text-[30rpx] font-bold">{{ getMarketingText.title }}描述</view>
                                <view class="mt-[20rpx] bg-white px-[36rpx] py-[30rpx] rounded-[20rpx]">
                                    <textarea
                                        class="w-full text-base"
                                        v-model="formData.content"
                                        type="textarea"
                                        height="300"
                                        placeholder-style="font-size: 26rpx;"
                                        confirm-type="done"
                                        :disable-default-padding="true"
                                        :show-confirm-bar="false"
                                        :adjust-position="false"
                                        :auto-height="false"
                                        :maxlength="maxDescLength"
                                        :placeholder="getMarketingText.placeholder" />
                                    <view class="flex justify-end text-[#0000004d] text-xs mt-2">
                                        {{ formData.content.length }}/{{ maxDescLength }}
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[50rpx]" v-if="themeType == ThemeType.CREATION">
                                <view class="text-[30rpx] font-bold">关键细节描述</view>
                                <view class="mt-[20rpx] bg-white px-[36rpx] py-[30rpx] rounded-[20rpx]">
                                    <textarea
                                        class="w-full text-base"
                                        v-model="formData.content"
                                        type="textarea"
                                        height="300"
                                        placeholder-style="font-size: 26rpx;"
                                        confirm-type="done"
                                        :disable-default-padding="true"
                                        :show-confirm-bar="false"
                                        :adjust-position="false"
                                        :auto-height="false"
                                        :maxlength="maxDescLength"
                                        placeholder="(选填)，例如：一个青年女性介绍这个商品" />
                                    <view class="flex justify-end text-[#0000004d] text-xs mt-2">
                                        {{ formData.content.length }}/{{ maxDescLength }}
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="flex-shrink-0" :style="{ height: dynamicHeight + 'px' }"></view>
                    </scroll-view>
                </view>
            </view>
            <view class="h-full flex flex-col" v-if="step == 2">
                <view class="px-4">
                    <view class="text-[30rpx] font-bold">参考素材</view>
                    <view class="mt-[24rpx] bg-white rounded-[20rpx] px-[8rpx]">
                        <view class="grid grid-cols-2 gap-x-1 h-[100rpx] relative">
                            <view
                                v-for="(item, index) in ['使用素材', '不使用素材']"
                                :key="index"
                                class="material-type-item"
                                :class="{ active: index == isMaterial }"
                                @click="isMaterial = index">
                                <text class="ml-[12rpx]">{{ item }}</text>
                            </view>
                            <view class="tab-slider" :style="{ transform: `translateX(${isMaterial * 100}%)` }"></view>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0" v-if="isMaterial === 0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[26rpx] p-4">
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view
                                    class="h-[220rpx] rounded-[12rpx] relative overflow-hidden"
                                    @click="previewMaterial(item)">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] flex items-center justify-center z-[88]">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteMaterial(item.id)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                v-if="formData.materialList.length < 1"
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                                @click="uploadAndProcessFiles('image')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view class="h-full" v-if="step == 3">
                <scroll-view scroll-y>
                    <view class="px-4 flex flex-col gap-y-[50rpx]">
                        <view>
                            <view class="text-[30rpx] font-bold">视频风格</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoStyles"
                                    :key="index"
                                    :class="{ active: formData.videoStyle == item.value }"
                                    @click="formData.videoStyle = item.value">
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>

                        <view>
                            <view class="text-[30rpx] font-bold">视频比例</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoProportions"
                                    :key="index"
                                    :class="{ active: formData.aspect_ratio == item.value }"
                                    @click="formData.aspect_ratio = item.value">
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="text-[30rpx] font-bold">视频时长</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoDurations"
                                    :key="index"
                                    :class="{ active: formData.videoDuration == item.value }"
                                    @click="formData.videoDuration = item.value">
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="text-[30rpx] font-bold">镜头切换频率</view>
                            <view class="mt-[20rpx] flex flex-wrap gap-2">
                                <view
                                    class="common-type-item"
                                    v-for="(item, index) in videoSwitchFrequencies"
                                    :key="index"
                                    :class="{ active: formData.videoSwitchFrequency == item.value }"
                                    @click="formData.videoSwitchFrequency = item.value">
                                    {{ item.label }}
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
            <view class="h-full px-4" v-if="step == 4">
                <view>
                    <view class="text-[30rpx] font-bold">视频名称</view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                        <u-input
                            class="w-full"
                            v-model="formData.name"
                            maxlength="50"
                            placeholder-style="font-size:26rpx;"
                            placeholder="请输入"
                            clearable />
                    </view>
                </view>
                <view class="bg-white rounded-[20rpx] px-[38rpx] mt-[20rpx]">
                    <view
                        class="h-[108rpx] flex items-center justify-between gap-x-2 border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                        <view class="text-[30rpx] font-bold">生成视频数量</view>
                        <view class="flex items-center gap-x-2">
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('minus')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/minus_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                            <view
                                class="w-[90rpx] h-[52rpx] px-1 flex items-center justify-center bg-[#F6F6F6] rounded-[10rpx]">
                                <u-input
                                    v-model="formData.video_count"
                                    type="digit"
                                    placeholder=""
                                    :min="1"
                                    :max="99"
                                    :custom-style="{ color: '#0065fb', fontWeight: 'bold' }"
                                    input-align="center" />
                            </view>
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('add')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                        </view>
                    </view>
                    <view class="h-[108rpx] flex items-center justify-between gap-x-2">
                        <view class="text-[30rpx] font-bold">{{
                            themeType == ThemeType.LONG_VIDEO ? "智能剪辑" : "AI优化内容"
                        }}</view>
                        <view class="flex items-center gap-x-1">
                            <u-switch
                                v-model="formData.ai_type"
                                :active-value="1"
                                :inactive-value="0"
                                inactive-color="#E5E5E5"
                                :size="40"></u-switch>
                        </view>
                    </view>
                </view>
                <view class="bg-white rounded-[20rpx] px-[38rpx] mt-[20rpx]">
                    <view
                        class="h-[108rpx] flex items-center justify-between gap-x-2 border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                        <view class="text-[30rpx] font-bold">视频主题</view>
                        <view class="flex items-center gap-x-1" @click="handleStep(1)">
                            <view>{{ themeTypeValue.label }}</view>
                            <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                        </view>
                    </view>
                    <view
                        class="h-[108rpx] flex items-center justify-between gap-x-2 border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                        <view class="text-[30rpx] font-bold">出镜人性别</view>
                        <view class="flex items-center gap-x-1" @click="handleStep(1)">
                            <view>{{ genders.find((item) => item.value == gender)?.label }}</view>
                            <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                        </view>
                    </view>
                    <view
                        class="h-[108rpx] flex items-center justify-between gap-x-2 border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                        <view class="text-[30rpx] font-bold">参考素材</view>
                        <view class="flex items-center gap-x-1" @click="handleStep(2)">
                            <view>
                                共<text class="mx-1 text-primary font-bold">{{ formData.materialList.length }}</text
                                >个
                            </view>
                            <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                        </view>
                    </view>
                    <view class="h-[108rpx] flex items-center justify-between gap-x-2">
                        <view class="text-[30rpx] font-bold">风格设置</view>
                        <view class="flex items-center gap-x-1" @click="handleStep(3)">
                            <view>
                                {{ videoStyles.find((item) => item.value == formData.videoStyle)?.label }};
                                {{ videoProportions.find((item) => item.value == formData.aspect_ratio)?.label }};
                                {{ videoDurations.find((item) => item.value == formData.videoDuration)?.label }};
                                {{
                                    videoSwitchFrequencies.find((item) => item.value == formData.videoSwitchFrequency)
                                        ?.label
                                }}
                            </view>
                            <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center px-4 h-[140rpx]" :class="[step == 1 ? 'justify-end' : 'justify-between']">
                <template v-if="step != steps.length">
                    <view
                        v-if="step != 1"
                        class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                        @click="handleStep(step, 'prev')">
                        上一步
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view class="flex flex-col items-center gap-y-2" @click="showTokensCost = true">
                        <image
                            src="@/ai_modules/digital_human/static/icons/star.svg"
                            class="w-[36rpx] h-[36rpx]"></image>
                        <text class="text-[#8C8C8C] text-[22rpx]">算力消耗</text>
                    </view>
                    <view
                        class="rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateVideo">
                        立即生成（{{ formData.video_count }}个）
                    </view>
                </template>
            </view>
        </view>
    </view>
    <u-popup
        v-model="showCustomVideoType"
        mode="center"
        width="90%"
        :border-radius="20"
        @close="closeCustomVideoTypePopup">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-bold text-center mt-2">输入视频主题</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="newVideoTypeVal"
                    placeholder="请输入"
                    maxlength="50"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-bold text-[#000000b3]"
                    @click="closeCustomVideoTypePopup">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-bold text-white"
                    @click="handleCustomVideoTypeConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <create-success-pop
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
    <tokens-cost v-model="showTokensCost" :type="5" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { createSoraVideo } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import useKeyboardHeight from "@/hooks/useKeyboardHeight";
import useUpload from "@/hooks/useUpload";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";

enum ThemeType {
    MARKETING = "marketing",
    CREATION = "creation",
    LONG_VIDEO = "long_video",
}

enum MarketingType {
    GOODS = "goods",
    STORE = "store",
    GROUP = "group",
}

enum Gender {
    MALE = "male",
    FEMALE = "female",
    NOBODY = "nobody",
}

enum VideoType {
    // 宣传片
    AD = "ad",
    // 产品展示
    PRODUCT = "product",
    // 品牌推广
    BRAND = "brand",
    // Vlog
    VLOG = "vlog",
    // 剧情片
    STORY = "story",
    // 教程
    TUTORIAL = "tutorial",
    // 自定义
    CUSTOM = "custom",
}

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const formData = reactive<{
    name: string;
    content: string;
    materialList: any[];
    videoStyle: number;
    videoResolution: number;
    aspect_ratio: string;
    videoDuration: number;
    videoSwitchFrequency: number;
    video_count: number;
    ai_type: 0 | 1;
}>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "一句话生成视频",
    content: "",
    materialList: [],
    videoStyle: 1,
    videoResolution: 1,
    aspect_ratio: "16:9",
    videoDuration: 1,
    videoSwitchFrequency: 1,
    video_count: 1,
    ai_type: 1,
});

// 步骤
const steps = ref([
    { step: 1, title: "视频主题" },
    { step: 2, title: "素材参考" },
    { step: 3, title: "风格设置" },
    { step: 4, title: "生成设置" },
]);
const step = ref(1);

const { dynamicHeight } = useKeyboardHeight();

// 视频主题类型
const themeTypes = [
    {
        label: "营销模版",
        value: ThemeType.MARKETING,
    },
    { label: "自由创作", value: ThemeType.CREATION },
];

// 选中的视频主题类型
const themeType = ref<ThemeType>(ThemeType.MARKETING);
const themeTypeIndex = computed(() => themeTypes.findIndex((item) => item.value == themeType.value));
const themeTypeValue = computed(() => themeTypes[themeTypeIndex.value]);
// 营销类型
const marketingTypes = [
    {
        label: "商品宣传",
        value: MarketingType.GOODS,
    },
    { label: "门店宣传", value: MarketingType.STORE },
    { label: "团购套餐宣传", value: MarketingType.GROUP },
];
// 选中的营销类型
const marketingType = ref<MarketingType>(MarketingType.GOODS);

// 出镜人性别类型
const genders = [
    {
        label: "男",
        value: Gender.MALE,
    },
    { label: "女", value: Gender.FEMALE },
    { label: "无人物", value: Gender.NOBODY },
];
const gender = ref<Gender>(Gender.MALE);

// 视频类型
const videoTypes = ref([
    {
        label: "宣传片",
        value: VideoType.AD,
    },
    { label: "产品展示", value: VideoType.PRODUCT },
    { label: "品牌推广", value: VideoType.BRAND },
    { label: "Vlog", value: VideoType.VLOG },
    { label: "剧情片", value: VideoType.STORY },
    { label: "教程", value: VideoType.TUTORIAL },
    { label: "自定义", value: VideoType.CUSTOM, isInt: false },
]);
const videoType = ref<VideoType>(VideoType.AD);

// 视频风格
const videoStyles = [
    {
        label: "标准",
        value: 1,
    },
    { label: "商业", value: 2 },
    { label: "纪实", value: 3 },
    { label: "温馨", value: 4 },
    { label: "极简", value: 5 },
    { label: "科幻", value: 6 },
    { label: "色彩", value: 7 },
];

// 视频比例
const videoProportions = [
    { label: "横屏16:9", value: "16:9" },
    { label: "竖屏9:16", value: "9:16" },
];

// 视频时长
const videoDurations = [{ label: "10s", value: 1 }];

// 镜头切换频率
const videoSwitchFrequencies = [
    { label: "中", value: 1 },
    { label: "低", value: 2 },
    { label: "高", value: 3 },
];

const showCustomVideoType = ref(false);
const newVideoTypeVal = ref("");
// 描述最大长度
const maxDescLength = 500;
// 是否使用素材
const isMaterial = ref(0);
const showCreateSuccess = ref(false);
const showTokensCost = ref(false);
// 创建结果
const createResult = ref<any>(null);

const rechargePopupRef = shallowRef();

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return true;
        case 2:
            return isMaterial.value === 0 ? formData.materialList.length > 0 : true;
        case 3:
            return true;
        default:
            return false;
    }
};

// 获取对应营销
const getMarketingText = computed(() => {
    return {
        [MarketingType.GOODS]: {
            title: "商品宣传",
            placeholder: "(选填)，例如：该商品是xx品牌，主要功能是xx，面向的人群是xx等",
        },
        [MarketingType.STORE]: {
            title: "门店宣传",
            placeholder: "(选填)，例如：该店位于xx位置，主打的是xx产品等",
        },
        [MarketingType.GROUP]: {
            title: "团购套餐宣传",
            placeholder: "(选填)，例如：该套餐名称叫xx，原价xx元，现在活动团购价xx元，套餐里包含xx等",
        },
    }[marketingType.value];
});

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 点击“上一步”
    if (type === "prev") {
        step.value--;
        return;
    }

    // 点击“下一步”
    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: string } = {
                1: "请填写视频标题和视频描述",
                2: "请至少添加一条素材",
            };
            uni.$u.toast(messages[step.value] || "请完成当前步骤");
        }
        return;
    }

    // 直接点击步骤条进行跳转
    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleThemeType = (type: ThemeType) => {
    if (themeType.value == type) return;
    themeType.value = type;
};

const handleMarketingType = (type: MarketingType) => {
    if (marketingType.value == type) return;
    marketingType.value = type;
};

const handleGender = (type: Gender) => {
    if (gender.value == type) return;
    gender.value = type;
};

const handleVideoType = (type: VideoType) => {
    if (type == VideoType.CUSTOM) {
        showCustomVideoType.value = true;
        const data = videoTypes.value.find((item) => item.value == type);
        if (data?.isInt) {
            newVideoTypeVal.value = data.label;
        }
    } else {
        videoType.value = type;
    }
};

const closeCustomVideoTypePopup = () => {
    showCustomVideoType.value = false;
    newVideoTypeVal.value = "";
};

const handleCustomVideoTypeConfirm = () => {
    if (!newVideoTypeVal.value) {
        uni.$u.toast("请输入内容");
        return;
    }
    showCustomVideoType.value = false;
    videoType.value = VideoType.CUSTOM;
    videoTypes.value.forEach((item) => {
        if (item.value == VideoType.CUSTOM) {
            item.label = newVideoTypeVal.value;
            item.isInt = true;
        }
    });
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    count: 1,
    onSuccess: (materials: any[]) => {
        formData.materialList = formData.materialList.concat(materials);
    },
});

const previewMaterial = (item: any) => {
    const { pic } = item;
    uni.previewImage({
        urls: [pic],
    });
};

const handleDeleteMaterial = (id: number) => {
    formData.materialList = formData.materialList.filter((item: any) => item.id !== id);
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.video_count <= 1) {
            uni.$u.toast("最少生成1个视频哦");
            return;
        }
        formData.video_count--;
    } else {
        if (formData.video_count >= 99) {
            uni.$u.toast("最多生成99个视频哦");
            return;
        }
        formData.video_count++;
    }
};

const handleCreateVideo = async () => {
    // 判断是否有算力
    if (userTokens.value < 0) {
        rechargePopupRef.value?.open();
        return;
    }
    if (!formData.name) {
        uni.$u.toast("请输入视频名称");
        return;
    }
    if (formData.video_count <= 0) {
        uni.$u.toast("请输入生成视频数量");
        return;
    }
    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        const res = await createSoraVideo({
            name: formData.name,
            theme: videoTypes.value.find((item) => item.value == videoType.value)?.label,
            content: formData.content,
            gender: genders.find((item) => item.value == gender.value)?.label,
            image_urls: formData.materialList.map((item: any) => item.pic),
            style: videoStyles.find((item) => item.value == formData.videoStyle)?.label,
            frequency: videoSwitchFrequencies.find((item) => item.value == formData.videoSwitchFrequency)?.label,
            aspect_ratio: formData.aspect_ratio,
            duration: formData.videoDuration,
            number: formData.video_count,
        });
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

// 去发布
const toPublish = () => {
    showCreateSuccess.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        type: "reLaunch",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: MontageTypeEnum.SORA_VIDEO,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
    });
};
</script>

<style scoped lang="scss">
.theme-type-item,
.material-type-item {
    @apply flex items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500;
    &.active {
        @apply text-black font-bold relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-[#F9FAFB] absolute top-[4rpx] left-0 transition-all duration-500;
    &::after {
        content: "";
        @apply absolute bottom-0 w-[20%] h-[4rpx] bg-black;
        // 让线居中
        left: 0;
        right: 0;
        margin: auto;
    }
}
.marketing-type-item,
.video-type-item,
.common-type-item {
    @apply px-[28rpx] h-[72rpx] flex items-center justify-center rounded-[10rpx] bg-white;
    &.active {
        @apply bg-black text-white font-bold;
    }
}
.gender-type-item {
    @apply w-[160rpx] h-[72rpx] flex items-center justify-center rounded-[10rpx] bg-white;
    &.active {
        @apply bg-black text-white font-bold;
    }
}
</style>
