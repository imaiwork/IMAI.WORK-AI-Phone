<template>
    <aside class="w-[210px] shrink-0 sticky top-0 self-start">
        <nav class="nav-card">
            <button
                v-for="item in navItems"
                :key="item.key"
                class="nav-item"
                :class="{ active: sidebarIndex === item.type }"
                @click="emit('select', item.type)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-[18px] h-[18px]">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                </svg>
                <span>{{ item.label }}</span>
                <svg
                    v-if="item.key === 'brand' && !oemActive"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    class="w-[14px] h-[14px] ml-auto text-slate-400">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75M6.75 21h10.5a2.25 2.25 0 002.25-2.25v-6a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6A2.25 2.25 0 006.75 21z" />
                </svg>
            </button>
        </nav>
    </aside>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { useTeamContext } from "../_composables/context";
import { NAV_MY_CARDS, NAV_OWNER } from "../_enums";

defineProps<{ sidebarIndex: number }>();
const emit = defineEmits<{ (e: "select", type: number): void }>();

const { info } = useTeamContext();
const { isOwner, isAdmin, oemActive } = info;
const appStore = useAppStore();

const navItems = computed(() => {
    // OEM 站点内的团队不提供品牌管理(不能套娃再开 OEM 站点)
    if (isOwner.value) {
        return appStore.isOemSite ? NAV_OWNER.filter((n) => n.key !== "brand") : NAV_OWNER;
    }
    // 管理员:组织信息 + 成员管理 + 消耗明细 + 我的卡密(无品牌管理)
    if (isAdmin.value) {
        return [
            ...NAV_OWNER.filter((n) => ["org", "members", "consume"].includes(n.key)),
            NAV_MY_CARDS,
        ];
    }
    // 成员:组织信息 + 我的卡密(查看被转移的卡密)
    return [NAV_OWNER[0], NAV_MY_CARDS];
});
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";
</style>
