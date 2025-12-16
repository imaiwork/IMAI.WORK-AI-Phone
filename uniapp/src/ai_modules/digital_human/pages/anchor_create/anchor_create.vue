<template>
    <view class="h-screen flex flex-col dh-bg">
        <u-navbar
            title="智能数字人"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
                    <view>
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-x-1">
                                <text class="text-[#FF3C26]">*</text>
                                <text class="font-bold">上传视频</text>
                            </view>
                            <view v-if="anchorData.pic" class="text-primary" @click="handleUploadAnchorVideo">
                                更换视频
                            </view>
                        </view>
                        <view class="mt-4 h-[416rpx] rounded-[16rpx] overflow-hidden">
                            <view
                                v-if="!anchorData.pic"
                                class="flex flex-col items-center justify-center h-full bg-[#f7f8fc]"
                                @click="handleUploadAnchorVideo">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add2.svg"
                                    class="w-[56rpx] h-[56rpx]"></image>
                                <text class="upload-text">上传训练视频</text>
                                <view class="mt-[48rpx] flex flex-col gap-y-2">
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]"
                                            >视频时长：{{ commonUploadLimit.videoMinDuration }}-{{
                                                commonUploadLimit.videoMaxDuration
                                            }}秒，文件大小≤{{ commonUploadLimit.size }}MB</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]"
                                            >分辨率：最大支持{{
                                                commonUploadLimit.maxWidthResolution / 1000
                                            }}k(单边小于{{ commonUploadLimit.maxWidthResolution }}*{{
                                                commonUploadLimit.maxHeightResolution
                                            }})</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]">视频编码：h264，帧率：25fps</view>
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]"
                                            >视频格式：{{ SUPPORTED_EXTENSIONS.join("、") }}</view
                                        >
                                    </view>
                                </view>
                            </view>
                            <view v-else class="bg-black h-full relative">
                                <video
                                    :src="anchorData.url"
                                    :poster="anchorData.pic"
                                    class="w-full h-full object-cover"></video>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[64rpx]">
                        <view class="flex items-center justify-between">
                            <view class="flex items-center gap-x-1">
                                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                                <text class="font-bold">上传授权视频</text>
                            </view>
                            <view v-if="authData.pic" class="text-primary" @click="handleUploadAuthVideo">
                                更换视频
                            </view>
                        </view>
                        <view class="mt-4 h-[416rpx] rounded-[16rpx] overflow-hidden">
                            <view
                                v-if="!authData.pic"
                                class="flex flex-col items-center justify-center h-full bg-[#f7f8fc]"
                                @click="handleUploadAuthVideo">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add2.svg"
                                    class="w-[56rpx] h-[56rpx]"></image>
                                <text class="upload-text">点击上传视频</text>
                                <view class="mt-[48rpx] flex flex-col gap-y-2">
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]"
                                            >视频时长：小于{{ AUTH_VIDEO_MAX_DURATION / 60 }}分钟</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]">视频编码：h264</view>
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#00000080] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#00000080] text-[24rpx]"
                                            >确保本人出境授权，保证声音清晰</view
                                        >
                                    </view>
                                </view>
                            </view>
                            <view v-else class="bg-black h-full relative">
                                <video
                                    :src="authData.url"
                                    :poster="authData.pic"
                                    class="w-full h-full object-cover"></video>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="flex-shrink-0 p-4 bg-white flex items-center gap-x-3">
            <view
                class="h-[100rpx] w-[220rpx] bg-[#F3F3F3] rounded-[20px] flex items-center justify-center text-[30rpx] font-bold"
                @click="showExample = true">
                拍摄教程
            </view>
            <view
                class="flex-1 h-[100rpx] text-white flex items-center justify-center rounded-[20rpx] font-bold"
                :class="[isCreate ? 'bg-black' : 'bg-[#787878CC]']"
                @click="handleCreateAnchor">
                开始克隆（消耗{{ getToken }}算力）
            </view>
        </view>
    </view>
    <u-popup v-model="showCreateStatus" mode="center" border-radius="48" width="90%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] p-[28rpx]">
            <view class="rounded-full w-[80rpx] h-[80rpx] mx-auto flex items-center justify-center bg-black mt-[40rpx]">
                <u-icon :name="isSuccess ? 'checkmark' : 'error'" color="#ffffff" size="28"></u-icon>
            </view>
            <view class="mt-[28rpx] text-center">{{ isSuccess ? "克隆成功" : detail.remark || "创建失败" }}</view>
            <view
                class="w-full h-[100rpx] text-white flex items-center justify-center rounded-[50rpx] bg-black mt-[66rpx] shadow-[0_12rpx_24rpx_0_rgba(0,101,251,0.2)]"
                @click="handleConfirm">
                确认</view
            >
        </view>
    </u-popup>
    <popup-bottom :show="showExample" title="拍摄教程" height="80%" @close="showExample = false">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
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
                    <view class="mt-[26rpx] grid grid-cols-2 gap-x-4">
                        <view>
                            <view class="flex items-center gap-x-2">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/video_upload_tips_2.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                                <text class="text-[30rpx] font-bold text-[#000000cc]">视频要求</text>
                            </view>
                            <view class="mt-[30rpx]">
                                <image
                                    class="w-[290rpx]"
                                    mode="widthFix"
                                    src="@/ai_modules/digital_human/static/images/common/video_upload_temp.png"></image>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center gap-x-2">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/video_upload_tips_3.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                                <text class="text-[30rpx] font-bold text-[#000000cc]">错误示例</text>
                            </view>
                            <view class="mt-[30rpx] grid grid-cols-2 gap-3">
                                <view class="flex flex-col items-center justify-between">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error1.png"
                                        class="w-[128rpx] h-[128rpx]"></image>
                                    <text class="text-[#000000cc] mt-[12rpx]">遮挡面部</text>
                                </view>
                                <view class="flex flex-col items-center justify-between">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error2.png"
                                        class="w-[128rpx] h-[128rpx]"></image>
                                    <text class="text-[#000000cc] mt-[12rpx]">人脸出框</text>
                                </view>
                                <view class="flex flex-col items-center justify-between">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error3.png"
                                        class="w-[128rpx] h-[128rpx]"></image>
                                    <text class="text-[#000000cc] mt-[12rpx]">侧脸拍摄</text>
                                </view>
                                <view class="flex flex-col items-center justify-between">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error4.png"
                                        class="w-[128rpx] h-[128rpx]"></image>
                                    <text class="text-[#000000cc] mt-[12rpx]">多人出镜</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
    <upload-loading
        v-if="showUploadProgress"
        :progress="uploadProgressNum"
        :loading-text="loadingText"
        :progress-type="uploadProgressType"
        @cancel="handleUploadCancel"></upload-loading>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { getVideoTranscodeResult, videoTranscode } from "@/api/app";
import { createAnchor, createShanjianAnchor, getShanjianAnchorDetail } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum, ModeTypeEnum } from "@/ai_modules/digital_human/enums";
import requestCancel from "@/utils/request/cancel";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import config from "@/config";
import { requestAuthorization } from "@/utils/file";
import usePolling from "@/hooks/usePolling";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useUpload, commonUploadLimit } from "@/ai_modules/digital_human/hooks/useUpload";
import UploadLoading from "@/ai_modules/digital_human/components/upload-loading/upload-loading.vue";
import { useEventBusManager } from "@/hooks/useEventBusManager";

