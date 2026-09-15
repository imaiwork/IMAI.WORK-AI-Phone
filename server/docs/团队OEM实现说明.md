# 团队 OEM 实现说明（v2.12.0）

> 面向开发/运维/测试。  
> 本文档描述 **P0–P6** 的完整实现、数据模型、计费/共享/隔离规则、部署步骤与验证方法。  
> 文档路径：`server/docs/团队OEM实现说明.md`（由原仓库根目录同名文件迁入并续补近期规则）。

---

## 1. 概念总览

团队 OEM = 多租户企业空间：用户可创建/加入「企业(团队)」，团队主付费升级 → 站长审核 → 获得独立域名/品牌/小程序/卡密/成员/算力，团队内共享智能体与知识库。

### 角色（`la_user.team_role` / `la_team_member.role`）

| 值 | 角色 | 算力结算 | 资源可见 |
|---|---|---|---|
| 0 | 散客(站点归属) | 自己个人算力 | 个人 |
| 1 | 团队成员 | **消耗团队长算力**(企业钱包→团队长个人) | 企业空间全员共享 |
| 2 | 团队主(超管) | 自己个人算力 | 企业空间全员共享 |
| 3 | 团队管理员 | **消耗团队长算力**(同成员) | 企业空间全员共享 |

### OEM 状态机（`la_team.oem_status`）

`0 免费版` → 团队主 `upgradeOem`(预缴算力) → `1 待审核` → 站长 `auditOem` / 后台直接开通：

- 通过 → `2 已开通`（预缴转正式消费，不退）
- 拒绝 → `0 免费版`（预缴算力退回团队主）

**注意**：`oem_status=2` 但未绑定 `domain` 时，该企业邀请码在主站会被站点隔离拦截，且没有 OEM 域可进入——属于异常配置，须补域名或改回免费版。

### 当前企业空间（关键）

`la_user.team_id` = 用户**当前选中**的企业（0=个人空间）。`switchTeam` 原子切换 `team_id/team_role/team_expire_time`。**所有计费/共享/明细均以该单一字段现查为准**，天然隔离多企业。

`la_user.origin_team_id` = OEM 站点原生账号归属（0=平台账号）。用于站点用户列表、解散后回落、跨站硬隔离；**不等于当前选中企业**。

---

## 2. 数据模型（本次涉及字段）

| 表 | 字段 | 用途 | 来源 |
|---|---|---|---|
| `la_team` | `oem_status`(0/1/2), `oem_apply_time`, `oem_pay_tokens`, `oem_audit_time`, `oem_audit_remark`, `owner_id`, `domain`, `status`, `seat_limit`, `member_count` | 团队/OEM 主数据+审核 | P1 |
| `la_team_member` | `team_id`, `user_id`, `role`, `team_tokens`(企业钱包), `expire_time`, `delete_time`(未删除=0) | 成员关系+企业钱包 | 已有 |
| `la_user` | `team_id`, `team_role`, `team_expire_time`, `tokens`, `origin_team_id` | 当前企业空间+个人算力+站点归属 | 已有 |
| `la_user_tokens_log` | `team_id`(0=个人), `status`(1=正常扣费,2=失败/超额退回) | 扣费明细按企业归属 | P0 |
| `la_config` | `team_id`(0=平台全局) | 品牌/小程序等按团队隔离 | 已有 |
| `la_kb_robot` / `la_knowledge` / `la_kb_know` | `team_id` | 智能体/知识库团队归属 | P3 |
| `la_oem` | `domain,user_id,name,logo_url,site_logo,status` | 旧版站点换肤 | 旧版，P6 回落 |

表前缀安装/更新时由 `la_` 替换为实际前缀（如 `iw_`）。

---

## 3. 分阶段实现

### P0 打地基

- `AccountLogEnum`：补团队 OEM/消费枚举（`TOKENS_DEC_OEM_UPGRADE=12010`、`TOKENS_INC_OEM_UPGRADE_REFUND=12011`、`TOKENS_DEC_TEAM_CONSUME=12012`、`TOKENS_INC_TEAM_CONSUME_REFUND=12013` 等）并入 `TOKENS_DEC/INC`。
- `AccountLogLogic::add`：`UserTokensLog` 写入企业 `team_id`；成员 `left_tokens` 写企业钱包口径。

### P1 OEM 审核闭环

