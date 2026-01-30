// 获取音频列表
export const getMaterialMusicList = (params: any) => {
    return $request.get({ url: "/material.music/lists", params });
};

// 添加音频
export const addMaterialMusic = (params: any) => {
    return $request.post({ url: "/material.music/add", params });
};

// 素材库列表
export function getMaterialLibraryList(params: any) {
    return $request.get({ url: "/sv.mediaMaterial/lists", params });
}

// 素材库新增
export function addMaterialLibrary(params: any) {
    return $request.post({ url: "/sv.mediaMaterial/add", params });
}

// 素材库更新
export function updateMaterialLibrary(params: any) {
    return $request.post({ url: "/sv.mediaMaterial/update", params });
}

// 素材库删除
export function deleteMaterialLibrary(params: any) {
    return $request.post({ url: "/sv.mediaMaterial/delete", params });
}

// 素材分组列表
export function getMaterialLibraryGroupList(params: any) {
    return $request.get({ url: "/sv.mediaMaterialGroup/lists", params });
}
// 更新素材分组
export function updateMaterialLibraryGroup(params: any) {
    return $request.post({ url: "/sv.mediaMaterialGroup/update", params });
}

// 批量更新素材到分组
export function batchUpdateMaterialToGroup(params: any) {
    return $request.post({ url: "/sv.mediaMaterial/batchUpdate", params });
}

// 添加素材组
export function addMaterialLibraryGroup(params: any) {
    return $request.post({ url: "/sv.mediaMaterialGroup/add", params });
}

// 删除素材组
export function deleteMaterialLibraryGroup(params: any) {
    return $request.post({ url: "/sv.mediaMaterialGroup/delete", params });
}
