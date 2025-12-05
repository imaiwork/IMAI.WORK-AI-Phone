<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header :content="detail.name" @back="$router.back()" />
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex gap-x-4 justify-between">
                <div class="flex gap-x-4">
                    <div>
                        <div class="text-lg font-bold break-all">{{ detail?.name }}</div>
                        <div class="text-sm mt-3">{{ detail?.create_time }}</div>
                        <div class="flex items-center gap-x-2 mt-3">
                            <Icon name="el-icon-Calendar"></Icon>
                            <span>{{ detail?.publish_cycle }}天</span>
                        </div>
                    </div>
                    <div class="w-[50%]">
                        <div class="flex flex-wrap gap-3">
                            <div v-for="(time, index) in detail.times" :key="index">
                                <el-tag type="info">{{ time }}</el-tag>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <el-progress
                        type="circle"
                        :percentage="
                            isNaN(detail?.published_count / detail?.count)
                                ? 0
                                : ((detail?.published_count / detail?.count) * 100).toFixed(2)
                        "
                        :width="100" />
                </div>
            </div>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                row-key="id"
                @selection-change="handleSelectionChange">
                <el-table-column type="selection" width="55" fixed="left" reserve-selection />
                <el-table-column label="ID" prop="id" min-width="60" />
                <el-table-column label="发布标题" prop="material_title" min-width="140" show-overflow-tooltip />
                <el-table-column label="正文内容" prop="material_subtitle" min-width="180" show-overflow-tooltip />
                <el-table-column label="封面图" prop="pic" min-width="120">
                    <template #default="{ row }">
                        <image-contain
                            v-if="row.pic"
                            :src="row.pic"
                            :width="48"
                            :height="48"
                            fit="cover"
                            preview-teleported
                            :preview-src-list="[row.pic]" />
                    </template>
                </el-table-column>
                <el-table-column label="发布状态" min-width="120">
                    <template #default="{ row }">
                        <template v-if="row.status == 1">
                            <el-tag type="success">成功</el-tag>
                        </template>
                        <template v-else-if="row.status == 2">
                            <el-tag type="danger">失败</el-tag>
                        </template>
                        <template v-else>
                            <el-tag type="warning">等待发布</el-tag>
                        </template>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180" />
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row)"> 播放 </el-button>
                        <el-button
                            v-perms="['ai_application.montage.publish_record/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
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

<script setup lang="ts">
import {
    getMontagePublishDetail,
    getMontagePublishDetailRecord,
    deleteMontagePublishDetailRecord,
} from "@/api/ai_application/digital_human/montage";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElTable } from "element-plus";

const route = useRoute();

const detail = ref<any>({
    name: "",
    count: 0,
    create_time: "",
    publish_cycle: "",
    published_count: 0,
});

const queryParams = reactive({
    name: "",
    start_time: "",
    end_time: "",
    user: "",
    id: route.query.id,
});

const { pager, getLists } = usePaging({
    fetchFun: getMontagePublishDetailRecord,
    params: queryParams,
});
const tableRef = ref<InstanceType<typeof ElTable>>();

const multipleSelection = ref<any[]>([]);

const handleSelectionChange = (val: any[]) => {
    multipleSelection.value = val;
};

const handleDelete = async (id: number | number[]) => {
    await feedback.confirm("确定要删除吗？");
    await deleteMontagePublishDetailRecord({ id });
    getLists();
    multipleSelection.value = [];
    tableRef.value?.clearSelection();
};

const showVideo = ref(false);
const videoUrl = ref("");
const handlePlay = (row: any) => {
    showVideo.value = true;
    videoUrl.value = row.material_url;
};
const getDetail = async () => {
    const res = await getMontagePublishDetail({ id: route.query.id });
    detail.value = res;
};
getLists();
getDetail();
</script>

<style scoped></style>
