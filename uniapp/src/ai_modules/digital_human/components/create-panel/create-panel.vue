<template>
    <u-popup
        mode="bottom"
        v-model="show"
        width="100%"
        border-radius="16"
        :mask="false"
        :custom-style="{
            background: 'transparent',
        }"
        :z-index="777"
        @close="close">
        <view class="mx-[20rpx] bg-white p-6 rounded-lg mb-[150rpx] shadow-[0rpx_10rpx_20rpx_2rpx_rgba(0,0,0,0.15)]">
            <view class="">
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-2">
                        <image
                            src="@/ai_modules/digital_human/static/images/common/shandian.png"
                            class="w-6 h-6"></image>
                        <text class="text-[#545454] text-lg"> 算力余额： </text>
                    </view>
                    <view class="text-[#575757]">
                        {{ userTokens }}
                    </view>
                </view>
                <view class="my-4">
                    <u-line />
                </view>
                <view class="flex items-center justify-between">
                    <view class="flex items-center gap-2">
                        <image
                            src="@/ai_modules/digital_human/static/images/common/shichang.png"
                            class="w-6 h-6"></image>
                        <text class="text-[#545454] text-lg"> 预估时长： </text>
                    </view>
                    <view class="text-[#575757]">
                        {{ formatAudioTime(audioDuration) }}
                    </view>
                </view>
                <view class="flex justify-between mt-4">
                    <view>
                        <view class="flex items-center gap-2">
                            <image src="@/ai_modules/digital_human/static/images/common/shuidi.png" class="w-6 h-6" />
                            <text class="text-[#545454] leading-[0]"> 算力消耗：</text>
                        </view>
                    </view>
                    <view class="flex flex-col gap-2 text-right">
                        <view class="text-[#575757]" v-if="costTokens.video_cost">
                            <view>
                                <text>(视频合成)：</text>
                                <text>
                                    {{ costTokens.video_cost * audioDuration }}
                                </text>
                            </view>
                            <view>
                                <text class="text-[20rpx]">
                                    {{ costTokens.video_cost }}
                                    {{ costTokens.video_unit }}
                                </text>
                            </view>
                        </view>
                        <view class="text-[#575757]" v-if="costTokens.figure_cost">
                            <view>
                                <text>(形象克隆)：</text>
                                <text>{{ costTokens.figure_cost }}</text>
                            </view>
                            <view>
                                <text class="text-[20rpx]">
                                    {{ costTokens.figure_cost }}
                                    {{ costTokens.figure_unit }}
                                </text>
                            </view>
                        </view>
                        <view class="text-[#575757]" v-if="costTokens.voice_cost">
                            <view>
                                <text>(音色克隆)：</text>
                                <text>{{ costTokens.voice_cost }}</text>
                            </view>
                            <view>
                                <text class="text-[20rpx]">
                                    {{ costTokens.voice_cost }}
                                    {{ costTokens.voice_unit }}
                                </text>
                            </view>
                        </view>
                        <view class="text-[#575757]" v-if="costTokens.audio_cost">
                            <view>
                                <text>(音频合成)：</text>
                                <text>{{ costTokens.audio_cost * audioDuration }}</text>
                            </view>
                            <view>
                                <text class="text-[20rpx]">
                                    {{ costTokens.audio_cost }}
                                    {{ costTokens.audio_unit }}
                                </text>
                            </view>
                        </view>
                        <view class="text-lg text-[#575757]"> 预计：{{ getConstTotal }} </view>
                    </view>
                </view>
            </view>
            <view class="my-4">
                <u-line />
            </view>
            <view>
                <u-button
                    type="primary"
                    shape="circle"
                    :custom-style="{
                        height: '90rpx',
                        boxShadow: ' 0px 3px 12px 0px rgba(0, 0, 0, 0.12)',
                        fontSize: '26rpx',
                    }"
                    @click="handleConfirm()">
                    <view class="flex items-center gap-2">
                        <image src="@/ai_modules/digital_human/static/icons/video.svg" class="w-6 h-6"></image>
                        <text class="text-white text-xl font-bold"> 开始生成视频 </text>
                    </view>
                </u-button>
                <view class="text-center text-primary mt-2" @click="close"> 取消 </view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { formatAudioTime } from "@/utils/util";
import { TokensSceneEnum } from "@/enums/appEnums";
import { CreateTypeEnum, DigitalHumanModelVersionEnum } from "@/ai_modules/digital_human/enums";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const props = withDefaults(
    defineProps<{
        formData: Record<string, any>;
    }>(),
    { formData: () => ({}) }
);

