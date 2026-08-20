/** 系统默认会员等级（普通用户），无到期时间 */
export const USER_LEVEL_DEFAULT_FLAG = 1

export function isDefaultUserLevel(level?: { is_default?: number | string } | null): boolean {
    return Number(level?.is_default) === USER_LEVEL_DEFAULT_FLAG
}

export function isUnsetUserLevelId(levelId: unknown): boolean {
    const id = Number(levelId)
    return !Number.isFinite(id) || id <= 0
}

export function findDefaultUserLevel<T extends { is_default?: number | string; id?: number | string }>(
    levels: T[]
): T | undefined {
    return levels.find((item) => isDefaultUserLevel(item))
}

export function resolveUserLevelId(levelId: unknown, defaultLevelId?: number | null): number | null {
    if (!isUnsetUserLevelId(levelId)) {
        return Number(levelId)
    }
    return defaultLevelId != null ? Number(defaultLevelId) : null
}
