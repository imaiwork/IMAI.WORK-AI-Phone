<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="真人口播视频混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
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
        <view class="grow min-h-0 mt-[24rpx]">
            <view class="h-full flex flex-col" v-if="step == 1">
                <view class="mx-4 text-[30rpx] font-bold"> 上传口播视频({{ formData.anchorLists.length }}) </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[26rpx] p-4">
                            <view
                                v-for="(item, index) in formData.anchorLists"
                                :key="index"
                                class="w-full h-[288rpx] relative rounded-[12rpx]">
                                <image :src="item.pic" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                                <view class="absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                    <view class="rounded-full w-[48rpx] h-[48rpx]" @click="handleVideoPlay(item)">
                                        <image src="/static/images/icons/play.svg" class="w-full h-full"></image>
                                    </view>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteAnchor(item.id)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                                <view class="absolute bottom-2 w-full z-[33] flex justify-center">
                                    <view class="dh-version-name" @click="handleReplaceMaterial(index, 0)"> 替换 </view>
                                </view>
                            </view>
                            <view
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[288rpx]"
                                @click="chooseUploadType(0)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加视频</text>
                            </view>
                        </view>
                    </scroll-view>
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
            <view class="h-full flex flex-col" v-if="step == 3">
                <view class="px-4">
                    <view class="text-[30rpx] font-bold">参考素材</view>
                    <view class="mt-1 text-xs text-[#0000004d]">
                        总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                            montageConfig.imageDuration
                        }}秒/张，视频按实际时长/个)</view
                    >
                    <view class="mt-[24rpx] bg-white rounded-[20rpx] px-[8rpx]">
                        <view class="grid grid-cols-2 gap-x-1 h-[100rpx] relative">
                            <view
                                v-for="(item, index) in ['使用素材', '不使用素材']"
                                :key="index"
                                class="material-type-item"
                                :class="{ active: index == isMaterial }"
                                @click="isMaterial = index">
                                {{ item }}
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
                                <div class="absolute bottom-4 w-full z-[89] flex justify-center">
                                    <view class="dh-version-name" @click.stop="handleReplaceMaterial(index, 1)">
                                        替换
                                    </view>
                                </div>
                            </view>
                            <view
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                                @click="chooseUploadType(1)">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <scroll-view class="h-full" scroll-y v-if="step == 4">
                <view class="px-4">
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
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view class="flex items-center justify-between py-2.5">
                            <view class="text-[30rpx] font-bold">口播视频</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(1)">
                                <view>
                                    共<text class="mx-1 text-primary font-bold">{{ formData.anchorLists.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <scroll-view scroll-x class="mt-1">
                            <view class="flex gap-x-2 pb-4">
                                <view
                                    class="flex-shrink-0 w-[164rpx] h-[220rpx] rounded-xl whitespace-nowrap"
                                    v-for="(item, index) in formData.anchorLists"
                                    :key="index">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-bold">身份人设</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(2)">
                                <view class="text-primary line-clamp-1 min-w-[150rpx] text-end">{{
                                    formData.person_name
                                }}</view>
                                <view class="w-[1rpx] h-[24rpx] bg-[#C6CACC] mx-1"></view>
                                <view class="line-clamp-1 text-primary">
                                    {{ formData.person_introduction }}
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-bold">参考素材</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(3)">
                                <view>
                                    共<text class="mx-1 text-primary font-bold">{{ formData.materialList.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center justify-between h-[106rpx]">
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
                    </view>
                </view>
            </scroll-view>
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
                        生成视频（{{ formData.video_count }}个）
                    </view>
                </template>
            </view>
        </view>
    </view>
    <choose-history v-model="showHistory" :type="3" @select="handleSelectHistory" />
    <choose-character v-model="showCharacter" @select="handleSelectCharacter" />
    <upload-rule-pop
        v-model="showUploadTip"
        @handle-upload="uploadAndProcessFiles('video')"
        @close="isFirstOpen = false" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview>
    <create-success-pop
        v-model="showCreateSuccess"
        title="视频生成中"
        :desc="`本次提交视频 ${formData.video_count} 条，预计生成时间比较长，您可以先去设置发布任务，也可以等待视频生成成功后再发布`"
        @to="toPublish"
        @seek="toRecord" />
    <tokens-cost v-model="showTokensCost" :type="2" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import useMontageMaterial, { montageConfig } from "@/hooks/useMontageMaterial";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import ChooseHistory from "@/ai_modules/digital_human/components/choose-history/choose-history.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "上传视频" },
    { step: 2, title: "填写身份" },
    { step: 3, title: "参考素材" },
    { step: 4, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    anchorLists: any[];
    copywriterList: any[];
    materialList: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    video_count: number;
    shanjian_type: MontageTypeEnum;
}>({
    anchorLists: [],
    copywriterList: [],
    materialList: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "真人口播视频智剪",
    // 人设名称
    person_name: "",
    // 人设介绍
    person_introduction: "",
    video_count: 1,
    shanjian_type: MontageTypeEnum.REAL_PERSON_AI,
});

const isFirstOpen = ref(true);
const showUploadTip = ref(false);
// 替换素材索引
const replaceMaterialIndex = ref(-1);
const uploadType = ref<"image" | "video">("video");
const uploadSource = ref<0 | 1>(0);
const showHistory = ref(false);

const playItem = reactive({
    pic: "",
    url: "",
});
const showVideoPreview = ref(false);

const showCharacter = ref(false);
const isCharacter = ref(false);

const isMaterial = ref(0);
const videoPreview = reactive({
    poster: "",
    url: "",
});

const showCreateSuccess = ref(false);
const createResult = ref<any>(null);
const showTokensCost = ref(false);
const rechargePopupRef = shallowRef();

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.anchorLists.length > 0;
        case 2:
            return formData.person_name && formData.person_introduction;
        case 3:
            if (isMaterial.value === 0) {
                const totalDuration =
                    formData.materialList.reduce(
                        (acc, item) => (item.type === "video" ? acc + item.duration : acc),
                        0
                    ) +
                    formData.materialList.filter((item: any) => item.type === "image").length *
                        montageConfig.imageDuration;
                if (totalDuration > montageConfig.materialTotalDuration * 60) {
                    uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟`);
                    return false;
                }
                return true;
            }
            return true;
        case 4:
            return true;
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
                1: "请上传视频",
                2: "请填写人物名称和介绍",
                3: "请上传至少3个素材",
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

const {
    showUploadProgress,
    uploadMaterialList,
    uploadAndProcessFiles,
    handleDeleteMaterial: handleDeleteMaterialFromHook,
} = useMontageMaterial({
    onSuccess: (materials: any[]) => {
        if (uploadSource.value == 0) {
            if (replaceMaterialIndex.value !== -1) {
                formData.anchorLists[replaceMaterialIndex.value] = materials[0];
            } else {
                formData.anchorLists = formData.anchorLists.concat(materials);
            }
            replaceMaterialIndex.value = -1;
        } else {
            if (replaceMaterialIndex.value !== -1) {
                formData.materialList[replaceMaterialIndex.value] = materials[0];
            } else {
                formData.materialList = formData.materialList.concat(materials);
            }
            replaceMaterialIndex.value = -1;
        }
    },
});

const chooseUploadType = (type: 0 | 1) => {
    uploadSource.value = type;
    if (type == 0) {
        uni.showActionSheet({
            itemList: ['从"创作历史"中选择', '从"手机相册"中选择'],
            success: async (res) => {
                if (res.tapIndex == 0) {
                    showHistory.value = true;
                }
                if (res.tapIndex == 1) {
                    if (isFirstOpen.value) {
                        isFirstOpen.value = false;
                        showUploadTip.value = true;
                        return;
                    }
                    uploadAndProcessFiles("video");
                }
            },
        });
    }
    if (type == 1) {
        uni.showActionSheet({
            itemList: ["选择图片素材", "选择视频素材"],
            success: async (res) => {
                uploadType.value = res.tapIndex == 0 ? "image" : "video";
                if (isFirstOpen.value) {
                    isFirstOpen.value = false;
                    showUploadTip.value = true;
                    return;
                }
                uploadAndProcessFiles(uploadType.value);
            },
        });
    }
};

const handleSelectHistory = (lists: any[]) => {
    const data = lists.map((item: any) => ({
        pic: item.pic,
        url: item.video_result_url,
        name: item.name,
        duration: item.duration,
    }));
    if (replaceMaterialIndex.value !== -1) {
        formData.anchorLists[replaceMaterialIndex.value] = data[0];
    } else {
        formData.anchorLists = formData.anchorLists.concat(data);
    }
    replaceMaterialIndex.value = -1;
    showHistory.value = false;
};

const handleVideoPlay = (item: any) => {
    playItem.pic = item.pic;
    playItem.url = item.url;
    showVideoPreview.value = true;
};

const handleReplaceMaterial = (index: number, type: 0 | 1) => {
    replaceMaterialIndex.value = index;
    chooseUploadType(type);
};

const handleDeleteAnchor = (id: number) => {
    formData.anchorLists = formData.anchorLists.filter((item) => item.id !== id);
    handleDeleteMaterialFromHook(id);
};
const handleDeleteMaterial = (id: number) => {
    formData.materialList = formData.materialList.filter((item) => item.id !== id);
    handleDeleteMaterialFromHook(id);
};

const handleSelectCharacter = (item: any) => {
    formData.person_name = item.name;
    formData.person_introduction = item.introduced;
    isCharacter.value = true;
    showCharacter.value = false;
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
        title: "创建中...",
        mask: true,
    });
    try {
        const res = await createShanjianTask({
            name: formData.name,
            anchor: formData.anchorLists.map((item: any) => ({
                pic: item.pic,
                anchor_url: item.url,
                name: item.name,
                duration: item.duration,
            })),
            character_design: [
                {
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                },
            ],
            copywriting: formData.copywriterList,
            material:
                isMaterial.value === 0
                    ? formData.materialList.map((item: any) => ({
                          fileUrl: item.url,
                          type: item.type,
                          cover: item.pic,
                      }))
                    : [],
            shanjian_type: formData.shanjian_type,
            video_count: formData.video_count,
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
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};
const toPublish = () => {
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
</script>

<style lang="scss">
.material-type-item {
    @apply flex items-center justify-center rounded-[16rpx] text-[hsla(0,0%,0%,1)] relative z-10 transition-colors duration-500;
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
</style>
