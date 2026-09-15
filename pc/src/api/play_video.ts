// ── 爆款玩法（影视换脸改词 face / 跳舞换脸 dance；影视引流拼接 clip P1）──

// 模板墙（模板归外部系统，站长代理；对接中返回空列表 + notice）
export function getPlayVideoTemplates(params: { play?: string }) {
    return $request.post({ url: '/playVideo/templates', params })
}

// 提交生成（预扣算力，失败自动退；sn 传同值幂等重试）
export function submitPlayVideo(params: Record<string, any>) {
    return $request.post({ url: '/playVideo/submit', params })
}

// 任务详情（生成中自带惰性同步）
export function getPlayVideoDetail(params: { sn: string }) {
    return $request.post(
        { url: '/playVideo/detail', params },
        { ignoreCancel: true }
    )
}

// 我的任务列表
export function getPlayVideoLists(params: Record<string, any>) {
    return $request.post({ url: '/playVideo/lists', params })
}

// 一键替换台词：按人设 AI 改写（DeepSeek，按模型用量计费）
export function rewritePlayVideoLines(params: {
    persona_id: number
    lines: { role: string; text: string }[]
    template_name?: string
}) {
    return $request.post({ url: '/playVideo/rewriteLines', params })
}

// 上传正脸照/背景图（人脸私有桶；返回 face_file_id + 10 分钟预览签名 URL）
export function uploadPlayVideoFace(
    params: { file: File },
    onProgress?: (percent: number) => void
) {
    return $request.uploadFile(
        {
            url: '/playVideo/uploadFace',
            requestOptions: { ignoreCancel: true }
        },
        params,
        onProgress
    )
}
