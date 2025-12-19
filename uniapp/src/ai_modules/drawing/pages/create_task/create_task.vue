<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="智能拼图"
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
        <view class="grow min-h-0">
            <view class="h-full flex flex-col" v-if="step == 1">
                <view class="mx-4">
                    <view class="text-[30rpx] font-bold"> 上传图片({{ formData.materialList.length }}) </view>
                    <view class="text-xs text-[#00000080] mt-1"> 至少需要上传2张图片 </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[26rpx] p-4">
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view
                                    class="h-[220rpx] rounded-[12rpx] relative overflow-hidden"
                                    @click="previewMaterial(item)">
                                    <image :src="item" class="w-full h-full rounded-[12rpx]" mode="aspectFill"></image>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteMaterial(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                                <view class="absolute bottom-2 w-full z-[33] flex justify-center">
                                    <view class="dh-version-name" @click="handleReplaceMaterial(index)"> 替换 </view>
                                </view>
                            </view>
                            <view
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                                @click="chooseUploadType">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加图片</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view class="h-full flex flex-col" v-if="step == 2">
                <view class="flex items-center gap-x-2 px-4">
                    <view
                        class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                        @click="handleShowCopywriter()">
                        <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                        <text class="font-bold text-[32rpx]">手动输入</text>
                    </view>
                    <navigator
                        url="/ai_modules/drawing/pages/puzzle_ai_copywriter/puzzle_ai_copywriter"
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
                                <view class="flex items-center gap-x-5" v-for="(val, valIndex) in item" :key="valIndex">
                                    <view class="flex-shrink-0 text-[#00000080]"
                                        >{{ valIndex == 0 ? "主标题" : "副标题" }}
                                    </view>
                                    <view
                                        class="flex-1 h-[80rpx] flex items-center border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                        <text class="break-all line-clamp-1">{{ val }}</text>
                                    </view>
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
            <view class="h-full px-4" v-if="step == 3">
                <view>
                    <view class="text-[30rpx] font-bold">作品名称</view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                        <u-input
                            v-model="formData.name"
                            maxlength="50"
                            placeholder-style="font-size:26rpx;"
                            placeholder="请输入"
                            clearable />
                    </view>
                </view>
                <view class="mt-[20rpx] bg-white rounded-[20rpx] px-[38rpx]">
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
                        <view class="text-[30rpx] font-bold">拼图标题</view>
                        <view class="flex items-center gap-x-1" @click="handleStep(2)">
                            <view>
                                共<text class="mx-1 text-primary font-bold">{{ formData.copywriterList.length }}</text
                                >个
                            </view>
                            <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                        </view>
                    </view>
                    <view class="flex items-center justify-between h-[106rpx]">
                        <view class="text-[30rpx] font-bold">生成拼图数量</view>
                        <view class="flex items-center gap-x-2">
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('minus')">
                                <image
                                    src="@/ai_modules/drawing/static/icons/minus_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                            <view
                                class="w-[90rpx] h-[52rpx] px-1 flex items-center justify-center bg-[#F6F6F6] rounded-[10rpx]">
                                <u-input
                                    v-model="formData.result_count"
                                    type="digit"
                                    placeholder=""
                                    :min="1"
                                    :max="100"
                                    :custom-style="{ color: '#0065fb', fontWeight: 'bold' }"
                                    input-align="center" />
                            </view>
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('add')">
                                <image
                                    src="@/ai_modules/drawing/static/icons/add_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                        </view>
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
                        <text class="font-bold text-[32rpx]">{{ formData.materialList.length }}</text>
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
                        class="rounded-[16rpx] mx-auto w-[456rpx] h-[100rpx] bg-black text-white font-bold flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateImage">
                        生成拼图（{{ formData.result_count }}个）
                    </view>
                </template>
            </view>
        </view>
    </view>
    <u-popup v-model="showUploadTip" mode="center" border-radius="24" width="90%">
        <view class="bg-white rounded-[24rpx] p-[48rpx]">
            <view class="font-bold text-center">图片上传须知</view>
            <view class="mt-[48rpx]">
                <view class="flex items-center gap-x-1">
                    <text class="text-[#FF3C26]">*</text>
                    <text>至少需要传2张图片，根据上传的图片数量随机拼图和包装</text>
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text
                        >图片素材支持{{ imageAccept.join("、") }}格式；{{ imageSize }}MB内，分辨率不超过{{
                            imageResolution[0]
                        }}*{{ imageResolution[1] }}</text
                    >
                </view>
                <view class="flex items-center gap-x-1 mt-[32rpx]">
                    <text class="text-[#FF3C26]">*</text>
                    <text>不符合条件的素材会被自动删除</text>
                </view>
            </view>
            <view class="flex gap-x-3 mt-[48rpx]">
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] rounded-[16rpx]"
                    @click="
                        showUploadTip = false;
                        isFirstOpen = false;
                    "
                    >取消</view
                >
                <view
                    class="flex-1 h-[100rpx] flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)] bg-[#000000] rounded-[16rpx] text-white"
                    @click="
                        showUploadTip = false;
                        uploadAndProcessFiles(uploadType);
                    "
                    >去上传</view
                >
            </view>
        </view>
    </u-popup>
    <choose-material
        v-model="showMaterial"
        type="image"
        :multiple="replaceMaterialIndex == -1"
        :limit="limit - formData.materialList.length"
        @select="handleSelectMaterial" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <u-popup
        v-model="showCreateSuccess"
        mode="center"
        border-radius="20"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[20rpx] py-[94rpx] px-[62rpx]">
            <view class="text-[40rpx] font-bold text-center">拼图生成中</view>
            <view class="text-[30rpx] mt-[80rpx]">拼图生成成功后，您可以自定义设置图组数量去发布图文任务 </view>
            <view
                class="mt-[98rpx] bg-black text-white text-[30rpx] font-bold rounded-[20rpx] h-[90rpx] flex items-center justify-center"
                @click="toPuzzleRecord">
                查看拼图创作
            </view>
        </view>
    </u-popup>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { createPuzzleTask } from "@/api/drawing";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { ListenerTypeEnum } from "@/ai_modules/drawing/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "上传图片" },
    { step: 2, title: "填写主题" },
    { step: 3, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    copywriterList: any[];
    materialList: any[];
    name: string;
    result_count: number;
}>({
    copywriterList: [],
    materialList: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "拼图",
    result_count: 4,
});

