# 技术文档与图片索引

[项目技术介绍](../README.md) · [源码分析与编写说明](./readme-analysis.md) · [图像生成与脱敏提示词](./image-prompts.json)

## 图片索引

所有图片均为仓库内 PNG 文件，主 README 通过相对路径引用，不依赖外部图片地址或临时签名链接。

| 类型 | 文件 | 尺寸 | 用途 |
| --- | --- | --- | --- |
| 介绍图 | [01-architecture.png](./assets/overview/01-architecture.png) | 1672 × 941 | 系统分层、技术组成与组件职责 |
| 介绍图 | [02-task-workflow.png](./assets/overview/02-task-workflow.png) | 1672 × 941 | 业务任务编排与执行流程概览 |
| 介绍图 | [03-capabilities.png](./assets/overview/03-capabilities.png) | 1672 × 941 | 设备、任务、内容、知识库与客户流程 |
| 脱敏截图 | [01-admin-device-redacted.png](./assets/screenshots/01-admin-device-redacted.png) | 1554 × 1012 | 后台设备管理界面 |
| 脱敏截图 | [02-matrix-publishing-redacted.png](./assets/screenshots/02-matrix-publishing-redacted.png) | 1381 × 1139 | PC 社媒内容发布入口 |
| 原版社区资料 | [01-community-invite.png](./assets/community/01-community-invite.png) | 1876 × 1074 | 官方交流群邀请图及入群二维码 |
| 原版社区资料 | [02-roadshow-qrcode.png](./assets/community/02-roadshow-qrcode.png) | 400 × 400 | 原项目资料中的《赢在 AI》路演二维码 |

## 目录与命名

```text
docs/
├── README.md                         文档与图片索引
├── readme-analysis.md                源码依据与调整说明
├── image-prompts.json                生成和脱敏提示词
└── assets/
    ├── overview/                     技术介绍图
    │   ├── 01-architecture.png
    │   ├── 02-task-workflow.png
    │   └── 03-capabilities.png
    ├── screenshots/                  已脱敏的界面展示图
    │   ├── 01-admin-device-redacted.png
    │   └── 02-matrix-publishing-redacted.png
    └── community/                    原版交流群与路演资料
        ├── 01-community-invite.png
        └── 02-roadshow-qrcode.png
```

数字前缀用于同类图片排序；`redacted` 表示已遮盖敏感信息。`overview/` 下的介绍图仅使用通用图形与项目标识，不包含第三方平台图标。

## 截图来源与处理

截图采集于 **2026-10-12**，来源为用户登录后的 [PC 演示站](https://demo.imai.work/pc/) 和 [管理后台](https://demo.imai.work/admin/)。后台设备图遮盖头像、昵称、设备标识和后台账号；发布入口图遮盖账户余额与头像区域。

截图经过图像工具脱敏、缩放或重绘，仅用于展示界面布局。演示站显示 v2.13.4，本地前端依赖文件标记 v2.13.2。图片不作为原始像素记录、设备执行成功或端到端验证的证据。

`assets/screenshots/` 仅保存脱敏后的最终界面图。更新界面图或介绍图时，应先检查敏感信息及第三方平台图标，再替换对应文件；公开的社区二维码按用户要求保留原样。

## 介绍图与源码说明

介绍图用于概括项目组成，具体协议、状态定义和实现细节以主 README 及所引用源码为准。Phone Agent 的多轮观察与动作执行链路由主 README 中的 Mermaid 图单独说明。

生成与处理提示词记录在 `image-prompts.json`，包括移除第三方平台图标的修正要求。

## 原版社区与路演资料

按用户要求保留原 README 的交流群邀请图、Star 支持说明、常用入口及路演二维码，位于主 README 前部。两张原图保存到 `assets/community/`，未经 AI 重绘或重新生成，保留原二维码像素。

来源为原 README 引用的 GitHub 附件（社区图标识 `c48dae80-089b-45d0-823e-ec45e80dcef3`，路演二维码标识 `5699dc59-4477-4547-b85f-99c915f184ad`）。原来带临时签名的图片链接和失效的交流群图片入口均替换为本地资产引用。

此处保存原项目公开资料；本次未验证二维码对应页面的有效期或内容。二维码不是自动化执行证据。
