/**
 * 爆款仿写(Douyin 视频复刻)
 *
 * 后端接口:POST /videoImitation.copywriting/video2text
 * 后端逻辑:VideoImitationLogic::createOrUpdateTask
 *   - 拉取抖音视频文案 + 匹配人设素材 → 生成新文案 + 渲染数字人口播视频
 *   - 扣费:AccountLogEnum::TOKENS_DEC_VIDEO_IMITATION_COPYWRITING_PARSE (10208)
 */

export interface HotWritePayload {
    /** 抖音视频分享链接(v.douyin.com / www.douyin.com / 含"复制打开抖音"口令均可,后端会抓取) */
    url: string;
    /** 关联的人设 ID(必填,后端用它来匹配素材库 + 文案风格) */
    persona_id: number;
    /**
     * 素材来源:
     *   1 = AI 找素材
     *   2 = AI + 人设素材(默认)
     *   3 = 纯人设素材
     */
    visual_material_source?: 1 | 2 | 3;
}

export interface HotWriteResult {
    /** 任务 ID,后续轮询 /videoImitation.task/detail 用 */
    id: number;
    /** 任务状态(0=初始 / 1=处理中 / 2=失败 / 3=成功) */
    status?: number;
    [key: string]: any;
}

export function createHotWrite(payload: HotWritePayload) {
    return $request.post<HotWriteResult>({
        url: "/videoImitation.copywriting/video2text",
        params: payload,
    });
}
