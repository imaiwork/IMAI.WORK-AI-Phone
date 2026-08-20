# IMAI PC AI 代码生成规则

本文件是 `pc` 项目的 AI 规则。之后在本项目生成、修改、重构、排查问题、评审或新增页面时，必须优先遵守本文件；当本文件与用户明确的新需求冲突时，以用户需求为准，但需要说明偏离点。

## 0. 执行前置规则

- 每次在本 `pc` 项目中执行代码生成、修改、重构、排查问题、评审或新增页面前，AI 必须先阅读本文件 `AGENTS.md`。
- 每次生成、修改或重构前端代码前，AI 必须同时阅读并遵守仓库根目录 `../SENIOR_FRONTEND_ENGINEER.md`，按前端高级开发工程师标准处理数据流、组件设计、性能优化、接口状态和可维护性。
- 开始具体实现前，必须基于本文件确认当前任务需要遵守的技术栈、目录边界、UI 风格、样式风格、请求封装、组件拆分和质量检查规则。
- 如果任务需要偏离本文件规则，必须在执行前或最终说明中明确说明偏离原因。

## 1. 项目技术栈

- 项目是基于 `Nuxt 3`、`Vue 3`、`TypeScript` 的 PC Web 前台项目，`nuxt.config.ts` 中 `srcDir` 指向 `src/`。
- UI 生态以 `Element Plus`、项目自有组件、Tailwind CSS、SCSS 为主。
- 状态管理使用 `Pinia`，全局状态放在 `src/stores`。
- 请求层基于 `ofetch` 封装在 `src/utils/http`，通过 `src/plugins/fetch.ts` 注入全局 `$request`，普通接口函数放在 `src/api`。
- SVG 图标通过 `vite-plugin-svg-icons` 从 `src/assets/icons` 注册，本地图标命名遵循 `local-icon-[dir]-[name]`。
- 项目包含富文本、Markdown、思维导图、文件解析、音视频、二维码、拖拽、图表等能力；新增功能前先查找已有组件、composable、plugin 和工具函数。

## 2. 目录与模块边界

- `src/pages` 放 Nuxt 页面。页面私有资源优先使用同目录 `_components`、`_composables`、`_hooks`、`_enums`、`_assets`、`_typings`。
- `src/components` 放跨业务复用组件，业务私有组件优先放回对应页面或模块目录。
- `src/api` 只放接口函数，不写页面状态和 UI 逻辑。
- `src/composables` 放全局组合式函数，命名必须是 `useXxx`；模块私有逻辑优先放模块自己的 `_composables` 或 `_hooks`。
- `src/stores` 放 Pinia store；用户、应用配置、登录态等全局状态不要散落在页面中。
- `src/utils` 放请求、环境、反馈、校验、缓存、文件、事件等基础能力。
- `src/enums` 放跨模块枚举和常量；模块私有枚举放模块目录的 `_enums`。
- `src/assets/styles` 放全局样式、变量和 Tailwind 入口；页面样式优先局部 scoped。
- 不要修改 `.nuxt`、`.output`、`dist`、`node_modules` 或生成文件，除非用户明确要求。

## 3. 代码风格

- 新增 Vue 单文件组件默认使用 `<script setup lang="ts">`。维护旧组件时保持局部一致，不为了风格统一重写无关代码。
- 单文件组件结构优先使用：

```vue
<template></template>

<script setup lang="ts"></script>

<style lang="scss" scoped></style>
```

- 遵守 `.eslintrc.cjs` 中的 Prettier 规则：4 空格缩进、单引号、无分号、`trailingComma: none`。
- 使用 `@/` 引入 `src` 内模块；Nuxt 特殊场景可沿用现有 `~~/src` 写法；同目录局部组件、composable 可用相对路径。
- 变量和函数命名使用清晰英文语义：事件处理 `handleXxx`，查询列表 `queryXxxList` / `getXxxList`，提交动作 `handleSubmit` / `submitXxx`，弹窗状态 `showXxxPopup`，请求参数 `queryParams` / `formData`。
- TypeScript 不强制完全消灭 `any`，但稳定业务结构必须补类型；接口字段兼容优先在归一化函数中处理。
- Props 和 Emits 优先类型化；组件对外状态优先使用 `modelValue` / `update:modelValue`。
- 注释只解释复杂业务、非显然兼容逻辑、SSR/客户端差异或性能原因，不给普通赋值和点击事件写空注释。

## 4. UI 风格

- 这是 PC 端 AI 工具与业务工作台产品，界面应克制、清晰、可扫描，优先服务高频操作和信息密度，不做营销式大视觉。
- 页面布局优先复用 `src/layouts`、`src/layouts/components`、现有页面结构和公共组件。
- 表单、表格、弹窗、抽屉、分页、上传、日期选择、空状态优先使用 Element Plus 或项目已有组件。
- 页面主体常用浅色工作台背景，内容容器白底或极浅灰底，主品牌动作使用 Tailwind / Element Plus 的 `primary` 语义色。
- 字号优先使用项目 Tailwind 字号：`text-xs`、`text-sm`、`text-base`、`text-lg`、`text-xl`；紧凑面板内不要使用过大的标题。
- 间距、圆角、阴影优先使用项目 Tailwind 主题或 Element Plus 变量，不新增另一套色板和阴影系统。
- 图标优先使用 Element Plus 图标、`src/assets/icons` 中的本地 SVG 或现有 `icon` 组件，不随意引入风格不一致的新图标。
- 文本必须考虑长内容：标题、文件名、知识库名称、提示词等需要 `line-clamp`、省略、换行或 `break-all`。
- 列表、表格、卡片、工具栏按钮必须有加载、禁用、空状态、错误反馈或提交中状态，不只做静态视觉。
- 不要卡片套卡片；页面区块应自然铺开，重复条目才使用卡片。

