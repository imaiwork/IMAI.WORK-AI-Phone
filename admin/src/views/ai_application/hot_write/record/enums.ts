/** 爆款仿写媒体类型 */
export enum HotWriteMediaType {
    VIDEO = 1,
    IMAGE_TEXT = 2
}

/** 平台：与 AppTypeEnum 对齐（爆款常用抖音/小红书） */
export enum HotWritePlatformType {
    XHS = 3,
    DOUYIN = 4
}

export const HOT_WRITE_MEDIA_TYPE_OPTIONS = [
    { label: '全部', value: '' },
    { label: '视频', value: String(HotWriteMediaType.VIDEO) },
    { label: '图文', value: String(HotWriteMediaType.IMAGE_TEXT) }
] as const

export const HOT_WRITE_PLATFORM_OPTIONS = [
    { label: '全部', value: '' },
    { label: '抖音', value: String(HotWritePlatformType.DOUYIN) },
    { label: '小红书', value: String(HotWritePlatformType.XHS) }
] as const

export const HOT_WRITE_PLATFORM_LABEL: Record<number, string> = {
    1: '视频号',
    3: '小红书',
    4: '抖音',
    5: '快手'
}

export const isImageTextRecord = (row: { media_type?: number | string }) =>
    Number(row?.media_type) === HotWriteMediaType.IMAGE_TEXT

export const getRecordPreviewImages = (row: {
    rewritten_images?: string[]
    selected_images?: string[]
    original_images?: string[]
    thumbnail?: string
}): string[] => {
    const rewritten = Array.isArray(row?.rewritten_images) ? row.rewritten_images.filter(Boolean) : []
    if (rewritten.length) return rewritten
    const selected = Array.isArray(row?.selected_images) ? row.selected_images.filter(Boolean) : []
    if (selected.length) return selected
    const original = Array.isArray(row?.original_images) ? row.original_images.filter(Boolean) : []
    if (original.length) return original
    return row?.thumbnail ? [row.thumbnail] : []
}
