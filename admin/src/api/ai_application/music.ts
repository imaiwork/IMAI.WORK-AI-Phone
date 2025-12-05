import request from '@/utils/request'

//音乐列表
export function getMusicRecord(params?: any) {
    return request.get({ url: '/music.musicRecord/lists', params })
}

//下拉选项
export function getMusicRecordOptions(params?: any) {
    return request.get({ url: '/music.musicRecord/options', params })
}

//音乐删除
export function deleteMusicRecord(params?: any) {
    return request.post({ url: '/music.musicRecord/del', params })
}

//AI配置详情
export function getMusicConfig(params?: any) {
    return request.get({ url: '/music.musicConfig/detail', params })
}

//AI配置保存
export function putMusicConfig(params?: any) {
    return request.post({ url: '/music.musicConfig/save', params })
}

//风格列表
export function getMusicStyle(params?: any) {
    return request.get({ url: '/music.musicStyle/lists', params })
}

//风格详情
export function getMusicStyleDetail(params?: any) {
    return request.get({ url: '/music.musicStyle/detail', params })
}

//风格新增
export function postMusicStyle(params?: any) {
    return request.post({ url: '/music.musicStyle/add', params })
}

//风格编辑
export function putMusicStyle(params?: any) {
    return request.post({ url: '/music.musicStyle/edit', params })
}

//风格状态
export function putMusicStyleStatus(params?: any) {
    return request.post({ url: '/music.musicStyle/status', params })
}

//风格删除
export function deleteMusicStyle(params?: any) {
    return request.post({ url: '/music.musicStyle/del', params })
}
