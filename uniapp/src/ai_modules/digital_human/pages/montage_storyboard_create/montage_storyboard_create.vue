<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="分镜混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full">
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
        <view class="grow min-h-0">
            <view class="h-full flex flex-col" v-show="step === 1">
                <view class="flex items-center justify-between px-4">
                    <text class="font-medium">镜头组素材</text>
                    <view
                        class="px-[28rpx] py-[12rpx] bg-black text-white rounded-[50rpx] font-medium"
                        @click="handleEditMaterial()"
                        >添加镜头</view
                    >
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full" v-if="formData.storyboardList.length > 0">
                        <view class="px-4 flex flex-col gap-4 pb-[100rpx]">
                            <view
                                class="bg-white rounded-[20rpx] p-4"
                                v-for="(storyboard, index) in formData.storyboardList"
                                :key="index"
                                @click="handleEditMaterial(index)">
                                <view class="flex items-center justify-between">
                                    <view class="font-medium break-all line-clamp-1">{{ storyboard.groupName }}</view>
                                    <view class="flex items-center font-medium gap-x-1">
                                        <text class="font-medium">{{ storyboard.materialList.length }}</text>
                                        <text class="text-[#B2B2B2] font-medium">个</text>
                                        <u-icon name="arrow-right" color="#B2B2B2" :size="20"></u-icon>
                                    </view>
                                </view>
                                <view class="grid grid-cols-4 gap-1 mt-[18rpx]">
                                    <view
                                        v-for="(value, valIndex) in storyboard.materialList"
                                        :key="valIndex"
                                        class="h-[156rpx] rounded-[10rpx]">
                                        <image
                                            :src="value.pic"
                                            class="w-full h-full rounded-[10rpx]"
                                            mode="aspectFill"></image>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between mt-[26rpx]">
                                    <view class="flex items-center gap-x-1" @click.stop="handleDeleteStoryboard(index)">
                                        <image
                                            src="/static/images/icons/delete.svg"
                                            class="w-[28rpx] h-[28rpx]"></image>
                                        <text class="text-[#0000004d]">删除</text>
                                    </view>
                                    <view class="flex items-center gap-x-1" @click.stop>
                                        素材原声<u-switch v-model="storyboard.is_use" :size="32" />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有镜头组素材哦" />
                        <view class="mt-[44rpx] flex justify-center">
                            <view
                                class="w-[220rpx] h-[88rpx] rounded-[20rpx] border border-solid flex items-center justify-center gap-x-2"
                                @click="handleEditMaterial()">
                                <u-icon name="plus" size="24"></u-icon>
                                <text class="font-medium">添加镜头</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="h-full flex flex-col" v-show="step === 2">
                <view class="flex justify-center mb-3">
                    <view class="bg-white rounded-[16rpx] px-[6rpx]">
                        <view class="w-[360rpx] grid grid-cols-2 relative h-[80rpx]">
                            <view
                                v-for="(item, index) in ['按顺序文案', '镜头匹配文案']"
                                :key="index"
                                class="type-item"
                                :class="{ active: copywriterTypeIndex === index }"
                                @click="copywriterTypeIndex = index">
                                {{ item }}
                            </view>
                            <view
                                class="tab-slider !bg-[#0065fb]/5"
                                :style="{ transform: `translateX(${copywriterTypeIndex * 100}%)` }"></view>
                        </view>
                    </view>
                </view>

                <view class="px-4" v-if="copywriterTypeIndex === 0">
                    <view class="flex items-center justify-between gap-x-2">
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
                </view>

                <view class="px-4" v-if="copywriterTypeIndex === 1">
                    <view class="flex items-center justify-between">
                        <view class="flex-1">
                            <view class="text-[30rpx] font-medium">字幕组({{ formData.subtitleList.length }})</view>
                            <view class="text-xs font-medium text-[#000000]/50"
                                >每个镜头组有多条字幕，则随机匹配1条</view
                            >
                        </view>
                    </view>
                </view>

                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-4 pb-4">
                            <template v-if="copywriterTypeIndex === 0">
                                <view
                                    v-for="(item, index) in formData.copywriterList"
                                    :key="index"
                                    class="copywriter-item"
                                    :class="{ 'copywriter-item--error': !isSingleCopywriterValid(item) }"
                                    @click="handleSelectCopywriter(index)">
                                    {{ item }}
                                    <view
                                        class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleDeleteCopywriter(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                    <view class="flex items-center justify-between mt-2">
                                        <view class="flex items-center gap-x-1">
                                            <template v-if="item.length > copywriterLimit">
                                                <u-icon name="info-circle-fill" color="#f56c6c"></u-icon>
                                                <text class="text-xs text-error"
                                                    >文案超出{{ copywriterLimit }}字限制，请修改</text
                                                >
                                            </template>
                                            <template v-else-if="item.trim().length < 3">
                                                <u-icon name="info-circle-fill" color="#f56c6c"></u-icon>
                                                <text class="text-xs text-error">文案不能少于3个字</text>
                                            </template>
                                        </view>
                                        <text
                                            class="text-xs text-right"
                                            :class="
                                                item.length > copywriterLimit
                                                    ? 'text-error font-medium'
                                                    : 'text-[#000000]/50'
                                            ">
                                            {{ item.length }} / {{ copywriterLimit }}
                                        </text>
                                    </view>
                                </view>
                            </template>

                            <template v-if="copywriterTypeIndex === 1">
                                <view
                                    v-for="(item, index) in formData.subtitleList"
                                    :key="index"
                                    class="bg-white rounded-[16rpx] p-4">
                                    <view class="flex items-center justify-between">
                                        <view class="font-medium">{{ item.title }}</view>
                                        <view
                                            class="flex items-center gap-x-1 px-[20rpx] py-[10rpx] bg-black rounded-[50rpx]"
                                            @click.stop="handleAddSubtitleContent(index)">
                                            <u-icon name="plus" size="14" color="#ffffff"></u-icon>
                                            <text class="text-white text-xs font-medium">添加文案</text>
                                        </view>
                                    </view>
                                    <view class="mt-3 space-y-2" v-if="item.contentList.length > 0">
                                        <view
                                            v-for="(content, contentIndex) in item.contentList"
                                            :key="contentIndex"
                                            class="rounded-[16rpx] px-4 py-3 relative pr-8 font-medium"
                                            :class="
                                                !isSingleCopywriterValid(content)
                                                    ? 'subtitle-content--error'
                                                    : 'bg-[#F1F2F5]'
                                            "
                                            @click="handleSelectCopywriter(index, contentIndex)">
                                            {{ content }}
                                            <view
                                                class="absolute right-2 top-3 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                                @click.stop="handleDeleteCopywriter(index, contentIndex)">
                                                <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                            </view>
                                            <view class="flex items-center justify-between mt-2">
                                                <view class="flex items-center gap-x-1">
                                                    <template v-if="content.length > 500">
                                                        <u-icon
                                                            name="info-circle-fill"
                                                            color="#ef4444"
                                                            size="14"></u-icon>
                                                        <text class="text-xs text-error"
                                                            >文案超出500字限制，请修改</text
                                                        >
                                                    </template>
                                                    <template v-else-if="content.trim().length < 3">
                                                        <u-icon
                                                            name="info-circle-fill"
                                                            color="#ef4444"
                                                            size="14"></u-icon>
                                                        <text class="text-xs text-red-500">文案不能少于3个字</text>
                                                    </template>
                                                </view>
                                                <text
                                                    class="text-xs text-right"
                                                    :class="
                                                        content.length > 500
                                                            ? 'text-red-500 font-medium'
                                                            : 'text-[#000000]/50'
                                                    ">
                                                    {{ content.length }} / 500
                                                </text>
                                            </view>
                                        </view>
                                    </view>
                                    <view v-else class="text-xs text-[#000000]/50 text-center py-4">
                                        点击右上角
                                        <text class="font-medium text-primary">添加文案</text>
                                        按钮添加字幕内容
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <scroll-view scroll-y class="h-full" v-show="step === 3">
                <view class="px-4 pb-[150rpx]">
                    <view>
                        <view class="text-[30rpx] font-medium">视频名称</view>
                        <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                            <u-input
                                v-model="formData.name"
                                maxlength="50"
                                placeholder-style="font-size:26rpx;"
                                placeholder="请输入" />
                        </view>
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">分镜素材</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(1)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.storyboardList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">字幕文案</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(2)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.copywriterList.length || formData.subtitleList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">视频顶部标题</view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/montage_storyboard_title/montage_storyboard_title?data=${JSON.stringify(
                                    formData.topTitleList
                                )}`"
                                hover-class="none"
                                class="flex items-center gap-x-1">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.topTitleList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </navigator>
                        </view>
                        <view class="flex items-center justify-between h-[106rpx]">
                            <view class="text-[30rpx] font-medium">背景音乐</view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music
                                )}&volume=${formData.extra.volume}&is_ai=0`"
                                hover-class="none"
                                class="flex items-center gap-x-1">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{ formData.music.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </navigator>
                        </view>
                    </view>
                    <view class="flex items-center justify-between bg-white mt-[22rpx] p-4 rounded-[20rpx]">
                        <view class="text-[30rpx] font-medium">生成视频数量</view>
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
                        <view class="flex items-center justify-between">
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
                    </view>
                </view>
            </scroll-view>
        </view>

        <!-- 底部操作栏 -->
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.storyboardList.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-medium text-[32rpx]">{{ formData.storyboardList.length }}</text>
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
                        立即生成({{ formData.extra.video_count }}个)
                    </view>
                </template>
            </view>
        </view>
    </view>

    <choose-agent v-if="showChooseAgent" v-model="showChooseAgent" @select="handleSelectAgent" />
    <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.STORYBOARD_MIX" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { createMontageStoryboard } from "@/api/digital_human";
import { ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";

const { on } = useEventBusManager();

const steps = ref([
    { step: 1, title: "分镜素材" },
    { step: 2, title: "字幕文案" },
    { step: 3, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    name: string;
    storyboardList: {
        is_use: boolean;
        groupName: string;
        materialList: any[];
    }[];
    copywriterList: any[];
    subtitleList: {
        title: string;
        contentList: any[];
    }[];
    topTitleList: any[];
    music: any[];
    clip: any[];
    extra: {
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
}>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "分镜混剪",
    storyboardList: [],
    copywriterList: [],
    subtitleList: [],
    topTitleList: [],
    music: [],
    clip: [],
    extra: {
        volume: 0.2,
        soundSwitch: false,
        human: 0,
        music: 0,
        clip: 0,
        video_count: 1,
    },
});

const editMaterialIndex = ref(-1);
const copywriterLimit = 600;
const copywriterTypeIndex = ref(0);
const editCopywriterIndex = ref(-1);
const editSubtitleContentIndex = ref(-1);
const addSubtitleContentIndex = ref(-1);
const showChooseAgent = ref(false);
const showTokensCost = ref(false);
const showCreateSuccess = ref(false);
const createResult = ref<any>(null);

const isSingleCopywriterValid = (text: string): boolean => {
    return text.trim().length >= 3 && text.length <= copywriterLimit;
};

const syncSubtitleList = () => {
    const newLen = formData.storyboardList.length;
    const oldLen = formData.subtitleList.length;
    if (newLen > oldLen) {
        for (let i = oldLen; i < newLen; i++) {
            formData.subtitleList.push({
                title: `镜头组${i + 1}的字幕`,
                contentList: [],
            });
        }
    } else if (newLen < oldLen) {
        formData.subtitleList.splice(newLen);
    }
};

const canStepProceed = (stepNumber: number) => {
    const strategy: Record<number, () => boolean> = {
        1: () =>
            formData.storyboardList.length > 0 &&
            formData.storyboardList.every((item) => item.materialList.length > 0 && item.materialList.length <= 200),
        2: () => {
            if (copywriterTypeIndex.value === 0) {
                return formData.copywriterList.length > 0 && isCopywriterValid();
            } else {
                return formData.subtitleList.length > 0 && isCopywriterValid();
            }
        },
        5: () => true,
    };
    return strategy[stepNumber]?.() ?? false;
};

const canNext = computed(() => canStepProceed(step.value));

const isCopywriterValid = () => {
    if (copywriterTypeIndex.value === 0) {
        return (
            formData.copywriterList.every((item: any) => isSingleCopywriterValid(item)) &&
            formData.copywriterList.length <= 50
        );
    } else {
        return formData.subtitleList.every(
            (item) =>
                item.contentList.length > 0 &&
                item.contentList.every((content) => isSingleCopywriterValid(content)) &&
                item.contentList.length <= 50
        );
    }
};

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    if (type === "prev") {
        step.value--;
        return;
    }

    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: Record<number, () => string> = {
                1: () => "请至少添加一个镜头组素材",
                2: () => {
                    if (copywriterTypeIndex.value === 0) {
                        if (!isCopywriterValid()) {
                            return `口播文案包含内容不能少于3个字，不能超过${copywriterLimit}个字`;
                        }
                        return "请至少添加一条文案";
                    } else {
                        if (!isCopywriterValid()) {
                            return "每个镜头组至少需要添加一条字幕，且内容不能少于3个字";
                        }
                        return "请至少添加一条字幕";
                    }
                },
            };
            uni.$u.toast(messages[step.value]?.() || "请完成当前步骤");
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

const handleEditMaterial = (index?: number) => {
    editMaterialIndex.value = index ?? -1;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_material_group/montage_material_group",
        params: {
            type: "storyboard",
            materialList:
                editMaterialIndex.value !== -1
                    ? JSON.stringify(formData.storyboardList[editMaterialIndex.value].materialList)
                    : "",
        },
    });
};

const handleDeleteStoryboard = (index: number) => {
    formData.storyboardList.splice(index, 1);
    syncSubtitleList();
};

const handleSelectCopywriter = (index: number, contentIndex?: number) => {
    editCopywriterIndex.value = index;

    if (copywriterTypeIndex.value === 0) {
        const selectedCopywriter = formData.copywriterList[index];
        handleShowCopywriter(selectedCopywriter);
    }
    if (copywriterTypeIndex.value === 1) {
        editSubtitleContentIndex.value = contentIndex ?? -1;
        const selectedCopywriter = formData.subtitleList[index].contentList[contentIndex ?? -1];
        handleShowCopywriter(selectedCopywriter);
    }
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter",
        params: {
            content: data,
        },
    });
};

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_ai_copywriter/montage_ai_copywriter",
        params: {
            agentData: JSON.stringify(data),
        },
    });
};

