# IMAI Uniapp AI 代码生成规则

本文件是本项目的全局 AI 规则。之后在本仓库生成、修改、重构代码时，必须优先遵守本文件；当本文件与用户明确的新需求冲突时，以用户需求为准，但需要说明偏离点。

## 0. 执行前置规则

-   每次在本 `uniapp` 项目中执行代码生成、修改、重构、排查问题、评审或新增页面前，AI 必须先阅读本文件 `AGENTS.md`。
-   每次生成、修改或重构前端代码前，AI 必须同时阅读并遵守仓库根目录 `../SENIOR_FRONTEND_ENGINEER.md`，按前端高级开发工程师标准处理数据流、组件设计、性能优化、接口状态和可维护性。
-   开始具体实现前，必须基于本文件确认当前任务需要遵守的 UI 风格、样式风格、代码风格、组件拆分、hook 抽取、跨端兼容和质量检查规则。
-   如果任务需要偏离本文件规则，必须在执行前或最终说明中明确说明偏离原因。

## 1. 项目技术栈

-   项目是基于 `uni-app`、`Vue 3`、`TypeScript` 的移动端多端应用，目标端包括 H5、App 和微信小程序。
-   构建工具是 `Vite`，路由使用 `uniapp-router-next` 和 `unplugin-uni-router`。
-   状态管理使用 `Pinia`，通用状态放在 `src/stores`。
-   UI 生态以 `vk-uview-ui` / `uView` 组件、`z-paging`、uni 原生组件为主。
-   样式体系是 Tailwind CSS + SCSS 混用，并通过 `weapp-tailwindcss` 适配小程序。
-   Vue、uni-app、Pinia 已通过 `unplugin-auto-import` 自动导入。新代码应尊重现有自动导入配置。

## 2. 目录与模块边界

-   `src/pages` 放主包页面。
-   `src/packages/pages` 放普通分包页面。
-   `src/ai_modules/*` 放 AI 业务模块，模块内可有自己的 `pages`、`components`、`hooks`、`stores`、`enums`、`static`。
-   `src/components` 放跨业务复用组件，模块私有组件优先放在对应模块目录。
-   `src/api` 只放接口函数，不写页面状态和 UI 逻辑。
-   `src/hooks` 放组合式函数，命名必须是 `useXxx`。
-   `src/utils` 放通用工具、请求、缓存、客户端判断等基础能力。
-   `src/enums` 放跨模块枚举和常量，模块私有枚举放模块内。
-   `src/static` 放全局静态资源，模块资源优先放模块自己的 `static`。
-   不要修改 `src/uni_modules`、`src/lib` 或生成文件，除非用户明确要求或修复必须触达。

## 3. 代码风格

-   新增 Vue 单文件组件默认使用 `<script setup lang="ts">`。维护旧 Options API 组件时可以保持局部一致，但不要在新功能里继续扩散 Options API。
-   单文件组件结构优先使用：

```vue
<template></template>

<script setup lang="ts"></script>

<style lang="scss" scoped></style>
```

-   遵守 `.eslintrc.js` 中的 Prettier 规则：4 空格缩进、单引号、无分号、`printWidth: 100`、`trailingComma: none`。
-   使用 `@/` 引入 `src` 内模块；相邻同目录组件和 hooks 可用相对路径。
-   变量和函数命名使用清晰英文语义：
    -   事件处理：`handleXxx`
    -   查询列表：`queryXxxList` / `getXxxList`
    -   提交动作：`handleSubmit` / `submitXxx`
    -   弹窗状态：`showXxxPopup`
    -   当前选中：`currXxx` 或 `activeXxx`
    -   请求参数：`queryParams` / `formData`
-   TypeScript 不强制完全消灭 `any`，但新业务结构稳定时必须补充接口类型；外部接口返回临时不稳定时可使用 `any`。
-   Props 和 Emits 优先类型化：

```ts
const props = withDefaults(defineProps<Props>(), {
    visible: false,
});

const emit = defineEmits<{
    (event: "update:visible", value: boolean): void;
    (event: "confirm"): void;
}>();
```

-   注释只解释复杂业务、平台差异、非显然兼容逻辑。不要给普通赋值、普通点击事件写无意义注释。

## 4. UI 风格

-   这是移动端 AI 工具类产品，界面应干净、克制、轻量、信息清晰，不做夸张营销式页面。
-   页面优先使用 `view`、`text`、`image`、`scroll-view`、`swiper`、`button` 等 uni 组件，不要随意使用浏览器专属标签。旧代码中已有 `div` 时可保留，但新代码优先用 `view`。
-   页面根容器常用模式：

