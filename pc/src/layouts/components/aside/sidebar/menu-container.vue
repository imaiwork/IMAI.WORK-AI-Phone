<template>
    <div class="menu-container h-full">
        <ElScrollbar>
            <div class="p-[18px]">
                <div class="h-full flex flex-col gap-2 mt-[18px]">
                    <router-link
                        :to="item.link"
                        class="h-11 flex items-center rounded-lg px-[10px] hover:bg-[#fbfbfb]"
                        :class="{
                            'router-link-active': activeMenu?.id === item.id,
                        }"
                        v-for="item in tools">
                        <div class="link-icon">
                            <Icon :name="`local-icon-${item.icon}`" :size="14"></Icon>
                        </div>
                        <div class="flex-1 flex items-center ml-3">
                            <div class="leading-5">
                                {{ item.name }}
                            </div>
                        </div>
                    </router-link>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { ToolEnum, ToolEnumMap } from "@/enums/appEnums";

const route = useRoute();

interface Tools {
    icon?: string;
    icon_active?: string;
    name: string;
    id: number;
    link: string;
    is_new?: boolean;
}

const tools = ref<Tools[]>([
    {
        id: ToolEnum.CHAT,
        name: ToolEnumMap[ToolEnum.CHAT],
        icon: "menu_chat",
        link: "/",
    },
    {
        id: ToolEnum.STAFF,
        name: ToolEnumMap[ToolEnum.STAFF],
        icon: "menu_staff",
        link: "/staff",
        is_new: false,
    },
    {
        id: ToolEnum.AID,
        name: ToolEnumMap[ToolEnum.AID],
        icon: "menu_auto_customer",
        link: "/robot",
        is_new: false,
    },
    {
        id: ToolEnum.DATABASE,
        name: ToolEnumMap[ToolEnum.DATABASE],
        icon: "menu_database",
        link: "/knowledge_base",
    },
    {
        id: ToolEnum.DEVICE,
        name: ToolEnumMap[ToolEnum.DEVICE],
        icon: "menu_terminal",
        link: "/device",
        is_new: true,
    },
    {
        id: ToolEnum.AGENT,
        name: ToolEnumMap[ToolEnum.AGENT],
        icon: "menu_agent",
        link: "/agent",
    },
]);

const activeMenu = ref(null);

const initActiveMenu = (path: string) => {
    activeMenu.value = tools.value.find((item) => item.link === path);
};

const setActiveMenu = (path: string) => {
    // 使用路径前缀映射来简化路由匹配逻辑
    const pathPrefixMap = {
        "/app": "/staff",
        "/robot": "/robot/aid",
        "/knowledge_base": "/knowledge_base",
        "/device": "/device",
        "/agent": "/agent",
        "/creation": "/creation",
    };
    // 查找匹配的路径前缀
    const matchedPrefix = Object.keys(pathPrefixMap).find((prefix) => path.startsWith(prefix));

    // 如果找到匹配的前缀，使用映射的路径，否则使用原始路径
    initActiveMenu(matchedPrefix ? pathPrefixMap[matchedPrefix] : path);
};

watch(
    () => route.path,
    (newVal) => {
        setActiveMenu(newVal);
    }
);

onMounted(() => {
    setActiveMenu(route.path);
});
</script>

<style lang="scss" scoped>
.menu-container {
    .link-icon {
        @apply flex-shrink-0 flex items-center justify-center rounded w-5 h-5 bg-[#ECECEC];
    }
    .router-link-active {
        @apply bg-white shadow-[0_0_0_1px_rgba(237,237,237,1)];
        .link-icon {
            @apply bg-black text-white;
        }
    }
}
</style>
