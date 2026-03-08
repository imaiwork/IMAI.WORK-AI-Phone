// 创建形象
export const createAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/createAnchor", params });
};

// 闪剪形象创建
export const createShanjianAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianAnchor/add", params });
};

// 形象列表
export const getAnchorList = (params?: Record<string, any>) => {
    return $request.get({ url: "/human/anchorLists", params });
};

// 数字人公共形象列表
export const getPublicAnchorList = (params?: Record<string, any>) => {
    return $request.get({ url: "/digitalHuman/anchorLists", params });
};

// 数字人公共形象列表
export const getPublicAnchorListV2 = (params?: Record<string, any>) => {
    return $request.get({ url: "/digitalHuman/anchorPublicLists", params });
};

// 重试形象
export const retryAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/anchorRetry", params });
};

// 删除形象
export const deleteAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/anchorDelete", params });
};
// 闪剪形象删除
export const deleteShanjianAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianAnchor/delete", params });
};

// 闪剪形象详情
export const getShanjianAnchorDetail = (params: Record<string, any>) => {
    return $request.get({ url: "/shanjian.shanjianAnchor/detail", params });
};

// 删除公共形象
export const deletePublicAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/digitalHuman/deletePublicAnchor", params });
};

// 获取数字人列表
export const getVideoList = (params?: Record<string, any>) => {
    return $request.get({ url: "/human/videoLists", params });
};

// 删除数字人
export const deleteDigitalHuman = (params: Record<string, any>) => {
    return $request.post({ url: "/human/videoDelete", params });
};

// 语音克隆
export const voiceClone = (params: Record<string, any>) => {
    return $request.post({ url: "/human/createVoice", params });
};

// 音色列表
export const getVoiceList = (params?: Record<string, any>) => {
    return $request.get({ url: "/human/voiceLists", params });
};

// 删除音色
export const deleteVoice = (params: Record<string, any>) => {
    return $request.post({ url: "/human/voiceDelete", params });
};

// 重新生成音色
export const retryVoice = (params: Record<string, any>) => {
    return $request.post({ url: "/human/voiceRetry", params });
};

// 创建音频
export const createAudio = (params: Record<string, any>) => {
    return $request.post({ url: "/human/createAudio", params });
};

// 重新生成音频
export const retryAudio = (params: Record<string, any>) => {
    return $request.post({ url: "/human/audioRetry", params });
};

// 音频列表
export const getAudioList = (params?: Record<string, any>) => {
    return $request.get({ url: "/human/audioLists", params });
};

// 删除音频
export const deleteAudio = (params: Record<string, any>) => {
    return $request.post({ url: "/human/audioDelete", params });
};

// 创建视频
export const createVideo = (params: Record<string, any>) => {
    return $request.post({ url: "/human/createVideo", params });
};

// 重试视频
export const retryVideo = (params: Record<string, any>) => {
    return $request.post({ url: "/human/videoRetry", params });
};

// 创建数字人任务
export const createTask = (params: Record<string, any>) => {
    return $request.post({ url: "/human/videoTask", params });
};

// 文案生成
export const generatePrompt = (params: Record<string, any>) => {
    return $request.post({ url: "/human/copywriting", params });
};

// 闪剪语音克隆
export const shanjianVoiceClone = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.voice/add", params });
};

// 批量克隆形象
export const batchCloneAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/digitalHuman/createAnchor", params });
};

export const createShanjianTask = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianVideoSetting/add", params });
};

// 人设新增
export const addShanjianPerson = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianCharacterDesign/add", params });
};

// 人设列表
export const getShanjianPersonList = (params: Record<string, any>) => {
    return $request.get({ url: "/shanjian.shanjianCharacterDesign/lists", params });
};

// 人设删除
export const deleteShanjianPerson = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianCharacterDesign/delete", params });
};

// 闪剪形象列表
export const getShanjianAnchorList = (params: Record<string, any>) => {
    return $request.get({ url: "/shanjian.shanjianAnchor/lists", params });
};

// 新闻体文案生成
export const generateNewsBodyPrompt = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.tools/getNewsMixcutTittle", params });
};

// 闪剪口播文案生成
export const generateShanjianPrompt = (params: Record<string, any>) => {
    return $request.post({ url: "/shanjian.shanjianVideoTask/copywriting", params }, { ignoreCancel: true });
};

// sora 任务重试
export const retrySoraTask = (params: Record<string, any>) => {
    return $request.post({ url: "/sora.soraVideoSetting/retry", params });
};

// 闪剪形象授权列表
export const shanjianAnchorAuthorizedList = (params: Record<string, any>) => {
    return $request.get({ url: "/shanjian.shanjianAnchor/authorizedList", params });
};
