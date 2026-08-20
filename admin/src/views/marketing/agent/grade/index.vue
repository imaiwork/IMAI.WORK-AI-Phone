<template>
    <el-card shadow="never" class="!border-none">
        <div class="mb-4 flex items-center justify-between">
            <span class="text-sm text-tx-secondary">
                等级数字越小等级越高，新增等级会追加为最低一级；下级人数上限 0 表示不限。
            </span>
            <el-button v-perms="['marketing.agent.grade/addLevel']" type="primary" @click="handleAdd">
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                添加等级
            </el-button>
        </div>

        <el-table :data="gradeList" style="width: 100%" v-loading="loading">
            <el-table-column prop="level" label="代理等级" width="120">
                <template #default="{ row }">
                    <div>{{ row.level }}级</div>
                </template>
            </el-table-column>
            <el-table-column prop="name" label="等级名称" min-width="140" show-overflow-tooltip />
            <el-table-column prop="remark" label="备注" min-width="180" show-overflow-tooltip>
                <template #default="{ row }">
                    <span :class="{ 'text-tx-placeholder': !row.remark }">{{ row.remark || "-" }}</span>
                </template>
            </el-table-column>
            <el-table-column label="下级人数上限" min-width="240">
                <template #default="{ row }">
                    <div v-if="subLevelsOf(row.level).length" class="text-sm text-tx-secondary">
                        <span v-for="(item, index) in subLevelsOf(row.level)" :key="item.level">
                            <span v-if="index > 0"> · </span>
                            {{ item.name }}: <b>{{ formatLimit(limits[row.level]?.[item.level]) }}</b>
                        </span>
                    </div>
                    <div v-else class="text-xs text-tx-placeholder">最低一级，无法发展下级</div>
                </template>
            </el-table-column>
            <el-table-column label="操作" width="140" fixed="right">
                <template #default="{ row }">
                    <el-button type="primary" link @click="handleEdit(row)"> 编辑 </el-button>
                    <el-button
                        v-perms="['marketing.agent.grade/delLevel']"
                        :disabled="gradeList.length <= 1"
                        type="danger"
                        link
                        @click="handleDelete(row)">
                        删除
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
    </el-card>

    <EditPopup v-if="showEditPopup" ref="editPopupRef" @success="reloadAll" @close="showEditPopup = false" />
</template>

<script setup lang="ts">
import { delAgentGrade, getAgentGradeConfig, getAgentSubLimits } from "@/api/marketing/agent";
import feedback from "@/utils/feedback";
import EditPopup from "./edit.vue";

type AgentGrade = {
    level: number;
    name: string;
    remark: string;
};

const loading = ref(false);
const showEditPopup = ref(false);
const gradeList = ref<AgentGrade[]>([]);
const limits = ref<Record<string, Record<string, number>>>({});
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

// 比当前等级更低的所有等级，即该等级可以发展的下级类型
const subLevelsOf = (level: number) => gradeList.value.filter((item) => item.level > level);
const formatLimit = (value: any) => (Number(value) > 0 ? `${value} 人` : "不限");

const handleAdd = async () => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open("add", gradeList.value, limits.value);
};

const handleEdit = async (row: AgentGrade) => {
    showEditPopup.value = true;
    await nextTick();
    editPopupRef.value?.open("edit", gradeList.value, limits.value, row);
};

const handleDelete = async (row: AgentGrade) => {
    await feedback.confirm(`确定删除等级【${row.name}】吗？该等级下若已有代理用户将无法删除。`);
    await delAgentGrade({ level: row.level });
    reloadAll();
};

const reloadAll = async () => {
    loading.value = true;
    try {
        const [grades, subLimits] = await Promise.all([getAgentGradeConfig(), getAgentSubLimits()]);
        gradeList.value = (grades as AgentGrade[]) ?? [];
        limits.value = (subLimits as Record<string, Record<string, number>>) ?? {};
    } finally {
        loading.value = false;
    }
};

reloadAll();
</script>
