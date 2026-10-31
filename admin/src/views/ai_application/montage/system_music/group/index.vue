<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-alert
                type="info"
                :closable="false"
                show-icon
                title="分组决定用户端选曲页的 tab 划分（全部 + 各分组）。分组只在本站生效，不参与模板中心同步；没有可用曲目的分组不会出现在用户端。删除分组不会删歌，组内曲目会退回「无分组」，后台列表显示「无」。"
                class="mb-4" />

            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="名称">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.name"
                        placeholder="请输入分组名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
                <el-form-item class="ml-auto">
                    <el-button v-perms="['shanjian.systemMusicGroup/save']" type="primary" @click="openAdd">
                        新增分组
                    </el-button>
                </el-form-item>
            </el-form>

            <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists" row-key="id">
                <el-table-column label="分组名称" prop="name" min-width="180" show-overflow-tooltip />
                <el-table-column label="曲目数" width="100" align="center">
                    <template #default="{ row }">{{ row.music_count }}</template>
                </el-table-column>
                <el-table-column label="排序" width="170">
                    <template #default="{ row }">
                        <el-input-number
                            class="!w-[110px]"
                            :controls="false"
                            :min="-99999"
                            :max="99999"
                            :disabled="!hasAuth(['shanjian.systemMusicGroup/updateSort'])"
                            v-model="row.sort"
                            @change="(val) => handleSort(row, val as number)" />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" min-width="170">
                    <template #default="{ row }">{{ row.create_time || "-" }}</template>
                </el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['shanjian.systemMusicGroup/save']" type="primary" link @click="openEdit(row)">
                            编辑
                        </el-button>
                        <el-button v-perms="['shanjian.systemMusicGroup/delete']" type="danger" link @click="handleDelete(row)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <el-dialog
            v-model="showForm"
            :title="form.id ? '编辑分组' : '新增分组'"
            width="460px"
            :close-on-click-modal="false"
            @closed="resetForm">
            <el-form label-width="90px">
                <el-form-item label="分组名称" required>
                    <el-input v-model="form.name" maxlength="32" show-word-limit placeholder="如：热门、纯音乐" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="showForm = false">取消</el-button>
                <el-button type="primary" :loading="saving" @click="handleSave">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
import {
    deleteSystemMusicGroup,
    getSystemMusicGroupLists,
    saveSystemMusicGroup,
    updateSystemMusicGroupSort,
} from "@/api/ai_application/digital_human/system_music";
import feedback from "@/utils/feedback";
import { usePaging } from "@/hooks/usePaging";
import { useAuth } from "@/hooks/useAuth";

const { hasAuth } = useAuth();

const queryParams = reactive({
    name: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getSystemMusicGroupLists,
    params: queryParams,
});

const showForm = ref(false);
const saving = ref(false);
const form = reactive({ id: 0, name: "" });

const resetForm = () => {
    form.id = 0;
    form.name = "";
};

const openAdd = () => {
    resetForm();
    showForm.value = true;
};

const openEdit = (row: any) => {
    form.id = Number(row.id) || 0;
    form.name = row.name || "";
    showForm.value = true;
};

const handleSave = async () => {
    const name = form.name.trim();
    if (!name) {
        feedback.msgError("请输入分组名称");
        return;
    }
    saving.value = true;
    try {
        await saveSystemMusicGroup({ id: form.id || undefined, name });
        feedback.msgSuccess(form.id ? "保存成功" : "添加成功");
        showForm.value = false;
        // 新增后总量变了，回到第一页也能看到；编辑则留在当前页
        form.id ? getLists() : resetPage();
    } finally {
        saving.value = false;
    }
};

const handleDelete = async (row: any) => {
    const tip =
        row.music_count > 0
            ? `确定删除「${row.name}」吗？组内 ${row.music_count} 首曲目会退回「无分组」（只删分组，不删歌），用户端该 tab 立即消失。`
            : `确定删除「${row.name}」吗？删除后用户端该 tab 立即消失。`;
    await feedback.confirm(tip);
    await deleteSystemMusicGroup({ id: Number(row.id) });
    feedback.msgSuccess("删除成功");
    // 删掉当前页最后一条时回到上一页更符合预期
    if (pager.lists.length === 1 && pager.page > 1) {
        pager.page -= 1;
    }
    getLists();
};

const handleSort = async (row: any, val: number | undefined) => {
    if (val === undefined || val === null) return;
    await updateSystemMusicGroupSort({ id: Number(row.id), sort: val });
    feedback.msgSuccess("排序已更新");
    getLists();
};

getLists();
</script>
