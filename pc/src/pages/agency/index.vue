<template>
    <div class="h-full flex flex-col min-w-[1000px] pb-4 px-4">
        <template v-if="!isAgent">
            <div class="flex-1 flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300">
                    <Icon name="el-icon-Lock" :size="28" />
                </div>
                <div class="text-center">
                    <p class="text-slate-700 font-[1000] text-base">暂无代理权限</p>
                    <p class="text-slate-400 text-xs font-medium mt-1.5">您当前不是代理用户，如需开通请联系管理员</p>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="flex justify-between items-end mb-6 gap-4 pt-4 shrink-0">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-8 bg-primary rounded-full shadow-[0_0_15px_rgba(0,101,251,0.3)]"></div>
                        <h1 class="text-2xl font-[1000] text-slate-900 tracking-tight">代理管理中心</h1>
                    </div>
                    <p class="text-slate-400 text-xs font-bold mt-2 ml-5 tracking-[0.1em] uppercase opacity-70">
                        管理您的下级代理资产、用户分销及授权数据
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="group flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-sm font-bold hover:bg-slate-50 hover:border-[#0065fb]/30 active:scale-95 transition-all"
                        @click="handleOpenAgencyContact">
                        <span class="group-hover:rotate-90 transition-transform duration-500 leading-[0]">
                            <Icon name="el-icon-Setting" />
                        </span>
                        代理全局配置
                    </button>
                </div>
            </div>

            <div
                class="shrink-0 relative rounded-[20px] px-8 py-6 overflow-hidden flex items-center justify-between premium-card">
                <div
                    class="absolute inset-0 opacity-[0.15] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px] [mask-image:linear-gradient(to_bottom,white,transparent)] pointer-events-none"></div>
                <div
                    class="absolute -right-20 -top-20 w-96 h-96 bg-[#0065fb]/20 rounded-full blur-[80px] animate-pulse-slow pointer-events-none"></div>

                <div class="relative z-10 flex items-center gap-6">
                    <div
                        class="w-12 h-12 bg-[#ffffff]/5 backdrop-blur-md rounded-2xl flex items-center justify-center border border-[#ffffff]/10 text-primary shrink-0">
                        <Icon name="el-icon-Lightning" :size="24" />
                    </div>
                    <div class="flex flex-col justify-center gap-2">
                        <div
                            class="px-3 py-1 rounded-full gap-1.5 bg-[#ffc83c2e] border border-[#ffc83c4d] w-fit select-none"
                            v-if="agentUserInfo?.level !== 0"
                            @click="handleLevelTap">
                            <span class="text-[10px] font-black tracking-widest text-[#ffe066]">
                                {{ agentUserInfo?.level_name }}
                            </span>
                        </div>
                        <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest">
                            当前可用算力余额
                        </span>
                        <div class="flex items-baseline gap-2">
                            <ElStatistic
                                :value="userTokens"
                                :precision="2"
                                :value-style="{
                                    color: '#ffffff',
                                    fontSize: '32px',
                                    fontWeight: '1000',
                                    letterSpacing: '-0.02em',
                                    lineHeight: '1',
                                }" />
                            <span class="text-primary text-xs font-black uppercase tracking-widest">算力</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 flex items-center gap-8">
                    <div class="flex flex-col justify-center gap-2">
                        <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest">
                            团队累计充值（含所有层级下级）
                        </span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-primary text-lg font-black">￥</span>
                            <ElStatistic
                                :value="agentUserInfo?.team_recharge_amount || 0"
                                :precision="2"
                                :value-style="{
                                    color: '#ffffff',
                                    fontSize: '32px',
                                    fontWeight: '1000',
                                    letterSpacing: '-0.02em',
                                    lineHeight: '1',
                                }" />
                        </div>
                        <span class="text-slate-500 text-[10px] font-bold tracking-wider">
                            {{ agentUserInfo?.team_recharge_count || 0 }} 笔 ·
                            {{ agentUserInfo?.team_user_count || 0 }} 位下级
                        </span>
                    </div>

                    <button
                        @click="handleInvite"
                        class="px-6 py-3 bg-primary text-white text-sm font-[1000] rounded-xl hover:bg-[#0056d6] hover:scale-105 active:scale-95 transition-all shadow-light shadow-[#0065fb]/30 flex items-center gap-2">
                        <Icon name="el-icon-Plus" />
                        邀请新代理用户
                    </button>
                </div>
            </div>

            <div class="grow min-h-0 bg-white rounded-[20px] mt-4 flex flex-col border border-br overflow-hidden">
                <div class="px-8 py-6 flex justify-between items-center gap-6">
                    <div class="flex p-1.5 bg-[#f1f5f9]/60 rounded-2xl w-fit">
                        <button
                            v-for="tab in tabsList"
                            :key="tab.id"
                            :class="[
                                'px-8 py-2.5 text-sm font-[1000] rounded-xl transition-all duration-300',
                                activeTab === tab.id
                                    ? 'bg-white text-primary shadow-sm translate-y-[-1px]'
                                    : 'text-slate-400 hover:text-slate-600',
                            ]"
                            @click="handleTabChange(tab.id)">
                            {{ tab.name }}
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <ElInput
                            v-model="searchQuery"
                            class="custom-input !w-80"
                            :placeholder="activeTab == 1 ? '搜索姓名、手机号...' : '搜索卡密序列号...'"
                            clearable
                            @clear="getLists"
                            @keyup.enter="getLists">
                            <template #prefix>
                                <span class="text-slate-300">
                                    <Icon name="el-icon-Search" />
                                </span>
                            </template>
                        </ElInput>
                        <button
                            @click="getLists"
                            class="px-8 py-3 bg-slate-900 text-white text-sm font-black rounded-2xl hover:bg-slate-800 transition-all active:scale-95">
                            查询
                        </button>
                        <button
                            v-if="activeTab === 2"
                            @click="handleOpenGenerate"
                            class="px-6 py-3 bg-primary text-white text-sm font-[1000] rounded-2xl hover:bg-[#0056d6] transition-all active:scale-95 shadow-light shadow-[#0065fb]/30 flex items-center gap-2">
                            <Icon name="el-icon-Plus" />
                            批量生成卡密
                        </button>
                    </div>
                </div>

                <div class="grow min-h-0">
                    <ElTable :data="pager.lists" height="100%" class="custom-table" v-loading="pager.loading">
                        <template v-if="activeTab == 1">
                            <ElTableColumn label="用户信息" min-width="220">
                                <template #default="{ row }">
                                    <div class="flex items-center justify-center gap-4">
                                        <div class="relative">
                                            <div
                                                class="absolute -inset-1 bg-gradient-to-tr from-primary to-blue-300 rounded-full opacity-10"></div>
                                            <img
                                                :src="row.avatar"
                                                class="relative w-11 h-11 rounded-full border-2 border-white object-cover" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-[1000] text-slate-800 text-sm">{{ row.nickname }}</span>
                                            <span class="text-slate-400 text-[11px] font-bold mt-1 tracking-wider">{{
                                                row.mobile
                                            }}</span>
                                        </div>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="下级人数" min-width="140" align="center">
                                <template #default="{ row }">
                                    <div class="flex flex-col items-center">
                                        <span class="text-slate-900 font-[1000]">{{ getDescendantCount(row) }}</span>
                                        <button
                                            v-if="canViewSubUsers(row)"
                                            class="text-primary text-[11px] font-black mt-0.5 hover:underline"
                                            @click="handleViewSubUsers(row)">
                                            {{ DESCENDANT_HINT }} · 查看
                                        </button>
                                        <span
                                            v-else-if="getDescendantCount(row) > 0"
                                            class="text-slate-300 text-[11px] font-bold mt-0.5">
                                            {{ DESCENDANT_HINT }}
                                        </span>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="充值业绩" min-width="160" align="center">
                                <template #default="{ row }">
                                    <div class="flex flex-col items-center">
                                        <span class="text-slate-900 font-[1000]">￥{{ row.recharge_amount || 0 }}</span>
                                        <button
                                            class="text-primary text-[11px] font-black mt-0.5 hover:underline"
                                            @click="handleViewRecharge(row)">
                                            {{ row.recharge_count || 0 }} 笔 · 查看流水
                                        </button>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="可用点数" min-width="120" align="center">
                                <template #default="{ row }">
                                    <span class="text-slate-900 font-[1000]">{{ row.tokens || 0 }}</span>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="代理等级" min-width="140" align="center">
                                <template #default="{ row }">
                                    <div class="p-1 flex justify-center">
                                        <div :class="getLevelClass(row.level)">
                                            {{
                                                agentLevel.find((item: any) => item.level == row.level)?.name ||
                                                "普通用户"
                                            }}
                                        </div>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="加入时间" min-width="180" align="center">
                                <template #default="{ row }">
                                    <div class="flex flex-col">
                                        <span class="text-slate-600 text-xs font-bold">
                                            {{ splitTime(row.become_time)[0] || "--" }}
                                        </span>
                                        <span class="text-slate-300 text-[10px]">
                                            {{ splitTime(row.become_time)[1] }}
                                        </span>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="快捷管理" width="300" align="right" fixed="right">
                                <template #default="{ row }">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            class="action-btn text-slate-600 bg-slate-50 hover:bg-slate-600 hover:text-white"
                                            @click="handleViewRecharge(row)">
                                            充值流水
                                        </button>
                                        <ElPopover
                                            v-if="canAdjustLevel"
                                            trigger="click"
                                            :width="150"
                                            :show-arrow="false"
                                            popper-class="!p-1.5 !rounded-2xl !border-slate-100 !shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)]">
                                            <template #reference>
                                                <button
                                                    class="action-btn text-primary bg-[#0065fb]/5 hover:bg-primary hover:text-white">
                                                    调整等级
                                                </button>
                                            </template>
                                            <div class="flex flex-col gap-1">
                                                <button
                                                    v-if="row.level != 0"
                                                    @click="handleUpgrade(row, 0)"
                                                    class="table-action-item">
                                                    普通用户
                                                </button>
                                                <template v-for="item in settableLevels" :key="item.level">
                                                    <button
                                                        v-if="item.level != row.level"
                                                        @click="handleUpgrade(row, item.level)"
                                                        class="table-action-item">
                                                        {{ item.name }}
                                                    </button>
                                                </template>
                                            </div>
                                        </ElPopover>
                                        <button
                                            @click="handleAction('gift', row)"
                                            class="action-btn text-emerald-600 bg-emerald-50 hover:bg-emerald-500 hover:text-white">
                                            赠送点数
                                        </button>
                                        <button
                                            v-if="row.level !== 0"
                                            @click="handleAction('remove', row)"
                                            class="action-btn text-rose-500 bg-rose-50 hover:bg-rose-500 hover:text-white">
                                            移除
                                        </button>
                                    </div>
                                </template>
                            </ElTableColumn>
                        </template>

                        <template v-else>
                            <ElTableColumn label="卡密序列号" min-width="240">
                                <template #default="{ row }">
                                    <div class="flex items-center justify-center gap-2 group">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-primary transition-colors">
                                            <Icon name="el-icon-Ticket" />
                                        </div>
                                        <span
                                            class="font-mono font-black text-slate-700 tracking-tight text-sm select-all"
                                            >{{ row.card_code }}</span
                                        >
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="关联套餐" min-width="180" align="center">
                                <template #default="{ row }">
                                    <div
                                        class="inline-flex flex-col px-3 py-1 bg-slate-50 rounded-xl border border-slate-100">
                                        <span class="text-slate-700 font-bold text-[11px]">{{ row.package_name }}</span>
                                        <span class="text-primary text-[10px] font-black mt-0.5"
                                            >{{ row.tokens }} 点算力</span
                                        >
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="生成时间" min-width="180" align="center">
                                <template #default="{ row }">
                                    <div class="flex flex-col">
                                        <span class="text-slate-600 text-xs font-bold">{{
                                            row.create_time?.split(" ")[0]
                                        }}</span>
                                        <span class="text-slate-300 text-[10px]">{{
                                            row.create_time?.split(" ")[1]
                                        }}</span>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="管理操作" width="200" align="right" fixed="right">
                                <template #default="{ row }">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="copy(row.card_code)"
                                            class="action-btn text-primary bg-[#0065fb]/5 hover:bg-primary hover:text-white">
                                            复制卡密
                                        </button>
                                        <button
                                            class="action-btn text-rose-500 bg-rose-50 hover:bg-rose-500 hover:text-white"
                                            @click="handleDelete(row.id)">
                                            删除
                                        </button>
                                    </div>
                                </template>
                            </ElTableColumn>
                        </template>

                        <template #empty>
                            <div class="py-20 flex flex-col items-center">
                                <ElEmpty :image-size="120" description="暂无相关记录数据" />
                            </div>
                        </template>
                    </ElTable>
                </div>

                <div class="shrink-0 h-[72px] px-8 flex items-center justify-between bg-[#f8fafc]/50">
                    <span class="text-xs font-medium text-[#94A3B8]"
                        >显示 {{ pager.lists.length }} 条，共 {{ pager.count }} 条{{
                            activeTab == 1 ? "代理用户" : "卡密"
                        }}数据</span
                    >
                    <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
                </div>
            </div>

            <agent-invite-poster
                v-if="showAgentInvitePoster"
                ref="agentInvitePosterRef"
                @close="showAgentInvitePoster = false" />
            <batch-generate-card
                v-if="showBatchGenerateCard"
                ref="batchGenerateCardRef"
                @success="getLists"
                @close="showBatchGenerateCard = false" />
            <agency-contact v-if="showAgencyContact" ref="agencyContactRef" @close="showAgencyContact = false" />
            <gift-tokens
                v-if="showGiftTokens"
                ref="giftTokensRef"
                @success="getLists"
                @close="showGiftTokens = false" />
            <sub-users
                v-if="showSubUsers"
                ref="subUsersRef"
                :agent-level="agentLevel"
                @view-recharge="handleViewRecharge"
                @close="showSubUsers = false" />
            <recharge-records
                v-if="showRechargeRecords"
                ref="rechargeRecordsRef"
                @close="showRechargeRecords = false" />
        </template>
    </div>
