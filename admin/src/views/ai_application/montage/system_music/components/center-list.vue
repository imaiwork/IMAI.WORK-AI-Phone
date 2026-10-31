<template>
    <div>
        <el-alert
            type="info"
            :closable="false"
            show-icon
            title="曲目由模板中心统一运营，每 5 分钟自动同步到本站。本页的「前台显示」「本站排序」「分组」仅在本站生效，同步不会覆盖；隐藏后用户端选曲列表、随机配乐与指定曲目都不再使用该曲目。名称与音频以模板中心为准，需要改名或删除请到「本站上传」tab 维护自己上传的曲目。"
            class="mb-4" />
        <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
            <el-form-item label="名称">
                <el-input
                    class="w-[220px]"
                    v-model="queryParams.name"
                    placeholder="请输入曲目名称"
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
            <el-form-item label="分组">
                <el-select
                    class="!w-[160px]"
                    v-model="queryParams.group_id"
                    placeholder="全部分组"
                    clearable
                    :empty-values="[undefined, null]"
                    @change="resetPage">
                    <el-option label="全部分组" value="" />
                    <el-option label="无分组" :value="0" />
                    <el-option v-for="item in groupOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="resetPage">查询</el-button>
                <el-button @click="resetParams">重置</el-button>
            </el-form-item>
        </el-form>

        <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists" row-key="id">
            <el-table-column label="名称" prop="name" min-width="180" show-overflow-tooltip />
            <el-table-column label="试听" min-width="240">
                <template #default="{ row }">
                    <audio v-if="row.audio_url" :src="row.audio_url" controls preload="none" class="w-full h-9" />
                    <span v-else class="text-tx-secondary">-</span>
                </template>
            </el-table-column>
            <el-table-column label="时长" width="90" align="center">
                <template #default="{ row }">{{ formatDuration(row.duration_sec) }}</template>
            </el-table-column>
            <el-table-column label="分组" min-width="150">
                <template #default="{ row }">
                    <el-select
                        class="!w-[140px]"
                        :model-value="row.group_id"
                        :disabled="!hasAuth(['shanjian.systemMusic/changeGroup'])"
                        @change="(val) => handleGroup(row, val as number)">
                        <el-option label="无分组" :value="0" />
                        <el-option v-for="item in groupOptions" :key="item.id" :label="item.name" :value="item.id" />
                    </el-select>
                </template>
            </el-table-column>
            <el-table-column label="中心状态" width="100" align="center">
                <template #default="{ row }">
                    <el-tag v-if="row.enabled === 1" type="success">上架</el-tag>
                    <el-tag v-else type="danger">已下架</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="中心排序" width="90" align="center">
                <template #default="{ row }">{{ row.sort ?? "-" }}</template>
            </el-table-column>
            <el-table-column label="本站排序" width="170">
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
                        v-perms="['shanjian.systemMusic/changeStatus']"
                        :model-value="row.status === 1"
                        @change="handleStatus(row)" />
                </template>
            </el-table-column>
            <el-table-column label="内容更新时间" min-width="170">
                <template #default="{ row }">{{ row.update_time || "-" }}</template>
            </el-table-column>
        </el-table>
        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { changeSystemMusicGroup, changeSystemMusicStatus, getSystemMusicLists, updateSystemMusicSort } from "@/api/ai_application/digital_human/system_music";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";
import { useAuth } from "@/hooks/useAuth";
import { formatDuration, loadMusicGroups } from "../shared";

const { hasAuth } = useAuth();

const queryParams = reactive({
    name: "",
    status: "",
    // 分组筛选：空=全部，0=无分组
    group_id: "" as number | "",
    // 只看模板中心同步来的曲目（本站上传的在另一个 tab）
    source: "center",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getSystemMusicLists,
    params: queryParams,
});

const groupOptions = ref<Array<{ id: number; name: string }>>([]);
loadMusicGroups().then((list) => {
    groupOptions.value = list;
});

const handleStatus = async (row: any) => {
    const next = row.status === 1 ? 0 : 1;
    if (next === 0) {
        await feedback.confirm(`确定隐藏「${row.name}」吗？隐藏后用户端选曲与随机配乐都不再使用该曲目（本站设置，不影响模板中心）。`);
    }
    await changeSystemMusicStatus({ id: row.id, status: next });
    getLists();
};

const handleSort = async (row: any, val: number | undefined) => {
    if (val === undefined || val === null) return;
    await updateSystemMusicSort({ id: row.id, sort: val });
    feedback.msgSuccess("排序已更新");
    getLists();
};

const handleResetSort = async (row: any) => {
    await updateSystemMusicSort({ id: row.id, sort: "" });
    row.sort_override = null;
    feedback.msgSuccess("已恢复跟随模板中心");
    getLists();
};

// 分组是本站设置，模板中心同步不会覆盖，所以 center 曲目也允许改
const handleGroup = async (row: any, val: number) => {
    if (row.group_id === val) return;
    await changeSystemMusicGroup({ id: row.id, group_id: val });
    feedback.msgSuccess("分组已更新");
    getLists();
};

getLists();
</script>
