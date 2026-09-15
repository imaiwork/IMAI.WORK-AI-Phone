<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="任务号">
                    <el-input
                        class="w-[240px]"
                        v-model="queryParams.sn"
                        placeholder="请输入任务号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="玩法">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.play"
                        placeholder="请选择玩法"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="影视换脸改词" value="face" />
                        <el-option label="跳舞换脸" value="dance" />
                        <el-option label="影视引流拼接" value="clip" />
                    </el-select>
                </el-form-item>
                <el-form-item label="任务状态">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.status"
                        placeholder="请选择任务状态"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="待提交" value="0" />
                        <el-option label="生成中" value="1" />
                        <el-option label="成功" value="2" />
                        <el-option label="失败" value="3" />
                        <el-option label="超时" value="4" />
                    </el-select>
                </el-form-item>
                <el-form-item label="创作时间">
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
            <el-table size="large" v-loading="pager.loading" :data="pager.lists" row-key="id">
                <el-table-column label="ID" prop="id" min-width="70" />
                <el-table-column label="任务号" prop="sn" width="200" show-overflow-tooltip />
                <el-table-column label="创建用户" prop="nickname" min-width="120" show-overflow-tooltip />
                <el-table-column label="玩法" min-width="120">
                    <template #default="{ row }">
                        <el-tag type="info">{{ playName(row.play) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="记录名" prop="name" min-width="160" show-overflow-tooltip />
                <el-table-column label="任务状态" min-width="110">
                    <template #default="{ row }">
                        <el-tag v-if="row.status == 0" type="info">待提交</el-tag>
                        <el-tag v-else-if="row.status == 1" type="warning">
                            {{ row.step_label || "生成中" }}
                        </el-tag>
                        <el-tag v-else-if="row.status == 2" type="success">成功</el-tag>
                        <el-tag v-else-if="row.status == 3" type="danger">失败</el-tag>
                        <el-tag v-else-if="row.status == 4" type="danger">超时</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="成片" min-width="90">
                    <template #default="{ row }">
                        <el-button v-if="row.video_url" type="primary" link @click="handlePreview(row.video_url)">
                            预览
                        </el-button>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="时长(秒)" min-width="90">
                    <template #default="{ row }">{{ row.duration || "-" }}</template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="tokens_cost" min-width="100" />
                <el-table-column label="计费状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag v-if="row.bill_status == 1" type="warning">已预扣</el-tag>
                        <el-tag v-else-if="row.bill_status == 2" type="success">已结算</el-tag>
                        <el-tag v-else-if="row.bill_status == 3" type="info">已退款</el-tag>
                        <el-tag v-else type="info">未扣费</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="失败原因" prop="fail_reason" min-width="160" show-overflow-tooltip />
                <el-table-column label="创建时间" min-width="170">
                    <template #default="{ row }">
                        {{ row.create_time ? timeFormat(row.create_time, "yyyy-mm-dd hh:MM:ss") : "-" }}
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="showPreview" width="420px" title="成片预览" destroy-on-close align-center>
            <video v-if="previewUrl" :src="previewUrl" controls autoplay class="w-full rounded-lg bg-black max-h-[600px]" />
        </el-dialog>
    </div>
</template>
<script lang="ts" setup>
import { getPlayVideoTaskLists } from "@/api/ai_application/digital_human/play_video";
import { usePaging } from "@/hooks/usePaging";
import { timeFormat } from "@/utils/util";

const queryParams = reactive({
    sn: "",
    play: "",
    status: "",
    start_time: "",
    end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPlayVideoTaskLists,
    params: queryParams,
});

const playName = (play: string) =>
    ({ face: "影视换脸改词", dance: "跳舞换脸", clip: "影视引流拼接" })[play] || play || "-";

const showPreview = ref(false);
const previewUrl = ref("");
const handlePreview = (url: string) => {
    previewUrl.value = url;
    showPreview.value = true;
};

getLists();
</script>