</template>

<script setup lang="ts">
import {
    getAgentUserInfo,
    getAgentSubList,
    getAgentCardList,
    deleteAgentCard,
    deleteAgentSub,
    setAgentLevel,
    getAgentLevel,
} from "@/api/user";
import { useUserStore } from "@/stores/user";
import { useGlobalSpin } from "@/composables/useSpinLoading";
import { useCopy } from "@/composables/useCopy";
import BatchGenerateCard from "./_components/batch-generate-card.vue";
import AgencyContact from "./_components/agency-contact.vue";
import GiftTokens from "./_components/gift-tokens.vue";
import AgentInvitePoster from "./_components/agent-invite-poster.vue";
import RechargeRecords from "./_components/recharge-records.vue";
import SubUsers from "./_components/sub-users.vue";

const userStore = useUserStore();
const { userTokens, userInfo } = toRefs(userStore);

const isAgent = computed(() => userInfo.value.is_distribution_agent);

const { copy } = useCopy();

const agentUserInfo = ref<Record<string, any> | null>(null);
const agentLevel = ref<any[]>([]);

const fetchAgentUserInfo = async () => {
    const res = await getAgentUserInfo();
    fetchAgentLevel();
    agentUserInfo.value = res;
};

const fetchAgentLevel = async () => {
    const res = await getAgentLevel();
    if (res.length > 0) {
        agentLevel.value = res;
    }
};

