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

        <!-- 30 日趋势 -->
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 text-sm font-medium text-[#111827]">近30日监测量</div>
                <v-charts style="height: 260px" :option="monitorChart" :autoresize="true" />
            </el-card>
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 text-sm font-medium text-[#111827]">近30日生成内容量</div>
                <v-charts style="height: 260px" :option="contentChart" :autoresize="true" />
            </el-card>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- 定时任务健康 -->
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-[#111827]">GEO 定时任务健康</span>
                    <span class="text-xs text-[#9ca3af]">
                        诊断批次:进行中 {{ data.running_batch ?? 0 }} / 排队 {{ data.pending_batch ?? 0 }}
                    </span>
                </div>
                <el-table size="small" :data="data.cron || []">
                    <el-table-column label="任务" prop="name" min-width="140" />
                    <el-table-column label="周期" prop="expression" min-width="100" />
                    <el-table-column label="最近执行" min-width="150">
                        <template #default="{ row }">
                            {{ row.last_time_text || "从未执行" }}
                        </template>
                    </el-table-column>
                    <el-table-column label="状态" width="90">
                        <template #default="{ row }">
                            <el-tag v-if="row.status != 1" type="info" size="small">已停用</el-tag>
                            <el-tag v-else-if="row.stale" type="danger" size="small">超期未跑</el-tag>
                            <el-tag v-else type="success" size="small">正常</el-tag>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>

            <!-- 场景算力消耗 -->
            <el-card class="!border-none" shadow="never">
                <div class="mb-2 text-sm font-medium text-[#111827]">近30日 GEO 场景算力消耗</div>
                <el-table size="small" :data="data.consume || []">
                    <el-table-column label="场景" prop="label" min-width="140" />
                    <el-table-column label="账变编号" prop="change_type" width="100" />
                    <el-table-column label="次数" prop="count" width="90" />
                    <el-table-column label="算力合计" prop="total" min-width="110" />
                </el-table>
                <div v-if="!(data.consume || []).length" class="py-4 text-center text-xs text-[#9ca3af]">
                    近30日无 GEO 场景扣费流水(已切换按模型计费后,模型用量在「AI模型-模型计费」口径下)
                </div>
            </el-card>
        </div>
    </div>
</template>

<script lang="ts" setup name="geoOverview">
import vCharts from "vue-echarts";
import { getGeoOverview } from "@/api/marketing/geo";

const loading = ref(true);
const data = ref<any>({});

const statCards = computed(() => {
    const s = data.value.stats || {};
    return [
        { label: "GEO 项目", value: s.project_total ?? 0, sub: `近7日活跃 ${s.project_active_7d ?? 0}` },
        { label: "累计监测", value: s.monitor_total ?? 0, sub: "问题 × 引擎 次数" },
        { label: "生成内容", value: s.content_total ?? 0, sub: `已采纳 ${s.content_adopted ?? 0}` },
        {
            label: "发布记录",
            value: s.publish_total ?? 0,
            sub: `成功 ${s.publish_published ?? 0} / 失败 ${s.publish_failed ?? 0}`,
        },
    ];
});

const lineOption = (trend: any[], color: string) => ({
    grid: { left: 40, right: 16, top: 20, bottom: 24 },
    tooltip: { trigger: "axis" },
    xAxis: { type: "category", data: (trend || []).map((t: any) => t.date), boundaryGap: false },
    yAxis: { type: "value", minInterval: 1 },
    series: [
        {
            type: "line",
            data: (trend || []).map((t: any) => Number(t.count)),
            smooth: true,
            showSymbol: false,
            lineStyle: { color },
            itemStyle: { color },
            areaStyle: { opacity: 0.08, color },
        },
    ],
});

const monitorChart = computed(() => lineOption(data.value.trend?.monitor || [], "#3b82f6"));
const contentChart = computed(() => lineOption(data.value.trend?.content || [], "#10b981"));

const load = async () => {
    loading.value = true;
    try {
        data.value = await getGeoOverview();
    } finally {
        loading.value = false;
    }
};

load();
</script>
