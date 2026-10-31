<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="info"
                :closable="false"
                show-icon
                title="模板内容由模板中心统一运营，每 5 分钟自动同步到本站（含素材转存）；本页的「前台显示」与「本站排序」仅在本站生效，同步不会覆盖。隐藏后手动创作、24 小时创作与热点/爆款复刻都不再使用该模板。"
                class="mb-4" />
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="场景">
                    <el-select
                        class="!w-[180px]"
                        v-model="queryParams.scene"
                        placeholder="全部"
                        clearable
                        :empty-values="[undefined, null]"
                        @change="resetPage">
                        <el-option label="全部" value="" />
                        <el-option v-for="(label, key) in SCENE_LABELS" :key="key" :label="label" :value="key" />
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
                <el-table-column label="名称" prop="name" min-width="150" show-overflow-tooltip />
                <el-table-column label="场景" min-width="110">
                    <template #default="{ row }">
                        <el-tag type="info">{{ SCENE_LABELS[row.scene] || row.scene || "-" }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="自动化" min-width="80">
                    <template #default="{ row }">{{ row.auto_type === 1 ? "是" : "-" }}</template>
                </el-table-column>
                <el-table-column label="中心排序" width="90" align="center">
                    <template #default="{ row }">{{ row.sort }}</template>
                </el-table-column>
                <el-table-column label="本站排序" width="180">
                    <template #default="{ row }">
                        <div class="flex items-center gap-1">
                            <el-input-number
                                class="!w-[90px]"
                                :controls="false"
                                :min="-99999"
                                :max="99999"
                                v-model="row.sort_override"
                                placeholder="跟随"
                                @change="(val) => handleSort(row, val)" />
                            <el-button
                                v-if="row.sort_override !== null && row.sort_override !== undefined"
                                type="primary"
                                link
                                @click="handleResetSort(row)">
                                跟随
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="前台显示" min-width="90">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['shanjian.shanjianClipTemplate/changeStatus']"
                            :model-value="row.status === 1"
                            @change="handleStatus(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="同步时间" min-width="170">
                    <template #default="{ row }">{{ row.update_time || "-" }}</template>
                </el-table-column>
                <el-table-column label="操作" width="90" fixed="right">
                    <template #default="{ row }">
                        <el-button v-if="row.demo_url" type="primary" link @click="handlePreview(row.demo_url)">
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
            <video v-if="previewUrl" :src="previewUrl" controls autoplay class="w-full rounded-lg bg-black max-h-[600px]" />
        </el-dialog>
    </div>
</template>
<script lang="ts" setup>
import { getClipTemplateLists, changeClipTemplateStatus, updateClipTemplateSort } from "@/api/ai_application/digital_human/clip_template";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";

const SCENE_LABELS: Record<string, string> = {
    virtualman: "数字人口播",
    realMan: "真人混剪",
    oralMixCutting: "纯素材混剪",
    newsMixCutting: "新闻体混剪",
};

const queryParams = reactive({
    scene: "",
    name: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getClipTemplateLists,
    params: queryParams,
});

const handleStatus = async (row: any) => {
    const next = row.status === 1 ? 0 : 1;
    if (next === 0) {
        await feedback.confirm(
            `确定隐藏「${row.name}」吗？隐藏后手动创作与 24 小时创作都不再使用该模板（本站设置，不影响模板中心）。`
        );
    }
    await changeClipTemplateStatus({ id: row.id, status: next });
    getLists();
};

const handleSort = async (row: any, val: number | undefined) => {
    if (val === undefined || val === null) return;
    await updateClipTemplateSort({ id: row.id, sort: val });
    feedback.msgSuccess("排序已更新");
    getLists();
};

const handleResetSort = async (row: any) => {
    await updateClipTemplateSort({ id: row.id, sort: "" });
    row.sort_override = null;
    feedback.msgSuccess("已恢复跟随模板中心");
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
