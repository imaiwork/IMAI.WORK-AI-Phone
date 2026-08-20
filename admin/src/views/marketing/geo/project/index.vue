<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="品牌名称">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.brand_name"
                        placeholder="请输入品牌名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="归属用户">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.user_keyword"
                        placeholder="用户编号/昵称/手机号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="自动监测">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.auto_monitor"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="已开启" value="1" />
                        <el-option label="已关闭" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创建时间">
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
                <el-table-column label="品牌" prop="brand_name" min-width="140" show-overflow-tooltip />
                <el-table-column label="行业" prop="industry" min-width="110" show-overflow-tooltip />
                <el-table-column label="归属用户" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div>{{ row.nickname || "-" }}</div>
                        <div class="text-xs text-[#9ca3af]">{{ row.user_sn }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="话题" prop="topic_count" width="70" />
                <el-table-column label="问题" prop="question_count" width="70" />
                <el-table-column label="内容" prop="content_count" width="70" />
                <el-table-column label="监测量" prop="monitor_count" width="90" />
                <el-table-column label="生成模型" prop="gen_model" min-width="130" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.gen_model || "平台默认" }}</template>
                </el-table-column>
                <el-table-column label="自动监测" width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['geo.project/setAutoMonitor']"
                            @change="changeAutoMonitor(row)"
                            v-model="row.auto_monitor"
                            :active-value="1"
                            :inactive-value="0" />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" show-overflow-tooltip min-width="160" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['geo.project/detail']" type="primary" link @click="handleDetail(row)">
                            详情
                        </el-button>
                        <el-button v-perms="['geo.project/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 详情抽屉(只读) -->
        <el-drawer v-model="showDetail" title="项目详情" size="520px">
            <div v-loading="detailLoading" class="px-1">
                <template v-if="detail.id">
                    <el-descriptions :column="1" border size="small">
                        <el-descriptions-item label="品牌名称">{{ detail.brand_name }}</el-descriptions-item>
                        <el-descriptions-item label="官网">{{ detail.website || "-" }}</el-descriptions-item>
                        <el-descriptions-item label="行业">{{ detail.industry || "-" }}</el-descriptions-item>
                        <el-descriptions-item label="品牌简介">{{ detail.intro || "-" }}</el-descriptions-item>
                        <el-descriptions-item label="核心能力">{{ detail.features || "-" }}</el-descriptions-item>
                        <el-descriptions-item label="目标客户">{{ detail.target_customer || "-" }}</el-descriptions-item>
                        <el-descriptions-item label="品牌别名">{{ joinList(detail.aliases) }}</el-descriptions-item>
                        <el-descriptions-item label="竞品">{{ joinList(detail.competitors) }}</el-descriptions-item>
                        <el-descriptions-item label="生成模型">{{ detail.gen_model || "平台默认" }}</el-descriptions-item>
                        <el-descriptions-item label="自动监测">
                            {{ detail.auto_monitor == 1 ? "已开启" : "已关闭" }}
                            <span v-if="detail.last_auto_date" class="ml-2 text-xs text-[#9ca3af]">
                                最近自动监测:{{ detail.last_auto_date }}
                            </span>
                        </el-descriptions-item>
                    </el-descriptions>
                    <div class="mt-4 mb-2 text-sm font-medium text-[#111827]">资产统计</div>
                    <el-descriptions :column="2" border size="small">
                        <el-descriptions-item label="话题">{{ detail.stat?.topic_count ?? 0 }}</el-descriptions-item>
                        <el-descriptions-item label="场景问题">{{ detail.stat?.question_count ?? 0 }}</el-descriptions-item>
                        <el-descriptions-item label="生成内容">{{ detail.stat?.content_count ?? 0 }}</el-descriptions-item>
                        <el-descriptions-item label="监测量">{{ detail.stat?.monitor_count ?? 0 }}</el-descriptions-item>
                        <el-descriptions-item label="发布记录">{{ detail.stat?.publish_count ?? 0 }}</el-descriptions-item>
                        <el-descriptions-item label="进行中批次">{{ detail.stat?.running_batch ?? 0 }}</el-descriptions-item>
                    </el-descriptions>
                </template>
            </div>
        </el-drawer>
    </div>
</template>

<script lang="ts" setup name="geoProject">
import { usePaging } from "@/hooks/usePaging";
import {
    getGeoProjectList,
    getGeoProjectDetail,
    setGeoProjectAutoMonitor,
    deleteGeoProject,
} from "@/api/marketing/geo";
import feedback from "@/utils/feedback";

const queryParams = reactive({
    brand_name: "",
    user_keyword: "",
    auto_monitor: "",
    start_time: "",
    end_time: "",
});

const showDetail = ref(false);
const detailLoading = ref(false);
const detail = ref<any>({});

const joinList = (val: any) => {
    if (Array.isArray(val)) return val.length ? val.join("、") : "-";
    if (typeof val === "string" && val) {
        try {
            const arr = JSON.parse(val);
            return Array.isArray(arr) && arr.length ? arr.join("、") : val;
        } catch {
            return val;
        }
    }
    return "-";
};

const handleDetail = async (row: any) => {
    showDetail.value = true;
    detailLoading.value = true;
    detail.value = {};
    try {
        detail.value = await getGeoProjectDetail({ id: row.id });
    } finally {
        detailLoading.value = false;
    }
};

const changeAutoMonitor = async (row: any) => {
    try {
        await setGeoProjectAutoMonitor({ id: row.id, auto_monitor: row.auto_monitor });
    } finally {
        getLists();
    }
};

const handleDelete = async (row: any) => {
    await feedback.confirm(
        `确定删除项目「${row.brand_name}」?将同时终止进行中的诊断批次、停用官网定时发布,并退回未发布投递的扣费。`
    );
    await deleteGeoProject({ id: row.id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getGeoProjectList,
    params: queryParams,
});

getLists();
</script>
