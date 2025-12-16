<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header @back="$router.back()" :content="route.query.name" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
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
                    v-perms="['ai_application.montage.create.news.detail/delete']"
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
                <el-table-column label="任务ID" prop="task_id" width="160" show-overflow-tooltip />
                <el-table-column label="素材组" prop="name" min-width="300">
                    <template #default="{ row }">
                        <div class="grid grid-cols-4 gap-2">
                            <div v-for="item in row.material" :key="item.id" class="w-[60px] h-[60px]">
                                <image-contain
                                    v-if="item.type === 'image'"
                                    :src="item.fileUrl"
                                    width="60"
                                    height="60"
                                    fit="cover"
                                    :preview-src-list="[item.fileUrl]"
                                    preview-teleported></image-contain>
                                <video v-else :src="item.fileUrl" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="生成状态" min-width="120">
                    <template #default="{ row }">
                        {{ getStatus(row.status) }}
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="video_token" min-width="120" />
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)"> 播放 </el-button>
                        <el-button type="primary" link @click="handleDownload(row)"> 下载 </el-button>
                        <el-button
                            v-perms="['ai_application.montage.create.news.detail/delete']"
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
    <el-dialog v-model="showVideo" width="740px" title="视频预览">
        <video-player ref="playerRef" :src="videoUrl" width="100%" height="450px" />
    </el-dialog>
</template>
<script lang="ts" setup>
import {
    getMontageCreateVideoRecord,
    deleteMontageCreateVideoRecord,
} from "@/api/ai_application/digital_human/montage";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { downloadFile } from "@/utils/util";
import { ElTable } from "element-plus";

const route = useRoute();

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    user: "",
    status: "",
    video_setting_id: route.query.id,
    shanjian_type: 4,
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMontageCreateVideoRecord,
    params: queryParams,
});

const tableRef = ref<InstanceType<typeof ElTable>>();

const showVideo = ref(false);
const videoUrl = ref("");

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const getStatus = (status: number) => {
    //状态-0待处理,1视频查询,2视频合成失败,3视频合成成功
    const statusMap = {
        0: "待处理",
        1: "视频合成中",
        2: "视频合成失败",
        3: "视频合成成功",
    };
    return statusMap[status as keyof typeof statusMap];
};

const handleDownload = async (row: any) => {
    const { video_result_url } = row;
    if (!video_result_url) {
        feedback.msgError("视频地址为空");
        return;
    }
    downloadFile(video_result_url);
};

const handlePlay = async (row: any) => {
    const { video_result_url } = row;
    if (!video_result_url) {
        feedback.msgError("视频地址为空");
        return;
    }
    showVideo.value = true;
    videoUrl.value = video_result_url;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMontageCreateVideoRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const jumpUrl = (url: string) => {
    window.open(url, "_blank");
};

getLists();
</script>
