<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :inline="true">
                <el-form-item label="榜单">
                    <el-radio-group v-model="period" @change="loadHot">
                        <el-radio-button value="day">日榜</el-radio-button>
                        <el-radio-button value="week">周榜</el-radio-button>
                        <!-- 飙升榜暂不开放（与小程序端 HOTSPOT_PERIOD_OPTIONS 同步，接口逻辑保留） -->
                        <!-- <el-radio-button value="rise">飙升榜</el-radio-button> -->
                    </el-radio-group>
                </el-form-item>
                <el-form-item v-if="period !== 'rise'" label="历史日期">
                    <el-select
                        class="!w-[160px]"
                        v-model="day"
                        placeholder="最新"
                        clearable
                        :empty-values="[null, undefined]"
                        @change="loadHot">
                        <el-option label="最新" value="" />
                        <el-option v-for="date in histDates" :key="date" :label="date" :value="date" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadHot">刷新</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="mb-2 text-xs text-[#9ca3af]">{{ meta }}</div>
            <el-table size="large" v-loading="loading" :data="topics">
                <el-table-column label="排名" prop="rank" width="80" />
                <el-table-column label="话题" prop="title" min-width="240" show-overflow-tooltip />
                <el-table-column label="分类" width="110">
                    <template #default="{ row }">{{ row.category || "-" }}</template>
                </el-table-column>
                <el-table-column label="热度" width="120">
                    <template #default="{ row }">{{ row.heat_text || "-" }}</template>
                </el-table-column>
                <el-table-column label="标签" min-width="160">
                    <template #default="{ row }">
                        <el-tag v-if="row.rank_diff" type="danger" size="small">↑ {{ row.rank_diff }}</el-tag>
                        <template v-else-if="row.days_on_board">
                            <el-tag type="warning" size="small">上榜 {{ row.days_on_board }} 天</el-tag>
                            <el-tag v-if="row.best_rank" class="ml-1" type="info" size="small">
                                最高 {{ row.best_rank }} 名
                            </el-tag>
                        </template>
                        <el-tag v-else-if="row.trend === 'new'" type="success" size="small">新</el-tag>
                        <el-tag v-else-if="row.rank <= 3" type="danger" size="small">热</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="已分析" width="90">
                    <template #default="{ row }">
                        <el-tag v-if="row.analyzed" type="success" size="small">是</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
            </el-table>
            <div v-if="!loading && !topics.length" class="py-6 text-center text-xs text-[#9ca3af]">暂无热榜数据</div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="hotspotHot">
import { getHotspotHotList, getHotspotHistoryDates } from "@/api/ai_application/hotspot";

// 目前产品仅开放抖音（与 HotListService::PLATFORMS 同步）
const PLATFORM = "douyin";

const period = ref("day");
const day = ref("");
const histDates = ref<string[]>([]);
const topics = ref<any[]>([]);
const meta = ref("-");
const loading = ref(false);

const loadHistDates = async () => {
    try {
        const res: any = await getHotspotHistoryDates({ platform: PLATFORM });
        histDates.value = res?.dates || [];
    } catch {
        histDates.value = [];
    }
};

const loadHot = async () => {
    loading.value = true;
    if (period.value === "rise") day.value = "";
    try {
        // 与小程序端展开上限(HOT_FULL=30)保持一致，两端看到的榜单数量相同
        const params: Record<string, any> = { platform: PLATFORM, period: period.value, limit: 30 };
        if (day.value) params.day = day.value;
        const res: any = await getHotspotHotList(params);
        topics.value = res?.topics || [];
        if (period.value === "week") {
            const covered = res?.covered_dates || [];
            meta.value = covered.length ? `近 7 天聚合 · 实际覆盖 ${covered.length} 天` : "还没有足够的快照做周榜";
        } else if (period.value === "rise") {
            meta.value = "实时飙升 · 按排名上升幅度排序" + (res?.cached ? " · 缓存" : "");
        } else {
            meta.value = (res?.date || "") + (res?.cached ? " · 缓存" : "") + (res?.live ? "" : " · 历史快照");
        }
    } catch (error: any) {
        topics.value = [];
        meta.value = String(error?.message || error || "热榜加载失败");
    } finally {
        loading.value = false;
    }
};

loadHistDates();
loadHot();
</script>
