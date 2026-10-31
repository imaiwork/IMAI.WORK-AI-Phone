# 文件变更日志

用于追溯 `admin` 子项目的文件变更记录。每条记录包含：变更日期、文件、变更人、变更说明。

| 变更日期 | 文件 | 变更人 | 变更说明 |
| --- | --- | --- | --- |
| 2026-10-29 | `src/api/ai_application/digital_human/system_music.ts`、`src/views/ai_application/montage/system_music/shared.ts`、`src/views/ai_application/montage/system_music/components/center-list.vue`、`src/views/ai_application/montage/system_music/components/local-list.vue`、`src/views/ai_application/montage/system_music/group/index.vue`（新增） | my | 公共音乐库新增分组：菜单拆成「列表」「分组」两个子页（列表沿用原页面，路径改由后端菜单改成目录 + 两个子菜单）。1) 分组页：新增/改名/删除/排序，删除只把组内曲目退回「无分组」并提示会退多少首，二次确认文案按组内曲目数区分；2) 列表页两个 tab 都新增「分组」列（行内下拉直接改归属，`systemMusic/changeGroup` 权限不足时置灰）与分组筛选（全部/无分组/具体分组）；3) 上传弹窗新增分组选择，默认「无分组」；4) `shared.ts` 新增 `loadMusicGroups()`，拉取失败回落空数组——分组只是附加维度，拉不到时上传仍然可用。 |
| 2026-10-26 | `src/api/ai_application/digital_human/system_music.ts`、`src/views/ai_application/montage/system_music/index.vue`、`src/views/ai_application/montage/system_music/components/center-list.vue`（新增）、`src/views/ai_application/montage/system_music/components/local-list.vue`（新增）、`src/views/ai_application/montage/system_music/shared.ts`（新增） | my | 公共音乐库改双 tab：`模板中心曲库`（原列表，内容只读，仅显隐/本站排序）与 `本站上传`（站长自传曲目）。新增上传弹窗（选择文件即上传、前端先校验扩展名与 20MB、浏览器读 metadata 补时长、名称默认取文件名）、编辑名称弹窗、删除（二次确认，删当前页最后一条自动回上一页）；试听/显隐/本站排序两个 tab 共用，按钮分别按 `systemMusic/upload|save|delete` 权限控制。接口封装补 `uploadSystemMusic`/`saveSystemMusic`/`deleteSystemMusic`，列表按 `source` 区分。 |
| 2026-10-26 | `src/api/ai_application/digital_human/clip_template.ts`（新增）、`src/api/ai_application/digital_human/system_music.ts`（新增）、`src/views/ai_application/montage/clip_template/index.vue`（新增）、`src/views/ai_application/montage/system_music/index.vue`（新增） | my | 口播混剪下新增「闪剪模板」「公共音乐库」两个管理页：内容由模板中心每 5 分钟同步，本页只维护本站覆盖位——前台显示开关（隐藏前二次确认，提示手动创作与 24 小时创作都不可用）与本站排序（el-input-number 失焦保存 + 「跟随」恢复 `sort_override=NULL`）；模板页另出中心排序（只读）、场景/自动化标签、封面与样片预览，音乐页出试听、时长与中心上下架状态。接口对应 IMAI `shanjian.shanjianClipTemplate/*` 与 `shanjian.systemMusic/*`。 |
| 2026-10-22 | `src/api/ai_application/device/person.ts`、`src/views/ai_application/device/person/setting/index.vue`（新增） | my | IP人设栏目下新增「人设设置」页：人设素材库一键清理按钮显隐开关（el-switch，1/0）+ 清理次数阈值（el-input-number，min 1 默认3），走 `/aiPersona.personaMaterialSetting/getConfig|setConfig`（后端菜单 SQL 见 IMAI `public/update/v2.13.7.sql`），保存用 `useLockFn` 防重复提交。 |
| 2026-10-21 | `src/api/consumer.ts`、`src/views/consumer/lists/index.vue` | my | 用户列表启用导出：新增导出接口 `exportUser`（`/user.user/export`），导出弹窗用 `export-data` 组件接入；按 `user.user/export` 权限控制显隐（v-if 判断，避免无权限时挂载即请求弹权限不足提示）。 |
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
