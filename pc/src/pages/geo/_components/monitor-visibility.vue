<template>
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-br px-5 py-3 flex items-center gap-4 flex-wrap">
            <GeoFilterBar
                v-model:date="filter.date"
                v-model:engine="filter.engine"
                v-model:topic-id="filter.topic_id"
                :engines="engines"
                :topics="topics"
                @change="load" />
            <div class="ml-auto flex items-center gap-4">
                <label
                    class="flex items-center gap-2 text-sm text-slate-500 cursor-pointer select-none"
                    title="开启后每天凌晨自动全量采集一轮(所有启用问题 × 已接入引擎),与手动诊断同价计费;算力不足自动跳过当日">
                    <span>每日自动监测</span>
                    <ElSwitch :model-value="!!autoMonitor" :loading="savingAuto" @change="toggleAutoMonitor" />
                </label>
                <div class="flex items-center gap-2">
                    <ElButton
                        type="primary"
                        class="!h-11 !px-5 !rounded-xl"
                        :loading="diagRunning"
                        @click="runDiagnosis">
                        {{ diagRunning ? `诊断中 ${diagDone}/${diagTotal}` : "一键诊断" }}
                    </ElButton>
                    <span class="text-slate-400 text-xs leading-4"
                        >按模型用量计费<br />失败不扣</span
                    >
                </div>
            </div>
        </div>

        <div class="space-y-4 min-h-[360px]" v-spin="{ show: contentLoading, text: '加载中...' }">
            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <div class="grid grid-cols-5 divide-x divide-[#F1F5F9]">
                    <div v-for="c in kpiCards" :key="c.label" class="px-5 py-4">
                        <div class="text-xs text-slate-500">{{ c.label }}</div>
                        <div
                            class="tabular-nums mt-1.5 leading-none"
                            :class="
                                c.primary
                                    ? 'text-[28px] font-bold text-primary'
                                    : 'text-2xl font-semibold text-slate-900'
                            ">
                            {{ c.val }}
                        </div>
                        <div class="text-xs mt-2" :class="c.deltaClass">较上一期 {{ c.delta }}</div>
                    </div>
                </div>
                <div class="border-t border-[#F1F5F9] px-5 pt-4 pb-5">
                    <div class="flex items-center justify-between gap-4 mb-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">可见度趋势</div>
                            <div class="text-xs text-slate-400 mt-0.5">按天看平均可见度分与在线率</div>
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <span class="flex items-center gap-1.5 text-xs text-slate-500">
                                <i class="inline-block w-3 h-0.5 rounded bg-primary"></i>平均可见度分
                            </span>
                            <span class="flex items-center gap-1.5 text-xs text-slate-500">
                                <i class="inline-block w-3 h-0.5 rounded bg-emerald-500"></i>在线率
                            </span>
                            <ElSelect v-model="trendDays" style="width: 108px" @change="loadTrend">
                                <ElOption label="近 7 天" :value="7" />
                                <ElOption label="近 30 天" :value="30" />
                                <ElOption label="近 90 天" :value="90" />
                            </ElSelect>
                        </div>
                    </div>
                    <TrendChart :series="trendSeries" :height="280" />
                </div>
            </section>

            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#F1F5F9]">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">竞争格局</div>
                        <div class="text-xs text-slate-400 mt-0.5">各品牌在 AI 回答中的排名</div>
                    </div>
                    <GeoSubTabs
                        :model-value="compTab"
                        :tabs="compTabs"
                        class="!border-0"
                        @update:model-value="compTab = $event" />
                </div>
                <!-- 没配竞品名单时榜单只有本品牌、恒排第1,不提示会被当成缺陷 -->
                <div
                    v-if="!hasCompetitors"
                    class="px-5 py-2.5 border-b border-[#F1F5F9] bg-[#F8FAFF] text-sm text-slate-600 flex items-center gap-2 flex-wrap">
                    <Icon name="el-icon-InfoFilled" :size="14" class="text-primary shrink-0" />
                    <span>还没有配置竞品品牌，当前榜单只有本品牌，排名恒为第 1，不代表真实竞争力。</span>
                    <button type="button" class="text-primary font-medium hover:underline" @click="emit('go', 'set_brand')">
                        去「设置-品牌画像」添加竞品
                    </button>
                    <span class="text-slate-400 text-xs">添加后重新诊断即可纳入对比</span>
                </div>
                <div class="min-h-[120px]" v-spin="{ show: compLoading, text: '加载中...' }">
                <ElTable :data="compRows" max-height="520" class="geo-plain-table">
                    <ElTableColumn label="排名" width="70">
                        <template #default="{ row }">
                            <span v-if="row.rank > 0 && row.rank <= 3" class="medal" :class="'m' + row.rank">{{ row.rank }}</span>
                            <span v-else class="text-slate-500 pl-2">{{ row.rank > 0 ? row.rank : '未上榜' }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="品牌" min-width="150">
                        <template #default="{ row }">
                            <span :class="row.is_self ? 'text-primary font-semibold' : 'text-slate-700'">
                                {{ row.brand }}
                                <ElTag v-if="row.is_self" size="small" class="ml-1" effect="light">本品牌</ElTag>
                            </span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="可见度" width="180">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 rounded-full bg-slate-100 flex-1 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        :style="{ width: Math.min(100, row.visibility) + '%' }"></div>
                                </div>
                                <span class="text-slate-700 text-sm w-[52px] tabular-nums">{{ row.visibility }}%</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="场景问题在线数" prop="online_count" width="120" align="center" />
                    <ElTableColumn label="平均位置" width="90" align="center"
                        ><template #default="{ row }">{{ row.avg_pos || "–" }}</template></ElTableColumn
                    >
                    <ElTableColumn label="首推占比" width="90" align="center"
                        ><template #default="{ row }">{{ row.top1_rate }}%</template></ElTableColumn
                    >
                    <ElTableColumn label="前3占比" width="90" align="center"
                        ><template #default="{ row }">{{ row.top3_rate }}%</template></ElTableColumn
                    >
                    <ElTableColumn label="前5占比" width="90" align="center"
                        ><template #default="{ row }">{{ row.top5_rate }}%</template></ElTableColumn
                    >
                    <template #empty>
                        <GeoEmpty description="暂无数据，先运行一键诊断">
                            <template #action>
                                <ElButton
                                    type="primary"
                                    class="!h-11 !px-5 !rounded-xl"
                                    :loading="diagRunning"
                                    @click="runDiagnosis"
                                    >一键诊断</ElButton
                                >
                            </template>
                        </GeoEmpty>
                    </template>
                </ElTable>
                </div>
            </section>

            <section class="bg-white rounded-xl border border-br overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#F1F5F9]">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">平台 / 话题维度</div>
                        <div class="text-xs text-slate-400 mt-0.5">按引擎或话题拆开看同一套指标</div>
                    </div>
                    <GeoSubTabs
                        :model-value="dimTab"
                        :tabs="dimTabs"
                        class="!border-0"
                        @update:model-value="dimTab = $event" />
                </div>
                <ElTable :data="dimRows" class="geo-plain-table">
                    <ElTableColumn :label="dimTab === 'engine' ? 'AI平台' : '话题'" min-width="150">
                        <template #default="{ row }">{{ dimTab === "engine" ? row.label : row.topic_name }}</template>
                    </ElTableColumn>
                    <ElTableColumn label="可见度" width="120" align="center"
                        ><template #default="{ row }">{{ row.visibility }}%</template></ElTableColumn
                    >
                    <ElTableColumn label="在线/采集" width="110" align="center"
                        ><template #default="{ row }">{{ row.online }} / {{ row.total }}</template></ElTableColumn
                    >
                    <ElTableColumn label="平均位置" width="100" align="center"
                        ><template #default="{ row }">{{ row.avg_pos || "–" }}</template></ElTableColumn
                    >
                    <ElTableColumn label="首推占比" width="100" align="center"
                        ><template #default="{ row }">{{ row.top1_rate }}%</template></ElTableColumn
                    >
                    <ElTableColumn label="前3占比" width="100" align="center"
                        ><template #default="{ row }">{{ row.top3_rate }}%</template></ElTableColumn
                    >
                    <ElTableColumn label="前5占比" width="100" align="center"
                        ><template #default="{ row }">{{ row.top5_rate }}%</template></ElTableColumn
                    >
                    <template #empty><GeoEmpty description="暂无数据" /></template>
                </ElTable>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from "element-plus";
import { geoConfirm } from "../_composables/geo-confirm";
import {
    geoInsightOverview,
    geoTopics,
    geoMonitorEngines,
    geoQuestions,
    geoMonitorBatch,
    geoMonitorProgress,
    geoInsightVisibilityTrend,
    geoProjectUpdate,
} from "@/api/geo";
import TrendChart from "./trend-chart.vue";
import GeoFilterBar from "./geo-filter-bar.vue";
import GeoSubTabs from "./geo-sub-tabs.vue";
import GeoEmpty from "./geo-empty.vue";

const props = defineProps<{ pid: number; info: any }>();
const emit = defineEmits(["go"]);
// 项目是否配置了竞品名单(品牌画像-竞品品牌):监测的竞争格局按这份名单对比提及
const hasCompetitors = computed(() => (props.info?.competitors || []).filter((c: any) => String(c || "").trim()).length > 0);
const errText = (e: any) => (typeof e === "string" ? e : e?.msg || "操作失败");

const filter = reactive<any>({ date: "", engine: "", topic_id: "" });
const engines = ref<any[]>([]);
const topics = ref<any[]>([]);
const cards = ref<any>({});
const delta = ref<any>({});
const competitors = ref<any>({ overall: [] });
const engineDim = ref<any[]>([]);
const topicDim = ref<any[]>([]);
const compTab = ref("overall");
const dimTab = ref("engine");
const dimTabs = [
    { key: "engine", label: "AI 平台" },
    { key: "topic", label: "话题维度" },
];
// 各引擎实际使用的大模型映射(engine_models):统计/计费按它归组,tab 上不展示
const engineModels = ref<Record<string, string>>({});
const compTabs = computed(() => [
    { key: "overall", label: "品牌整体" },
    ...engines.value.filter((x: any) => x.available).map((e: any) => ({ key: e.key, label: e.label })),
]);

const fmtPct = (v: any) => (v == null ? "–" : `${v}%`);
const fmtDelta = (v: any, suffix = "") =>
    v == null ? "–" : v > 0 ? `▲${v}${suffix}` : v < 0 ? `▼${Math.abs(v)}${suffix}` : "持平";
const deltaClass = (v: any) => (v > 0 ? "text-emerald-600" : v < 0 ? "text-rose-500" : "text-slate-400");
const kpiCards = computed(() => [
    {
        label: "平均可见度",
        val: fmtPct(cards.value.visibility),
        delta: fmtDelta(delta.value.visibility, "%"),
        deltaClass: deltaClass(delta.value.visibility),
        primary: true,
    },
    {
        label: "平均位置",
        val: cards.value.avg_pos || "–",
        delta: fmtDelta(delta.value.avg_pos),
        deltaClass: deltaClass(delta.value.avg_pos),
        primary: false,
    },
    {
        label: "首推占比",
        val: fmtPct(cards.value.top1_rate),
        delta: fmtDelta(delta.value.top1_rate, "%"),
        deltaClass: deltaClass(delta.value.top1_rate),
        primary: false,
    },
    {
        label: "前3占比",
        val: fmtPct(cards.value.top3_rate),
        delta: fmtDelta(delta.value.top3_rate, "%"),
        deltaClass: deltaClass(delta.value.top3_rate),
        primary: false,
    },
    {
        label: "前5占比",
        val: fmtPct(cards.value.top5_rate),
        delta: fmtDelta(delta.value.top5_rate, "%"),
        deltaClass: deltaClass(delta.value.top5_rate),
        primary: false,
    },
]);
const compRows = computed(() => competitors.value[compTab.value] || []);

// 切换竞争格局 tab 时按对应引擎拉最新一份数据(模型计费口径下保证统计实时),
// 序号守卫防快速切换时旧响应覆盖新数据
const compLoading = ref(false);
let compSeq = 0;
watch(compTab, async (tab) => {
    const seq = ++compSeq;
    compLoading.value = true;
    try {
        const res: any = await geoInsightOverview({
            project_id: props.pid,
            ...filter,
            engine: tab === "overall" ? filter.engine || "" : tab,
        });
        if (seq !== compSeq) return;
        const grp = res?.competitors || {};
        competitors.value = { ...competitors.value, [tab]: grp[tab] || grp.overall || [] };
        engineModels.value = { ...engineModels.value, ...(res?.engine_models || {}) };
    } catch (e) {
        if (seq === compSeq) ElMessage.error(errText(e));
    } finally {
        if (seq === compSeq) compLoading.value = false;
    }
});
const dimRows = computed(() => (dimTab.value === "engine" ? engineDim.value : topicDim.value));

// ---- 可见度趋势曲线(二期) ----
const trendDays = ref(30);
const trendRows = ref<any[]>([]);
const trendSeries = computed(() => [
    {
        name: "平均可见度分",
        color: "#0065fb",
        unit: "",
        data: trendRows.value.map((r: any) => ({
            label: r.date,
            value: r.visibility,
            extra: `${r.online}/${r.total} 在线`,
        })),
    },
    {
        name: "在线率",
        color: "#10b981",
        unit: "%",
        data: trendRows.value.map((r: any) => ({ label: r.date, value: r.online_rate })),
    },
]);
const loadTrend = async () => {
    try {
        trendRows.value = (await geoInsightVisibilityTrend({ project_id: props.pid, days: trendDays.value })) || [];
    } catch (e) {
        /* 可选数据 */
    }
};

// ---- 每日自动监测开关(二期) ----
const autoMonitor = ref(Number(props.info?.auto_monitor || 0));
const savingAuto = ref(false);
const toggleAutoMonitor = async (v: boolean) => {
    savingAuto.value = true;
    try {
        await geoProjectUpdate({ id: props.pid, auto_monitor: v ? 1 : 0 });
        autoMonitor.value = v ? 1 : 0;
        ElMessage.success(
            v
                ? "已开启每日自动监测:每天凌晨自动全量采集一轮(计费同手动诊断,算力不足自动跳过当日)"
                : "已关闭每日自动监测",
        );
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        savingAuto.value = false;
    }
};

// 请求序号守卫:快速切换筛选时旧请求晚返回不允许覆盖新数据(同 monitor-snapshots 的做法)
let loadSeq = 0;
const contentLoading = ref(false);
const load = async () => {
    const seq = ++loadSeq;
    contentLoading.value = true;
    try {
        const res: any = await geoInsightOverview({ project_id: props.pid, ...filter });
        if (seq !== loadSeq) return;
        cards.value = res?.cards || {};
        delta.value = res?.delta || {};
        competitors.value = res?.competitors || { overall: [] };
        engineModels.value = res?.engine_models || {};
        engineDim.value = res?.engine_dim || [];
        topicDim.value = res?.topic_dim || [];
    } catch (e) {
        if (seq === loadSeq) ElMessage.error(errText(e));
    } finally {
        if (seq === loadSeq) contentLoading.value = false;
    }
};

// 一键诊断:所有启用场景问题 × 可用引擎
const diagRunning = ref(false);
const diagTotal = ref(0);
const diagDone = ref(0);
// 切换菜单/品牌导致组件卸载时终止循环,避免死组件里继续发几十个慢请求、
// 与新实例的再次诊断并发造成重复采集
let diagCancelled = false;
onUnmounted(() => {
    diagCancelled = true;
    stopDiagPolling();
});
const runDiagnosis = async () => {
    if (diagRunning.value) return;
    diagRunning.value = true;
    try {
        // 拉取失败要报"加载失败"而不是误导性的"没有场景问题"
        let qs: any = null;
        try {
            qs = await geoQuestions({ project_id: props.pid, status: 1 });
        } catch (e) {
            ElMessage.error("场景问题加载失败,请重试:" + errText(e));
            return;
        }
        const avail = engines.value.filter((e: any) => e.available).map((e: any) => e.key);
        // 兼容全量数组与分页 {list,total} 两种返回形态
        const list = Array.isArray(qs) ? qs : qs?.list || [];
        if (!list.length) {
            ElMessage.warning("还没有启用的场景问题,先去「设置-话题」添加");
            return;
        }
        // 模型计费口径:无固定场景价,确认框只列规模不报价
        try {
            await geoConfirm({
                title: "一键诊断",
                message: "将对启用中的场景问题发起全引擎监测，按模型用量计费，失败的引擎不扣。",
                confirmText: "开始诊断",
                tone: "info",
                facts: [
                    { label: "场景问题", value: `${list.length} 个` },
                    { label: "AI 引擎", value: `${Math.max(1, avail.length)} 个` },
                ],
                note: "按模型用量计费，失败不扣",
            });
        } catch {
            diagRunning.value = false;
            return; // 用户取消
        }
        // 批次异步执行(后端队列/cron 消费):刷新页面不中断,进度可恢复
        const res: any = await geoMonitorBatch({ project_id: props.pid });
        diagTotal.value = Number(res?.total || 0);
        diagDone.value = 0;
        startDiagPolling(Number(res?.batch_task_id || 0));
    } catch (e) {
        const msg = errText(e);
        // 后端防重复提交:已有进行中的批次 → 不报错,直接恢复该批次的进度轮询
        if (msg.includes("还在进行中")) {
            ElMessage.info(msg);
            startDiagPolling(0);
            return;
        }
        ElMessage.error(msg);
        diagRunning.value = false;
        return;
    }
    // 提交成功:diagRunning 保持 true,由轮询在批次终态时复位
};

// 诊断进度轮询:每 5s 查一次批次进度;组件卸载只停轮询,后端批次继续跑
let diagTimer: any = null;
const stopDiagPolling = () => {
    if (diagTimer) {
        clearInterval(diagTimer);
        diagTimer = null;
    }
};
const startDiagPolling = (batchId = 0) => {
    stopDiagPolling();
    diagRunning.value = true;
    const tick = async () => {
        try {
            const p: any = await geoMonitorProgress(props.pid, 0, batchId);
            diagDone.value = Number(p?.done || 0);
            diagTotal.value = Number(p?.total || diagTotal.value);
            if (p?.finished) {
                stopDiagPolling();
                diagRunning.value = false;
                ElMessage.success(`诊断完成:成功 ${p?.success ?? 0} · 失败 ${p?.failed ?? 0}`);
                load();
                loadTrend();
            }
        } catch (e) {
            /* 网络抖动,下一轮再试 */
        }
    };
    tick();
    diagTimer = setInterval(tick, 5000);
};

// 刷新/重进页面时恢复进行中的诊断批次(后端自动取该项目最近批次;
// batch_task_id>0 才说明真的存在批次,避免把"从未诊断"误判成进行中)
const resumeDiagIfRunning = async () => {
    try {
        const p: any = await geoMonitorProgress(props.pid, 0, 0);
        if (Number(p?.batch_task_id || 0) > 0 && p?.finished === false) {
            diagTotal.value = Number(p?.total || 0);
            diagDone.value = Number(p?.done || 0);
            startDiagPolling(Number(p.batch_task_id));
        }
    } catch (e) {
        /* 恢复失败不影响页面其他功能 */
    }
};

onMounted(async () => {
    try {
        const [eRes, tRes]: any = await Promise.all([geoMonitorEngines(), geoTopics(props.pid)]);
        engines.value = eRes || [];
        topics.value = tRes?.list || [];
    } catch (e) {
        /* 忽略 */
    }
    load();
    // 刷新前若有进行中的诊断批次,恢复进度显示并继续轮询
    resumeDiagIfRunning();
    loadTrend();
});
</script>

<style lang="scss" scoped>
.medal {
    @apply inline-flex w-6 h-6 rounded-full text-white text-xs font-bold items-center justify-center;
}
.medal.m1 {
    background: #f5b940;
}
.medal.m2 {
    background: #9aa8bd;
}
.medal.m3 {
    background: #d29060;
}
</style>

