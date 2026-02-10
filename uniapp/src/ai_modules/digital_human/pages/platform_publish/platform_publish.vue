<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="发布视频创建"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-2 w-full">
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
                <view class="px-4">
                    <view class="text-[30rpx] font-medium">视频素材({{ formData.materialList.length }})</view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[26rpx] p-4">
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view
                                    class="aspect-[3/4] rounded-[12rpx] relative overflow-hidden"
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
                                    @click="handleDeleteMaterial(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                                <div class="absolute bottom-4 w-full z-[89] flex justify-center">
                                    <view class="dh-version-name" @click.stop="handleReplaceMaterial(index, 1)">
                                        替换
                                    </view>
                                </div>
                            </view>
                            <view
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center aspect-[3/4]"
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
            <view class="h-full flex flex-col" v-show="step === 2">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleShowCopywriter()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-medium text-[32rpx]">手动输入</text>
                    </view>
                    <navigator
                        url="/ai_modules/digital_human/pages/platform_publish_ai_copywriter/platform_publish_ai_copywriter"
                        hover-class="none"
                        class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="text-white font-medium text-[32rpx]">AI生成</text>
                    </navigator>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-y-[30rpx] pb-[100rpx]">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="copywriter-item"
                                @click="handleEditCopywriter(index)">
                                <view class="text-[30rpx] font-medium"> {{ item.title }} </view>
                                <view class="font-medium mt-[26rpx]">
                                    {{ item.content }}
                                </view>
                                <view class="mt-[50rpx] flex items-center flex-wrap gap-2" v-if="item.topic.length > 0">
                                    <view
                                        v-for="(tag, tindex) in item.topic"
                                        :key="tindex"
                                        class="text-xs text-[#0000004d]">
                                        #{{ tag }}
                                    </view>
                                </view>
                                <view class="mt-2" v-if="item.poi">
                                    <u-icon name="map-fill" size="24" color="#0000004d"></u-icon>
                                    <text class="text-xs text-[#000000]/50 ml-1 font-medium">{{ item.poi }}</text>
                                </view>
                                <view
                                    class="absolute top-2 right-2 w-5 h-5 flex items-center justify-center bg-[#0000004d] rounded-full"
                                    @click.stop="handleDeleteCopywriter(index)">
                                    <u-icon name="close" size="20" color="#ffffff"></u-icon>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <view v-else class="mt-[100rpx]">
                        <empty :size="260" text="您还没有文案哦" />
                    </view>
                </view>
            </view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.materialList.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-medium text-[32rpx]">{{ formData.materialList.length }}</text>
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
                    <view
                        class="flex-1 rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateVideo">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>
    <choose-history v-model="showHistory" :limit="replaceMaterialIndex === -1 ? 9 : 1" @select="handleSelectHistory" />
    <choose-material
        v-model="showMaterialLibrary"
        :limit="replaceMaterialIndex === -1 ? 9 : 1"
        :type="uploadMaterialType"
        @select="handleSelectMaterial" />
    <video-preview
        v-if="showVideoPreview"
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false"></video-preview>
    <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="视频发布任务创建成功"
        desc="您可以立即去设置发布任务"
        to-text=""
        @seek="toRecord" />
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";
import { createManualPublish } from "@/api/device";
import useUpload from "@/hooks/useUpload";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import ChooseHistory from "@/ai_modules/digital_human/components/choose-history/choose-history.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";

const { on } = useEventBusManager();

const steps = ref([
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
]);

const step = ref(1);

const formData = reactive<{
    name: string;
    copywriterList: any[];
    materialList: any[];
}>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "发布视频",
    copywriterList: [],
    materialList: [],
});
const uploadMaterialType = ref<any>();
const showHistory = ref(false);
const showMaterialLibrary = ref(false);
const replaceMaterialIndex = ref(-1);
const showVideoPreview = ref(false);
const playItem = reactive({
    pic: "",
    url: "",
});

const editCopywriterIndex = ref(-1);
const showChooseAgent = ref(false);

const showCreateSuccess = ref(false);

