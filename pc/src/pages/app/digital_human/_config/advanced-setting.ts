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
    music_volume: ADVANCED_MUSIC_VOLUME_DEFAULT,
});

/**
 * 转成 packaging 提交参数：自定义音乐走 music 数组，music_url 留空由后端归一化后回填；
 * 系统推荐来源不带音乐，后端随机挑曲
 */
export const toPackagingParams = (setting: AdvancedSetting): Record<string, any> => {
    const isCustom = setting.music_source === MusicSourceEnum.CUSTOM;
    const musicUrl = isCustom ? setting.music_url : "";
    return {
        header_switch: setting.header_switch,
        material_switch: setting.material_switch,
        subtitle_switch: setting.subtitle_switch,
        keyword_switch: setting.keyword_switch,
        music_source: setting.music_source,
        music: musicUrl ? [musicUrl] : [],
        music_url: "",
        music_volume: setting.music_volume,
    };
};
