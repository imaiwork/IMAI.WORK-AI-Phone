# CLAUDE.md — imai 工程约定

本文件记录跨功能的工程铁律，改代码前先读。规矩来自真实事故，别复踩。

## 1. `.env` 是 `parse_ini_file` 解析的（三条铁律）

ThinkPHP 用 `parse_ini_file($file, true, INI_SCANNER_RAW)` 读 `.env`，PHP7+ 里
`#` 并不是真正的注释符，`#` 行会被当内容解析。由此三条铁律：

1. **注释只写纯文字**：出现 `( ) | & ~ " [ ]` 等 ini 保留字符会直接 syntax error，
   **整个 `.env` 作废**——数据库配置全丢，症状是莫名的 `root@localhost 拒绝访问`。
2. **顶级键必须放在所有 `[段]` 之前**：键一旦出现在某个段之后就归属该段，
   `env('KEY')` 取不到（得用 `env('段.KEY')`）。往尾部追加配置时开一个自己的段。
3. **段内键用 `env('段.KEY')` 读**，如 `env('NOTIFY.TOKEN')`、`env('DATABASE.DATABASE')`。

改完 `.env` 用一行自检：`php -r "var_dump(parse_ini_file('.env', true, INI_SCANNER_RAW) !== false);"`

## 2. 免登录回调接口统一用 `CallbackTokenService`（全站共用回调令牌）

异步生成类功能（视频/音频/数字人等）的回调接口必须免登录——上游服务器直调，
没有用户会话，等于公网敞开的门。不设防则任何人可伪造终态报文改任务状态、触发退款。

统一方案（`app/common/service/CallbackTokenService.php`）：

- 配置：`.env` 的 `[NOTIFY]` 段 `TOKEN` 键，各站自定随机长串（`openssl rand -hex 24`），
 令牌只在本站闭环、上游仅透传，无需与任何一方协调；不配置 = 回调通道关闭，
 纯靠定时任务兜底同步（功能不坏，状态更新慢一拍）。
 **新装站点由安装器自动生成**（`public/install/YxEnv.php::putEnv()`，2026-09-09 起；
 2026-09-14 起 `LEGACY_UNTIL` 也一并填成安装时刻 + 1 天的时间戳）；
 存量站点升级不重写 `.env`，仍需站长按 2.1 的顺序手动开启。
- 发出回调地址：`CallbackTokenService::appendTo($url)`（令牌未配置返回空串，表示不开启）。
- 校验回调：`CallbackTokenService::verify($this->request->get('token'))`，
  内部 `hash_equals` 防时序攻击；未配置令牌恒拒。
- **新增免登录回调接口一律接入本服务，禁止各自造轮子或裸奔**；存量未防护的
  notify 接口（闪剪/数字人等）逐步迁移接入。
- 接入示例见 `app/api/controller/PlayVideoController.php::notify`。

### 2.1 存量接口迁移用 `guard`，不要直接换成 `verify`

`verify()` 在令牌未配置时恒拒。新接口无所谓（本来就没发出过回调地址），
但存量接口上线前一直靠回调推业务，直接换 `verify` 等于「站长没改 .env 就把回调通道关了」。
迁移一律用 `CallbackTokenService::guard($日志通道, $接口名, $token)`，三档判定见 `judge()`：

| 本站 `NOTIFY.TOKEN` | 请求带的令牌 | 结果 |
| --- | --- | --- |
| 未配置 | 任意 | 放行（与接入前行为一致，写 info 日志） |
| 已配置 | 正确 | 放行 |
| 已配置 | 错误 | 拒绝 |
| 已配置 | 没带 | `NOTIFY.LEGACY_UNTIL` 宽限期内放行并写 warning，过期则拒绝 |

发出方对应传 `setRequestAndNotifyUrl(..., withToken: true)`（令牌未配置时回落成不带令牌的老地址）。
回调报文里的令牌写日志前用 `maskForLog()` 打码。
**`guard` 通过后立刻 `unset($data['token'])`**：令牌在 query 上，`$this->request->all()` 会把它并进 `$data`，
不剥掉就会跟着 `$data` 流进业务层——2026-09-09 审核发现 `AudioLogic::updateAudioInfo()` 把整包 `$data`
存进 `audio_info.response`，列表/详情接口又原样回传，等于任何登录用户转写一次就能拿到全站回调令牌。
控制器层打码日志挡不住这条路径，只有在入口剥掉才彻底。

**站长开启鉴权的顺序**：先在 `.env` 里把 `LEGACY_UNTIL` 填成「当前时间 + 1 天」，
再填 `TOKEN`，两个一起生效——在途任务提交时回调地址还没有令牌，靠宽限期兜住；
新任务从提交那一刻起就带令牌。宽限期到点自动收紧，不用回头再改配置。

**已接入**：闪剪视频 `notify` / 封面 `covernotify` / 音色 `notify`（2026-09-03）；
`/api/shanjian.shanjianAnchor/anchornotify`、`voicenotify`、`/api/videoImitation.task/notify`、
`/api/sora.*`、`/api/sv.*`（含 `clipnotify`）、`/api/human/notify`、`/api/human/clipnotify`、
`/api/draw.draw/notify`、`/api/audio/notify`、`/api/minimax.voice/asrnotify`（2026-09-09，
全部 `ToolsService` 免登录回调发出点已带 `withToken: true`；`clip()` 与画图 `MidPlatformDrawClient::buildNotifyUrl()`
是手拼地址，走 `appendTo()`）。至此 `api` 模块下除支付回调外的免登录 notify 已全部接入，
新增回调接口照 `PlayVideoController::notify` 用 `verify`。

