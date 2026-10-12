# 文件变更日志

用于追溯 `uniapp` 子项目的文件变更记录。每条记录包含：变更日期、文件、变更人、变更说明。

| 变更日期 | 文件 | 变更人 | 变更说明 |
| --- | --- | --- | --- |
| 2026-09-23 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 主按钮费用角标改为固定高度并与文案垂直居中，避开小程序 button 不吃 flex 对齐。 |
| 2026-09-23 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 热点流程底部「更新入库」和主按钮收成 72rpx 高、24rpx 字；主文案单行显示，过长末尾省略。 |
| 2026-09-23 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 高级设置置灰时清空口播文案（含已回放内容），并在落缓存前清掉，避免下次打开再出现。 |
| 2026-09-23 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 切换人设时清空上一人设口播草稿；未生成口播时，入库和更新入库都不提交文案。 |
| 2026-09-22 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 热点话题分析进行中时，入库/更新入库按钮置灰并禁止点击。 |
| 2026-09-22 | `src/ai_modules/device/hooks/useDeviceWs.ts` | my | bindSocket 上报 content 增加登录 token；连接未建立、无 userId 或 token 为空时不发送。 |
| 2026-09-22 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 热点流程缓存写入成功后立刻点亮热榜「已分析」；两次存储都失败则不改列表。 |
| 2026-09-21 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 仅标题入库也保存搜索摘要/要点；服务端还原支持摘要与字符串要点，清缓存后不再重跑搜索。 |
| 2026-09-21 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 入库/更新入库不再提交热榜上的 `analysis_id`，避免别人设分析记录导致「人设或话题不匹配」。 |
| 2026-09-21 | `src/ai_modules/hotspot/pages/index/index.vue` | my | 热榜加载后用本地分析缓存补点「已分析」；点亮标签时按规范化标题匹配，避免截断对不上。 |
| 2026-09-21 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 入库成功、缓存/服务端还原上次记录后立刻点亮热榜「已分析」；缓存读写改走公共 util。 |
| 2026-09-21 | `src/ai_modules/hotspot/utils/flowCache.ts` | my | 新增热点流程本地缓存读写，以及按缓存给热榜补「已分析」标记。 |
| 2026-09-21 | `src/ai_modules/person/pages/material_library/components/copy-hotspot-panel.vue` | my | 热点库标题展示待用/已使用条数，条目标识待用或已使用，已使用也可移出；右侧状态/文案/平台间距收紧。 |
| 2026-09-21 | `src/ai_modules/person/pages/material_library/enums/index.ts` | my | 新增热点库占用标识 `HotspotStockUseStatus`，条目文案为已使用/待用。 |
| 2026-09-21 | `src/ai_modules/person/components/square-picker-popup/square-picker-popup.vue` | my | 素材库广场弹窗挪到人设模块 components，避免页面深层组件未被微信分包 appservice 收集。 |
| 2026-09-21 | `src/manifest.json` | my | 关闭微信「过滤无依赖文件」，避免新组件 JS 未打进分包。 |
| 2026-09-21 | `src/ai_modules/person/pages/detail/components/materials-tab.vue` | my | 素材库广场弹窗上收到详情页，本组件只负责打开和同步遮罩。 |
| 2026-09-21 | `src/packages/pages/home/components/popups/create-popup.vue` | my | 云端 Tab 视频生成失败时展示失败图标和 `shanjian_remark` 原因，不再误显示「生成中」。 |
| 2026-09-20 | `src/ai_modules/person/pages/material_library/components/copy-hotspot-panel.vue` | my | 热点库列表入库时间改为月日，与平台拼成「抖音09-20」。 |
| 2026-09-20 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 选择「不选人设」时隐藏入库/更新入库按钮。 |
| 2026-09-20 | `src/ai_modules/person/pages/material_library/components/copy-hotspot-panel.vue` | my | 热点库待用条目增加删除按钮，确认后调用 `stockDelete` 移出。 |
| 2026-09-20 | `src/ai_modules/person/pages/material_library/components/rule-setting-pop.vue` | my | 视频剪辑弹框承接热点库删除确认，避免确认框被底部「完成配置」挡住。 |
| 2026-09-20 | `src/ai_modules/person/pages/material_library/enums/index.ts` | my | 新增热点库移出确认文案 `HOTSPOT_STOCK_DELETE_CONFIRM`。 |
| 2026-09-20 | `src/api/hotspot.ts` | my | 新增 `deleteHotspotStock`，请求 `/hotspot.hotspot/stockDelete`。 |
| 2026-09-20 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 热点入库/更新入库时若人设 id 为 0，toast 提示「请选择人设」；成功后按 `updated` 提示「入库成功」或「更新入库成功」。 |
| 2026-09-20 | `src/ai_modules/person/pages/material_library/components/rule-setting-pop.vue` | my | 热点生成面板随视频剪辑弹框显隐卸载，打开时重新请求热点库待用列表。 |
| 2026-09-19 | `src/packages/pages/home/components/popups/create-popup.vue` | my | 内容弹窗保留原平台 tabs，追加云端成片：空态文案、标题/文案/话题编辑、成片成功后一键手动发布。 |
| 2026-09-19 | `src/packages/pages/home/home.vue` | my | 把当前人设 `run_mode` 传给内容弹窗。 |
| 2026-09-19 | `src/ai_modules/device/pages/create_task/create_task.vue` | my | 手动发布支持 `source=cloud_video`，按成片 id 回填素材与标题/文案/话题；跳转 source 改用枚举。 |
| 2026-09-19 | `src/enums/publishEnums.ts` | my | 新增 `PublishSourceEnum.CLOUD` 与手动发布 `ManualPublishSourceEnum`（含 `cloud_video`）。 |
| 2026-09-19 | `src/api/person.ts` | my | 内容列表/保存注释放开 `cloud`，`updatePublishContent` 可传 `persona_id`。 |
| 2026-09-19 | `src/ai_modules/hotspot/pages/index/index.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/ai_modules/hotspot/pages/detail/detail.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/ai_modules/hot_write/pages/index/index.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/ai_modules/hot_write/pages/detail/detail.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/packages/pages/creation/creation.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/ai_modules/drawing/pages/puzzle_detail/puzzle_detail.vue` | my | 跳转手动发布的 source 改为 `ManualPublishSourceEnum`。 |
| 2026-09-19 | `src/ai_modules/person/pages/material_library/enums/index.ts` | my | 热点库 `has_script` 与平台文案收到人设分包，避免小程序跨分包引用。 |
| 2026-09-19 | `src/ai_modules/person/pages/material_library/components/copy-hotspot-panel.vue` | my | 热点生成面板改为使用本包枚举/平台文案，去掉对 hotspot 分包的 import。 |
| 2026-09-19 | `scripts/check-persona-popup-regression.mjs` | my | 回归脚本增加断言：热点面板不得引用 hotspot 分包。 |
| 2026-09-19 | `src/ai_modules/person/pages/material_library/components/rule-setting-pop.vue` | my | 热点生成保存时随 `copywriting_source=5` 提交并回显 `hotspot_pick_mode`（1 最新入库优先 / 2 每条只用一次）。 |
| 2026-09-19 | `src/ai_modules/person/pages/material_library/enums/index.ts` | my | `HotspotTakeModeEnum` 改为与后端一致的 1/2，并增加 `normalizeHotspotTakeMode`。 |
| 2026-09-19 | `src/ai_modules/person/pages/material_library/components/rule-setting-pop.vue` | my | 放开热点生成配置保存，`copywriting_source` 提交/回显为 5。 |
| 2026-09-19 | `src/ai_modules/person/enums/index.ts` | my | `CopywritingSourceEnum` 对齐后端，新增 `HOTSPOT = 5`。 |
| 2026-09-19 | `src/ai_modules/person/pages/video_record/video_record.vue` | my | 创作记录文案来源标签补充文案库、热点生成。 |
| 2026-09-19 | `src/ai_modules/person/pages/detail/hooks/useHistoryTab.ts` | my | 人设历史记录文案来源标签补充文案库、热点生成。 |
| 2026-09-18 | `src/ai_modules/hotspot/pages/index/components/flow-popup.vue` | my | 热点追踪流程弹窗新增入库：可不选人设；已待用显示「更新入库」，否则「入库」。 |
| 2026-09-18 | `src/api/hotspot.ts` | my | 新增热点库待用列表、入库、待用详情接口：`getHotspotStock` / `addHotspotStock` / `getHotspotStockDetail`。 |
| 2026-09-18 | `src/ai_modules/hotspot/static/icons/archive-restore-gray.svg` | my | 新增入库按钮图标。 |
| 2026-09-18 | `src/ai_modules/hotspot/enums/index.ts` | my | 新增热点库 `has_script` 枚举 `HotspotStockHasScript`（仅标题 / 已带文案）。 |
| 2026-09-18 | `src/ai_modules/person/pages/material_library/components/rule-setting-pop.vue` | my | 文案来源增加「热点生成」Tab，选中后展示热点库子面板并拦截尚未开放的保存。 |
| 2026-09-18 | `src/ai_modules/person/pages/material_library/components/copy-hotspot-panel.vue` | my | 新增热点生成子面板：待用列表、取用规则、跳转热点追踪。 |
| 2026-09-18 | `src/ai_modules/person/pages/material_library/enums/index.ts` | my | 文案来源新增 `Hotspot`，并增加热点取用规则枚举 `HotspotTakeModeEnum`。 |
| 2026-09-18 | `src/ai_modules/person/static/icons/employee/corner-down-right-gray.svg` | my | 新增热点库标题图标。 |
| 2026-09-18 | `src/ai_modules/person/static/icons/employee/flame-orange.svg` | my | 新增热点库条目图标。 |
| 2026-09-18 | `src/ai_modules/person/static/icons/employee/flame-primary.svg` | my | 新增跳转热点追踪按钮图标。 |
| 2026-09-18 | `src/ai_modules/person/pages/task_flow/stores/taskStore.ts` | my | 24h 自动任务流新增任务类型「抖音搜寻获客」（`TaskScene.DY_SEARCH = 18`），执行平台仅抖音。 |
| 2026-09-18 | `src/ai_modules/device/pages/create_add_wechat/create_add_wechat.vue` | my | 「加微方式」改回由 `checkWechat` 的 `exists` 控制显隐；添加任务选项仍不依赖该接口。 |
| 2026-09-18 | `src/api/device.ts` | my | 恢复 `checkWechatClue`，仅用于「加微方式」显隐。 |
| 2026-09-18 | `src/ai_modules/device/pages/create_add_wechat/hooks/useTaskList.ts` | my | 添加任务选项固定展示「从客资线索中动态获取」，不再依赖 `checkWechat` 的 `exists`。 |
| 2026-09-17 | `src/ai_modules/device/enums/index.ts` | my | 新增线索来源枚举 `CUSTOMER_LEADS = 4`，并补充对应文案「客资线索」。 |
| 2026-09-17 | `src/ai_modules/device/pages/create_add_wechat/create_add_wechat.vue` | my | 新增「加微方式」选择（ai获客 / ai销售，客资线索时展示）；接入 `checkWechatClue` 接口控制客资选项显隐；微信账号列表参数追加 `mode: 1`。 |
| 2026-09-17 | `src/ai_modules/device/pages/create_add_wechat/hooks/types.ts` | my | 表单类型新增 `add_friend_type` 字段，默认值 1（ai获客）。 |
| 2026-09-17 | `src/ai_modules/device/pages/create_add_wechat/hooks/useCreateTask.ts` | my | 客资线索来源下 `crawling_task_ids` 置空，避免误传任务 ID。 |
| 2026-09-17 | `src/ai_modules/device/pages/create_add_wechat/hooks/useTaskList.ts` | my | 加微任务来源选择新增「从客资线索中动态获取」；选中后拉取客资线索统计填充任务卡片。 |
| 2026-09-17 | `src/api/device.ts` | my | 新增 `checkWechatClue`、`getCustomerLeadClues` 两个接口方法。 |

## 说明

- 变更人取自 git 配置 `user.name`（`my`），如有正式姓名可自行替换。
- 本日志按「一次提交 / 一次功能变更」为粒度手动维护，新变更请追加到表格顶部（最新在上）。