- 后台：`adminapi/controller/team/TeamController` + `adminapi/logic/team/TeamLogic`（detail/auditOem/openOem/changeStatus/setSeat 等）+ `adminapi/lists/team/TeamLists`。
- 菜单：团队管理 → 团队列表 / OEM申请列表 / OEM授权管理。
- 用户端：`api/controller/TeamController::upgradeOem`（手机号+短信校验）。
- 品牌/小程序/卡密/站点用户：`TeamLogic::setTenantByTeam` / `attributedUsers` / `setSiteUserTokens` 等。

### P2 算力管理（核心计费）

统一实现「团队成员消费 = 消耗团队长算力」。

**接入点**：全局唯一扣/退费入口 `User::userTokensChange()` 委托 `TeamBillingService`，**无需逐处改造**。

**`app/common/service/TeamBillingService.php`**：

- `resolveSpender(userId)`：team_id>0 且 role∈{1,3} → `{team_id, owner_id}`；否则按个人。
- `spendableTokens(userId)`：成员=企业钱包+团队长个人；其他=个人。
- `deduct`：钱包优先，不足回落团队长；行锁；不足抛 4059。
- `refund` / `refundToTeam`：按原始扣费企业退回；异步退费不退错人。
- `billingTeamOverride`：异步任务按创建时团队结算。

**余额校验团队感知**：`ChatBillingService` / `DrawBillingService` / `TokenLogService::checkToken` 走 `spendableTokens`，并调用 `TeamMemberService::assertActive`（到期/停用硬拦）。

**退费矩阵**：

| 消费者 | 扣谁 | 退给谁 |
|---|---|---|
| 成员/管理员(1/3) | 企业钱包→团队长个人 | **团队长**(钱包份额原路回) |
| 散客(0) / 团队主(2) / 个人 | 自己 | 自己 |

### P3 智能体/知识库团队共享

规则：企业空间创建写 `team_id`；列表企业空间按 `team_id` 全员共享，个人空间只看自己；系统预置始终可见。

- `TeamContextService::currentTeamId`（到期成员视为退出企业空间）。
- 创建/列表：`KbRobotLogic` / `KbKnowLogic` / `KnowledgeLogic` 及对应 Lists。

### P4 扣费明细

- 个人明细：`AccountLogLists`（`source=team|personal`）。
- 企业消耗明细：`TeamConsumptionLists`（业务 DEC + 业务退回 INC，含 `status∈{1,2}` 与专用退费类型 9100/915x；剩余算力用流水 `left_tokens`）。
- 转账类（划拨/OEM/制卡等）走 `list_type=transfer`，不计入业务净消耗。

### P5 互相包含场景隔离

- `switchTeam` 切换当前企业；资源归属唯一 `team_id`。
- `TeamMemberService::assertActive`：到期以 **`team_member.expire_time` 为准**（勿只信 `user.team_expire_time` 镜像）。
- 过期成员：禁止切换进该企业、禁止企业控制台、禁止 IP 分析/报告等消费链路。

### P6 合并旧版 OEM

- `TeamLogic::resolveTenant`：团队 OEM 未命中 → 回落旧 `la_oem`。
- `/oem.oem/check` 统一入口（域名 + 小程序 appid）。
- 名额：`oemUsedQuota()` = 旧OEM数 + 已开通团队数；与站长授权名额联动。

---

## 4. 站点隔离与租户解析（必读）

### 4.1 `resolveTenant(domain, appid)`

优先级概要：

1. 域名命中已开通且启用的团队 OEM（`oem_status=2` + `status=1`）
2. 否则用小程序 `appid` 查 `mnp_setting.app_id`（`team_id>0`）
3. 未命中 → 关站落痕 / 旧版 `la_oem`
4. **主站渠道 AppID**：即使 Host 是 OEM 域，也按主站小程序解析（防 OEM 误填主站 AppID）

### 4.2 域名 / AppID 占用校验

保存 OEM 品牌时：

- 域名不得为主站域名（`assertDomainNotMainSite`）
- 小程序 AppID 不得为主站渠道 AppID（`assertMnpAppIdNotMainSite`）

### 4.3 邀请码站点隔离（`applyJoin`）

| 邀请目标 | 哪里能用 |
|---------|---------|
| 已开通 OEM（`oem_status=2`） | **仅**对应 OEM 站点（`requestSiteId === team.id`） |
| 免费团 | **主站始终可用**；OEM 站仅当团长归属该 OEM 站时可用 |

常见误报「当前站点不给用」：

