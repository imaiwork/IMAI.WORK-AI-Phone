<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.keyword"
                        placeholder="请输入用户信息"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="创建时间">
                    <daterange-picker
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
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['ai_application.device.person/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="IP人设名称" prop="persona_name" min-width="180" show-overflow-tooltip />
                <el-table-column label="IP类型" prop="material_num" width="100">
                    <template #default="{ row }">
                        <el-tag
                            :type="row.persona_type == 1 ? 'primary' : row.persona_type == 2 ? 'success' : 'warning'"
                            >{{ getPersonaType(row.persona_type) }}</el-tag
                        >
                    </template>
                </el-table-column>
                <el-table-column label="素材数量" prop="material_num" width="100" />
                <el-table-column label="绑定设备数" prop="device_num" width="120" />
                <el-table-column label="运营报告" width="120">
                    <template #default="{ row }">
                        <div>
                            <el-button v-if="row.report_status == 2" type="primary" link @click="handleViewReport(row)"
                                >点击查看</el-button
                            >
                            <el-button v-else type="info" link>未生成</el-button>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['ai_application.device.person/detail']" type="primary" link>
                            <router-link
                                :to="{
                                    path: getRoutePath('ai_application.device.person/detail'),
                                    query: {
                                        id: row.id,
                                        name: row.name,
                                    },
                                }">
                                详情
                            </router-link>
                        </el-button>
                        <el-button
                            v-perms="['ai_application.device.person/delete']"
                            type="danger"
                            link
                            @click="handleDelete([row.id])">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <report-pop v-if="showReportPop" ref="reportPopRef" @close="showReportPop = false" />
</template>
<script lang="ts" setup>
import { getPersonList, deletePerson } from "@/api/ai_application/device/person";
import { getRoutePath } from "@/router";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import ReportPop from "./components/report-pop.vue";
const queryParams = reactive({
    start_time: "",
    end_time: "",
    keyword: "",
});
const showReportPop = ref(false);
const reportPopRef = ref<InstanceType<typeof ReportPop>>();
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPersonList,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deletePerson({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const getPersonaType = (type: number) => {
    return {
        1: "个人IP",
        2: "企业服务",
        3: "本地商家",
    }[type];
};

const handleViewReport = async (row: any) => {
    showReportPop.value = true;
    await nextTick();
    reportPopRef.value?.open(row.id);
};

getLists();
</script>
