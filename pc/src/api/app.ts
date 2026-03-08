//发送短信
export function smsSend(params: any) {
    return $request.post({ url: "/sms/sendCode", params });
}

// 获取配置
export function getConfig() {
    return $request.get({ url: "/pc/config" });
}

// 获取协议
export function getPolicy(params: any) {
    return $request.get({ url: "/index/policy", params });
}

// 上传图片
export function uploadImage(params: any, onProgress?: (percent: number) => void) {
    return $request.uploadFile({ url: "/upload/image", requestOptions: { ignoreCancel: true } }, params, onProgress);
}

// 上传音频
export function uploadAudio(params: any, onProgress?: (percent: number) => void) {
    return $request.uploadFile({ url: "/upload/audio", requestOptions: { ignoreCancel: true } }, params, onProgress);
}

// 上传文件
export function uploadFile(params: any, onProgress?: (percent: number) => void) {
    return $request.uploadFile({ url: "/upload/file", requestOptions: { ignoreCancel: true } }, params, onProgress);
}

// 上传GPT文件
export function uploadGPTFile(params: any, onProgress?: (percent: number) => void) {
    // 确保requestKey参数被正确传递
    return $request.uploadFile({ url: "/GptFile/add" }, params, onProgress);
}

// 获取默认机器人
export function getDefaultRobot() {
    return $request.get({ url: "/tools/lists" });
}

// 员工列表
export function getStaffLists() {
    return $request.get({ url: "/staff/lists" });
}

// 员工详情
export function getStaffDetail(params: any) {
    return $request.get({ url: "/staff/detail", params });
}

// 检查是否需要调查问卷
export function checkSurvey() {
    return $request.get({ url: "/survey/check" });
}

// 提交调查问卷
export function submitSurvey(params: any) {
    return $request.post({ url: "/survey/add", params });
}

// 获取微信二维码
export function getMnpQrcode(params: any) {
    return $request.get({ url: "/wechat/getMnpCodeUrl", params });
}

// 获取场景提示词
export function getScenePrompt() {
    return $request.post({ url: "/tools/getPrompt" });
}

// 检查站点是否是oem
export function checkOem() {
    return $request.get({ url: "/oem.oem/check" });
}

// 获取剪辑配置
export function getClipConfig() {
    return $request.get({ url: "/tools/clip" });
}

// 获取视频创作记录
export function getVideoCreationRecord(params?: any) {
    return $request.get({ url: "/video/creationRecord", params }, { ignoreCancel: true });
}

// 删除视频创作记录
export function deleteVideoCreationRecord(params: any) {
    return $request.post({ url: "/video/creationRecordDelete", params });
}

// 更新视频创作记录
export function updateVideoCreationRecord(params: any) {
    return $request.post({ url: "/video/creationRecordUpdate", params });
}

// 查询视频转码结果
export function getVideoTranscodeResult(params: any) {
    return $request.post({ url: "/sv.tools/searchTranscoding", params });
}

// 视频转码
export function videoTranscode(params: any) {
    return $request.post({ url: "/file/videoTranscoding", params });
}

// 批量获取视频信息
export function batchGetVideoInfoByUrl(params: any) {
    return $request.post({ url: "/videoInfo/batchGetInfo", params });
}
