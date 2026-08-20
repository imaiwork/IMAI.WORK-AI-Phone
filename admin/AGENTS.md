# IMAI Admin AI 代码生成规则

本文件是 `admin` 项目的 AI 规则。之后在本项目生成、修改、重构、排查问题、评审或新增页面时，必须优先遵守本文件；当本文件与用户明确的新需求冲突时，以用户需求为准，但需要说明偏离点。

## 0. 执行前置规则

- 每次在本 `admin` 项目中执行代码生成、修改、重构、排查问题、评审或新增页面前，AI 必须先阅读本文件 `AGENTS.md`。
- 每次生成、修改或重构前端代码前，AI 必须同时阅读并遵守仓库根目录 `../SENIOR_FRONTEND_ENGINEER.md`，按前端高级开发工程师标准处理数据流、组件设计、性能优化、接口状态和可维护性。
- 开始具体实现前，必须基于本文件确认当前任务需要遵守的技术栈、目录边界、UI 风格、样式风格、请求封装、路由权限、组件拆分和质量检查规则。
- 如果任务需要偏离本文件规则，必须在执行前或最终说明中明确说明偏离原因。

## 1. 项目技术栈

- 项目是基于 `Vite`、`Vue 3`、`TypeScript` 的管理后台项目。
- UI 生态以 `Element Plus`、项目自有组件、Tailwind CSS、SCSS 为主。
- 路由使用 `vue-router`，路由与权限相关逻辑在 `src/router`、`src/router/guard`、`src/permission.ts`。
- 状态管理使用 `Pinia`，全局状态放在 `src/stores` 和 `src/stores/modules`。
- 请求层基于 `axios` 封装在 `src/utils/request`，普通接口函数放在 `src/api`。
- 自动导入使用 `unplugin-auto-import`，组件自动注册使用 `unplugin-vue-components` 和 `ElementPlusResolver`。
- SVG 图标通过 `vite-plugin-svg-icons` 从 `src/assets/icons` 注册，本地图标命名遵循 `local-icon-[dir]-[name]`。

## 2. 目录与模块边界

- `src/views` 放后台页面，按业务域组织；页面私有组件优先放在当前 view 的 `components` 目录。
- `src/components` 放跨业务复用组件，业务私有组件不要提升到全局组件目录。
- `src/api` 只放接口函数，不写页面状态和 UI 逻辑。
- `src/hooks` 放全局组合式函数，命名必须是 `useXxx`。
- `src/stores` 放 Pinia store；用户、权限、标签页、应用配置等全局状态不要散落在页面中。
- `src/router` 放路由定义和守卫，新增菜单页面必须同步确认路由、权限和菜单来源。
- `src/utils` 放请求、反馈、缓存、权限、校验、文件、主题等基础能力。
- `src/enums` 放跨模块枚举和常量；业务私有枚举优先放业务目录内。
- 不要修改 `dist`、`node_modules`、`auto-imports.d.ts`、`components.d.ts` 或生成文件，除非用户明确要求。

## 3. 代码风格

- 新增 Vue 单文件组件默认使用 `<script setup lang="ts">`。维护旧组件时保持局部一致，不为了风格统一重写无关代码。
- 单文件组件结构优先使用：

```vue
<template></template>

<script setup lang="ts"></script>

<style lang="scss" scoped></style>
```

- 遵守 `.eslintrc.cjs` 中的 Prettier 规则：4 空格缩进、单引号、无分号、`printWidth: 100`、`trailingComma: none`。
- 使用 `@/` 引入 `src` 内模块；相邻同目录组件和 hook 可用相对路径。
- 变量和函数命名使用清晰英文语义：事件处理 `handleXxx`，查询列表 `queryXxxList` / `getXxxList`，提交动作 `handleSubmit` / `submitXxx`，弹窗状态 `showXxxPopup`，请求参数 `queryParams` / `formData`。
- TypeScript 不强制完全消灭 `any`，但稳定业务结构必须补类型；接口字段兼容优先在归一化函数中处理。
- Props 和 Emits 优先类型化；组件对外状态优先使用 `modelValue` / `update:modelValue`。
- 注释只解释复杂业务、权限规则、非显然兼容逻辑或性能原因，不给普通赋值和点击事件写空注释。

## 4. UI 风格

- 这是管理后台，界面应安静、紧凑、清晰、可扫描，优先支持配置、审核、管理、检索和批量操作。
- 页面布局优先复用 `src/layout`、`src/layout/default`、现有 view 的结构和公共组件。
- 表单、表格、弹窗、抽屉、分页、上传、日期选择、空状态优先使用 Element Plus 或项目已有组件。
- 页面主体常用 `page` 背景，内容容器白底或浅色背景，主品牌动作使用 Tailwind / Element Plus 的 `primary` 语义色。
- 字号优先使用项目 Tailwind 字号：`text-xs`、`text-sm`、`text-base`、`text-lg`、`text-xl`；后台面板内不要使用夸张标题。
- 间距、圆角、阴影优先使用项目 Tailwind 主题或 Element Plus 变量，不新增另一套色板和阴影系统。
- 图标优先使用 Element Plus 图标、`src/assets/icons` 中的本地 SVG 或现有 `icon` 组件。
- 文本必须考虑长内容：用户昵称、菜单名、知识库名、文件名、提示词等需要省略、换行或 `break-all`。
- 表格、分页、筛选、批量操作、导入导出必须有加载、禁用、空状态、错误反馈或提交中状态。
- 不要卡片套卡片；页面区块应自然铺开，重复条目才使用卡片。

