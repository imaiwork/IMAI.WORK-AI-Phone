// 获取设备列表
export function getDeviceList(params: any) {
    return $request.get({ url: "/device.device/lists", params });
}

// 添加设备
export function addDevice(params: any) {
    return $request.post({ url: "/sv.device/add", params });
}

// 删除设备
export function deleteDevice(params: any) {
    return $request.post({ url: "/sv.device/remove", params });
}

// 设备配置列表
export function getDeviceConfigList(params: any) {
    return $request.get({ url: "/sv.device/rpaLists", params });
}

// 设备配置更新
export function updateDeviceConfig(params: any) {
    return $request.post({ url: "/sv.device/rpaUpdate", params });
}

// 矩阵任务列表
export function getMatrixTaskList(params: any) {
    return $request.get({ url: "/sv.matrixMediaSetting/lists", params });
}

// 矩阵任务新增
export function addMatrixTask(params: any) {
    return $request.post({ url: "/sv.matrixMediaSetting/add", params });
}

// 矩阵任务更新
export function updateMatrixTask(params: any) {
    return $request.post({ url: "/sv.matrixMediaSetting/update", params });
}

// 矩阵任务详情
export function getMatrixTaskDetail(params: any) {
    return $request.get({ url: "/sv.matrixMediaSetting/detail", params });
}

// 设备任务发布
export function publishDeviceTask(params: any) {
    return $request.post({ url: "/device.publish/add", params });
}

// 设备任务列表
export function getDeviceTaskList(params: any) {
    return $request.get({ url: "/device.publish/lists", params });
}

// 删除设备任务
export function deleteDeviceTask(params: any) {
    return $request.post({ url: "/device.publish/delete", params });
}

// 更新设备任务
export function updateDeviceTask(params: any) {
    return $request.post({ url: "/device.publish/update", params });
}

// 获取设备账号任务列表
export function getDeviceAccountTaskList(params: any) {
    return $request.get({ url: "/device.publish/accountLists", params });
}

// 删除设备账号任务
export function deleteDeviceAccountTask(params: any) {
    return $request.post({ url: "/device.publish/accountDelete", params });
}

// 获取设备账号任务详情
export function getDeviceAccountTaskDetail(params: any) {
    return $request.get({ url: "/device.publish/accountDetail", params });
}

// 设备任务发布记录列表
export function getDeviceTaskRecordList(params: any) {
    return $request.get({ url: "/device.publish/recordLists", params });
}

// 更新设备账号任务
export function updateDeviceAccountTask(params: any) {
    return $request.post({ url: "/device.publish/accountUpdate", params });
}

// 删除设备任务记录
export function deleteDeviceTaskRecord(params: any) {
    return $request.post({ url: "/device.publish/recordDelete", params });
}

// 设备账号列表
export function getDeviceAccountList(params: any) {
    return $request.get({ url: "/device.account/lists", params });
}
