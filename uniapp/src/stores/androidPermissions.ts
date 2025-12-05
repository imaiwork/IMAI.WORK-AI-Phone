import { defineStore } from 'pinia'

export const usePermissionsStore = defineStore({
    id: 'androidPermissions',
    // 初始化状态
    state: (): any => ({
        // 处理应用程序权限请求
        // CAMERA: false,
        WRITE_EXTERNAL_STORAGE: false,
        // ACCESS_FINE_LOCATION: false,
        // CALL_PHONE: false,
        mapping: {
            // 'CAMERA': {
            //     title: "摄像头权限说明",
            //     content: "摄像头权限将用于拍摄照片和视频。这样，您可以在应用程序中记录瞬间、分享内容或进行其他相关操作。",
            //     methods: 'SET_CAMERA'
            // },
            WRITE_EXTERNAL_STORAGE: {
                title: '对存储空间/照片权限申请说明',
                content:
                    '便于您使用该功能上传您的照片/图片及用于更换头像、发布示例图片、下载、与客服沟通等场景中读取和写入相册和文件内容。',
                methods: 'SET_WRITE_EXTERNAL_STORAGE'
            }
            // 'ACCESS_FINE_LOCATION': {
            //     title: "****对地理位置权限申请说明",
            //     content: "****应用程序可以提供基于位置的服务、定位导航、附近搜索等功能。",
            //     methods: 'SET_ACCESS_FINE_LOCATION'
            // },
            // 'CALL_PHONE': {
            //     title: "****拨打/管理电话权限申请说明",
            //     content: "便于您使用该功能联系买家、骑手或者客服、业务经理与联系等场景下使用",
            //     methods: 'SET_CALL_PHONE'
            // }
        }
    }),
    actions: {
        //权限获取
        async requestPermissions(permissionID: string) {
            try {
                const viewObj = this.nativeObjView(permissionID)
                viewObj.show()
                console.log(
                    'android.permission.' + permissionID,
                    '当前手机权限'
                )

                return new Promise(async (resolve, reject) => {
                    // Android权限查询
                    function requestAndroidPermission(permissionID_: string) {
                        return new Promise((resolve, reject) => {
                            plus.android.requestPermissions(
                                [permissionID_],
                                function (resultObj) {
                                    let result = 0
                                    for (
                                        let i = 0;
                                        i < resultObj.granted.length;
                                        i++
                                    ) {
                                        result = 1
                                    }
                                    for (
                                        let i = 0;
                                        i < resultObj.deniedPresent.length;
                                        i++
                                    ) {
                                        result = 0
                                    }
                                    for (
                                        let i = 0;
                                        i < resultObj.deniedAlways.length;
                                        i++
                                    ) {
                                        result = -1
                                    }
                                    resolve(result)
                                },
                                function (error) {
                                    console.log(
                                        '申请权限错误：' +
                                            error.code +
                                            ' = ' +
                                            error.message
                                    )
                                    resolve({
                                        code: error.code,
                                        message: error.message
                                    })
                                }
                            )
                        })
                    }

                    const result = await requestAndroidPermission(
                        'android.permission.' + permissionID
                    )
                    viewObj.close()
                    // @ts-ignore
                    if (result !== 1 && result !== 0) {
                        uni.showModal({
                            title: '提示',
                            content: '操作权限已被拒绝，请手动前往设置',
                            confirmText: '立即设置',
                            success: (res) => {
                                if (res.confirm) {
                                    this.gotoAppPermissionSetting()
                                }
                            }
                        })
                    }
                    resolve(result)
                })
            } catch (error) {
                console.log(error)
            }
        },

        //提示框
        nativeObjView(permissionID: string) {
            const systemInfo = uni.getSystemInfoSync()
            const statusBarHeight: number | unknown = systemInfo.statusBarHeight
            const navigationBarHeight =
                systemInfo.platform === 'android' ? 48 : 44
            const totalHeight = (statusBarHeight as number) + navigationBarHeight
            let view = new plus.nativeObj.View('per-modal', {
                top: '0px',
                left: '0px',
                width: '100%',
                backgroundColor: '#444'
            })
            view.drawRect(
                {
                    color: '#fff',
                    radius: '5px'
                },
                {
                    top: totalHeight + 'px',
                    left: '5%',
                    width: '90%',
                    height: '100px'
                }
            )
            view.drawText(
                this.mapping[permissionID].title,
                {
                    top: totalHeight + 5 + 'px',
                    left: '8%',
                    height: '30px'
                },
                {
                    align: 'left',
                    color: '#000'
                }
            )
            view.drawText(
                this.mapping[permissionID].content,
                {
                    top: totalHeight + 35 + 'px',
                    height: '60px',
                    left: '8%',
                    width: '84%'
                },
                {
                    whiteSpace: 'normal',
                    size: '14px',
                    align: 'left',
                    color: '#656563'
                }
            )

            function show() {
                view = plus.nativeObj.View.getViewById('per-modal')
                view.show()
                view = null
            }

            function close() {
                view = plus.nativeObj.View.getViewById('per-modal')
                view.close()
                view = null
            }
            return {
                show,
                close
            }
        },

        // 跳转到**应用**的权限页面
        gotoAppPermissionSetting() {
            const Intent = plus.android.importClass(
                'android.content.Intent'
            )
            const Settings: any = plus.android.importClass(
                'android.provider.Settings'
            )
            const Uri: any = plus.android.importClass('android.net.Uri')
            const mainActivity = plus.android.runtimeMainActivity()
            const intent: any = new Intent()
            intent.setAction(Settings.ACTION_APPLICATION_DETAILS_SETTINGS)
            const uri = Uri.fromParts(
                'package',
                mainActivity.getPackageName(),
                null
            )
            intent.setData(uri)
            mainActivity.startActivity(intent)
        }
    }
})
