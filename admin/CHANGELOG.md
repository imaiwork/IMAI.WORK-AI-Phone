# 文件变更日志

用于追溯 `admin` 子项目的文件变更记录。每条记录包含：变更日期、文件、变更人、变更说明。

| 变更日期 | 文件 | 变更人 | 变更说明 |
| --- | --- | --- | --- |
| 2026-09-21 | `src/views/ai_application/device/person/components/copy-hotspot-panel.vue` | my | 热点库标题展示待用/已使用条数，条目标识待用或已使用，已使用也可移出；右侧状态/文案/平台间距收紧。 |
| 2026-09-20 | `src/views/ai_application/device/person/components/copy-hotspot-panel.vue` | my | 热点库待用列表新增删除：确认后移出，成功刷新条数；已占用由接口提示不可移出。 |
| 2026-09-20 | `src/api/ai_application/hotspot.ts` | my | 新增热点库移出接口 `deleteHotspotStock`（`/hotspot.pool/delete`）。 |
| 2026-09-20 | `src/views/ai_application/device/person/components/copy-hotspot-panel.vue` | my | 新增热点生成只读待用列表：条数、标题、已带文案/仅标题、平台；无跳转入库。 |
| 2026-09-20 | `src/views/ai_application/device/person/components/editor-pop.vue` | my | 视频剪辑选「热点生成」时，在取用规则上方接入热点库待用列表。 |
| 2026-09-20 | `src/api/ai_application/hotspot.ts` | my | 新增热点库待用列表接口 `getHotspotStock`（`/hotspot.pool/lists`）。 |
| 2026-09-19 | `src/views/ai_application/device/person/components/editor-pop.vue` | my | 视频剪辑文案来源增加「热点生成」，保存/回显 `hotspot_pick_mode`（1 最新入库优先 / 2 每条只用一次）。 |
| 2026-09-18 | `src/views/ai_application/device/task_template/lists/edit.vue` | my | 任务模板编辑新增任务类型「抖音搜寻获客」（scene=18），执行平台仅抖音。 |

## 说明

- 变更人取自 git 配置 `user.name`（`my`），如有正式姓名可自行替换。
- 本日志按「一次提交 / 一次功能变更」为粒度手动维护，新变更请追加到表格顶部（最新在上）。
