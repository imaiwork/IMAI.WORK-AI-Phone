<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="标题">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.title"
                        placeholder="请输入标题关键词"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="品牌">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.brand_name"
                        placeholder="项目品牌名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[130px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="待发布" value="pending" />
                        <el-option label="已发布" value="published" />
                        <el-option label="失败" value="failed" />
                    </el-select>
                </el-form-item>
                <el-form-item label="发布方式">
                    <el-select
                        class="!w-[150px]"
                        v-model="queryParams.mode"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option v-for="(label, value) in MODE_MAP" :key="value" :label="label" :value="value" />
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
                <el-table-column label="标题" prop="title" min-width="180" show-overflow-tooltip />
                <el-table-column label="品牌" prop="brand_name" min-width="110" show-overflow-tooltip />
                <el-table-column label="媒体" prop="media_name" min-width="110" show-overflow-tooltip />
                <el-table-column label="发布方式" width="110">
                    <template #default="{ row }">{{ MODE_MAP[row.mode] || row.mode || "-" }}</template>
                </el-table-column>
                <el-table-column label="账号" min-width="110" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.account || row.site_name || "-" }}</template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.status === 'published'" type="success" size="small">已发布</el-tag>
                        <el-tag v-else-if="row.status === 'failed'" type="danger" size="small">失败</el-tag>
                        <el-tag v-else type="warning" size="small">待发布</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="失败原因" min-width="150">
                    <template #default="{ row }">
                        <el-tooltip
                            v-if="row.error_msg"
                            :content="row.error_msg"
                            placement="top"
                            :show-after="200"
                            popper-class="max-w-[420px]">
                            <span class="line-clamp-1 cursor-default text-[#f56c6c]">{{ row.error_msg }}</span>
                        </el-tooltip>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="链接" width="80">
                    <template #default="{ row }">
                        <el-link
                            v-if="row.published_url"
                            type="primary"
                            :href="row.published_url"
                            target="_blank"
                            :underline="false">
                            查看
                        </el-link>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="互动" width="130">
                    <template #default="{ row }">
                        <span class="text-xs text-[#6b7280]">
                            {{ row.stat_views ?? 0 }}阅 / {{ row.stat_likes ?? 0 }}赞 / {{ row.stat_comments ?? 0 }}评
                        </span>
                    </template>
                </el-table-column>
                <el-table-column label="发布时间" prop="publish_time" min-width="150">
                    <template #default="{ row }">{{ row.publish_time || "-" }}</template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="150" show-overflow-tooltip />
                <el-table-column label="操作" width="80" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['geo.publish/delete']" type="danger" link @click="handleDelete(row)">
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
</template>

<script lang="ts" setup name="geoPublish">
import { usePaging } from "@/hooks/usePaging";
import { getGeoPublishList, deleteGeoPublish } from "@/api/marketing/geo";
import feedback from "@/utils/feedback";

const MODE_MAP: Record<string, string> = {
    auth: "授权直发",
    phone: "AI手机",
    register: "AI手机",
    api: "官网/站点",
    manual: "手动",
};

const queryParams = reactive({
    title: "",
    brand_name: "",
    status: "",
    mode: "",
    start_time: "",
    end_time: "",
});

const handleDelete = async (row: any) => {
    await feedback.confirm("确定删除该发布记录?仅删除后台台账,不会撤回已发布内容。");
    await deleteGeoPublish({ id: row.id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getGeoPublishList,
    params: queryParams,
});

getLists();
</script>