```vue
<view class="h-screen flex flex-col bg-page">
    <u-navbar :border-bottom="false" :is-fixed="false" />
    <view class="grow min-h-0">
        <scroll-view scroll-y class="h-full">
            ...
        </scroll-view>
    </view>
</view>
```

-   列表页优先使用 `z-paging`，设置 `:fixed="false"`，需要底部安全区时设置 `:safe-area-inset-bottom="true"`。
-   页面主体背景优先使用 `bg-page`、`#F4F7FA`、`#EEF0F6`、`#F9FAFB` 这类浅灰蓝背景；内容卡片优先白底。
-   主品牌动作使用 Tailwind 主题色：`bg-primary`、`text-primary`、`border-primary`。不要随意新增一套主色。
-   语义色使用 Tailwind 主题：`success`、`warning`、`error`、`info`。需要透明度时优先使用现有色值或 Tailwind 透明语法。
-   字号优先使用项目 Tailwind 字号：`text-xs`、`text-sm`、`text-base`、`text-lg`，特殊尺寸使用 `text-[xxrpx]`。
-   尺寸、间距、圆角优先使用 `rpx`。卡片常用圆角区间是 `20rpx` 到 `30rpx`，底部弹窗可使用更大圆角。
-   使用紧凑、可扫描的卡片，不要卡片套卡片。页面区块应自然铺开，重复条目才使用卡片。
-   图标优先使用 `u-icon` 或现有 `/static/images/icons`、模块 `static/icons` 中的资源，不要无理由新增风格不一致的 SVG。
-   图片必须设置明确宽高和 `mode`，头像、封面等优先使用 `mode="aspectFill"`。
-   文本必须考虑移动端换行和截断：列表标题常用 `line-clamp-1`，说明文字常用 `line-clamp-2`，长文本需要 `break-all`。
-   固定底部操作区必须处理安全区：`pb-[calc(20rpx+env(safe-area-inset-bottom))]` 或同等方案。
-   按钮、菜单、列表项必须有清晰的加载、禁用、空状态或错误反馈，不要只做静态视觉。

## 5. 样式规则

-   优先用 Tailwind 原子类完成布局、间距、颜色、字体、圆角、边框。
-   当样式包含伪元素、复杂渐变、动画、状态组合、深层组件覆盖时，再使用 `<style lang="scss" scoped>`。
-   `<style lang="scss" scoped>` 中自定义 class 应优先通过 `@apply` 组合 Tailwind 工具类，避免把布局、间距、颜色、字体、圆角、边框等基础样式拆成大量传统 CSS 属性。
-   `box-shadow`、复杂背景渐变、关键帧动画、组件深层覆盖、Tailwind 当前无法表达或小程序转换不稳定的样式，可以保留传统 CSS 写法。
-   当前项目 Tailwind 主题覆盖了默认颜色，部分默认工具类不可用；透明色不要写 `bg-transparent`、`border-transparent`，应使用 `bg-[transparent]`、`border-[transparent]` 这类 arbitrary value 写法。
-   **单边边框必须先重置全边**：写 `border-t-[2rpx] border-solid border-[#F3F4F8]` 这类单边边框时，必须同时加 `border-[0]`（写在最前，如 `border-[0] border-t-[2rpx] border-solid border-[#F3F4F8]`），否则小程序端会四条边全部显示边框。四边同宽的 `border-[2rpx]` 不受影响，无需重置。
-   全局主题色从 `tailwind.config.js` 和 `src/uni.scss` 获取，不要在多个页面重复定义一套色板。
-   不要引入新的 CSS 框架。
-   不要在页面内写大段重复 SCSS；重复 2 次以上且有业务意义时抽成组件或局部 class。
-   小程序兼容优先，避免依赖 H5 only 的 CSS 能力；确需使用时必须加平台条件或降级方案。
-   不要使用 `px` 做移动端布局尺寸，除非是平台 API 返回像素或与触摸坐标相关。
-   `scroll-view` 不要直接写样式：padding、margin、背景、圆角、`flex`、间距等一律不要加在 `scroll-view` 上（小程序下会失效或破坏滚动）。`scroll-view` 上只允许写 `h-full`、`w-full` 这类尺寸约束类；需要内边距、布局、背景、间距等样式时，统一放到 `scroll-view` 内部的包裹 `view` 上。

## 6. 跨端与平台条件

