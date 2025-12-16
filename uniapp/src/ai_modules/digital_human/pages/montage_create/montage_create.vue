<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="数字人口播混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-5 w-full">
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
        <view class="grow min-h-0 mt-[24rpx]">
            <view v-if="step === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4">
                    <text class="font-bold">选择形象</text>
                    <view class="flex items-center gap-x-1" @click="handleCreateAnchor">
                        <image
                            src="@/ai_modules/digital_human/static/icons/add.svg"
                            class="w-[28rpx] h-[28rpx]"></image>
                        <text>新增形象</text>
                    </view>
                </view>
                <view class="grow min-h-0 mt-[38rpx]">
                    <scroll-view
                        scroll-y
                        class="h-full"
                        v-if="anchorLists.length > 0"
                        @scrolltolower="handleLoadAnchorMore">
                        <view class="grid grid-cols-3 gap-4 px-4 pb-4">
                            <view
                                v-for="(item, index) in anchorLists"
                                :key="index"
                                class="h-[276rpx] rounded-xl relative overflow-hidden"
                                @click="handleSelect(item)">
                                <image :src="item.pic" lazy class="w-full h-full rounded-xl" mode="aspectFill"></image>
                                <view
                                    class="absolute right-2 bottom-2"
                                    @click.stop="previewMaterial({ pic: item.pic, url: item.anchor_url })">
                                    <image src="/static/images/icons/play.svg" class="w-[40rpx] h-[40rpx]" />
                                </view>
                                <view
                                    class="absolute top-0 left-0 w-full h-full bg-[#00000080]"
                                    v-if="formData.anchorLists.includes(item)">
                                    <view class="absolute top-2 right-2">
                                        <image
                                            src="/static/images/icons/success.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white"
                                    v-else></view>
                            </view>
                        </view>
                    </scroll-view>
                    <view class="h-full flex flex-col items-center justify-center" v-else>
                        <image
                            src="@/ai_modules/digital_human/static/images/common/avatar.png"
                            class="w-[120rpx] h-[136rpx] mx-auto"></image>
                        <view class="text-[26rpx] text-[#828282] mt-[32rpx] text-center">
                            您还没有数字人，快去定制一个吧~
                        </view>
                        <view
                            class="mt-[28rpx] mx-auto w-[202rpx] h-[68rpx] flex items-center justify-center rounded-[12rpx] text-white bg-black"
                            @click="handleCreateAnchor">
                            定制数字人
                        </view>
                    </view>
                </view>
            </view>
            <view
                v-if="step === 2"
                class="bg-white rounded-[16rpx] px-4 py-[28rpx] shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] mx-4">
                <text class="font-bold">身份信息</text>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人物名称</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_name"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人物名称"
                                maxlength="20"
                                @change="isCharacter = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人物介绍</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_introduction"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人物介绍"
                                maxlength="50"
                                @change="isCharacter = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[48rpx] flex justify-end">
                    <view
                        class="flex items-center gap-x-1 bg-[#F1F1F1] px-2 py-1 rounded-[8rpx]"
                        @click="showCharacter = true">
                        <image
                            src="@/ai_modules/digital_human/static/icons/user2.svg"
                            class="w-[24rpx] h-[24rpx]"></image>
                        <text class="text-xs">历史人设</text>
                    </view>
                </view>
            </view>
            <view v-if="step === 3" class="h-full flex flex-col">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleShowCopywriter()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold text-[32rpx]">手动输入</text>
                    </view>
                    <navigator
                        url="/ai_modules/digital_human/pages/montage_ai_copywriter/montage_ai_copywriter"
                        hover-class="none"
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]"
                        @click="editCopywriterIndex = -1">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-bold text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-4 pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleSelectCopywriter(index)">
                                <view class="text-[32rpx] font-bold mr-4">
                                    {{ item.title }}
                                </view>
                                <view class="mt-[28rpx]">
                                    {{ item.content }}
                                </view>
                                <view
                                    class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                    @click.stop="handleDeleteCopywriter(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-if="step === 4" class="h-full flex flex-col">
                <view class="mx-4">
                    <text class="font-bold">混剪素材（共{{ formData.materialList.length }}个）</text>
                    <view class="mt-1 text-xs text-[#0000004d]">
                        总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                            montageConfig.imageDuration
                        }}秒/张，视频按实际时长/个)</view
                    >
                </view>
                <view class="grow min-h-0">
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
                                        class="absolute bottom-0 h-[40rpx] w-full bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-[88]">
                                        <image
                                            v-if="item.type === 'image'"
                                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <image
                                            v-else
                                            src="@/ai_modules/digital_human/static/icons/video.svg"
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
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                                @click="chooseUploadType">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-if="step === 5" class="h-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4">
                            <view>
                                <view class="text-[30rpx] font-bold">视频名称</view>
                                <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;"
                                        placeholder="请输入" />
                                </view>
                            </view>
                            <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                                <view class="flex items-center justify-between py-2.5">
                                    <view class="text-[30rpx] font-bold">数字人形象</view>
                                    <view class="flex items-center gap-x-1" @click="handleStep(2)">
                                        <view>
                                            共<text class="mx-1 text-primary font-bold">{{
                                                formData.anchorLists.length
                                            }}</text
                                            >个
                                        </view>
                                        <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <scroll-view scroll-x class="mt-1">
                                    <view class="flex gap-x-[24rpx]">
                                        <view
                                            v-for="(item, index) in formData.anchorLists"
                                            :key="index"
                                            class="flex-shrink-0 w-[167rpx] h-[224rpx] rounded-[24rpx]">
                                            <image
                                                :src="item.pic"
                                                class="w-full h-full rounded-[24rpx]"
                                                mode="aspectFill"></image>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                                <view
                                    class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                    <view class="text-[30rpx] font-bold">身份人设</view>
                                    <view class="flex items-center">
                                        <view class="text-primary line-clamp-1 min-w-[150rpx] text-end">{{
                                            formData.person_name
                                        }}</view>
                                        <view class="w-[1rpx] h-[24rpx] bg-[#C6CACC] mx-2"></view>
                                        <view class="line-clamp-1 text-primary">
                                            {{ formData.person_introduction }}
                                        </view>
                                        <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                    <view class="text-[30rpx] font-bold">口播文案</view>
                                    <view class="flex items-center gap-x-1" @click="handleStep(3)">
                                        <view>
                                            共<text class="mx-1 text-primary font-bold">{{
                                                formData.copywriterList.length
                                            }}</text
                                            >个
                                        </view>
                                        <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                    <view class="text-[30rpx] font-bold">参考素材</view>
                                    <view class="flex items-center gap-x-1" @click="handleStep(4)">
                                        <view>
                                            共<text class="mx-1 text-primary font-bold">{{
                                                formData.materialList.length
                                            }}</text
                                            >个
                                        </view>
                                        <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between h-[106rpx]">
                                    <view class="text-[30rpx] font-bold">生成视频数量</view>
                                    <view class="flex items-center gap-x-1" @click="handleStep(2)">
                                        <view>
                                            共<text class="mx-1 text-primary font-bold">{{
                                                formData.copywriterList.length
                                            }}</text
                                            >个
                                        </view>
                                        <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="text-center text-[#B8B8B8] text-[24rpx] my-4 flex-shrink-0">
                    - 生成的视频数量由文案数量控制 -
                </view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.anchorLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-bold text-[32rpx]">{{ formData.anchorLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
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
                        生成视频（{{ formData.copywriterList.length }}个）
                    </view>
                </template>
            </view>
        </view>
    </view>
    <choose-character v-model="showCharacter" @select="handleSelectCharacter" />
    <upload-rule-pop v-model="showUploadTip" @handle-upload="chooseUploadType" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        v-model="showVideoPreview"
        title="视频预览"
        :poster="videoPreview.poster"
        :video-url="videoPreview.url" />
    <create-success-pop
        v-model="showCreateSuccess"
        title="混剪视频创建成功"
        desc="您可以立即去设置发布任务，也可以等待混剪视频成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <tokens-cost v-model="showTokensCost" :type="1" />
