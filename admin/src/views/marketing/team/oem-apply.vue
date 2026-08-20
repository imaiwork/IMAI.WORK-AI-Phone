<template>
    <div class="team-oem-apply">
        <el-card class="!border-none" shadow="never">
            <div class="text-lg font-bold">OEM 申请审核</div>
            <div class="text-tx-secondary text-sm mt-1">
                团队主提交企业OEM升级后在此审核；通过后立即开通品牌能力，拒绝则全额退回预缴算力。
            </div>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="团队名称" prop="name" min-width="140" />
                <el-table-column label="团队主" min-width="160">
                    <template #default="{ row }">
                        <span>{{ row.owner_name || '-' }}</span>
                        <span class="text-gray-400 ml-1">{{ row.owner_mobile }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="预缴算力" min-width="110">
                    <template #default="{ row }">
                        <span class="text-amber-500 font-bold">{{ row.oem_pay_tokens }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="成员数 / 坐席" min-width="110">
                    <template #default="{ row }">
                        {{ row.member_count }} / {{ row.seat_limit }}
                    </template>
                </el-table-column>
                <el-table-column label="申请时间" prop="oem_apply_time_desc" min-width="160" />
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['team.team/oemReview']"
                            type="primary"
                            link
                            @click="handleReview(row, true)">
                            通过
                        </el-button>
                        <el-button
                            v-perms="['team.team/oemReview']"
                            type="danger"
                            link
                            @click="handleReview(row, false)">
                            拒绝
                        </el-button>
                    </template>
                </el-table-column>
                <template #empty>
                    <el-empty description="暂无待审核申请" />
                </template>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="teamOemApply">
import { teamLists, teamOemReview } from '@/api/team'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'

// 仅拉取待审核团队(oem_status=1)
const { pager, getLists } = usePaging({
    fetchFun: teamLists,
    params: { oem_status: 1 },
})

const handleReview = async (row: any, approve: boolean) => {
    await feedback.confirm(
        approve
            ? `确定通过「${row.name}」的企业OEM申请吗？通过后立即开通品牌能力。`
            : `确定拒绝「${row.name}」的申请吗？预缴的 ${row.oem_pay_tokens} 算力将全额退回团队主。`
    )
    await teamOemReview({ id: row.id, approve: approve ? 1 : 0 })
    feedback.msgSuccess(approve ? '已通过并开通' : '已拒绝并退款')
    getLists()
}

onMounted(() => {
    getLists()
})
</script>

<style scoped></style>