const { emit, on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const appStore = useAppStore();

const isOssTranscode = computed(() => appStore.config?.is_oss_transcode);

const anchorData = reactive<any>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM"),
    pic: "",
    url: "",
    width: 0,
    height: 0,
    anchor_id: "",
});

const authData = reactive<any>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM"),
    pic: "",
    url: "",
});

const detail = ref<any>({});
const showCreateStatus = ref(false);
const activePollingEnds = ref<Array<() => void>>([]);

const pageSource = ref<DigitalHumanModelVersionEnum | DigitalHumanModelVersionEnum[]>();

const isSuccess = ref(false);

// 支持的上传格式
const SUPPORTED_EXTENSIONS = ["mp4", "mov"];
// 授权视频最大时长
const AUTH_VIDEO_MAX_DURATION = 120;

// 显示拍摄教程弹框
const showExample = ref(false);

// 上传状态管理
const showUploadProgress = ref(false);
const uploadProgressNum = ref(0);
const uploadProgressType = shallowRef<"video" | "image">();
const loadingText = ref("");

// 充值弹窗
const rechargePopupRef = shallowRef();

// 获取消耗的算力
const getToken = computed(() => {
    const token1 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN)?.score;
    const token2 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_CHANJING)?.score;
    return parseFloat(token1) + parseFloat(token2);
});

