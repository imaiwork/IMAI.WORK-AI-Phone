// 添加设备
export function addDevice(params: any) {
    return $request.post({ url: "/wechat.device/add", params });
}

// 更新设备
export function updateDevice(params: any) {
    return $request.post({ url: "/wechat.device/update", params });
}

// 删除设备
export function deleteDevice(params: any) {
    return $request.post({ url: "/wechat.device/remove", params });
}

// 添加微信
export function addWeChat(params: any) {
    return $request.post({ url: "/wechat.wechat/add", params });
}

// 更新微信
export function updateWeChat(params: any) {
    return $request.post({ url: "/wechat.wechat/update", params });
}

// 更新微信好友信息
export function updateWeChatFriend(params: any) {
    return $request.post({ url: "/wechat.friend/update", params });
}

// 获取微信好友信息
export function getWeChatFriend(params: any) {
    return $request.get({ url: "/wechat.friend/info", params });
}

// 新增微信好友
export function addWeChatFriend(params: any) {
    return $request.post({ url: "/wechat.friend/add", params });
}

// 删除微信好友
export function deleteWeChatFriend(params: any) {
    return $request.post({ url: "/wechat.friend/delete", params });
}

// 微信列表
export function getWeChatLists(params?: any) {
    return $request.get({ url: "/wechat.wechat/lists", params });
}

// 微信好友列表
export function getWeChatFriendLists(params: any) {
    return $request.get({ url: "/wechat.friend/lists", params });
}

// 微信AI设置
export function saveWeChatAi(params: any) {
    return $request.post({ url: "/wechat.wechat/ai", params });
}

// 获取微信AI设置
export function getWeChatAi(params: any) {
    return $request.get({ url: "/wechat.wechat/detail", params });
}

// 设备列表
export function getDeviceLists(params: any) {
    return $request.get({ url: "/wechat.device/lists", params });
}

// 上报微信好友
export function reportWeChatFriends(params: any) {
    return $request.post({ url: "/wechat.friend/batch", params });
}

// 添加代办
export function addTodo(params: any) {
    return $request.post({ url: "/wechat.todo/add", params });
}

// 编辑代办
export function editTodo(params: any) {
    return $request.post({ url: "/wechat.todo/update", params });
}

// 删除代办
export function deleteTodo(params: any) {
    return $request.post({ url: "/wechat.todo/delete", params });
}

// 获取代办列表
export function todoLists(params: any) {
    return $request.get({ url: "/wechat.todo/lists", params });
}

// 添加机器人
export function addRobot(params: any) {
    return $request.post({ url: "/wechat.robot/add", params });
}

// 删除机器人
export function deleteRobot(params: any) {
    return $request.post({ url: "/wechat.robot/delete", params });
}

// 更新机器人
export function updateRobot(params: any) {
    return $request.post({ url: "/wechat.robot/update", params });
}

// 获取机器人列表
export function robotLists(params: any) {
    return $request.get({ url: "/wechat.robot/lists", params });
}

// 获取机器人详情
export function robotDetail(params: any) {
    return $request.get({ url: "/wechat.robot/detail", params });
}

// 微信下线
export function offlineWeChat(params: any) {
    return $request.post({ url: "/wechat.wechat/offline", params });
}

// sop打招呼信息
export function sopGreetInfo() {
    return $request.get({ url: "/wechat.strategy/greetInfo" });
}

// sop打招呼编辑
export function sopGreetEdit(params: any) {
    return $request.post({ url: "/wechat.strategy/greet", params });
}

// 打招呼
export function messageGreet(params: any) {
    return $request.post({ url: "/wechat.message/greet", params });
}

// 发送消息
export function messageSend(params: any) {
    return $request.post({ url: "/wechat.message/send", params });
}

// 机器人新增关键词回复
export function addRobotKeywords(params: any) {
    return $request.post({ url: "/wechat.robotKeyword/add", params });
}

// 机器人更新关键词回复
export function updateRobotKeywords(params: any) {
    return $request.post({ url: "/wechat.robotKeyword/update", params });
}

// 机器人删除关键词回复
export function deleteRobotKeywords(params: any) {
    return $request.post({ url: "/wechat.robotKeyword/delete", params });
}

// 机器人关键词回复列表
export function robotKeywordsLists(params: any) {
    return $request.get({ url: "/wechat.robotKeyword/lists", params });
}

// 批量上传关键词回复
export function batchAddRobotKeywords(params: any) {
    return $request.post({ url: "/wechat.robotKeyword/import", params });
}

// 机器人回复策略设置
export function saveRobotReplyStrategy(params: any) {
    return $request.post({ url: "/wechat.strategy/reply", params });
}

// 获取机器人回复策略信息
export function getRobotReplyStrategy() {
    return $request.get({ url: "/wechat.strategy/replyInfo" });
}

