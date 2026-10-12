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
    └── screenshots/                  已脱敏的界面展示图
        ├── 01-admin-device-redacted.png
        └── 02-matrix-publishing-redacted.png
```

数字前缀用于同类图片排序；`redacted` 表示已遮盖敏感信息。介绍图仅使用通用图形与项目标识，不包含第三方平台图标。

## 截图来源与处理

截图采集于 **2026-10-12**，来源为用户登录后的 [PC 演示站](https://demo.imai.work/pc/) 和 [管理后台](https://demo.imai.work/admin/)。后台设备图遮盖头像、昵称、设备标识和后台账号；发布入口图遮盖账户余额与头像区域。

截图经过图像工具脱敏、缩放或重绘，仅用于展示界面布局。演示站显示 v2.13.4，本地前端依赖文件标记 v2.13.2。图片不作为原始像素记录、设备执行成功或端到端验证的证据。

仓库仅保存脱敏后的最终图片。更新图片时应先检查用户、设备、账号、余额及其他敏感信息，并确认没有第三方平台图标，再替换对应文件。

## 介绍图与源码说明

介绍图用于概括项目组成，具体协议、状态定义和实现细节以主 README 及所引用源码为准。Phone Agent 的多轮观察与动作执行链路由主 README 中的 Mermaid 图单独说明。

生成与处理提示词记录在 `image-prompts.json`，包括移除第三方平台图标的修正要求。
