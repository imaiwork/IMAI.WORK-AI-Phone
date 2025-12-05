<template>
    <div class="mx-2 flex items-center gap-x-2 flex-col md:flex-row">
        <router-link to="/" class="flex-shrink-0">
            <img :src="getLogo" alt="logo" class="h-7 w-7 rounded-full" />
        </router-link>
        <div class="text-[#7F7F7F] text-sm hidden md:block">
            {{ getWebsiteConfig.pc_home_title }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const { getWebsiteConfig, getOemConfig } = useAppStore();

const { is_oem, site_logo } = getOemConfig;

const getLogo = computed(() => {
    return is_oem == 1 ? site_logo : getWebsiteConfig.pc_logo;
});
</script>

<style scoped></style>