// 自动好友通过策略
export function autoFriendPassStrategy(params: any) {
    return $request.post({ url: "/wechat.strategy/acceptFriend", params });
}

// 自动好友通过策略信息
export function autoFriendPassStrategyInfo() {
    return $request.get({ url: "/wechat.strategy/acceptFriendInfo" });
}

// 标签新增
export function addTag(params: any) {
    return $request.post({ url: "/wechat.strategy/tag", params });
}

// 标签更新
export function updateTag(params: any) {
    return $request.post({ url: "/wechat.strategy/tagUpdate", params });
}

// 标签删除
export function deleteTag(params: any) {
    return $request.post({ url: "/wechat.strategy/tagDelete", params });
}

// 标签信息
export function tagInfo(params: any) {
    return $request.get({ url: "/wechat.strategy/tagInfo", params });
}

// 标签列表
export function tagLists(params: any) {
    return $request.get({ url: "/wechat.strategy/tagLists", params });
}

// 批量上传标签
export function batchAddTags(params: any) {
    return $request.post({ url: "/wechat.strategy/tagImport", params });
}

// 标签好友列表
export function tagFriendLists(params: any) {
    return $request.get({ url: "/wechat.tag/friendLists", params });
}

// 好友标签添加
export function tagFriendAdd(params: any) {
    return $request.post({ url: "/wechat.tag/batchFriends", params });
}

// 好友标签删除
export function tagFriendDelete(params: any) {
    return $request.post({ url: "/wechat.tag/friendTagDelete", params });
}

// 列表v2
export function tagListsV2(params: any) {
    return $request.get({ url: "/wechat.tag/lists", params });
}

// 标签删除v2
export function deleteTagV2(params: any) {
    return $request.post({ url: "/wechat.tag/delete", params });
}

// 标签更新v2
export function tagUpdateV2(params: any) {
    return $request.post({ url: "/wechat.tag/update", params });
}

// 微信好友标签
export function friendTagDetail(params: any) {
    return $request.post({ url: "/wechat.tag/friendTagDetail", params });
}

// 微信好友标签删除
export function friendTagDelete(params: any) {
    return $request.post({ url: "/wechat.tag/friendTagDelete", params });
}

// 微信好友标签更新
export function friendTagUpdate(params: any) {
    return $request.post({ url: "/wechat.tag/friendTagUpdate", params });
}

// 朋友圈添加任务
export function circleTaskAdd(params: any) {
    return $request.post({ url: "/wechat.circle/addTask", params });
}

// 朋友圈任务列表
export function circleTaskLists(params: any) {
    return $request.get({ url: "/wechat.circle/taskLists", params });
}

// 朋友圈任务更新
export function circleTaskUpdate(params: any) {
    return $request.post({ url: "/wechat.circle/updateTask", params });
}

// 朋友圈任务删除
export function circleTaskDelete(params: any) {
    return $request.post({ url: "/wechat.circle/deleteTask", params });
}

// 朋友圈任务详情
export function circleTaskInfo(params: any) {
    return $request.get({ url: "/wechat.circle/taskInfo", params });
}

// 朋友圈策略设置
export function circleStrategySet(params: any) {
    return $request.post({ url: "/wechat.strategy/circleReplyLike", params });
}

// 朋友圈策略设置详情
export function circleStrategyInfo() {
    return $request.get({ url: "/wechat.strategy/circleReplyLikeInfo" });
}

// 素材分组列表
export function materialGroupLists(params: any) {
    return $request.get({ url: "/wechat.file/groupLists", params });
}

// 素材分组新增
export function materialGroupAdd(params: any) {
    return $request.post({ url: "/wechat.file/addGroup", params });
}

// 素材分组更新
export function materialGroupUpdate(params: any) {
    return $request.post({ url: "/wechat.file/updateGroup", params });
}

// 素材分组删除
export function materialGroupDelete(params: any) {
    return $request.post({ url: "/wechat.file/deleteGroup", params });
}

// 素材列表
export function materialLists(params: any) {
    return $request.get({ url: "/wechat.file/fileLists", params });
}

// 素材新增
export function materialAdd(params: any) {
    return $request.post({ url: "/wechat.file/add", params });
}

// 素材更新
export function materialUpdate(params: any) {
    return $request.post({ url: "/wechat.file/update", params });
}

// 素材删除
export function materialDelete(params: any) {
    return $request.post({ url: "/wechat.file/delete", params });
}

// 素材详情
export function materialInfo(params: any) {
    return $request.get({ url: "/wechat.file/info", params });
}

// sop推送列表
export function sopPushLists(params: any) {
    return $request.get({ url: "/wechat.sop.push/lists", params });
}

// sop推送新增
export function sopPushAdd(params: any) {
    return $request.post({ url: "/wechat.sop.push/add", params });
}