const showAgentInvitePoster = ref(false);
const agentInvitePosterRef = ref<InstanceType<typeof AgentInvitePoster>>();
const showBatchGenerateCard = ref(false);
const batchGenerateCardRef = ref<InstanceType<typeof BatchGenerateCard>>();
const showAgencyContact = ref(false);
const agencyContactRef = ref<InstanceType<typeof AgencyContact>>();
const showGiftTokens = ref(false);
const giftTokensRef = ref<InstanceType<typeof GiftTokens>>();
const showRechargeRecords = ref(false);
const rechargeRecordsRef = ref<InstanceType<typeof RechargeRecords>>();
const showSubUsers = ref(false);
const subUsersRef = ref<InstanceType<typeof SubUsers>>();

const tabsList = ref([
    { id: 1, name: "代理用户管理" },
    { id: 2, name: "激活卡密列表" },
]);
const activeTab = ref(1);
const searchQuery = ref("");

const { show, hide } = useGlobalSpin();

const { pager, getLists } = usePaging({
    fetchFun: (params: any) => {
        return activeTab.value === 1
            ? getAgentSubList({ ...params, status: 1, user_keyword: searchQuery.value })
            : getAgentCardList({ ...params, sn: searchQuery.value });
    },
});

const handleTabChange = (id: number) => {
    activeTab.value = id;
    searchQuery.value = "";
    getLists();
};