const canStepProceed = (stepNumber: number) => {
    const strategy: Record<number, () => boolean> = {
        1: () => formData.materialList.length > 0,
        2: () => true,
    };
    return strategy[stepNumber]?.() ?? false;
};

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
            const messages: Record<number, () => string> = {
                1: () => "请至少选择一个素材",
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

const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    onSuccess: (materials: any[]) => {
        const targetList = "materialList";
        const index = replaceMaterialIndex.value;
        if (index !== -1) {
            formData[targetList][index] = materials[0];
        } else {
            formData[targetList] = [...formData[targetList], ...materials];
        }
        replaceMaterialIndex.value = -1;
    },
});

const chooseUploadType = (type: 0 | 1) => {
    uni.showActionSheet({
        itemList: ["从相册选择图片", "从相册选择视频", "从图片素材库选择", "从视频素材库选择", "从创作库选择"],
        success: async (res) => {
            const { tapIndex } = res;
            if ([0, 1].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 0 ? "image" : "video";

                uploadAndProcessFiles(uploadMaterialType.value);
            } else if ([2, 3].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 2 ? "image" : "video";
                showMaterialLibrary.value = true;
            } else if (tapIndex === 4) {
                showHistory.value = true;
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
        playItem.pic = pic;
        playItem.url = url;
        showVideoPreview.value = true;
    }
};

const handleReplaceMaterial = (index: number, type: 0 | 1) => {
    replaceMaterialIndex.value = index;
    chooseUploadType(type);
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
};

const handleSelectHistory = async (lists: any[]) => {
    const data = lists.map((item: any) => ({
        pic: item.pic,
        url: item.clip_result_url || item.video_result_url,
        name: item.name,
        duration: item.duration,
    }));

    const normalized = data.map((item) => ({
        ...item,
        actualUrl: item.url,
    }));

    await processAndAppend({
        isParseVideoElement: false,
        rawList: normalized,
        urlField: "actualUrl",
        type: "video",
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showHistory.value = false),
    });
};
const handleSelectMaterial = async (res: any[]) => {
    const type = uploadMaterialType.value;
    await processAndAppend({
        isParseVideoElement: false,
        rawList: res,
        urlField: "url",
        type: type as "video" | "image",
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showMaterialLibrary.value = false),
    });
};

const handleSelectCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    const selectedCopywriter = formData.copywriterList[index];
    handleShowCopywriter(selectedCopywriter);
};

const handleEditCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_copywriter/platform_publish_copywriter",
        params: { copywriter: JSON.stringify(formData.copywriterList[index]) },
    });
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_copywriter/platform_publish_copywriter",
        params: {
            data: data ? JSON.stringify(data) : "",
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_ai_copywriter/platform_publish_ai_copywriter",
        params: {
            agentData: JSON.stringify(data),
        },
    });
};

const handleCreateVideo = async () => {
    if (formData.copywriterList.length == 0) {
        uni.$u.toast("请至少填写一个文案");
        return;
    }
    const isOverLimit = formData.copywriterList.some((item: any) => item.content.length > 1000);
    if (isOverLimit) {
        uni.$u.toast("文案内容含有超过1000个字符的文案，请修改后重新提交");
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        await createManualPublish({
            name: formData.name,
            media_url: formData.materialList.map((item: any) => ({ url: item.url, pic: item.pic, type: item.type })),
            copywriting: formData.copywriterList.map((item: any) => ({
                title: item.title,
                content: item.content,
                topic: item.topic,
                poi: item.poi,
            })),
        });
        uni.hideLoading();
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

const toRecord = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/platform_publish_works/platform_publish_works",
        type: "redirect",
    });
};

onLoad(async (options: any) => {
    if (options.source === "creation_video") {
        const videoIds = JSON.parse(options.ids || "[]");
        const { lists } = await getVideoCreationRecord({ page_size: 99999 });
        if (lists?.length) {
            lists
                .filter((item: any) => videoIds.includes(item.task_id))
                .forEach((item: any) => {
                    formData.materialList.push({
                        url: item.clip_result_url || item.video_result_url,
                        pic: item.pic,
                        type: "video",
                    });
                });
        }
    }
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (
            type === ListenerTypeEnum.PLATFORM_PUBLISH_COPYWRITER ||
            type === ListenerTypeEnum.PLATFORM_PUBLISH_AI_COPYWRITER
        ) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
                editCopywriterIndex.value = -1;
            } else {
                formData.copywriterList.push(...data);
            }
        }
    });
});
</script>

<style scoped lang="scss">
.copywriter-item {
    @apply rounded-[20rpx] bg-white p-[38rpx] relative;
}
</style>
