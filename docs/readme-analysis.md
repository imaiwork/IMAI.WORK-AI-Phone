# README 技术化调整分析

分析日期：2026-10-12。依据当前本地代码及用户授权访问的演示站页面。技术版已替换到根目录的 `README.md`，图片统一整理到 `docs/assets/`，索引见 `docs/README.md`。下文“现有 README 的主要问题”描述的是调整前版本。

## 建议定位

将首页定位调整为“面向手机任务执行与业务流程协作的 AI 自动化平台”，围绕设备、账号、任务、通信、结果记录组织介绍。让读者先理解系统的组成与执行机制，再理解内容发布、客户跟进等应用场景。

项目值得强调的技术主线是：多端交互入口 → 服务端业务逻辑与任务调度 → 设备通信 → 执行端上报 → 状态与业务记录。

进一步检查后确认还存在独立 Phone Agent 服务端实现。技术版将“按计划调度的业务任务”与“观察、模型决策、动作执行、结果反馈的多轮任务”分别说明，并新增动作白名单、状态定义、执行事件、模型接口及用量处理。项目定位应同时覆盖这两条链路。

## 现有 README 的主要问题

| 内容 | 当前问题 | 调整建议 |
| --- | --- | --- |
| 首屏 | Star 激励、宣传图片和路演信息先于项目定位 | 首屏放项目名称、技术简介及架构入口，社区信息移到末尾 |
| 项目介绍 | 宣称 OpenClaw 驱动，但当前代码未找到足够实现依据 | 描述已确认的服务端编排与设备通信，待执行端证据补齐后再加入 OpenClaw |
| 核心逻辑 | 以替代人工及效率百分比说明 | 改为任务持久化、时间窗口、指令下发、结果回传 |
| 技术优势 | 成本、收益及“永不封号”缺乏本次分析可核验依据 | 改为模块职责、通信方式、任务状态和接入条件 |
| 技术徽章 | MongoDB、Java 17、UiPath 未找到当前项目实现依据 | 使用实际依赖；补 ThinkPHP 8、Nuxt 3、Workerman、PostgreSQL/pgvector |
| 快速开始 | 克隆目录名与后续 cd 不一致；根目录缺 `.env.example` | 按四个子项目分别说明；服务端模板实际为 `server/.example.env` |
| 子目录说明 | 服务端 README 仍为 ThinkPHP 6/PHP 7.1 的框架模板 | 以 Composer 声明为准，后续应同步清理模板文档 |
| 图片链接 | 部分图片使用包含临时签名的链接 | 使用仓库内的稳定相对路径资产 |
| English 链接 | 根目录未找到对应 `README-EN.md` | 在实际补齐英文文档前移除该入口 |
| 仓库链接 | clone、Issues、Star History 使用的仓库标识不一致 | 发布前确认官方仓库地址并统一链接 |

“未找到依据”指本次检查的实现范围，不能反向证明其他仓库或未提供执行端不具备该能力。

## 主要源码依据

- `server/composer.json`：PHP ≥ 8.0、ThinkPHP ^8.0.2、Think Queue、Workerman、Channel 等依赖。
- `pc/package.json`：Nuxt 3.6.5、Element Plus、TypeScript、PC 端 Node.js 要求。
- `admin/package.json`：Vue 3、Vite、Element Plus。
- `uniapp/package.json`：uni-app、Vue 3 及多目标构建脚本。
- `server/app/common/command/WorkermanServie.php`：RPA WebSocket、微信 WebSocket、设备 TCP 及 Channel 服务。
- `server/app/common/workerman/rpa/RpaSocketService.php`：消息处理、连接管理、跨进程路由与定时检查。
- `server/app/common/command/DeviceTaskScheduler.php`：时间窗口调度、超时检查、运行锁、分批处理及单轮时间限制。
- `server/app/common/model/sv/SvDeviceTask.php`：任务的团队归属记录。
- `server/config/queue.php`：Redis 默认队列及重试相关配置。
- `server/config/database.php`、`server/app/common/pgsql/KbEmbedding.php`、`server/public/install/db/pg.sql`：MySQL、PostgreSQL 和向量数据。
- `server/app/api/controller/device/ClawController.php`、`server/app/api/logic/device/ClawLogic.php`：设备信息、任务查询与账号同步接口；接口命名不能证明 OpenClaw 实现。
- `server/app/common/service/ToolsService.php`：外部服务请求、签名、回调和流式请求等接口机制。
- `server/app/api/controller/phoneAgent/PhoneAgentController.php`、`server/app/api/logic/phoneAgent/PhoneAgentLogic.php`、`server/app/api/validate/phoneAgent/PhoneAgentValidate.php`：任务规划、下发、查询、事件及取消接口。
- `server/app/common/service/phoneAgent/`：规划、观察上下文、模型请求、动作解析、状态推进、指令下发及用量处理。
- `server/app/common/model/phoneAgent/PhoneAgentTask.php`：Phone Agent 的字符串状态及终态定义。
- `server/app/common/workerman/rpa/WorkerEnum.php`：观察、执行、报告、取消等协议消息编号。
- `server/app/common/service/recall/RecallKnow.php`、`RecallUtils.php`、`server/app/api/logic/kb/KbKnowLogic.php`：向量、全文、混合召回与结果融合过滤。
- `server/app/common/Jobs/`：媒体、微信及 GEO 等异步处理；重试规则由各 Job 决定。
- `server/app/api/http/middleware/LoginMiddleware.php`、`server/app/adminapi/http/middleware/AuthMiddleware.php`、`server/app/common/service/TeamContextService.php`：用户登录、后台权限及团队上下文。
- `pc/scripts/build.mjs`、`admin/scripts/release.mjs`、`uniapp/scripts/release.mjs`：构建产物复制到服务端 public 子目录，并先清理对应目标目录。
- `pc/src/pages/app/matrix/`、`pc/src/pages/app/person_wechat/sop/`：发布与 SOP 的前端业务入口。

## 演示站与截图边界

- 已由用户完成前后台登录，并实际查看对应页面。
- 演示站显示 v2.13.4，本地前端依赖文件显示 v2.13.2。
- 当前前台账号的 AI 终端设备列表为空，不能以此评价平台是否具备设备执行能力。
- 后台设备列表存在数据；选用图片已遮盖昵称、头像、设备标识和后台账号区域。
- 矩阵发布入口已遮盖账户余额与头像区域。
- 没有创建任务、发送消息、提交生成请求、购买或修改配置。
- 介绍图依据源码梳理生成；界面图经过 AI 图像工具脱敏处理，可能有缩放或像素重绘，仅用于布局展示，不是像素级原始截图或执行验证材料。
- 未验证完整安装、实际设备任务执行、生成服务或外部模型接口。

## 图片与生成方式

三张介绍图和两张脱敏界面图使用内置 `image_gen` 工具生成或处理。最终介绍图没有第三方平台图标；技术名称保留文字说明。完整提示词保存在 `docs/image-prompts.json`，其中包含第三方图标移除修正及截图遮盖要求。

最终资产：

- `docs/assets/overview/01-architecture.png`
- `docs/assets/overview/02-task-workflow.png`
- `docs/assets/overview/03-capabilities.png`
- `docs/assets/screenshots/01-admin-device-redacted.png`
- `docs/assets/screenshots/02-matrix-publishing-redacted.png`

未脱敏原图未作为项目交付资产写入仓库目录。