const handleDeleteCopywriter = (index: number, contentIndex?: number) => {
    if (copywriterTypeIndex.value === 0) {
        formData.copywriterList.splice(index, 1);
    } else {
        formData.subtitleList[index].contentList.splice(contentIndex ?? -1, 1);
    }
};

const handleAddSubtitleContent = (index: number) => {
    addSubtitleContentIndex.value = index;
    editCopywriterIndex.value = -1;
    editSubtitleContentIndex.value = -1;
    handleShowCopywriter();
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.extra.video_count <= 1) {
            uni.$u.toast("视频数量最少为1");
            return;
        }
        formData.extra.video_count--;
    } else {
        if (formData.extra.video_count >= 99) {
            uni.$u.toast("视频数量最多为99");
            return;
        }
        formData.extra.video_count++;
    }
};

const handleCreateVideo = async () => {
    uni.showLoading({ title: "提交中...", mask: true });
    try {
        const mediaGroupArray = formData.storyboardList.map((item) => ({
            GroupName: item.groupName,
            MediaArray: item.materialList.map((item) => item.url),
            Volume: item.is_use ? 1 : 0,
        }));
        const totalDuration = formData.storyboardList.reduce((groupAcc, group) => {
            const groupDuration = group.materialList.reduce((materialAcc, material) => {
                return materialAcc + (material.type === "video" ? material.duration : 5);
            }, 0);
            return groupAcc + groupDuration;
        }, 0);
        const params: any = {
            name: formData.name,
            number: formData.extra.video_count,
            duration: totalDuration,
            TitleArray: formData.topTitleList,
            SpeechTextArray: formData.copywriterList,
            MediaGroupArray: mediaGroupArray,
            BackgroundMusicArray: formData.music.map((item) => item.content),
            BackgroundMusicVolume: formData.extra.volume,
        };
        if (copywriterTypeIndex.value === 1) {
            delete params.SpeechTextArray;
            for (let i = 0; i < mediaGroupArray.length; i++) {
                params.MediaGroupArray[i].SpeechTextArray =
                    i < formData.subtitleList.length ? formData.subtitleList[i].contentList.map((item) => item) : [];
            }
        }
        const res = await createMontageStoryboard(params);
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "提交失败", icon: "none", duration: 3000 });
    }
};

