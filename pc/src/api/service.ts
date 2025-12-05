// 账号列表
export function getAccountList(params: any) {
    return $request.get({ url: "/sv.account/lists", params });
}

// 账号详情
export function getAccountDetail(params: any) {
    return $request.get({ url: "/sv.account/detail", params });
}

// 账号添加
export function addAccount(params: any) {
    return $request.post({ url: "/sv.account/add", params });
}

// 账号移除
export function deleteAccount(params: any) {
    return $request.post({ url: "/sv.account/delete", params });
}

// 账号更新
export function updateAccount(params: any) {
    return $request.post({ url: "/sv.account/update", params });
}

// 账号接管状态修改
export function changeAccountStatus(params: any) {
    return $request.post({ url: "/sv.account/ai", params });
}

// 账号名片列表
export function getAccountCardList(params: any) {
    return $request.get({ url: "/sv.material/lists", params });
}

// 账号名片添加
export function createAccountCard(params: any) {
    return $request.post({ url: "/sv.material/add", params });
}

// 账号名片更新
export function updateAccountCard(params: any) {
    return $request.post({ url: "/sv.material/update", params });
}

// 账号名片删除
export function deleteAccountCard(params: any) {
    return $request.post({ url: "/sv.material/delete", params });
}

// 素材列表
export function getMaterialList(params: any) {
    return $request.get({ url: "/sv.material/lists", params });
}

// 素材添加
export function addMaterial(params: any) {
    return $request.post({ url: "/sv.material/add", params });
}

// 素材更新
export function updateMaterial(params: any) {
    return $request.post({ url: "/sv.material/update", params });
}

// 素材详情
export function getMaterialDetail(params: any) {
    return $request.get({ url: "/sv.material/detail", params });
}

// 素材删除
export function deleteMaterial(params: any) {
    return $request.post({ url: "/sv.material/delete", params });
}

// 关键词列表
export function accountKeywordList(params: any) {
    return $request.get({ url: "/sv.accountKeyword/lists", params });
}

// 关键词添加
export function addAccountKeyword(params: any) {
    return $request.post({ url: "/sv.accountKeyword/add", params });
}

// 关键词更新
export function updateAccountKeyword(params: any) {
    return $request.post({ url: "/sv.accountKeyword/update", params });
}

// 关键词删除
export function deleteAccountKeyword(params: any) {
    return $request.post({ url: "/sv.accountKeyword/delete", params });
}

// 关键词详情
export function getAccountKeywordDetail(params: any) {
    return $request.get({ url: "/sv.accountKeyword/detail", params });
}

// 自动添加微信保存
export function updateAutoAddWechat(params: any) {
    return $request.post({ url: "/sv.addWechat/strategyUpdate", params });
}

// 自动添加微信配置
export function getAutoAddWechatConfig(params: any) {
    return $request.get({ url: "/sv.addWechat/strategyDetail", params });
}

// 自动添加微信记录
export function getAutoAddWechatRecord(params: any) {
    return $request.get({ url: "/sv.addWechat/lists", params });
}

// 自动添加微信删除
export function deleteAutoAddWechat(params: any) {
    return $request.post({ url: "/sv.addWechat/delete", params });
}

// 自动添加微信重试
export function retryAutoAddWechat(params: any) {
    return $request.post({ url: "/sv.addWechat/retry", params });
}
