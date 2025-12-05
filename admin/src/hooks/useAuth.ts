import useUserStore from '@/stores/modules/user'

export function useAuth() {
    const userStore = useUserStore()
    const permissions = userStore.perms
    const hasAuth = (auth: string[]) => {
        const all_permission = '*'
        if (Array.isArray(auth)) {
            if (auth.length > 0) {
                const hasPermission = permissions.some((key: string) => {
                    return all_permission == key || auth.includes(key)
                })
                return hasPermission
            }
        } else {
            throw new Error('auth must be array')
        }
    }
    return {
        hasAuth
    }
}
