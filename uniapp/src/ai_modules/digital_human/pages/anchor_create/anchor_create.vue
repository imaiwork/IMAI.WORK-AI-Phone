<template>
    <view class="h-screen flex flex-col bg-[#F5F7FA]">
        <u-navbar
            title="智能数字人"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }">
        </u-navbar>

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="p-4 pb-8 flex flex-col gap-y-4">
                    <view class="bg-white rounded-[32rpx] p-5 shadow-sm">
                        <view class="flex items-center justify-between mb-4">
                            <view class="flex items-center gap-x-1">
                                <view class="w-1 h-4 bg-black rounded-full mr-1"></view>
                                <text class="font-bold text-[32rpx] text-[#1A1A1A]">上传形象视频</text>
                                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            </view>
                            <view
                                v-if="anchorData.pic"
                                class="text-[26rpx] px-3 py-1 bg-[#F5F7FA] rounded-full text-[#666] active:opacity-70"
                                @click="handleUploadAnchorVideo">
                                更换视频
                            </view>
                        </view>

                        <view class="h-[440rpx] rounded-[24rpx] overflow-hidden relative group">
                            <view
                                v-if="!anchorData.pic"
                                class="flex flex-col items-center justify-center h-full bg-[#F8F9FB] rounded-[24rpx] border-2 border-dashed border-[#E1E4E8] transition-all active:bg-[#F0F2F5]"
                                @click="handleUploadAnchorVideo">
                                <view
                                    class="w-[88rpx] h-[88rpx] bg-white rounded-full flex items-center justify-center shadow-sm mb-3">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/add2.svg"
                                        class="w-[48rpx] h-[48rpx]"></image>
                                </view>
                                <text class="upload-text text-[30rpx]">点击上传训练视频</text>

                                <view class="mt-3 px-4 py-3 bg-[#ffffff]/60 rounded-[16rpx] w-[90%] backdrop-blur-sm">
                                    <view class="flex flex-col gap-y-1.5">
                                        <view class="info-item">
                                            <text class="info-dot"></text>
                                            <text
                                                >时长：{{ commonUploadLimit.videoMinDuration }}-{{
                                                    commonUploadLimit.videoMaxDuration
                                                }}秒，大小≤{{ commonUploadLimit.size }}MB</text
                                            >
                                        </view>
                                        <view class="info-item">
                                            <text class="info-dot"></text>
                                            <text
                                                >分辨率：单边 ≤ {{ commonUploadLimit.maxWidthResolution }}*{{
                                                    commonUploadLimit.maxHeightResolution
                                                }}</text
                                            >
                                        </view>
                                        <view class="info-item">
                                            <text class="info-dot"></text>
                                            <text>编码：h264，帧率：25fps</text>
                                        </view>
                                        <view class="info-item">
                                            <text class="info-dot"></text>
                                            <text>格式：{{ SUPPORTED_EXTENSIONS.join(" / ") }}</text>
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view v-else class="w-full h-full relative bg-black">
                                <video
                                    :src="anchorData.url"
                                    :poster="anchorData.pic"
                                    class="w-full h-full object-cover"></video>
                                <view
                                    class="absolute inset-0 pointer-events-none border border-[#000000]/5 rounded-[24rpx]"></view>
                            </view>
                        </view>
                    </view>

                    <view class="bg-white rounded-[32rpx] p-5 shadow-sm">
                        <view class="flex items-center justify-between mb-4">
                            <view class="flex items-center gap-x-1">
                                <view class="w-1 h-4 bg-black rounded-full mr-1"></view>
                                <text class="font-bold text-[32rpx] text-[#1A1A1A]">上传授权视频</text>
                                <text class="text-[#FF3C26] text-[32rpx]">*</text>
                            </view>
                            <view class="flex items-center gap-x-2">
                                <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-fit">
                                    <view class="w-[268rpx] grid grid-cols-2 relative h-[60rpx]">
                                        <view
                                            v-for="(item, index) in ['手动授权', 'AI授权']"
                                            :key="index"
                                            class="rounded-[12rpx] text-xs font-bold flex items-center justify-center z-10 transition-colors duration-500"
                                            :class="authIndex === index ? 'text-primary' : 'text-[#000000]/50'"
                                            @click="authIndex = index">
                                            {{ item }}
                                        </view>
                                        <view
                                            class="tab-slider"
                                            :style="{
                                                transform: `translateX(${authIndex * 100}%)`,
                                            }"></view>
                                    </view>
                                </view>
                                <view @click="showAuthHelp = true">
                                    <u-icon name="question-circle-fill" color="#CCCCCC" size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                        <template v-if="authIndex === 0">
                            <view class="h-[420rpx] rounded-[24rpx] overflow-hidden relative">
                                <view
                                    v-if="!authData.pic"
                                    class="flex flex-col items-center justify-center h-full bg-[#F8F9FB] rounded-[24rpx] border-2 border-dashed border-[#E1E4E8] transition-all active:bg-[#F0F2F5]"
                                    @click="handleUploadAuthVideo">
                                    <view
                                        class="w-[88rpx] h-[88rpx] bg-white rounded-full flex items-center justify-center shadow-sm mb-3">
                                        <image
                                            src="@/ai_modules/digital_human/static/icons/add2.svg"
                                            class="w-[48rpx] h-[48rpx]"></image>
                                    </view>
                                    <text class="upload-text text-[30rpx]">点击上传授权视频</text>

                                    <view
                                        class="mt-6 px-4 py-3 bg-[#ffffff]/60 rounded-[16rpx] w-[90%] backdrop-blur-sm">
                                        <view class="flex flex-col gap-y-1.5">
                                            <view class="info-item">
                                                <text class="info-dot"></text>
                                                <text>视频时长：小于{{ AUTH_VIDEO_MAX_DURATION / 60 }}分钟</text>
                                            </view>
                                            <view class="info-item">
                                                <text class="info-dot"></text>
                                                <text>视频编码：h264</text>
                                            </view>
                                            <view class="info-item">
                                                <text class="info-dot"></text>
                                                <text class="text-[#FF3C26]">确保本人出镜授权，保证声音清晰</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="w-full h-full relative bg-black">
                                    <video
                                        :src="authData.url"
                                        :poster="authData.pic"
                                        class="w-full h-full object-cover"></video>
                                    <view
                                        class="absolute inset-0 pointer-events-none border border-[#000000]/5 rounded-[24rpx]"></view>
                                </view>
                            </view>
                            <view class="flex items-center justify-end mt-4" v-if="authData.pic">
                                <view
                                    class="text-[26rpx] px-3 py-1 bg-[#F5F7FA] rounded-full text-[#666]"
                                    @click="handleUploadAuthVideo">
                                    更换视频
                                </view>
                            </view>
                        </template>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 p-4 pb-[calc(20rpx+env(safe-area-inset-bottom))] bg-white flex items-center gap-x-3 shadow-[0_-4rpx_16rpx_rgba(0,0,0,0.03)] z-10">
            <view
                class="h-[96rpx] w-[200rpx] bg-[#F5F7FA] rounded-full flex items-center justify-center text-[28rpx] font-bold text-[#333] active:scale-95 transition-transform"
                @click="showExample = true">
                <u-icon name="play-circle" size="32" class="mr-1"></u-icon>
                拍摄教程
            </view>
            <view
                class="flex-1 h-[96rpx] text-white flex items-center justify-center rounded-full font-bold text-[30rpx] shadow-lg active:scale-[0.98] transition-all"
                :class="[isCreate ? 'bg-[#1A1A1A] shadow-[#000000]/20' : 'bg-[#E1E4E8] text-[#999] shadow-[none]']"
                @click="handleCreateAnchor">
                开始克隆 <text class="text-[24rpx] font-normal ml-1 opacity-80"> (消耗{{ getToken }}算力)</text>
            </view>
        </view>
    </view>

    <u-popup v-model="showCreateStatus" mode="center" border-radius="48" width="85%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] p-8 flex flex-col items-center">
            <view
                class="rounded-full w-[100rpx] h-[100rpx] flex items-center justify-center mb-6"
                :class="isSuccess ? 'bg-[#1A1A1A]' : 'bg-red-50'">
                <u-icon
                    :name="isSuccess ? 'checkmark' : 'close'"
                    :color="isSuccess ? '#ffffff' : '#FF3C26'"
                    size="40"></u-icon>
            </view>
            <text class="text-[36rpx] font-bold text-[#1A1A1A] mb-2">{{
                isSuccess ? "创建任务成功" : "创建任务失败"
            }}</text>
            <text v-if="!isSuccess" class="text-[#666] text-center text-[28rpx]">{{
                detail.remark || "请检查网络或稍后重试"
            }}</text>

            <view
                class="w-full h-[96rpx] text-white flex items-center justify-center rounded-full bg-[#1A1A1A] mt-8 text-[30rpx] font-bold active:opacity-90"
                @click="handleConfirm">
                确认
            </view>
        </view>
    </u-popup>

    <popup-bottom v-model="showExample" title="拍摄教程" height="85%" @close="showExample = false">
        <template #content>
            <scroll-view scroll-y class="h-full bg-[#F5F7FA]">
                <view class="p-4 pb-8">
                    <!-- 教程内容样式优化 -->
                    <view class="bg-white p-4 rounded-[24rpx] mb-4 overflow-hidden">
                        <view class="flex items-center gap-x-2 mb-4">
                            <view class="w-1 h-4 bg-[#1A1A1A] rounded-full"></view>
                            <text class="text-[30rpx] font-bold text-[#1A1A1A]">视频教程</text>
                        </view>
                        <view class="h-[384rpx] rounded-[24rpx] overflow-hidden relative bg-black">
                            <video-player
                                :play-icon-size="88"
                                :poster="`${config.baseUrl}static/images/dh_example_bg2.png`"
                                :video-url="`${config.baseUrl}static/videos/dh_example2.mp4`"></video-player>
                        </view>
                    </view>

                    <view class="grid grid-cols-1 gap-y-4">
                        <view class="bg-white p-4 rounded-[24rpx]">
                            <view class="flex items-center gap-x-2 mb-4">
                                <view class="w-1 h-4 bg-[#1A1A1A] rounded-full"></view>
                                <text class="text-[30rpx] font-bold text-[#1A1A1A]">拍摄要求</text>
                            </view>
                            <image
                                class="w-full rounded-[16rpx]"
                                mode="widthFix"
                                src="@/ai_modules/digital_human/static/images/common/video_upload_temp.png"></image>
                        </view>

                        <view class="bg-white p-4 rounded-[24rpx]">
                            <view class="flex items-center gap-x-2 mb-4">
                                <view class="w-1 h-4 bg-[#FF3C26] rounded-full"></view>
                                <text class="text-[30rpx] font-bold text-[#1A1A1A]">错误示例</text>
                            </view>
                            <view class="grid grid-cols-2 gap-3">
                                <view class="bg-[#F8F9FB] p-3 rounded-[16rpx] flex flex-col items-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error1.png"
                                        class="w-[120rpx] h-[120rpx] mb-2"></image>
                                    <text class="text-[#666] text-[24rpx]">遮挡面部</text>
                                </view>
                                <view class="bg-[#F8F9FB] p-3 rounded-[16rpx] flex flex-col items-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error2.png"
                                        class="w-[120rpx] h-[120rpx] mb-2"></image>
                                    <text class="text-[#666] text-[24rpx]">人脸出框</text>
                                </view>
                                <view class="bg-[#F8F9FB] p-3 rounded-[16rpx] flex flex-col items-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error3.png"
                                        class="w-[120rpx] h-[120rpx] mb-2"></image>
                                    <text class="text-[#666] text-[24rpx]">侧脸拍摄</text>
                                </view>
                                <view class="bg-[#F8F9FB] p-3 rounded-[16rpx] flex flex-col items-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/example_error4.png"
                                        class="w-[120rpx] h-[120rpx] mb-2"></image>
                                    <text class="text-[#666] text-[24rpx]">多人出镜</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>

    <u-popup v-model="showAuthHelp" mode="center" border-radius="20" width="85%">
        <view class="bg-white px-[54rpx] py-[44rpx]">
            <view class="text-[30rpx] font-bold"> AI授权是什么？ </view>
            <view class="mt-[32rpx] text-[#000000]/70 font-bold leading-6">
                启用后，无需自行录制授权视频，系统将自动使用您已上传的训练视频生成一段带口型同步的授权声明视频。
            </view>
            <view class="mt-[32rpx] text-[#000000]/70 font-bold leading-6">
                该功能按次收费，每次生成会消耗对应算力/金额。 建议在确认训练视频无误后再使用，可减少重复扣费。
            </view>
            <view
                class="mt-[70rpx] w-[320rpx] h-[90rpx] mx-auto bg-[#F3F3F3] rounded-[20rpx] text-center leading-[90rpx] text-[30rpx] font-bold"
                @click="showAuthHelp = false">
                我已知晓
            </view>
        </view>
    </u-popup>

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
import { batchCloneAnchor } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
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
const authIndex = ref(0);
const showAuthHelp = ref(false);

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
    const token3 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR)?.score;
    const token4 = userStore.getTokenByScene(TokensSceneEnum.AI_SHANJIAN_AUTHORIZED_VIDEO)?.score;
    return (
        parseFloat(token1) + parseFloat(token2) + parseFloat(token3) + (authIndex.value === 1 ? parseFloat(token4) : 0)
    );
});

