<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="关键词">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.keyword"
                        placeholder="话题/标题/任务号"
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
                <el-form-item label="状态">
                    <el-select
                        class="!w-[130px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="合成中" value="running" />
                        <el-option label="待确认" value="wait" />
                        <el-option label="已完成" value="done" />
                        <el-option label="失败" value="fail" />
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
                <el-table-column label="任务号" prop="task_no" width="160" show-overflow-tooltip />
                <el-table-column label="用户" prop="user" min-width="100" show-overflow-tooltip />
                <el-table-column label="标题" prop="title" min-width="200" show-overflow-tooltip />
                <el-table-column label="人设" prop="persona_name" min-width="100" show-overflow-tooltip />
                <el-table-column label="类型" width="130">
                    <template #default="{ row }">{{ VIDEO_TYPE_MAP[row.video_type] || row.video_type || "-" }}</template>
                </el-table-column>
                <el-table-column label="状态" width="90">
                    <template #default="{ row }">
                        <el-tag :type="statusTag(row.status)" size="small">
                            {{ STATUS_MAP[row.status] || row.status }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="下发" width="90">
                    <template #default="{ row }">
                        <el-tag :type="dispatchTag(row.dispatch_status)" size="small">
                            {{ DISPATCH_MAP[row.dispatch_status] || row.dispatch_status }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="重试" width="70">
                    <template #default="{ row }">{{ row.retry_seq || "-" }}</template>
                </el-table-column>
                <el-table-column label="失败原因" min-width="160">
                    <template #default="{ row }">
                        <el-tooltip
                            v-if="row.error"
                            :content="row.error"
                            placement="top"
                            :show-after="200"
                            popper-class="max-w-[420px]">
                            <span class="line-clamp-1 cursor-default text-[#f56c6c]">{{ row.error }}</span>
                        </el-tooltip>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="成片" width="80">
                    <template #default="{ row }">
                        <el-link
                            v-if="row.video_url"
                            type="primary"
                            :underline="false"
                            @click="openVideo(row.video_url)">
                            查看
                        </el-link>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="150" show-overflow-tooltip />
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['hotspot.task/detail']" type="primary" link @click="openDetail(row)">
                            详情
                        </el-button>
                        <el-button
                            v-if="row.status === 'fail'"
                            v-perms="['hotspot.task/retry']"
                            type="warning"
                            link
                            @click="handleRetry(row)">
                            重试
                        </el-button>
                        <el-button v-perms="['hotspot.task/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-drawer v-model="detailVisible" title="任务详情" size="520px">
            <div v-loading="detailLoading" class="px-1">
                <template v-if="detail">
                    <div class="text-base font-semibold text-[#111827]">{{ detail.title || detail.topic }}</div>
                    <div class="mt-1 text-xs text-[#9ca3af]">
                        {{ detail.id }} · {{ detail.user }} · 来自热点「{{ detail.topic }}」
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <el-tag :type="statusTag(detail.status)" size="small">
                            {{ STATUS_MAP[detail.status] || detail.status }}
                        </el-tag>
                        <el-tag size="small" type="info">
                            {{ VIDEO_TYPE_MAP[detail.options?.video_type] || detail.options?.video_type || "-" }}
                        </el-tag>
                        <el-tag v-if="detail.options?.avatar" size="small" type="info">{{ detail.options.avatar }}</el-tag>
                        <el-tag size="small" type="info">{{ detail.options?.duration_sec || "-" }}s</el-tag>
                        <el-tag v-if="detail.persona?.name" size="small">{{ detail.persona.name }}</el-tag>
                    </div>

                    <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">步骤状态</div>
                    <div class="flex flex-wrap gap-1">
                        <el-tag
                            v-for="step in STEPS"
                            :key="step.key"
                            :type="stepTag(detail.step_status?.[step.key])"
                            size="small">
                            {{ step.label }}
                        </el-tag>
                    </div>

                    <div v-if="detail.error" class="mt-3 rounded border border-[#fecaca] bg-[#fef2f2] p-3 text-xs text-[#ef4444]">
                        {{ detail.error }}
                    </div>

                    <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">口播文案</div>
                    <div class="whitespace-pre-wrap rounded bg-[#f9fafb] p-3 text-sm leading-relaxed text-[#374151]">
                        {{ detail.script || "（无文案）" }}
                    </div>

                    <div class="mt-4 text-xs text-[#9ca3af]">
                        闪剪任务:{{ detail.shanjian_video_task_id || "-" }} · 创建:{{ detail.created_at || "-" }} ·
                        更新:{{ detail.updated_at || "-" }}
                    </div>
                    <div v-if="detail.video_url" class="mt-3">
                        <el-link type="primary" :underline="false" @click="openVideo(detail.video_url)">
                            查看成片
                        </el-link>
                    </div>
                </template>
            </div>
        </el-drawer>

        <el-dialog v-model="videoVisible" width="420px" title="成片预览" destroy-on-close>
            <video-player v-if="videoVisible" :src="videoUrl" width="100%" height="560px" />
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="hotspotTask">
import { usePaging } from "@/hooks/usePaging";
import {
    getHotspotTaskList,
    getHotspotTaskDetail,
    retryHotspotTask,
    deleteHotspotTask,
} from "@/api/ai_application/hotspot";
import feedback from "@/utils/feedback";

const STATUS_MAP: Record<string, string> = {
    running: "合成中",
    wait: "待确认",
    done: "已完成",
    fail: "失败",
};
const DISPATCH_MAP: Record<string, string> = {
    pending: "待下发",
    dispatching: "下发中",
    done: "已下发",
    fail: "下发失败",
};
const VIDEO_TYPE_MAP: Record<string, string> = {
    digital: "数字人口播混剪",
    clips: "素材混剪",
};
const STEPS = [
    { key: "select", label: "选定热点" },
    { key: "search", label: "联网搜索" },
    { key: "analyze", label: "结合分析" },
    { key: "script", label: "口播文案" },
    { key: "video", label: "视频合成" },
];

const queryParams = reactive({
    keyword: "",
    user: "",
    status: "",
    start_time: "",
    end_time: "",
});

const detailVisible = ref(false);
const detailLoading = ref(false);
const detail = ref<any>(null);

const videoVisible = ref(false);
const videoUrl = ref("");
const openVideo = (url: string) => {
    if (!url) return;
    videoUrl.value = url;
    videoVisible.value = true;
};

const statusTag = (status: string) => {
    if (status === "done") return "success";
    if (status === "fail") return "danger";
    if (status === "running") return "primary";
    return "warning";
};
const dispatchTag = (status: string) => {
    if (status === "done") return "success";
    if (status === "fail") return "danger";
    if (status === "dispatching") return "primary";
    return "info";
};
const stepTag = (status: string) => {
    if (status === "done") return "success";
    if (status === "fail") return "danger";
    if (status === "running") return "primary";
    return "info";
};

const openDetail = async (row: any) => {
    detailVisible.value = true;
    detailLoading.value = true;
    detail.value = null;
    try {
        detail.value = await getHotspotTaskDetail({ id: row.id });
    } finally {
        detailLoading.value = false;
    }
};

const handleRetry = async (row: any) => {
    await feedback.confirm(`确定重试任务 ${row.task_no}?将按原设置重新合成视频。`);
    await retryHotspotTask({ id: row.id });
    feedback.msgSuccess("已重新入队");
    getLists();
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定删除该任务?合成中的任务不可删除。");
    await deleteHotspotTask({ id: row.id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getHotspotTaskList,
    params: queryParams,
});

getLists();
</script>
