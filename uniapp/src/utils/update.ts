export function checkMiniProgramUpdate() {
    // #ifdef MP-WEIXIN
    if (!uni.canIUse('getUpdateManager')) {
        return
    }

    const updateManager = uni.getUpdateManager()

    updateManager.onUpdateReady(() => {
        uni.showModal({
            title: '发现新版本',
            content: '新版本已准备好，是否立即重启更新？',
            confirmText: '立即更新',
            cancelText: '稍后',
            success: (modalRes) => {
                if (modalRes.confirm) {
                    updateManager.applyUpdate()
                }
            }
        })
    })

    updateManager.onUpdateFailed(() => {
        uni.showModal({
            title: '更新失败',
            content: '新版本下载失败，请删除当前小程序后重新搜索打开。',
            showCancel: false,
            confirmText: '知道了'
        })
    })

    updateManager.onCheckForUpdate((res) => {
        if (res.hasUpdate) {
            uni.showToast({
                title: '发现新版本，正在下载',
                icon: 'none'
            })
        }
    })
    // #endif
}
