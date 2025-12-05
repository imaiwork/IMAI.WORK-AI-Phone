<template>
    <view class="h-screen flex flex-col">
        <view class="grow min-h-0 relative z-30">
            <scroll-view scroll-y class="h-full">
                <view class="p-[32rpx]">
                    <view>
                        <view class="flex items-center gap-x-2">
                            <image
                                src="@/ai_modules/digital_human/static/icons/video_upload_tips_1.svg"
                                class="w-[36rpx] h-[36rpx]"></image>
                            <text class="text-[30rpx] font-bold text-[#000000cc]">视频教程</text>
                        </view>
                        <view class="mt-[36rpx]">
                            <view class="h-[384rpx] rounded-[40rpx] relative">
                                <view class="absolute top-[40rpx] left-0 w-full px-[40rpx] z-[788]">
                                    <view class="text-white text-[26rpx]"> 快速了解操作流程 </view>
                                </view>
                                <video-player
                                    :play-icon-size="88"
                                    :poster="`${config.baseUrl}static/images/dh_example_bg2.png`"
                                    :video-url="`${config.baseUrl}static/videos/dh_example2.mp4`"></video-player>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[26rpx]">
                        <view class="flex items-center gap-x-2">
                            <image
                                src="@/ai_modules/digital_human/static/icons/video_upload_tips_2.svg"
                                class="w-[36rpx] h-[36rpx]"></image>
                            <text class="text-[30rpx] font-bold text-[#000000cc]">视频要求</text>
                        </view>
                        <view class="mt-[30rpx] flex items-center gap-x-4">
                            <image
                                class="w-[320rpx] flex-shrink-0"
                                mode="widthFix"
                                src="@/ai_modules/digital_human/static/images/common/video_upload_temp.png"></image>
                            <view class="flex flex-col gap-y-[24rpx]">
                                <view
                                    v-for="(item, index) in uploadTemplateContentLists"
                                    :key="index"
                                    class="flex gap-x-[6rpx] leading-6">
                                    <text
                                        class="mt-1 flex-shrink-0 w-[36rpx] h-[36rpx] rounded-full flex items-center justify-center text-[22rpx] text-primary bg-primary-light-9">
                                        {{ index + 1 }}
                                    </text>
                                    <view>
                                        <text class="text-[26rpx] text-[#000000cc] flex-shrink-0">{{ item.name }}</text>
                                        <text class="text-[26rpx] text-[#0000004d] ml-1">{{ item.value }}</text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[26rpx]">
                        <view class="flex items-center gap-x-2">
                            <image
                                src="@/ai_modules/digital_human/static/icons/video_upload_tips_3.svg"
                                class="w-[36rpx] h-[36rpx]"></image>
                            <text class="text-[30rpx] font-bold text-[#000000cc]">错误示例</text>
                        </view>
                        <view class="mt-[30rpx]">
                            <image
                                src="@/ai_modules/digital_human/static/images/common/video_upload_error_temp.png"
                                class="h-[198rpx] w-full"></image>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="mx-[60rpx] mb-[60rpx]">
            <u-button
                type="primary"
                shape="circle"
                :custom-style="{
                    height: '90rpx',
                    boxShadow: ' 0px 3px 12px 0px rgba(0, 0, 0, 0.12)',
                    fontSize: '26rpx',
                }"
                @click="startUpload()">
                我已经知晓，开始上传视频
            </u-button>
        </view>
    </view>
    <upload-loading
        v-if="showUploadProgress"
        :progress="uploadProgressNum"
        :loading-text="loadingText"
        :progress-type="uploadProgressType"
        :show-back="formData.model_version !== DigitalHumanModelVersionEnum.SHANJIAN"
        @cancel="handleUploadCancel"
        @back="back"
        @confirm="handelUploadConfirm"></upload-loading>
    <recharge-popup v-if="showRechargePopup" ref="rechargePopupRef" @close="showRechargePopup = false"></recharge-popup>
</template>

<script setup lang="ts">
import config from "@/config";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ModeTypeEnum, ListenerTypeEnum, DigitalHumanModelVersionEnum } from "@/ai_modules/digital_human/enums";
import VideoPlayer from "@/ai_modules/digital_human/components/video-player/video-player.vue";
import UploadLoading from "@/ai_modules/digital_human/components/upload-loading/upload-loading.vue";
import { useUserStore } from "@/stores/user";
import { useUpload, uploadLimit, commonUploadLimit } from "../../hooks/useUpload";
import requestCancel from "@/utils/request/cancel";

// 定义表单数据类型
interface FormData {
    name: string;
    url: string;
    pic: string;
    anchor_id: string;
    model_version: number | string;
    width: number;
    height: number;
}