-   涉及 `window`、`document`、`fetch`、`TextDecoder`、`plus`、微信 JS SDK 等平台能力时，必须使用条件编译保护：

```ts
// #ifdef H5
// H5 only
// #endif

// #ifdef APP-PLUS
// App only
// #endif

// #ifdef MP-WEIXIN
// WeChat Mini Program only
// #endif
```

-   优先使用 `uni.*` API 实现跨端能力，例如导航、缓存、上传、下载、Toast、Loading、系统信息。
-   不要在小程序路径中引入浏览器专属对象；不要在 H5 路径中直接使用 `plus`。
-   分享、登录、授权、支付、上传、录音、文件选择等能力必须先查找现有 `hooks`、`utils`、`mixins` 和模块实现。
-   图片、头像、视频、文件上传统一使用通用 hook `@/hooks/useUpload`（`useUpload` 默认导出），通过 `uploadAndProcessFiles('image' | 'video' | 'file' | 'all')` 触发选择并上传，在 `onSuccess(materials)` 中取 `materials[0].url` 等结果。禁止在页面里直接写 `uni.chooseImage`、`uni.chooseMedia`、`uni.uploadFile` 或裸调 `api/app` 的 `uploadImage` / `uploadFile`；确有特殊上传场景（如数字人视频转码）才复用对应模块内的专用 `useUpload`。
-   复制文本到剪贴板统一使用 `@/hooks/useCopy`：`const { copy } = useCopy()`，再调用 `copy(text)`。禁止在页面或组件里直接写 `uni.setClipboardData`。

## 7. 请求、状态与业务逻辑

-   所有普通接口必须通过 `src/utils/request` 封装调用，不要在页面里直接写 `uni.request` 或裸 `fetch`。
-   接口函数统一放在 `src/api/*.ts` 或对应业务已有 API 文件中：

```ts
import request from "@/utils/request";

export function getExampleList(data: any) {
    return request.get({ url: "/example/lists", data });
}
```

-   流式接口使用现有 `request.eventStream` 能力，不要重复实现 SSE 兼容层。
-   用户信息、登录态、网站配置等全局状态使用 Pinia store，不要在多个页面重复请求和缓存。
-   本地缓存使用 `src/utils/cache` 和 `src/enums/constantEnums` 中的 key。
-   防重复提交优先使用 `useLockFn`。
-   分页逻辑优先使用 `z-paging`；非组件分页可使用 `usePaging`。
-   用户反馈优先使用 `uni.$u.toast`、`uni.showLoading`、`uni.hideLoading`，异步 Loading 必须在 `finally` 中关闭。
-   页面导航优先使用 `router` / `router-navigate`，鉴权页面必须在 `pages.json` 的 `meta.auth` 中声明。

## 8. 页面与路由

-   新增页面必须同步维护 `src/pages.json`，主包放 `pages`，分包放对应 `subPackages`。
-   常规移动端页面优先使用 `navigationStyle: "custom"` 搭配 `u-navbar`，除非已有页面风格不是自定义导航。
-   需要分享的页面加 `meta.share: true`；需要登录的页面加 `meta.auth: true`；白名单页面加 `meta.white: true`。
-   长列表页面通常设置 `disableScroll: true`，由内部 `scroll-view` 或 `z-paging` 承接滚动。
-   页面文件命名使用小写加短横线或现有模块命名风格，组件文件使用语义化名称。

## 9. 组件设计

-   组件只承接明确的 UI 或业务片段，避免把整页状态塞进通用组件。
-   通用组件通过 props 输入、emit 输出，不直接依赖具体页面变量。
-   当单个页面或组件生成代码较多时，必须优先拆分为子组件再引入，避免把大量模板、弹窗、列表项、表单区块堆在一个 `.vue` 文件中。
-   页面内重复出现的 UI 片段必须抽成组件；同类卡片、列表项、操作栏、弹窗内容优先拆成局部组件。
-   当出现大量相同或相似的状态、请求、表单处理、轮询、上传、分页、倒计时、支付、登录校验等逻辑时，必须抽成 `useXxx` hook，并放到当前模块 `hooks` 或全局 `src/hooks`。
-   拆分组件时，页面负责组织数据流和业务编排，子组件负责展示和局部交互；复杂副作用优先放 hook，不要让子组件和页面互相隐式依赖。
-   弹窗优先复用 `popup-bottom`、`u-popup`、`confirm-dialog` 等现有组件。
-   表单组件必须处理初始值、校验、提交中状态、失败提示和关闭后的状态复位。
-   上传、选择素材、选择设备、聊天记录、文件选择等能力先复用现有组件。

