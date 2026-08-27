<template>
    <div v-loading="loading">
        <!-- 指标卡 -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <el-card v-for="card in statCards" :key="card.label" class="!border-none" shadow="never">
                <div class="text-sm text-[#6b7280]">{{ card.label }}</div>
                <div class="mt-2 text-2xl font-semibold text-[#111827]">{{ card.value }}</div>
                <div v-if="card.sub" class="mt-1 text-xs text-[#9ca3af]">{{ card.sub }}</div>
            </el-card>
        </div>

        <!-- 14 日趋势 -->
        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-[#111827]">近14日分析 / 创作量</span>
                <span class="text-xs text-[#9ca3af]">
                    服务状态:{{ data.health?.ok ? "正常" : "异常" }} · 合成引擎 {{ data.health?.video_provider || "-" }}
                </span>
            </div>
            <v-charts style="height: 280px" :option="trendChart" :autoresize="true" />
        </el-card>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- 人设使用排行 -->
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 text-sm font-medium text-[#111827]">人设使用排行</div>
                <el-table size="small" :data="data.persona_rank || []">
                    <el-table-column label="人设" min-width="140">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2">
                                <el-avatar :size="24" :src="row.avatar" />
                                <span>{{ row.name }}</span>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="分析次数" prop="count" width="100" />
                    <el-table-column label="平均契合度" width="110">
                        <template #default="{ row }">{{ row.avg_fit }} 分</template>
                    </el-table-column>
                </el-table>
                <div v-if="!(data.persona_rank || []).length" class="py-4 text-center text-xs text-[#9ca3af]">
                    暂无分析记录
                </div>
            </el-card>

            <!-- 快照与最近动态 -->
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 text-sm font-medium text-[#111827]">热榜快照存量</div>
                <el-table size="small" :data="data.snapshots || []">
                    <el-table-column label="平台" prop="label" width="100" />
                    <el-table-column label="累计天数" prop="days" width="100" />
                    <el-table-column label="最新快照" min-width="120">
                        <template #default="{ row }">{{ row.latest || "暂无" }}</template>
                    </el-table-column>
                </el-table>
                <div class="mb-2 mt-4 text-sm font-medium text-[#111827]">最近动态</div>
                <div
                    v-for="(item, index) in data.recent || []"
                    :key="index"
                    class="flex items-center gap-2 border-b border-[#f3f4f6] py-2 text-xs last:border-b-0">
                    <el-tag :type="item.kind === 'analysis' ? 'primary' : 'success'" size="small">
                        {{ item.kind === "analysis" ? "分析" : "创作" }}
                    </el-tag>
                    <span class="flex-1 truncate text-[#374151]">{{ item.topic }}</span>
                    <span class="text-[#9ca3af]">{{ item.who || "-" }}</span>
                    <span class="text-[#9ca3af]">{{ item.extra }}</span>
                </div>
                <div v-if="!(data.recent || []).length" class="py-4 text-center text-xs text-[#9ca3af]">暂无动态</div>
            </el-card>
        </div>
    </div>
</template>

<script lang="ts" setup name="hotspotOverview">
import vCharts from "vue-echarts";
import { getHotspotOverview } from "@/api/ai_application/hotspot";

const loading = ref(true);
const data = ref<any>({});

const statCards = computed(() => {
    const totals = data.value.totals || {};
    return [
        { label: "累计分析", value: totals.analyses ?? 0, sub: `今日 ${totals.analyses_today ?? 0}` },
        { label: "累计创作", value: totals.creations ?? 0, sub: `今日 ${totals.creations_today ?? 0}` },
        { label: "合成中任务", value: totals.tasks_running ?? 0, sub: "视频合成进行中" },
        { label: "已完成任务", value: totals.tasks_done ?? 0, sub: "成片已生成" },
    ];
});

const trendChart = computed(() => {
    const trend = data.value.trend || [];
    return {
        grid: { left: 40, right: 16, top: 30, bottom: 24 },
        tooltip: { trigger: "axis" },
        legend: { data: ["分析", "创作"], top: 0 },
        xAxis: { type: "category", data: trend.map((t: any) => t.date), boundaryGap: false },
        yAxis: { type: "value", minInterval: 1 },
        series: [
            {
                name: "分析",
                type: "line",
                data: trend.map((t: any) => Number(t.analyses)),
                smooth: true,
                showSymbol: false,
                lineStyle: { color: "#3b82f6" },
                itemStyle: { color: "#3b82f6" },
                areaStyle: { opacity: 0.08, color: "#3b82f6" },
            },
            {
                name: "创作",
                type: "line",
                data: trend.map((t: any) => Number(t.creations)),
                smooth: true,
                showSymbol: false,
                lineStyle: { color: "#10b981" },
                itemStyle: { color: "#10b981" },
                areaStyle: { opacity: 0.08, color: "#10b981" },
            },
        ],
    };
});

const load = async () => {
    loading.value = true;
    try {
        data.value = await getHotspotOverview();
    } finally {
        loading.value = false;
    }
};

load();
</script>
