<template>
    <div class="team-console h-full">
        <ElScrollbar class="h-full">
            <div class="mx-auto max-w-[1200px] px-6 py-6">
                <!-- 未登录 -->
                <div v-if="!isLogin" class="empty-state">请先登录后使用团队功能</div>

                <template v-else>
                    <!-- 未加入团队 -->
                    <JoinCreate v-if="info && Number(info.in_team) === 0" />

                    <!-- 已加入团队：控制台 -->
                    <div v-else-if="info && Number(info.in_team) === 1" class="flex gap-6 items-start">
                        <!-- 左导航 -->
                        <TeamSidebar :sidebar-index="sidebarIndex" @select="getSliderIndex" />

                        <!-- 右侧内容 -->
                        <main class="flex-1 min-w-0">
                            <!-- 团队被平台停用提示(所有分区可见) -->
                            <div v-if="Number(info.status) === 0" class="notice-danger !mb-5">
                                团队已被平台停用，企业空间功能（AI 使用、创建资源等）暂不可用，请联系平台管理员。
                            </div>
                            <SectionOrg v-show="section === 'org'" @navigate="getSliderIndex" />
                            <SectionMembers v-if="isManager" v-show="section === 'members'" />
                            <SectionConsume v-if="isManager" v-show="section === 'consume'" />
                            <SectionBrand v-if="isOwner" v-show="section === 'brand'" />
                            <SectionMyCards v-if="!isOwner" v-show="section === 'cards'" />
                        </main>
                    </div>
                </template>
            </div>
        </ElScrollbar>

        <!-- 弹窗 -->
        <DialogUpgrade />
        <DialogBenefits />
        <DialogInvite />
        <DialogExpire />
        <DialogEditTokens />
        <DialogConsumption />
        <DialogOutput />
        <DialogGenerateCard />
        <DialogTransferCard />
        <DialogDisband />
        <DialogLeave />
        <DialogRename />
        <DialogFeature />
        <DialogRemoveMember />
    </div>
</template>

<script setup lang="ts">
import { provide } from "vue";
import { useAppStore } from "@/stores/app";
import useSidebar from "@/pages/app/_hooks/useSidebar";
import { useTeamConsole } from "./_composables/useTeamConsole";
import { TEAM_CONSOLE_KEY } from "./_composables/context";
import { SECTION_KEYS } from "./_enums";
import JoinCreate from "./_components/join-create.vue";
import TeamSidebar from "./_components/sidebar.vue";
import SectionOrg from "./_components/section-org.vue";
import SectionMembers from "./_components/section-members.vue";
import SectionConsume from "./_components/section-consume.vue";
import SectionBrand from "./_components/section-brand.vue";
import SectionMyCards from "./_components/section-my-cards.vue";
import DialogUpgrade from "./_components/dialog-upgrade.vue";
import DialogBenefits from "./_components/dialog-benefits.vue";
import DialogInvite from "./_components/dialog-invite.vue";
import DialogExpire from "./_components/dialog-expire.vue";
import DialogEditTokens from "./_components/dialog-edit-tokens.vue";
import DialogConsumption from "./_components/dialog-consumption.vue";
import DialogOutput from "./_components/dialog-output.vue";
import DialogGenerateCard from "./_components/dialog-generate-card.vue";
import DialogTransferCard from "./_components/dialog-transfer-card.vue";
import DialogDisband from "./_components/dialog-disband.vue";
import DialogLeave from "./_components/dialog-leave.vue";
import DialogRename from "./_components/dialog-rename.vue";
import DialogFeature from "./_components/dialog-feature.vue";
import DialogRemoveMember from "./_components/dialog-remove-member.vue";

definePageMeta({ key: "team", layout: "base", backTo: "/" });

// 团队控制台上下文:创建并 provide 给所有子组件
const ctx = useTeamConsole();
provide(TEAM_CONSOLE_KEY, ctx);
const { info: infoCtx, brand, consumption, members, refresh } = ctx;
const { isLogin, info, isManager, isOwner } = infoCtx;

// 分区导航:复用全站 useSidebar(sidebarIndex + ?type= URL 同步/刷新恢复)
const { sidebar: teamSidebar, sidebarIndex, getSliderIndex } = useSidebar();
teamSidebar.value = [
    { type: 1, name: "组织信息", components: null },
    { type: 2, name: "成员管理", components: null },
    { type: 3, name: "消耗明细", components: null },
    { type: 4, name: "品牌管理", components: null },
    { type: 5, name: "我的卡密", components: null },
];
// type 从 1 开始(与全站 SidebarTypeEnum 一致,避免 useSidebar 里 0 被当作未选中)
const section = computed(() => SECTION_KEYS[sidebarIndex.value - 1] ?? "org");

/** 当前角色可访问的侧栏 type；成员含我的卡密，管理员无品牌 */
const appStore = useAppStore();
const allowedSidebarTypes = computed(() => {
    // OEM 站点内的团队无品牌管理(不能套娃再开 OEM 站点)
    if (isOwner.value) return appStore.isOemSite ? [1, 2, 3] : [1, 2, 3, 4];
    if (isManager.value) return [1, 2, 3, 5];
    return [1, 5];
});

// 加入/开通后或角色变化时，若当前分区无权限则回落到组织信息，避免主区域空白
watch(
    [() => Number(info.value?.in_team), allowedSidebarTypes, sidebarIndex],
    ([inTeam]) => {
        if (inTeam !== 1) return;
        if (!allowedSidebarTypes.value.includes(sidebarIndex.value)) {
            getSliderIndex(1);
        }
    },
    { immediate: true },
);

// 团队主进入且在团队中时,加载品牌/小程序/卡密数据(OEM 站点无品牌管理,不加载)
watch(
    () => info.value && isOwner.value && Number(info.value.in_team) === 1 && !appStore.isOemSite,
    (ok) => {
        if (ok) {
            brand.loadTenant();
            brand.loadMnpVersion();
            brand.getCardLists();
        }
    },
);

// 切分区时按需刷新:组织信息→席位指标;成员管理→成员列表(避免入团后仍看缓存);消耗明细→流水
watch(sidebarIndex, (i) => {
    if (Number(info.value?.in_team) !== 1) return;
    if (i === 1) {
        infoCtx.loadInfo();
    }
    if (i === 2 && isManager.value) {
        infoCtx.loadInfo();
        members.resetMemberPage();
        members.loadMemberOptions();
    }
    // 切回消耗明细时清空上次的筛选项(业务类型/成员/关键词/时间),避免残留
    if (i === 3 && isManager.value) consumption.resetConsumeFilters();
});

onMounted(async () => {
    await refresh(); // 先拿团队信息/角色 + 成员
    // 若从 URL 恢复到消耗明细分区,补加载一次(此时角色已就绪)
    if (sidebarIndex.value === 3 && isManager.value) consumption.resetTeamConsume();
});
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";

.team-console {
    :deep(.el-scrollbar) {
        height: 100%;
    }
    :deep(.el-scrollbar__wrap) {
        overflow-x: hidden;
    }
}
</style>

<!-- 消耗/算力弹窗外壳:ElDialog teleport 到 body,故全局引入 -->
<style lang="scss">
@import "@/pages/team/_styles/dialog.scss";
</style>
