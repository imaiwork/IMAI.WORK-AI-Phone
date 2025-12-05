import request from "@/utils/request";

export const getMaterialMusicList = (data: any) => {
    return request.get({ url: "/material.music/lists", data });
};

export const addMaterialMusic = (data: any) => {
    return request.post({ url: "/material.music/add", data });
};

// 平台素材库列表
export function getMaterialLibraryList(data: any) {
    return request.get({ url: "/sv.mediaMaterial/lists", data });
}

// 平台素材库添加
export function addMaterialLibrary(data: any) {
    return request.post({ url: "/sv.mediaMaterial/add", data });
}

// 平台素材库删除
export function deleteMaterialLibrary(data: any) {
    return request.post({ url: "/sv.mediaMaterial/delete", data });
}

// 平台素材库编辑
export function editMaterialLibrary(data: any) {
    return request.post({ url: "/sv.mediaMaterial/edit", data });
}

// 平台素材库详情
export function getMaterialLibraryDetail(data: any) {
    return request.get({ url: "/sv.mediaMaterial/detail", data });
}