// sop推送更新
export function sopPushUpdate(params: any) {
    return $request.post({ url: "/wechat.sop.push/update", params });
}

// sop推送详情
export function sopPushDetail(params: any) {
    return $request.get({ url: "/wechat.sop.push/detail", params });
}

// sop推送删除
export function sopPushDelete(params: any) {
    return $request.post({ url: "/wechat.sop.push/delete", params });
}

// sop推送人员绑定
export function sopPushMemberAdd(params: any) {
    return $request.post({ url: "/wechat.sop.push/choiceFlow", params });
}

// sop推送内容新增
export function sopPushContentAdd(params: any) {
    return $request.post({ url: "/wechat.sop.pushContent/add", params });
}

// sop推送内容更新
export function sopPushContentUpdate(params: any) {
    return $request.post({ url: "/wechat.sop.pushContent/update", params });
}

// sop推送内容删除
export function sopPushContentDelete(params: any) {
    return $request.post({ url: "/wechat.sop.pushContent/delete", params });
}

// sop推送内容详情
export function sopPushContentDetail(params: any) {
    return $request.get({ url: "/wechat.sop.pushContent/detail", params });
}

// sop推送内容时间
export function sopPushContentTimeLists(params: any) {
    return $request.get({ url: "/wechat.sop.pushTime/pushTimeLists", params });
}

// sop流程列表
export function sopFlowLists(params: any) {
    return $request.get({ url: "/wechat.sop.flow/lists", params });
}

// sop流程新增
export function sopFlowAdd(params: any) {
    return $request.post({ url: "/wechat.sop.flow/add", params });
}

// sop流程更新
export function sopFlowUpdate(params: any) {
    return $request.post({ url: "/wechat.sop.flow/update", params });
}

// sop流程删除
export function sopFlowDelete(params: any) {
    return $request.post({ url: "/wechat.sop.flow/delete", params });
}

// sop流程详情
export function sopFlowDetail(params: any) {
    return $request.get({ url: "/wechat.sop.flow/detail", params });
}

// sop阶段新增
export function sopAddStage(params: any) {
    return $request.post({ url: "/wechat.sop.stage/add", params });
}

// sop阶段更新
export function sopUpdateStage(params: any) {
    return $request.post({ url: "/wechat.sop.stage/update", params });
}

// sop阶段删除
export function sopDeleteStage(params: any) {
    return $request.post({ url: "/wechat.sop.stage/delete", params });
}

// sop阶段标签触发条件新增
export function sopAddTagTrigger(params: any) {
    return $request.post({ url: "/wechat.sop.stage/addTrigger", params });
}

// sop阶段标签触发条件更新
export function sopUpdateTagTrigger(params: any) {
    return $request.post({ url: "/wechat.sop.stage/updateTrigger", params });
}

// sop阶段标签触发条件删除
export function sopDeleteTagTrigger(params: any) {
    return $request.post({ url: "/wechat.sop.stage/deleteTrigger", params });
}

// sop阶段自动跟进提醒新增
export function sopAddAutoFollow(params: any) {
    return $request.post({ url: "/wechat.sop.remind/add", params });
}

// sop阶段自动跟进提醒删除
export function sopDeleteAutoFollow(params: any) {
    return $request.post({ url: "/wechat.sop.remind/delete", params });
}

//sop阶段自动跟进提醒更新
export function sopUpdateAutoFollow(params: any) {
    return $request.post({ url: "/wechat.sop.remind/update", params });
}

// 流程看板
export function sopFlowBoard(params: any) {
    return $request.post({ url: "/wechat.sop.flow/dashboard", params });
}

// sop流程添加用户
export function sopFlowAddUser(params: any) {
    return $request.post({ url: "/wechat.sop.pushMember/add", params });
}

// sop流程删除用户
export function sopFlowDeleteUser(params: any) {
    return $request.post({ url: "/wechat.sop.pushMember/delete", params });
}

// sop流程转移用户
export function sopFlowTransferUser(params: any) {
    return $request.post({ url: "/wechat.sop.pushMember/transfer", params });
}

// sop流程用户列表
export function sopFlowUserLists(params: any) {
    return $request.get({ url: "/wechat.sop.pushMember/lists", params });
}

// 获取微信好友sop流程
export function getWeChatFriendSopFlow(params: any) {
    return $request.get({ url: "/wechat.sop.index/getFriendFlow", params });
}

// 获取好友sop推送记录
export function getWeChatFriendSopPush(params: any) {
    return $request.get({ url: "/wechat.sop.index/getFriendPushLog", params });
}

// 删除好友sop推送记录
export function deleteWeChatFriendSopPush(params: any) {
    return $request.post({ url: "/wechat.sop.index/deleteFriendPushLog", params });
}

// 获取sop推送记录
export function getSopPushLog(params: any) {
    return $request.get({ url: "/wechat.sop.index/getPushLogList", params });
}
