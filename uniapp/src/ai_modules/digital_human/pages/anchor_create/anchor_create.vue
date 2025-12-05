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
                                <text class="font-bold">数字人形象</text>
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
                                <text class="upload-text">点击上传视频</text>
                                <view class="mt-[48rpx] flex flex-col gap-y-2">
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]"
                                            >视频长度为<text class="text-[#7E8085]"
                                                >{{ commonUploadLimit.videoMinDuration }}-{{
                                                    commonUploadLimit.videoMaxDuration
                                                }}秒</text
                                            ></view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]"
                                            >视频大小建议在<text class="text-[#7E8085]"
                                                >{{ commonUploadLimit.size }}MB</text
                                            >以内，上传速度更快</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]"
                                            >保证声音清晰可听，尽量避免嘈杂背景</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]">只支持H.264编码</view>
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
                                <text class="font-bold">授权视频</text>
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
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]"
                                            >视频长度为小于<text>两分钟</text></view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]"
                                            >保证声音清晰可听，尽量避免嘈杂背景</view
                                        >
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]">确保本人出镜授权</view>
                                    </view>
                                    <view class="flex items-center gap-x-2">
                                        <view class="bg-[#ACADB0] rounded-full w-[8rpx] h-[8rpx]"></view>
                                        <view class="text-[#ACADB0] text-[24rpx]">只支持H.264编码</view>
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
        <view class="flex-shrink-0 px-4 pb-6">
            <view
                class="h-[100rpx] text-white flex items-center justify-center rounded-[8rpx]"
                :class="[isCreate ? 'bg-black' : 'bg-[#787878CC]']"
                @click="handleCreateAnchor">
                提交克隆（消耗{{ getToken }}算力）
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
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { getVideoTranscodeResult, videoTranscode } from "@/api/app";
import { createAnchor, createShanjianAnchor, getShanjianAnchorDetail } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum, ListenerTypeEnum, ModeTypeEnum } from "@/ai_modules/digital_human/enums";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { useUpload, commonUploadLimit } from "@/ai_modules/digital_human/hooks/useUpload";
import { requestAuthorization } from "@/utils/file";
import usePolling from "@/hooks/usePolling";
import { TokensSceneEnum } from "@/enums/appEnums";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const appStore = useAppStore();

const isOssTranscode = computed(() => appStore.config.is_oss_transcode);

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

const pageSource = ref<DigitalHumanModelVersionEnum>();

const isSuccess = ref(false);

// 支持的上传格式
const SUPPORTED_EXTENSIONS = ["mp4", "mov"];

// 充值弹窗
const rechargePopupRef = shallowRef();

// 获取消耗的算力
const getToken = computed(() => {
    const token1 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN)?.score;
    const token2 = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_CHANJING)?.score;
    return parseFloat(token1) + parseFloat(token2);
});

// 是否是chanjing
const isCJ = computed(() => pageSource.value == DigitalHumanModelVersionEnum.CHANJING);

const isCreate = computed(() => {
    return !isCJ.value ? authData.url && anchorData.url : anchorData.url;
});

const handleUploadAnchorVideo = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/video_upload/video_upload",
        params: {
            type: ModeTypeEnum.ANCHOR,
        },
    });
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
        duration: [1, 120],
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
    // chanjing形象创建
    const chanjingCreateAnchor = () => {
        return new Promise(async (resolve: any, reject: any) => {
            await createAnchor({
                name: anchorData.name,
                url: anchorData.url,
                pic: anchorData.pic,
                model_version: DigitalHumanModelVersionEnum.CHANJING,
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
        const [res1, res2]: any = await Promise.allSettled([shanjianCreateAnchor(), chanjingCreateAnchor()]);
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
        } else if (isCJ.value) {
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
        uni.$emit("confirm", {
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
    uni.$on("confirm", (result: any) => {
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
    uni.$off("confirm");
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