const isCreate = computed(() => {
    return anchorData.url && (authIndex.value !== 0 || authData.url);
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
        uni.$u.toast("请上传形象视频");
        return;
    } else if (authIndex.value === 0 && !authData.url) {
        uni.$u.toast("请上传授权视频");
        return;
    }

    uni.showLoading({
        title: "创建形象中...",
        mask: true,
    });

    try {
        await batchCloneAnchor({
            name: anchorData.name,
            width: anchorData.width,
            height: anchorData.height,
            anchor_url: anchorData.url,
            authorized_url: authIndex.value === 0 ? authData.url : "",
            pic: anchorData.pic,
            authorized_pic: authIndex.value === 0 ? authData.pic : "",
            ai_type: authIndex.value,
        });
        uni.hideLoading();
        showCreateStatus.value = true;
        isSuccess.value = true;
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
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}

.upload-text {
    background: linear-gradient(90deg, rgba(71, 213, 159, 1) 0%, rgba(55, 204, 237, 1) 100%);
    font-weight: bold;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    font-size: 24rpx;
    color: #888;
    line-height: 1.4;
}

.info-dot {
    width: 8rpx;
    height: 8rpx;
    border-radius: 50%;
    background-color: #ccc;
    margin-top: 10rpx;
    flex-shrink: 0;
}
</style>