const handleViewRecharge = async (row: any) => {
    showRechargeRecords.value = true;
    await nextTick();
    rechargeRecordsRef.value?.open(row);
};

const DESCENDANT_HINT = "含子孙";
const getDescendantCount = (row: any) => Number(row?.descendant_count ?? row?.sub_count ?? 0);
const canViewSubUsers = (row: any) => Number(row?.sub_count ?? 0) > 0;

// 只支持看到下级的下级这一层，孙级卡片里不再有继续下钻的入口
const handleViewSubUsers = async (row: any) => {
    showSubUsers.value = true;
    await nextTick();
    subUsersRef.value?.open(row);
};

const handleOpenAgencyContact = () => {
    showAgencyContact.value = true;
    nextTick(() => {
        agencyContactRef.value?.open();
    });
};

const handleOpenGenerate = () => {
    showBatchGenerateCard.value = true;
    nextTick(() => {
        batchGenerateCardRef.value?.open();
    });
};

const handleInvite = () => {
    showAgentInvitePoster.value = true;
    nextTick(() => {
        agentInvitePosterRef.value?.open();
    });
};

// 早期后台绑定的下级没有加入时间，拿到的可能是 0/空，不能直接当字符串切分
const splitTime = (value: any) => (typeof value === "string" ? value.split(" ") : []);

