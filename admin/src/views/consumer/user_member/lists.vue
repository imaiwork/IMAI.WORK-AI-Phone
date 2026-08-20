<template>
    <el-card shadow="never" class="!border-none">
        <el-form :model="queryParams" :inline="true">
            <el-form-item label="用户信息">
                <el-input
                    v-model="queryParams.user_keyword"
                    placeholder="ID / 编号 / 昵称 / 手机号"
                    clearable
                    class="!w-[240px]" />
            </el-form-item>
            <el-form-item label="会员等级">
                <el-select v-model="queryParams.level_id" placeholder="全部" clearable class="!w-[180px]">
                    <el-option v-for="l in levels" :key="l.id" :label="l.name" :value="l.id" />
                </el-select>
            </el-form-item>
            <el-form-item label="状态">
                <el-select v-model="queryParams.status" placeholder="全部" clearable class="!w-[140px]">
                    <el-option label="有效" :value="1" />
                    <el-option label="已过期" :value="2" />
                    <el-option label="已取消" :value="3" />
                </el-select>
            </el-form-item>
            <el-form-item>
                <el-button type="primary" @click="resetPage">查询</el-button>
                <el-button @click="resetParams">重置</el-button>
            </el-form-item>
        </el-form>

        <el-table size="large" v-loading="pager.loading" :data="pager.lists" class="mt-2">
            <el-table-column label="用户" min-width="240">
                <template #default="{ row }">
                    <div class="flex items-center">
                        <el-avatar :size="40" :src="row.avatar" class="flex-none mr-2">
                            {{ (row.nickname || "?").slice(0, 1) }}
                        </el-avatar>
                        <div>
                            <div>{{ row.nickname || "—" }}</div>
                            <div class="text-xs text-gray-400">ID:{{ row.user_id }} · SN:{{ row.sn || "—" }}</div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="手机号" prop="mobile" min-width="140" />
            <el-table-column label="会员等级" min-width="130">
                <template #default="{ row }">
                    <el-tag type="primary" v-if="row.level_name">{{ row.level_name }}</el-tag>
                    <span v-else class="text-xs text-gray-400">—</span>
                </template>
            </el-table-column>
            <el-table-column label="开通日期" prop="start_time_str" min-width="170" />
            <el-table-column label="到期日期" prop="end_time_str" min-width="170" />
            <el-table-column label="状态" width="100">
                <template #default="{ row }">
                    <el-tag :type="statusType(row.status)">{{ statusLabel(row.status) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column label="操作" width="200" fixed="right">
                <template #default="{ row }">
                    <el-button type="primary" link @click="handleGrant(row)">更改等级 / 续期</el-button>
                    <el-button type="danger" link v-if="row.status == 1" @click="handleCancel(row)">取消</el-button>
                </template>
            </el-table-column>
        </el-table>

        <div class="flex justify-end mt-4">
            <pagination v-model="pager" @change="getLists" />
        </div>
    </el-card>

    <grant-popup v-if="showGrant" ref="grantRef" :levels="levels" @success="getLists" @close="showGrant = false" />
</template>

<script setup lang="ts">
import { getMemberUserList, getUserLevelList, grantMember, cancelMember } from "@/api/consumer";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import GrantPopup from "./grant.vue";

const queryParams = reactive({
    user_keyword: "",
    level_id: "",
    status: "",
});
const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getMemberUserList,
    params: queryParams,
});

const levels = ref<any[]>([]);
const loadLevels = async () => {
    const res: any = await getUserLevelList({ page_no: 1, page_size: 100 });
    levels.value = res?.lists ?? [];
};

const statusLabel = (s: number) => ({ 1: "有效", 2: "已过期", 3: "已取消" }[Number(s)] ?? "—");
const statusType = (s: number) => ({ 1: "success", 2: "info", 3: "warning" }[Number(s)] ?? "");

const showGrant = ref(false);
const grantRef = ref();
const handleGrant = async (row: any) => {
    showGrant.value = true;
    await nextTick();
    grantRef.value?.open(row);
};
const handleCancel = async (row: any) => {
    await feedback.confirm(`确定取消 ${row.nickname || row.mobile} 的会员?其超出免费配额的内容会被冻结`);
    await cancelMember({ user_id: row.user_id });
    getLists();
};

getLists();
loadLevels();
</script>