</template>

<script setup lang="ts">
import { getShanjianAnchorList, createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { montageConfig } from "@/ai_modules/digital_human/config";
import useMontageMaterial from "@/ai_modules/digital_human/hooks/useMontageMaterial";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "选择形象" },
    { step: 2, title: "填写身份" },
    { step: 3, title: "选择文案" },
    { step: 4, title: "参考素材" },
    { step: 5, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    shanjian_type: MontageTypeEnum;
    anchorLists: any[];
    name: string;
    copywriterList: any[];
    materialList: any[];
    person_name: string;
    person_introduction: string;
}>({
    shanjian_type: MontageTypeEnum.REAL_PERSON_MIX,
    // 形象列表
    anchorLists: [],
    // 人物名称
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "口播混剪",
    // 人物介绍
    person_name: "",
    person_introduction: "",
    copywriterList: [],
    // 素材列表
    materialList: [],
});

// 形象列表
const anchorLists = ref<any[]>([]);
const anchorLoading = ref(false);
const anchorFinished = ref(false);
const anchorQueryParams = reactive({
    page_no: 1,
    page_size: 20,
    status: 6,
});

// 历史人设列表
const showCharacter = ref(false);
// 是否是历史人设
const isCharacter = ref(false);

// 编辑文案索引
const editCopywriterIndex = ref(-1);

// 上传素材提示显示
const showUploadTip = ref(false);
// 是否第一次打开
const isFirstOpen = ref(true);
const showVideoPreview = ref(false);
// 视频预览数据
const videoPreview = reactive({
    poster: "",
    url: "",
});

