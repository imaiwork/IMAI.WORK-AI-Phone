<template>
    <u-tabbar
        v-model="currentIndex"
        :list="tabbarList"
        :active-color="activeColor"
        :inactive-color="inactiveColor"
        :hide-tab-bar="false"
        @change="handleChange"></u-tabbar>
</template>

<script lang="ts" setup>
import { useAppStore } from "@/stores/app";
import { useNavigationBarTitleStore } from "@/stores/navigationBarTitle";
import { navigateTo } from "@/utils/util";
import { useRoute } from "uniapp-router-next";

import PhoneIcon from "@/static/images/tabbar/phone.png";
import PhoneSelectedIcon from "@/static/images/tabbar/phone_s.png";
import ChatIcon from "@/static/images/tabbar/chat.png";
import ChatSelectedIcon from "@/static/images/tabbar/chat_s.png";
import CreativeIcon from "@/static/images/tabbar/creative.png";
import CreativeSelectedIcon from "@/static/images/tabbar/creative_s.png";
import MeIcon from "@/static/images/tabbar/me.png";
import MeSelectedIcon from "@/static/images/tabbar/me_s.png";

interface TabbarLink {
    path: string;
    name?: string;
    type?: string;
    appid?: string;
}

interface TabbarItem {
    iconPath: string;
    selectedIconPath: string;
    text: string;
    link: TabbarLink;
}

// 链接类型：page=小程序页面 webview=web-view链接 miniapp=其他小程序
enum TabbarLinkType {
    PAGE = "page",
    WEBVIEW = "webview",
    MINIAPP = "miniapp",
}

const WEBVIEW_PAGE = "/packages/pages/webview/webview";
/** 主包启动壳；真实首页在分包 */
const LAUNCH_HOME_PATH = "/pages/index/index";
const REAL_HOME_PATH = "/packages/pages/home/home";

const normalizePath = (path = "") => {
    if (!path) return "";
    const withSlash = path.startsWith("/") ? path : `/${path}`;
    return withSlash.split("?")[0];
};

const resolveTabPath = (path = "") => {
    const normalized = normalizePath(path);
    if (normalized === LAUNCH_HOME_PATH || normalized === REAL_HOME_PATH) {
        return REAL_HOME_PATH;
    }
    return normalized;
};

const isSameTabPath = (a = "", b = "") => resolveTabPath(a) === resolveTabPath(b);

// 接口无数据时的空态兜底
const DEFAULT_TABBAR: TabbarItem[] = [
    {
        iconPath: PhoneIcon,
        selectedIconPath: PhoneSelectedIcon,
        text: "AI手机",
        link: { path: REAL_HOME_PATH },
    },
    {
        iconPath: ChatIcon,
        selectedIconPath: ChatSelectedIcon,
        text: "AI助手",
        link: { path: "/packages/pages/chat/chat" },
    },
    {
        iconPath: CreativeIcon,
        selectedIconPath: CreativeSelectedIcon,
        text: "AI创作",
        link: { path: "/ai_modules/digital_human/pages/index/index" },
    },
    {
        iconPath: MeIcon,
        selectedIconPath: MeSelectedIcon,
        text: "我的",
        link: { path: "/packages/pages/user/user" },
    },
];

const DEFAULT_COLOR = "#999999";
const DEFAULT_ACTIVE_COLOR = "#0065FB";

const appStore = useAppStore();
const route = useRoute();
const navigationBarTitleStore = useNavigationBarTitleStore();

const tabbarList = computed<TabbarItem[]>(() => {
    const list = appStore.getTabbarConfig;
    if (!Array.isArray(list) || !list.length) {
        return DEFAULT_TABBAR;
    }
    return list.map((item: any) => {
        const link = item.link || { path: "" };
        return {
            iconPath: item.unselected,
            selectedIconPath: item.selected,
            text: item.name,
            link: {
                ...link,
                path: resolveTabPath(link.path),
            },
        };
    });
});

const activeColor = computed(() => appStore.getTabbarStyle.selected_color || DEFAULT_ACTIVE_COLOR);
const inactiveColor = computed(() => appStore.getTabbarStyle.default_color || DEFAULT_COLOR);

const currentIndex = computed(() => {
    const idx = tabbarList.value.findIndex((item) => isSameTabPath(item.link.path, route.path));
    return idx < 0 ? 0 : idx;
});

const handleChange = (index: number) => {
    const tab = tabbarList.value[index];
    if (!tab) return;
    const link = tab.link || ({} as TabbarLink);

    if (link.type === TabbarLinkType.WEBVIEW) {
        if (!link.path) return;
        const query = `url=${encodeURIComponent(link.path)}&title=${encodeURIComponent(tab.text || "")}`;
        uni.navigateTo({ url: `${WEBVIEW_PAGE}?${query}` });
        return;
    }

    if (link.type === TabbarLinkType.MINIAPP) {
        // #ifdef MP-WEIXIN
        uni.navigateToMiniProgram({
            appId: link.appid || "",
            path: link.path || "",
            fail: () => uni.$u.toast("打开小程序失败"),
        });
        // #endif
        // #ifndef MP-WEIXIN
        uni.$u.toast("请在微信小程序中打开");
        // #endif
        return;
    }

    const targetPath = resolveTabPath(link.path);
    if (!targetPath) return;
    if (isSameTabPath(targetPath, route.path)) return;

    navigateTo(
        { path: targetPath, name: tab.text, type: link.type || TabbarLinkType.PAGE, isTab: false },
        false,
        "reLaunch",
    );
};

watch(
    tabbarList,
    (list) => {
        const index = currentIndex.value;
        if (index >= 0 && list.length) {
            navigationBarTitleStore.add({
                path: list[index].link.path,
                title: list[index].text,
            });
            navigationBarTitleStore.setTitle();
        }
    },
    { immediate: true },
);
</script>

<style lang="scss" scoped></style>
