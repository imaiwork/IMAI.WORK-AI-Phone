<template>
    <ElButton
        class="w-full !rounded-full !h-[50px]"
        type="primary"
        size="large"
        :loading="createIsLock"
        @click="createLockFn">
        确定
    </ElButton>
</template>

<script setup lang="ts">
import { createTask } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { TokensSceneEnum } from "@/enums/appEnums";
import { CreateTypeEnum, DigitalHumanModelVersionEnum } from "../../../_enums";

interface CostTokens {
    video_cost: number;
    figure_cost: number;
    voice_cost: number;
    audio_cost: number;
    video_unit: string;
    voice_unit: string;
    figure_unit: string;
    audio_unit: string;
}

const props = defineProps<{ formData: any }>();

const emit = defineEmits<{
    (e: "error", error: Record<string, any>): void;
    (e: "success"): void;
}>();

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const showNext = ref(false);
const costTokens = ref<Partial<CostTokens>>({});
const audioDuration = ref(0);

const createTaskParams = ref<any>({});

const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

const getConstTotal = computed(() => {
    const { video_cost, figure_cost, voice_cost, audio_cost } = costTokens.value;
    return (
        (video_cost || 0) * audioDuration.value +
        (figure_cost || 0) +
        (voice_cost || 0) +
        (audio_cost || 0) * audioDuration.value
    );
});

const getCostRules = async () => {
    const { anchor_id, msg, model_version, voice_id, voice_type, audio_type, audio_duration } = props.formData;
    const _costTokens: CostTokens = {
        video_cost: 0,
        figure_cost: 0,
        voice_cost: 0,
        audio_cost: 0,
        video_unit: "",
        voice_unit: "",
        figure_unit: "",
        audio_unit: "",
    };
    getAudioDuration(msg, audio_duration);

    const setCosts = (videoKey: string, voiceKey: string, audioKey: string, figureKey?: string) => {
        _costTokens.video_cost = parseFloat(getTokenByScene(videoKey).score || 0);
        _costTokens.video_unit = getTokenByScene(videoKey).unit;
        _costTokens.voice_cost =
            CreateTypeEnum.AUDIO == audio_type || (voice_type == 1 && voice_id == -1)
                ? parseFloat(getTokenByScene(voiceKey).score || 0)
                : 0;

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
        chanjing: {
            video: TokensSceneEnum.HUMAN_VIDEO_CHANJING,
            voice: TokensSceneEnum.HUMAN_VOICE_CHANJING,
            audio: TokensSceneEnum.HUMAN_AUDIO_CHANJING,
            avatar: TokensSceneEnum.HUMAN_AVATAR_CHANJING,
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
            case DigitalHumanModelVersionEnum.CHANJING:
                return sceneKeys.chanjing;
            default:
                return sceneKeys.normal;
        }
    })();
    setCosts(keys.video, keys.voice, keys.audio, !anchor_id ? keys.avatar : undefined);
    costTokens.value = _costTokens;
};

const getAudioDuration = (msg: string, duration: number) => {
    audioDuration.value = duration || Math.floor(msg.trim().length / 3);
};

const validateFormData = (formData: any) => {
    const { url, audio_type, msg, voice_id, audio_url } = formData;

    if (!url) {
        feedback.msgError("请选择一位数字人");
        return false;
    }
    switch (audio_type) {
        case CreateTypeEnum.TEXT:
            if (!msg) {
                feedback.msgError("请输入视频文案");
                return false;
            } else if (!voice_id) {
                feedback.msgError("请选择音色");
                return false;
            }
            break;
        case CreateTypeEnum.AUDIO:
            if (!audio_url) {
                feedback.msgError("请上传音频文件");
                return false;
            }
            break;
    }

    // if (automatic_clip == 1) {
    //     if (!clip_type) {
    //         feedback.msgError("请选择剪辑风格");
    //         return false;
    //     }
    //     if (!music_url) {
    //         feedback.msgError("请上传背景音乐");
    //         return false;
    //     }
    // }

    return true;
};

const handleNext = () => {
    const {
        url,
        msg,
        name,
        pic,
        anchor_id,
        anchor_name,
        model_version,
        audio_type,
        voice_type,
        gender,
        voice_id,
        voice_name,
        audio_url,
        audio_duration,
        width,
        height,
        music_url,
        clip_type,
        automatic_clip,
        music_type,
    } = props.formData;

    createTaskParams.value = {
        ...createTaskParams.value,
        name,
        pic,
        video_url: url,
        voice_type,
        anchor_id,
        anchor_name,
        model_version,
        audio_type,
        gender,
        audio_url,
        width,
        height,
        music_url,
        clip_type,
        automatic_clip,
        music_type,
    };

    const setAudioDetails = (message: string, duration: number, voice_id?: number) => {
        createTaskParams.value.msg = message;
        if (voice_id != -1) {
            createTaskParams.value.voice_id = voice_id;
            createTaskParams.value.voice_name = voice_name;
        }
        getAudioDuration(msg, duration);
    };

    switch (audio_type) {
        case CreateTypeEnum.TEXT:
            setAudioDetails(msg, audioDuration.value, voice_id);
            break;
        case CreateTypeEnum.AUDIO:
            setAudioDetails("", audio_duration);
            break;
    }

    showNext.value = !showNext.value;
};

const handleCreate = async () => {
    try {
        handleNext();
        if (!validateFormData(props.formData)) return;
        if (getConstTotal.value > userTokens.value) {
            feedback.msgPowerInsufficient();
            return;
        }
        await createTask(createTaskParams.value);
        feedback.msgSuccess("创建成功,请在数字人管理中或者历史记录查看");
        userStore.getUser();
        showNext.value = false;
        emit("success");
    } catch (error) {
        feedback.msgError(error || "创建失败");
    }
};

const { lockFn: createLockFn, isLock: createIsLock } = useLockFn(handleCreate);

watch(() => props.formData, getCostRules, { immediate: true });
</script>

<style scoped></style>
