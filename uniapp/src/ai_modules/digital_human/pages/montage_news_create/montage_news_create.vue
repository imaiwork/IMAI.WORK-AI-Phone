<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="新闻体视频"
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
            <view class="h-full flex flex-col" v-show="step == 1">
                <view class="px-4 flex items-center justify-between">
                    <view class="text-[30rpx] font-medium">素材图组</view>
                    <view
                        class="px-[28rpx] py-[12rpx] bg-black text-white rounded-[50rpx] font-medium"
                        @click="handleEditMaterial()"
                        >添加图组</view
                    >
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full" v-if="formData.materialList.length > 0">
                        <view class="px-4 flex flex-col gap-4 pb-[100rpx]">
                            <view
                                class="bg-white rounded-[20rpx] p-4"
                                v-for="(group, index) in formData.materialList"
                                :key="index"
                                @click="handleEditMaterial(index)">
                                <view class="flex items-center justify-between">
                                    <view class="font-medium break-all line-clamp-1">素材图组{{ index + 1 }}</view>
                                    <view class="flex items-center font-medium gap-x-1">
                                        <text class="font-medium">{{ group.length }}</text>
                                        <text class="text-[#B2B2B2] font-medium">张</text>
                                        <u-icon name="arrow-right" color="#B2B2B2" :size="20"></u-icon>
                                    </view>
                                </view>
                                <view class="grid grid-cols-4 gap-1 mt-[18rpx]">
                                    <view
                                        v-for="(value, valIndex) in group"
                                        :key="valIndex"
                                        class="h-[156rpx] rounded-[10rpx]">
                                        <image
                                            :src="value.pic"
                                            class="w-full h-full rounded-[10rpx]"
                                            mode="aspectFill"></image>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between mt-[26rpx]">
                                    <view class="flex items-center gap-x-1" @click.stop="handleDeleteMaterial(index)">
                                        <image
                                            src="/static/images/icons/delete.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="text-[#0000004d]">删除</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有图组哦" />
                        <view class="mt-[44rpx] flex justify-center">
                            <view
                                class="w-[220rpx] h-[88rpx] rounded-[20rpx] border border-solid flex items-center justify-center gap-x-2"
                                @click="handleEditMaterial()">
                                <u-icon name="plus" size="24"></u-icon>
                                <text class="font-medium">添加图组</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <view
                v-show="step === 2"
                class="bg-white rounded-[16rpx] px-4 py-[28rpx] shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] mx-4">
                <text class="font-medium">身份人设</text>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人设名称</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_name"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人设名称"
                                maxlength="20"
                                type="textarea"
                                height="30"
                                @change="isCharacter = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人设介绍</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_introduction"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人设介绍"
                                maxlength="50"
                                type="textarea"
                                height="30"
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
            <view class="h-full flex flex-col" v-show="step == 3">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleShowCopywriter()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-medium text-[32rpx]">手动输入</text>
                    </view>
                    <view
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]"
                        @click="showChooseAgent = true">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-medium text-[32rpx]">AI生成</text>
                    </view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-4 pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleSelectCopywriter(index)">
                                <view class="min-h-[80rpx] leading-5">
                                    {{ item }}
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
            <scroll-view class="h-full" scroll-y v-show="step == 4">
                <view class="px-4 pb-[150rpx]">
                    <view class="text-[30rpx] font-medium">作品名称</view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                        <u-input
                            class="w-full"
                            v-model="formData.name"
                            maxlength="50"
                            placeholder-style="font-size:26rpx;"
                            placeholder="请输入"
                            clearable />
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">参考素材</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(1)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.materialList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">身份人设</view>
                            <view class="flex items-center" @click="handleStep(2)">
                                <view
                                    class="line-clamp-1 min-w-[150rpx] text-end"
                                    :class="{ 'text-primary': formData.person_name }"
                                    >{{ formData.person_name || "无" }}</view
                                >
                                <view class="w-[1rpx] h-[24rpx] bg-[#C6CACC] mx-2"></view>
                                <view class="line-clamp-1" :class="{ 'text-primary': formData.person_introduction }">
                                    {{ formData.person_introduction || "无" }}
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                            </view>
                        </view>

                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">文案内容</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(3)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.copywriterList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center justify-between h-[106rpx]">
                            <view class="text-[30rpx] font-medium">背景音乐</view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music
                                )}&volume=${formData.extra.volume}`"
                                hover-class="none"
                                class="flex items-center gap-x-1">
                                <view>
                                    <template v-if="formData.music.length > 0">
                                        共<text class="mx-1 text-primary font-medium">{{ formData.music.length }}</text
                                        >个
                                    </template>
                                    <text class="text-[#000000]/70" v-else>AI音乐库</text>
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </navigator>
                        </view>
                    </view>
                    <view class="flex items-center justify-between bg-white mt-[22rpx] p-4 rounded-[20rpx]">
                        <view>
                            <view class="text-[30rpx] font-medium">生成视频数量</view>
                            <view class="text-[#000000]/50"> 每条新闻体生成视频的数量 </view>
                        </view>
                        <view class="flex items-center gap-x-2">
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('minus')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/minus_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                            <view
                                class="w-[90rpx] h-[52rpx] px-1 flex items-center justify-center bg-[#F6F6F6] rounded-[10rpx]">
                                <u-input
                                    v-model="formData.extra.video_count"
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
                    <view class="mt-[22rpx] bg-white rounded-[20rpx] px-4">
                        <view class="font-medium text-[30rpx] py-3">使用设置</view>
                        <view
                            class="flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="flex items-center justify-between h-[106rpx]">
                                <view class="font-medium">背景音乐使用</view>
                            </view>
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-[268rpx]">
                                <view class="grid grid-cols-2 gap-x-1 h-[68rpx] relative">
                                    <view
                                        v-for="(item, index) in ['按顺序', '随机']"
                                        :key="index"
                                        class="type-item"
                                        :class="{ active: index == formData.extra.music }"
                                        @click="formData.extra.music = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${formData.extra.music * 100}%)`,
                                        }"></view>
                                </view>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view
                                class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                <view class="font-medium">视频风格</view>
                            </view>
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-[268rpx]">
                                <view class="grid grid-cols-2 gap-x-1 h-[68rpx] relative">
                                    <view
                                        v-for="(item, index) in ['随机', '手动选择']"
                                        :key="index"
                                        class="type-item"
                                        :class="{ active: index == formData.extra.clip }"
                                        @click="formData.extra.clip = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${formData.extra.clip * 100}%)`,
                                        }"></view>
                                </view>
                            </view>
                        </view>
                        <navigator
                            v-if="formData.extra.clip === 1"
                            :url="`/ai_modules/digital_human/pages/montage_styles_choose/montage_styles_choose?type=${
                                MontageStylesType.MATERIAL
                            }&data=${JSON.stringify(formData.clip)}`"
                            hover-class="none"
                            class="flex items-center justify-between h-[106rpx] b">
                            <view class="text-[30rpx] font-medium">选择视频风格</view>
                            <view class="flex items-center">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{ formData.clip.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                            </view>
                        </navigator>
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
                        <text class="font-medium text-[32rpx]">{{ formData.anchorLists.length }}</text>
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
                        class="rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateVideo">
                        生成视频
                    </view>
                </template>
            </view>
        </view>
    </view>
    <choose-agent
        v-if="showChooseAgent"
        v-model="showChooseAgent"
        :system-agent-ids="[2, 6]"
        @select="handleSelectAgent" />
    <choose-character v-if="showCharacter" v-model="showCharacter" @select="handleSelectCharacter" />
    <video-preview
        v-if="showVideoPreview"
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"></video-preview>
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
    <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="4" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { addShanjianPerson, createShanjianTask } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { ListenerTypeEnum, MontageTypeEnum, MontageStylesType } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";

const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "上传视频" },
    { step: 2, title: "填写身份" },
    { step: 3, title: "填写文案" },
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
    shanjian_type: MontageTypeEnum;
    music: any[];
    extra: {
        volume: number;
        clip: number;
        music: number;
        video_count: number;
    };
    audio: any[];
    clip: any[];
}>({
    anchorLists: [],
    copywriterList: [],
    materialList: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "新闻体视频",
    person_name: "",
    person_introduction: "",
    shanjian_type: MontageTypeEnum.NEWS_BODY,
    music: [],
    extra: {
        volume: 0.5,
        clip: 0,
        music: 0,
        video_count: 1,
    },
    audio: [],
    clip: [],
});

const editMaterialIndex = ref(-1);

const playItem = reactive({
    pic: "",
    url: "",
});
const showVideoPreview = ref(false);
const showCharacter = ref(false);
const isCharacter = ref(false);
const showChooseAgent = ref(false);
const videoPreview = reactive({
    poster: "",
    url: "",
});

// 编辑文案索引
const editCopywriterIndex = ref(-1);

const showTokensCost = ref(false);
const showCreateSuccess = ref(false);
const createResult = ref<any>(null);
const rechargePopupRef = shallowRef();

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    const strategy: Record<number, () => boolean> = {
        1: () => formData.materialList.length > 0,
        2: () => {
            if (!formData.person_introduction && !formData.person_name) {
                return true;
            }
            return !!formData.person_introduction.trim() && !!formData.person_name.trim();
        },
        3: () => {
            return formData.copywriterList.length > 0 && isContentValid();
        },
        4: () => true,
    };
    return strategy[stepNumber]?.() ?? false;
};

// 计算当前步骤是否可以点击“下一步”
const canNext = computed(() => canStepProceed(step.value));

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    if (type === "prev") {
        step.value--;
        return;
    }

    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: { [key: number]: () => string } = {
                1: () => "请上传参考素材",
                2: () => "填写完整的人设信息",
                3: () => {
                    if (!isContentValid()) {
                        return "文案标题不能少于3个字";
                    }
                    return "请至少添加一条文案";
                },
            };
            uni.$u.toast((messages[step.value] as () => string)?.() || "请完成当前步骤");
        }
        return;
    }

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

const isContentValid = () => {
    return formData.copywriterList.every((title: any) => title.trim().length >= 3 && title.trim().length <= 1000);
};

const handleEditMaterial = (index?: number) => {
    editMaterialIndex.value = index ?? -1;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_material_group/montage_material_group",
        params: {
            materialList:
                editMaterialIndex.value !== -1 ? JSON.stringify(formData.materialList[editMaterialIndex.value]) : "",
        },
    });
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
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
            data: data || "",
            isNewsBody: 1,
        },
    });
};

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_ai_copywriter/montage_ai_copywriter",
        params: {
            agentData: JSON.stringify(data),
            montageType: formData.shanjian_type,
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.extra.video_count <= 1) {
            uni.$u.toast("数量最少为1");
            return;
        }
        formData.extra.video_count--;
    } else {
        if (formData.extra.video_count >= 99) {
            uni.$u.toast("数量最多为99");
            return;
        }
        formData.extra.video_count++;
    }
};

const handleCreateVideo = async () => {
    // 判断是否有算力
    if (userTokens.value <= 0) {
        rechargePopupRef.value?.open();
        return;
    }
    if (!formData.name) {
        uni.$u.toast("请输入视频名称");
        return;
    }
    if (formData.extra.video_count <= 0) {
        uni.$u.toast("请输入视频数量");
        return;
    }
    if (formData.extra.video_count > 99) {
        uni.$u.toast("视频数量最多为99");
        return;
    }

    if (formData.extra.clip === 1 && formData.clip.length === 0) {
        uni.$u.toast("请选择视频风格");
        return;
    }
    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        const res = await createShanjianTask({
            name: formData.name,
            copywriting: formData.copywriterList.map((item: any) => ({ title: [item] })),
            character_design: [
                {
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                },
            ],
            material: formData.materialList.map((group: any) =>
                group.map((item: any) => ({
                    fileUrl: item.url,
                    type: item.type,
                    cover: item.pic,
                }))
            ),
            shanjian_type: formData.shanjian_type,
            music: formData.music.map((item: any) => item.content),
            extra: formData.extra,
            audio: formData.audio,
            clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
        });

        if (formData.person_name && formData.person_introduction) {
            addShanjianPerson({
                name: formData.person_name,
                introduced: formData.person_introduction,
            });
        }
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
        WechatOA.notify();
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
        params: {
            source: "1",
            type: 5,
        },
    });
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
                editCopywriterIndex.value = -1;
            } else {
                formData.copywriterList.push(...data);
            }
        }
        if (type === ListenerTypeEnum.MONTAGE_MATERIAL_GROUP) {
            if (editMaterialIndex.value !== -1) {
                if (data.length == 0) {
                    formData.materialList.splice(editMaterialIndex.value, 1);
                    editMaterialIndex.value = -1;
                    return;
                }
                formData.materialList.splice(editMaterialIndex.value, 1, data);
                editMaterialIndex.value = -1;
            } else {
                formData.materialList.push(data);
            }
        }
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music = data.music;
            formData.extra.volume = data.volume;
        }
        if (type === ListenerTypeEnum.CHOOSE_VIDEO_STYLES) {
            if (data.length === 0) return;
            formData.clip = data;
        }
    });
});
</script>
<style scoped lang="scss">
.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
}
.type-item {
    @apply flex flex-col items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500 text-xs;
    &.active {
        @apply text-primary font-medium relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[6rpx] left-0 transition-all duration-500;
}
</style>
