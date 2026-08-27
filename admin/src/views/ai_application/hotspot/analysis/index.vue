<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="话题">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.keyword"
                        placeholder="话题关键词"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="用户">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.user"
                        placeholder="昵称/手机号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="分析时间">
                    <daterange-picker
                        class="w-[280px]"
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="编号" prop="record_no" width="140" show-overflow-tooltip />
                <el-table-column label="用户" prop="user" min-width="110" show-overflow-tooltip />
                <el-table-column label="话题" prop="topic" min-width="200" show-overflow-tooltip />
                <el-table-column label="人设" min-width="130">
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <el-avatar v-if="row.persona?.avatar" :size="24" :src="row.persona.avatar" />
                            <span>{{ row.persona?.name || "-" }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="契合度" width="100">
                    <template #default="{ row }">
                        <el-tag :type="fitTagType(row.fit_score)" size="small">{{ row.fit_score }} 分</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="推荐目标" width="110">
                    <template #default="{ row }">{{ GOAL_MAP[row.recommended_goal] || row.recommended_goal || "-" }}</template>
                </el-table-column>
                <el-table-column label="时间" prop="create_time" min-width="150" show-overflow-tooltip />
                <el-table-column label="操作" width="130" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['hotspot.analysis/detail']" type="primary" link @click="openDetail(row)">
                            详情
                        </el-button>
                        <el-button v-perms="['hotspot.analysis/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-drawer v-model="detailVisible" title="分析详情" size="480px">
            <div v-loading="detailLoading" class="px-1">
                <template v-if="detail">
                    <div class="text-base font-semibold text-[#111827]">{{ detail.topic }}</div>
                    <div class="mt-1 text-xs text-[#9ca3af]">
                        {{ detail.record_no }} · {{ detail.user }} · {{ detail.create_time }}
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <el-avatar v-if="detail.persona?.avatar" :size="32" :src="detail.persona.avatar" />
                        <div>
                            <div class="text-sm font-medium">{{ detail.persona?.name || "-" }}</div>
                            <div class="text-xs text-[#9ca3af]">{{ detail.persona?.tag || "" }}</div>
                        </div>
                        <el-tag class="ml-auto" :type="fitTagType(detail.fit_score)">契合 {{ detail.fit_score }}</el-tag>
                    </div>
                    <div v-if="detail.fit_reason" class="mt-3 rounded bg-[#f9fafb] p-3 text-sm leading-relaxed text-[#374151]">
                        {{ detail.fit_reason }}
                    </div>
                    <template v-if="(detail.hooks || []).length">
                        <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">切入方式</div>
                        <div v-for="(hook, index) in detail.hooks" :key="index" class="mb-2 rounded bg-[#f9fafb] p-3">
                            <div class="text-sm font-medium text-[#2563eb]">{{ hook.label }}</div>
                            <div class="mt-1 text-xs leading-relaxed text-[#6b7280]">{{ hook.detail }}</div>
                        </div>
                    </template>
                    <template v-if="(detail.risks || []).length">
                        <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">风险提醒</div>
                        <div class="rounded border border-[#fde68a] bg-[#fffbeb] p-3">
                            <div v-for="(risk, index) in detail.risks" :key="index" class="text-xs leading-relaxed text-[#d97706]">
                                {{ risk }}
                            </div>
                        </div>
                    </template>
                    <div class="mt-4 text-xs text-[#9ca3af]">
                        推荐目标:{{ GOAL_MAP[detail.recommended_goal] || detail.recommended_goal || "-" }} ·
                        推荐方向:{{ detail.recommended_direction || "-" }}
                    </div>
                </template>
            </div>
        </el-drawer>
    </div>
</template>

<script lang="ts" setup name="hotspotAnalysis">
import { usePaging } from "@/hooks/usePaging";
import {
    getHotspotAnalysisList,
    getHotspotAnalysisDetail,
    deleteHotspotAnalysis,
} from "@/api/ai_application/hotspot";
import feedback from "@/utils/feedback";

const GOAL_MAP: Record<string, string> = {
    sell: "卖产品",
    leads: "私域获客",
    traffic: "涨粉引流",
    brand: "品牌种草",
    engage: "点击播放",
};

const queryParams = reactive({
    keyword: "",
    user: "",
    start_time: "",
    end_time: "",
});

const detailVisible = ref(false);
const detailLoading = ref(false);
const detail = ref<any>(null);

const fitTagType = (score: number) => (score >= 70 ? "success" : score >= 40 ? "warning" : "danger");

const openDetail = async (row: any) => {
    detailVisible.value = true;
    detailLoading.value = true;
    detail.value = null;
    try {
        detail.value = await getHotspotAnalysisDetail({ id: row.id });
    } finally {
        detailLoading.value = false;
    }
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定删除该分析记录?");
    await deleteHotspotAnalysis({ id: row.id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getHotspotAnalysisList,
    params: queryParams,
});

getLists();
</script>
