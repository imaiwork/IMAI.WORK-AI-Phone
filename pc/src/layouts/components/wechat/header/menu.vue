<template>
    <div class="w-full flex items-center justify-center gap-x-[20px] xl:gap-x-[40px]">
        <a
            v-for="(menu, index) in menuList"
            :key="index"
            :href="`${getBaseUrl()}${menu.path}`"
            class="flex items-center gap-2 cursor-pointer relative">
            <Icon :name="`local-icon-${menu.icon}`"></Icon>
            <span class="text-white">{{ menu.name }}</span>
            <div
                class="absolute -bottom-3 left-0 w-full h-[3px] bg-white rounded-lg"
                v-if="activeMenu.name === menu.name"></div>
        </a>
    </div>
</template>

<script setup lang="ts">
import { getBaseUrl } from "@/utils/env";
const router = useRouter();

// 菜单列表
const menuList = [
    {
        key: "chat",
        name: "智能聊天",
        path: "/app/person_wechat/chat",
        icon: "wx_chat",
    },
    {
        key: "sop",
        name: "SOP",
        path: "/app/person_wechat/sop",
        icon: "wx_sop",
    },
    {
        key: "circle",
        name: "朋友圈",
        path: "/app/person_wechat/circle",
        icon: "wx_circle",
    },
    {
        key: "robot",
        name: "智能机器人",
        path: "/app/person_wechat/robot",
        icon: "wx_robot",
    },
    {
        key: "marketing",
        name: "营销工具",
        path: "/app/person_wechat/marketing",
        icon: "wx_marketing",
    },
    {
        key: "setting",
        name: "通用设置",
        path: "/app/person_wechat/setting",
        icon: "wx_setting",
    },
];

const isConfirmRouter = ["chat", "sop"];

const handleClickMenu = async (menu: any) => {
    if (isConfirmRouter.includes(menu.key)) {
        // await feedback.confirm("确定要离开吗？");
    }

    activeMenu.value = menu;
    router.push(menu.path);
};

const activeMenu = ref(menuList[0]);

const initRouter = () => {
    // 兼容页面路径可能会出现最后一个斜杠被截断的情况
    let path = router.currentRoute.value.path;
    if (path.endsWith("/")) {
        path = path.slice(0, -1);
    }
    activeMenu.value = menuList.find((menu) => menu.path === path);
    if (isConfirmRouter.includes(activeMenu.value.key)) {
        // setupBeforeUnload();
    }
};

onMounted(() => {
    initRouter();
});
</script>

<style scoped></style>
