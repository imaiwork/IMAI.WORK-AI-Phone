import request from '@/utils/request'

export function getPublicCopies(params?: Record<string, any>) {
    return request.get({ url: '/copywriting.publicCopy/lists', params })
}

export function addPublicCopy(params: Record<string, any>) {
    return request.post({ url: '/copywriting.publicCopy/add', params })
}

export function updatePublicCopy(params: Record<string, any>) {
    return request.post({ url: '/copywriting.publicCopy/update', params })
}

export function deletePublicCopy(params: { id: number }) {
    return request.post({ url: '/copywriting.publicCopy/delete', params })
}