## 5. 样式规则

- 优先用 Tailwind 原子类完成布局、间距、颜色、字体、圆角、边框；复杂状态、伪元素、动画、深层组件覆盖再使用 scoped SCSS。
- Tailwind 主题桥接 Element Plus 变量，主色和语义色优先使用 `primary`、`success`、`warning`、`danger`、`error`、`info`、`page`、`tx-*`、`br-*` 等现有语义。
- 不要引入新的 CSS 框架，不要在页面内重复定义大段色板。
- Element Plus 深层覆盖必须收敛在组件局部，并尽量用 `:deep()` 精准命中，不写影响全站的宽泛选择器。
- 固定尺寸 UI（工具栏、图标按钮、表格操作列、上传卡、状态标签）要有稳定宽高或约束，避免加载态和文本变化导致布局跳动。
- 不要写大段内联 style；需要动态样式时优先 computed class 或 CSS 变量。

## 6. 路由、权限与后台能力

- 新增后台页面必须同步确认路由、菜单、权限码、按钮权限和面包屑/标签页行为。
- 鉴权和登录失效逻辑遵循 `src/permission.ts`、`src/router/guard`、`src/utils/auth.ts` 和请求拦截器，不在页面里重复实现。
- 权限判断优先复用 `src/hooks/useAuth.ts`、现有指令或已有页面写法。
- 涉及 `window`、`document`、下载、剪贴板、Canvas、音视频等浏览器能力时，先查找现有 `utils`、组件或 hook。
- 富文本、Markdown、文件预览、上传、导入导出、拖拽排序、图表等能力优先复用现有组件和工具。

## 7. 请求、状态与业务逻辑

- 所有普通接口必须通过 `src/utils/request` 封装调用，不要在页面里直接裸写 `axios` 或 `fetch`。
- 接口函数统一放在 `src/api/*.ts` 或对应业务已有 API 文件中：

```ts
import request from '@/utils/request'

export function getExampleList(params: any) {
    return request.get({ url: '/example/lists', params })
}
```

- 用户信息、登录态、权限、菜单、标签页等全局状态使用 Pinia store；不要在多个页面重复请求和缓存。
- 分页逻辑优先复用 `src/components/pagination` 或 `src/hooks/usePaging`。
- 防重复提交优先使用 `useLockFn`。
- 用户反馈优先使用 `src/utils/feedback`、Element Plus Message/Loading 或现有组件；异步 Loading 必须在 `finally` 中关闭。
- 请求需要考虑取消、重复请求、路由切换、旧请求晚返回覆盖新状态和登录失效。

## 8. 组件设计

- 页面负责请求、状态和业务编排；组件负责展示和局部交互；hook 负责可复用副作用。
- 单个页面或组件代码变长时，优先拆分为局部 `components`，不要把复杂表单、弹窗、表格操作和导入导出全部堆在一个 `.vue` 文件中。
- 页面内重复 2 次以上且有业务意义的 UI 或逻辑，应抽成局部组件、hook 或纯函数。
- 通用组件通过 props 输入、emit 输出，不直接依赖具体页面变量或全局状态，除非它本来就是全局基础能力。
- 弹窗、确认框、上传、分页、导入导出、富文本、Markdown、音视频预览等先复用现有组件和插件。
- 表单组件必须处理初始值、回显、校验、提交中状态、失败提示和关闭后的状态复位。

## 9. 常量与魔法值

- 同一字符串或数字在同一文件出现 2 次以上，或同一业务含义跨文件出现，必须抽成 `enum`、`const` 或 `Record`。
- 后端返回的状态码、业务标识、类型字段放到 `src/enums` 或业务目录常量文件，禁止在模板和事件函数里散落字符串比较。
- API URL 放在 `src/api` 接口函数里，不要在页面调用处拼接。
- 抽常量遵循就近原则：单文件用放文件顶部，模块复用放业务目录，全局通用才放 `src/enums`。

## 10. 质量检查

- 修改完成后优先运行与改动相关的局部检查；必要时运行 `npm run type-check`、`npm run lint` 或 `npm run build`。
- 涉及 UI 交互时必须自检打开、确认/保存、取消/关闭、切换、路由离开再返回等完整路径。
- 涉及请求时必须自检 loading、空状态、错误状态、重复点击、旧请求覆盖和登录失效。
- 最终说明聚焦关键改动、验证结果和未验证风险，不堆无关细节。
