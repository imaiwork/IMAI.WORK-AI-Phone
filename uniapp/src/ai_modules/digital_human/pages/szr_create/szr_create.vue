<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="pb-[32rpx] h-full flex flex-col">
                    <view class="bg-white px-[44rpx]" :class="{
                        'grow min-h-0 flex flex-col': anchorLists.length == 0,
                    }">
                        <view class="h-[100rpx] flex items-center justify-between">
                            <view class="font-bold text-[32rpx]">选择数字人</view>
                            <view class="flex items-center justify-center gap-x-2 p-1" @click="showChooseAnchor = true">
                                <image src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[28rpx] h-[28rpx]"></image>
                                <text class="text-[#000000cc] text-[26rpx]">选择形象</text>
                            </view>
                        </view>
                        <view class="py-4" :class="[
                            anchorLists.length == 0
                                ? 'grow min-h-0 flex flex-col items-center justify-center'
                                : 'border-[0rpx] border-t-[1rpx] border-b-[1rpx] border-solid border-[#E5E5E5]',
                        ]">
                            <view v-if="anchorLists.length > 0">
                                <scroll-view scroll-x>
                                    <view class="flex gap-x-2 whitespace-nowrap">
                                        <view v-for="(item, index) in anchorLists"
                                            class="flex-shrink-0 w-[164rpx] h-[224rpx] rounded-[24rpx] relative overflow-hidden card-gradient"
                                            :key="item.anchor_id || index" @click="chooseAnchor(index)">
                                            <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                                            <view class="absolute top-2 right-2 z-[223]"
                                                v-if="currAnchorIndex == index">
                                                <image src="/static/images/icons/success.svg"
                                                    class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                            <view class="absolute bottom-1 right-0 z-[224]"
                                                @click.stop="previewVideo(item.result_url)">
                                                <image src="@/ai_modules/digital_human/static/icons/play.svg"
                                                    class="w-[48rpx] h-[48rpx]"></image>
                                            </view>
                                            <view v-if="getAnchorStatus(item.status, item.source_type) == 0"
                                                class="z-[222] absolute top-0 left-0 w-full h-full flex items-center justify-center bg-[#00000080]">
                                                <view
                                                    class="bg-primary text-xs font-bold text-white rounded-[10rpx] px-2 py-1">
                                                    克隆中</view>
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view class="h-[468rpx] flex flex-col items-center justify-center" v-else>
                                <image src="@/ai_modules/digital_human/static/images/common/avatar.png"
                                    class="w-[120rpx] h-[136rpx] mx-auto"></image>
                                <view class="text-[26rpx] text-[#828282] mt-[32rpx] text-center">
                                    您还没有数字人，快去定制一个吧~
                                </view>
                                <view
                                    class="mt-[28rpx] mx-auto w-[202rpx] h-[68rpx] flex items-center justify-center rounded-[12rpx] text-white bg-black"
                                    @click="openModel()">
                                    定制数字人
                                </view>
                            </view>
                        </view>
                        <view class="mb-2">
                            <view class="flex items-center justify-between h-[80rpx] gap-x-2">
                                <text class="font-bold flex-shrink-0">选择模型</text>
                                <view class="flex items-center gap-x-2" @click="openChooseModel()">
                                    <text class="text-xs font-bold line-clamp-1"
                                        :class="[formData.model_version ? 'text-primary' : 'text-[#0000004d]']">{{
                                            modelVersionMap[formData.model_version] || "请选择" }}</text>
                                    <u-icon name="arrow-right" color="#00000020" size="22"></u-icon>
                                </view>
                            </view>
                            <view class="flex items-center justify-between h-[80rpx] gap-x-2">
                                <text class="font-bold flex-shrink-0">选择声音</text>
                                <view class="flex items-center gap-x-2" @click="openChooseTone()">
                                    <text v-if="isOriginalTone"
                                        class="text-[20rpx] text-primary bg-[#DDF3FF] rounded font-bold p-1">
                                        视频原音
                                    </text>
                                    <text v-else class="text-xs font-bold line-clamp-1"
                                        :class="[formData.voice_name ? 'text-primary' : 'text-[#0000004d]']">{{
                                            formData.voice_name || "无" }}</text>
                                    <u-icon name="arrow-right" color="#00000020" size="22"></u-icon>
                                </view>
                            </view>
                            <view v-if="clipConfig.is_open">
                                <view class="flex items-center justify-between h-[80rpx] gap-x-2">
                                    <text class="font-bold flex-shrink-0">Ai智剪</text>
                                    <view class="flex items-center gap-x-1">
                                        <u-switch v-model="formData.automatic_clip" size="36" :active-value="1"
                                            :inactive-value="0"></u-switch>
                                    </view>
                                </view>
                                <view class="flex items-center justify-between h-[80rpx] gap-x-2" v-if="false">
                                    <text class="text-[#333333] flex-shrink-0">背景音乐</text>
                                    <navigator class="flex items-center gap-x-1"
                                        url="/ai_modules/digital_human/pages/audio_choose/audio_choose"
                                        hover-class="none">
                                        <text class="text-[#00000080] line-clamp-1">{{
                                            formData.voice_name || "选择背景音乐"
                                        }}</text>
                                        <u-icon name="arrow-right" color="#00000020" size="22"></u-icon>
                                    </navigator>
                                </view>
                                <view class="flex items-center justify-between h-[80rpx] gap-x-2" v-if="false">
                                    <text class="text-[#333333] flex-shrink-0">剪辑风格选择</text>
                                    <navigator class="flex items-center gap-x-1"
                                        url="/ai_modules/digital_human/pages/styles_choose/styles_choose"
                                        hover-class="none">
                                        <text class="text-[#00000080] line-clamp-1">{{
                                            formData.voice_name || "选择剪辑风格"
                                        }}</text>
                                        <u-icon name="arrow-right" color="#00000020" size="22"></u-icon>
                                    </navigator>
                                </view>
                            </view>
                        </view>
                    </view>
                    <!-- 文案编辑区域 -->
                    <view class="bg-white px-[44rpx] pb-[40rpx] mt-[16rpx]">
                        <view class="flex items-center -mx-[24rpx] gap-x-2 py-[8rpx]">
                            <view class="util-item" @click="randomCopywriter()">
                                <view class="w-[30rpx] h-[30rpx]">
                                    <image src="@/ai_modules/digital_human/static/icons/random.svg"
                                        class="w-full h-full"></image>
                                </view>
                                <text class="text-xs font-bold">随机文案</text>
                            </view>
                            <view class="h-[20rpx] w-[1rpx] bg-[#EDEDED]"></view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/ai_copywriter/ai_copywriter?limit=${textLimit}`"
                                hover-class="none">
                                <view class="util-item">
                                    <view class="w-[30rpx] h-[30rpx]">
                                        <image src="@/ai_modules/digital_human/static/icons/copywriter.svg"
                                            class="w-full h-full"></image>
                                    </view>
                                    <text class="text-xs font-bold">智能文案</text>
                                </view>
                            </navigator>
                            <view class="h-[20rpx] w-[1rpx] bg-[#EDEDED]"></view>
                            <view class="util-item" @click="showAudioType = true">
                                <view class="w-[30rpx] h-[30rpx]">
                                    <image src="@/ai_modules/digital_human/static/icons/sound.svg"
                                        class="w-full h-full"></image>
                                </view>
                                <text class="text-xs font-bold">使用音频</text>
                            </view>
                        </view>
                        <navigator v-if="formData.audio_type == CreateTypeEnum.TEXT"
                            :url="`/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter?limit=${textLimit}&content=${formData.msg}`"
                            hover-class="none"
                            class="border-[1rpx] border-solid border-[#E5E5E5] border-l-0 border-r-0 border-b-0 py-[32rpx] relative">
                            <view class="min-h-[364rpx]" :class="formData.msg ? 'text-black' : 'text-[#00000033]'">
                                {{ formData.msg || "请输入您的文案..." }}
                            </view>
                            <view class="text-right text-[22rpx] text-[#999] mt-2">
                                {{ formData.msg.length }}/{{ textLimit }}
                            </view>
                        </navigator>
                        <view class="bg-[#F6F7FA] rounded-[20rpx] p-[32rpx] relative" v-else>
                            <view
                                class="absolute top-[24rpx] right-[20rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#E9EAED] flex items-center justify-center"
                                @click="clearAudioData()">
                                <u-icon name="close" size="20"></u-icon>
                            </view>
                            <template v-if="audioLoading">
                                <view class="font-bold text-[30rpx]">录制的音频</view>
                                <view class="flex flex-col items-center justify-center mt-4">
                                    <view class="rotate">
                                        <u-icon name="reload" color="#ACACAF" size="40"></u-icon>
                                    </view>
                                    <text class="mt-2 text-[30rpx] font-bold">正在提取中...</text>
                                    <text class="text-xs font-bold text-[#000000]/30">请勿熄屏或切换应用</text>
                                </view>
                            </template>
                            <template v-else>
                                <view class="flex items-center gap-x-2">
                                    <view @click="handlePlayAudio">
                                        <u-icon :name="isPlaying ? 'pause-circle' : 'play-circle'" color="#0065fb"
                                            size="50"></u-icon>
                                    </view>
                                    <text class="font-bold text-[30rpx]">录制的音频</text>
                                    <text class="text-[#000000]50">{{ formatAudioTime(currDuration) }}/{{
                                        formatAudioTime(formData.audio_duration)
                                        }}</text>
                                </view>
                                <navigator class="mt-[40rpx] pb-3"
                                    :url="`/ai_modules/digital_human/pages/szr_copywriter/szr_copywriter?limit=${textLimit}&content=${formData.msg}`"
                                    hover-class="none">
                                    {{ formData.msg }}
                                </navigator>
                            </template>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white px-4 pt-2 pb-[64rpx] flex items-center justify-between gap-x-[40rpx]">
            <view>
                <view class="flex flex-col items-center gap-y-2" @click="openModelRule()">
                    <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]"></image>
                    <text class="text-[#8C8C8C] text-[22rpx]">算力消耗</text>
                </view>
            </view>
            <view class="flex-1">
                <view class="rounded-full h-[100rpx] bg-black text-white font-bold flex items-center justify-center"
                    @click="startCreate()">
                    生成视频
                </view>
            </view>
        </view>
    </view>
    <!-- 弹窗组件 -->
    <video-preview v-model="showVideoPreview" title="视频预览" :video-url="previewVideoUrl"
        @confirm="showVideoPreview = false" />
    <select-anchor v-model="showChooseAnchor" @confirm="handleChooseAnchor" />
    <choose-tone v-if="showChooseTone" v-model="showChooseTone" :model-version="formData.model_version"
        :active-tone="formData.voice_id" :show-original-tone="showOriginalTone" @confirm="handleChooseTone" />
    <choose-model v-model="showChooseModel" :filter="[DigitalHumanModelVersionEnum.SHANJIAN]"
        @confirm="handleChooseModel" />
    <model-rule v-model="showModelRule" :model-version="formData.model_version" />
    <create-panel ref="createPanelRef" :formData="formData" @success="confirmCreate" @recharge="recharge" />
    <agreement v-model="showAgreement" @agree="agreeCreate" @close="showAgreement = false" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <recorder-control v-if="showRecorder" v-model="showRecorder" ref="recorderRef" @close="showRecorder = false"
        @success="recorderSuccess" />
    <choose-audio-type v-model="showAudioType" @recorder="openRecorder" @file="uploadAndProcessFiles('file')" />
    <create-success-pop v-if="showCreateSuccess" v-model="showCreateSuccess" title="数字人创作成功" desc="您可以立即去我的作品中查看" center
        to-text="取消" @to="goHome" @seek="toRecord" />
</template>

<script setup lang="ts">
import Cache from "@/utils/cache";
import WechatOA from "@/utils/wechat";
import { createTask, getPublicAnchorList } from "@/api/digital_human";
import { getClipConfig } from "@/api/app";
import { lpSceneSpeechToText } from "@/api/ladder_player";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { ModeTypeEnum, CreateTypeEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { ClipStyleEnum } from "@/ai_modules/digital_human/enums";
import useUpload from "@/hooks/useUpload";
import { useAudio } from "@/hooks/useAudio";
import usePolling from "@/hooks/usePolling";
import { formatAudioTime } from "@/utils/util";
import { createVideoCopywriter } from "@/ai_modules/digital_human/config/copywriter";
import SelectAnchor from "@/ai_modules/digital_human/components/choose-anchor/choose-anchor.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import ChooseModel from "@/ai_modules/digital_human/components/choose-model/choose-model.vue";
import ModelRule from "@/ai_modules/digital_human/components/model-rule/model-rule.vue";
import Agreement from "@/ai_modules/digital_human/components/agreement/agreement.vue";
import CreatePanel from "@/ai_modules/digital_human/components/create-panel/create-panel.vue";
import RecorderControl from "@/ai_modules/digital_human/components/recorder-control/recorder-control.vue";
import ChooseAudioType from "@/ai_modules/digital_human/components/choose-audio-type/choose-audio-type.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";

// 定义锚点数据接口
interface AnchorItem {
    name: string;
    model_version: DigitalHumanModelVersionEnum & 0;
    anchor_id: string;
    anchor_ids: {
        chanjing_anchor_id: string;
        weiju_anchor_id: string;
    };
    result_url: string;
    pic: string;
    width: number;
    height: number;
    status: number;
    source_type: string;
    extra_info: {
        width: number;
        height: number;
    };
}

const { on } = useEventBusManager();

const appStore = useAppStore();
const userStore = useUserStore();

const loading = ref(true);

// 常量定义
const DH_CREATE_AGREEMENT_KEY = "create_agreement";

// 表单数据初始化
const formData = reactive<any>({
    name: "",
    pic: "",
    width: 0,
    height: 0,
    anchor_id: "",
    anchor_name: "",
    gender: "male",
    model_version: "" as unknown as DigitalHumanModelVersionEnum,
    audio_type: CreateTypeEnum.TEXT,
    audio_url: "",
    voice_id: "-1",
    voice_type: 1,
    voice_name: "",
    msg: "",
    video_url: "",
    automatic_clip: 0,
    clip_type: ClipStyleEnum.AI_RECOMMEND,
    music_url: "",
    music_name: "",
    music_type: 1,
});

// 状态变量
const anchorLists = ref<AnchorItem[]>([]);
const currAnchorIndex = ref(-1);
const showChooseAnchor = ref(false);
const showChooseModel = ref(false);
const previewVideoUrl = ref<string>("");
const showVideoPreview = ref(false);
const showChooseTone = ref(false);
const currCopywriterIndex = ref(-1);
const showModelRule = ref(false);
const showAgreement = ref(false);
const showAudioType = ref(false);
const audioLoading = ref(false);
const showRecorder = ref(false);
const showCreateSuccess = ref(false);
const createPanelRef = shallowRef<InstanceType<typeof CreatePanel>>();
const rechargePopupRef = ref();
const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();
// 计算属性
const textLimit = computed(() => {
    const limits: Record<DigitalHumanModelVersionEnum, number> | any = {
        [DigitalHumanModelVersionEnum.STANDARD]: 150,
        [DigitalHumanModelVersionEnum.SUPER]: 300,
        [DigitalHumanModelVersionEnum.ADVANCED]: 1000,
        [DigitalHumanModelVersionEnum.ELITE]: 1000,
        [DigitalHumanModelVersionEnum.CHANJING]: 4000,
    };
    //@ts-ignore
    return limits[formData.model_version] || 150;
});

const modelChannel = computed(() => appStore.getDigitalHumanConfig?.channel || []);

const isPublicAnchor = computed(() => {
    if (anchorLists.value.length === 0) return false;
    const { model_version } = anchorLists.value[currAnchorIndex.value];
    return model_version === 0;
});

const modelVersionMap = computed(() => {
    return modelChannel.value.reduce((acc: Record<string, any>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

const canCreate = computed(() => {
    return (formData.voice_type == 1 || !!formData.voice_id) && formData.msg?.length > 0;
});

const isOriginalTone = computed(() => {
    return formData.voice_id == -1;
});

const showOriginalTone = computed(() => {
    return formData.model_version == DigitalHumanModelVersionEnum.CHANJING || isOriginalTone.value;
});

const clipConfig = reactive({
    is_open: false,
});
const getClipConfigData = async () => {
    const { code } = await getClipConfig();
    clipConfig.is_open = code == 10000;
};

const openChooseModel = () => {
    if (isPublicAnchor.value) {
        showChooseModel.value = true;
    } else {
        uni.$u.toast("该形象无法更改模型哦~");
    }
};

const getAnchorStatus = (status: number, source_type: string) => {
    const anchorStatusMapping: Record<string, any> = {
        human_anchor: {
            1: 1,
            2: 2,
            default: 0,
        },
        public_anchor: {
            1: 0,
            2: 1,
            3: 2,
            default: 0,
        },
    };
    return anchorStatusMapping[source_type][status] || anchorStatusMapping[source_type]?.["default"];
};
// 形象相关方法
const chooseAnchor = (index: number) => {
    const { status, source_type, model_version } = anchorLists.value[index];
    const anchorStatus = getAnchorStatus(status, source_type);
    if (anchorStatus != 1) {
        uni.$u.toast("该形象正在克隆中，请稍后再试");
        return
    }

    if (formData.model_version !== model_version) {
        formData.voice_id = "-1";
        formData.voice_name = "";
    }
    if (model_version === 0) {
        formData.model_version = DigitalHumanModelVersionEnum.CHANJING;
    } else {
        formData.model_version = model_version;
    }
    currAnchorIndex.value = index;
};

const handleChooseAnchor = (data: AnchorItem) => {
    // 检查是否已存在相同anchor_id的项目
    const exists = anchorLists.value.findIndex((item) => item.anchor_id === data.anchor_id);
    if (exists === -1) {
        anchorLists.value = [data, ...anchorLists.value];
        chooseAnchor(0);
    } else {
        chooseAnchor(exists);
    }
    showChooseAnchor.value = false;
};

// 模型相关方法
const openModel = () => {
    uni.$u.route({
        url: `/ai_modules/digital_human/pages/anchor_create/anchor_create?source=${DigitalHumanModelVersionEnum.CHANJING}&type=${ModeTypeEnum.ANCHOR}`,
    });
};

const handleChooseModel = (id: string) => {
    if (formData.model_version == id) return;
    formData.model_version = id;
    formData.voice_id = "-1";
    formData.voice_name = "";
    formData.voice_type = 1;

};

// 视频预览相关方法
const previewVideo = (url: string) => {
    if (!url) return;
    showVideoPreview.value = true;
    previewVideoUrl.value = url;
};

// 音色相关方法
const openChooseTone = () => {
    showChooseTone.value = true;
};

const handleChooseTone = (data: any) => {
    const { voice_id, name, builtin } = data;
    if (!data.voice_id) {
        formData.voice_id = "-1";
        formData.voice_name = "";
        formData.voice_type = 1;
    } else {
        if (builtin === 0) {
            formData.voice_type = 0;
        } else {
            formData.voice_type = 1;
        }
        formData.voice_id = voice_id;
        formData.voice_name = name;
    }
    showChooseTone.value = false;
};

// 文案相关方法
const randomCopywriter = () => {
    if (!createVideoCopywriter.length) return;
    currCopywriterIndex.value = (currCopywriterIndex.value + 1) % createVideoCopywriter.length;
    formData.msg = createVideoCopywriter[currCopywriterIndex.value];
    formData.audio_type = CreateTypeEnum.TEXT;
};

const { uploadAndProcessFiles, showUploadProgress, uploadMaterialList } = useUpload({
    count: 1,
    fileAccept: ["mp3", "wav", "m4a", "MP3", "WAV", "M4A"],
    fileSize: 100,
    onSuccess: async (res: any) => {
        const { url } = res[0];
        formData.audio_type = CreateTypeEnum.AUDIO;
        showAudioType.value = false;
        audioLoading.value = true;
        try {
            const { message, audio_duration } = await lpSceneSpeechToText({
                audio: url,
            });
            formData.msg = message;
            formData.audio_url = url;
            formData.audio_duration = audio_duration;
        } catch (error: any) {
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        } finally {
            audioLoading.value = false;
        }
    },
});

const { play, pause, currentTime: currDuration, isPlaying, destroy } = useAudio();

const handlePlayAudio = () => {
    if (isPlaying.value) {
        pause();
    } else {
        play(formData.audio_url);
    }
};

const openRecorder = async () => {
    showAudioType.value = false;
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = (res: any) => {
    const { link, duration, message } = res;
    formData.msg = message;
    formData.audio_url = link;
    formData.audio_type = CreateTypeEnum.AUDIO;
    formData.audio_duration = duration / 1000;
    showRecorder.value = false;
};

const clearAudioData = () => {
    uni.showModal({
        title: "提示",
        content: "删除该音频后，将无法找回，确认删除？",
        success: (res: any) => {
            if (res.confirm) {
                formData.msg = "";
                formData.audio_url = "";
                formData.audio_type = CreateTypeEnum.TEXT;
                destroy();
            }
        },
    });
};

// 算力规则相关方法
const openModelRule = () => {
    showModelRule.value = true;
};

// 协议相关方法
const agreeCreate = () => {
    Cache.set(DH_CREATE_AGREEMENT_KEY, "1");
    confirmCreate();
};

// 充值相关方法
const recharge = () => {
    rechargePopupRef.value?.open();
};

// 创建视频相关方法
const startCreate = () => {
    if (!canCreate.value) {
        if (!formData.model_version) {
            openModel();
        } else if (formData.voice_type != 1 && !formData.voice_id) {
            uni.$u.toast("请先选择音色");
            openChooseTone();
        } else if (!formData.msg) {
            uni.$u.toast("请先输入视频文案");
        }
        // else if (formData.automatic_clip == 1 && !formData.music_url) {
        //     uni.$u.toast("请先选择音乐");
        //     return;
        // }
        return;
    }
    createPanelRef.value?.confirm();
};
const confirmCreate = async () => {
    const closeAgreement = Cache.get(DH_CREATE_AGREEMENT_KEY);
    if (!closeAgreement) {
        showAgreement.value = true;
        return;
    }
    // 判断文案是否超出限制
    if (formData.msg?.length > textLimit.value) {
        uni.$u.toast("文案超出限制，请重新编辑文案");
        return;
    }

    showAgreement.value = false;
    try {
        uni.showLoading({
            title: "正在生成",
            mask: true,
        });
        const voice_id = formData.voice_id == "-1" ? undefined : formData.voice_id;
        const {
            name,
            width,
            height,
            model_version,
            anchor_id,
            anchor_ids: { chanjing_anchor_id, weiju_anchor_id },
            pic,
            result_url,
        } = anchorLists.value[currAnchorIndex.value];

        let anchorId = anchor_id;
        if (model_version === 0) {
            if (formData.model_version == DigitalHumanModelVersionEnum.CHANJING) {
                anchorId = chanjing_anchor_id;
            }
            if (formData.model_version == DigitalHumanModelVersionEnum.STANDARD) {
                anchorId = weiju_anchor_id;
            }
        }
        await createTask({
            name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "数字人口播",
            width,
            height,
            pic,
            voice_id,
            anchor_id: anchorId,
            msg: formData.msg,
            video_url: result_url,
            anchor_name: name,
            voice_name: formData.voice_name,
            voice_type: formData.voice_type,
            audio_type: formData.audio_type,
            audio_url: formData.audio_url,
            model_version: formData.model_version,
            automatic_clip: formData.automatic_clip,
            clip_type: formData.clip_type,
            music_url: formData.music_url,
            music_type: formData.music_type,
        });
        createPanelRef.value?.close();
        userStore.getUser();
        uni.hideLoading();
        showCreateSuccess.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "生成失败",
            icon: "none",
            duration: 3000,
        });
    }
};

const getModelLists = async () => {
    try {
        const { lists } = await getPublicAnchorList({
            page_size: 10,
            page_no: 1,
            status: 1,
            filter: 2,
        });
        if (lists && lists.length) {
            anchorLists.value = lists;

            if (currAnchorIndex.value === -1) {
                const index = lists.findIndex((item: any) => getAnchorStatus(item.status, item.source_type) == 1);
                if (index != -1) {
                    chooseAnchor(index);
                } else {
                    chooseAnchor(0);
                }
            }

            // 判断是不是有生成中的形象
            const generatingAnchor = lists.find((item: any) => getAnchorStatus(item.status, item.source_type) == 0);

            if (!generatingAnchor) {
                end();
            }
        }
    } finally {
        loading.value = false;
    }
};

const { start, end } = usePolling(getModelLists, {
    time: 3000,
});



const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: {
            source: "1",
            type: 1,
        },
    });
};

const goHome = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/index/index",
        type: "redirect",
    });
};



onShow(() => {
    getClipConfigData();
    getModelLists();
    start();
});

// 生命周期钩子
onLoad(async (options: any) => {
    on("confirm", (result: any) => {
        const { type, data } = result;
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            getModelLists();
        }
        if (type === ListenerTypeEnum.SZR_COPYWRITER) {
            formData.msg = data;
        }
        if (type === ListenerTypeEnum.AI_COPYWRITER) {
            formData.msg = data;
            if (formData.msg?.length > textLimit.value) {
                formData.msg = formData.msg.slice(0, textLimit.value);
            }
            formData.audio_type = CreateTypeEnum.TEXT;
        }
        if (type === ListenerTypeEnum.CHOOSE_STYLES) {
            // formData.styles = data;
        }
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music_url = data.url;
            formData.music_name = data.name;
        }
    });
});

onUnload(() => {
    destroy();
    end()
});
</script>

<style scoped lang="scss">
.util-item {
    @apply flex items-center gap-x-1 py-1 px-2 rounded transition-all duration-300 h-[80rpx];
}

.rotate {
    animation: rotate 1s linear infinite;
}

// 转圈动画
@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
