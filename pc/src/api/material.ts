// 获取音频列表
export const getMaterialMusicList = (params: any) => {
    return $request.get({ url: "/material.music/lists", params });
};

// 添加音频
export const addMaterialMusic = (params: any) => {
    return $request.post({ url: "/material.music/add", params });
};
