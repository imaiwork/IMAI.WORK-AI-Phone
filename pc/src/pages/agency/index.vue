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
                            class="px-3 py-1 rounded-full gap-1.5 bg-[#ffc83c2e] border border-[#ffc83c4d] w-fit"
                            v-if="agentUserInfo?.level !== 0">
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

                <div class="relative z-10 flex items-center">
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
                            <ElTableColumn label="用户信息" min-width="260">
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
                            <ElTableColumn label="可用点数" min-width="140" align="center">
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
                                        <span class="text-slate-600 text-xs font-bold">{{
                                            row.become_time?.split(" ")[0]
                                        }}</span>
                                        <span class="text-slate-300 text-[10px]">{{
                                            row.become_time?.split(" ")[1]
                                        }}</span>
                                    </div>
                                </template>
                            </ElTableColumn>
                            <ElTableColumn label="快捷管理" width="280" align="right" fixed="right">
                                <template #default="{ row }">
                                    <div class="flex justify-end gap-2">
                                        <ElPopover
                                            v-if="isShowAdjustLevel(row)"
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
                                                <template v-for="item in agentLevel" :key="item.level">
                                                    <button
                                                        v-if="
                                                            1000 - (agentUserInfo?.level || 0) > 1000 - item.level &&
                                                            item.level != row.level
                                                        "
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

const userStore = useUserStore();
const { userTokens, userInfo } = toRefs(userStore);

const isAgent = computed(() => userInfo.value.is_distribution_agent);

const { copy } = useCopy();

const agentUserInfo = ref<{ level: number; level_name: string } | null>(null);
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

const getLevelClass = (level: number) => {
    const base = "px-3 py-1.5 rounded-xl text-[10px] font-black tracking-tight border ";
    if (!level) return base + "bg-slate-50 text-slate-400 border-slate-100";
    if (level == 1) return base + "bg-blue-50 text-blue-600 border-blue-100";
    if (level == 2) return base + "bg-amber-50 text-amber-600 border-amber-100";
    return base + "bg-slate-50 text-slate-400 border-slate-100";
};

const isShowAdjustLevel = (row: any) => {
    const { level } = agentUserInfo.value || {};
    const { level: rowLevel } = row || {};
    return rowLevel == 0 && level != 3
        ? level > rowLevel
        : 1000 - level > 1000 - rowLevel && level != rowLevel && level != 0;
};

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
