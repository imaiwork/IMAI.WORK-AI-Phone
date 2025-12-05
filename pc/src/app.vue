<script lang="ts" setup>
// Element Plus 相关导入
import { ElConfigProvider } from "element-plus";
import zhCn from "element-plus/es/locale/lang/zh-cn";

// Store 相关导入
import { useUserStore } from "@/stores/user";
import { useAppStore } from "./stores/app";

// 布局组件导入
import LayoutAccount from "@/layouts/components/account/index.vue";
import LayoutSurvey from "@/layouts/components/survey/index.vue";

// Store 实例化
const userStore = useUserStore();
const appStore = useAppStore();

// Element Plus 配置
const config = {
    locale: zhCn,
};

// 网站配置
const { pc_title, pc_ico, pc_keywords, pc_desc } = appStore.getWebsiteConfig;
const { is_oem, logo_url, name } = appStore.getOemConfig;

// 设置网站头部信息
useHead({
    title: (is_oem == 1 ? name : pc_title) || "AI数字员工开源系统",
    meta: [
        {
            name: "description",
            content:
                pc_desc ||
                "专注于AI数字员工解决方案，为企业提供智能化的虚拟助手和自动化员工，提升工作效率，降低人力成本。支持多场景应用，助力企业数字化转型。",
        },
        {
            name: "keywords",
            content:
                pc_keywords || "AI数字员工, 虚拟助手, 自动化员工, 数字化转型, 智能化办公, 人工智能员工, 企业AI解决方案",
        },
    ],

    link: [
        {
            rel: "icon",
            type: "image/x-icon",
            href: is_oem == 1 ? logo_url : pc_ico,
        },
    ],
});
</script>

<template>
    <ElConfigProvider v-bind="config">
        <NuxtLayout>
            <!-- 加载指示器 -->
            <NuxtLoadingIndicator />

            <!-- 页面主体内容 -->
            <NuxtPage />

            <!-- 全局弹窗组件 -->
            <LayoutAccount v-if="userStore.showLogin" />
            <LayoutSurvey v-if="appStore.showSurvey" />
        </NuxtLayout>
    </ElConfigProvider>
</template>