const getLevelClass = (level: number) => {
    const base = "px-3 py-1.5 rounded-xl text-[10px] font-black tracking-tight border ";
    if (!level) return base + "bg-slate-50 text-slate-400 border-slate-100";
    if (level == 1) return base + "bg-blue-50 text-blue-600 border-blue-100";
    if (level == 2) return base + "bg-amber-50 text-amber-600 border-amber-100";
    return base + "bg-slate-50 text-slate-400 border-slate-100";
};

// 隐藏入口：连点 5 次自己的等级标签，弹出后台为该等级配置的备注说明
const LEVEL_REMARK_TAPS = 5;
const currentLevelRemark = computed(
    () => agentLevel.value.find((item: any) => item.level == agentUserInfo.value?.level)?.remark || "",
);
let levelTapCount = 0;
let levelTapTimer: ReturnType<typeof setTimeout> | null = null;

const handleLevelTap = () => {
    // 该等级没配备注时不做任何反馈，保持入口隐形
    if (!currentLevelRemark.value) return;

    if (levelTapTimer) clearTimeout(levelTapTimer);
    levelTapCount += 1;

    if (levelTapCount < LEVEL_REMARK_TAPS) {
        // 间隔太久视为零散误触，重新计数
        levelTapTimer = setTimeout(() => (levelTapCount = 0), 1500);
        return;
    }

    levelTapCount = 0;
    useNuxtApp().$confirm({
        title: `${agentUserInfo.value?.level_name || "当前等级"}说明`,
        message: currentLevelRemark.value,
        confirmButtonText: "知道了",
        cancelButtonText: "",
    });
};

// 等级数字越大等级越低，只能把下级设置成比自己更低的等级；等级数量后台可增删，不能写死
const settableLevels = computed(() =>
    agentLevel.value.filter((item: any) => item.level > (agentUserInfo.value?.level ?? 0)),
);
const canAdjustLevel = computed(() => (agentUserInfo.value?.level ?? 0) > 0 && settableLevels.value.length > 0);

const handleUpgrade = async (row: any, level: number) => {
    useNuxtApp().$confirm({
        message: `确定将用户 ${row.nickname} 调整为【${
            agentLevel.value.find((item: any) => item.level == level)?.name || "普通用户"
        }】吗？`,
        onConfirm: async () => {
            show({ text: "调整中..." });
            try {
                await setAgentLevel({ user_id: row.user_id, level });
                feedback.msgSuccess("调整成功");
                getLists();
            } catch (error) {
                feedback.msgError(error);
            } finally {
                hide();
            }
        },
    });
};

const handleAction = (action: "gift" | "remove", row: any) => {
    try {
        switch (action) {
            case "gift":
                showGiftTokens.value = true;
                nextTick(() => {
                    giftTokensRef.value?.open(row);
                });
                break;
            case "remove":
                useNuxtApp().$confirm({
                    message: "确定移除该代理用户吗？",
                    onConfirm: async () => {
                        show({ text: "移除中..." });
                        try {
                            await deleteAgentSub({ user_id: row.user_id });
                            feedback.msgSuccess("移除成功");
                            getLists();
                        } catch (error) {
                            feedback.msgError(error);
                        } finally {
                            hide();
                        }
                    },
                });
                break;
        }
    } catch (error) {
        feedback.msgError(error);
    } finally {
        hide();
    }
};

const handleDelete = async (id: any) => {
    useNuxtApp().$confirm({
        message: "确定删除该卡密吗？",
        onConfirm: async () => {
            show({ text: "删除中..." });
            try {
                await deleteAgentCard({ id });
                feedback.msgSuccess("删除成功");
                getLists();
                userStore.getUser();
            } catch (error) {
                feedback.msgError(error);
            } finally {
                hide();
            }
        },
    });
};

const init = async () => {
    if (isAgent.value) {
        getLists();
        fetchAgentUserInfo();
    }
};

onMounted(() => {
    init();
});
</script>

<style scoped lang="scss">
.action-btn {
    @apply px-4 py-1.5 rounded-xl text-[11px] font-black transition-all active:scale-95 border border-[transparent];
}

.premium-card {
    background: linear-gradient(110deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    background-size: 200% auto;
    animation: shimmer 8s ease-in-out infinite;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

@keyframes shimmer {
    0% {
        background-position: 0% 50%;
    }

    50% {
        background-position: 100% 50%;
    }

    100% {
        background-position: 0% 50%;
    }
}

.animate-pulse-slow {
    animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-slow {
    0%,
    100% {
        opacity: 0.5;
        transform: scale(1);
    }

    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}
</style>