⚠️ **拒绝一次回调 = 永久丢一次终态**：中台把 2xx 当投递成功（`fail()` 也是 HTTP 200），
不会重推；下一次只能等定时任务兜底。所以开鉴权前先确认该链路真有兜底轮询。当前各链路兜底：

| 链路 | 兜底 | 能否取回真实结果 |
| --- | --- | --- |
| 闪剪视频 | `ShanjianVideoTaskLogic::check()`（每 3 分钟，2h~24h 窗口）+ `ShanjianVideoSettingLogic::check()` 24 小时收口 + `checkOrphanTasks()` 孤儿扫尾 | 能（收口那两层只判失败退费） |
| 闪剪封面 | `checkCover()`（每 3 分钟，>30 分钟） | 能 |
| 闪剪音色 | `VoiceLogic::check()`（挂在 `shanjian_video_task`，>30 分钟查状态补偿 notify，>24h 标失败退费，2026-09-09 补） | 能 |
| Sora 视频/形象 | `SoraVideoTaskLogic::checkStatus()`、`SoraAnchorLogic::checkStatus()` / `checkVideoStatus()` | 能 |
| 爆款复刻 | `ShanjianQueueStatusCron` → `VideoImitationTaskLogic::handleQueueStatus()` | 能 |
| 批量生产文案 | `SvCopywritingTaskLogic::queryCopywritingCron()` | 能 |
| 数字人 | `HumanLogic::videoTaskCron()`（仅视频）+ `DigitalHumanLogic::*AnchorStatusCron()`（仅形象） | 部分：音色/音频无兜底 |
| 闪剪形象/形象音色、音频转文字、闪剪 ASR、批量生产数字人/剪辑、AI 画图 | **无**（画图仅详情页顺带 poll） | 否——回调被拒即永久丢终态 |

后六条链路（2026-09-09 接入 `guard`）目前没有轮询兜底：站长开鉴权时 **必须** 先填 `LEGACY_UNTIL`
（当前时间 + 1 天）再填 `TOKEN`，让在途任务靠宽限期过渡；宽限期过后被拒的只会是伪造报文或
配置错误。给这些链路补兜底轮询是后续项。

轮询走中台 `/api/shanjian/status`，场景码 `shanjian_status` 为 **free 计费（unit_price=0）**，
轮询本身不吃算力；该场景在中台 `scene_pricings` 里必须存在且 enabled，否则轮询直接 400。

`ShanjianVideoTaskLogic::check()` 曾自 2026-07-02（e84b0ade8，理由「以回调为准，轮询冗余」）
起被摘出定时任务、空转两个月，2026-09-03 随回调鉴权迁移重新挂回。重新挂回时补了**年龄上界**：
只捞 120~1440 分钟的任务，防止查无此单的老任务把 `order id asc + limit 3` 的名额占死。
**改这里的筛选条件时务必保留上界。**

### 2.2 收口三层各管一段，孤儿子任务单独扫尾

`ShanjianVideoSettingLogic::check()` 只按**父设置** `status IN (1,2)` 捞：父设置一旦被
`notify` 推成终态（3/4/5），底下没跑完的子任务就再也没人管——不标失败、不退费、
列表一直显示「生成中」。`checkOrphanTasks()` 反过来按子任务查已终态的父设置补这个口，
两条路径共用 `closeSettingUnfinishedTasks()`（同一份行锁 + 退费 + 父计数回写）。
收口只动过了 1440 分钟横线的子任务，父设置终态后新提交的重试任务仍归回调和轮询，不误杀。

⚠️ **查这张表的僵尸行必须带 `delete_time IS NULL`**：`ShanjianVideoTask` 是软删模型，
所有模型查询自带该条件。2026-09-03 测试库「40 条 `status=1` 僵尸」的口径漏了这个过滤——
那 40 条**全部是软删行**（多数 4~5 月就删了），应用侧任何地方都看不到、也不占任何 limit 名额；
按模型口径当时的活跃未完成子任务是 **0 条**。裸 SQL 统计前先加过滤，别把删除记录当事故。

## 2.3 任务失败退费统一走 `AccountLogLogic::refundTaskOnce()`

「回调判失败 / 轮询超时收口 → 按 task_id 退一次」这类退费，一律调
`AccountLogLogic::refundTaskOnce($userId, $changeType, $taskId)`，不要再手写
「`action=1` 条数 < `action=2` 条数才退」那五行（2026-09-09 已把 27 处手写替换掉）。
它在同样的幂等判定外加了一把 redis 锁：回调与轮询、两条 cron 同时判失败时不会各退一次；
退费主体仍由 `recordUserTokensLog(false)` 按原始扣费流水归属退回。
按金额差退的「结余退费」（爆款复刻、GEO、爆款玩法等）不属于这一类，保持各自逻辑。

## 3. 升级 SQL（`public/update/`）

- 当前开发版本的 SQL 文件在发布前可直接改（含改建表/INSERT 语句本身），
  不要往尾部叠 UPDATE 补丁；历史版本已存在的表才用可重入 ALTER 追加。
- SQL 注释与字段 COMMENT 用中性措辞，不出现内部架构称呼。
- INSERT 一律 `WHERE NOT EXISTS` 幂等；表前缀仓库写 `la_`，执行时按环境实际前缀换。
