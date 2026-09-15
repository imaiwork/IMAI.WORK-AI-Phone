/**
 * 数字人视频生成 — 调系统已有的两个接口:
 *
 * 1) 闪剪体系(4 种形态,通过 shanjian_type 区分):
 *    POST /shanjian.shanjianVideoSetting/add
 *    - shanjian_type=1 数字人口播混剪
 *    - shanjian_type=2 真人口播混剪
 *    - shanjian_type=3 素材混剪
 *    - shanjian_type=4 新闻体
 *
 * 2) 纯数字人视频(无混剪):
 *    POST /human/createVideo
 */
import type { FollowupField } from "./drawing";

export type ShanjianType = 1 | 2 | 3 | 4;

/** 数字人形象 / 真人形象 */
export interface AnchorRef { anchor_id: string | number }
export interface VoiceRef { voice_id: string | number }
/** 人设 */
export interface CharacterDesign { name: string; introduced?: string }
/** 文案条目(可多条) */
export interface CopywritingItem { content: string; title?: string }
/** 素材 */
export interface MaterialItem { url: string; type?: "image" | "video"; name?: string }
/** 高级设置 */
export interface ShanjianExtra {
    /** 每条文案生成几个视频 */
    video_count?: number;
    /** 形象选取顺序:1=按序 0=随机 */
    human?: 0 | 1;
    /** 音乐选取:1=按序 0=随机 */
    music?: 0 | 1;
    /** 剪辑模板:1=按序 0=随机 */
    clip?: 0 | 1;
    /** 素材选取:1=按序 0=随机 */
    material?: 0 | 1;
    /** 素材原声音量(0=静音/不保留,>0 保留;0.3 是常用值) */
    volume?: number;
}

export interface ShanjianVideoPayload {
    /** 1=数字人口播 / 2=真人口播 / 3=素材混剪 / 4=新闻体 */
    shanjian_type: ShanjianType;
    title?: string;
    anchor: AnchorRef[];
    voice?: VoiceRef[];
    character_design?: CharacterDesign[];
    copywriting?: CopywritingItem[];
    /** 与 copywriting 二选一:直接录音模式 */
    audio?: { url: string }[];
    material?: MaterialItem[];
    music?: string[];
    clip?: { clip_template_id?: string }[];
    extra?: ShanjianExtra;
    /** 封面图 URL */
    pic?: string;
}

/** 4 种闪剪混剪类型统一入口 */
export function createShanjianVideo(payload: ShanjianVideoPayload) {
    return $request.post<{ id?: number; setting_id?: number; [k: string]: any }>({
        url: "/shanjian.shanjianVideoSetting/add",
        params: payload,
    });
}

/** 纯数字人口播视频(无混剪) — 用 /human/createVideo */
export interface PlainDigitalHumanPayload {
    anchor_id: string | number;
    voice_id?: string | number;
    audio_id?: string | number;
    /** 文案文本(后端会拿它转音频) */
    text?: string;
    title?: string;
    /** 背景音乐 URL */
    music_url?: string;
    [k: string]: any;
}
export function createPlainDigitalHumanVideo(payload: PlainDigitalHumanPayload) {
    return $request.post<{ task_id?: string | number; id?: number; [k: string]: any }>({
        url: "/human/createVideo",
        params: payload,
    });
}

/* ---------- 数字人 AI 追问（走后端 → 中台 Coze；后端接口尚未实现，数字人入口恢复前需先落地） ---------- */

/** 第 1 步：用户描述 → 动态问卷 {description, ppt_type, fields[]} */
export function genDigitalFollowupForm(text: string) {
    return $request.post<{ description: string; ppt_type: string; fields: FollowupField[] }>({
        url: "/human/followupForm",
        params: { text },
    });
}

/** 第 2 步：用户填好的表单答案 → 文案参数 */
export function genDigitalFollowupParams(answers: Record<string, any>) {
    return $request.post<any>({ url: "/human/followupParams", params: { answers } });
}

/** 第 3 步：文案参数 → 文案列表；amountOverride 用于单条「重新生成」时只要 1 条 */
export function genDigitalFinalCopy(params: any, options?: { amountOverride?: number }) {
    return $request.post<string[]>({
        url: "/human/generateCopy",
        params: { params, amount: options?.amountOverride },
    });
}

/** 按文案生成视频标题 */
export function genVideoTitle(copyText: string) {
    return $request.post<string>({ url: "/human/generateTitle", params: { text: copyText } });
}
