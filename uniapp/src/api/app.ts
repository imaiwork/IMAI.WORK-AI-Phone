import request, { RequestEventStreamConfig, RequestOptions } from "@/utils/request";

//发送短信
export function smsSend(data: any) {
    return request.post({ url: "/sms/sendCode", data: data });
}

export function getConfig(data: any) {
    return request.get({ url: "/index/config", data });
}

export function getPolicy(data: any) {
    return request.get({ url: "/index/policy", data: data });
}

export function getMnpQrCode(data: any) {
    return request.post({ url: "/share/getMnpQrCode", data: data });
}

export function uploadImage(file: any, data?: any, token?: string, onProgress?: (e: any) => void) {
    return request.uploadFile(
        {
            url: "/upload/image",
            filePath: file,
            name: "file",
            header: {
                token,
            },
            formData: data,
            fileType: "image",
        },
        {
            onProgress,
            ignoreCancel: true,
        }
    );
}

export function uploadFile(
    type: "image" | "file" | "video" | "audio" | "llAudio",
    options: Omit<UniApp.UploadFileOption, "url">,
    onProgress?: (progress: number, options: RequestOptions) => void
) {
    return request.uploadFile(
        { ...options, url: `/upload/${type}`, name: "file" },
        {
            ignoreCancel: true,
            onProgress,
        }
    );
}

export function wxJsConfig(data: any) {
    return request.get({ url: "/wechat/jsConfig", data });
}

// 获取默认机器人
export function getDefaultRobot() {
    return request.get({ url: "/tools/lists" });
}

// 员工列表
export function getStaffLists() {
    return request.get({ url: "/staff/lists" });
}

// 员工详情
export function getStaffDetail(data: any) {
    return request.get({ url: "/staff/detail", data });
}

// 获取场景提示词
export function getScenePrompt() {
    return request.post({ url: "/tools/getPrompt" });
}

// 获取剪辑配置
export function getClipConfig() {
    return request.get({ url: "/tools/clip" });
}

// 视频转码
export function videoTranscode(data: any) {
    return request.post({ url: "/sv.tools/transcoding", data });
}

// 查询视频转码结果
export function getVideoTranscodeResult(data: any) {
    return request.post(
        { url: "/sv.tools/searchTranscoding", data },
        {
            ignoreCancel: true,
        }
    );
}

// 获取视频创作记录
export function getVideoCreationRecord(data?: any) {
    return request.get({ url: "/video/creationRecord", data });
}

// 删除视频创作记录
export function deleteVideoCreationRecord(data: any) {
    return request.post({ url: "/video/creationRecordDelete", data });
}

// 更新视频创作记录
export function updateVideoCreationRecord(data: any) {
    return request.post({ url: "/video/creationRecordUpdate", data });
}

// 视频转码
export function videoTranscoding(url: string) {
    return request.post({ url: "/file/videoTranscoding", data: { uri: url } });
}

// 获取视频信息
export function getVideoInfoByUrl(data: any) {
    return request.post({ url: "/videoInfo/getInfo", data });
}

// 批量获取视频信息
export function batchGetVideoInfoByUrl(data: any) {
    return request.post({ url: "/videoInfo/batchGetInfo", data });
}

// 获取视频缩略图
export function getVideoThumbnail(data: any) {
    return request.post({ url: "/videoInfo/thumbnail", data });
}

// 获取小程序通知模板列表
export function getMnpNoticeTemplateList(data: any) {
    return request.get({ url: "/notice.notice/settingMnpLists", data });
}

// 首页统计
export function getHomeDisplay() {
    return request.get({ url: "/display/display" });
}
