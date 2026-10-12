import { getSystemMusicList } from "@/api/digital_human";
import { MusicSourceEnum } from "../_enums";

/** 四项视频包装开关：混剪写进 extra，数字人纯口播写进 packaging */
export interface PackSwitchSetting {
    header_switch: boolean;
    material_switch: boolean;
    subtitle_switch: boolean;
    keyword_switch: boolean;
}

/** 带背景音乐的完整高级设置，目前只有数字人纯口播用（混剪各自有独立的背景音乐入口） */
export interface AdvancedSetting extends PackSwitchSetting {
    music_source: MusicSourceEnum;
    music_url: string;
    /** 只用于本地回显，不提交 */
    music_name: string;
    /** 系统推荐时指定一首，空=每次随机 */
    system_music_id: string;
    system_music_name: string;
    /** 指定或随机抽到的系统曲地址，提交时写入 packaging.music_url */
    system_music_url: string;
    music_volume: number;
}

export const ADVANCED_MUSIC_VOLUME_DEFAULT = 0.3;
/** 与音乐库上传口径一致：闪剪背景音乐不吃 m4a，music_choose 也把 m4a 过滤掉了 */
export const ADVANCED_MUSIC_ACCEPT = ["mp3", "wav"];
export const ADVANCED_MUSIC_SIZE = 20;

/** 后端 hasPackSwitch 是「传了任一个就四个全下发」，所以四个开关必须成组给默认值 */
export const createPackSwitchSetting = (): PackSwitchSetting => ({
    header_switch: true,
    material_switch: true,
    subtitle_switch: true,
    keyword_switch: true,
});

export const createAdvancedSetting = (): AdvancedSetting => ({
    ...createPackSwitchSetting(),
    music_source: MusicSourceEnum.SYSTEM,
    music_url: "",
    music_name: "",
    system_music_id: "",
    system_music_name: "",
    system_music_url: "",
    music_volume: ADVANCED_MUSIC_VOLUME_DEFAULT,
});

/**
 * 系统推荐在提交前补上具体音频地址。
 * 指定曲用已选地址；随机则从曲库抽一首写进 music_url。只开 music_source、地址留空时，包装下发没有 audioUrl。
 */
export const withResolvedSystemMusicUrl = async (setting: AdvancedSetting): Promise<AdvancedSetting> => {
    if (setting.music_source !== MusicSourceEnum.SYSTEM) return setting;
    if (setting.system_music_url) return setting;
    const res = await getSystemMusicList();
    const tracks = (Array.isArray(res) ? res : []).filter((item) => item?.audio_url);
    if (!tracks.length) return setting;
    const pinnedId = String(setting.system_music_id || "");
    if (pinnedId) {
        const pinned = tracks.find((item) => String(item.id) === pinnedId);
        if (!pinned) return setting;
        return { ...setting, system_music_url: String(pinned.audio_url) };
    }
    const hit = tracks[Math.floor(Math.random() * tracks.length)];
    return {
        ...setting,
        system_music_url: String(hit.audio_url),
    };
};

/**
 * 转成 packaging 提交参数：自定义音乐走 music 数组；
 * 系统推荐带上曲目 id，并把具体音频地址放进 music_url
 */
export const toPackagingParams = (setting: AdvancedSetting): Record<string, any> => {
    const isCustom = setting.music_source === MusicSourceEnum.CUSTOM;
    const isSystem = setting.music_source === MusicSourceEnum.SYSTEM;
    const musicUrl = isCustom ? setting.music_url : "";
    return {
        header_switch: setting.header_switch,
        material_switch: setting.material_switch,
        subtitle_switch: setting.subtitle_switch,
        keyword_switch: setting.keyword_switch,
        music_source: setting.music_source,
        music: musicUrl ? [musicUrl] : [],
        music_url: isSystem ? setting.system_music_url || "" : "",
        system_music_id: isSystem ? setting.system_music_id || "" : "",
        music_volume: setting.music_volume,
    };
};