const emit = defineEmits(["success", "close", "recharge"]);

// Refs and computed
const show = ref(false);
const audioDuration = ref(0);
const costTokens = ref<Record<string, any>>({});

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

const getConstTotal = computed(() => {
    const { video_cost, figure_cost, voice_cost, audio_cost } = costTokens.value;
    return video_cost * audioDuration.value + figure_cost + voice_cost + audio_cost * audioDuration.value;
});

// Methods
const getCostRules = async () => {
    const { anchor_id, model_version, voice_id, voice_type } = props.formData;
    const _costTokens = reactive({
        video_cost: 0,
        figure_cost: 0,
        voice_cost: 0,
        audio_cost: 0,
        video_unit: "",
        voice_unit: "",
        figure_unit: "",
        audio_unit: "",
    });

    const setCosts = (videoKey: string, voiceKey: string, audioKey: string, figureKey?: string) => {
        _costTokens.video_cost = parseFloat(getTokenByScene(videoKey).score || 0);
        _costTokens.video_unit = getTokenByScene(videoKey).unit;
        _costTokens.voice_cost =
            voice_type == 1 && voice_id == -1 ? parseFloat(getTokenByScene(voiceKey).score || 0) : 0;
        _costTokens.voice_unit = getTokenByScene(voiceKey).unit;
        _costTokens.audio_cost = parseFloat(getTokenByScene(audioKey).score || 0);
        _costTokens.audio_unit = getTokenByScene(audioKey).unit;
        if (figureKey) {
            _costTokens.figure_cost = parseFloat(getTokenByScene(figureKey).score || 0);
            _costTokens.figure_unit = getTokenByScene(figureKey).unit;
        }
    };

    const sceneKeys = {
        pro: {
            video: TokensSceneEnum.HUMAN_VIDEO_PRO,
            voice: TokensSceneEnum.HUMAN_VOICE_PRO,
            audio: TokensSceneEnum.HUMAN_AUDIO_PRO,
            avatar: TokensSceneEnum.HUMAN_AVATAR_PRO,
        },
        normal: {
            video: TokensSceneEnum.HUMAN_VIDEO,
            voice: TokensSceneEnum.HUMAN_VOICE,
            audio: TokensSceneEnum.HUMAN_AUDIO,
            avatar: TokensSceneEnum.HUMAN_AVATAR,
        },
        advanced: {
            video: TokensSceneEnum.HUMAN_VIDEO_ADVANCED,
            voice: TokensSceneEnum.HUMAN_VOICE_ADVANCED,
            audio: TokensSceneEnum.HUMAN_AUDIO_ADVANCED,
            avatar: TokensSceneEnum.HUMAN_AVATAR_ADVANCED,
        },
        elite: {
            video: TokensSceneEnum.HUMAN_VIDEO_ELITE,
            voice: TokensSceneEnum.HUMAN_VOICE_ELITE,
            audio: TokensSceneEnum.HUMAN_AUDIO_ELITE,
            avatar: TokensSceneEnum.HUMAN_AVATAR_ELITE,
        },
    };

    const keys = (() => {
        switch (parseInt(model_version)) {
            case DigitalHumanModelVersionEnum.SUPER:
                return sceneKeys.pro;
            case DigitalHumanModelVersionEnum.ADVANCED:
                return sceneKeys.advanced;
            case DigitalHumanModelVersionEnum.ELITE:
                return sceneKeys.elite;
            default:
                return sceneKeys.normal;
        }
    })();
    setCosts(keys.video, keys.voice, keys.audio, !anchor_id ? keys.avatar : undefined);
    costTokens.value = _costTokens;
};

const getAudioDuration = (msg: string, duration: number) => {
    audioDuration.value = duration || Math.floor(msg.length / 3);
};

const handleConfirm = () => {
    initData();
    if (userTokens.value < parseFloat(getConstTotal.value || 0)) {
        uni.$u.toast("算力不足，请充值！");
        emit("recharge");
        show.value = false;
        return;
    }
    emit("success");
};

const initData = () => {
    const { msg, audio_duration, audio_type } = props.formData;
    switch (audio_type) {
        case CreateTypeEnum.TEXT:
            getAudioDuration(msg, audio_duration);
            break;
        case CreateTypeEnum.AUDIO:
            audioDuration.value = audio_duration;
            break;
    }
    getCostRules();
};

const open = () => {
    // show.value = true;
    initData();
};

const close = () => {
    show.value = false;
    emit("close");
};

defineExpose({ open, close, confirm: handleConfirm });
</script>

<style scoped></style>
