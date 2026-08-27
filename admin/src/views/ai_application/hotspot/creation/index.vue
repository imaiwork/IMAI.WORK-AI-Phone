<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="关键词">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.keyword"
                        placeholder="话题/标题关键词"
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
                        class="!w-[140px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        clearable
                        :empty-values="[null, undefined]">
                        <el-option label="全部" value="" />
                        <el-option label="仅文案" value="script" />
                        <el-option label="已建视频任务" value="video" />
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
                <el-table-column label="用户" prop="user" min-width="100" show-overflow-tooltip />
                <el-table-column label="标题" prop="title" min-width="200" show-overflow-tooltip />
                <el-table-column label="来源热点" prop="topic" min-width="180" show-overflow-tooltip />
                <el-table-column label="人设" prop="persona_name" min-width="110" show-overflow-tooltip />
                <el-table-column label="时长" width="80">
                    <template #default="{ row }">{{ row.duration_sec }}s</template>
                </el-table-column>
                <el-table-column label="进度" min-width="120">
                    <template #default="{ row }">
                        <el-tag :type="taskStatusTag(row.task_status)" size="small">
                            {{ TASK_STATUS_MAP[row.task_status] || row.task_status || "仅文案" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="备注" min-width="160">
                    <template #default="{ row }">
                        <el-tooltip
                            v-if="row.remark"
                            :content="row.remark"
                            placement="top"
                            :show-after="200"
                            popper-class="max-w-[420px]">
                            <span class="line-clamp-1 cursor-default">{{ row.remark }}</span>
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
                <el-table-column label="时间" prop="create_time" min-width="150" show-overflow-tooltip />
                <el-table-column label="操作" width="130" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['hotspot.creation/detail']" type="primary" link @click="openDetail(row)">
                            详情
                        </el-button>
                        <el-button v-perms="['hotspot.creation/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-drawer v-model="detailVisible" title="创作详情" size="520px">
            <div v-loading="detailLoading" class="px-1">
                <template v-if="detail">
                    <div class="text-base font-semibold text-[#111827]">{{ detail.title }}</div>
                    <div class="mt-1 text-xs text-[#9ca3af]">
                        {{ detail.record_no }} · {{ detail.user }} · 来自热点「{{ detail.topic }}」
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <el-tag size="small">{{ detail.persona_name || "-" }}</el-tag>
                        <el-tag size="small" type="info">{{ GOAL_MAP[detail.goal] || detail.goal || "-" }}</el-tag>
                        <el-tag size="small" type="info">{{ detail.direction || "-" }}</el-tag>
                        <el-tag size="small" type="info">{{ VIDEO_TYPE_MAP[detail.video_type] || detail.video_type || "-" }}</el-tag>
                        <el-tag size="small" type="info">{{ detail.duration_sec }}s</el-tag>
                        <el-tag :type="taskStatusTag(detail.task_status)" size="small">
                            {{ TASK_STATUS_MAP[detail.task_status] || detail.task_status || "仅文案" }}
                        </el-tag>
                    </div>
                    <div v-if="detail.task_error" class="mt-3 rounded border border-[#fecaca] bg-[#fef2f2] p-3 text-xs text-[#ef4444]">
                        {{ detail.task_error }}
                    </div>
                    <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">
                        口播文案 · {{ detail.word_count }} 字 · 约 {{ detail.est_duration_sec }} 秒
                    </div>
                    <div class="whitespace-pre-wrap rounded bg-[#f9fafb] p-3 text-sm leading-relaxed text-[#374151]">
                        {{ detail.script || "（无文案）" }}
                    </div>
                    <div v-if="(detail.hashtags || []).length" class="mt-3 flex flex-wrap gap-1">
                        <el-tag v-for="tag in detail.hashtags" :key="tag" size="small" type="primary"># {{ tag }}</el-tag>
                    </div>
                    <template v-if="(detail.shots || []).length">
                        <div class="mt-4 mb-1 text-xs font-semibold text-[#9ca3af]">画面建议</div>
                        <div v-for="(shot, index) in detail.shots" :key="index" class="text-xs leading-relaxed text-[#6b7280]">
                            {{ index + 1 }}. {{ shot }}
                        </div>
                    </template>
                    <div v-if="detail.video_url" class="mt-4">
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

<script lang="ts" setup name="hotspotCreation">
import { usePaging } from "@/hooks/usePaging";
import {
    getHotspotCreationList,
    getHotspotCreationDetail,
    deleteHotspotCreation,
} from "@/api/ai_application/hotspot";
import feedback from "@/utils/feedback";

const GOAL_MAP: Record<string, string> = {
    sell: "卖产品",
    leads: "私域获客",
    traffic: "涨粉引流",
    brand: "品牌种草",
    engage: "点击播放",
};
const VIDEO_TYPE_MAP: Record<string, string> = {
    digital: "数字人口播混剪",
    clips: "素材混剪",
};
const TASK_STATUS_MAP: Record<string, string> = {
    running: "合成中",
    wait: "待确认",
    done: "已完成",
    fail: "失败",
    script: "仅文案",
};

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

const taskStatusTag = (status: string) => {
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
        detail.value = await getHotspotCreationDetail({ id: row.id });
    } finally {
        detailLoading.value = false;
    }
};

const handleDelete = async (row: any) => {
    await feedback.confirm("确定删除该创作记录?仅删除后台台账,不影响用户已生成的视频。");
    await deleteHotspotCreation({ id: row.id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getHotspotCreationList,
    params: queryParams,
});

getLists();
</script>
