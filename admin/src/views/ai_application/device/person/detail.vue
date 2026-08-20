<template>
    <div>
        <el-card class="!border-none !rounded-2xl overflow-hidden" shadow="never">
            <el-page-header @back="$router.back()" :content="detail.persona_name || route.query.name" />
        </el-card>

        <el-card class="!border-none !rounded-2xl mt-5" shadow="never">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 rounded-sm bg-gradient-to-b from-[#667eea] to-[#764ba2]" />
                <span class="text-base font-semibold text-[#1e1e2e]">人设信息</span>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <!-- ① 基础信息 -->
                <div
                    class="flex items-center gap-3.5 px-5 py-[18px] rounded-2xl border border-[#ddd6fe] bg-gradient-to-br from-[#f5f3ff] to-[#eef2ff] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] cursor-default">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0 bg-[#ede9fe]">
                        🧑‍🎨
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] font-bold tracking-wider uppercase text-[#8b5cf6]">基础信息</div>
                        <div class="flex items-center gap-2 mt-1">
                            <el-avatar
                                :size="28"
                                :src="detail.avatar_url"
                                class="!bg-gradient-to-br !from-[#667eea] !to-[#764ba2] !text-white !text-xs flex-shrink-0">
                                {{ detail.persona_name?.charAt(0) }}
                            </el-avatar>
                            <span class="text-sm font-bold truncate text-[#3730a3]">{{ detail.persona_name }}</span>
                        </div>
                    </div>
                    <button
                        class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-[10px] text-xs font-semibold text-[#6366f1] bg-white border border-[#c7d2fe] cursor-pointer transition-all duration-150 hover:bg-[#eef2ff] hover:shadow-[0_2px_8px_rgba(99,102,241,0.15)] hover:scale-105 whitespace-nowrap"
                        @click="handleEdit">
                        <svg
                            class="w-3 h-3 stroke-[#6366f1] fill-none stroke-2"
                            viewBox="0 0 24 24"
                            stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                        编辑
                    </button>
                </div>
            </div>
        </el-card>

        <ai-employee-section class="mt-5" :detail="detail" @refresh="getDetail" />

        <el-card class="!border-none !rounded-2xl mt-5" shadow="never">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 rounded-sm bg-gradient-to-b from-[#667eea] to-[#764ba2]" />
                <span class="text-base font-semibold text-gray-800">IP 创作记录</span>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="pager.loading"
                :data="pager.lists"
                class="!rounded-xl overflow-hidden"
                :header-cell-style="{ background: '#f8f9ff', color: '#5a5e7a', fontWeight: '600', fontSize: '13px' }"
                :row-style="{ cursor: 'default' }">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="任务ID" prop="task_id" width="180" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span class="font-mono text-sm text-[#6366f1]">{{ row.task_id }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <el-avatar
                                :size="28"
                                class="!bg-gradient-to-br !from-[#667eea] !to-[#764ba2] !text-white !text-xs flex-shrink-0">
                                {{ row.nickname?.charAt(0) }}
                            </el-avatar>
                            <span>{{ row.nickname }}</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="任务名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="任务状态" min-width="120">
                    <template #default="{ row }">
                        <el-tag v-if="row.status == 1" type="info" effect="light" round>形象合成中</el-tag>
                        <el-tag v-else-if="row.status == 2" type="danger" effect="light" round>形象合成失败</el-tag>
                        <el-tag v-else-if="row.status == 3" type="success" effect="light" round>形象合成成功</el-tag>
                        <el-tag v-else-if="row.status == 4" type="info" effect="light" round>音色合成中</el-tag>
                        <el-tag v-else-if="row.status == 5" type="danger" effect="light" round>音色合成失败</el-tag>
                        <el-tag v-else-if="row.status == 6" type="success" effect="light" round>音色合成成功</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="消耗算力" prop="video_token" min-width="120">
                    <template #default="{ row }">
                        <span class="font-semibold text-[#6366f1]">{{ row.video_token }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180">
                    <template #default="{ row }">
                        <span class="text-sm text-[#9ca3af]">{{ row.create_time }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="220" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.status == 2"
                            type="danger"
                            link
                            @click="handleViewFailReason(row)"
                            >查看原因</el-button
                        >
                        <el-button type="primary" link @click="handlePlay(row.video_result_url)">播放</el-button>
                        <el-button type="primary" link @click="handleDownload(row)">下载</el-button>
                        <el-button
                            type="danger"
                            link
                            size="small"
                            v-perms="['ai_application.device.person.detail/delete']"
                            @click="deleteRecord(row)"
                            >删除</el-button
                        >
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-card class="!border-none !rounded-2xl mt-5" shadow="never">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 rounded-sm bg-gradient-to-b from-[#667eea] to-[#764ba2]" />
                <span class="text-base font-semibold text-gray-800">素材管理</span>
            </div>
            <div>
                <el-tabs v-model="materialTab" @tab-click="handleMaterialTabClick">
                    <el-tab-pane label="自动生成内容" :name="1"></el-tab-pane>
                    <el-tab-pane label="指定素材内容" :name="2"></el-tab-pane>
                </el-tabs>
            </div>
            <el-table
                ref="tableRef"
                size="large"
                v-loading="materialPager.loading"
                :data="materialPager.lists"
                class="!rounded-xl overflow-hidden"
                :header-cell-style="{ background: '#f8f9ff', color: '#5a5e7a', fontWeight: '600', fontSize: '13px' }"
                :row-style="{ cursor: 'pointer' }"
                @row-click="handleMaterialClick">
                <el-table-column label="ID" prop="id" min-width="80" />
                <el-table-column label="素材类型" prop="material_type" min-width="120" show-overflow-tooltip>
                    <template #default="{ row }">
                        {{ row.material_type === 1 ? "视频" : "图片" }}
                    </template>
                </el-table-column>
                <el-table-column label="素材名称" prop="material_name" min-width="180" show-overflow-tooltip />
                <el-table-column label="使用次数" prop="use_count" min-width="120">
                    <template #default="{ row }">
                        <span class="font-semibold text-[#6366f1]">{{ row.use_num }}</span>
                        <span class="text-xs ml-1 text-[#9ca3af]">次</span>
                    </template>
                </el-table-column>
                <el-table-column label="使用状态" min-width="120">
                    <template #default="{ row }">
                        <el-tag :type="row.use_status == 2 ? 'danger' : 'success'" effect="light" round>
                            {{ row.use_status == 2 ? "已停止使用" : "启用中" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="上传时间" prop="create_time" min-width="180">
                    <template #default="{ row }">
                        <span class="text-sm text-[#9ca3af]">{{ row.create_time }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            type="danger"
                            link
                            size="small"
                            v-perms="['ai_application.device.person.detail/delete']"
                            @click.stop="handleDeleteMaterial(row)"
                            >删除</el-button
                        >
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="materialPager" @change="getMaterialLists" />
            </div>
        </el-card>
    </div>

    <edit-pop v-if="showEditPop" ref="editPopRef" @success="getDetail" @close="showEditPop = false" />
    <el-image-viewer
        v-if="showPreviewImage"
        :url-list="[previewUrl]"
        hide-on-click-modal
        :teleported="true"
        @close="showPreviewImage = false" />
    <el-dialog v-if="showPreviewVideo" v-model="showPreviewVideo" width="740px" title="视频预览">
        <video-player ref="playerRef" :src="previewUrl" width="100%" height="450px" />
    </el-dialog>
</template>

<script setup lang="ts">
import { getPersonDetail, getMaterialList, updateMaterial, deleteMaterial } from "@/api/ai_application/device/person";
import {
    getMontageCreateVideoRecord,
    deleteMontageCreateVideoRecord,
} from "@/api/ai_application/digital_human/montage";
import { usePaging } from "@/hooks/usePaging";
import EditPop from "./components/edit-pop.vue";
import AiEmployeeSection from "./components/ai-employee-section.vue";
import feedback from "@/utils/feedback";
import { downloadFile } from "@/utils/util";

const route = useRoute();
const id = route.query.id;
const detail = ref<any>({});
const originPersonType = ref<number>();
const materialTab = ref<number>(1);
const showPreviewImage = ref(false);
const showPreviewVideo = ref(false);
const previewUrl = ref<string>("");
const showEditPop = ref(false);
const editPopRef = ref<InstanceType<typeof EditPop>>();

const { pager, getLists } = usePaging({
    fetchFun: getMontageCreateVideoRecord,
    params: { persona_id: id },
    size: 5,
});

const materialParams = reactive({
    persona_id: id,
    publish_mode: materialTab.value,
});

const { pager: materialPager, getLists: getMaterialLists } = usePaging({
    fetchFun: getMaterialList,
    params: materialParams,
    size: 5,
});

const handleMaterialTabClick = (e: any) => {
    materialParams.publish_mode = e.paneName;
    getMaterialLists();
};

const getDetail = async () => {
    const res = await getPersonDetail({ id });
    originPersonType.value = res.persona_type;
    detail.value = res;
};

const handleEdit = async () => {
    showEditPop.value = true;
    await nextTick();
    editPopRef.value?.open();
    editPopRef.value?.setFormData(detail.value);
};

const toggleMaterial = async (row: any) => {
    await updateMaterial({ id: row.id, use_status: row.use_status == 2 ? 1 : 2 });
    getMaterialLists();
};

const handleDeleteMaterial = async (row: any) => {
    await feedback.confirm("确定要删除该素材吗？");
    await deleteMaterial({ id: row.id });
    getMaterialLists();
};

const handleMaterialClick = (row: any) => {
    if (row.material_type == 1) {
        showPreviewVideo.value = true;
    } else {
        showPreviewImage.value = true;
    }
    previewUrl.value = row.file_url;
};

const deleteRecord = async (row: any) => {
    await feedback.confirm("确定要删除该创作记录吗？");
    await deleteMontageCreateVideoRecord({ id: row.id });
    getLists();
};

const handleDownload = async (row: any) => {
    const { video_result_url } = row;
    if (!video_result_url) {
        feedback.msgError("视频地址为空");
        return;
    }
    downloadFile(video_result_url);
};

const handlePlay = async (url: string) => {
    if (!url) {
        feedback.msgError("视频地址为空");
        return;
    }
    showPreviewVideo.value = true;
    previewUrl.value = url;
};

const handleViewFailReason = (row: any) => {
    const reason = String(row?.remark || "").trim() || "暂无失败原因";
    feedback.alert(reason, "失败原因");
};

onMounted(() => {
    getDetail();
    getLists();
    getMaterialLists();
});
</script>

<style scoped>
:deep(.el-card__body) {
    padding: 24px 28px;
}
:deep(.el-table__row:hover > td) {
    background-color: #fafbff !important;
}
:deep(.el-progress-bar__inner) {
    background: linear-gradient(90deg, #667eea, #764ba2);
}
</style>
