<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="素材混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-3 w-full px-4">
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
                <view class="px-4 flex items-center justify-between">
                    <view class="text-[30rpx] font-bold">素材图组</view>
                    <view
                        class="px-[28rpx] py-[12rpx] bg-black text-white rounded-[50rpx] font-bold"
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
                                    <view class="font-bold break-all line-clamp-1">素材图组{{ index + 1 }}</view>
                                    <view class="flex items-center font-bold gap-x-1">
                                        <text class="font-bold">{{ group.length }}</text>
                                        <text class="text-[#B2B2B2] font-bold">张</text>
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
                                <text class="font-bold">添加图组</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <view v-if="step === 2" class="h-full flex flex-col">
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
            <scroll-view class="h-full" scroll-y v-if="step == 3">
                <view class="px-4">
                    <view class="text-[30rpx] font-bold">视频名称</view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                        <u-input
                            v-model="formData.name"
                            maxlength="50"
                            placeholder-style="font-size:26rpx;"
                            placeholder="请输入"
                            clearable />
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-bold">参考素材</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(1)">
                                <view>
                                    共<text class="mx-1 text-primary font-bold">{{ formData.materialList.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-bold">文案内容</view>
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
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-bold">选择音色</view>
                            <view class="flex items-center gap-x-1" @click="showChooseTone = true">
                                <view :class="[voiceValue.name ? 'text-primary' : 'text-[#B2B2B2]']">
                                    {{ voiceValue.name || "请选择音色" }}
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center justify-between h-[106rpx]">
                            <view class="text-[30rpx] font-bold">视频原生</view>
                            <u-switch v-model="formData.extra" inactive-color="#E5E5E5" :size="40" />
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
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
    <confirm-dialog
        v-model="confirmDialogVisible"
        confirm-text="删除"
        center
        content="是否确定删除该图组？"
        @confirm="handleDeleteMaterialConfirm" />
    <choose-tone
        v-model="showChooseTone"
        :model-version="DigitalHumanModelVersionEnum.SHANJIAN"
        :active-tone="formData.voice"
        :show-free-tone="false"
        @confirm="handleSelectTone" />
    <video-preview v-model="showVideoPreview" :video-url="playItem.url" :poster="playItem.pic"></video-preview>
    <create-success-pop
        v-model="showCreateSuccess"
        title="视频生成中"
        desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
    <tokens-cost v-model="showTokensCost" :type="3" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { createShanjianTask } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum, MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import ChooseHistory from "@/ai_modules/digital_human/components/choose-history/choose-history.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "上传素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    anchorLists: any[];
    copywriterList: any[];
    materialList: any[];
    name: string;
    video_count: number;
    shanjian_type: MontageTypeEnum;
    extra: boolean;
    voice: string;
}>({
    anchorLists: [],
    copywriterList: [],
    materialList: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "素材混剪",
    video_count: 1,
    shanjian_type: MontageTypeEnum.MATERIAL_MIX,
    extra: false,
    voice: "",
});
const confirmDialogVisible = ref(false);
const editMaterialIndex = ref(-1);

const playItem = reactive({
    pic: "",
    url: "",
});
const showVideoPreview = ref(false);
// 编辑文案索引
const editCopywriterIndex = ref(-1);
const videoPreview = reactive({
    poster: "",
    url: "",
});
const showChooseTone = ref(false);
const voiceValue = ref<any>({});
const showCreateSuccess = ref(false);
const createResult = ref<any>(null);
// 显示算力消耗
const showTokensCost = ref(false);
const rechargePopupRef = shallowRef();

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.materialList.length > 0;
        case 2:
            return formData.copywriterList.length > 0;
        case 3:
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
                1: "请上传素材图组",
                2: "请至少添加一条文案",
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
    confirmDialogVisible.value = true;
    editMaterialIndex.value = index;
};
const handleDeleteMaterialConfirm = () => {
    formData.materialList.splice(editMaterialIndex.value, 1);
    confirmDialogVisible.value = false;
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

const handleSelectTone = (tone: any) => {
    formData.voice = tone.voice_id;
    voiceValue.value = tone;
    showChooseTone.value = false;
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
    if (!formData.voice) {
        uni.$u.toast("请选择音色");
        showChooseTone.value = true;
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
        const res = await createShanjianTask({
            name: formData.name,
            copywriting: formData.copywriterList,
            extra: formData.extra,
            material: formData.materialList.map((group: any) =>
                group.map((item: any) => ({
                    fileUrl: item.url,
                    type: item.type,
                    cover: item.pic,
                }))
            ),

            shanjian_type: formData.shanjian_type,
            video_count: formData.video_count,
            voice: formData.voice,
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

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
            } else {
                formData.copywriterList = formData.copywriterList.concat(data);
            }
        }
        if (type === ListenerTypeEnum.MONTAGE_MATERIAL_GROUP) {
            if (editMaterialIndex.value !== -1) {
                if (data.length == 0) {
                    formData.materialList.splice(editMaterialIndex.value, 1);
                    return;
                }
                formData.materialList[editMaterialIndex.value] = data;
            } else {
                formData.materialList.push(data);
            }
        }
    });
});
</script>

<style lang="scss">
.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
}
</style>
