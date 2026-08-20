<template>
    <popup
        ref="popupRef"
        title="链接选择"
        width="900px"
        :async="true"
        :destroy-on-close="true"
        @confirm="handleConfirm"
        @close="handleClose">
        <div class="flex h-[480px]">
            <!-- 左侧链接类型 -->
            <div class="w-[200px] border-r border-br-light overflow-y-auto">
                <el-menu :default-active="activeMenu" @select="handleSelect">
                    <el-menu-item index="page">小程序页面链接</el-menu-item>
                    <el-menu-item index="webview">web-view 链接</el-menu-item>
                    <el-menu-item index="other">其他小程序</el-menu-item>
                </el-menu>
            </div>

            <!-- 右侧内容 -->
            <div class="flex-1 p-5 overflow-y-auto">
                <el-alert class="link-tip mb-4" type="warning" title="温馨提示" :closable="false" show-icon>
                    <ol class="link-tip__list m-0 pl-4 text-sm leading-6">
                        <li>配置小程序跳转后，需在后台使用【小程序一键上传】重新上传小程序，跳转才能生效。</li>
                        <li>
                            若采用手动上传，需将目标小程序 AppID 写入
                            <code>app.json</code>
                            的
                            <code>navigateToMiniProgramAppIdList</code>
                            字段，配置后重新上传才能跳转。示例模板：
                            <pre class="link-tip__code">{{ APP_JSON_EXAMPLE }}</pre>
                        </li>
                        <li>
                            同时需前往
                            <a
                                href="https://mp.weixin.qq.com/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary">
                                微信小程序后台
                            </a>
                            添加目标小程序 AppID。
                        </li>
                    </ol>
                </el-alert>
                <!-- 类型1：小程序页面 -->
                <template v-if="currentType === 1">
                    <div v-if="currentPages.length" class="grid grid-cols-4 gap-3">
                        <div
                            v-for="page in currentPages"
                            :key="page.path"
                            class="link-item"
                            :class="{ active: selected.path === page.path }"
                            @click="selectPage(page)">
                            {{ page.name }}
                        </div>
                    </div>
                    <el-empty v-else description="暂无数据（内容待补充）" />
                </template>

                <!-- 类型2：web-view 链接 -->
                <el-form v-else-if="currentType === 2" label-width="90px">
                    <el-form-item label="链接地址">
                        <el-input v-model="webUrl" placeholder="请输入 web-view 链接，如 https://..." clearable />
                    </el-form-item>
                </el-form>

                <!-- 类型3：其他小程序 -->
                <el-form v-else label-width="90px">
                    <el-form-item label="AppID">
                        <el-input v-model="otherAppid" placeholder="请输入其他小程序 AppID" clearable />
                    </el-form-item>
                    <el-form-item label="页面路径">
                        <el-input v-model="otherPath" placeholder="请输入页面路径" clearable />
                    </el-form-item>
                </el-form>
            </div>
        </div>
    </popup>
</template>
<script lang="ts" setup>
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";

// 链接类型：1 小程序页面链接 2 web-view链接 3 其他小程序
const APP_JSON_EXAMPLE = `{
  "navigateToMiniProgramAppIdList": [
    "wxXXXXXXXXXXXXXXXX"
  ]
}`;

// 小程序页面链接列表
const MP_PAGES: { name: string; path: string }[] = [
    { name: "AI手机", path: "/pages/index/index" },
    { name: "AI助手", path: "/packages/pages/chat/chat" },
    { name: "AI创作", path: "/ai_modules/digital_human/pages/index/index" },
    { name: "我的", path: "/packages/pages/user/user" },
    { name: "AI手机管理", path: "/ai_modules/device/pages/index/index" },
    { name: "我的人设", path: "/ai_modules/person/pages/index/index" },
    { name: "使用教程", path: "/packages/pages/tutorial/tutorial" },
    { name: "客资线索", path: "/packages/pages/customer_leads/customer_leads" },
    { name: "数据报表", path: "/packages/pages/effect_statistics/effect_statistics" },
    {
        name: "工作安排",
        path: "/ai_modules/device/pages/task_calendar_full/task_calendar_full",
    },
];

const emit = defineEmits(["confirm"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

const activeMenu = ref<string>("page");
const currentType = ref<1 | 2 | 3>(1);

const selected = ref<{ name: string; path: string }>({ name: "", path: "" });
const webUrl = ref("");
const otherAppid = ref("");
const otherPath = ref("");

const currentPages = MP_PAGES;

const handleSelect = (index: string) => {
    if (index === "page") {
        currentType.value = 1;
    } else if (index === "webview") {
        currentType.value = 2;
    } else {
        currentType.value = 3;
    }
};

const selectPage = (page: { name: string; path: string }) => {
    selected.value = { ...page };
};

const handleConfirm = () => {
    let result: any = null;
    if (currentType.value === 1) {
        if (!selected.value.path) return feedback.msgError("请选择页面");
        result = {
            type: "page",
            name: selected.value.name,
            path: selected.value.path,
        };
    } else if (currentType.value === 2) {
        if (!webUrl.value) return feedback.msgError("请输入链接地址");
        result = { type: "webview", name: webUrl.value, path: webUrl.value };
    } else {
        if (!otherAppid.value) return feedback.msgError("请输入 AppID");
        result = {
            type: "miniapp",
            name: otherPath.value ? `${otherAppid.value} ${otherPath.value}` : otherAppid.value,
            path: otherPath.value,
            appid: otherAppid.value,
        };
    }
    emit("confirm", result);
    popupRef.value?.close();
};

const handleClose = () => {
    selected.value = { name: "", path: "" };
    webUrl.value = "";
    otherAppid.value = "";
    otherPath.value = "";
};

const open = (link?: any) => {
    handleClose();
    if (link?.type === "webview") {
        currentType.value = 2;
        activeMenu.value = "webview";
        webUrl.value = link.path || "";
    } else if (link?.type === "miniapp") {
        currentType.value = 3;
        activeMenu.value = "other";
        otherAppid.value = link.appid || "";
        otherPath.value = link.path || "";
    } else {
        currentType.value = 1;
        activeMenu.value = "page";
        if (link?.path) selected.value = { name: link.name, path: link.path };
    }
    popupRef.value?.open();
};

defineExpose({ open });
</script>
<style scoped>
.link-tip :deep(.el-alert__icon) {
    align-self: flex-start;
    margin-top: 2px;
}
.link-tip__list {
    color: var(--el-color-warning-dark-2);
}
.link-tip__list li + li {
    margin-top: 6px;
}
.link-tip__code {
    margin: 8px 0 0;
    padding: 10px 12px;
    border-radius: 6px;
    background: var(--el-bg-color);
    border: 1px solid var(--el-border-color-lighter);
    color: var(--el-text-color-regular);
    font-size: 12px;
    line-height: 1.6;
    overflow-x: auto;
    white-space: pre;
}
.link-item {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    padding: 0 12px;
    border: 1px solid var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}
.link-item:hover {
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
}
.link-item.active {
    border-color: var(--el-color-primary);
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
}
</style>
