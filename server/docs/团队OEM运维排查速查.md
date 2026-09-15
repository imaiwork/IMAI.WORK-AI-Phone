# 团队 OEM 运维排查速查

> 配合 [`团队OEM实现说明.md`](./团队OEM实现说明.md)。只列高频故障与可执行 SQL（表前缀按环境替换，示例用 `iw_`）。

---

## 1. 邀请码提示「当前站点不给用」

**规则摘要**

| 目标团队 | 哪里能加 |
|---------|---------|
| `oem_status=2` | 仅该 OEM 站点（域名/appid 解析到该 `team_id`） |
| 免费团（`oem_status≠2`） | 主站可用 |

**排查**

```sql
-- 邀请码 → 团队
SELECT i.code, i.team_id, i.status, t.name, t.domain, t.oem_status, t.status AS team_status, t.owner_id
FROM iw_team_invite i
JOIN iw_team t ON t.id = i.team_id
WHERE i.code = '邀请码';

-- 团长归属（影响免费团在 OEM 站的可用性）
SELECT id, sn, nickname, team_id, team_role, origin_team_id
FROM iw_user WHERE id = <owner_id>;
```

**常见原因**

1. **团队已是 OEM（`oem_status=2`）但 `domain` 为空** → 主站加不了，也没有 OEM 域可加。  
   - 处理：品牌管理补域名，或确认不需要 OEM 则改回 `oem_status=0`（改库前先确认业务）。
2. 在主站用 **OEM 企业** 邀请码 → 须走该企业独立域名。
3. 在 OEM 站用 **主站免费团** 邀请码 → 按规则会被拦（免费团主站加）。

---

## 2. `/oem.oem/check` 把主站小程序判成 OEM

**现象**：Host 是 OEM 域，或库里 OEM 误填了主站 AppID，`is_oem=1`。

**排查**

```sql
-- 主站渠道 AppID（team_id=0）
SELECT value FROM iw_config
WHERE type='mnp_setting' AND name='app_id' AND team_id=0;

-- 哪些团队绑了同一 AppID
SELECT team_id, value FROM iw_config
WHERE type='mnp_setting' AND name='app_id' AND value='wx...';

-- 域名是否绑了 OEM 团队
SELECT id, name, domain, oem_status, status FROM iw_team
WHERE domain IN ('xxx.example.com') AND oem_status=2 AND status=1;
```

**处理**

- 代码侧：主站 AppID 请求应按主站解析；OEM 保存时禁止占用主站 AppID/主站域名。
- 数据侧：清掉 OEM 团队误填的主站 AppID；域名勿绑主站 Host。

---

## 3. 成员到期仍能用企业功能 / 进不了团队页

**到期权威字段**：`iw_team_member.expire_time`（`user.team_expire_time` 只是镜像，可能不同步）。

```sql
SELECT m.user_id, m.team_id, m.role, m.expire_time,
       FROM_UNIXTIME(m.expire_time) AS expire_at,
       u.team_id AS cur_team, u.team_role, u.team_expire_time
FROM iw_team_member m
JOIN iw_user u ON u.id = m.user_id
WHERE m.user_id = <uid> AND m.team_id = <tid>
  AND (m.delete_time IS NULL OR m.delete_time = 0);
```

**预期**

- 列表/切换：`expired=1` 不可进该企业空间。
- 消费/创建 IP：`TeamMemberService::assertActive` 硬拦。
- 若成员表已过期但 `user.team_expire_time=0`，以成员表为准（代码已按成员表判断）。

---

## 4. 团队成员列表看不到创始人

**原因**：`delete_time` 落成 `NULL`，软删查询按「未删除=`0`」会滤掉。

```sql
-- 查异常
SELECT id, team_id, user_id, role, delete_time
FROM iw_team_member
WHERE delete_time IS NULL;

-- 修复（确认后执行）
UPDATE iw_team_member SET delete_time = 0 WHERE delete_time IS NULL;
```

建表/写入应保证 `delete_time NOT NULL DEFAULT 0`。

---

## 5. 站点用户「移除」报不属于团队

**列表口径**：`origin_team_id=本企业`（可已切个人空间）。  
**旧移除口径**只认 `team_id+role=0` → 对不上。

```sql
SELECT id, sn, nickname, team_id, team_role, origin_team_id
FROM iw_user
WHERE origin_team_id = <teamId>
   OR (origin_team_id = 0 AND team_id = <teamId> AND team_role = 0);
```

移除后应清掉 `origin_team_id`（代码 `detachFromTeam`）。若仍留在列表，查该用户 `origin_team_id` 是否未清。

---

## 6. 消耗明细缺退费 / 剩余算力整列相同

**缺退费**

- 专用 INC（如 `9100` 数字人退费）或 `status=2`（失败/超额退回）须计入消耗列表与净消耗。
- 核对：

```sql
SELECT id, change_type, action, status, change_amount, left_tokens, remark, team_id,
       FROM_UNIXTIME(create_time) AS t
FROM iw_user_tokens_log
WHERE user_id = <uid> AND team_id = <tid>
ORDER BY id DESC LIMIT 50;
```

**剩余算力整列相同**

- 列表应展示流水 `left_tokens`（当时余额），不是现时钱包余额。

---

## 7. 企业扣费没落到团队

**核对**

```sql
-- 流水是否挂了 team_id
SELECT id, change_type, action, change_amount, team_id, task_id, remark
FROM iw_user_tokens_log
WHERE user_id = <uid> AND create_time >= UNIX_TIMESTAMP('2026-08-01')
ORDER BY id DESC LIMIT 30;

-- 成员企业钱包
SELECT team_id, role, team_tokens, expire_time
FROM iw_team_member
WHERE user_id = <uid> AND (delete_time IS NULL OR delete_time = 0);
```

异步任务须带创建时的计费团队（`billingTeamOverride` / 任务 `extra.billing_team_id`），避免执行时已切个人空间导致 `team_id=0`。

---

## 8. OEM 关站 / 解散后仍像主站

解散会写关站落痕（`oem_site.closed_domain:*` / `closed_appid:*`）。  
`/oem.oem/check` 对已解散 OEM 应返回 `site_closed=1`，不应静默回落主站品牌。

```sql
SELECT * FROM iw_config
WHERE type = 'oem_site' AND (name LIKE 'closed_domain:%' OR name LIKE 'closed_appid:%');
```

---

## 9. 自检命令（不改库）

```bash
php think team:oem-selfcheck --member=<成员用户ID> [--guest=<散客用户ID>]
```

事务内模拟扣退费后回滚。`--member` 须为某企业 `role=1/3`。

---

## 10. 改库前检查清单

1. 先 `SELECT` 确认影响行数，再 `UPDATE`。  
2. `oem_status` / `domain` / `origin_team_id` 影响登录归属与加团，改前备份。  
3. 生产改 OEM 状态须产品确认：是「未配域名的误开通」还是「必须走独立域」。
