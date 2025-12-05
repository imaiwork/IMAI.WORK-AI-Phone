// 创建形象
export const createAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/createAnchor", params });
};

// 形象列表
export const getAnchorList = (params?: Record<string, any>) => {
    return $request.get({ url: "/human/anchorLists", params });
};

// 重试形象
export const retryAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/anchorRetry", params });
};

// 删除形象
export const deleteAnchor = (params: Record<string, any>) => {
    return $request.post({ url: "/human/anchorDelete", params });
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
