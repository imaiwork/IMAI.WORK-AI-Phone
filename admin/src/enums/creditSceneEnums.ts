export const HUMAN_VOICE_SHANJIAN_S3_SCENE = "human_voice_shanjian_s3";
export const HUMAN_VOICE_SHANJIAN_S3_CODE = 5043;

export const AI_PERSON_BILLING_SCENES = [
    "human_avatar_chanjing",
    "human_voice_chanjing",
    "human_video_chanjing",
    "human_avatar_shanjian",
    "human_voice_shanjian",
    HUMAN_VOICE_SHANJIAN_S3_SCENE,
    "human_video_shanjian",
    "shanjian_realman_broadcast",
    "shanjian_broadcast_mixcut",
    "shanjian_news_mixcut",
    "ai_shanjian_authorized_video",
    "storyboard_video_create",
    "seedance2_480p_image2video_create",
    "seedance2_720p_image2video_create",
    "seedance2_480p_video2video_create",
    "seedance2_720p_video2video_create",
    "human_voice_minimax_hd",
    "human_voice_minimax_turbo",
    "human_audio_minimax_hd",
    "human_audio_minimax_turbo",
    "shanjian_ai_cover",
    "human_avatar_shanjian_pro",
    "play_face",
    "play_dance",
    "play_splice",
] as const;

export function isAiPersonBillingItem(item: { scene?: string; code?: string | number }) {
    const scene = String(item?.scene ?? "");
    if ((AI_PERSON_BILLING_SCENES as readonly string[]).includes(scene)) {
        return true;
    }
    return Number(item?.code) === HUMAN_VOICE_SHANJIAN_S3_CODE;
}
