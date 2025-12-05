<template>
    <u-tabbar
        v-model="getCurrentIndex"
        v-show="showTabbar"
        v-bind="tabbarStyle"
        :list="tabbarList"
        @change="handleChange"
        :hide-tab-bar="false"></u-tabbar>
</template>

<script lang="ts" setup>
import { useNavigationBarTitleStore } from "@/stores/navigationBarTitle";
import { navigateTo } from "@/utils/util";
import { useRoute } from "uniapp-router-next";
import ChatIcon from "@/static/images/tabbar/chat.png";
import ChatSelectedIcon from "@/static/images/tabbar/chat_s.png";
import AgentIcon from "@/static/images/tabbar/agent.png";
import AgentSelectedIcon from "@/static/images/tabbar/agent_s.png";
import StaffIcon from "@/static/images/tabbar/staff.png";
import StaffSelectedIcon from "@/static/images/tabbar/staff_s.png";
import PhoneIcon from "@/static/images/tabbar/phone.png";
import PhoneSelectedIcon from "@/static/images/tabbar/phone_s.png";
import MeIcon from "@/static/images/tabbar/me.png";
import MeSelectedIcon from "@/static/images/tabbar/me_s.png";
const route = useRoute();
const navigationBarTitleStore = useNavigationBarTitleStore();
const tabbarList = computed(() => {
    const lists = [
        {
            iconPath: ChatIcon,
            selectedIconPath: ChatSelectedIcon,
            text: "AI聊天",
            link: {
                path: "/pages/index/index",
            },
        },
        {
            iconPath: AgentIcon,
            selectedIconPath: AgentSelectedIcon,
            text: "智能体",
            link: {
                path: "/pages/agent/agent",
            },
        },
        {
            iconPath: StaffIcon,
            selectedIconPath: StaffSelectedIcon,
            text: "AI员工",
            link: {
                path: "/pages/staff/staff",
            },
        },
        {
            iconPath: PhoneIcon,
            selectedIconPath: PhoneSelectedIcon,
            text: "AI手机",
            link: {
                path: "/pages/phone/phone",
            },
        },
        {
            iconPath: MeIcon,
            selectedIconPath: MeSelectedIcon,
            text: "我的",
            link: {
                path: "/pages/user/user",
            },
        },
    ];
    return (
        lists?.map((item: any) => {
            return {
                iconPath: item.iconPath,
                selectedIconPath: item.selectedIconPath,
                text: item.text,
                link: item.link,
            };
        }) || []
    );
});

// 原生菜单列表
const nativeTabList: string | any[] = [];

const getCurrentIndex = computed(() => {
    const current = tabbarList.value.findIndex((item: any) => {
        return item.link.path === route.path;
    });
    return route.path == "/" ? 0 : current;
});
const showTabbar = computed(() => {
    const current = getCurrentIndex.value;
    return current >= 0;
});

const tabbarStyle = computed(() => ({
    activeColor: "#0065FB",
}));
const handleChange = (index: number) => {
    const selectTab = tabbarList.value[index];
    selectTab.link.name = selectTab.text;
    const navigateType = nativeTabList.includes(selectTab.link.path) ? "switchTab" : "reLaunch";
    navigateTo(selectTab.link, false, navigateType);
};

watch(
    tabbarList,
    (value) => {
        const current = getCurrentIndex.value;

        if (current >= 0 && value.length) {
            const currentTab = value[current];
            navigationBarTitleStore.add({
                path: currentTab.link.path,
                title: currentTab.text,
            });
            navigationBarTitleStore.setTitle();
        }
    },
    {
        immediate: true,
    }
);
</script>
<style lang="scss"></style>
