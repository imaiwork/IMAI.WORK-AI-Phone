import request from '@/utils/request'

// 爆款仿写列表
export const getHotWriteList = (data: Record<string, any>) => {
    return request.get({ url: '/videoImitation.task/lists', data })
}

// 爆款仿写详情
export const getHotWriteDetail = (data: Record<string, any>) => {
    return request.get({ url: '/videoImitation.task/detail', data })
}

// 爆款仿写删除
export const deleteHotWrite = (data: Record<string, any>) => {
    return request.post({ url: '/videoImitation.task/delete', data })
}

// 爆款仿写创建/重跑（抖音视频；id>0 时重跑已有任务；rewrite_mode 1人设复刻 2洗稿）
export const createHotWrite = (data: {
    url: string
    persona_id: number | string
    id?: number | string
    visual_material_source?: number | string
    /** 仿写文案目标字数，见 HOT_WRITE_COPY_LENGTH_OPTIONS；不传按后端默认档 */
    copywriting_length?: number
    rewrite_mode?: number
}) => {
    return request.post({ url: '/videoImitation.copywriting/video2text', data })
}

// 爆款仿写创建/重跑（小红书图文；id>0 时重跑已有任务；rewrite_mode 1人设复刻 2洗稿）
export const createHotWriteImageText = (data: {
    url: string
    persona_id: number | string
    id?: number | string
    rewrite_mode?: number
}) => {
    return request.post({ url: '/videoImitation.copywriting/image2text', data })
}

// 洗稿任务：获取可选的视频类型/形象/音色
export const getGenerationOptions = (data: { id: number | string }) => {
    return request.get({ url: '/videoImitation.task/generationOptions', data })
}

// 洗稿任务：确认视频类型/形象/音色并启动合成（可一并提交编辑后的洗稿文案）
export const confirmGenerationOptions = (data: {
    id: number | string
    generation_type: number
    avatar_id?: number
    /** 音色库 human_voice.id；-1 表示用所选形象的原音（WASH_ORIGIN_VOICE_ID） */
    voice_id?: number
    rewritten_text?: string
    /** 洗稿自选背景音乐音量 0~1，0 表示不加音乐；不传则沿用已有设置 */
    music_volume?: number
    /** 洗稿自选口播语速 0.5~2；仅闪剪音色的口播类视频可用，不传则沿用已有设置 */
    speech_rate?: number
    /** 洗稿自选剪辑模板ID；传空串=改回自动随机，不传则沿用已有设置 */
    clip_template_id?: string
    /** 洗稿自选背景音乐地址；传空串=改回系统音乐随机，不传则沿用已有设置 */
    music_url?: string
}) => {
    return request.post({ url: '/videoImitation.task/confirmGenerationOptions', data })
}

// 洗稿任务：单独确认洗稿文案（不触发合成，用于返回重进后恢复到视频配置步）
export const confirmRewrittenText = (data: { id: number | string; rewritten_text?: string }) => {
    return request.post({ url: '/videoImitation.task/confirmRewrittenText', data })
}

// 生成视频
export const generateVideo = (data: Record<string, any>) => {
    return request.post({ url: '/videoImitation.task/generate', data })
}

// 确认发布文案
export const confirmPublishText = (data: Record<string, any>) => {
    return request.post({ url: '/videoImitation.task/confirmPublishText', data })
}

// 图文任务：确认选图并启动图片改写（可同时提交编辑后的标题/正文）
export const confirmImageRewrite = (data: {
    id: number | string
    image_indexes: number[]
    title?: string
    rewritten_text?: string
}) => {
    return request.post({ url: '/videoImitation.task/confirmImageRewrite', data })
}

// 预发布
export const createHotWritePublishTask = (data: Record<string, any>) => {
    return request.post({ url: '/videoImitation.publish/add', data })
}
