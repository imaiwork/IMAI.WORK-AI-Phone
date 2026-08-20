<template>
    <div class="output-map flex w-full flex-col gap-3.5">
        <!-- 历史轮次:用户气泡 + 助手卡片,各自独立成行,不套进同一助手气泡 -->
        <div
            v-for="(turn, ti) in priorTurns"
            :key="'turn-' + ti"
            class="map-turn flex flex-col gap-3.5 border-b border-dashed border-[#e5e7eb] pb-3.5">
            <div class="msg user ml-auto flex max-w-[88%] items-start gap-2.5">
                <div
                    class="bubble break-words rounded-[14px] bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3.5 py-2.5 text-sm leading-relaxed text-white">
                    {{ turn.userText }}
                </div>
                <div
                    class="user-avatar flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white">
                    <img v-if="userAvatar" :src="userAvatar" alt="" class="h-full w-full object-cover" />
                    <span v-else>我</span>
                </div>
            </div>
            <div class="assistant-row flex max-w-full items-start gap-2.5">
                <div
                    class="content bubble min-w-0 flex-1 rounded-[14px] border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                    <div class="prior-cards grid grid-cols-[repeat(auto-fill,minmax(220px,1fr))] gap-3 opacity-[0.85]">
                        <div
                            v-for="(card, idx) in turn.cards"
                            :key="card.key + '-' + idx"
                            class="biz-card mt-0 rounded-xl border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
                            <div class="mb-2 text-[15px] font-bold text-[#2563eb]">{{ card.name }}</div>
                            <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                <span class="shrink-0">📍</span>{{ card.addr }}
                            </div>
                            <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                <span class="shrink-0">📞</span>{{ card.phone }}
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="rounded-[10px] bg-[#ecf2ff] px-2.5 py-0.5 text-[11px] font-medium text-[#2563eb]"
                                    >{{ card.tag }}</span
                                >
                                <span class="flex items-center gap-0.5 text-[13px] font-semibold text-[#f59e0b]"
                                    >⭐ {{ card.rating }}</span
                                >
                            </div>
                        </div>
                        <div class="col-span-full pt-1 text-right text-xs text-[#94a3b8]">
                            📦 已归档 {{ turn.cards.length }} 条
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 当前轮用户气泡 -->
        <div v-if="currentUserText" class="msg user ml-auto flex max-w-[88%] items-start gap-2.5">
            <div
                class="bubble break-words rounded-[14px] bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3.5 py-2.5 text-sm leading-relaxed text-white">
                {{ currentUserText }}
            </div>
            <div
                class="user-avatar flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold text-white">
                <img v-if="userAvatar" :src="userAvatar" alt="" class="h-full w-full object-cover" />
                <span v-else>我</span>
            </div>
        </div>

        <!-- 当前轮助手回复 -->
        <div v-if="showAssistantPanel" class="assistant-row flex max-w-full items-start gap-2.5">
            <div
                class="content bubble min-w-0 flex-1 rounded-[14px] border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
                <!-- 思考 loading:发送瞬间立刻出现,最少展示 800ms,慢就等到响应回来 -->
                <div v-if="thinking" class="flex items-center gap-2.5 py-0.5 text-[13px] text-[#4b5563]">
                    <span
                        class="th-spin h-3.5 w-3.5 shrink-0 animate-spin rounded-full border-2 border-[#dbeafe] border-t-[#2563eb]" />
                    <span class="font-medium text-[#1f2937]"
                        >AI 正在思考<span class="th-dots inline-block overflow-hidden align-bottom">…</span></span
                    >
                </div>

                <!-- 抓取批次结果 -->
                <div v-for="(batch, bi) in batches" :key="batch.id" class="batch mb-[18px] last:mb-0">
                    <div class="mb-2 rounded-[10px] border border-[#f0f1f4] bg-white px-3.5 py-3">
                        <div class="mb-1.5 text-[13px] font-bold text-[#1f2937]">本次抓取策略：</div>
                        <div class="mb-1 text-xs text-[#6b7280]">
                            📄 关键词：<b>{{ batch.query }}</b
                            >，分页 {{ bi + 1 }}/{{ totalPages }}
                        </div>
                        <div class="mb-1 text-xs text-[#6b7280]">🔎 过滤含有效电话的商家，自动去重</div>
                    </div>

                    <div
                        v-if="batch.loading"
                        class="flex items-center gap-2 rounded-[10px] border border-[#e5e7eb] bg-[#f8fafc] px-3.5 py-2.5 text-xs text-[#6b7280]">
                        <div
                            class="ld-spin h-3 w-3 animate-spin rounded-full border-2 border-[#dbeafe] border-t-[#2563eb]" />
                        正在抓取
                        <span class="font-semibold text-[#2563eb]">{{ batch.fetched }}/{{ batch.size }}</span>
                        条…
                    </div>

                    <div v-else class="biz-list relative">
                        <div
                            v-for="(card, idx) in visibleCards(batch)"
                            :key="card.key"
                            class="biz-card stream-in mt-2.5 rounded-xl border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]"
                            :style="{ animationDelay: idx * 50 + 'ms' }">
                            <div class="mb-2 text-[15px] font-bold text-[#2563eb]">{{ card.name }}</div>
                            <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                <span class="shrink-0">📍</span>{{ card.addr }}
                            </div>
                            <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                <span class="shrink-0">📞</span>{{ card.phone }}
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="rounded-[10px] bg-[#ecf2ff] px-2.5 py-0.5 text-[11px] font-medium text-[#2563eb]"
                                    >{{ card.tag }}</span
                                >
                                <span class="flex items-center gap-0.5 text-[13px] font-semibold text-[#f59e0b]"
                                    >⭐ {{ card.rating }}</span
                                >
                            </div>
                        </div>
                        <div
                            v-if="batch.cards.length > 3"
                            class="mt-2.5 inline-flex cursor-pointer items-center gap-1.5 rounded-2xl border border-[#c7dafd] bg-white px-3.5 py-1.5 text-xs font-medium text-[#2563eb] hover:bg-[#eff6ff]"
                            @click="batch.expanded = !batch.expanded">
                            <span>{{ batch.expanded ? "快速收起" : `显示全部 ${batch.cards.length} 条` }}</span>
                            <span class="transition-transform duration-200" :class="{ 'rotate-180': batch.expanded }"
                                >▾</span
                            >
                        </div>

                        <div
                            v-if="bi === batches.length - 1"
                            class="mt-3.5 flex flex-col gap-2.5 rounded-xl border border-[#c7dafd] bg-[#eff6ff] px-3.5 py-2.5">
                            <div class="flex flex-wrap items-center gap-1 text-[13px] text-[#1f2937]">
                                <span class="mr-1 text-[#ef4444]">📍</span>
                                当前:已获取
                                <span class="font-bold text-[#2563eb]">{{ totalFetched }}</span>
                                <template v-if="gaodeTotalCount > 0">
                                    <span class="mx-0.5 text-[#9ca3af]">/</span>
                                    <span class="font-bold text-[#2563eb]">{{ gaodeTotalCount }}</span>
                                </template>
                                <span>条</span>
                                <span
                                    v-if="isFullyComplete"
                                    class="ml-2 rounded-md bg-[#d1fae5] px-2 py-px text-[11px] font-medium text-[#10b981]"
                                    >已全部获取</span
                                >
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="canContinue"
                                    class="inline-flex items-center gap-1.5 rounded-2xl border border-transparent bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] px-3.5 py-1.5 text-xs font-medium text-white shadow-[0_2px_8px_rgba(37,99,235,0.22)] transition-all duration-150 hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="fetching"
                                    @click="continueFetchAll">
                                    <span
                                        v-if="fetching"
                                        class="h-2.5 w-2.5 animate-spin rounded-full border-[1.5px] border-white/40 border-t-white" />
                                    {{ fetching ? "拉取中…" : "继续获取" }}
                                </button>
                                <button
                                    class="inline-flex items-center gap-1.5 rounded-2xl border border-[#93c5fd] bg-white px-3.5 py-1.5 text-xs font-medium text-[#2563eb] transition-all duration-150 hover:bg-[#eff6ff] disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="exporting || !currentExportMessageId"
                                    @click="exportCurrent">
                                    {{ exporting ? "导出中…" : "⬇ 下载" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 业务错误 / 接口异常 -->
                <div v-if="replyError && !thinking" class="map-error stream-in m-0">
                    <div class="rounded-xl border border-[#fecaca] bg-[#fff5f5] px-3.5 py-3">
                        <div class="mb-1 flex items-center gap-1.5 text-[13px] font-bold text-[#dc2626]">
                            <span
                                class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#dc2626] text-[11px] font-bold text-white"
                                >!</span
                            >{{ replyErrorTitle }}
                        </div>
                        <div class="text-[13px] leading-normal text-[#4b5563]">{{ replyError }}</div>
                        <button
                            class="mt-2.5 inline-flex h-[30px] items-center gap-1 rounded-lg border border-[#fecaca] bg-white px-3 text-xs font-semibold text-[#dc2626] transition-[background,opacity] duration-150 hover:bg-[#fef2f2] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="fetching || !lastQuery"
                            @click="retryAnswer">
                            ↻ 重新回答
                        </button>
                    </div>
                </div>

                <!-- 成功但无商家数据 -->
                <div
                    v-else-if="emptyResult && !thinking"
                    class="map-empty stream-in flex flex-col items-center px-0 pb-1 pt-2">
                    <ElEmpty :image-size="80" description="暂未找到相关商家" />
                    <button
                        class="mt-1 inline-flex h-[30px] items-center gap-1 rounded-lg border border-[#e5e7eb] bg-white px-3 text-xs font-semibold text-[#374151] transition-[background,opacity] duration-150 hover:bg-[#f9fafb] disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="fetching || !lastQuery"
                        @click="retryAnswer">
                        ↻ 重新回答
                    </button>
                </div>
            </div>
        </div>

        <!-- 历史抽屉 -->
        <Teleport to="body">
            <div
                v-if="historyOpen"
                class="history-overlay fixed inset-0 z-[9000] flex flex-col bg-[#f5f6f8]"
                @click.self="historyOpen = false">
                <div class="flex h-14 items-center justify-between border-b border-[#eef0f3] bg-white px-6">
                    <div class="flex items-center gap-3 text-[15px] font-semibold text-[#1f2937]">
                        <button
                            v-if="historyDetail"
                            class="cursor-pointer rounded-2xl border-0 bg-[#f3f5f9] px-3.5 py-1.5 text-[13px] text-[#4b5563] hover:bg-[#e5e7eb] hover:text-[#2563eb]"
                            @click="
                                historyDetail = null;
                                detailCards = [];
                                historyExportMessageId = null;
                            ">
                            ‹ 返回
                        </button>
                        <span
                            >📍
                            {{
                                historyDetail ? `${historyDetail.city} · ${historyDetail.kind}` : "地图获客 · 历史记录"
                            }}</span
                        >
                    </div>
                    <button
                        class="h-8 w-8 cursor-pointer rounded-full border-0 bg-[#f3f5f9] text-[#6b7280] hover:bg-[#e5e7eb] hover:text-[#1f2937]"
                        @click="historyOpen = false">
                        ✕
                    </button>
                </div>
                <div class="ho-body flex-1 overflow-y-auto px-8 pb-8 pt-6">
                    <div v-if="!historyDetail" class="mx-auto max-w-[1100px]">
                        <div v-if="historyLoading" class="px-4 py-12 text-center text-[13px] text-[#9ca3af]">
                            加载中…
                        </div>
                        <div
                            v-else-if="pageItems.length === 0"
                            class="px-4 py-12 text-center text-[13px] text-[#9ca3af]">
                            暂无历史会话
                        </div>
                        <template v-else>
                            <div class="overflow-hidden rounded-[14px] border border-[#eef0f3] bg-white">
                                <div
                                    class="ho-row grid grid-cols-[42px_1fr_100px_130px_110px_200px] items-center gap-3.5 border-b border-[#f3f4f6] bg-[#fafbfc] px-[18px] py-3.5 text-xs font-semibold text-[#6b7280]">
                                    <span>#</span>
                                    <span>任务名称</span>
                                    <span>地区</span>
                                    <span>条数</span>
                                    <span>数据源</span>
                                    <span class="text-right">操作</span>
                                </div>
                                <div
                                    v-for="(t, i) in pageItems"
                                    :key="t.id"
                                    class="ho-row grid grid-cols-[42px_1fr_100px_130px_110px_200px] items-center gap-3.5 border-b border-[#f3f4f6] px-[18px] py-3.5 text-[13px] text-[#4b5563] last:border-b-0">
                                    <span class="text-[#9ca3af]">{{ (historyPage - 1) * pageSize + i + 1 }}</span>
                                    <div>
                                        <div class="font-semibold text-[#1f2937]">{{ t.city }} · {{ t.kind }}</div>
                                        <div class="text-xs text-[#9ca3af]">{{ t.time }}</div>
                                    </div>
                                    <span class="font-medium text-[#2563eb]">{{ t.city }}</span>
                                    <span class="font-semibold text-[#f97316]">{{ t.count }} 条</span>
                                    <span class="text-xs">{{ t.source }}</span>
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            class="cursor-pointer rounded-[18px] border border-[#e5e7eb] bg-white px-3.5 py-1.5 text-xs text-[#4b5563] hover:border-[#93c5fd] hover:bg-[#f5f8ff] hover:text-[#2563eb]"
                                            @click="openDetail(t)">
                                            查看详情
                                        </button>
                                        <button
                                            class="cursor-pointer rounded-[18px] border border-transparent bg-gradient-to-br from-[#34d399] to-[#059669] px-3.5 py-1.5 text-xs font-semibold text-white"
                                            @click.stop="resumeHistory(t)">
                                            打开会话
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-[18px] flex items-center justify-between">
                                <div class="text-xs text-[#9ca3af]">
                                    共 <b class="text-[#1f2937]">{{ historyTotal }}</b> 条记录，第
                                    <b class="text-[#1f2937]">{{ historyPage }}</b> / {{ totalHistoryPages }} 页
                                </div>
                                <div class="flex gap-1.5">
                                    <button
                                        class="pg-btn h-8 min-w-8 cursor-pointer rounded-lg border border-[#e5e7eb] bg-white px-2.5 text-xs text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb] disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="historyPage === 1"
                                        @click="historyPage--">
                                        ‹
                                    </button>
                                    <button
                                        v-for="p in totalHistoryPages"
                                        :key="p"
                                        class="pg-btn h-8 min-w-8 cursor-pointer rounded-lg border px-2.5 text-xs"
                                        :class="
                                            p === historyPage
                                                ? 'border-transparent bg-gradient-to-br from-[#4f8ef7] to-[#2563eb] font-semibold text-white'
                                                : 'border-[#e5e7eb] bg-white text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb]'
                                        "
                                        @click="historyPage = p">
                                        {{ p }}
                                    </button>
                                    <button
                                        class="pg-btn h-8 min-w-8 cursor-pointer rounded-lg border border-[#e5e7eb] bg-white px-2.5 text-xs text-[#4b5563] hover:border-[#93c5fd] hover:text-[#2563eb] disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="historyPage === totalHistoryPages"
                                        @click="historyPage++">
                                        ›
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div v-else class="mx-auto max-w-[1100px]">
                        <div
                            class="mb-[18px] flex flex-wrap justify-between gap-4 rounded-[14px] border border-[#c7dafd] bg-gradient-to-br from-[#eff6ff] to-[#dbeafe] px-6 py-5">
                            <div>
                                <div class="mb-1.5 text-lg font-bold text-[#1f2937]">
                                    {{ historyDetail.city }} · {{ historyDetail.kind }}
                                </div>
                                <div class="flex flex-wrap gap-3.5 text-[13px] text-[#4b5563]">
                                    <span
                                        >抓取时间：<b class="text-[#2563eb]">{{ historyDetail.time }}</b></span
                                    >
                                    <span
                                        >共 <b class="text-[#2563eb]">{{ historyDetail.count }}</b> 条</span
                                    >
                                    <span
                                        >数据源：<b class="text-[#2563eb]">{{ historyDetail.source }}</b></span
                                    >
                                </div>
                            </div>
                            <button
                                class="inline-flex items-center gap-2 rounded-xl border border-[#e5e7eb] bg-white px-7 py-3 text-sm font-medium text-[#1f2937] hover:border-[#2563eb] hover:bg-[#f5f8ff] hover:text-[#2563eb] disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="exporting || !historyExportMessageId"
                                @click="downloadHistory(historyDetail)">
                                {{ exporting ? "导出中…" : `⬇ 下载 Excel（${historyDetail.count} 条）` }}
                            </button>
                        </div>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-3.5">
                            <div
                                v-for="(c, i) in detailCards"
                                :key="i"
                                class="rounded-xl border border-[#f0f1f4] bg-white px-4 py-3.5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
                                <div class="mb-2 text-[15px] font-bold text-[#2563eb]">{{ c.name }}</div>
                                <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                    <span class="shrink-0">📍</span>{{ c.addr }}
                                </div>
                                <div class="mb-1 flex items-start gap-1.5 text-[13px] leading-normal text-[#4b5563]">
                                    <span class="shrink-0">📞</span>{{ c.phone }}
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        class="rounded-[10px] bg-[#ecf2ff] px-2.5 py-0.5 text-[11px] font-medium text-[#2563eb]"
                                        >{{ c.tag }}</span
                                    >
                                    <span class="flex items-center gap-0.5 text-[13px] font-semibold text-[#f59e0b]"
                                        >⭐ {{ c.rating }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import {
    downloadMapLeadExport,
    getMapLeadConversations,
    loadMapLeadConversation,
    sendMapLeadMessage,
} from "@/api/map_lead";
import { useUserStore } from "@/stores/user";
import feedback from "@/utils/feedback";

const userStore = useUserStore();
const { userInfo } = storeToRefs(userStore);
const userAvatar = computed(() => userInfo.value?.avatar || "");

interface BizCard {
    key: string;
    name: string;
    addr: string;
    phone: string;
    tag: string;
    rating: string | number;
}
interface Batch {
    id: number;
    query: string;
    size: number;
    fetched: number;
    cards: BizCard[];
    loading: boolean;
    expanded: boolean;
    /** 对应助手消息 id,导出 Excel 用 */
    messageId?: number | null;
}
interface HistoryItem {
    id: string;
    conversationId: string;
    city: string;
    kind: string;
    count: number;
    time: string;
    source: string;
}

defineProps<{
    hideInlineActions?: boolean;
    /** 当前轮用户输入(由父级传入,避免再套一层助手气泡) */
    currentUserText?: string;
}>();
const emit = defineEmits<{
    (e: "pick-cat", name: string): void;
    (e: "fetch-more"): void;
    (e: "export", batches: Batch[]): void;
    /** 每次本批次写入完成后通知父级 */
    (e: "progress"): void;
    /** 后端返回 / 恢复会话后同步 conversation_id 到 URL */
    (e: "conversation-change", conversationId: string): void;
    /** 从会话记录恢复完成后,把最新一轮用户文案回传给父级 */
    (e: "restored", payload: { lastUserText: string }): void;
}>();

const ASSISTANT_AVATAR_BG = "linear-gradient(135deg, #60a5fa, #2563eb)";
const SYSTEM_ERROR_TEXT = "抱歉，系统开小差了，请稍后重试";

const batches = ref<Batch[]>([]);
// 同一会话里"之前几轮"的归档(每次新发送时把当前 batches 摊平进来,然后清空 batches)
interface PriorTurn {
    userText: string;
    cards: BizCard[];
}
const priorTurns = ref<PriorTurn[]>([]);
const fetching = ref(false);
const exporting = ref(false);
/** 当前结果区可导出的助手 message_id(取最新一批成功结果) */
const currentExportMessageId = computed(() => {
    for (let i = batches.value.length - 1; i >= 0; i--) {
        const id = batches.value[i]?.messageId;
        if (id) return id;
    }
    return null;
});
// 思考态:从用户发送瞬间 → 第一批数据准备好之前
const thinking = ref(false);
// 思考 loading 最少展示时长(毫秒),让快响应也能看见
const THINKING_MIN_MS = 800;
const replyError = ref("");
const replyErrorTitle = computed(() =>
    replyError.value && replyError.value !== SYSTEM_ERROR_TEXT ? "提示" : "系统错误",
);
/** 首轮成功但商家卡片为空（类目点击后无数据时展示空态） */
const emptyResult = ref(false);
const totalTarget = ref(0);
// 高德报告的总匹配数(真实可拉到的天花板)
const gaodeTotalCount = ref(0);
// 翻页耗尽 — 高德返回 < 25 条
const exhausted = ref(false);
const totalFetched = computed(() => batches.value.reduce((s, b) => s + b.cards.length, 0));
const totalPages = computed(() => Math.max(1, Math.ceil(totalTarget.value / 20)));
const progressPct = computed(() =>
    totalTarget.value ? Math.round((totalFetched.value / totalTarget.value) * 100) : 0,
);
// 有思考态 / 批次 / 错误 / 空结果时才展示助手面板
const showAssistantPanel = computed(
    () => thinking.value || batches.value.length > 0 || !!replyError.value || emptyResult.value,
);

let _id = 1;
const lastQuery = ref("");
// 后端会话 id(首轮拿到后,同会话续聊 / 翻页时带上;仅 reset 清空)
let conversationId = "";
/** 下次继续获取要传的 page(= 上次响应 next_page；0 表示无下一页) */
const nextPageToFetch = ref(0);

function bindConversationId(id: string) {
    if (!id) return;
    const changed = conversationId !== id;
    conversationId = id;
    if (changed) emit("conversation-change", id);
}

function visibleCards(b: Batch) {
    return b.expanded ? b.cards : b.cards.slice(0, 3);
}

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

/** 已展示商家 key,用于继续获取时过滤重复卡片 */
function collectSeenCardKeys() {
    const keys = new Set<string>();
    for (const batch of batches.value) {
        for (const card of batch.cards) {
            if (card.key) keys.add(String(card.key));
        }
    }
    return keys;
}

/** 同步翻页游标:next_page=0 或 exhausted 视为没有下一页 */
function syncPagingCursor(res: Awaited<ReturnType<typeof sendMapLeadMessage>>) {
    nextPageToFetch.value = Number(res.nextPage) > 0 ? Number(res.nextPage) : 0;
    exhausted.value = !!res.exhausted || nextPageToFetch.value <= 0;
}

// 把一次对话结果写进一个新批次(自动去掉与历史批次重复的卡片)
function pushResultBatch(res: Awaited<ReturnType<typeof sendMapLeadMessage>>) {
    replyError.value = "";
    bindConversationId(res.conversationId);
    syncPagingCursor(res);

    const seen = collectSeenCardKeys();
    const newCards = (res.cards as BizCard[]).filter((card) => {
        const key = String(card.key || "");
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
    });

    if (res.totalCount > 0) gaodeTotalCount.value = res.totalCount;

    // 接口有返回但全是重复数据 → 视为翻页耗尽,避免无限「继续获取」叠同款
    if (!newCards.length) {
        exhausted.value = true;
        nextPageToFetch.value = 0;
        // 首轮无任何商家卡片 → 展示空数据 UI（此前会静默空白）
        if (!batches.value.length) {
            emptyResult.value = true;
        }
        if (gaodeTotalCount.value > 0) {
            gaodeTotalCount.value = Math.min(gaodeTotalCount.value, totalFetched.value);
        }
        totalTarget.value = Math.max(totalTarget.value, gaodeTotalCount.value || totalFetched.value);
        emit("progress");
        return false;
    }

    emptyResult.value = false;
    const batchId = _id++;
    batches.value.push({
        id: batchId,
        query: lastQuery.value,
        size: newCards.length,
        fetched: newCards.length,
        cards: newCards,
        loading: false,
        expanded: false,
        messageId: res.messageId,
    });
    totalTarget.value = Math.max(totalTarget.value, gaodeTotalCount.value || totalFetched.value);
    emit("progress");
    return true;
}

/**
 * 抓取(后端会话):
 *   isFirst=true  → 新一轮搜索(可不带 page)
 *   isFirst=false → 带 query + conversation_id + page(=next_page) 翻下一页
 */
async function startFetch(payload: { query: string; isFirst: boolean }) {
    if (fetching.value) return;
    if (payload.isFirst || payload.query) {
        lastQuery.value = payload.query;
    }
    fetching.value = true;

    try {
        if (payload.isFirst) {
            batches.value = [];
            totalTarget.value = 0;
            gaodeTotalCount.value = 0;
            exhausted.value = false;
            nextPageToFetch.value = 0;
            replyError.value = "";
            emptyResult.value = false;

            // ⭐ 思考 loading — 首轮无 loading 批次,先转圈再落卡片
            thinking.value = true;
            const thinkStart = Date.now();
            try {
                const res = await sendMapLeadMessage(payload.query, conversationId || undefined);
                const elapsed = Date.now() - thinkStart;
                if (elapsed < THINKING_MIN_MS) await sleep(THINKING_MIN_MS - elapsed);
                bindConversationId(res.conversationId);
                if (res.isError) {
                    // content_type=error 时展示后端具体原因(如「未识别出商家类型」)
                    replyError.value = res.errorMessage || SYSTEM_ERROR_TEXT;
                    emptyResult.value = false;
                    syncPagingCursor(res);
                    return;
                }
                pushResultBatch(res);
            } catch (e: any) {
                const elapsed = Date.now() - thinkStart;
                if (elapsed < THINKING_MIN_MS) await sleep(THINKING_MIN_MS - elapsed);
                const msg = e || SYSTEM_ERROR_TEXT;
                replyError.value = msg;
                feedback.msgError(msg);
                emptyResult.value = false;
            } finally {
                thinking.value = false;
            }
            return;
        }

        // 继续获取:原 query + conversation_id + page(上次 next_page)
        if (!lastQuery.value || !conversationId || nextPageToFetch.value <= 0) {
            exhausted.value = true;
            return;
        }
        const page = nextPageToFetch.value;
        const batchId = _id++;
        batches.value.push({
            id: batchId,
            query: lastQuery.value,
            size: 25,
            fetched: 0,
            cards: [],
            loading: true,
            expanded: false,
        });
        try {
            const res = await sendMapLeadMessage(lastQuery.value, conversationId, { page });
            bindConversationId(res.conversationId);
            batches.value = batches.value.filter((b) => b.id !== batchId);
            if (res.isError) {
                replyError.value = res.errorMessage || SYSTEM_ERROR_TEXT;
                syncPagingCursor(res);
                return;
            }
            pushResultBatch(res);
        } catch (e: any) {
            batches.value = batches.value.filter((b) => b.id !== batchId);
            console.warn("[map] chat failed:", e?.message ?? e);
            const msg = e?.msg || e?.message || SYSTEM_ERROR_TEXT;
            replyError.value = msg;
            feedback.msgError(msg);
        }
    } finally {
        fetching.value = false;
    }
}

// 已达到后端报告的总数 → "已全部获取"徽章
const isFullyComplete = computed(() => gaodeTotalCount.value > 0 && totalFetched.value >= gaodeTotalCount.value);
// 是否还能"继续获取":有下一页游标、未拉满、未耗尽
const canContinue = computed(
    () =>
        !!conversationId &&
        !!lastQuery.value &&
        nextPageToFetch.value > 0 &&
        !exhausted.value &&
        !isFullyComplete.value,
);

// 「继续获取」:带 page=next_page 循环翻页,拉到总数或拉不动为止
async function continueFetchAll() {
    if (!conversationId || !lastQuery.value) return;
    const MAX_PAGES = 20;
    let pagesLeft = MAX_PAGES;
    let stuckRounds = 0;

    while (canContinue.value && pagesLeft > 0) {
        const beforeFetched = totalFetched.value;
        const pageBefore = nextPageToFetch.value;
        await startFetch({ query: lastQuery.value, isFirst: false });
        pagesLeft--;

        // 这一轮没拿到任何新卡,或游标没前进
        if (totalFetched.value === beforeFetched || nextPageToFetch.value === pageBefore) {
            stuckRounds++;
            if (stuckRounds >= 2) {
                feedback.msg(`已尽力拉取,共获取 ${totalFetched.value} 条`);
                gaodeTotalCount.value = totalFetched.value;
                nextPageToFetch.value = 0;
                exhausted.value = true;
                break;
            }
        } else {
            stuckRounds = 0;
        }
    }
}

/** 错误后用同一 query 重新发起首轮回答 */
function retryAnswer() {
    if (fetching.value || !lastQuery.value) return;
    startFetch({ query: lastQuery.value, isFirst: true });
}

function reset() {
    batches.value = [];
    priorTurns.value = [];
    totalTarget.value = 0;
    gaodeTotalCount.value = 0;
    exhausted.value = false;
    nextPageToFetch.value = 0;
    conversationId = "";
    replyError.value = "";
    emptyResult.value = false;
    lastQuery.value = "";
}

function restoreBatches(arr: Batch[], target: number, prior: PriorTurn[] = []) {
    batches.value = Array.isArray(arr) ? arr : [];
    totalTarget.value = target || 0;
    priorTurns.value = Array.isArray(prior) ? prior : [];
}

/**
 * 按 URL / 外部传入的 conversation_id 拉消息记录并回显。
 * 最后一轮作为当前 batches / 错误态,更早轮次进 priorTurns。
 */
async function restoreConversation(id: string): Promise<{ lastUserText: string } | null> {
    if (!id) return null;
    try {
        const view = await loadMapLeadConversation(id);
        bindConversationId(view.conversationId || id);
        if (!view.turns.length) {
            priorTurns.value = [];
            batches.value = [];
            replyError.value = "";
            emptyResult.value = false;
            lastQuery.value = "";
            emit("restored", { lastUserText: "" });
            return { lastUserText: "" };
        }

        const prior = view.turns.slice(0, -1);
        const last = view.turns[view.turns.length - 1];
        priorTurns.value = prior.map((t) => ({
            userText: t.userText,
            cards: t.cards as BizCard[],
        }));

        lastQuery.value = last.query || last.userText;
        batches.value = [];
        replyError.value = "";
        emptyResult.value = false;
        gaodeTotalCount.value = 0;
        totalTarget.value = 0;
        exhausted.value = false;
        nextPageToFetch.value = 0;

        if (last.isError) {
            replyError.value = last.errorMessage || SYSTEM_ERROR_TEXT;
        } else if (!last.cards.length) {
            emptyResult.value = true;
            exhausted.value = true;
        } else {
            batches.value = [
                {
                    id: _id++,
                    query: last.query || last.userText,
                    size: last.cards.length,
                    fetched: last.cards.length,
                    cards: last.cards as BizCard[],
                    loading: false,
                    expanded: false,
                    messageId: last.messageId,
                },
            ];
            if (last.totalCount > 0) gaodeTotalCount.value = last.totalCount;
            totalTarget.value = last.totalCount || last.cards.length;
            nextPageToFetch.value = Number(last.nextPage) > 0 ? Number(last.nextPage) : 0;
            exhausted.value = !!last.exhausted || nextPageToFetch.value <= 0;
        }

        const lastUserText = last.userText || last.query || "";
        emit("restored", { lastUserText });
        emit("progress");
        return { lastUserText };
    } catch (e: any) {
        console.warn("[map] restore conversation failed:", e?.message ?? e);
        feedback.msgError("加载地图获客会话失败，请稍后重试");
        return null;
    }
}

/** 父级在用户发起"第二次/第三次..."搜索时调用 — 把当前 batches 平铺成只读快照塞进 priorTurns,
 *  接着 startFetch(isFirst=true) 会清空 batches 开始新一轮 */
function archiveCurrent(userText: string) {
    const cards: BizCard[] = batches.value.flatMap((b) => b.cards);
    if (!cards.length) return;
    priorTurns.value.push({ userText, cards });
}

// History — 走后端会话列表接口
const historyOpen = ref(false);
const historyLoading = ref(false);
const historyDetail = ref<HistoryItem | null>(null);
const detailCards = ref<BizCard[]>([]);
/** 历史详情里用于导出的助手 message_id */
const historyExportMessageId = ref<number | null>(null);
const historyPage = ref(1);
const pageSize = 10;
const historyTotal = ref(0);
const realHistory = ref<HistoryItem[]>([]);

const totalHistoryPages = computed(() => Math.max(1, Math.ceil(historyTotal.value / pageSize)));
const pageItems = computed(() => realHistory.value);

function formatHistoryTime(raw: string | number | undefined) {
    if (!raw) return "-";
    if (typeof raw === "string" && raw.includes("-")) return raw.slice(0, 16);
    const d = new Date(typeof raw === "number" ? raw : Date.parse(raw));
    if (Number.isNaN(d.getTime())) return String(raw);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(
        2,
        "0",
    )} ${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

function normalizeHistoryItem(raw: any, index: number): HistoryItem {
    const conversationId = String(raw?.conversation_id ?? raw?.id ?? "");
    const title = String(raw?.title ?? raw?.query ?? raw?.last_query ?? raw?.content ?? "未命名");
    return {
        id: conversationId || `row-${index}`,
        conversationId,
        city: title,
        kind: "地图获客",
        count: Number(raw?.lead_count ?? raw?.total_count ?? raw?.count ?? 0),
        time: formatHistoryTime(raw?.create_time ?? raw?.update_time ?? raw?.timestamp),
        source: "高德地图",
    };
}

async function loadHistoryList() {
    historyLoading.value = true;
    try {
        const data = await getMapLeadConversations({
            page_no: historyPage.value,
            page_size: pageSize,
        });
        const lists = Array.isArray(data)
            ? data
            : Array.isArray(data?.lists)
            ? data.lists
            : Array.isArray(data?.data)
            ? data.data
            : [];
        historyTotal.value = Number(data?.count ?? data?.total ?? lists.length);
        realHistory.value = lists.map(normalizeHistoryItem);
    } catch (e: any) {
        console.warn("[map] load conversations failed:", e?.message ?? e);
        realHistory.value = [];
        historyTotal.value = 0;
        feedback.msgError("加载历史会话失败");
    } finally {
        historyLoading.value = false;
    }
}

async function openDetail(t: HistoryItem) {
    if (!t.conversationId) return;
    historyDetail.value = t;
    detailCards.value = [];
    historyExportMessageId.value = null;
    try {
        const view = await loadMapLeadConversation(t.conversationId);
        detailCards.value = view.turns.flatMap((turn) => turn.cards as BizCard[]);
        for (let i = view.turns.length - 1; i >= 0; i--) {
            const turn = view.turns[i];
            if (!turn.isError && turn.messageId) {
                historyExportMessageId.value = turn.messageId;
                break;
            }
        }
        if (!t.count && detailCards.value.length) {
            historyDetail.value = { ...t, count: detailCards.value.length };
        }
    } catch (e: any) {
        console.warn("[map] load history detail failed:", e?.message ?? e);
        feedback.msgError("加载会话详情失败");
    }
}

async function runExport(messageId: number | null | undefined) {
    if (!messageId || exporting.value) return;
    exporting.value = true;
    try {
        await downloadMapLeadExport(messageId);
        feedback.msgSuccess("导出成功，开始下载");
        emit("export", batches.value);
    } catch (e: any) {
        console.warn("[map] export failed:", e?.message ?? e);
        feedback.msgError(e?.message || "导出失败，请稍后重试");
    } finally {
        exporting.value = false;
    }
}

function exportCurrent() {
    runExport(currentExportMessageId.value);
}

/** 打开历史中的会话到当前对话区 */
async function resumeHistory(t: HistoryItem) {
    if (!t.conversationId) return;
    historyOpen.value = false;
    historyDetail.value = null;
    detailCards.value = [];
    const result = await restoreConversation(t.conversationId);
    if (result) {
        emit("restored", { lastUserText: result.lastUserText });
        emit("progress");
    }
}

async function openHistory() {
    historyOpen.value = true;
    historyDetail.value = null;
    detailCards.value = [];
    historyExportMessageId.value = null;
    historyPage.value = 1;
    await loadHistoryList();
}

function downloadHistory(_t: HistoryItem) {
    runExport(historyExportMessageId.value);
}

function onKey(e: KeyboardEvent) {
    if (e.key === "Escape" && historyOpen.value) historyOpen.value = false;
}
onMounted(() => window.addEventListener("keydown", onKey));
onBeforeUnmount(() => window.removeEventListener("keydown", onKey));

watch(historyPage, () => {
    if (historyOpen.value) loadHistoryList();
});

defineExpose({
    startFetch,
    continueFetchAll,
    openHistory,
    reset,
    restoreBatches,
    restoreConversation,
    archiveCurrent,
    batches,
    priorTurns,
    totalTarget,
    totalFetched,
    progressPct,
    gaodeTotalCount,
    exhausted,
    getConversationId: () => conversationId,
});
</script>

<style lang="scss" scoped>
/* 仅保留 Tailwind 难表达的关键帧动画 */
.th-dots {
    animation: dots 1.4s steps(4, end) infinite;
}
.stream-in {
    animation: streamIn 0.25s ease both;
}
@keyframes dots {
    0% {
        width: 0;
    }
    100% {
        width: 1em;
    }
}
@keyframes streamIn {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
