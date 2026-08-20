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

// 爆款仿写创建/重跑（抖音视频；id>0 时重跑已有任务）
export const createHotWrite = (data: {
    url: string
    persona_id: number | string
    id?: number | string
    visual_material_source?: number | string
}) => {
    return request.post({ url: '/videoImitation.copywriting/video2text', data })
}

// 爆款仿写创建/重跑（小红书图文；id>0 时重跑已有任务）
export const createHotWriteImageText = (data: {
    url: string
    persona_id: number | string
    id?: number | string
}) => {
    return request.post({ url: '/videoImitation.copywriting/image2text', data })
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
