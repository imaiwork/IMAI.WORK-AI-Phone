import { ref } from "vue";

/**
 * 请求用户授权
 * @param scope 需要请求的权限 scope，例如 'scope.writePhotosAlbum'
 * @returns Promise<boolean> 授权成功返回 true，失败返回 false
 */
export const requestAuthorization = (scope: string): Promise<boolean> => {
    return new Promise((resolve, reject) => {
        uni.getSetting({
            success: (res: any) => {
                if (res.authSetting[scope]) {
                    resolve(true);
                } else {
                    uni.authorize({
                        scope: scope,
                        success: () => {
                            resolve(true); // 授权成功
                        },
                        fail: async (err) => {
                            try {
                                const modalRes = await uni.showModal({
                                    title: "提示",
                                    content: "您关闭了权限，需要前往设置页面打开权限才能继续使用该功能。",
                                    confirmText: "去设置",
                                    cancelText: "取消",
                                });

                                if (modalRes.confirm) {
                                    const settingRes = await uni.openSetting();
                                    if (settingRes.authSetting && settingRes.authSetting[scope]) {
                                        resolve(true);
                                    } else {
                                        resolve(false);
                                    }
                                } else {
                                    resolve(false);
                                }
                            } catch (modalError) {
                                reject(modalError);
                            }
                        },
                    });
                }
            },
        });
    });
};

const downloading = ref<boolean>(false);
export async function saveImageToPhotosAlbum(url: string) {
    if (!url) return uni.$u.toast("图片错误");

    //#ifdef H5
    uni.$u.toast("请长按图片保存");
    //#endif
    //#ifndef H5
    if (downloading.value) {
        return;
    }
    uni.showLoading({ title: "下载中" });
    downloading.value = true;
    try {
        const res: any = await uni.downloadFile({ url, timeout: 10000 });
        await uni.saveImageToPhotosAlbum({
            filePath: res.tempFilePath,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "success",
            duration: 3000,
        });
        downloading.value = false;
    } catch (error: any) {
        uni.hideLoading();
        downloading.value = false;
        if (error.errMsg == "saveImageToPhotosAlbum:fail cancel") {
            uni.$u.toast("取消保存");
            return;
        }
        if (error.errMsg == "downloadFile:fail fail:timeout") {
            uni.$u.toast("下载图片超时，请重新下载");
            return;
        }
        if (error.errMsg == "saveImageToPhotosAlbum:fail auth deny") {
            const res: UniApp.ShowModalRes = await uni.showModal({
                title: "提示",
                content: "您关闭了权限，请前往设置打开权限",
            });
            if (res.confirm) {
                const setting = await uni.openSetting();
                if (setting.authSetting["scope.writePhotosAlbum"]) {
                    uni.showModal({
                        title: "提示",
                        content: "获取权限成功,再次保存图片即可成功",
                        showCancel: false,
                    });
                } else {
                    uni.showModal({
                        title: "提示",
                        content: "获取权限失败，无法保存到相册",
                        showCancel: false,
                    });
                }
            }
            return;
        }
        if (error.errno == 112) {
            uni.$u.toast("请到对应小程序后台完善用户隐私保护指引");
            return;
        }
        uni.showToast({
            title: error.errMsg || "保存失败",
            icon: "success",
            duration: 3000,
        });
    }
    //#endif
}

export async function saveVideoToPhotosAlbum(url: string) {
    try {
        // const isAuthorized = await requestAuthorization("scope.writePhotosAlbum");
        // if (!isAuthorized) {
        //     uni.$u.toast("您关闭了权限，请前往设置打开权限");
        //     return;
        // }

        uni.showLoading({ title: "下载中" });

        const { tempFilePath } = await uni.downloadFile({
            url,
        });

        await uni.saveVideoToPhotosAlbum({
            filePath: tempFilePath,
        });
        uni.hideLoading();
        uni.showToast({
            title: "保存成功",
            icon: "success",
            duration: 3000,
        });
    } catch (error: any) {
        console.log(error);
        if (error.errMsg == "saveVideoToPhotosAlbum:fail cancel") {
            uni.$u.toast("取消保存");
            return;
        }
        if (error.errMsg == "downloadFile:fail fail:timeout") {
            uni.$u.toast("下载视频超时，请重新下载");
            return;
        }
        if (error.errMsg == "saveVideoToPhotosAlbum:fail auth deny") {
            const res: UniApp.ShowModalRes = await uni.showModal({
                title: "提示",
                content: "您关闭了权限，请前往设置打开权限",
            });
            if (res.confirm) {
                const setting = await uni.openSetting();
                if (setting.authSetting["scope.writePhotosAlbum"]) {
                    uni.showModal({
                        title: "提示",
                        content: "获取权限成功,再次保存视频即可成功",
                        showCancel: false,
                    });
                } else {
                    uni.showModal({
                        title: "提示",
                        content: "获取权限失败，无法保存到相册",
                        showCancel: false,
                    });
                }
            }
            return;
        }
        if (error.errno == 112) {
            uni.$u.toast("请到对应小程序后台完善用户隐私保护指引");
            return;
        }

        uni.hideLoading();
        uni.showToast({
            title: error.errMsg || "保存失败",
            icon: "none",
            duration: 3000,
        });
    }
}
