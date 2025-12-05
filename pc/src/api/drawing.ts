// 绘制文生图
export function drawingTextToImage(params: any) {
    return $request.post({ url: "/hd/txt2img", params });
}
// 绘制文生图-即梦
export function drawingTextToImageVolc(params: any) {
    return $request.post({ url: "/hd/txt2volcimg", params });
}

// 绘制图生图
export function drawingImageToImage(params: any) {
    return $request.post({ url: "/hd/img2img", params });
}

// 绘制图生图-即梦
export function drawingImageToImageVolc(params: any) {
    return $request.post({ url: "/hd/img2volcimg", params });
}

// 生成商品图片
export function drawingGoods(params: any) {
    return $request.post({ url: "/hd/segmentImage", params });
}

// 生成AI试衣图片
export function drawingFitting(params: any) {
    return $request.post({ url: "/hd/vton", params });
}

// 即梦文生视频
export function drawingTextToVideo(params: any) {
    return $request.post({ url: "/volc/text2Video", params });
}

// 即梦图生视频
export function drawingImageToVideo(params: any) {
    return $request.post({ url: "/volc/image2Video", params });
}

// 豆包文生视频
export function drawingTextToVideoDoubao(params: any) {
    return $request.post({ url: "/hd.doubao/txt2video", params });
}

// 豆包图生视频
export function drawingImageToVideoDoubao(params: any) {
    return $request.post({ url: "/hd.doubao/img2video", params });
}
// 查询图片生成状态
export function drawingImageStatus(params: any) {
    return $request.post({
        url: "/hd/getTaskStatus",
        params,
    });
}

// 即梦查询视频生成状态
export function drawingVolcVideoStatus(params: any) {
    return $request.post({
        url: "/volc/getTaskStatus",
        params,
    });
}

// 豆包查询视频生成状态
export function drawingDoubaoVideoStatus(params: any) {
    return $request.post({
        url: "/hd.doubao/getTaskStatus",
        params,
    });
}

// 生成图片记录
export function drawingRecord(params: any) {
    return $request.get({
        url: "/hd/lists",
        params,
    });
}

// 生成视频记录
export function drawingVideoRecord(params: any) {
    return $request.get({
        url: "/volc/lists",
        params,
    });
}

// 删除
export function drawingDelete(params: any) {
    return $request.post({
        url: "/hd/deleteImage",
        params,
    });
}
// 删除视频
export function drawingVideoDelete(params: any) {
    return $request.post({
        url: "/volc/deleteVideo",
        params,
    });
}

// 获取模板列表
export function getTemplateList(params: any) {
    return $request.get({ url: "/hd/templates", params });
}

// 新增模板
export function templateAdd(params: any) {
    return $request.post({ url: "/hd/addTemplates", params });
}

// 编辑模板
export function templateEdit(params: any) {
    return $request.post({ url: "/hd/editTemplates", params });
}

// 删除模板
export function templateDelete(params: any) {
    return $request.post({ url: "/hd/deleteTemplates", params });
}

// 图片灵感分类
export function getImagePromptCategoryList(params: any) {
    return $request.get({ url: "/hd/cueImageCategory", params });
}

// 图片灵感列表
export function getImagePromptList(params: any) {
    return $request.post({ url: "/hd/cueImage", params });
}

// 快速组装分类
export function getQuickComposeCategoryList(params: any) {
    return $request.get({ url: "/hd.quickCompose/category", params });
}

// 快速组装列表
export function getQuickComposeList(params: any) {
    return $request.get({ url: "/hd/cueWord", params });
}

// 提示词生成
export function generateCueWord(params: any) {
    return $request.get({ url: "/assistants/sceneDetail", params });
}

// 优秀案例
export function getCaseLists(params: any) {
    return $request.get({ url: "/hd/caseLists", params });
}

// 添加模特
export function addModelCase(params: any) {
    return $request.post({ url: "/hd/addModelCase", params });
}

// 删除模特
export function deleteModelCase(params: any) {
    return $request.post({ url: "/hd/delModelCase", params });
}