const showUploadTip = ref(false);
const isFirstOpen = ref(true);
const imageAccept = ["jpg", "png", "jpeg"];
const limit = 100;
const imageSize = 5;
const imageResolution = [2000, 2000];
const showMaterial = ref(false);
const uploadType = ref<"file" | "image">("image");
const replaceMaterialIndex = ref(-1);
const editCopywriterIndex = ref(-1);
const showCreateSuccess = ref(false);

const rechargePopupRef = shallowRef<any>();

//判断是否可以下一步
const canStepProceed = (stepNumber: number) => {
    switch (stepNumber) {
        case 1:
            return formData.materialList.length > 1;
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
                1: "请上传图片",
                2: "请填写主题",
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

const chooseUploadType = () => {
    uni.showActionSheet({
        itemList: ['从"微信聊天"中选择', '从"素材库"中选择', '从"手机相册"中选择'],
        success: (res) => {
            if (res.tapIndex == 0 || res.tapIndex == 2) {
                uploadType.value = res.tapIndex == 0 ? "file" : "image";
                if (isFirstOpen.value) {
                    isFirstOpen.value = false;
                    showUploadTip.value = true;
                    return;
                }
                if (!isFirstOpen.value) {
                    uploadAndProcessFiles(uploadType.value);
                }
            }
            if (res.tapIndex == 1) {
                showMaterial.value = true;
            }
        },
    });
};

const { uploadMaterialList, showUploadProgress, uploadAndProcessFiles } = useUpload({
    imageAccept,
    imageSize,
    imageResolution,
    fileAccept: imageAccept,
    fileSize: imageSize,
    onSuccess: (materials: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = materials[0].url;
        } else {
            formData.materialList.push(...materials.map((item: any) => item.url));
        }
        replaceMaterialIndex.value = -1;
    },
});

const handleSelectMaterial = async (lists: any[]) => {
    const imageCheckPromises = lists.map(
        (item: any, index) =>
            new Promise((resolve) => {
                wx.getImageInfo({
                    src: item.content,
                    success: (info: any) => {
                        const { type, width, height } = info;
                        // 判断是否符合条件
                        const isAccord =
                            width <= imageResolution[0] &&
                            height <= imageResolution[1] &&
                            imageAccept.includes(type) &&
                            parseInt(item.size) <= imageSize * 1024 * 1024;
                        if (isAccord) {
                            resolve(item.content);
                        } else {
                            uni.showToast({
                                title: `选择的图片包含不符合条件的图片，已自动过滤`,
                                icon: "none",
                            });
                            resolve(null);
                        }
                    },
                    fail: () => {
                        resolve(null);
                    },
                });
            })
    );
    const uploadImages = (await Promise.all(imageCheckPromises)).filter((url: any) => url);
    if (replaceMaterialIndex.value !== -1) {
        formData.materialList[replaceMaterialIndex.value] = uploadImages[0];
    } else {
        formData.materialList.push(...uploadImages);
    }
    replaceMaterialIndex.value = -1;
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    chooseUploadType();
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
};

const previewMaterial = (pic: string) => {
    uni.previewImage({
        urls: [pic],
    });
};

const handleSelectCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    const selectedCopywriter = formData.copywriterList[index];
    handleShowCopywriter(selectedCopywriter);
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/drawing/pages/puzzle_copywriter/puzzle_copywriter",
        params: {
            data: data ? JSON.stringify(data) : "",
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    formData.copywriterList.splice(index, 1);
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.result_count < 4) {
            uni.$u.toast("最少生成4个拼图哦");
            return;
        }

        // 四的倍数递减
        formData.result_count = Math.floor((formData.result_count - 1) / 4) * 4;
    } else {
        if (formData.result_count >= 100) {
            uni.$u.toast("最多生成100个拼图哦");
            formData.result_count = 100;
            return;
        }
        // 四的倍数递增
        formData.result_count = Math.ceil((formData.result_count + 1) / 4) * 4;
    }
};

const handleCreateImage = async () => {
    if (userTokens.value < 0) {
        rechargePopupRef.value?.open();
        return;
    }
    if (formData.result_count < 4) {
        uni.$u.toast("最少生成4个拼图哦");
        return;
    }
    if (formData.result_count % 4 !== 0) {
        uni.$u.toast("生成数量必须是4的倍数");
        return;
    }
    uni.showLoading({
        title: "提交中...",
        mask: true,
    });
    try {
        await createPuzzleTask({
            name: formData.name,
            copywriting: formData.copywriterList.map((item: any) => ({ title: item })),
            material: formData.materialList,
            result_count: formData.result_count,
        });
        showCreateSuccess.value = true;
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const toPuzzleRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation?tab=2",
        type: "reLaunch",
    });
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.PUZZLE_AI_COPYWRITER || type === ListenerTypeEnum.PUZZLE_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
            } else {
                formData.copywriterList.push(...data);
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
