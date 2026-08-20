<template>
    <div class="team-manage">
        <el-card class="!border-none" shadow="never">
            <div class="flex justify-between items-start gap-4 mb-4">
                <div>
                    <div class="text-lg font-bold">团队列表</div>
                    <div class="text-tx-secondary text-sm mt-1">管理全部团队；创建受全局授权名额限制。</div>
                </div>
                <div class="flex items-center gap-6 shrink-0">
                    <div class="text-right">
                        <div class="text-sm text-tx-secondary">OEM剩余名额</div>
                        <div class="text-2xl font-bold text-primary leading-tight mt-1">
                            {{ teamInfo.balance < 0 ? '不限' : teamInfo.balance }}
                        </div>
                        <div class="text-xs text-tx-secondary mt-1">
                            已用 {{ teamInfo.useauthnum }} / 总额 {{ teamInfo.authnum }}
                        </div>
                    </div>
                    <el-button v-perms="['team.team/create']" type="primary" @click="handleCreate">
                        创建团队
                    </el-button>
                </div>
            </div>
            <!-- 搜索 -->
            <el-form class="mb-[-16px]" :model="queryParams" inline>
                <el-form-item label="团队名称">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.name"
                        placeholder="请输入团队名称"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="团队主">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.owner"
                        placeholder="昵称/手机号"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="站点域名">
                    <el-input
                        class="w-[180px]"
                        v-model="queryParams.domain"
                        placeholder="请输入站点域名"
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
            <!-- 团队列表(全部团队) -->
            <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="团队名称" prop="name" min-width="140" />
                <el-table-column label="版本" width="110">
                    <template #default="{ row }">
                        <el-tag :type="row.oem_status === 2 ? 'success' : row.oem_status === 1 ? 'warning' : 'info'" effect="light">
                            {{ row.oem_status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="站点标题" prop="site_title" min-width="140" show-overflow-tooltip />
                <el-table-column label="站点域名" prop="domain" min-width="180" show-overflow-tooltip />
                <el-table-column label="团队主" min-width="160">
                    <template #default="{ row }">
                        <span>{{ row.owner_name || '-' }}</span>
                        <span class="text-gray-400 ml-1">{{ row.owner_mobile }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="成员数 / 坐席" min-width="120">
                    <template #default="{ row }">
                        {{ row.member_count }} / {{ row.seat_limit }}
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-model="row.status"
                            :active-value="1"
                            :inactive-value="0"
                            @change="changeStatus(row)" />
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" min-width="170" />
                <el-table-column label="操作" width="360" fixed="right">
                    <template #default="{ row }">
                        <!-- 免费版：站长可直接开通 OEM(不扣算力)，开通后进设置配域名 -->
                        <el-button
                            v-if="row.oem_status === 0"
                            v-perms="['team.team/openOem']"
                            type="success"
                            link
                            @click="handleOpenOem(row)">
                            开通OEM
                        </el-button>
                        <!-- 待审核/已开通才有站点设置 -->
                        <el-button v-if="row.oem_status !== 0" type="primary" link @click="handleConfig(row)">进入设置</el-button>
                        <el-button type="primary" link @click="handleSetSeat(row)">设置坐席</el-button>
                        <el-button type="primary" link @click="handleWallet(row)">算力钱包</el-button>
                        <el-button v-if="row.oem_status !== 0" type="danger" link @click="handleCancelOem(row)">取消OEM</el-button>
                        <el-button v-perms="['team.team/delete']" type="danger" link @click="handleDelete(row)">删除</el-button>
                    </template>
                </el-table-column>
                <template #empty>
                    <el-empty description="暂无团队数据" />
                </template>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <!-- 设置坐席 -->
        <el-dialog v-model="seatVisible" title="设置坐席上限" width="380px">
            <el-form label-width="90px">
                <el-form-item label="团队">{{ current?.name }}</el-form-item>
                <el-form-item label="当前成员">{{ current?.member_count }}</el-form-item>
                <el-form-item label="坐席上限">
                    <el-input-number v-model="seatValue" :min="current?.member_count || 1" :max="9999" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="seatVisible = false">取消</el-button>
                <el-button type="primary" @click="submitSeat">确定</el-button>
            </template>
        </el-dialog>

        <!-- 团队算力钱包 -->
        <el-dialog v-model="walletVisible" title="团队算力钱包" width="620px">
            <div v-if="walletData" class="mb-3 text-tx-secondary">
                团队主个人算力：<b class="text-primary">{{ walletData.owner_tokens }}</b>
                &nbsp;·&nbsp; 全体成员企业钱包合计：<b class="text-warning">{{ walletData.wallet_total }}</b>
            </div>
            <el-table :data="walletData?.members || []" size="large" max-height="420">
                <el-table-column label="成员" min-width="140">
                    <template #default="{ row }">
                        {{ row.nickname }}
                        <el-tag v-if="row.is_owner" type="success" size="small" class="ml-1">团队主</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="手机号" prop="mobile" min-width="120" />
                <el-table-column label="个人算力" prop="personal_tokens" min-width="110" />
                <el-table-column label="本企业钱包" prop="team_tokens" min-width="110" />
                <template #empty><el-empty description="暂无成员" /></template>
            </el-table>
        </el-dialog>
    </div>

    <!-- 创建团队 -->
    <edit-popup
        v-if="showEdit"
        ref="editPopupRef"
        @close="showEdit = false"
        @success="onCreateSuccess" />

    <!-- 团队设置 -->
    <config-popup
        v-if="showConfig"
        ref="configPopupRef"
        @close="showConfig = false"
        @success="getLists" />
</template>

<script lang="ts" setup name="teamManage">
import {
    teamLists,
    teamSetSeat,
    teamChangeStatus,
    teamCancelOem,
    teamOpenOem,
    teamWallet,
    teamDelete,
    teamGetInfo,
} from '@/api/team'
import { ElMessageBox } from 'element-plus'
import { usePaging } from '@/hooks/usePaging'
import feedback from '@/utils/feedback'
import EditPopup from './edit.vue'
import ConfigPopup from './config.vue'

const queryParams = reactive({
    name: '',
    owner: '',
    domain: '',
    start_time: '',
    end_time: '',
})

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: teamLists,
    params: queryParams,
})

const teamInfo = reactive({
    authnum: 0,
    useauthnum: 0,
    balance: 0,
})

const getTeamInfo = async () => {
    try {
        const res = await teamGetInfo()
        teamInfo.authnum = Number(res?.authnum ?? 0)
        teamInfo.useauthnum = Number(res?.useauthnum ?? 0)
        teamInfo.balance = Number(res?.balance ?? 0)
    } catch {
        // 无 getInfo 权限时忽略，不影响列表
    }
}

const showEdit = ref(false)
const editPopupRef = ref()

const showConfig = ref(false)
const configPopupRef = ref()

const seatVisible = ref(false)
const current = ref<any>(null)
const seatValue = ref(1)

const handleCreate = async () => {
    // authnum>0 时受名额限制；balance=-1 表示不限
    if (teamInfo.authnum > 0 && teamInfo.balance === 0) {
        await feedback.msgWarning('OEM剩余名额不足，无法创建')
        return
    }
    showEdit.value = true
    await nextTick()
    editPopupRef.value?.open()
}

const onCreateSuccess = async () => {
    await Promise.all([getLists(), getTeamInfo()])
}

const handleConfig = async (row: any) => {
    showConfig.value = true
    await nextTick()
    configPopupRef.value?.open(row.id)
}

const handleSetSeat = (row: any) => {
    current.value = row
    seatValue.value = row.seat_limit
    seatVisible.value = true
}

const submitSeat = async () => {
    await teamSetSeat({ id: current.value.id, seat_limit: seatValue.value })
    feedback.msgSuccess('设置成功')
    seatVisible.value = false
    getLists()
}

// 站长后台直接开通 OEM(不扣算力)，成功后跳转进入设置
const handleOpenOem = async (row: any) => {
    if (teamInfo.authnum > 0 && teamInfo.balance === 0) {
        await feedback.msgWarning('OEM剩余名额不足，无法开通')
        return
    }
    await feedback.confirm(
        `确定为团队「${row.name}」开通 OEM 吗？不扣团队主算力，开通后请配置域名与品牌。`
    )
    await teamOpenOem({ id: row.id })
    feedback.msgSuccess('开通成功，请配置域名与品牌')
    await Promise.all([getLists(), getTeamInfo()])
    await handleConfig(row)
}

// 强制取消 OEM(可选退预缴)
const handleCancelOem = async (row: any) => {
    const action = await ElMessageBox.confirm(
        `确定取消团队「${row.name}」的 OEM 吗？将清除 OEM 状态和绑定域名，站点立即失效。`,
        '取消 OEM',
        {
            type: 'warning',
            distinguishCancelAndClose: true,
            confirmButtonText: '取消OEM并退预缴',
            cancelButtonText: '取消OEM不退款',
        }
    ).then(() => 'refund').catch((a: string) => (a === 'cancel' ? 'norefund' : 'abort'))
    if (action === 'abort') return
    await teamCancelOem({ id: row.id, refund: action === 'refund' ? 1 : 0 })
    feedback.msgSuccess('已取消该团队 OEM')
    await Promise.all([getLists(), getTeamInfo()])
}

// 查看团队算力钱包
const walletVisible = ref(false)
const walletData = ref<any>(null)
const handleWallet = async (row: any) => {
    walletData.value = await teamWallet({ id: row.id })
    walletVisible.value = true
}

const changeStatus = async (row: any) => {
    try {
        await teamChangeStatus({ id: row.id, status: row.status })
    } finally {
        getLists()
    }
}

// 删除(解散)团队
const handleDelete = async (row: any) => {
    await feedback.confirm(
        `确定删除团队「${row.name}」吗？将解散团队、清除其智能体/知识库/品牌配置，未使用的企业算力退回团队主，操作不可恢复。`
    )
    await teamDelete({ id: row.id })
    feedback.msgSuccess('删除成功')
    await Promise.all([getLists(), getTeamInfo()])
}

onMounted(async () => {
    await Promise.all([getTeamInfo(), getLists()])
})
</script>

<style scoped></style>