// 充值弹窗
const rechargePopupRef = shallowRef();
// 显示创建成功
const showCreateSuccess = ref(false);
// 创建结果
const createResult = ref<any>(null);
// 显示算力消耗
const showTokensCost = ref(false);

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.anchorLists.length > 0;
        case 2:
            return !!formData.person_name && !!formData.person_introduction;
        case 3:
            return formData.copywriterList.length > 0;
        case 4:
            // 单张图片计算为 2秒 + 视频时长，所有素材总时长不能超过5分钟，
            const totalDuration =
                formData.materialList.reduce((acc, item) => (item.type === "video" ? acc + item.duration : acc), 0) +
                formData.materialList.filter((item: any) => item.type === "image").length * montageConfig.imageDuration;
            if (totalDuration > montageConfig.materialTotalDuration * 60) {
                uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟`);
                return false;
            }
            return formData.materialList.length >= 3;
        case 5:
            return true; // 最后一步总是可进行的
        default:
            return false;
    }
};

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

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
                1: "请至少选择一个形象",
                2: "请填写人物名称和介绍",
                3: "请至少添加一条文案",
                4: "请上传至少3个素材",
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

const handleSelect = (val: any) => {
    if (formData.anchorLists.includes(val)) {
        formData.anchorLists = formData.anchorLists.filter((item: any) => item !== val);
    } else {
        formData.anchorLists.push(val);
    }
};

const handleCreateAnchor = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        params: {
            source: DigitalHumanModelVersionEnum.SHANJIAN,
        },
    });
};

const handleSelectCharacter = (item: any) => {
    formData.person_name = item.name;
    formData.person_introduction = item.introduced;
    isCharacter.value = true;
    showCharacter.value = false;
};

const handleSelectCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    const selectedCopywriter = formData.copywriterList[index];
    handleShowCopywriter(selectedCopywriter);
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_copywriter/montage_copywriter",
        params: {
            data: data ? JSON.stringify(data) : "",
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

const {
    showUploadProgress,
    uploadMaterialList,
    uploadAndProcessFiles,
    handleDeleteMaterial: handleDeleteMaterialFromHook,
} = useMontageMaterial({
    onSuccess: (materials: any[]) => {
        formData.materialList = formData.materialList.concat(materials);
    },
});

const chooseUploadType = () => {
    if (isFirstOpen.value) {
        isFirstOpen.value = false;
        showUploadTip.value = true;
        return;
    }
    showUploadTip.value = false;
    uni.showActionSheet({
        itemList: ["选择图片素材", "选择视频素材"],
        success: (res) => {
            if (res.tapIndex === 0) uploadAndProcessFiles("image");
            else if (res.tapIndex === 1) {
                uploadAndProcessFiles("video");
            }
        },
    });
};

const previewMaterial = (item: any) => {
    const { type, pic, url } = item;
    if (type === "image") {
        uni.previewImage({
            urls: [pic],
        });
    } else {
        videoPreview.poster = pic;
        videoPreview.url = url;
        showVideoPreview.value = true;
    }
};

const handleDeleteMaterial = (id: number) => {
    formData.materialList = formData.materialList.filter((item: any) => item.id !== id);
    handleDeleteMaterialFromHook(id);
};

// 生成视频
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
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        const res = await createShanjianTask({
            name: formData.name,
            anchor: formData.anchorLists.map((item: any) => ({
                anchor_id: item.anchor_id,
                pic: item.pic,
                anchor_url: item.anchor_url,
                name: item.name,
            })),
            character_design: [
                {
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                },
            ],
            voice: formData.anchorLists.map((item: any) => ({
                voice_id: item.voice_id,
                voice_url: item.voice_url,
                voice_name: item.voice_name,
            })),
            copywriting: formData.copywriterList,
            material: formData.materialList.map((item: any) => ({ fileUrl: item.url, type: item.type })),
        });
        if (!isCharacter.value) {
            addShanjianPerson({
                name: formData.person_name,
                introduced: formData.person_introduction,
            });
        }
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
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
        type: "redirect",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: formData.shanjian_type,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
    });
};

// 获取形象列表
const getAnchorList = async () => {
    anchorLoading.value = true;
    try {
        const { lists, count } = await getShanjianAnchorList(anchorQueryParams);
        anchorFinished.value = !(lists.length < (anchorQueryParams.page_size || count));
        anchorLists.value = anchorLists.value.concat(lists);
    } finally {
        anchorLoading.value = false;
    }
};

const handleLoadAnchorMore = () => {
    if (!anchorFinished.value || anchorLoading.value) return;
    anchorQueryParams.page_no++;
    getAnchorList();
};

onShow(() => {
    anchorLists.value = [];
    getAnchorList();
});

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            if (!data) return;
            anchorLists.value = anchorLists.value.concat(data);
        }
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
            } else {
                formData.copywriterList = formData.copywriterList.concat(data);
            }
        }
    });
});
</script>

<style scoped lang="scss">
.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
}
</style>
