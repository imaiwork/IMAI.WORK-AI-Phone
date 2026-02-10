<template>
    <div class="h-full flex flex-col min-w-[1000px]">
        <div
            class="bg-[#FFFFFF] rounded-[20px] px-8 h-[120px] flex items-center justify-between border border-br flex-shrink-0">
            <div v-for="(stat, idx) in statConfigs" :key="idx" class="flex-1 flex flex-col items-center relative">
                <div v-if="idx !== 0" class="absolute left-0 top-1/2 -translate-y-1/2 w-[1px] h-10 bg-[#F1F5F9]"></div>

                <span class="text-[13px] font-medium text-[#94A3B8] mb-1 uppercase tracking-wider">{{
                    stat.label
                }}</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-[32px] font-[900] text-[#0F172A] tracking-tight">
                        {{ statistics[stat.key] || 0 }}
                    </span>
                    <span v-if="stat.unit" class="text-xs font-medium text-[#94A3B8]">{{ stat.unit }}</span>
                </div>
            </div>
        </div>

        <div class="grow min-h-0 flex flex-col mt-3 bg-[#FFFFFF] rounded-[24px] border border-br overflow-hidden">
            <div class="flex items-center justify-between gap-4 px-6 py-6 border-b border-[#F8FAFC]">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-5 rounded-full bg-primary"></div>
                    <span class="text-lg font-[900] text-[#0F172A]">面试记录明细</span>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <ElSelect
                        v-model="queryParams.status"
                        placeholder="面试状态"
                        class="custom-select !w-[150px]"
                        :show-arrow="false"
                        @change="getLists()">
                        <ElOption label="全部状态" value="" />
                        <ElOption label="分析中" value="5" />
                        <ElOption label="已完成" value="1" />
                        <ElOption label="分析失败" value="7" />
                    </ElSelect>

                    <div class="search-input-wrapper">
                        <ElInput
                            v-model="queryParams.interview_name"
                            class="custom-input !w-[200px]"
                            placeholder="搜索面试者名称..."
                            clearable
                            @clear="getLists()"
                            @keyup.enter="getLists()">
                            <template #prefix>
                                <Icon name="el-icon-Search" :size="16" color="#94A3B8" />
                            </template>
                        </ElInput>
                    </div>
                </div>
            </div>

            <div class="grow min-h-0 px-2">
                <ElTable
                    :data="pager.lists"
                    class="custom-table"
                    height="100%"
                    v-loading="pager.loading"
                    :row-style="{ height: '72px' }">
                    <ElTableColumn label="面试者" min-width="160">
                        <template #default="{ row }">
                            <div class="flex flex-col">
                                <span class="font-black text-[#0F172A]">{{ row.interview_name }}</span>
                                <span class="text-[11px] text-[#94A3B8] font-medium"
                                    >{{ row.degree || "未知学历" }} · {{ row.work_years || 0 }}年经验</span
                                >
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn prop="job_name" label="面试岗位" min-width="160">
                        <template #default="{ row }">
                            <span class="px-3 py-1 rounded-lg bg-[#F1F5F9] text-[#475569] text-xs font-medium">{{
                                row.job_name
                            }}</span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="AI 评分" width="120">
                        <template #default="{ row }">
                            <div class="flex justify-center items-baseline gap-0.5">
                                <span class="text-lg font-[900] text-primary">{{ row.best_score || 0 }}</span>
                                <span class="text-[10px] text-[#94A3B8] font-medium">/100</span>
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="状态" width="120">
                        <template #default="{ row }">
                            <div
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black"
                                :class="statusStyle(row.status)">
                                <div class="w-1 h-1 rounded-full" :class="statusDot(row.status)"></div>
                                {{ row.status_text }}
                            </div>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="面试时间" width="180">
                        <template #default="{ row }">
                            <span class="text-xs font-medium text-[#64748B]">{{ row.first_start_time_text }}</span>
                        </template>
                    </ElTableColumn>

                    <ElTableColumn label="操作" width="80" fixed="right" align="right">
                        <template #default="{ row }">
                            <div class="flex justify-end">
                                <ElPopover
                                    :show-arrow="false"
                                    popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                                    <template #reference>
                                        <div
                                            class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-slate-50 transition-colors cursor-pointer">
                                            <Icon name="el-icon-MoreFilled" color="#94A3B8" :size="14" />
                                        </div>
                                    </template>
                                    <div class="p-1 space-y-1">
                                        <div class="table-action-item" @click="handleDetail(row.id)">
                                            <Icon name="el-icon-View" :size="14" />
                                            <span>报告详情</span>
                                        </div>
                                        <div
                                            v-if="row.status == 7"
                                            class="mtable-action-item text-primary"
                                            @click="handleReanalyze(row.id)">
                                            <Icon name="el-icon-Refresh" :size="14" />
                                            <span>重新分析</span>
                                        </div>
                                        <div class="h-[1px] bg-[#F1F5F9] my-1"></div>
                                        <div
                                            class="table-action-item !text-red-500 hover:!bg-red-50"
                                            @click="handleDelete(row.id)">
                                            <Icon name="el-icon-Delete" :size="14" />
                                            <span>删除此记录</span>
                                        </div>
                                    </div>
                                </ElPopover>
                            </div>
                        </template>
                    </ElTableColumn>

                    <template #empty>
                        <ElEmpty description="未找到相关面试记录" />
                    </template>
                </ElTable>
            </div>

            <div class="flex justify-between items-center px-6 py-4 border-t border-[#F8FAFC]">
                <span class="text-xs font-medium text-[#94A3B8]">共 {{ pager.count }} 条记录</span>
                <pagination v-model="pager" @change="getLists" />
            </div>
        </div>
        <detail ref="detailRef" v-if="showDetail" @close="showDetail = false" />
    </div>
</template>

<script setup lang="ts">
import {
    getInterviewRecord,
    getInterviewStatistics,
    deleteInterviewRecord,
    reanalyzeInterviewRecord,
} from "@/api/interview";
import Detail from "./detail.vue";

const route = useRoute();
const nuxtApp = useNuxtApp();

const detailRef = ref<InstanceType<typeof Detail>>();
const showDetail = ref(false);

const queryParams = reactive({
    job_id: "",
    interview_name: "",
    status: "",
});

const statistics = ref({
    job_count: 0,
    avg_time: 0,
    interview_count: 0,
});

const getStatData = async () => {
    const data = await getInterviewStatistics();
    statistics.value = data;
};

const { pager, getLists, resetParams } = usePaging({
    fetchFun: getInterviewRecord,
    params: queryParams,
});

const statConfigs = [
    { label: "当前在线岗位", key: "job_count", unit: "个" },
    { label: "面试平均时长", key: "avg_time", unit: "分钟" },
    { label: "累计面试人次", key: "interview_count", unit: "人" },
];

const statusStyle = (status: number) => {
    if (status == 1) return "bg-[#F0FDF4] text-[#16A34A]";
    if (status == 5) return "bg-[#EFF6FF] text-primary";
    if (status == 7) return "bg-[#FEF2F2] text-[#EF4444]";
    return "bg-slate-50 text-[#94A3B8]";
};

const statusDot = (status: number) => {
    if (status == 1) return "bg-[#16A34A]";
    if (status == 5) return "bg-primary";
    if (status == 7) return "bg-[#EF4444]";
    return "bg-[#94A3B8]";
};

const handleDetail = async (id: number) => {
    showDetail.value = true;
    await nextTick();
    detailRef.value?.open();
    detailRef.value?.getDetail(id);
};

const handleDelete = async (id: number) => {
    nuxtApp.$confirm({
        message: "确定要删除该面试记录吗？",
        onConfirm: async () => {
            try {
                await deleteInterviewRecord({ ids: [id] });
                feedback.msgSuccess("删除成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "删除失败");
            }
        },
    });
};

const handleReanalyze = async (id: number) => {
    nuxtApp.$confirm({
        message: "确定要重新分析该面试记录吗？",
        onConfirm: async () => {
            try {
                await reanalyzeInterviewRecord({ id });
                feedback.msgSuccess("重新分析成功");
                getLists();
            } catch (error) {
                feedback.msgError(error || "重新分析失败");
            }
        },
    });
};
onMounted(() => {
    queryParams.job_id = route.query.id as string;
    getLists();
    getStatData();
});
</script>
