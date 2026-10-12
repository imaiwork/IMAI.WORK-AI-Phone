# 文件变更日志

用于追溯 `pc` 子项目的文件变更记录。每条记录包含：变更日期、文件、变更人、变更说明。

| 变更日期 | 文件 | 变更人 | 变更说明 |
| --- | --- | --- | --- |
| 2026-09-23 | `.env.development.example`、`src/api/account.ts` | my | 开发环境补 `NUXT_CLIENT=4`；登录与注册在客户端类型为空时回退为 PC 终端 `4`，避免请求体丢掉 `terminal` / `channel` 后报「终端参数缺失」。本地 `.env.development` 同步补了该变量（该文件不入库）。 |
| 2026-09-22 | `src/composables/useDeviceWs.ts` | my | bindSocket 上报 content 增加登录 token；连接未建立、无 userId 或 token 为空时不发送。 |

## 说明

- 变更人取自 git 配置 `user.name`（`my`），如有正式姓名可自行替换。
- 本日志按「一次提交 / 一次功能变更」为粒度手动维护，新变更请追加到表格顶部（最新在上）。