const toPublish = () => {
    showCreateSuccess.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        type: "redirect",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: MontageTypeEnum.STORYBOARD_MIX,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: { source: "1", type: 7 },
    });
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        // 素材组回调
        if (type === ListenerTypeEnum.MONTAGE_MATERIAL_GROUP) {
            if (editMaterialIndex.value !== -1) {
                if (data.length === 0) {
                    formData.storyboardList.splice(editMaterialIndex.value, 1);
                } else {
                    formData.storyboardList[editMaterialIndex.value].materialList = data;
                }
                editMaterialIndex.value = -1;
            } else {
                if (data.length > 0) {
                    formData.storyboardList.push({
                        is_use: true,
                        groupName: `镜头组${formData.storyboardList.length + 1}`,
                        materialList: data,
                    });
                }
            }
            // 每次素材变动后同步字幕组数量
            syncSubtitleList();
        }

        // AI 文案回调（仅按顺序文案模式）
        if (type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            formData.copywriterList.push(...data.map((item: any) => item.content));
        }

        // 手动文案输入回调
        if (type === ListenerTypeEnum.SZR_COPYWRITER) {
            if (copywriterTypeIndex.value === 0) {
                // 按顺序文案：编辑 or 新增
                if (editCopywriterIndex.value !== -1) {
                    formData.copywriterList[editCopywriterIndex.value] = data;
                    editCopywriterIndex.value = -1;
                } else {
                    formData.copywriterList.push(data);
                }
            } else {
                // 镜头匹配文案：为指定组新增 or 编辑已有条目
                if (addSubtitleContentIndex.value !== -1) {
                    formData.subtitleList[addSubtitleContentIndex.value].contentList.push(data);
                    addSubtitleContentIndex.value = -1;
                } else if (editSubtitleContentIndex.value !== -1) {
                    formData.subtitleList[editCopywriterIndex.value].contentList[editSubtitleContentIndex.value] = data;
                    editSubtitleContentIndex.value = -1;
                    editCopywriterIndex.value = -1;
                }
            }
        }

        // 顶部标题回调
        if (type === ListenerTypeEnum.MONTAGE_TOP_TITLE) {
            formData.topTitleList = data;
        }

        // 背景音乐回调
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music = data.music;
            formData.extra.volume = data.volume;
        }
    });
});
</script>

<style scoped lang="scss">
.type-item {
    @apply flex flex-col items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500 text-xs;
    &.active {
        @apply text-primary font-medium relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[6rpx] left-0 transition-all duration-500;
}

.copywriter-item {
    @apply whitespace-pre-line relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
    &--error {
        @apply border border-solid border-error bg-[#f56c6c]/50;
    }
}

.subtitle-content--error {
    @apply bg-[#f56c6c]/50 border border-solid border-error;
}
</style>
