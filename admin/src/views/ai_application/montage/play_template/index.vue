<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="info"
                :closable="false"
                show-icon
                title="模板内容由模板中心统一运营，每小时自动同步到本站（含素材转存）；本页的「前台显示」开关为本站本地控制，关闭后该模板不在前台展示，且不会被同步覆盖。"
                class="mb-4" />
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
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
                <el-form-item label="名称">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.name"
                        placeholder="请输入模板名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[140px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage">
                        <el-option label="全部" value="" />
                        <el-option label="前台显示" value="1" />
                        <el-option label="已隐藏" value="0" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists" row-key="id">
                <el-table-column label="封面" min-width="90">
                    <template #default="{ row }">
                        <el-image
                            v-if="row.cover_url"
                            :src="row.cover_url"
                            fit="cover"
                            class="h-16 w-12 rounded-md"
                            :preview-src-list="[row.cover_url]"
                            preview-teleported />
                        <div v-else class="h-16 w-12 rounded-md bg-[#f0f2f5]" />
                    </template>
                </el-table-column>
                <el-table-column label="玩法" min-width="110">
                    <template #default="{ row }">
                        <el-tag type="info">{{ playName(row.play) }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="名称" prop="name" min-width="150" show-overflow-tooltip />
                <el-table-column label="分类/热度" min-width="110">
                    <template #default="{ row }">{{ row.category || "-" }} / {{ row.heat || "-" }}</template>
                </el-table-column>
                <el-table-column label="时长(秒)" min-width="80">
                    <template #default="{ row }">{{ row.duration_sec || "-" }}</template>
                </el-table-column>
                <el-table-column label="人脸数" min-width="70">
                    <template #default="{ row }">{{ row.face_count || "-" }}</template>
                </el-table-column>
                <el-table-column label="台本" min-width="70">
                    <template #default="{ row }">{{ row.script_count ? row.script_count + " 句" : "-" }}</template>
                </el-table-column>
                <el-table-column label="背景可换" min-width="80">
                    <template #default="{ row }">{{ row.allow_bg_replace ? "是" : "-" }}</template>
                </el-table-column>
                <el-table-column label="前台显示" min-width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['playVideo.playVideoTemplate/changeStatus']"
                            :model-value="row.status === 1"
                            @change="handleStatus(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="60" />
                <el-table-column label="同步时间" min-width="170">
                    <template #default="{ row }">
                        {{ row.update_time ? timeFormat(row.update_time, "yyyy-mm-dd hh:MM:ss") : "-" }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button v-if="row.video_url" type="primary" link @click="handlePreview(row.video_url)">
                            预览
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog v-model="showPreview" width="420px" title="模板预览" destroy-on-close align-center>
            <video
                v-if="previewUrl"
                :src="previewUrl"
                controls
                autoplay
                class="w-full rounded-lg bg-black max-h-[600px]" />
        </el-dialog>
    </div>
</template>
<script lang="ts" setup>
import {
    getPlayVideoTemplateLists,
    changePlayVideoTemplateStatus,
} from "@/api/ai_application/digital_human/play_video";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";
import { timeFormat } from "@/utils/util";

const queryParams = reactive({
    play: "",
    name: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getPlayVideoTemplateLists,
    params: queryParams,
});

const playName = (play: string) =>
    ({ face: "影视换脸改词", dance: "跳舞换脸", clip: "影视引流拼接" }[play] || play || "-");

const handleStatus = async (row: any) => {
    const next = row.status === 1 ? 0 : 1;
    if (next === 0) {
        await feedback.confirm(`确定隐藏「${row.name}」吗？前台用户将看不到该模板（本地设置，不影响模板中心）。`);
    }
    await changePlayVideoTemplateStatus({ id: row.id, status: next });
    getLists();
};

const showPreview = ref(false);
const previewUrl = ref("");
const handlePreview = (url: string) => {
    previewUrl.value = url;
    showPreview.value = true;
};

getLists();
</script>