const isCreate = computed(() => {
    return authData.url && anchorData.url;
});

const handleUploadAnchorVideo = () => {
    const { upload } = useUpload({
        size: commonUploadLimit.size,
        widthResolution: [commonUploadLimit.minWidthResolution, commonUploadLimit.maxWidthResolution],
        heightResolution: [commonUploadLimit.minHeightResolution, commonUploadLimit.maxHeightResolution],
        duration: [commonUploadLimit.videoMinDuration, commonUploadLimit.videoMaxDuration],
        extension: SUPPORTED_EXTENSIONS,
        async onSuccess(res) {
            const { url, pic, width, height } = res;
            // 更新表单数据
            anchorData.url = url;
            anchorData.pic = pic;
            anchorData.width = width;
            anchorData.height = height;

            anchorData.name = uni.$u.timeFormat(Date.now(), "yyyymmddhhMM");
            showUploadProgress.value = false;
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
            showUploadProgress.value = false;
            uploadProgressNum.value = 0;
            resetNavigationBarColor();
        },
    });
    upload();
};

const handleUploadAuthVideo = () => {
    uni.showActionSheet({
        itemList: ["录制授权视频", "从手机相册选择", "选择历史授权视频"],
        success: async (res) => {
            if (res.tapIndex === 0) {
                const isAuthorized = await requestAuthorization("scope.camera");
                if (!isAuthorized) {
                    uni.$u.toast("您关闭了权限，请前往设置打开权限");
                    return;
                }
                uni.$u.route({
                    url: "/ai_modules/digital_human/pages/anchor_auth_camera/anchor_auth_camera",
                });
            } else if (res.tapIndex === 1) {
                handleUploadAuthVideoAlbum();
            } else if (res.tapIndex === 2) {
                uni.$u.route({
                    url: "/ai_modules/digital_human/pages/anchor_auth_video/anchor_auth_video",
                });
            }
        },
    });
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

// 视频转码
const handleVideoTranscode = async (url: string) => {
    return new Promise(async (resolve: any, reject: any) => {
        try {
            const data = await videoTranscode({
                video_url: url,
            });
            const { start, end } = usePolling(async () => {
                try {
                    const result = await getVideoTranscodeResult({
                        jobid: data.jobid,
                    });
                    if (result.state == "TranscodeSuccess") {
                        end();
                        resolve(true);
                    } else if (result.state == "TranscodeFail" || result.state == "TranscodeCancelled") {
                        end();
                        resolve(false);
                    }
                } catch (error: any) {
                    end();
                    resolve(false);
                }
            }, {});
            activePollingEnds.value.push(end);
            await start();
        } catch (error: any) {
            resolve(false);
        }
    });
};

const handleUploadAuthVideoAlbum = () => {
    const { upload } = useUpload({
        duration: [1, AUTH_VIDEO_MAX_DURATION],
        extension: SUPPORTED_EXTENSIONS,
        onProgress: (res: any) => {
            uni.showLoading({
                title: "视频上传中",
                mask: true,
            });
        },
        onSuccess: async (res: any) => {
            uni.hideLoading();
            uni.showToast({
                title: "视频上传成功",
                icon: "none",
                duration: 3000,
            });

            authData.pic = res.pic;
            authData.url = res.url;
            authData.width = res.width;
            authData.height = res.height;
        },
        onError: (err: any) => {
            const { type, error } = err;
            uni.hideLoading();
            if (type == "video") {
                uni.showToast({
                    title: error || "视频上传失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        },
    });

    upload();
};

const handleCreateAnchor = async () => {
    if (userTokens.value <= getToken.value) {
        rechargePopupRef.value?.open();
        return;
    }

    if (!anchorData.url) {
        uni.showToast({
            title: "请上传形象视频",
            icon: "none",
            duration: 3000,
        });
        return;
    } else if (!authData.url) {
        uni.showToast({
            title: "请上传授权视频",
            icon: "none",
            duration: 3000,
        });
        return;
    }

    uni.showLoading({
        title: "创建形象中...",
        mask: true,
    });

    if (isOssTranscode.value) {
        try {
            Promise.allSettled([await handleVideoTranscode(anchorData.url), await handleVideoTranscode(authData.url)]);
        } catch (error: any) {}
    }
    // shanjian形象创建
    const shanjianCreateAnchor = () => {
        return new Promise(async (resolve: any, reject: any) => {
            await createShanjianAnchor({
                name: anchorData.name,
                pic: anchorData.pic,
                anchor_url: anchorData.url,
                authorized_pic: authData.pic,
                authorized_url: authData.url,
            })
                .then(async (res) => {
                    resolve(res);
                })
                .catch((error) => {
                    reject(false);
                });
        });
    };
    const createOtherAnchor = (modelVersion: DigitalHumanModelVersionEnum) => {
        return new Promise(async (resolve: any, reject: any) => {
            await createAnchor({
                name: anchorData.name,
                url: anchorData.url,
                pic: anchorData.pic,
                model_version: modelVersion,
                width: anchorData.width,
                height: anchorData.height,
            })
                .then((res) => {
                    resolve(res);
                })
                .catch((error) => {
                    reject(false);
                });
        });
    };
    try {
        const [res1, res2]: any = await Promise.allSettled([
            shanjianCreateAnchor(),
            createOtherAnchor(DigitalHumanModelVersionEnum.CHANJING),
            createOtherAnchor(DigitalHumanModelVersionEnum.STANDARD),
        ]);
        const isSJ = pageSource.value == DigitalHumanModelVersionEnum.SHANJIAN;
        if (!pageSource.value) {
            uni.hideLoading();
            showCreateStatus.value = true;
            isSuccess.value = true;
        } else if (isSJ) {
            const { start, end } = usePolling(async () => {
                detail.value = await getShanjianAnchorDetail({
                    id: res1.value.id,
                });
                const { status } = detail.value;
                if (status == 2 || status == 3 || status == 5 || status == 6) {
                    uni.hideLoading();
                    showCreateStatus.value = true;
                    isSuccess.value = status == 3 || status == 6;
                    end();
                    return;
                }
            });
            activePollingEnds.value.push(end);
            start();
        } else {
            uni.hideLoading();
            showCreateStatus.value = true;
            anchorData.anchor_id = res2.value.id;
            anchorData.model_version = DigitalHumanModelVersionEnum.CHANJING;
            isSuccess.value = !!res2.value;
        }
    } catch (error) {
        isSuccess.value = false;
        uni.hideLoading();
    }
};

const handleConfirm = () => {
    if (isSuccess.value) {
        emit("confirm", {
            type: ListenerTypeEnum.CREATE_ANCHOR,
            data: DigitalHumanModelVersionEnum.SHANJIAN == pageSource.value ? detail.value : anchorData,
        });
        uni.navigateBack();
    } else {
        // 清空授权信息
        authData.pic = "";
        authData.url = "";
        authData.name = "";
        authData.width = 0;
        authData.height = 0;
        authData.anchor_id = "";
        showCreateStatus.value = false;
    }
};

const getAnchorData = (data: any) => {
    anchorData.name = data.name;
    anchorData.pic = data.pic;
    anchorData.url = data.url;
    anchorData.width = data.width;
    anchorData.height = data.height;
};

const getAuthData = (data: any) => {
    authData.name = data.name;
    authData.pic = data.pic;
    authData.url = data.url;
};

onLoad((options: any) => {
    if (options.source) pageSource.value = options.source;
    on("confirm", (result: any) => {
        const { type, data } = result;
        if (type === ListenerTypeEnum.VIDEO_UPLOAD) {
            getAnchorData(data);
        }
        if (type === ListenerTypeEnum.ANCHOR_AUTH || type === ListenerTypeEnum.UPLOAD_AUTH_CAMERA) {
            getAuthData(data);
        }
    });
});

onUnload(() => {
    uni.hideLoading();
    activePollingEnds.value.forEach((endFn) => endFn());
    activePollingEnds.value = [];
});
</script>

<style scoped lang="scss">
.upload-text {
    margin-top: 28rpx;
    background: linear-gradient(90deg, rgba(71, 213, 159, 1) 0%, rgba(55, 204, 237, 1) 100%);
    font-weight: bold;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
