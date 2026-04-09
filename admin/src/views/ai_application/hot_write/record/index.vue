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
                <el-form-item label="状态">
                    <el-select
                        class="!w-[120px]"
                        v-model="queryParams.status"
                        placeholder="请选择状态"
                        clearable
                        :empty-values="[null, undefined]"
                        @change="getLists()">
                        <el-option label="全部" value="" />
                        <el-option label="成功" value="3" />
                        <el-option label="失败" value="4" />
                        <el-option label="生成中" value="0,1,2" />
                    </el-select>
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
            <div class="mb-4">
                <el-button
                    v-perms="['ai_application.hot_write.record/delete']"
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
                <el-table-column prop="nickname" label="用户" min-width="140" show-overflow-tooltip></el-table-column>
                <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip></el-table-column>
                <el-table-column label="复制链接" prop="prompt" min-width="220" show-overflow-tooltip></el-table-column>
                <el-table-column label="消耗算力" prop="total_tokens_cost" width="120">
                    <template #default="{ row }"> {{ row.total_tokens_cost }}算力 </template>
                </el-table-column>
                <el-table-column label="生成状态" prop="status_text" width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        <el-tag :type="getStatusType(row.status)">
                            {{ getStatusText(row.status) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                    label="发布时间"
                    prop="create_time"
                    width="200"
                    show-overflow-tooltip></el-table-column>

                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link :disabled="!row.video_url" @click="handlePreviewVideo(row)">
                            查看
                        </el-button>
                        <el-button type="primary" link @click="handleDetail(row)">详情</el-button>
                        <el-button
                            v-perms="['ai_application.hot_write.record/delete']"
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
        <el-dialog v-if="showPreviewVideo" v-model="showPreviewVideo" width="740px" title="视频预览">
            <video-player ref="playerRef" :src="videoUrl" width="100%" height="450px" />
        </el-dialog>
        <detail-pop v-if="showDetailPop" ref="detailPopRef" @close="showDetailPop = false" />
    </div>
</template>
<script lang="ts" setup>
import { getHotWriteRecord, deleteHotWriteRecord } from "@/api/ai_application/hot_write/record";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import DetailPop from "./detail.vue";
import { isArray } from "lodash-es";
const queryParams = reactive({
    keyword: "",
    status: "",
    job_name: "",
    start_time: "",
    end_time: "",
});

const tableRef = ref<InstanceType<typeof ElTable>>();
const showPreviewVideo = ref(false);
const videoUrl = ref("");
const showDetailPop = ref(false);
const detailPopRef = ref<InstanceType<typeof DetailPop>>();
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getHotWriteRecord,
    params: queryParams,
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteHotWriteRecord({ ids: isArray(id) ? id : [id] });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const getStatusType = (status: number) => {
    if (status == 3) return "success";
    if (status == 4) return "error";
    return "primary";
};

const getStatusText = (status: number) => {
    if (status == 3) return "成功";
    if (status == 4) return "失败";
    return "生成中";
};

const handlePreviewVideo = (row: any) => {
    videoUrl.value = row.video_url;
    showPreviewVideo.value = true;
};

const handleDetail = async (row: any) => {
    showDetailPop.value = true;
    await nextTick();
    detailPopRef.value?.open(row);
};

getLists();
</script>