## 5. 样式规则

- 优先用 Tailwind 原子类完成布局、间距、颜色、字体、圆角、边框；复杂状态、伪元素、动画、深层组件覆盖再使用 scoped SCSS。
- Tailwind 主题桥接 Element Plus 变量，主色和语义色优先使用 `primary`、`success`、`warning`、`danger`、`error`、`info`、`page`、`tx-*`、`br-*` 等现有语义。
- 不要引入新的 CSS 框架，不要在页面内重复定义大段色板。
- Element Plus 深层覆盖必须收敛在组件局部，并尽量用 `:deep()` 精准命中，不写影响全站的宽泛选择器。
- 固定尺寸 UI（工具栏、图标按钮、列表项、上传卡、聊天消息操作区）要有稳定宽高或约束，避免加载态和文本变化导致布局跳动。
- 不要写大段内联 style；需要动态样式时优先 computed class 或 CSS 变量。

## 6. Nuxt 与浏览器能力

- 涉及 `window`、`document`、`localStorage`、剪贴板、Canvas、音视频、文件下载、WebSocket 等浏览器能力时，必须考虑 Nuxt SSR：优先放在 `onMounted`、`.client.ts` 插件或 `process.client` / `import.meta.client` 保护中。
- 不要在服务端渲染路径直接访问 DOM 或浏览器专属对象。
- 路由跳转优先使用 Nuxt / Vue Router 现有方式，新增页面必须符合 `src/pages` 的文件路由规则。
- 插件能力优先放在 `src/plugins`，客户端专用插件使用 `.client.ts` 或显式客户端保护。
- 页面 meta、鉴权、布局切换先查找 `src/middleware`、`src/layouts`、`src/stores/user.ts` 和现有页面写法。

## 7. 请求、状态与业务逻辑

- 所有普通接口必须通过 `src/utils/http` 封装和全局 `$request` 调用，不要在页面里直接裸写 `fetch` 或 `$fetch`。
- 接口函数统一放在 `src/api/*.ts` 或对应业务已有 API 文件中：

```ts
export function getExampleList(params: any) {
    return $request.get({ url: '/example/lists', params })
}
```

- 流式接口优先复用 `request.eventStream` 或 `request.sse`，不要重复实现 SSE 解析层。
- 用户信息、登录态、站点配置等全局状态使用 Pinia store；不要在多个页面重复请求和缓存。
- 分页逻辑优先复用 `src/components/pagination` 或 `src/composables/usePaging`。
- 防重复提交优先使用 `useLockFn`。
- 用户反馈优先使用 `src/utils/feedback`、Element Plus Message/Loading 或现有 `v-spin` / `$spin` 能力；异步 Loading 必须在 `finally` 中关闭。
- 请求需要考虑取消、重复请求、路由切换和旧请求晚返回覆盖新状态的问题。

## 8. 组件设计

- 页面负责请求、状态和业务编排；组件负责展示和局部交互；composable 负责可复用副作用。
- 单个页面或组件代码变长时，优先拆分为局部 `_components`，不要把复杂表单、弹窗、列表项、上传区全部堆在一个 `.vue` 文件中。
- 页面内重复 2 次以上且有业务意义的 UI 或逻辑，应抽成局部组件、composable 或纯函数。
- 通用组件通过 props 输入、emit 输出，不直接依赖具体页面变量或全局状态，除非它本来就是全局基础能力。
- 弹窗、确认框、上传、复制、拖拽、分页、Markdown、音视频预览等先复用现有组件和插件。
- 表单组件必须处理初始值、回显、校验、提交中状态、失败提示和关闭后的状态复位。

## 9. 常量与魔法值

- 同一字符串或数字在同一文件出现 2 次以上，或同一业务含义跨文件出现，必须抽成 `enum`、`const` 或 `Record`。
- 后端返回的状态码、业务标识、类型字段放到 `src/enums` 或模块 `_enums`，不要在模板和事件函数里散落字符串比较。
- API URL 放在 `src/api` 接口函数里，不要在页面调用处拼接。
- 抽常量遵循就近原则：单文件用放文件顶部，模块复用放模块 `_enums`，全局通用才放 `src/enums`。

## 10. 质量检查

- 修改完成后优先运行与改动相关的局部检查；必要时运行 `npm run build` 或 `npm run dev` 做页面验证。
- 涉及 UI 交互时必须自检打开、确认/保存、取消/关闭、切换、路由离开再返回等完整路径。
- 涉及请求时必须自检 loading、空状态、错误状态、重复点击、旧请求覆盖和登录失效。
- 最终说明聚焦关键改动、验证结果和未验证风险，不堆无关细节。
