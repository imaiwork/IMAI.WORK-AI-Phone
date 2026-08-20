<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="等级名称">
                    <el-input
                        class="w-[220px]"
                        v-model="queryParams.level_name"
                        placeholder="请输入等级名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>

                <el-form-item label="创建时间">
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
            <el-button v-perms="['user.user_level/add']" type="primary" @click="handleAdd">
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                新增会员等级
            </el-button>

            <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="等级名称" min-width="160">
                    <template #default="{ row }">
                        <span class="font-medium">{{ row.level_name }}</span>
                        <el-tag v-if="row.is_default == 1" type="warning" size="small" class="!ml-2">默认</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="赠送算力" min-width="140">
                    <template #default="{ row }">
                        <span class="font-medium">{{ row.grant_tokens }}</span>
                        <span class="text-xs text-gray-400 ml-1">/ {{ cycleLabel(row.grant_cycle) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="智能体" min-width="80">
                    <template #default="{ row }">{{ fmt(row.max_robots) }}</template>
                </el-table-column>
                <el-table-column label="知识库" min-width="80">
                    <template #default="{ row }">{{ fmt(row.max_knowledges) }}</template>
                </el-table-column>
                <el-table-column label="人设" min-width="60">
                    <template #default="{ row }">{{ fmt(row.max_personas) }}</template>
                </el-table-column>
                <el-table-column label="绑定手机" min-width="80">
                    <template #default="{ row }">{{ fmt(row.max_mobiles) }}</template>
                </el-table-column>
                <el-table-column label="数字人" min-width="80">
                    <template #default="{ row }">{{ fmt(row.max_digital_humans) }}</template>
                </el-table-column>
                <el-table-column label="音色" min-width="60">
                    <template #default="{ row }">{{ fmt(row.max_voices) }}</template>
                </el-table-column>
                <el-table-column label="可用模型" min-width="120">
                    <template #default="{ row }">
                        <span v-if="!modelNameList(row.allowed_models).length" class="text-xs text-gray-400">
                            全部
                        </span>
                        <el-popover
                            v-else
                            placement="top"
                            :width="320"
                            trigger="hover">
                            <template #reference>
                                <el-tag size="small" class="cursor-default">
                                    {{ modelNameList(row.allowed_models).length }} 个模型
                                </el-tag>
                            </template>
                            <div class="flex flex-wrap gap-1 max-h-[240px] overflow-y-auto">
                                <el-tag
                                    v-for="(m, idx) in modelNameList(row.allowed_models)"
                                    :key="`${m}-${idx}`"
                                    size="small">
                                    {{ m }}
                                </el-tag>
                            </div>
                        </el-popover>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" width="70" />
                <el-table-column label="状态" width="80">
                    <template #default="{ row }">
                        <el-tag :type="row.status == 1 ? 'success' : 'info'">
                            {{ row.status == 1 ? "启用" : "停用" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="140" fixed="right">
                    <template #default="{ row }">
                        <el-button v-perms="['user.user_level/edit']" type="primary" link @click="handleEdit(row)">
                            编辑
                        </el-button>
                        <el-button
                            v-if="row.is_default != 1"
                            v-perms="['user.user_level/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
                            删除
                        </el-button>
                        <el-tag v-else type="info" size="small">系统默认</el-tag>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
    <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
</template>

<script setup lang="ts">
import { getUserLevelList, deleteUserLevel } from "@/api/consumer";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

const queryParams = reactive({
    level_name: "",
    start_time: "",
    end_time: "",
});

const showEdit = ref(false);
const editRef = ref();
const { pager, getLists, resetPage, resetParams } = usePaging({ fetchFun: getUserLevelList, params: queryParams });

const cycleLabel = (n: number) => ({ 0: "不发放", 1: "每日", 2: "每月", 3: "每年" }[Number(n)] ?? "—");
const fmt = (n: number) => {
    const v = Number(n);
    if (v === -1) return "禁止";
    if (v === 0) return "不限";
    return v;
};

/** 兼容 allowed_models: { id: name } | string[] | JSON 字符串 */
const normalizeAllowedModels = (v: any): Record<string, string> => {
    if (!v) return {};
    if (typeof v === "string") {
        try {
            return normalizeAllowedModels(JSON.parse(v));
        } catch {
            return {};
        }
    }
    if (Array.isArray(v)) {
        const map: Record<string, string> = {};
        v.forEach((item) => {
            if (item == null || item === "") return;
            if (typeof item === "object") {
                const id = item.id ?? item.model_id;
                const name = item.name ?? item.label;
                if (id != null && name) map[String(id)] = String(name);
                return;
            }
            // 历史数组可能是 name 或 id
            map[String(item)] = String(item);
        });
        return map;
    }
    if (typeof v === "object") {
        const map: Record<string, string> = {};
        Object.entries(v).forEach(([id, name]) => {
            if (name == null || name === "") return;
            map[String(id)] = String(name);
        });
        return map;
    }
    return {};
};

const modelNameList = (v: any): string[] => Object.values(normalizeAllowedModels(v));

const handleAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("add");
};
const handleEdit = async (row: any) => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open("edit");
    // 编辑框按模型 id 多选，从 { id: name } 取 key
    editRef.value?.setFormData({
        ...row,
        allowed_models: Object.keys(normalizeAllowedModels(row.allowed_models)),
    });
};
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除该会员等级?");
    await deleteUserLevel({ id });
    getLists();
};

getLists();
</script>
