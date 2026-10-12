import request from '@/utils/request'

export function getSquareGroups() {
    return request.get({ url: '/square.group/lists' })
}

export function addSquareGroup(params: Record<string, any>) {
    return request.post({ url: '/square.group/add', params })
}

export function updateSquareGroup(params: Record<string, any>) {
    return request.post({ url: '/square.group/update', params })
}

export function deleteSquareGroup(params: { id: number }) {
    return request.post({ url: '/square.group/delete', params })
}

export function getSquareAssets(params?: Record<string, any>) {
    return request.get({ url: '/square.asset/lists', params })
}

export function addSquareAsset(params: Record<string, any>) {
    return request.post({ url: '/square.asset/add', params })
}

export function updateSquareAsset(params: Record<string, any>) {
    return request.post({ url: '/square.asset/update', params })
}

export function deleteSquareAsset(params: { id: number }) {
    return request.post({ url: '/square.asset/delete', params })
}
