import request from "@/utils/request";

// 教程类目列表
export function getTutorialCategoryLists(params: any) {
    return request.get({ url: "/tutorial.tutorialCategory/lists", params });
}

// 删除教程类目
export function deleteTutorialCategory(params: any) {
    return request.post({ url: "/tutorial.tutorialCategory/delete", data: params });
}

// 添加教程类目
export function addTutorialCategory(data: any) {
    return request.post({ url: "/tutorial.tutorialCategory/add", data });
}

// 编辑教程类目
export function editTutorialCategory(data: any) {
    return request.post({ url: "/tutorial.tutorialCategory/edit", data });
}

// 教程类目详情
export function getTutorialCategoryDetail(params: any) {
    return request.get({ url: "/tutorial.tutorialCategory/detail", params });
}

// 教程列表
export function getTutorialLists(params: any) {
    return request.get({ url: "/tutorial.tutorial/lists", params });
}

// 删除教程
export function deleteTutorial(params: any) {
    return request.post({ url: "/tutorial.tutorial/delete", data: params });
}

// 添加教程
export function addTutorial(data: any) {
    return request.post({ url: "/tutorial.tutorial/add", data });
}

// 编辑教程
export function editTutorial(data: any) {
    return request.post({ url: "/tutorial.tutorial/edit", data });
}

// 教程详情
export function getTutorialDetail(params: any) {
    return request.get({ url: "/tutorial.tutorial/detail", params });
}