## 10. 常量与魔法值

-   **禁止重复出现的字符串字面量、数字字面量直接散落在代码里**。同一字符串/数字在同一文件里出现 ≥ 2 次，或同一含义的字面量在多个文件里出现，必须抽成 `enum` / `const` / `Record` 集中管理：
    -   弹窗 / 模态类型标识：使用 TS `enum` 或 `as const` 对象，例如 `enum HomeModalType { HOT = 'hot', CREATE = 'create' }`；不要在 `activeModal.value === 'hot'`、`task_code === 'create'` 等比较里散落字符串。
    -   后端返回的状态码 / 业务标识（task_code、panel_code、status、type）：放到模块 `enums/` 目录或 `src/enums/`，命名 `XxxCodeEnum`。
    -   配色 / 间距 / 时长等纯样式数字：优先用 Tailwind 主题或 CSS 变量；确需 JS 里使用，集中常量定义。
    -   API URL、路径片段：放到 `src/api/*.ts` 接口函数里，不要在调用处拼字符串。
-   抽常量时**就近原则**：单文件内用就放文件顶部；跨文件用就放模块 `enums/`；全局通用才放 `src/enums/`。
-   抽完后**必须替换所有现有引用**，不要留"新代码用 enum、旧代码用字面量"的混用状态。
-   类型上配合 `enum` 或 `keyof typeof`、`as const` 拿到字面量联合类型，让编译器替你检查拼写。

## 11. 弹窗组件抽取规范

页面里出现的 `popup-bottom` / `u-popup` / `u-modal` 等弹窗，如果**超过 30 行模板**或**承担独立交互**（编辑表单、选择列表、确认对话），必须抽成独立子组件放在当前页面的 `components/popups/` 目录下。

**统一对外契约**：使用 Vue 3 默认 v-model 约定（`modelValue` + `update:modelValue`），父层调用时直接 `v-model="showXxx"` 即可。**禁止**新代码再用 `:visible` / `update:visible` 的旧命名。

**模板与脚本骨架**（按这套写，不要再发明变体）：

```vue
<template>
    <popup-bottom v-model="show">
        <template #header>
            <!-- 弹窗头：标题、关闭按钮等。关闭一律 emit('update:modelValue', false) -->
        </template>
        <template #content>
            <!-- 弹窗主体内容 -->
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    modelValue: boolean;
    // ...其它业务 props
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    // ...其它业务事件，如 (e: 'confirm', v: string): void
}>();

// 桥接：popup-bottom 内部的 v-model 走本地 writable computed，
// 父层契约始终是 modelValue / update:modelValue，互不影响。
const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});
</script>
```

**配套要点**：

-   `popup-bottom` 的弹窗头部（拖拽条、标题、副标题、关闭按钮）必须用组件提供的 `#header` slot，不要在 `#content` 里自己堆一套头部。`#content` 只放主体（滚动区 + 底部操作区）。提供 `#header` 后，`popup-bottom` 默认的拖拽条/标题不再渲染，避免头部重复或结构错位。参考 `packages/components/chat-scroll-view/components/mount-popup.vue` 的 `#header` 写法。
-   `#content` 内若需要「滚动区 + 固定底部按钮」布局：根节点用 `h-full w-full flex flex-col`，中间滚动区用 `grow min-h-0` 包裹 `scroll-view`，底部操作区作为 `flex` 末项固定；底部安全区用 `pb-[calc(20rpx+env(safe-area-inset-bottom))]`。
-   只在弹窗内部维护「纯 UI 临时态」（如展开项、tab 索引）；业务状态走 props 进、emit 出，不下沉到弹窗。
-   弹窗关闭时需要重置的内部 UI 态，用 `watch(() => props.modelValue, v => { if (!v) reset() })`。
-   弹窗内若用到 `popup-bottom` 自身的 input 事件回流（如蒙层关闭），需要 `@input="emit('update:modelValue', $event)"` 显式透传，避免把 `show.value` 反向赋值时漏掉父层通知。
-   关闭按钮、确认按钮等触发关闭的位置，直接 `emit('update:modelValue', false)`，不要写 `show.value = false`（让数据流单向）。
-   静态数据（如 chip 配置、tab 列表）就近放在弹窗组件内，不要从父层 prop 灌入。
-   样式：弹窗内部所有依赖类（含原页面 SCSS 里的 `.modal-*` 等）必须一并搬入弹窗的 `<style scoped>`；scoped 不串，留在父文件里子组件看不到。