// 定义上传要求类型
interface UploadRequirement {
    resolution: string;
    fileSize: number;
}

// 支持的上传格式
const SUPPORTED_EXTENSIONS = ["mp4", "mov"];

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// 使用shallowRef优化性能，因为这些值不需要深层响应式
const modeType = shallowRef<ModeTypeEnum>();

// 使用类型定义提高代码可维护性
const formData = reactive<FormData>({
    name: "",
    url: "",
    pic: "",
    anchor_id: "",
    model_version: "",
    width: 0,
    height: 0,
});

// 充值弹窗
const showRechargePopup = ref(false);
const rechargePopupRef = ref();

// 上传状态管理
const showUploadProgress = ref(false);
const uploadProgressNum = ref(0);
const uploadProgressType = shallowRef<"video" | "image">();
const isUploadSuccess = ref(false);
const loadingText = ref("");

// 通用上传要求
const commonUploadRequirements: UploadRequirement = {
    resolution: `${commonUploadLimit.minWidthResolution}P-${commonUploadLimit.maxWidthResolution}P`,
    fileSize: commonUploadLimit.size,
};

/**
 * 创建分辨率字符串
 * @param modelVersion 模型版本
 * @returns 格式化的分辨率字符串
 */
const createResolutionString = (modelVersion: number): string => {
    return `${uploadLimit[modelVersion].minResolution}P-${uploadLimit[modelVersion].maxResolution}P`;
};

// 模型要求对应的上传要求描述
const modelUploadRequirements: Record<number, UploadRequirement> = {
    [DigitalHumanModelVersionEnum.STANDARD]: {
        resolution: createResolutionString(DigitalHumanModelVersionEnum.STANDARD),
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.STANDARD].size,
    },
    [DigitalHumanModelVersionEnum.SUPER]: {
        resolution: createResolutionString(DigitalHumanModelVersionEnum.SUPER),
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.SUPER].size,
    },
    [DigitalHumanModelVersionEnum.ADVANCED]: commonUploadRequirements,
    [DigitalHumanModelVersionEnum.ELITE]: commonUploadRequirements,
    [DigitalHumanModelVersionEnum.CHANJING]: {
        resolution: createResolutionString(DigitalHumanModelVersionEnum.CHANJING),
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.CHANJING].size,
    },
    [DigitalHumanModelVersionEnum.SHANJIAN]: {
        resolution: createResolutionString(DigitalHumanModelVersionEnum.SHANJIAN),
        fileSize: uploadLimit[DigitalHumanModelVersionEnum.SHANJIAN].size,
    },
};

// 上传模板内容列表
const uploadTemplateContentLists = computed(() => {
    // 确保model_version有效，否则使用默认值
    const modelVersion = formData.model_version
        ? typeof formData.model_version === "string"
            ? parseInt(formData.model_version)
            : formData.model_version
        : DigitalHumanModelVersionEnum.STANDARD;

    // 确保modelVersion是有效的枚举值
    const validModelVersion = Object.values(DigitalHumanModelVersionEnum).includes(modelVersion)
        ? modelVersion
        : DigitalHumanModelVersionEnum.STANDARD;

    const requirements = commonUploadRequirements;

    return [
        { name: "视频方向", value: "横向或纵向" },
        { name: "文件格式", value: SUPPORTED_EXTENSIONS.join("、") },
        { name: "分辨率", value: requirements.resolution },
        {
            name: "声音要求",
            value: "视频内音频需要清晰、响亮且无嘈杂背景音等干扰",
        },
        { name: "文件大小", value: `小于${requirements.fileSize}MB` },
    ];
});

// 上传参数
const uploadParams = computed(() => {
    // 确保model_version有效
    return formData.model_version ? uploadLimit[formData.model_version] : null;
});

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

/**
 * 获取对应模型版本的场景键
 * @param modelVersion 模型版本
 * @returns 对应的场景键
 */
const getSceneKeysByModelVersion = (modelVersion: number): { avatar: string } => {
    const sceneKeys = {
        pro: { avatar: TokensSceneEnum.HUMAN_AVATAR_PRO },
        normal: { avatar: TokensSceneEnum.HUMAN_AVATAR },
        advanced: { avatar: TokensSceneEnum.HUMAN_AVATAR_ADVANCED },
        elite: { avatar: TokensSceneEnum.HUMAN_AVATAR_ELITE },
        chanjing: { avatar: TokensSceneEnum.HUMAN_AVATAR_CHANJING },
    };

    switch (modelVersion) {
        case DigitalHumanModelVersionEnum.SUPER:
            return sceneKeys.pro;
        case DigitalHumanModelVersionEnum.ADVANCED:
            return sceneKeys.advanced;
        case DigitalHumanModelVersionEnum.ELITE:
            return sceneKeys.elite;
        case DigitalHumanModelVersionEnum.CHANJING:
            return sceneKeys.chanjing;
        default:
            return sceneKeys.normal;
    }
};