1. 目标团队已被标成 `oem_status=2` 但未绑 `domain` → 主站加不了、也没有 OEM 域可加  
2. 历史逻辑曾把免费团挂到团长 `origin_team_id` 上误拦主站（已改为上表规则）

### 4.4 站点用户移除

列表口径：`origin_team_id=本企业` 或历史散客。  
移除须同口径，并在 `detachFromTeam` 时清除 `origin_team_id`，否则会出现「列表看得到、移除报不属于团队」。

---

## 5. 关键代码位置

| 模块 | 路径 |
|------|------|
| 用户端团队逻辑 | `app/api/logic/TeamLogic.php` |
| 用户端接口 | `app/api/controller/TeamController.php` |
| 后台团队 | `app/adminapi/logic/team/TeamLogic.php` |
| 计费 | `app/common/service/TeamBillingService.php` |
| 空间上下文 | `app/common/service/TeamContextService.php` |
| 到期拦截 | `app/common/service/TeamMemberService.php` |
| OEM check | `app/api/logic/oem/OemLogic.php` |
| 消耗明细 | `app/api/lists/team/TeamConsumptionLists.php` |
| 自检命令 | `app/common/command/TeamOemSelfCheck.php` |
| 升级 SQL | `public/update/v2.12.0.sql`（及后续 `v2.12.x_*.sql`） |

前端（`web-dev-version` / `web-master`）：

- PC：`pc/src/pages/team/**`、`pc/src/layouts/components/header/user.vue`
- 小程序：`uniapp/src/packages/pages/team/**`、`uniapp/src/packages/pages/user/user.vue`

---

## 6. 部署步骤

1. 拉取代码（建议分支含 v2.12.0 团队 OEM）。
2. 执行 SQL（表前缀 `la_` → 实际前缀）：
   - 主包：`public/update/v2.12.0.sql`（结构 + 菜单 + 存量回填；更新器可能只跑 DML，DDL 需库结构比对或手工执行）
   - 增量：同目录 `v2.12.0_*.sql` / `v2.12.1.sql` 等按需
3. 清缓存：`php think clear`（如有）。
4. 后台可见「团队管理」相关菜单即部署成功。

### SQL 增量约定

- 命名：`版本_简述_YYYYMMDD.sql` 或更新器约定的 `vX.Y.Z_*.sql`
- 文件头写清日期与一句话说明
- 更新器若过滤非 DML，DDL 须单独处理

---

## 7. 验证

### 7.1 自动自检（P2 计费/退费）

事务内模拟+回滚，**不改动任何数据**：

```bash
php think team:oem-selfcheck --member=<成员用户ID> [--guest=<散客用户ID>]
```

覆盖：可用算力口径 / 钱包优先扣 / 回落团队长 / 余额不足拦截 / 退费对称 / 异步退费归属 / 散客退回自己。

### 7.2 手动验证清单

**P3 共享+隔离**

1. 团队长企业空间创建智能体/知识库 → 成员同企业可见可用。  
2. 成员切个人空间 → 看不到该企业资源。

**P4 明细**

1. 企业空间消费 → 消耗明细有记录，`team_id` 正确，剩余为流水当时余额。  
2. 数字人/混剪退费（含 `status=2`、9100 等）计入净消耗。

**P5 隔离 / 到期**

1. 多企业切换消费扣对应团队长。  
2. 成员到期：不能进企业控制台/不能切进该企业；创建 IP / 分析报告被拦。

**P6 / 站点**

1. 旧 `la_oem` 域名 → `/oem.oem/check` 仍换肤。  
2. 团队 OEM 域名 → 返回团队品牌。  
3. 主站 AppID + OEM Host → `is_oem=0`。  
4. 免费团邀请码主站可加；OEM 企业邀请码须走对应域名。

---

## 8. 后续可选（需产品确认）

- Coze 智能体、AI 人设是否纳入团队共享。  
- 会员配额统计是否按 `team_id`。  
- 团队知识库 edit/del 是否放开给全体成员。  
- 旧 `la_oem` 迁入 `la_team` 并废弃。  
- 散落内联 `$user->tokens < X` 预检是否全部改为团队感知（`deduct` 已兜底）。

---

## 9. 修订记录

| 日期 | 说明 |
|------|------|
| 2026-07 | 初版：P0–P6 实现说明 |
| 2026-08 | 迁入 `server/docs/`；补充 origin/站点隔离、AppID/域名禁占主站、到期拦截、消耗明细口径、邀请码规则与 OEM 无域名误配说明 |
