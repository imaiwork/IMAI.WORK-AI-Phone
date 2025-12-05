<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="视频名称">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.name"
                        placeholder="请输入视频名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.user"
                        placeholder="请输入用户"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="使用模型">
                    <el-select v-model="queryParams.model_version" class="!w-[180px]" placeholder="请选择使用模型">
                        <el-option v-for="item in modelChannel" :key="item.id" :label="item.name" :value="item.id" />
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
            <div class="mb-4 flex justify-between">
                <el-button
                    v-perms="['dh_record.video/del']"
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
                <el-table-column label="ID" prop="id" min-width="60" />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="140" show-overflow-tooltip />
                <el-table-column label="视频名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="形象名称" prop="anchor_name" min-width="180" show-overflow-tooltip />
                <el-table-column label="智能剪辑类型" width="120">
                    <template #default="{ row }">
                        {{ row.automatic_clip == 1 ? ClipStyleMap[row.clip_type] : "-" }}
                    </template>
                </el-table-column>
                <el-table-column label="使用模型" width="120">
                    <template #default="{ row }">
                        {{ getModelName(row.model_version) }}
                    </template>
                </el-table-column>
                <el-table-column label="使用音色" min-width="120" prop="voice_name" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.voice_name || "原视频声音" }}
                    </template>
                </el-table-column>
                <el-table-column label="生成模式" min-width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ getVideoType(row.audio_type) }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" min-width="120">
                    <template #default="{ row }">
                        <div>
                            <div>合成：{{ row.video_points || 0 }}算力</div>
                            <div v-if="row.automatic_clip == 1">剪辑：{{ row.clip_points || 0 }}算力</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="生成状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.status == 1">
                            <el-tag type="success">生成成功</el-tag>
                        </template>
                        <template v-else-if="row.status == 2">
                            <el-tag type="danger">生成失败</el-tag>
                        </template>
                        <template v-else-if="[0, 5].includes(row.status)">
                            <el-tag type="warning">生成中</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="剪辑状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.automatic_clip == 1">
                            <template v-if="row.clip_status == 3">
                                <el-tag type="success">剪辑成功</el-tag>
                            </template>
                            <template v-else-if="[0, 1, 2].includes(row.clip_status)">
                                <el-tag type="warning">剪辑中</el-tag>
                            </template>
                            <template v-else-if="row.clip_status == 4">
                                <el-tag type="danger">剪辑失败</el-tag>
                            </template>
                        </template>
                        <template v-else> - </template>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" show-overflow-tooltip />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)"> 播放 </el-button>
                        <el-button v-perms="['dh_record.video/del']" type="danger" link @click="handleDelete([row.id])">
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
    <el-dialog v-model="showVideo" width="740px" title="视频预览">
        <div>
            <div class="text-lg font-bold mb-2">数字人视频</div>
            <video-player ref="playerRef" :src="videoData.url" width="100%" height="450px" />
            <el-button class="mt-2" type="primary" @click="downloadFile(videoData.url)">下载</el-button>
        </div>
        <div v-if="videoData.ai_url" class="mt-2">
            <div class="text-lg font-bold mb-2">AI剪辑视频</div>
            <video-player ref="playerRef" :src="videoData.ai_url" width="100%" height="450px" />
            <el-button class="mt-2" type="primary" @click="downloadFile(videoData.ai_url)">下载</el-button>
        </div>
    </el-dialog>
</template>
<script lang="ts" setup>
import { getVideoRecord, deleteVideoRecord } from "@/api/ai_application/digital_human/record";
import { usePaging } from "@/hooks/usePaging";
import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";
import { ClipStyleMap } from "@/enums/appEnums";
import { downloadFile } from "@/utils/util";

const appStore = useAppStore();
const { config } = toRefs(appStore);
const modelChannel = computed(() => config.value?.digital_human.channel);

enum DubTypeEnum {
    TEXT = 1, // 文本
    AUDIO = 2, // 音频
}

const dubTypeIndex: Record<number, string> = {
    [DubTypeEnum.TEXT]: "文字驱动",
    [DubTypeEnum.AUDIO]: "语音驱动",
};

const queryParams = reactive({
    name: "",
    user: "",
    start_time: "",
    end_time: "",
    model_version: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getVideoRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const getModelName = (model_version: string) => {
    return modelChannel.value.find((item: any) => item.id == model_version)?.name;
};

const getVideoType = (audio_type: number) => {
    return dubTypeIndex[audio_type];
};

const showVideo = ref(false);
const videoData = reactive({
    url: "",
    ai_url: "",
});

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handlePlay = async (row: any) => {
    showVideo.value = true;
    videoData.url = row.result_url;
    if (row.clip_result_url) {
        videoData.ai_url = row.clip_result_url;
    }
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteVideoRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

getLists();
</script>
