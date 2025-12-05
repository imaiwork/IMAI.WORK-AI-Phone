<template>
    <div class="h-full flex flex-col">
        <div class="bg-white rounded-lg px-4 h-[107px] grid grid-cols-3 gap-4 flex-shrink-0">
            <div class="flex flex-col justify-center items-center">
                <span class="text-[#74798C]">当前岗位数</span>
                <span class="text-[24px] font-bold">{{ statistics.job_count }}</span>
            </div>
            <div class="flex flex-col justify-center items-center">
                <div class="text-[#74798C]">面试平局时长</div>
                <div>
                    <span class="text-[24px] font-bold">{{ statistics.avg_time || 0 }}</span>
                    <span class="text-[#74798C]">分钟</span>
                </div>
            </div>
            <div class="flex flex-col justify-center items-center">
                <span class="text-[#74798C]">总面试数量</span>
                <span class="text-[24px] font-bold">{{ statistics.interview_count || 0 }}</span>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col mt-6 bg-white rounded-lg overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-4 py-6">
                <div class="text-[18px] font-bold">面试列表</div>
                <div class="flex items-center justify-end gap-2 grow">
                    <ElRadioGroup v-model="queryParams.status" @change="getLists()">
                        <ElRadioButton label="全部" value=""></ElRadioButton>
                        <ElRadioButton label="分析中" value="5"></ElRadioButton>
                        <ElRadioButton label="已完成" value="1"></ElRadioButton>
                        <ElRadioButton label="分析失败" value="7"></ElRadioButton>
                    </ElRadioGroup>
                    <ElInput
                        v-model="queryParams.interview_name"
                        class="h-[32px] !w-[240px]"
                        placeholder="请输入面试者名称">
                        <template #append>
                            <ElButton @click="getLists()">
                                <Icon name="el-icon-Search" :size="16"></Icon>
                            </ElButton>
                        </template>
                    </ElInput>
                    <ElButton :icon="Refresh" @click="resetParams()" />
                </div>
            </div>
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :row-style="{ height: '60px' }"
                    :header-cell-style="{ height: '50px' }"
                    v-loading="pager.loading">
                    <ElTableColumn
                        prop="interview_name"
                        label="面试者"
                        min-width="140"
                        show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn
                        prop="job_name"
                        label="面试岗位"
                        min-width="160"
                        show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn label="学历" prop="degree" min-width="120" show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn label="工作年限" prop="work_years" width="80" show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn label="AI评分" width="120" prop="best_score" show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn label="面试时长" width="120" prop="duration" show-overflow-tooltip> </ElTableColumn>
                    <ElTableColumn
                        label="面试状态"
                        prop="status_text"
                        width="120"
                        show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn
                        label="面试时间"
                        prop="first_start_time_text"
                        width="200"
                        show-overflow-tooltip></ElTableColumn>
                    <ElTableColumn label="操作" width="120" fixed="right">
                        <template #default="{ row }">
                            <ElPopover
                                :show-arrow="false"
                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl">
                                <template #reference>
                                    <ElButton link>
                                        <Icon name="el-icon-MoreFilled"></Icon>
                                    </ElButton>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleDetail(row.id)">
                                        <ElButton link icon="el-icon-View" class="w-full !justify-start">详情</ElButton>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleDelete(row.id)">
                                        <ElButton link icon="el-icon-Delete" class="w-full !justify-start"
                                            >删除</ElButton
                                        >
                                    </div>
                                    <div
                                        v-if="row.status == 7"
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer"
                                        @click="handleReanalyze(row.id)">
                                        <ElButton link icon="el-icon-Refresh" class="w-full !justify-start"
                                            >重新分析</ElButton
                                        >
                                    </div>
                                </div>
                            </ElPopover>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-end p-4">
                <pagination v-model="pager" @change="getLists"></pagination>
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
import { Refresh } from "@element-plus/icons-vue";
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

<style scoped lang="scss">
:deep(.el-radio-group) {
    .el-radio-button__inner {
        padding: 8px 30px;
    }
}
:deep(.el-input-group__append) {
    background-color: transparent;
    border: none;
}
</style>
