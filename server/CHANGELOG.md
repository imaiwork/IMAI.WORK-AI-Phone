# 文件变更日志

用于追溯 IMAI 后端的文件变更记录。每条记录包含：变更日期、文件、变更人、变更说明。

| 变更日期 | 文件 | 变更人 | 变更说明 |
| --- | --- | --- | --- |
| 2026-10-08 | `app/api/lists/sv/SvAddWechatRecordLists.php` | my | 自动加微记录列表和总数按 `lead_source` 过滤：`crawling` 为视频号获客，`dy_search` 为抖音搜寻获客，`ch_{渠道}_ex_{执行类型}` 要求两个任务 ID 都为 0。渠道和执行类型只接受页面已有取值。 |
| 2026-09-23 | `app/common/service/hotspot/PoolService.php` | my | 入库/更新入库时，若口播与同一话题下其他人设逐字相同，则按空文案处理并打日志，避免串稿覆盖当前人设口播。 |
| 2026-09-23 | `checks/hotspot_pool_persona_isolation.php` | my | 补串稿判定：其他人设逐字相同视为串稿，清空后更新入库仍保留当前人设自己的口播。 |
| 2026-09-23 | `tests/hotspot/pool_persona_isolation.php` | my | 与 checks 同步补串稿判定。 |
| 2026-09-21 | `app/api/logic/hotspot/HotspotLogic.php` | my | lastFlow 热榜不传人设时取最近任务；没有任务则回落入库现场，清缓存后不再重跑搜索。 |
| 2026-09-21 | `app/common/service/hotspot/PoolService.php` | my | 新增 `toLastFlow`：把热点库行转成与任务现场同构的还原结构。 |
| 2026-09-21 | `tests/hotspot/hotspot_copy_align_check.php` | my | 补 PI-18：入库要点字符串可转 lastFlow。 |
| 2026-09-21 | `app/common/service/hotspot/RecordService.php` | my | 全局热榜只点亮「已分析」，`persona_id<=0` 时不回填跨人设 `analysis_id`，避免更新入库带上别人设分析记录。 |
| 2026-09-21 | `app/common/service/hotspot/PoolService.php` | my | 入库遇到缺失或不匹配的 `analysis_id` 改为忽略并继续写入，不再抛「分析记录与当前人设或话题不匹配」。 |
| 2026-09-21 | `tests/hotspot/hotspot_copy_align_check.php` | my | 补 PI-14c/PI-14d：全局热榜不回填 `analysis_id`，指定人设仍回填本人设 ID。 |

## 说明

- 变更人取自 git 配置 `user.name`（`my`），如有正式姓名可自行替换。
- 本日志按「一次提交 / 一次功能变更」为粒度手动维护，新变更请追加到表格顶部（最新在上）。
