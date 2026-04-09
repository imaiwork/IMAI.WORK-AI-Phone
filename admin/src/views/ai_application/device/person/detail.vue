<template>
    <div>
        <el-card class="!border-none !rounded-2xl overflow-hidden" shadow="never">
            <el-page-header @back="$router.back()" :content="detail.persona_name || route.query.name" />
        </el-card>

        <el-card class="!border-none !rounded-2xl mt-5" shadow="never">
            <div class="flex items-center gap-2 mb-6">
                <div class="w-1 h-5 rounded-sm" style="background: linear-gradient(135deg, #667eea, #764ba2)" />
                <span class="text-base font-semibold" style="color: #1e1e2e">IP 人设管理</span>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="col-span-2 flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 hover:shadow-lg cursor-default"
                    style="background: linear-gradient(135deg, #f5f3ff, #eef2ff); border-color: #e0e7ff">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        style="background: #ede9fe">
                        🧑‍🎨
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs mb-2 font-medium" style="color: #a5b4fc">IP 信息</div>
                        <div class="flex items-center gap-2">
                            <el-avatar
                                :size="34"
                                :src="detail.avatar_url"
                                style="
                                    background: linear-gradient(135deg, #667eea, #764ba2);
                                    color: #fff;
                                    font-size: 13px;
                                    flex-shrink: 0;
                                ">
                                {{ detail.persona_name?.charAt(0) }}
                            </el-avatar>
                            <span class="text-sm font-semibold" style="color: #3730a3">{{ detail.persona_name }}</span>
                        </div>
                    </div>
                    <el-button
                        size="small"
                        class="!rounded-xl !font-medium"
                        style="color: #6366f1; border-color: #c7d2fe; background: #fff"
                        @click="handleEditInfo">
                        <Icon name="el-icon-edit" class="mr-1" />编辑
                    </el-button>
                </div>

                <div
                    class="flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 hover:shadow-lg cursor-default"
                    style="background: linear-gradient(135deg, #faf5ff, #f3e8ff); border-color: #e9d5ff">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        style="background: #f3e8ff">
                        🏷️
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs mb-2 font-medium" style="color: #c084fc">IP 类型</div>
                        <el-tag effect="light" round style="background: #f3e8ff; color: #7e22ce; border-color: #d8b4fe">
                            {{ getPersonaType(detail.persona_type) }}
                        </el-tag>
                    </div>
                    <el-button
                        size="small"
                        class="!rounded-xl !font-medium"
                        style="color: #6366f1; border-color: #c7d2fe; background: #fff"
                        @click="handleEditType">
                        <Icon name="el-icon-edit" class="mr-1" />编辑
                    </el-button>
                </div>

                <div
                    class="flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 hover:shadow-lg cursor-default"
                    style="background: linear-gradient(135deg, #fff7ed, #ffedd5); border-color: #fed7aa">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        style="background: #ffedd5">
                        🎯
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs mb-1 font-medium" style="color: #fb923c">截流获客配置</div>
                        <div class="text-xs" style="color: #d1d5db">点击编辑进行配置</div>
                    </div>
                    <el-button
                        size="small"
                        class="!rounded-xl !font-medium"
                        style="color: #6366f1; border-color: #c7d2fe; background: #fff"
                        @click="handleEditTraffic">
                        <Icon name="el-icon-edit" class="mr-1" />编辑
                    </el-button>
                </div>

                <div
                    class="flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 hover:shadow-lg cursor-default"
                    style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #bbf7d0">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        style="background: #dcfce7">
                        📚
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs mb-1 font-medium" style="color: #4ade80">知识库配置</div>
                        <div class="text-xs" style="color: #d1d5db">点击编辑进行配置</div>
                    </div>
                    <el-button
                        size="small"
                        class="!rounded-xl !font-medium"
                        style="color: #6366f1; border-color: #c7d2fe; background: #fff"
                        @click="handleEditKn">
                        <Icon name="el-icon-edit" class="mr-1" />编辑
                    </el-button>
                </div>

                <div
                    class="flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 hover:shadow-lg cursor-default"
                    style="background: linear-gradient(135deg, #ecfeff, #cffafe); border-color: #a5f3fc">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                        style="background: #cffafe">
                        🤖
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs mb-1 font-medium" style="color: #22d3ee">智能体配置</div>
                        <div class="text-xs" style="color: #d1d5db">点击编辑进行配置</div>
                    </div>
                    <el-button
                        size="small"
                        class="!rounded-xl !font-medium"
                        style="color: #6366f1; border-color: #c7d2fe; background: #fff"
                        @click="handleEditAgent">
                        <Icon name="el-icon-edit" class="mr-1" />编辑
                    </el-button>
                </div>
            </div>
        </el-card>

        <el-card class="!border-none !rounded-2xl mt-5" shadow="never">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-1 h-5 rounded-sm" style="background: linear-gradient(135deg, #667eea, #764ba2)" />
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
                        <span class="font-mono text-sm" style="color: #6366f1">{{ row.task_id }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创建用户" prop="nickname" min-width="140" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div class="flex items-center gap-2">
                            <el-avatar
                                :size="28"
                                style="
                                    background: linear-gradient(135deg, #667eea, #764ba2);
                                    color: #fff;
                                    font-size: 12px;
                                    flex-shrink: 0;
                                ">
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
                        <span class="font-semibold" style="color: #6366f1">{{ row.video_token }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="创作时间" prop="create_time" min-width="180">
                    <template #default="{ row }">
                        <span class="text-sm" style="color: #9ca3af">{{ row.create_time }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handlePlay(row.video_result_url)"> 播放 </el-button>
                        <el-button type="primary" link @click="handleDownload(row)"> 下载 </el-button>
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
                <div class="w-1 h-5 rounded-sm" style="background: linear-gradient(135deg, #667eea, #764ba2)" />
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
                        <span class="font-semibold" style="color: #6366f1">{{ row.use_num }}</span>
                        <span class="text-xs ml-1" style="color: #9ca3af">次</span>
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
                        <span class="text-sm" style="color: #9ca3af">{{ row.create_time }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            type="warning"
                            link
                            size="small"
                            v-perms="['ai_application.device.person.detail/edit']"
                            @click.stop="toggleMaterial(row)"
                            >{{ row.use_status == 2 ? "启用" : "禁用" }}</el-button
                        >
                        <el-divider direction="vertical" />
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
    <info-pop v-if="showInfoPop" ref="infoPopRef" @success="getDetail" @close="showInfoPop = false" />
    <type-pop v-if="showTypePop" ref="typePopRef" @success="getDetail" @close="showTypePop = false" />
    <traffic-pop v-if="showTrafficPop" ref="trafficPopRef" @success="getDetail" @close="showTrafficPop = false" />
    <kn-pop v-if="showKnPop" ref="knPopRef" @success="getDetail" @close="showKnPop = false" />
    <agent-pop v-if="showAgentPop" ref="agentPopRef" @success="getDetail" @close="showAgentPop = false" />
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
import InfoPop from "./components/info-pop.vue";
import TypePop from "./components/type-pop.vue";
import TrafficPop from "./components/traffic-pop.vue";
import KnPop from "./components/kn-pop.vue";
import AgentPop from "./components/agent-pop.vue";
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
const showInfoPop = ref(false);
const infoPopRef = ref<InstanceType<typeof InfoPop>>();
const showTypePop = ref(false);
const typePopRef = ref<InstanceType<typeof TypePop>>();
const showTrafficPop = ref(false);
const trafficPopRef = ref<InstanceType<typeof TrafficPop>>();
const showKnPop = ref(false);
const knPopRef = ref<InstanceType<typeof KnPop>>();
const showAgentPop = ref(false);
const agentPopRef = ref<InstanceType<typeof AgentPop>>();
const { pager, getLists } = usePaging({
    fetchFun: getMontageCreateVideoRecord,
    params: {
        persona_id: id,
    },
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

const getPersonaType = (type: number) => {
    return {
        1: "个人IP",
        2: "企业服务",
        3: "本地商家",
    }[type];
};

const handleEditInfo = async () => {
    showInfoPop.value = true;
    await nextTick();
    infoPopRef.value?.open();
    infoPopRef.value?.setFormData(detail.value);
};

const handleEditType = async () => {
    showTypePop.value = true;
    await nextTick();
    typePopRef.value?.open();
    const keys = {
        1: "individual",
        2: "enterprise",
        3: "local",
    };
    typePopRef.value?.setFormData({
        // @ts-ignore
        ...detail.value[keys[detail.value.persona_type]],
        id: detail.value.id,
        persona_name: detail.value.persona_name,
        persona_type: detail.value.persona_type,
    });
};

const handleEditTraffic = async () => {
    showTrafficPop.value = true;
    await nextTick();
    trafficPopRef.value?.open(detail.value.id);
};

const handleEditKn = async () => {
    showKnPop.value = true;
    await nextTick();
    knPopRef.value?.open(detail.value.id);
    knPopRef.value?.setFormData(detail.value);
};

const handleEditAgent = async () => {
    showAgentPop.value = true;
    await nextTick();
    agentPopRef.value?.open(detail.value.id);
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
