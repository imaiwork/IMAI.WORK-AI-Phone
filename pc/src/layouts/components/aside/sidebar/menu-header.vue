<template>
    <div class="fixed top-0 z-[889]">
        <div
            class="flex items-center justify-between h-[var(--nav-height)] px-[18px] relative transition-all duration-[1000]"
            :class="[hideSidebar ? 'w-auto' : 'w-[var(--aside-width)]']">
            <ElTooltip :content="getWebsiteConfig.shop_title">
                <div class="w-10 h-10 rounded-full border border-[#EDEDED] p-[2px]" v-show="!hideSidebar">
                    <router-link to="/" class="flex-shrink-0">
                        <img :src="getLogo" alt="logo" class="w-full h-full rounded-full object-contain" />
                    </router-link>
                </div>
            </ElTooltip>
            <ElTooltip :content="hideSidebar ? '显示侧边栏' : '隐藏侧边栏'" placement="right">
                <div
                    class="flex-shrink-0 cursor-pointer w-10 h-10 transition-all duration-300 rounded-full border border-[#EDEDED] p-[2px] flex items-center justify-center"
                    :class="[hideSidebar ? 'bg-[#EAEAEA] border-[transparent]' : '']"
                    @click="toggleSidebar()">
                    <Icon :name="!hideSidebar ? 'local-icon-sidebar' : 'local-icon-sidebar_primary'" :size="16"></Icon>
                </div>
            </ElTooltip>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const { toggleSidebar } = useAppStore();
const { getWebsiteConfig, getOemConfig } = useAppStore();
const hideSidebar = computed(() => useAppStore().hideSidebar);

const { is_oem, site_logo } = getOemConfig;

const getLogo = computed(() => {
    return is_oem == 1 ? site_logo : getWebsiteConfig.pc_logo;
});
</script>

<style scoped lang="scss"></style>
