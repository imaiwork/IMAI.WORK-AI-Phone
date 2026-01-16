<template>
    <div class="menu-container h-full flex flex-col py-5">
        <ElScrollbar>
            <div class="px-4 pb-10">
                <ElMenu :default-active="route.path" class="custom-main-menu" router unique-opened>
                    <template v-for="item in tools" :key="item.key">
                        <ElSubMenu v-if="item.children?.length" :index="item.key" class="menu-group">
                            <template #title>
                                <div class="icon-box">
                                    <Icon v-if="item.icon" :name="`local-icon-${item.icon}`" :size="18"></Icon>
                                </div>
                                <span class="menu-text">{{ item.name }}</span>
                            </template>

                            <div class="sub-menu-wrapper">
                                <ElMenuItem v-for="child in item.children" :key="child.key" :index="child.link">
                                    <div class="dot-indicator"></div>
                                    <Icon v-if="child.icon" :name="`local-icon-${child.icon}`" :size="16"></Icon>
                                    <span class="ml-3">{{ child.name }}</span>
                                </ElMenuItem>
                            </div>
                        </ElSubMenu>

                        <ElMenuItem v-else :index="item.link" class="single-menu">
                            <div class="icon-box">
                                <Icon v-if="item.icon" :name="`local-icon-${item.icon}`" :size="18"></Icon>
                            </div>
                            <span class="menu-text">{{ item.name }}</span>
                            <div class="active-bar" v-if="route.path === item.link"></div>
                        </ElMenuItem>
                    </template>
                </ElMenu>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
const route = useRoute();
const tools = ref<any[]>([
    {
        key: "chat",
        name: "工作台",
        icon: "menu_chat",
        link: "/",
    },
    {
        key: "staff",
        name: "AI员工",
        icon: "menu_staff",
        children: [
            {
                key: "ai_customer",
                name: "AI获客",
                icon: "menu_customer",
                link: "/app/customer",
            },
            {
                key: "ai_sales",
                name: "AI销售",
                icon: "menu_sales",
                link: "/app/person_wechat",
            },
            {
                key: "digital_human",
                name: "数字人",
                icon: "menu_dh",
                link: "/app/digital_human",
            },
            {
                key: "ai_image",
                name: "AI图片",
                icon: "menu_draw",
                link: "/app/drawing",
            },
            {
                key: "matrix",
                name: "矩阵任务",
                icon: "menu_matrix",
                link: "/app/matrix",
            },
            {
                key: "internal",
                name: "AI内务",
                icon: "menu_internal",
                link: "/app/internal",
            },
        ],
    },
    {
        key: "aid",
        name: "岗位员工",
        icon: "menu_aid",
        link: "/robot",
    },
    {
        key: "database",
        name: "知识库",
        icon: "menu_database",
        link: "/knowledge_base",
    },
    {
        key: "agent",
        name: "智能体",
        icon: "menu_agent",
        link: "/agent",
    },
    {
        key: "device",
        name: "AI终端",
        icon: "menu_device",
        link: "/device",
    },
]);
</script>

<style lang="scss" scoped>
$primary: var(--el-color-primary);
$text-main: #1e293b;
$text-muted: #64748b;
$bg-active: rgba(0, 101, 251, 0.06);

.custom-main-menu {
    border-right: none !important;
    background: transparent !important;
    :deep(.el-menu) {
        background: transparent !important;
    }
    :deep(.el-menu-item),
    :deep(.el-sub-menu__title) {
        @apply flex items-center h-[50px] rounded-xl transition-all duration-300;
        color: $text-muted;
        font-weight: 700;
        margin-bottom: 4px;

        &:hover {
            color: $primary;
            background-color: #f8fafc !important;
            .icon-box {
                @apply scale-110 text-primary;
            }
        }
    }

    :deep(.el-menu-item.is-active) {
        color: $primary !important;
        background-color: $bg-active !important;
        position: relative;

        &::before {
            content: "";
            @apply absolute left-0 w-1 h-5 bg-primary rounded-full;
        }

        .icon-box {
            color: $primary;
            @apply scale-100;
        }
    }

    .icon-box {
        @apply w-8 h-8 flex items-center justify-center transition-all duration-300;
        color: #94a3b8;
    }

    .menu-text {
        @apply ml-1 font-[900] text-[14px];
    }

    .sub-menu-wrapper {
        @apply pl-4 pt-1 pb-1 relative;

        &::after {
            content: "";
            @apply absolute left-[25px] top-1 bottom-4 w-[1px] bg-[#F1F5F9];
        }

        :deep(.el-menu-item) {
            @apply h-[44px] text-[13px] pl-[40px] !important;
            font-weight: 700;

            .dot-indicator {
                @apply absolute left-[23px] w-1 h-1 rounded-full bg-[#CBD5E1] transition-all;
            }

            &.is-active {
                .dot-indicator {
                    @apply bg-primary scale-[1.5] shadow-[0_0_8px_rgba(0,101,251,0.5)];
                }
                &::before {
                    display: none;
                }
            }
        }
    }
}

:deep(.el-sub-menu__title:hover) {
    background-color: #f8fafc !important;
}
</style>
