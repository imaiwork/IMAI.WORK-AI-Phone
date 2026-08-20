<template>
    <ElButton
        class="w-full !h-[50px] !rounded-[18px]"
        type="primary"
        size="large"
        :loading="isSubmitting"
        @click="createLockFn">
        <template v-if="isSubmitting">
            <span class="animate-spin leading-[0]">
                <Icon name="el-icon-Loading" color="#fff" :size="20" />
            </span>
            <span class="text-white font-[1000] text-[15px] tracking-wide">生成中...</span>
        </template>
        <template v-else>
            <Icon name="el-icon-VideoCamera" color="#fff" :size="20" />
            <span class="text-white font-[1000] text-[15px] tracking-wide">立即生成视频</span>
        </template>
    </ElButton>
</template>

<script setup lang="ts">
import { dayjs } from "element-plus";
import { createShanjianTask } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import {
    CreateTypeEnum,
    DigitalHumanModelVersionEnum,
    CHANJING_ORIGINAL_VOICE_ID,
    SpeechEngineTypeEnum,
} from "../../../_enums";

const DIGITAL_HUMAN_SPEECH_SHANJIAN_TYPE = 5;
const VIDEO_NAME_SUFFIX = "数字人口播";

/** 未填/清空视频名称时回落到与页面初始值一致的默认名 */
const resolveVideoName = (name: unknown) => {
    const trimmed = String(name ?? "").trim();
    if (trimmed) return trimmed;
    return dayjs().format("YYYYMMDDHHmmss").substring(2) + VIDEO_NAME_SUFFIX;
};

const props = withDefaults(
    defineProps<{
        formData: any;
        textLimit?: number;
    }>(),
    {
        textLimit: 1500,
    },
);

const emit = defineEmits<{
    (e: "error", error: Record<string, any>): void;
    (e: "success"): void;
}>();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const normalizeVolume = (volume: number) => {
    return Number(Number(volume || 0).toFixed(1));
};

const isChanjingOriginalVoice = (voiceId: any) => {
    return voiceId == CHANJING_ORIGINAL_VOICE_ID || voiceId === "" || voiceId == null;
};

const validateFormData = (formData: any) => {
    const { url, anchor_id, audio_type, msg, voice_id, audio_url } = formData;
    if (!url || !anchor_id) {
        feedback.msgError("请选择一位数字人");
        return false;
    }
    if (audio_type === CreateTypeEnum.AUDIO) {
        if (!audio_url) {
            feedback.msgError("请上传音频文件");
            return false;
        }
        return true;
    }
    if (!voice_id) {
        feedback.msgError("请选择音色");
        return false;
    }
    if (!msg) {
        feedback.msgError("请输入视频文案");
        return false;
    }
    if (msg.length > props.textLimit) {
        feedback.msgError("文案超出限制，请重新编辑文案");
        return false;
    }
    return true;
};

const getContentParams = () => {
    if (props.formData.audio_type === CreateTypeEnum.AUDIO) {
        return {
            audio: [
                {
                    url: props.formData.audio_url,
                },
            ],
        };
    }
    return {
        copywriting: [
            {
                content: props.formData.msg,
            },
        ],
    };
};

/**
 * type5 无包装：engine=1 闪剪 / engine=2 蝉镜
 * 蝉镜视频原音传空 voice，由服务端从形象视频克隆原音
 */
const createSpeechTaskByEngine = (engineType: SpeechEngineTypeEnum) => {
    const isOriginal = isChanjingOriginalVoice(props.formData.voice_id);
    const voiceId = isOriginal ? "" : props.formData.voice_id;
    const extra: Record<string, any> = {
        video_count: props.formData.extra?.video_count || 1,
        volume: normalizeVolume(props.formData.extra?.volume),
    };
    if (engineType === SpeechEngineTypeEnum.CHANJING) {
        extra.width = props.formData.width;
        extra.height = props.formData.height;
    }
    return createShanjianTask({
        name: resolveVideoName(props.formData.name),
        shanjian_type: DIGITAL_HUMAN_SPEECH_SHANJIAN_TYPE,
        engine_type: engineType,
        ai_clip_enabled: props.formData.ai_clip_enabled,
        pic: props.formData.pic,
        ...getContentParams(),
        anchor: [
            {
                anchor_id: props.formData.anchor_id,
            },
        ],
        voice: voiceId
            ? [
                  {
                      voice_id: voiceId,
                  },
              ]
            : [],
        extra,
    });
};

const handleCreate = async () => {
    try {
        if (!validateFormData(props.formData)) return;
        if (userTokens.value <= 0) {
            feedback.msgPowerInsufficient();
            return;
        }
        const engineType =
            props.formData.model_version === DigitalHumanModelVersionEnum.CHANJING
                ? SpeechEngineTypeEnum.CHANJING
                : SpeechEngineTypeEnum.SHANJIAN;
        await createSpeechTaskByEngine(engineType);
        userStore.getUser();
        emit("success");
    } catch (error) {
        feedback.msgError(error || "创建失败");
    }
};

const { lockFn: createLockFn, isLock: isSubmitting } = useLockFn(handleCreate);
</script>

<style scoped></style>
