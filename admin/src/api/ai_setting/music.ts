import request from "@/utils/request";
// 获取音乐列表
export const getMaterialMusicList = (params: any) => {
    return request.get({ url: "/material.music/lists", params });
};

// 添加音乐
export const addMaterialMusic = (data: any) => {
    return request.post({ url: "/material.music/add", data });
};

// 删除音乐
export const deleteMaterialMusic = (params: any) => {
    return request.post({ url: "/material.music/delete", params });
};

// 更新音乐
export const updateMaterialMusic = (data: any) => {
    return request.post({ url: "/material.music/update", data });
};

// 获取音乐详情
export const getMaterialMusicDetail = (id: number) => {
    return request.get({ url: "/material.music/detail", params: { id } });
};
