const MUSIC_CHOOSE_PATH = "/ai_modules/digital_human/pages/music_choose/music_choose";

/** 选曲页地址。放在脚本里拼，避免模板属性里的引号把编译截断 */
export function buildMusicChooseUrl(formData: {
    music: unknown;
    extra: { volume?: number | string; ai_music?: boolean; system_music_id?: string };
}) {
    const music = JSON.stringify(formData.music ?? []);
    const volume = formData.extra.volume ?? "";
    const aiMusic = formData.extra.ai_music ?? false;
    const systemMusicId = formData.extra.system_music_id || "";
    return `${MUSIC_CHOOSE_PATH}?music=${music}&volume=${volume}&ai_music=${aiMusic}&system_music_id=${systemMusicId}`;
}

export interface ChosenMusicPayload {
    music?: any;
    volume?: string | number;
    system_music_id?: string;
    system_music_name?: string;
}

/** 闪剪选曲页回填：指定一首、系统随机、用户自己的音乐三选一 */
export const applyMontageMusic = (
    formData: { music: any[]; extra: Record<string, any> },
    data: ChosenMusicPayload,
) => {
    const id = String(data.system_music_id || "");
    if (id) {
        formData.music = [];
        formData.extra.ai_music = false;
        formData.extra.system_music_id = id;
        formData.extra.system_music_name = data.system_music_name || "";
    } else if (data.music === -1 || data.music === "-1") {
        formData.music = [];
        formData.extra.ai_music = true;
        formData.extra.system_music_id = "";
        formData.extra.system_music_name = "";
    } else {
        formData.music = Array.isArray(data.music) ? data.music : [];
        formData.extra.ai_music = false;
        formData.extra.system_music_id = "";
        formData.extra.system_music_name = "";
    }
    if (data.volume !== undefined && data.volume !== "") {
        formData.extra.volume = data.volume;
    }
};
