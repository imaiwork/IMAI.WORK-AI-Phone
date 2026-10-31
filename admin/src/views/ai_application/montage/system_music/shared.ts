// 公共音乐库后台页共用的小工具

import { getAllSystemMusicGroups } from "@/api/ai_application/digital_human/system_music";

// 与后端 SystemMusic::LOCAL_ALLOWED_EXTS 保持一致：
// 只收各端与闪剪链路都能直接解码的格式
export const MUSIC_ACCEPT = ".mp3,.wav,.m4a,.aac,.flac,.ogg";

export const MUSIC_ALLOWED_EXTS = ["mp3", "wav", "m4a", "aac", "flac", "ogg"];

// 前端先拦一道，超过 20MB 的多半会撞 PHP 上传上限，提前报错比等超时友好
export const MUSIC_MAX_SIZE = 20 * 1024 * 1024;

/** 曲目分组下拉选项（「无分组」在模板里单独拼，不进这个数组） */
export interface MusicGroupOption {
    id: number;
    name: string;
}

/**
 * 拉分组列表供下拉使用。失败时返回空数组：
 * 分组只是附加维度，拉不到时上传仍应可用（后端 group_id 缺省就是无分组）。
 */
export async function loadMusicGroups(): Promise<MusicGroupOption[]> {
    try {
        const res: any = await getAllSystemMusicGroups();
        if (!Array.isArray(res)) return [];
        return res
            .filter((item: any) => Number(item?.id) > 0)
            .map((item: any) => ({ id: Number(item.id), name: String(item.name || "") }))
            .filter((item: MusicGroupOption) => item.name !== "");
    } catch {
        return [];
    }
}

export function formatDuration(seconds: number | string): string {
    const total = Number(seconds) || 0;
    if (total <= 0) return "-";
    const min = Math.floor(total / 60);
    const sec = total % 60;
    return `${min}:${String(sec).padStart(2, "0")}`;
}

/**
 * 读取音频时长（秒）。上传接口不做音频探测（OSS 场景拿不到本地文件），
 * 由浏览器读 metadata 后随曲目信息一起提交；读不到就存 0，列表显示「-」，
 * 不影响曲目本身可用。
 */
export function readAudioDuration(url: string): Promise<number> {
    return new Promise((resolve) => {
        const audio = document.createElement("audio");
        let settled = false;
        let timer = 0;
        const finish = (value: number) => {
            if (settled) return;
            settled = true;
            window.clearTimeout(timer);
            audio.src = "";
            resolve(value);
        };
        timer = window.setTimeout(() => finish(0), 8000);
        audio.preload = "metadata";
        audio.onloadedmetadata = () => {
            finish(Number.isFinite(audio.duration) ? Math.round(audio.duration) : 0);
        };
        audio.onerror = () => finish(0);
        audio.src = url;
    });
}
