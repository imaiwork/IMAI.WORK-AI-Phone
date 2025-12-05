<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header content="视频列表" @back="$router.back()" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="名称">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.name"
                        placeholder="请输入名称"
                        clearable
                        @keyup.enter="resetPage" />
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
                    v-perms="['ai_application.redbook.digital_human/delete']"
                    type="default"
                    :plain="true"
                    :disabled="!multipleSelection.length"
                    @click="handleDelete(multipleSelection.map((item) => item.id))">
                    批量删除
                </el-button>
            </div>
            <el-table
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" />
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="生成状态" min-width="120">
                    <template #default="{ row }">
                        {{ getStatusText(row.status) }}
                    </template>
                </el-table-column>
                <el-table-column label="智能剪辑类型" width="120">
                    <template #default="{ row }">
                        {{ ClipStyleMap[row.clip_type] }}
                    </template>
                </el-table-column>
                <el-table-column label="使用音色" prop="voice_name" min-width="120" />
                <el-table-column label="创作文案" prop="msg" min-width="180" show-overflow-tooltip />
                <el-table-column label="费用记录" min-width="120">
                    <template #default="{ row }">
                        <div class="">
                            <div>形象：{{ row.anchor_token }}算力</div>
                            <div>音频：{{ row.audio_token }}算力</div>
                            <div>音色：{{ row.voice_token }}算力</div>
                            <div>视频：{{ row.video_token }}算力</div>
                            <div v-if="row.automatic_clip == 1">剪辑：{{ row.clip_token }}算力</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" width="180" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="previewVideo(row)"> 播放 </el-button>
                        <el-button class="ml-2" type="primary" link @click="downloadFile(row.video_result_url)"
                            >下载</el-button
                        >
                        <el-button
                            v-perms="['ai_application.redbook.dh_detail/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                            >删除</el-button
                        >
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <el-dialog v-model="showPreviewVideo" width="740px" title="视频预览">
            <video-player ref="playerRef" :src="videoUrl" width="100%" height="450px" />
        </el-dialog>
    </div>
</template>
<script lang="ts" setup>
import { usePaging } from "@/hooks/usePaging";
import { getDigitalHumanTaskList, deleteDigitalHumanTask } from "@/api/ai_application/redbook";
import feedback from "@/utils/feedback";
import { ClipStyleMap } from "@/enums/appEnums";
import { downloadFile } from "@/utils/util";

const route = useRoute();

const queryParams = reactive({
    name: "",
    video_setting_id: route.query.id as string,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getDigitalHumanTaskList,
    params: queryParams,
});

enum GenStatus {
    WAITING = 0,
    AUDIO_RESULT_QUERY = 1,
    AUDIO_COMPOSITION_FAILED = 2,
    AUDIO_COMPOSITION_SUCCESS = 3,
    VIDEO_RESULT_QUERY = 4,
    VIDEO_COMPOSITION_FAILED = 5,
    VIDEO_COMPOSITION_SUCCESS = 6,
}

// 获取对应状态文本
const getStatusText = (status: number) => {
    const statusMap = {
        [GenStatus.WAITING]: "待处理",
        [GenStatus.AUDIO_RESULT_QUERY]: "音频结果生成中",
        [GenStatus.AUDIO_COMPOSITION_FAILED]: "音频合成失败",
        [GenStatus.AUDIO_COMPOSITION_SUCCESS]: "音频合成成功",
        [GenStatus.VIDEO_RESULT_QUERY]: "视频结果生成中",
        [GenStatus.VIDEO_COMPOSITION_FAILED]: "视频合成失败",
        [GenStatus.VIDEO_COMPOSITION_SUCCESS]: "视频合成成功",
    } as Record<number, string>;
    return statusMap[status];
};

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    const row = val[0];
    if (![GenStatus.VIDEO_COMPOSITION_SUCCESS].includes(row.status)) {
        feedback.msgError("当前视频正在处理中，不可选中");
        return;
    }
    multipleSelection.value = val;
};

const handleDownload = (url: string) => {
    if (!url) {
        feedback.msgError("视频地址为空");
        return;
    }
    downloadFile(url);
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteDigitalHumanTask({ id });
    getLists();
};

const showPreviewVideo = ref(false);
const videoUrl = ref("");
const previewVideo = async (row: any) => {
    videoUrl.value = row.video_result_url;
    showPreviewVideo.value = true;
    await nextTick();
};

getLists();
</script>
