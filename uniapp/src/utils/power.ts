import { useUserStore } from '@/stores/user'
import { useAppStore } from '@/stores/app'

/**
 * 算力不足统一提示
 * - OEM 站点：提示并进入算力中心(联系管理员二维码 + 兑换码)
 * - 企业空间内成员/管理员：提示联系团队主
 * - 团队主/散客/个人用户：提示充值
 */
export function powerInsufficientTip() {
    const info = (useUserStore().userInfo || {}) as any
    const inTeam = Number(info.team_id) > 0 && [1, 3].includes(Number(info.team_role))
    const appStore = useAppStore()
    if (appStore.isOemSite) {
        // 页面侧通常会接着 rechargePopup.open()，OEM 时弹窗内已是二维码+兑换
        uni.$u.toast('算力不足，请联系管理员或兑换卡密')
        return
    }
    uni.$u.toast(inTeam ? '当前团队算力不足，请联系团队主' : '算力不足，请充值！')
}