/**
 * 开始上传视频
 */
const startUpload = async () => {
    // 检查是否为形象模式，并验证积分
    if (modeType.value === ModeTypeEnum.ANCHOR) {
        const shanjianKeys = getSceneKeysByModelVersion(DigitalHumanModelVersionEnum.SHANJIAN);
        const chanjingKeys = getSceneKeysByModelVersion(DigitalHumanModelVersionEnum.CHANJING);
        const { score: shanjianScore } = getTokenByScene(shanjianKeys.avatar);
        const { score: chanjingScore } = getTokenByScene(chanjingKeys.avatar);

        // 积分不足，显示充值弹窗
        if (userTokens.value < shanjianScore + chanjingScore) {
            showRechargePopup.value = true;
            await nextTick();
            rechargePopupRef.value?.open();
            return;
        }
    }
    // 设置导航栏颜色（非H5环境）
    // #ifndef H5
    uni.setNavigationBarColor({
        frontColor: "#000000",
        backgroundColor: "#000000",
    });
    // #endif

    // 开始上传
    const { upload } = useUpload({
        size: commonUploadLimit.size,
        widthResolution: [commonUploadLimit.minWidthResolution, commonUploadLimit.maxWidthResolution],
        heightResolution: [commonUploadLimit.minHeightResolution, commonUploadLimit.maxHeightResolution],
        duration: [commonUploadLimit.videoMinDuration, commonUploadLimit.videoMaxDuration],
        extension: SUPPORTED_EXTENSIONS,
        async onSuccess(res) {
            const { url, pic, width, height } = res;
            // 更新表单数据
            formData.url = url;
            formData.pic = pic;
            formData.width = width;
            formData.height = height;

            formData.name = uni.$u.timeFormat(Date.now(), "yyyymmddhhMM");
            handelUploadConfirm();
        },
        onProgress(res) {
            // 更新进度
            uploadProgressType.value = res.type;
            uploadProgressNum.value = res.progress;
            loadingText.value = uploadProgressType.value === "video" ? "视频正在上传中..." : "图片正在上传中...";
            showUploadProgress.value = true;
        },
        onError(err) {
            // 错误处理
            if (err.errMsg && err.errMsg === "chooseMedia:fail api scope is not declared in the privacy agreement") {
                uni.$u.toast("请完善隐私协议，否则无法使用");
            } else if (err && err.errMsg && err.errMsg.indexOf("cancel") == -1) {
                uni.$u.toast(err.errMsg || "上传失败");
            }
            showUploadProgress.value = false;
            uploadProgressNum.value = 0;
            resetNavigationBarColor();
        },
    });
    upload();
};

/**
 * 处理上传取消
 */
const handleUploadCancel = () => {
    // 取消请求
    requestCancel.remove("/upload/video");
    requestCancel.remove("/upload/image");

    // 重置状态
    showUploadProgress.value = false;
    uploadProgressNum.value = 0;
    loadingText.value = "";
    resetNavigationBarColor();
};

/**
 * 处理上传确认
 */
const handelUploadConfirm = async () => {
    uni.$emit("confirm", {
        type: ListenerTypeEnum.VIDEO_UPLOAD,
        data: formData,
    });
    uni.navigateBack();
    return;
};

/**
 * 返回首页
 */
const back = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/index/index",
        type: "redirect",
    });
};

/**
 * 重置导航栏颜色
 */
const resetNavigationBarColor = () => {
    // #ifndef H5
    uni.setNavigationBarColor({
        frontColor: "#000000",
        backgroundColor: "#F9FAFB",
    });
    // #endif
};

/**
 * 页面加载时处理
 */
onLoad((options: any) => {
    try {
        // 设置模式类型
        if (options.type) {
            modeType.value = options.type;
        }

        // 设置模型版本
        // if (options.model_version) {
        //     const modelVersion = parseInt(options.model_version);
        //     if (!isNaN(modelVersion)) {
        //         formData.model_version = modelVersion;
        //     } else {
        //         console.warn("无效的模型版本:", options.model_version);
        //     }
        // }
    } catch (error) {
        console.error("页面加载参数处理失败:", error);
    }
});
</script>

<style scoped lang="scss">
/* 滚动条样式优化 */
:deep(.uni-scroll-view::-webkit-scrollbar) {
    display: none;
    width: 0;
    height: 0;
    background-color: transparent;
}

/* 按钮悬停效果 */
:deep(.u-button) {
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:active {
        transform: translateY(2rpx);
        box-shadow: 0px 1px 6px 0px rgba(0, 0, 0, 0.08);
    }
}
</style>
