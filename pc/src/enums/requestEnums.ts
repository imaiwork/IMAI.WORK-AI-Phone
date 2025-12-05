export enum ContentTypeEnum {
    // json
    JSON = "application/json;charset=UTF-8",
    // form-data   上传资源（图片，视频）
    FORM_DATA = "multipart/form-data",
    // event-stream
    EVENT_STREAM = "text/event-stream",
}

export enum RequestMethodsEnum {
    GET = "GET",
    POST = "POST",
}

export enum RequestCodeEnum {
    SUCCESS = 1,
    FAIL = 0,
    LOGIN_FAILURE = -1,
    OPEN_NEW_PAGE = 2,
}
